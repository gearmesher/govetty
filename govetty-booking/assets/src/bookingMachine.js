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
 * One deliberate implementation choice, since the two written accounts of
 * the existing-customer branch differ slightly in emphasis: existing
 * customers always see Pet Selection first (a customer can have several
 * pets with different balances), and the balance check runs against
 * whichever pet they select there -- not against the customer as a whole.
 * That matches the fuller original description; the shorthand summary in
 * the follow-up email is consistent with it, just less explicit.
 */

const machine = setup({
  actors: {
    startOtp: fromPromise(({ input }) => api.otpStart(input.phone)),
    verifyOtp: fromPromise(({ input }) => api.otpVerify(input.phone, input.code)),
    loadBreeds: fromPromise(() => api.infoBreeds()),
    submitRegistration: fromPromise(({ input }) => api.register(input.payload)),
    loadPlans: fromPromise(({ input }) => api.billingPlans(input.petId)),
    startCheckout: fromPromise(({ input }) => api.billingCheckout(input.planKey, input.petIds)),
    loadSlots: fromPromise(({ input }) => api.slotAvailable(input.petCount)),
    bookSlot: fromPromise(({ input }) => api.slotBook(input.petId, input.slotId)),
  },
  guards: {
    isNewCustomer: ({ event }) => event.output.data.customer === null,
    isPostRegistrationPurpose: ({ context }) => context.otpPurpose === 'postRegistration',
    selectedPetHasBalance: ({ context }) => {
      const pet = context.pets.find((p) => p.id === context.selectedPetId);
      return !!pet && pet.token_amount > 0;
    },
    canResend: ({ context }) => context.canResend,
    isLoginOnly: ({ context }) => context.loginOnly,
  },
}).createMachine({
  id: 'booking',
  initial: 'phoneEntry',
  context: {
    phone: '',
    code: '',
    otpPurpose: 'login', // 'login' | 'postRegistration'
    loginOnly: readLoginOnly(),
    otpSentAt: null,
    canResend: false,
    otpError: null,
    customer: null,
    pets: [],
    selectedPetId: null,
    selectedPlanKey: null,
    selectedSlotId: null,
    flowVariant: null, // 'newCustomer' | 'existingHasBalance' | 'existingNoBalance'
    breeds: { dog_breeds: [], cat_breeds: [] },
    registrationError: null,
    plans: [],
    slots: [],
    slotError: null,
    checkoutError: null,
  },
  states: {
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
        onDone: {
          target: 'otpEntry',
          actions: assign({ otpError: null, otpSentAt: () => Date.now() }),
        },
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
                event.output.data.message === 'too_many_attempts'
                  ? 'too_many_attempts'
                  : 'invalid_or_expired',
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
            guard: 'selectedPetHasBalance',
            target: 'slotSelection',
            actions: assign({
              selectedPetId: ({ event }) => event.petId,
              flowVariant: 'existingHasBalance',
            }),
          },
          {
            target: 'planSelection',
            actions: assign({
              selectedPetId: ({ event }) => event.petId,
              flowVariant: 'existingNoBalance',
            }),
          },
        ],
      },
    },

    // -----------------------------------------------------------------
    // Plan + payment (shared by newCustomer and existingNoBalance)
    // -----------------------------------------------------------------

    planSelection: {
      invoke: {
        src: 'loadPlans',
        input: ({ context }) => ({ petId: context.selectedPetId }),
        onDone: {
          actions: assign({ plans: ({ event }) => event.output.data.plans || [] }),
        },
      },
      on: {
        SELECT_PLAN: {
          target: 'checkingOut',
          actions: assign({ selectedPlanKey: ({ event }) => event.planKey }),
        },
      },
    },

    checkingOut: {
      invoke: {
        src: 'startCheckout',
        input: ({ context }) => ({
          planKey: context.selectedPlanKey,
          petIds: [context.selectedPetId],
        }),
        onDone: [
          {
            guard: ({ event }) => event.output.status !== 200 || !event.output.data.checkout_url,
            target: 'planSelection',
            actions: assign({
              checkoutError: ({ event }) => event.output.data.message || 'checkout_session_failed',
            }),
          },
          {
            target: 'redirectingToStripe',
            actions: [
              assign({ checkoutError: null }),
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

    redirectingToStripe: {
      // Terminal from the SPA's point of view -- the browser is about to
      // do a full navigation to Stripe's hosted page. The app resumes on
      // the payment-success/payment-cancelled routes, see index.jsx.
      type: 'final',
    },

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

    // -----------------------------------------------------------------
    // Post-payment: new-customer branch asks first, existing-no-balance
    // goes straight to slots (per the agreed flow)
    // -----------------------------------------------------------------

    scheduleImmediateCall: {
      on: {
        CHOOSE_YES: 'slotSelection',
        CHOOSE_NO: 'bookingCompleteNoCall',
      },
    },

    slotSelection: {
      invoke: {
        src: 'loadSlots',
        input: () => ({ petCount: 1 }),
        onDone: {
          actions: assign({ slots: ({ event }) => event.output.data.slots || [] }),
        },
      },
      on: {
        SELECT_SLOT: {
          target: 'booking',
          actions: assign({ selectedSlotId: ({ event }) => event.slotId }),
        },
      },
    },

    booking: {
      invoke: {
        src: 'bookSlot',
        input: ({ context }) => ({ petId: context.selectedPetId, slotId: context.selectedSlotId }),
        onDone: [
          {
            guard: ({ event }) => event.output.status !== 200,
            target: 'slotSelection',
            actions: assign({ slotError: ({ event }) => event.output.data.message }),
          },
          { target: 'bookingConfirmation' },
        ],
        onError: {
          target: 'slotSelection',
          actions: assign({ slotError: 'server_error' }),
        },
      },
    },

    bookingConfirmation: { type: 'final' },
    bookingCompleteNoCall: { type: 'final' },
  },
});

export default machine;
