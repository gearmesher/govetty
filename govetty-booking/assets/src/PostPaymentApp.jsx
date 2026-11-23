import React, { useEffect, useState } from 'react';
import { useMachine } from '@xstate/react';
import postPaymentMachine from './postPaymentMachine';

import ScheduleCallPrompt from './components/ScheduleCallPrompt';
import SlotSelection from './components/SlotSelection';
import { SlotRequestStatus, BookingCompleteNoCall } from './components/BookingConfirmation';
import { Shell, Loading } from './components/shared/UI';

export default function PostPaymentApp({ continuation, checkoutRef }) {
  const [state, send] = useMachine(postPaymentMachine, {
    input: {
      selectedPetId: continuation?.selectedPetId ?? null,
      flowVariant: continuation?.flowVariant ?? 'existingNoBalance',
      selectedSlot: continuation?.selectedSlot ?? null,
      reservedSlotId: continuation?.reservedSlotId ?? null,
      checkoutRef,
      slotHoldExpiresAt: continuation?.slotHoldExpiresAt ?? null,
    },
  });
  const { context } = state;
  const [holdExpired, setHoldExpired] = useState(
    Boolean(context.slotHoldExpiresAt && Date.now() >= context.slotHoldExpiresAt)
  );

  useEffect(() => {
    if (!context.slotHoldExpiresAt || holdExpired) return undefined;
    const remaining = context.slotHoldExpiresAt - Date.now();
    if (remaining <= 0) {
      setHoldExpired(true);
      return undefined;
    }
    const timeout = window.setTimeout(() => setHoldExpired(true), remaining);
    return () => window.clearTimeout(timeout);
  }, [context.slotHoldExpiresAt, holdExpired]);

  if (state.matches('scheduleImmediateCall')) {
    return (
      <ScheduleCallPrompt
        onYes={() => send({ type: 'CHOOSE_YES' })}
        onNo={() => send({ type: 'CHOOSE_NO' })}
      />
    );
  }

  if (state.matches('slotSelection') || state.matches('reservingSlot') || state.matches('booking')) {
    return (
      <SlotSelection
        slots={context.slots}
        loading={context.slotsLoading}
        booking={state.matches('reservingSlot') || state.matches('booking')}
        error={context.slotError || context.bookingError}
        onSelect={(slotId) => send({ type: 'SELECT_SLOT', slotId })}
        onRetry={() => send({ type: 'RETRY_SLOTS' })}
        onBack={() => send({ type: 'BACK_FROM_SLOTS' })}
      />
    );
  }

  if (state.matches('bookingConfirmation')) {
    return (
      <SlotRequestStatus
        slot={context.selectedSlot}
        joinUrl={context.joinUrl}
        paymentReturnReceived
        holdExpired={holdExpired}
      />
    );
  }

  if (state.matches('bookingCompleteNoCall')) {
    return <BookingCompleteNoCall onBack={() => send({ type: 'BACK_TO_SCHEDULE' })} />;
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
