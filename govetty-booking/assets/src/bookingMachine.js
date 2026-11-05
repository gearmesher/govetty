import { setup, assign, fromPromise } from 'xstate';
import { api, saveContinuation } from './api';

/**
 * The header's [govetty_login] shortcode appends ?govetty_intent=login to
 * the booking-page URL it links to (see class-govetty-shortcodes.php) so
 * this machine can tell "clicked the header's login control" apart from
 * "clicked book a call" -- both land on the same page/shortcode. Read once
 * at module load (this bundle is freshly loaded on every page navigation,
 * there's no client-side routing here), same timing index.jsx already
 * relies on for its own govetty_step check.
 */
function readRequestedPetId() {
  // The Personal Area's per-pet "Book" button links to the booking page with
  // ?govetty_pet=<id>, meaning "book this pet" -- skip pet selection.
  if (typeof window === 'undefined' || !window.location) return null;
  try {
    return new URLSearchParams(window.location.search).get('govetty_pet') || null;
  } catch (e) {
    return null;
  }
}

function readLoginOnly() {
  if (typeof window === 'undefined' || !window.location) return false;
  try {
    return new URLSearchParams(window.location.search).get('govetty_intent') === 'login';
  } catch (e) {
    return false;
  }
}

/**
 * This machine's states map 1:1 onto the Figma board's nodes, and its
 * guarded transitions map onto the board's diamonds. Keeping that
 * correspondence exact is deliberate: when the client requests a flow
 * change, the diff should show up as a diff in this file's states/
 * transitions, not as a rewrite of scattered if/else logic.
 *
 * Board node               -> machine state
 * ------------------------------------------------------------
 * Enter Phone Number        -> phoneEntry
 * OTP Sent                  -> otpSending
 * Enter OTP                 -> otpEntry (+ 60s cooldown child states)
 * OTP Verified?             -> otpVerifying (guards pick the next state)
 * Customer Status           -> guards inside otpVerifying's onDone
 * Registration Form         -> registration
 * Pet Selection             -> petSelection
 * Plan Selection             -> planSelection
 * Credit Card Payment       -> checkingOut / redirectingToStripe
 * Schedule Immediate Call?  -> scheduleImmediateCall
 * Slot Selection             -> slotSelection
 * Booking Confirmation      -> bookingConfirmation / bookingCompleteNoCall
 *
 * Not on the original board: header "Login" click (govetty_intent=login) by
 * an existing customer -> redirectingToAccount, a client-requested shortcut
 * so logging in from the site header goes straight to the Personal Area
 * dashboard instead of into this booking flow.
 *
 * Flow -- the selected pet's balance determines whether payment is needed:
 *
 *   Login (OTP) -> Pet Selection
 *     pet has >= 1 call:  -> Slot Selection -> provisional slot response
 *     pet has 0 calls:    -> Plan Selection -> Stripe Checkout
 *                         -> Slot Selection -> provisional slot response
 *
 * The API currently returns dummy availability and /slot/book only validates
 * the request; the UI must not represent that response as a confirmed booking.
 */

