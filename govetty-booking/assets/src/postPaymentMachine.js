import { setup, assign, fromPromise } from 'xstate';
import { api } from './api';

/**
 * Stripe redirects destroy the original booking machine, so this machine
 * resumes the saved reservation and confirms it after checkout returns.
 */
const postPaymentMachine = setup({
  actors: {
    confirmCheckoutReturn: fromPromise(({ input }) => api.confirmReturn(input.checkoutRef, 'success')),
    loadSlots: fromPromise(({ input }) => api.slotAvailable(input.petCount)),
    reserveSlot: fromPromise(({ input }) => api.slotReserve(input.petId, input.slotId)),
    bookSlot: fromPromise(({ input }) => api.slotBook(input.petId, input.slotId)),
  },
  guards: {
    slotLoadFailed: ({ event }) =>
      !event.output.ok || !Array.isArray(event.output.data.slots) || event.output.data.status === 'error',
    slotReserveFailed: ({ event }) =>
      !event.output.ok ||
      event.output.data.status === 'error' ||
      !Number.isFinite(Number(event.output.data.slot_id)) ||
      Number(event.output.data.slot_id) <= 0,
    slotBookFailed: ({ event }) =>
      !event.output.ok || event.output.data.status === 'error' || !event.output.data.join_url,
  },
}).createMachine({
  id: 'postPayment',
  context: ({ input }) => ({
    selectedPetId: input.selectedPetId,
    flowVariant: input.flowVariant,
    selectedSlot: input.selectedSlot,
    reservedSlotId: input.reservedSlotId,
    checkoutRef: input.checkoutRef,
    slotHoldExpiresAt: input.slotHoldExpiresAt,
    slots: [],
    selectedSlotId: Number(input.reservedSlotId) || null,
    joinUrl: null,
    slotError: null,
    bookingError: null,
    slotsLoading: false,
  }),
  initial: 'start',
  states: {
    start: {
      always: [
        { guard: ({ context }) => !context.selectedPetId, target: 'continuationMissing' },
        { guard: ({ context }) => !!context.checkoutRef, target: 'confirmingCheckoutReturn' },
        { target: 'resumeBooking' },
      ],
    },

    confirmingCheckoutReturn: {
      invoke: {
        src: 'confirmCheckoutReturn',
        input: ({ context }) => ({ checkoutRef: context.checkoutRef }),
        onDone: 'resumeBooking',
        onError: 'resumeBooking',
      },
    },

    resumeBooking: {
      always: [
        { guard: ({ context }) => Number(context.reservedSlotId) > 0, target: 'booking' },
        { guard: ({ context }) => !!context.selectedSlot, target: 'reservingSlot' },
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
          target: 'reservingSlot',
          actions: assign({
            selectedSlotId: ({ event }) => event.slotId,
            selectedSlot: ({ context, event }) =>
              context.slots.find((slot) => String(slot.slot_id) === String(event.slotId)) || null,
            bookingError: null,
          }),
        },
      },
    },

    reservingSlot: {
      invoke: {
        src: 'reserveSlot',
        input: ({ context }) => ({ petId: context.selectedPetId, slotId: context.selectedSlotId }),
        onDone: [
          {
            guard: 'slotReserveFailed',
            target: 'slotSelection',
            actions: assign({
              bookingError: ({ event }) => event.output.data.message || 'slot_reserve_failed',
              selectedSlotId: null,
            }),
          },
          {
            target: 'booking',
            actions: assign(({ event }) => ({
              reservedSlotId: Number(event.output.data.slot_id),
              selectedSlotId: Number(event.output.data.slot_id),
            })),
          },
        ],
        onError: {
          target: 'slotSelection',
          actions: assign({ bookingError: 'slot_reserve_failed', selectedSlotId: null }),
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
              selectedSlotId: null,
              reservedSlotId: null,
            }),
          },
          {
            target: 'bookingConfirmation',
            actions: assign({ joinUrl: ({ event }) => event.output.data.join_url }),
          },
        ],
        onError: {
          target: 'slotSelection',
          actions: assign({ bookingError: 'slot_book_failed', selectedSlotId: null, reservedSlotId: null }),
        },
      },
    },

    continuationMissing: { type: 'final' },
    bookingConfirmation: {
      on: {
        BACK_TO_SLOTS: {
          target: 'slotSelection',
          actions: assign({ selectedSlotId: null, reservedSlotId: null, joinUrl: null }),
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
