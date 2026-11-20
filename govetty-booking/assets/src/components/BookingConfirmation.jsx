import React from 'react';
import { Shell, PROGRESS_BY_STATE } from './shared/UI';

export function SlotRequestStatus({ slot, petName, onBack, paymentReturnReceived = false, holdExpired = false }) {
  return (
    <Shell
      progress={PROGRESS_BY_STATE.bookingConfirmation}
      onBack={onBack}
      eyebrow={paymentReturnReceived ? 'Checkout return received' : 'Slot response received'}
      title={paymentReturnReceived ? 'Check your appointment and payment status' : 'Your appointment is not confirmed'}
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
      {holdExpired && (
        <p className="gv-error">
          The five-minute slot hold expired while checkout was in progress. Please check your account before trying again.
        </p>
      )}
      <p className="gv-subtitle" style={{ marginBottom: 0 }}>
        {paymentReturnReceived
          ? 'This site cannot independently confirm the payment or appointment. Check your account or contact GoVetty if the appointment is not shown.'
          : 'The API response validated the slot details, but this flow cannot confirm that an appointment was saved. No consultation was deducted. Please contact GoVetty to complete scheduling.'}
      </p>
    </Shell>
  );
}

export function BookingCompleteNoCall({ onBack }) {
  return (
    <Shell
      progress={PROGRESS_BY_STATE.bookingCompleteNoCall}
      onBack={onBack}
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
