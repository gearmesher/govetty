import React, { useEffect, useRef, useState } from 'react';

function formatTime(milliseconds) {
  const totalSeconds = Math.ceil(milliseconds / 1000);
  const minutes = Math.floor(totalSeconds / 60);
  const seconds = totalSeconds % 60;
  return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
}

export default function SlotHoldCountdown({ expiresAt, onExpired }) {
  const [now, setNow] = useState(Date.now());
  const expiredNotified = useRef(false);
  const remaining = expiresAt ? Math.max(0, expiresAt - now) : null;

  useEffect(() => {
    expiredNotified.current = false;
  }, [expiresAt]);

  useEffect(() => {
    if (!expiresAt) return undefined;
    setNow(Date.now());
    const interval = window.setInterval(() => setNow(Date.now()), 1000);
    return () => window.clearInterval(interval);
  }, [expiresAt]);

  useEffect(() => {
    if (remaining !== 0 || !onExpired || expiredNotified.current) return;
    expiredNotified.current = true;
    onExpired();
  }, [remaining, onExpired]);

  if (!expiresAt) return null;

  return (
    <p className="gv-slot-hold-timer" role="timer">
      {remaining > 0
        ? `Complete checkout within ${formatTime(remaining)} to keep this time slot.`
        : 'Your five-minute slot hold has expired. Choose another time.'}
    </p>
  );
}
