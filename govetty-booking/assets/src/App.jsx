import React from 'react';
import { useMachine } from '@xstate/react';
import machine from './bookingMachine';

import PhoneEntry from './components/PhoneEntry';
import OtpEntry from './components/OtpEntry';
import Registration from './components/Registration';
import PetSelection from './components/PetSelection';
import PlanSelection from './components/PlanSelection';
import RedirectingToStripe from './components/RedirectingToStripe';
import RedirectingToAccount from './components/RedirectingToAccount';
import SlotSelection from './components/SlotSelection';
import { BookingConfirmation, BookingCompleteNoCall } from './components/BookingConfirmation';
import { Shell, Loading } from './components/shared/UI';

export default function App() {
  const [state, send] = useMachine(machine);
  const { context } = state;

  if (state.matches('phoneEntry') || state.matches('otpSending')) {
    return (
      <PhoneEntry
        onSubmit={(phone) => send({ type: 'SUBMIT_PHONE', phone })}
        loading={state.matches('otpSending')}
        error={context.otpError}
      />
    );
  }

  if (state.matches('otpEntry') || state.matches('otpVerifying')) {
    return (
      <OtpEntry
        phone={context.phone}
        otpSentAt={context.otpSentAt}
        canResend={context.canResend}
        loading={state.matches('otpVerifying')}
        error={context.otpError}
        onSubmit={(code) => send({ type: 'SUBMIT_CODE', code })}
        onResend={() => send({ type: 'RESEND' })}
        onEditPhone={() => send({ type: 'EDIT_PHONE' })}
      />
    );
  }

  if (state.matches('registration') || state.matches('registering')) {
    return (
      <Registration
        phone={context.phone}
        breeds={context.breeds}
        loading={state.matches('registering')}
        error={context.registrationError}
        onSubmit={(payload) => send({ type: 'SUBMIT_REGISTRATION', payload })}
      />
    );
  }

  if (state.matches('petSelection')) {
    return <PetSelection customer={context.customer} pets={context.pets} onSelect={(petId) => send({ type: 'SELECT_PET', petId })} />;
  }

  if (state.matches('planSelection') || state.matches('checkingOut')) {
    return (
      <PlanSelection
        plans={context.plans}
        loading={state.matches('planSelection')}
        checkoutLoading={state.matches('checkingOut')}
        error={context.checkoutError}
        onSelect={(planKey) => send({ type: 'SELECT_PLAN', planKey })}
      />
    );
  }

  if (state.matches('redirectingToStripe')) {
    return <RedirectingToStripe />;
  }

  if (state.matches('redirectingToAccount')) {
    return <RedirectingToAccount />;
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
      <Loading label="Loading…" />
    </Shell>
  );
}
