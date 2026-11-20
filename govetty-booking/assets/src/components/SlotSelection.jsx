import React from 'react';
import { Shell, ErrorBanner, Loading, LinkButton, PROGRESS_BY_STATE } from './shared/UI';
import SlotHoldCountdown from './SlotHoldCountdown';

function formatDate(dateStr) {
  if (!dateStr) return 'Date to be confirmed';
  const d = new Date(dateStr + 'T00:00:00');
  if (Number.isNaN(d.getTime())) return dateStr;
  return d.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' });
}

function getSlotTimestamp(slot) {
  const date = new Date(`${slot.date || ''}T00:00:00`);
  const time = String(slot.time_start || '').match(/^(\d{1,2}):(\d{2})/);
  if (Number.isNaN(date.getTime())) return Number.MAX_SAFE_INTEGER;
  if (time) date.setHours(Number(time[1]), Number(time[2]));
  return date.getTime();
}

export default function SlotSelection({
  slots,
  loading,
  error,
  onSelect,
  booking,
  onBack,
  onRetry,
  selectedSlot,
  slotHoldExpiresAt,
  onHoldExpired,
}) {
  return (
    <Shell
      progress={PROGRESS_BY_STATE.slotSelection}
      onBack={booking ? undefined : onBack}
      eyebrow="Pick a time"
      title="Choose an available slot"
      subtitle="Select one of the available times. If payment is needed, the slot will be held for five minutes while you complete checkout."
    >
      {selectedSlot && slotHoldExpiresAt && (
        <div className="gv-slot-hold-summary">
          <strong>Selected appointment</strong>
          <span>
            {formatDate(selectedSlot.date)} · {selectedSlot.time_start || 'Time TBD'}
            {selectedSlot.time_end ? `–${selectedSlot.time_end}` : ''}
          </span>
        </div>
      )}
      <SlotHoldCountdown expiresAt={slotHoldExpiresAt} onExpired={onHoldExpired} />
      <ErrorBanner code={error} />
      {loading && slots.length === 0 ? (
        <Loading label="Loading available times…" />
      ) : slots.length === 0 ? (
        <div>
          <p className="gv-subtitle">No available times were returned.</p>
          <LinkButton onClick={onRetry}>Try again</LinkButton>
        </div>
      ) : (
        <div className="gv-list gv-slot-list">
          {slots
            .slice()
            .sort((a, b) => getSlotTimestamp(a) - getSlotTimestamp(b))
            .map((slot) => (
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
      {!loading && slots.length > 5 && (
        <p className="gv-hint">Scroll the list to see later appointment times.</p>
      )}
      {booking && <Loading label="Holding your selected time…" />}
    </Shell>
  );
}
