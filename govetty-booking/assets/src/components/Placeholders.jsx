import React from 'react';
import { Shell, Button, PROGRESS_BY_STATE } from './shared/UI';

/**
 * Stand-ins for screens that belong to other parts of the flow and aren't
 * built yet (client update, Oct 2026):
 *   - Slot Selection and Appointment Confirmation -> Marpet Video
 *   - Payment                                     -> the payment page
 * Each one only needs to hand control back to the booking machine, so the real
 * screen can replace it later without touching the routing in
 * bookingMachine.js. The buttons exist purely so the flow can be walked through.
 */

function PlaceholderNote({ children }) {
  return <p className="gv-placeholder-note">{children}</p>;
}

export function SlotSelectionPlaceholder({ onContinue }) {
  return (
    <Shell
      progress={PROGRESS_BY_STATE.slotSelection}
      eyebrow="Pick a time"
      title="Choose an available slot"
      subtitle="Available consultation times will be shown here."
    >
      <PlaceholderNote>Placeholder &mdash; time-slot selection (Marpet Video) is not connected yet.</PlaceholderNote>
      <Button onClick={onContinue}>Continue</Button>
    </Shell>
  );
}

export function PaymentPlaceholder({ plan, onPaid, onBack }) {
  return (
    <Shell progress={PROGRESS_BY_STATE.paymentPlaceholder} eyebrow="Payment" title="Complete your payment" onBack={onBack}>
      {plan && (
        <div className="gv-summary">
          <div className="gv-summary-row">
            <span>Plan</span>
            <span>{plan.plan_key}</span>
          </div>
          <div className="gv-summary-row">
            <span>Price</span>
            <span>${(plan.price / 100).toFixed(2)}</span>
          </div>
        </div>
      )}
      <PlaceholderNote>
        Placeholder &mdash; the payment page is not connected yet. No payment is taken here.
      </PlaceholderNote>
      <Button onClick={onPaid}>Continue (simulate paid)</Button>
    </Shell>
  );
}

export function AppointmentConfirmationPlaceholder({ petName, paid }) {
  return (
    <Shell
      progress={PROGRESS_BY_STATE.appointmentConfirmation}
      eyebrow={paid ? 'Payment complete' : "You're all set"}
      title="Appointment confirmation"
    >
      {petName && <p className="gv-subtitle">Consultation for {petName}.</p>}
      <PlaceholderNote>
        Placeholder &mdash; appointment confirmation (Marpet Video) is not connected yet. No appointment is booked and
        no call is deducted.
      </PlaceholderNote>
    </Shell>
  );
}
