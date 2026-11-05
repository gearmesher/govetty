import React from 'react';
import { Shell, Button, PROGRESS_BY_STATE } from './shared/UI';

export default function ScheduleCallPrompt({ onYes, onNo }) {
  return (
    <Shell
      progress={PROGRESS_BY_STATE.scheduleImmediateCall}
      eyebrow="Checkout return received"
      title="Schedule a call right now?"
      subtitle="Payment status has not been independently confirmed. You can continue to choose a time or return to your account."
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
