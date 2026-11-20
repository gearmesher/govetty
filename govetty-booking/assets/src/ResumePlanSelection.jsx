import React, { useCallback, useEffect, useRef, useState } from 'react';
import { api, clearContinuation, saveContinuation } from './api';
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
  const [plansError, setPlansError] = useState(null);
  const [checkoutError, setCheckoutError] = useState(null);
  const [redirecting, setRedirecting] = useState(false);
  const confirmedRef = useRef(false);

  const petId = continuation?.selectedPetId;
  const slotHoldExpiresAt = continuation?.slotHoldExpiresAt ?? null;

  const returnToSlotSelection = useCallback(() => {
    const baseUrl =
      (typeof window.GovettyBooking !== 'undefined' && window.GovettyBooking.bookingUrl) ||
      window.location.pathname;
    const url = new URL(baseUrl, window.location.href);
    url.searchParams.delete('govetty_step');
    url.searchParams.delete('checkout_ref');
    if (petId) url.searchParams.set('govetty_pet', String(petId));
    clearContinuation();
    window.location.assign(url.toString());
  }, [petId]);

  const fetchPlans = useCallback(async () => {
    if (!petId) return;
    setLoading(true);
    setPlansError(null);
    try {
      const res = await api.billingPlans(petId);
      if (res.ok && Array.isArray(res.data.plans)) {
        setPlans(res.data.plans);
      } else {
        setPlans([]);
        setPlansError(res.data.message || res.data.error || 'plans_load_failed');
      }
    } catch (e) {
      setPlans([]);
      setPlansError('plans_load_failed');
    } finally {
      setLoading(false);
    }
  }, [petId]);

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
    fetchPlans();
  }, [acknowledged, petId, fetchPlans]);

  const handleSelect = async (planKey) => {
    if (slotHoldExpiresAt && Date.now() >= slotHoldExpiresAt) {
      returnToSlotSelection();
      return;
    }

    setCheckoutLoading(true);
    setCheckoutError(null);
    try {
      const res = await api.billingCheckout(planKey, [petId]);
      if (res.ok && res.data.checkout_url) {
        saveContinuation({
          selectedPetId: petId,
          flowVariant: continuation.flowVariant,
          selectedSlot: continuation.selectedSlot,
          slotHoldExpiresAt,
        });
        setRedirecting(true);
        window.location.href = res.data.checkout_url;
      } else {
        setCheckoutError(res.data.message || 'checkout_session_failed');
      }
    } catch (e) {
      setCheckoutError('checkout_session_failed');
    } finally {
      setCheckoutLoading(false);
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
      plansStatus={loading ? 'loading' : plansError ? 'error' : 'ready'}
      plansError={plansError}
      error={checkoutError}
      checkoutLoading={checkoutLoading}
      onBack={() => setAcknowledged(false)}
      selectedSlot={continuation.selectedSlot}
      slotHoldExpiresAt={slotHoldExpiresAt}
      onHoldExpired={returnToSlotSelection}
      onRetry={fetchPlans}
      onSelect={handleSelect}
    />
  );
}
