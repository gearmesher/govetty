import React, { useEffect, useRef, useState } from 'react';
import { api, saveContinuation } from './api';
import PlanSelection from './components/PlanSelection';
import PaymentCancelled from './components/PaymentCancelled';
import RedirectingToStripe from './components/RedirectingToStripe';

/**
 * After a Stripe cancel_url redirect, the customer_token cookie is still
 * valid -- there's no need to make them log in again just to pick a
 * different plan. This is a small, self-contained re-run of Plan
 * Selection -> Payment that doesn't need the full bookingMachine (there's
 * no branching left to encode at this point in the flow).
 */
export default function ResumePlanSelection({ continuation, checkoutRef }) {
  const [acknowledged, setAcknowledged] = useState(false);
  const [plans, setPlans] = useState([]);
  const [loading, setLoading] = useState(true);
  const [checkoutLoading, setCheckoutLoading] = useState(false);
  const [error, setError] = useState(null);
  const [redirecting, setRedirecting] = useState(false);
  const confirmedRef = useRef(false);

  const petId = continuation?.selectedPetId;

  // Fire-and-forget, same as PostPaymentApp's success-side ping -- lets the
  // admin's Subscriptions & Revenue log know this checkout was cancelled
  // rather than left dangling as "initiated" forever.
  useEffect(() => {
    if (checkoutRef && !confirmedRef.current) {
      confirmedRef.current = true;
      api.confirmReturn(checkoutRef, 'cancelled');
    }
  }, [checkoutRef]);

  useEffect(() => {
    if (!acknowledged || !petId) return;
    (async () => {
      const res = await api.billingPlans(petId);
      setLoading(false);
      if (res.ok) setPlans(res.data.plans || []);
      else setError(res.data.message);
    })();
  }, [acknowledged, petId]);

  const handleSelect = async (planKey) => {
    setCheckoutLoading(true);
    setError(null);
    const res = await api.billingCheckout(planKey, [petId]);
    setCheckoutLoading(false);
    if (res.ok && res.data.checkout_url) {
      saveContinuation({ selectedPetId: petId, flowVariant: continuation.flowVariant });
      setRedirecting(true);
      window.location.href = res.data.checkout_url;
    } else {
      setError(res.data.message || 'checkout_session_failed');
    }
  };

  if (!petId) {
    // Continuation data didn't survive (e.g. a different browser/tab, or
    // sessionStorage cleared) -- nothing safe to resume automatically.
    return <PaymentCancelled onRetry={() => window.location.assign(window.location.pathname)} />;
  }

  if (redirecting) return <RedirectingToStripe />;

  if (!acknowledged) {
    return <PaymentCancelled onRetry={() => setAcknowledged(true)} />;
  }

  return (
    <PlanSelection
      plans={plans}
      loading={loading}
      checkoutLoading={checkoutLoading}
      error={error}
      onSelect={handleSelect}
    />
  );
}
