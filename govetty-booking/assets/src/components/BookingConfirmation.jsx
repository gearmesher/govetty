import React from 'react';
import { Shell, PROGRESS_BY_STATE } from './shared/UI';

export function SlotRequestStatus({ slot, petName }) {
  return (
    <Shell
      progress={PROGRESS_BY_STATE.bookingConfirmation}
      eyebrow="Slot response received"
      title="Your appointment is not confirmed"
    >
      {petName && <p className="gv-subtitle">Consultation for {petName}.</p>}
      {slot && (
        <div className="gv-summary">
          <div className="gv-summary-row">
            <span>Selected date</span>
            <span>{slot.date || 'Not provided'}</span>
          </div>
          <div className="gv-summary-row">
            <span>Selected time</span>
            <span>
              {slot.time_start || 'Not provided'}
              {slot.time_end ? `–${slot.time_end}` : ''}
            </span>
          </div>
          {slot.veterinary_name && (
            <div className="gv-summary-row">
              <span>Vet</span>
              <span>{slot.veterinary_name}</span>
            </div>
          )}
        </div>
      )}
      <p className="gv-subtitle" style={{ marginBottom: 0 }}>
        The API response validated the slot details, but the documentation does not confirm that an appointment is
        saved. No consultation was deducted by this flow. Please contact GoVetty to complete scheduling.
      </p>
    </Shell>
  );
}

export function BookingCompleteNoCall() {
  return (
    <Shell
      progress={PROGRESS_BY_STATE.bookingCompleteNoCall}
      eyebrow="Checkout return received"
      title="Check your account for payment status"
    >
      <p className="gv-subtitle" style={{ marginBottom: 0 }}>
        This site cannot independently confirm whether the payment completed. Check your account before starting
        another checkout. You can schedule a call from your Personal Area when ready.
      </p>
    </Shell>
  );
}
