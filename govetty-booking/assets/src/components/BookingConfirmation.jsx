import React from 'react';
import { ErrorBanner, Shell, PROGRESS_BY_STATE } from './shared/UI';

export function SlotRequestStatus({
  slot,
  petName,
  joinUrl,
  bookingError,
  onBack,
  paymentReturnReceived = false,
  holdExpired = false,
}) {
  const confirmed = !!joinUrl;
  return (
    <Shell
      progress={PROGRESS_BY_STATE.bookingConfirmation}
      onBack={onBack}
      eyebrow={confirmed ? 'Appointment booked' : paymentReturnReceived ? 'Checkout return received' : 'Booking response received'}
      title={confirmed ? 'Your appointment is confirmed' : 'Your appointment is not confirmed'}
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
      {bookingError && <ErrorBanner code={bookingError} />}
      {holdExpired && !confirmed && (
        <p className="gv-error">
          The five-minute slot hold expired while checkout was in progress. The API could not confirm the appointment.
        </p>
      )}
      {confirmed ? (
        <p className="gv-subtitle">
          Your consultation is booked. Use this link to join:
          <br />
          <a href={joinUrl} target="_blank" rel="noopener noreferrer">
            Join your consultation
          </a>
        </p>
      ) : (
        <p className="gv-subtitle" style={{ marginBottom: 0 }}>
          {paymentReturnReceived
            ? 'The payment return was received, but the API did not confirm an appointment. Check your account or contact GoVetty for help.'
            : 'The API did not confirm an appointment. No consultation should be considered booked. Please try another time or contact GoVetty.'}
        </p>
      )}
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
