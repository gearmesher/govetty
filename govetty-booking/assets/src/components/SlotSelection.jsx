import React from 'react';
import { Shell, ErrorBanner, Loading, LinkButton, PROGRESS_BY_STATE } from './shared/UI';
import { formatSlotDate, getSlotTimestamp } from './shared/slotUtils';

function getSlotId(slot) {
  const slotId = slot.slot_id ?? slot.slotId ?? slot.id;
  return slotId !== undefined && slotId !== null && String(slotId).trim() !== '' && Number(slotId) > 0
    ? slotId
    : null;
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
}) {
  const hasSlotsWithoutId = slots.some((slot) => getSlotId(slot) === null);

  return (
    <Shell
      progress={PROGRESS_BY_STATE.slotSelection}
      onBack={booking ? undefined : onBack}
      eyebrow="Pick a time"
      title="Choose an available slot"
      subtitle="Select an available time. If payment is needed, complete checkout within five minutes."
    >
      {selectedSlot && (
        <div className="gv-slot-hold-summary">
          <strong>Selected appointment</strong>
          <span>
            {selectedSlot.date ? formatSlotDate(selectedSlot.date) : 'Date to be confirmed'} · {selectedSlot.time_start || 'Time TBD'}
            {selectedSlot.time_end ? `–${selectedSlot.time_end}` : ''}
          </span>
        </div>
      )}
      <ErrorBanner code={error} />
      {hasSlotsWithoutId && (
        <p className="gv-error" role="alert">
          Some available times can’t be selected because the scheduling service didn’t provide an appointment ID.
          Please try again later.
        </p>
      )}
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
            .map((slot, index) => {
              const slotId = getSlotId(slot);
              return (
                <button
                  key={slotId ?? `unavailable-slot-${index}`}
                  type="button"
                  className="gv-slot"
                  disabled={booking || slotId === null}
                  onClick={() => onSelect(slotId)}
                >
                  <div>
                    <div className="gv-slot-date">
                      {slot.date ? formatSlotDate(slot.date) : 'Date to be confirmed'}
                    </div>
                    <div className="gv-slot-meta">
                      {slot.time_start || 'Time TBD'}
                      {slot.time_end ? `–${slot.time_end}` : ''}
                    </div>
                  </div>
                  {(slot.vet_name || slot.veterinary_name) && (
                    <div className="gv-slot-meta">{slot.vet_name || slot.veterinary_name}</div>
                  )}
                </button>
              );
            })}
        </div>
      )}
      {!loading && slots.length > 5 && (
        <p className="gv-hint">Scroll the list to see later appointment times.</p>
      )}
      {booking && <Loading label="Holding your selected time…" />}
    </Shell>
  );
}
