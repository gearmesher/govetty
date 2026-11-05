import React from 'react';
import { Shell, Button } from './shared/UI';

export default function PaymentCancelled({ onRetry }) {
  return (
    <Shell eyebrow="Checkout cancelled" title="Checkout was not completed" progress={0.65}>
      <p className="gv-subtitle" style={{ marginBottom: 20 }}>
        This site cannot confirm payment status from the cancellation redirect. If you think you completed payment,
        check your account before trying again.
      </p>
      <Button onClick={onRetry}>Choose a plan</Button>
    </Shell>
  );
}
