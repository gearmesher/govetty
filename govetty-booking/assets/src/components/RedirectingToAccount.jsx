import React from 'react';
import { Shell, Loading, PROGRESS_BY_STATE } from './shared/UI';

export default function RedirectingToAccount() {
  return (
    <Shell
      progress={PROGRESS_BY_STATE.redirectingToAccount}
      eyebrow="One moment"
      title="Taking you to your account"
      subtitle="You're signed in -- bringing you to your Personal Area."
    >
      <Loading label="Redirecting…" />
    </Shell>
  );
}
