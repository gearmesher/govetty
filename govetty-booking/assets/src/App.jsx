import React, { useEffect } from 'react';
import { useMachine } from '@xstate/react';
import machine from './bookingMachine';

import PhoneEntry from './components/PhoneEntry';
import OtpEntry from './components/OtpEntry';
import Registration from './components/Registration';
import PetSelection from './components/PetSelection';
import PlanSelection from './components/PlanSelection';
import SlotSelection from './components/SlotSelection';
import RedirectingToAccount from './components/RedirectingToAccount';
import RedirectingToStripe from './components/RedirectingToStripe';
import { SlotRequestStatus } from './components/BookingConfirmation';
import { Shell, Loading } from './components/shared/UI';

function goToPersonalArea() {
  const url =
    (typeof window.GovettyBooking !== 'undefined' && window.GovettyBooking.personalAreaUrl) || '/';
  window.location.assign(url);
}

export default function App() {
  const [state, send] = useMachine(machine);
  const { context } = state;

  useEffect(() => {
    if (context.customer && typeof window !== 'undefined') {
      window.dispatchEvent(new Event('govetty:session-changed'));
    }
  }, [context.customer]);

  if (state.matches('checkingSession')) {
    return (
      <Shell>
        <Loading label="One moment…" />
      </Shell>
    );
  }

  if (state.matches('phoneEntry') || state.matches('otpSending')) {
    return (
      <PhoneEntry
        onSubmit={(phone) => send({ type: 'SUBMIT_PHONE', phone })}
        loading={state.matches('otpSending')}
        error={context.otpError}
        loginOnly={context.loginOnly}
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
        attemptsRemaining={context.otpAttemptsRemaining}
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
        onBack={() => send({ type: 'BACK_TO_OTP' })}
        onSubmit={(payload) => send({ type: 'SUBMIT_REGISTRATION', payload })}
      />
    );
  }

  if (state.matches('petSelection')) {
    return (
      <PetSelection
        customer={context.customer}
        pets={context.pets}
        onSelect={(petId) => send({ type: 'SELECT_PET', petId })}
        onBack={goToPersonalArea}
      />
    );
  }

  if (state.matches('slotSelection') || state.matches('booking')) {
    return (
      <SlotSelection
        slots={context.slots}
        loading={context.slotsLoading}
        error={context.slotError || context.bookingError}
        booking={state.matches('booking')}
        onSelect={(slotId) => send({ type: 'SELECT_SLOT', slotId })}
        onRetry={() => send({ type: 'RETRY_SLOTS' })}
        onBack={() =>
          context.petSelectionSkipped
            ? goToPersonalArea()
            : send({ type: 'BACK_TO_PETS' })
        }
      />
    );
  }

  if (state.matches('planSelection') || state.matches('checkingOut')) {
    return (
      <PlanSelection
        plans={context.plans}
        plansStatus={context.plansStatus}
        plansError={context.plansError}
        error={context.checkoutError}
        checkoutLoading={state.matches('checkingOut')}
        onBack={() =>
          context.petSelectionSkipped
            ? goToPersonalArea()
            : send({ type: 'BACK_TO_PETS' })
        }
        onRetry={() => send({ type: 'RETRY_PLANS' })}
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

  if (state.matches('appointmentConfirmation')) {
    const pet = context.pets.find((p) => p.id === context.selectedPetId);
    const slot = context.slots.find((item) => String(item.slot_id) === String(context.selectedSlotId));
    return <SlotRequestStatus slot={slot} petName={pet && pet.name} onBack={() => send({ type: 'BACK_TO_SLOTS' })} />;
  }

  return (
    <Shell title="One moment">
      <Loading label="Loading…" />
    </Shell>
  );
}
