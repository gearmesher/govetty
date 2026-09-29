import React from 'react';
import { Shell, Button, PROGRESS_BY_STATE } from './shared/UI';

export default function ScheduleCallPrompt({ onYes, onNo }) {
  return (
    <Shell
      progress={PROGRESS_BY_STATE.scheduleImmediateCall}
      eyebrow="Payment complete"
      title="Schedule a call right now?"
      subtitle="You can book a time now, or come back and schedule whenever suits you."
    >
      <div className="gv-actions" style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
        <Button onClick={onYes}>Yes, pick a time</Button>
        <Button variant="secondary" onClick={onNo}>
          Not right now
        </Button>
      </div>
    </Shell>
  );
}