const machine = setup({
  actors: {
    startOtp: fromPromise(({ input }) => api.otpStart(input.phone)),
    verifyOtp: fromPromise(({ input }) => api.otpVerify(input.phone, input.code)),
    loadSession: fromPromise(() => api.infoUser()),
    loadBreeds: fromPromise(() => api.infoBreeds()),
    submitRegistration: fromPromise(({ input }) => api.register(input.payload)),
    loadPlans: fromPromise(({ input }) => api.billingPlans(input.petId)),
    loadSlots: fromPromise(({ input }) => api.slotAvailable(input.petCount)),
    bookSlot: fromPromise(({ input }) => api.slotBook(input.petId, input.slotId)),
    createCheckout: fromPromise(({ input }) => api.billingCheckout(input.planKey, [input.petId])),
  },
  guards: {
    otpStartFailed: ({ event }) => event.output.status !== 200,
    isNewCustomer: ({ event }) => event.output.data.customer === null,
    isPostRegistrationPurpose: ({ context }) => context.otpPurpose === 'postRegistration',
    canResend: ({ context }) => context.canResend,
    isLoginOnly: ({ context }) => context.loginOnly,
    // Existing session, arrived from a pet's "Book" button, and that pet is
    // really on this account (never trust the URL alone).
    hasRequestedPetWithBalance: ({ context, event }) => {
      const pet = (event.output.data.pets || []).find((p) => String(p.id) === String(context.requestedPetId));
      return !!pet && pet.token_amount > 0;
    },
    hasRequestedPetWithoutBalance: ({ context, event }) => {
      const pet = (event.output.data.pets || []).find((p) => String(p.id) === String(context.requestedPetId));
      return !!pet && !(pet.token_amount > 0);
    },
    selectedEventPetHasBalance: ({ context, event }) => {
      const pet = context.pets.find((p) => String(p.id) === String(event.petId));
      return !!pet && pet.token_amount > 0;
    },
    slotLoadFailed: ({ event }) =>
      !event.output.ok || !Array.isArray(event.output.data.slots) || event.output.data.status === 'error',
    slotBookFailed: ({ event }) => !event.output.ok || event.output.data.status === 'error',
    checkoutFailed: ({ event }) => !event.output.ok || !event.output.data.checkout_url,
    // Existing-session check (page load while already logged in).
    noActiveSession: ({ event }) => event.output.status !== 200 || !event.output.data.customer,
  },
}).createMachine({
  id: 'booking',
  initial: 'checkingSession',
  context: {
    phone: '',
    code: '',
    otpPurpose: 'login', // 'login' | 'postRegistration'
    loginOnly: readLoginOnly(),
    requestedPetId: readRequestedPetId(),
    otpSentAt: null,
    canResend: false,
    otpError: null,
    otpAttemptsRemaining: 5,
    customer: null,
    pets: [],
    selectedPetId: null,
    selectedPlanKey: null,
    selectedSlotId: null,
    flowVariant: null, // 'newCustomer' | 'existingHasBalance' | 'existingNoBalance'
    breeds: { dog_breeds: [], cat_breeds: [] },
    registrationError: null,
    plans: [],
    plansStatus: 'idle',
    plansError: null,
    checkoutError: null,
    slots: [],
    slotsLoading: false,
    slotError: null,
    bookingError: null,
  },
  states: {
    // Already logged in (valid customer_token cookie) and clicking "Talk to a
    // Vet Now"? Skip the phone/OTP steps and route by call balance. Anyone
    // without a session gets a quick 401 from our own route (no upstream
    // call) and lands on phone entry as before.
    checkingSession: {
      invoke: {
        src: 'loadSession',
        onDone: [
          { guard: 'noActiveSession', target: 'phoneEntry' },
          {
            guard: 'isLoginOnly',
            target: 'redirectingToAccount',
            actions: () => {
              window.location.href =
                (typeof window.GovettyBooking !== 'undefined' && window.GovettyBooking.personalAreaUrl) || '/';
            },
          },
          {
            // From a pet's "Book" button in the Personal Area: that pet is
            // already chosen; only pets with an available call skip payment.
            guard: 'hasRequestedPetWithBalance',
            target: 'slotSelection',
            actions: assign(({ context, event }) => {
              const pet = event.output.data.pets.find((p) => String(p.id) === String(context.requestedPetId));
              return {
                customer: event.output.data.customer,
                pets: event.output.data.pets,
                selectedPetId: pet.id,
                flowVariant: pet.token_amount > 0 ? 'existingHasBalance' : 'existingNoBalance',
              };
            }),
          },
          {
            guard: 'hasRequestedPetWithoutBalance',
            target: 'planSelection',
            actions: assign(({ context, event }) => {
              const pet = event.output.data.pets.find((p) => String(p.id) === String(context.requestedPetId));
              return {
                customer: event.output.data.customer,
                pets: event.output.data.pets,
                selectedPetId: pet.id,
                flowVariant: 'existingNoBalance',
              };
            }),
          },
          {
            target: 'petSelection',
            actions: assign({
              customer: ({ event }) => event.output.data.customer,
              pets: ({ event }) => event.output.data.pets,
            }),
          },
        ],
        onError: { target: 'phoneEntry' },
      },
    },

    phoneEntry: {
      on: {
        SUBMIT_PHONE: {
          target: 'otpSending',
          actions: assign({ phone: ({ event }) => event.phone, otpPurpose: 'login' }),
        },
      },
    },

    otpSending: {
      invoke: {
        src: 'startOtp',
        input: ({ context }) => ({ phone: context.phone }),
        onDone: [
          {
            guard: 'otpStartFailed',
            target: 'phoneEntry',
            actions: assign({ otpError: ({ event }) => event.output.data.message || 'send_failed' }),
          },
          {
            target: 'otpEntry',
            actions: assign({
              otpError: null,
              otpSentAt: () => Date.now(),
              otpAttemptsRemaining: 5,
            }),
          },
        ],
        onError: { target: 'phoneEntry', actions: assign({ otpError: 'send_failed' }) },
      },
    },

    otpEntry: {
      initial: 'cooldown',
      states: {
        cooldown: {
          entry: assign({ canResend: false }),
          after: { 60000: 'ready' },
        },
        ready: {
          entry: assign({ canResend: true }),
        },
      },
      on: {
        SUBMIT_CODE: {
          target: 'otpVerifying',
          actions: assign({ code: ({ event }) => event.code }),
        },
        RESEND: {
          target: 'otpSending',
          guard: 'canResend',
        },
        EDIT_PHONE: 'phoneEntry',
      },
    },

    otpVerifying: {
      invoke: {
        src: 'verifyOtp',
        input: ({ context }) => ({ phone: context.phone, code: context.code }),
        onDone: [
          {
            // The API returns one combined `invalid_or_expired` for both a
            // wrong code and an expired one -- confirmed by the backend
            // team, there is no separate distinction to surface. This
            // deliberately does NOT try to split "wrong code" vs "expired
            // code" into different messages; too_many_attempts is the only
            // other real, distinct code the API returns here.
            guard: ({ event }) => event.output.status !== 200,
            target: 'otpEntry.cooldown',
            actions: assign({
              otpError: ({ event }) =>
                event.output.data.message === 'too_many_attempts' ||
                event.output.data.attempts_remaining === 0
                  ? 'too_many_attempts'
                  : 'invalid_or_expired',
              otpAttemptsRemaining: ({ context, event }) =>
                event.output.data.message === 'too_many_attempts'
                  ? 0
                  : Number.isInteger(event.output.data.attempts_remaining)
                    ? Math.max(0, event.output.data.attempts_remaining)
                    : Math.max(0, context.otpAttemptsRemaining - 1),
            }),
          },
          {
            guard: 'isPostRegistrationPurpose',
            target: 'planSelection',
            actions: assign({
              customer: ({ event }) => event.output.data.customer,
              pets: ({ event }) => event.output.data.pets,
              selectedPetId: ({ event }) => event.output.data.pets[0]?.id ?? null,
              flowVariant: 'newCustomer',
              otpError: null,
              otpAttemptsRemaining: 5,
            }),
          },
          {
            guard: 'isNewCustomer',
            target: 'registration',
            actions: assign({ otpError: null }),
          },
          {
            // Header "Login" click (govetty_intent=login) by a recognized,
            // already-registered customer -- the client wants this to land
            // on the Personal Area dashboard directly, not continue into
            // pet/plan selection. A phone that turns out to be unregistered
            // still falls into isNewCustomer above regardless of loginOnly,
            // since there's no dashboard to send them to yet.
            guard: 'isLoginOnly',
            target: 'redirectingToAccount',
            actions: [
              assign({
                customer: ({ event }) => event.output.data.customer,
                pets: ({ event }) => event.output.data.pets,
                otpError: null,
                otpAttemptsRemaining: 5,
              }),
              () => {
                window.location.href =
                  (typeof window.GovettyBooking !== 'undefined' && window.GovettyBooking.personalAreaUrl) || '/';
              },
            ],
          },
          {
            target: 'petSelection',
            actions: assign({
              customer: ({ event }) => event.output.data.customer,
              pets: ({ event }) => event.output.data.pets,
              otpError: null,
              otpAttemptsRemaining: 5,
            }),
          },
        ],
        onError: { target: 'otpEntry.cooldown', actions: assign({ otpError: 'send_failed' }) },
      },
    },

    // -----------------------------------------------------------------
    // New customer
    // -----------------------------------------------------------------

    registration: {
      initial: 'loadingBreeds',
      states: {
        loadingBreeds: {
          invoke: {
            src: 'loadBreeds',
            onDone: {
              target: 'form',
              actions: assign({ breeds: ({ event }) => event.output.data }),
            },
            onError: 'form',
          },
        },
        form: {
          on: {
            SUBMIT_REGISTRATION: '#booking.registering',
          },
        },
      },
    },

    registering: {
      invoke: {
        src: 'submitRegistration',
        input: ({ event }) => ({ payload: event.payload }),
        onDone: [
          {
            guard: ({ event }) => event.output.status !== 200,
            target: 'registration.form',
            actions: assign({
              registrationError: ({ event }) => event.output.data.message || event.output.data.error,
            }),
          },
          {
            // TODO(temporary-workaround): registered, but /register doesn't
            // hand back a customer_token (see plugin README, gap #1) --
            // re-run the OTP flow silently for the same phone to obtain
            // one before continuing. The client has explicitly confirmed
            // this is a temporary state, not final architecture -- remove
            // this extra round-trip once /register (or an equivalent
            // endpoint) returns a token directly.
            target: 'otpSending',
            actions: assign({ otpPurpose: 'postRegistration', registrationError: null }),
          },
        ],
        onError: {
          target: 'registration.form',
          actions: assign({ registrationError: 'server_error' }),
        },
      },
    },

    // -----------------------------------------------------------------
    // Existing customer
    // -----------------------------------------------------------------

    petSelection: {
      on: {
        SELECT_PET: [
          {
            guard: 'selectedEventPetHasBalance',
            target: 'slotSelection',
            actions: assign(({ event }) => ({
              selectedPetId: event.petId,
              flowVariant: 'existingHasBalance',
            })),
          },
          {
            target: 'planSelection',
            actions: assign(({ event }) => ({
              selectedPetId: event.petId,
              flowVariant: 'existingNoBalance',
            })),
          },
        ],
      },
    },

    slotSelection: {
      entry: assign({ slotsLoading: true, slotError: null }),
      invoke: {
        src: 'loadSlots',
        input: () => ({ petCount: 1 }),
        onDone: [
          {
            guard: 'slotLoadFailed',
            actions: assign({
              slots: [],
              slotsLoading: false,
              slotError: ({ event }) => event.output.data.message || 'slots_load_failed',
            }),
          },
          {
            actions: assign({
              slots: ({ event }) => event.output.data.slots,
              slotsLoading: false,
              slotError: null,
            }),
          },
        ],
        onError: {
          actions: assign({ slots: [], slotsLoading: false, slotError: 'slots_load_failed' }),
        },
      },
      on: {
        RETRY_SLOTS: {
          target: 'slotSelection',
          reenter: true,
          actions: assign({ bookingError: null }),
        },
        SELECT_SLOT: {
          target: 'booking',
          actions: assign({
            selectedSlotId: ({ event }) => event.slotId,
            bookingError: null,
          }),
        },
      },
    },

    booking: {
      invoke: {
        src: 'bookSlot',
        input: ({ context }) => ({ petId: context.selectedPetId, slotId: context.selectedSlotId }),
        onDone: [
          {
            guard: 'slotBookFailed',
            target: 'slotSelection',
            actions: assign({
              bookingError: ({ event }) => event.output.data.message || 'slot_book_failed',
            }),
          },
          { target: 'appointmentConfirmation' },
        ],
        onError: {
          target: 'slotSelection',
          actions: assign({ bookingError: 'slot_book_failed' }),
        },
      },
    },

    // -----------------------------------------------------------------
    // Plan + payment (shared by newCustomer and existingNoBalance)
    // -----------------------------------------------------------------

    planSelection: {
      // plansStatus is what the screen actually renders from. It used to key
      // off "are we in this state" alone, which is true for the whole time
      // the screen is up -- so a failed or empty /billing/getPlans response
      // left "Loading plans..." on screen forever instead of an error.
      entry: assign({ plansStatus: 'loading', plansError: null }),
      invoke: {
        src: 'loadPlans',
        input: ({ context }) => ({ petId: context.selectedPetId }),
        onDone: [
          {
            guard: ({ event }) => event.output.status !== 200 || !Array.isArray(event.output.data.plans),
            actions: assign({
              plansStatus: 'error',
              plansError: ({ event }) => event.output.data.message || event.output.data.error || 'plans_load_failed',
            }),
          },
          {
            actions: assign({
              plans: ({ event }) => event.output.data.plans,
              plansStatus: 'ready',
            }),
          },
        ],
        onError: {
          actions: assign({ plansStatus: 'error', plansError: 'plans_load_failed' }),
        },
      },
      on: {
        RETRY_PLANS: {
          target: 'planSelection',
          reenter: true,
          actions: assign({ checkoutError: null }),
        },
        SELECT_PLAN: {
          target: 'checkingOut',
          actions: assign({
            selectedPlanKey: ({ event }) => event.planKey,
            checkoutError: null,
          }),
        },
      },
    },

    checkingOut: {
      invoke: {
        src: 'createCheckout',
        input: ({ context }) => ({ planKey: context.selectedPlanKey, petId: context.selectedPetId }),
        onDone: [
          {
            guard: 'checkoutFailed',
            target: 'planSelection',
            actions: assign({
              checkoutError: ({ event }) => event.output.data.message || 'checkout_session_failed',
            }),
          },
          {
            target: 'redirectingToStripe',
            actions: [
              ({ context }) =>
                saveContinuation({
                  selectedPetId: context.selectedPetId,
                  flowVariant: context.flowVariant,
                }),
              ({ event }) => {
                window.location.href = event.output.data.checkout_url;
              },
            ],
          },
        ],
        onError: {
          target: 'planSelection',
          actions: assign({ checkoutError: 'checkout_session_failed' }),
        },
      },
    },

    redirectingToStripe: { type: 'final' },

    redirectingToAccount: {
      // Terminal from the SPA's point of view -- same pattern as
      // redirectingToStripe above, but for the header-login shortcut: the
      // browser is about to do a full navigation to the Personal Area
      // dashboard (window.GovettyBooking.personalAreaUrl, localized from
      // Govetty_Rest_Routes::personal_area_page_url() in
      // govetty_booking_register_config()). The actual redirect happens in
      // otpVerifying's onDone action above; this state just gives the UI
      // something to render for the moment before that navigation lands.
      type: 'final',
    },

    appointmentConfirmation: { type: 'final' },
  },
});

export default machine;
