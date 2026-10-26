import React from 'react';
import { useMachine } from '@xstate/react';
import machine from './bookingMachine';

import PhoneEntry from './components/PhoneEntry';
import OtpEntry from './components/OtpEntry';
import Registration from './components/Registration';
import PetSelection from './components/PetSelection';
import PlanSelection from './components/PlanSelection';
import RedirectingToAccount from './components/RedirectingToAccount';
import { SlotSelectionPlaceholder, PaymentPlaceholder, AppointmentConfirmationPlaceholder } from './components/Placeholders';
import { Shell, Loading } from './components/shared/UI';

export default function App() {
  const [state, send] = useMachine(machine);
  const { context } = state;

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

  if (state.matches('slotSelection')) {
    return <SlotSelectionPlaceholder onContinue={() => send({ type: 'CONTINUE' })} />;
  }

  if (state.matches('planSelection')) {
    return (
      <PlanSelection
        plans={context.plans}
        plansStatus={context.plansStatus}
        plansError={context.plansError}
        onRetry={() => send({ type: 'RETRY_PLANS' })}
        onSelect={(planKey) => send({ type: 'SELECT_PLAN', planKey })}
      />
    );
  }

  if (state.matches('paymentPlaceholder')) {
    const plan = context.plans.find((p) => p.plan_key === context.selectedPlanKey);
    return (
      <PaymentPlaceholder
        plan={plan}
        onPaid={() => send({ type: 'PAYMENT_COMPLETE' })}
        onBack={() => send({ type: 'BACK_TO_PLANS' })}
      />
    );
  }

  if (state.matches('redirectingToAccount')) {
    return <RedirectingToAccount />;
  }

  if (state.matches('appointmentConfirmation')) {
    const pet = context.pets.find((p) => p.id === context.selectedPetId);
    const paid = context.flowVariant !== 'existingHasBalance';
    return <AppointmentConfirmationPlaceholder petName={pet && pet.name} paid={paid} />;
  }

  return (
    <Shell title="One moment">
      <Loading label="Loading…" />
    </Shell>
  );
}
