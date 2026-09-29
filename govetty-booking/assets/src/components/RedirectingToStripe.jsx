import React from 'react';
import { Shell, Loading, PROGRESS_BY_STATE } from './shared/UI';

export default function RedirectingToStripe() {
  return (
    <Shell
      progress={PROGRESS_BY_STATE.redirectingToStripe}
      eyebrow="One moment"
      title="Taking you to secure payment"
      subtitle="You'll be redirected to Stripe to complete your payment, then brought right back here."
    >
      <Loading label="Redirecting…" />
    </Shell>
  );
}
