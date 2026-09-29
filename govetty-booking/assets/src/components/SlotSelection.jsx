import React from 'react';
import { Shell, ErrorBanner, Loading, PROGRESS_BY_STATE } from './shared/UI';

function formatDate(dateStr) {
  const d = new Date(dateStr + 'T00:00:00');
  return d.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' });
}

export default function SlotSelection({ slots, loading, error, onSelect, booking }) {
  return (
    <Shell
      progress={PROGRESS_BY_STATE.slotSelection}
      eyebrow="Pick a time"
      title="Choose an available slot"
      subtitle="All times shown are in your local time zone."
    >
      <ErrorBanner code={error} />
      {loading && slots.length === 0 ? (
        <Loading label="Loading available times…" />
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
                  {slot.time_start}–{slot.time_end}
                </div>
              </div>
              <div className="gv-slot-meta">{slot.veterinary_name}</div>
            </button>
          ))}
        </div>
      )}
      {booking && <Loading label="Booking your slot…" />}
    </Shell>
  );
}
