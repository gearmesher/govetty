import React from 'react';
import { Shell, PROGRESS_BY_STATE } from './shared/UI';

export function BookingConfirmation({ slot }) {
  return (
    <Shell progress={PROGRESS_BY_STATE.bookingConfirmation} eyebrow="You're all set" title="Booking confirmed">
      <div className="gv-confirm-icon" aria-hidden="true">
        ✓
      </div>
      {slot && (
        <div className="gv-summary">
          <div className="gv-summary-row">
            <span>Date</span>
            <span>{slot.date}</span>
          </div>
          <div className="gv-summary-row">
            <span>Time</span>
            <span>
              {slot.time_start}–{slot.time_end}
            </span>
          </div>
          <div className="gv-summary-row">
            <span>Vet</span>
            <span>{slot.veterinary_name}</span>
          </div>
        </div>
      )}
      <p className="gv-subtitle" style={{ marginTop: 16, marginBottom: 0 }}>
        We'll send a reminder before your call. You can find this booking anytime in your Personal Area.
      </p>
    </Shell>
  );
}

export function BookingCompleteNoCall() {
  return (
    <Shell progress={PROGRESS_BY_STATE.bookingCompleteNoCall} eyebrow="You're all set" title="You're ready to go">
      <div className="gv-confirm-icon" aria-hidden="true">
        ✓
      </div>
      <p className="gv-subtitle" style={{ marginBottom: 0 }}>
        Your plan is active. Whenever you're ready for a call, come back and pick a time from your Personal Area.
      </p>
    </Shell>
  );
}
