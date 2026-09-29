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
 * Mirrors the tail end of bookingMachine.js:
 *   Schedule Immediate Call? -> Slot Selection -> Booking Confirmation
 */
const postPaymentMachine = setup({
  actors: {
    loadSlots: fromPromise(({ input }) => api.slotAvailable(input.petCount)),
    bookSlot: fromPromise(({ input }) => api.slotBook(input.petId, input.slotId)),
  },
  guards: {
    isNewCustomer: ({ context }) => context.flowVariant === 'newCustomer',
  },
}).createMachine({
  id: 'postPayment',
  context: ({ input }) => ({
    selectedPetId: input.selectedPetId,
    flowVariant: input.flowVariant,
    slots: [],
    selectedSlotId: null,
    slotError: null,
  }),
  initial: 'start',
  states: {
    start: {
      always: [
        { guard: 'isNewCustomer', target: 'scheduleImmediateCall' },
        { target: 'slotSelection' },
      ],
    },

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

export default postPaymentMachine;
