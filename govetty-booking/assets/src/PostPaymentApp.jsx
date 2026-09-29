import React, { useEffect, useRef } from 'react';
import { useMachine } from '@xstate/react';
import postPaymentMachine from './postPaymentMachine';
import { api } from './api';

import ScheduleCallPrompt from './components/ScheduleCallPrompt';
import SlotSelection from './components/SlotSelection';
import { BookingConfirmation, BookingCompleteNoCall } from './components/BookingConfirmation';
import { Shell, Loading } from './components/shared/UI';

export default function PostPaymentApp({ continuation, checkoutRef }) {
  const [state, send] = useMachine(postPaymentMachine, {
    input: {
      selectedPetId: continuation?.selectedPetId ?? null,
      flowVariant: continuation?.flowVariant ?? 'existingNoBalance',
    },
  });
  const { context } = state;
  const confirmedRef = useRef(false);

  // Fire-and-forget: lets the admin's Subscriptions & Revenue log know the
  // browser came back via the success redirect. This is NOT payment
  // verification (no webhook exists to confirm that server-side) -- it
  // never blocks or affects the actual booking flow either way.
  useEffect(() => {
    if (checkoutRef && !confirmedRef.current) {
      confirmedRef.current = true;
      api.confirmReturn(checkoutRef, 'success');
    }
  }, [checkoutRef]);

  if (state.matches('scheduleImmediateCall')) {
    return (
      <ScheduleCallPrompt
        onYes={() => send({ type: 'CHOOSE_YES' })}
        onNo={() => send({ type: 'CHOOSE_NO' })}
      />
    );
  }

  if (state.matches('slotSelection') || state.matches('booking')) {
    return (
      <SlotSelection
        slots={context.slots}
        loading={state.matches('slotSelection')}
        booking={state.matches('booking')}
        error={context.slotError}
        onSelect={(slotId) => send({ type: 'SELECT_SLOT', slotId })}
      />
    );
  }

  if (state.matches('bookingConfirmation')) {
    const slot = context.slots.find((s) => s.slot_id === context.selectedSlotId);
    return <BookingConfirmation slot={slot} />;
  }

  if (state.matches('bookingCompleteNoCall')) {
    return <BookingCompleteNoCall />;
  }

  return (
    <Shell title="One moment">
      <Loading label="Confirming your payment…" />
    </Shell>
  );
}
