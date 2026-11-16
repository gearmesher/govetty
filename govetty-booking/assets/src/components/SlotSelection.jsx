import React from 'react';
import { Shell, ErrorBanner, Loading, LinkButton, PROGRESS_BY_STATE } from './shared/UI';

function formatDate(dateStr) {
  const d = new Date(dateStr + 'T00:00:00');
  return d.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' });
}

export default function SlotSelection({ slots, loading, error, onSelect, booking, onBack, onRetry }) {
  return (
    <Shell
      progress={PROGRESS_BY_STATE.slotSelection}
      onBack={booking ? undefined : onBack}
      eyebrow="Pick a time"
      title="Choose an available slot"
      subtitle="Choose one of the times currently returned by the scheduling API."
    >
      <ErrorBanner code={error} />
      {loading && slots.length === 0 ? (
        <Loading label="Loading available times…" />
      ) : slots.length === 0 ? (
        <div>
          <p className="gv-subtitle">No available times were returned.</p>
          <LinkButton onClick={onRetry}>Try again</LinkButton>
        </div>
      ) : (
        <div className="gv-list">
          {slots.map((slot) => (
            <button
              key={slot.slot_id}
              type="button"
              className="gv-slot"
              disabled={booking}
              onClick={() => onSelect(slot.slot_id)}
            >
              <div>
                <div className="gv-slot-date">{formatDate(slot.date)}</div>
                <div className="gv-slot-meta">
                  {slot.time_start || 'Time TBD'}
                  {slot.time_end ? `–${slot.time_end}` : ''}
                </div>
              </div>
              {slot.veterinary_name && <div className="gv-slot-meta">{slot.veterinary_name}</div>}
            </button>
          ))}
        </div>
      )}
      {booking && <Loading label="Validating your selected time…" />}
      <p className="gv-placeholder-note">
        Availability is currently dummy data. The API documentation describes slot validation but does not confirm that
        an appointment is saved.
      </p>
    </Shell>
  );
}
