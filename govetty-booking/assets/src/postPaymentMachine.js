import { setup, assign, fromPromise } from 'xstate';
import { api } from './api';

/**
 * Stripe's hosted checkout means a full-page navigation away from the app
 * and back -- the original bookingMachine instance is gone by the time the
 * browser lands on the success_url. Rather than fight to rehydrate a whole
 * mid-flow XState snapshot across that boundary, this is a small, separate
 * machine that picks up exactly where the flow needs to continue, seeded
 * from the non-sensitive continuation data saved to sessionStorage before
 * the redirect (see api.js: saveContinuation / readContinuation).
 *
 * Resumes the slot chosen before Stripe checkout. Older continuation data
 * without a slot falls back to the legacy scheduling prompt.
 */
const postPaymentMachine = setup({
  actors: {
    loadSlots: fromPromise(({ input }) => api.slotAvailable(input.petCount)),
    bookSlot: fromPromise(({ input }) => api.slotBook(input.petId, input.slotId)),
  },
  guards: {
    slotLoadFailed: ({ event }) =>
      !event.output.ok || !Array.isArray(event.output.data.slots) || event.output.data.status === 'error',
    slotBookFailed: ({ event }) => !event.output.ok || event.output.data.status === 'error',
  },
}).createMachine({
  id: 'postPayment',
  context: ({ input }) => ({
    selectedPetId: input.selectedPetId,
    flowVariant: input.flowVariant,
    selectedSlot: input.selectedSlot,
    slotHoldExpiresAt: input.slotHoldExpiresAt,
    slots: [],
    selectedSlotId: null,
    slotError: null,
    bookingError: null,
    slotsLoading: false,
  }),
  initial: 'start',
  states: {
    start: {
      always: [
        { guard: ({ context }) => !context.selectedPetId, target: 'continuationMissing' },
        { guard: ({ context }) => !!context.selectedSlot, target: 'bookingConfirmation' },
        { target: 'scheduleImmediateCall' },
      ],
    },

    scheduleImmediateCall: {
      on: {
        CHOOSE_YES: 'slotSelection',
        CHOOSE_NO: 'bookingCompleteNoCall',
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
              slotError: ({ event }) =>
                !event.output.ok
                  ? `slot_http_${event.output.status}`
                  : event.output.data.message || 'slots_load_failed',
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
          actions: assign({ slots: [], slotsLoading: false, slotError: 'slot_network_error' }),
        },
      },
      on: {
        BACK_FROM_SLOTS: 'scheduleImmediateCall',
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
          { target: 'bookingConfirmation' },
        ],
        onError: {
          target: 'slotSelection',
          actions: assign({ bookingError: 'slot_book_failed' }),
        },
      },
    },

    continuationMissing: { type: 'final' },
    bookingConfirmation: {
      on: {
        BACK_TO_SLOTS: {
          target: 'slotSelection',
          actions: assign({ selectedSlotId: null }),
        },
      },
    },
    bookingCompleteNoCall: {
      on: {
        BACK_TO_SCHEDULE: 'scheduleImmediateCall',
      },
    },
  },
});

export default postPaymentMachine;
