import React, { useState } from 'react';
import { Shell, Button, ErrorBanner, PROGRESS_BY_STATE } from './shared/UI';

export default function PhoneEntry({ onSubmit, loading, error }) {
  const [phone, setPhone] = useState('');

  const handleSubmit = (e) => {
    e.preventDefault();
    if (phone.trim()) onSubmit(phone.trim());
  };

  return (
    <Shell
      progress={PROGRESS_BY_STATE.phoneEntry}
      eyebrow="Talk to a vet"
      title="What's your phone number?"
      subtitle="We'll text you a one-time code to continue -- no password needed."
    >
      <ErrorBanner code={error} />
      <form onSubmit={handleSubmit}>
        <div className="gv-field">
          <label className="gv-label" htmlFor="gv-phone">
            Phone number
          </label>
          <input
            id="gv-phone"
            className="gv-input"
            type="tel"
            inputMode="tel"
            autoComplete="tel"
            placeholder="+1 (555) 123-4567"
            value={phone}
            onChange={(e) => setPhone(e.target.value)}
            required
          />
          <p className="gv-hint">Standard message and data rates may apply.</p>
        </div>
        <div className="gv-actions">
          <Button type="submit" disabled={loading || !phone.trim()}>
            {loading ? 'Sending code…' : 'Send code'}
          </Button>
        </div>
      </form>
    </Shell>
  );
}
