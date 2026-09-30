import React from 'react';

// Rough progress through whichever branch is currently active. Approximate
// on purpose -- the flow forks, so this is a sense of "getting there"
// rather than a precise step count.
export const PROGRESS_BY_STATE = {
  phoneEntry: 0.08,
  otpSending: 0.15,
  otpEntry: 0.2,
  otpVerifying: 0.25,
  registration: 0.35,
  registering: 0.4,
  petSelection: 0.35,
  planSelection: 0.55,
  checkingOut: 0.65,
  redirectingToStripe: 0.7,
  redirectingToAccount: 0.9,
  scheduleImmediateCall: 0.75,
  slotSelection: 0.85,
  booking: 0.92,
  bookingConfirmation: 1,
  bookingCompleteNoCall: 1,
};

export function Shell({ progress, eyebrow, title, subtitle, children, onBack, wide }) {
  const mockMode = typeof window !== 'undefined' && window.GovettyBooking && window.GovettyBooking.mockMode;
  return (
    <div className="gv-root">
      <div className={wide ? 'gv-shell gv-shell-wide' : 'gv-shell'}>
        {mockMode && (
          <div className="gv-mock-banner">
            Mock mode &mdash; no real API, SMS, or Stripe calls. Any 6-digit code works.
          </div>
        )}
        <div className="gv-card">
          {progress !== undefined && (
            <div className="gv-progress-track" role="progressbar" aria-valuenow={Math.round(progress * 100)} aria-valuemin={0} aria-valuemax={100}>
              <div className="gv-progress-fill" style={{ width: `${Math.round(progress * 100)}%` }} />
            </div>
          )}
          {onBack && (
            <button type="button" className="gv-back-link" onClick={onBack}>
              &larr; Back
            </button>
          )}
          {eyebrow && <p className="gv-eyebrow">{eyebrow}</p>}
          {title && <h1 className="gv-title">{title}</h1>}
          {subtitle && <p className="gv-subtitle">{subtitle}</p>}
          {children}
        </div>
      </div>
    </div>
  );
}

export function Button({ children, variant = 'primary', ...props }) {
  const cls = variant === 'primary' ? 'gv-btn gv-btn-primary' : 'gv-btn gv-btn-secondary';
  return (
    <button type="button" className={cls} {...props}>
      {children}
    </button>
  );
}

export function LinkButton({ children, ...props }) {
  return (
    <button type="button" className="gv-btn-link" {...props}>
      {children}
    </button>
  );
}

const ERROR_MESSAGES = {
  invalid_or_expired: 'That code is incorrect or has expired. Check the digits and try again.',
  too_many_attempts: "That's 5 incorrect tries -- request a fresh code to keep going.",
  invalid_code_format: 'Enter all 6 digits from the text message.',
  invalid_phone: "That phone number doesn't look right.",
  send_failed: "We couldn't reach the server just now. Try again in a moment.",
  server_error: 'Something went wrong on our end. Try again in a moment.',
  checkout_session_failed: "We couldn't start checkout. Try again in a moment.",
  plan_inactive: 'That plan is no longer available -- pick another one.',
  too_many_pets_for_profile: "That's more pets than this plan allows -- pick a different plan.",
  validation_error: 'Double-check the highlighted fields and try again.',
  not_found: "We couldn't find that -- try again.",
};

export function errorMessage(code) {
  return ERROR_MESSAGES[code] || 'Something went wrong. Please try again.';
}

export function ErrorBanner({ code }) {
  if (!code) return null;
  return <div className="gv-error">{errorMessage(code)}</div>;
}

export function Loading({ label }) {
  return (
    <div className="gv-loading">
      <span className="gv-spinner" aria-hidden="true" />
      <span>{label}</span>
    </div>
  );
}
