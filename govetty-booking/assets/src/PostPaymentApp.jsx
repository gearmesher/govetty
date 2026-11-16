import React, { useEffect, useRef } from 'react';
import { useMachine } from '@xstate/react';
import postPaymentMachine from './postPaymentMachine';
import { api } from './api';

import ScheduleCallPrompt from './components/ScheduleCallPrompt';
import SlotSelection from './components/SlotSelection';
import { SlotRequestStatus, BookingCompleteNoCall } from './components/BookingConfirmation';
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
        loading={context.slotsLoading}
        booking={state.matches('booking')}
        error={context.slotError || context.bookingError}
        onSelect={(slotId) => send({ type: 'SELECT_SLOT', slotId })}
        onRetry={() => send({ type: 'RETRY_SLOTS' })}
        onBack={() => send({ type: 'BACK_FROM_SLOTS' })}
      />
    );
  }

  if (state.matches('bookingConfirmation')) {
    const slot = context.slots.find((s) => String(s.slot_id) === String(context.selectedSlotId));
    return <SlotRequestStatus slot={slot} onBack={() => send({ type: 'BACK_TO_SLOTS' })} />;
  }

  if (state.matches('bookingCompleteNoCall')) {
    return <BookingCompleteNoCall />;
  }

  if (state.matches('continuationMissing')) {
    return (
      <Shell title="We couldn't resume your appointment">
        <p className="gv-subtitle">
          Your checkout return could not be matched to a selected pet. Check your account for payment status, then
          contact GoVetty if you need help scheduling.
        </p>
      </Shell>
    );
  }

  return (
    <Shell title="One moment">
      <Loading label="Confirming your payment…" />
    </Shell>
  );
}
