import React, { useEffect, useRef, useState } from 'react';
import { Shell, Button, LinkButton, ErrorBanner, PROGRESS_BY_STATE } from './shared/UI';

const COOLDOWN_SECONDS = 60;

export default function OtpEntry({ phone, otpSentAt, canResend, onSubmit, onResend, onEditPhone, loading, error }) {
  const [digits, setDigits] = useState(['', '', '', '', '', '']);
  const [secondsLeft, setSecondsLeft] = useState(COOLDOWN_SECONDS);
  const inputsRef = useRef([]);

  useEffect(() => {
    const tick = () => {
      const elapsed = Math.floor((Date.now() - (otpSentAt || Date.now())) / 1000);
      setSecondsLeft(Math.max(0, COOLDOWN_SECONDS - elapsed));
    };
    tick();
    const id = setInterval(tick, 1000);
    return () => clearInterval(id);
  }, [otpSentAt]);

  const setDigit = (index, value) => {
    const clean = value.replace(/[^0-9]/g, '').slice(-1);
    const next = [...digits];
    next[index] = clean;
    setDigits(next);
    if (clean && index < 5) {
      inputsRef.current[index + 1]?.focus();
    }
    if (next.every((d) => d !== '')) {
      onSubmit(next.join(''));
    }
  };

  const handleKeyDown = (index, e) => {
    if (e.key === 'Backspace' && !digits[index] && index > 0) {
      inputsRef.current[index - 1]?.focus();
    }
  };

  const handlePaste = (e) => {
    const pasted = e.clipboardData.getData('text').replace(/[^0-9]/g, '').slice(0, 6);
    if (!pasted) return;
    e.preventDefault();
    const next = pasted.split('');
    while (next.length < 6) next.push('');
    setDigits(next);
    if (pasted.length === 6) onSubmit(pasted);
  };

  const handleResend = () => {
    setDigits(['', '', '', '', '', '']);
    onResend();
  };

  return (
    <Shell
      progress={PROGRESS_BY_STATE.otpEntry}
      onBack={onEditPhone}
      eyebrow="Verify your number"
      title="Enter the 6-digit code"
      subtitle={`We sent a code to ${phone}.`}
    >
      <ErrorBanner code={error} />
      <div className="gv-otp-row" onPaste={handlePaste}>
        {digits.map((d, i) => (
          <input
            key={i}
            ref={(el) => (inputsRef.current[i] = el)}
            className="gv-otp-digit"
            type="text"
            inputMode="numeric"
            maxLength={1}
            value={d}
            disabled={loading}
            onChange={(e) => setDigit(i, e.target.value)}
            onKeyDown={(e) => handleKeyDown(i, e)}
            aria-label={`Digit ${i + 1} of 6`}
          />
        ))}
      </div>
      <div className="gv-otp-meta">
        {loading ? (
          <span>Checking…</span>
        ) : (
          <LinkButton onClick={handleResend} disabled={!canResend}>
            {canResend ? 'Resend code' : `Resend code in ${secondsLeft}s`}
          </LinkButton>
        )}
      </div>
    </Shell>
  );
}
