import React from 'react';
import { Shell, Button } from './shared/UI';

export default function PaymentCancelled({ onRetry }) {
  return (
    <Shell eyebrow="Checkout cancelled" title="No charge was made" progress={0.65}>
      <p className="gv-subtitle" style={{ marginBottom: 20 }}>
        You cancelled before completing payment. Nothing was charged -- you can pick a plan again whenever you're
        ready.
      </p>
      <Button onClick={onRetry}>Choose a plan</Button>
    </Shell>
  );
}
