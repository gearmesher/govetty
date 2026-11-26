import React from 'react';
import { Shell, ErrorBanner, Loading, LinkButton, PROGRESS_BY_STATE } from './shared/UI';
import { formatSlotDate } from './shared/slotUtils';

// The API only returns plan_key + price -- friendly copy lives here so it's
// easy to update without touching the machine or the API layer. Keys
// confirmed by the client: single_call, single_pet_monthly,
// single_pet_yearly, multi_pet_monthly, with tiered pet-count discounts
// computed entirely server-side -- this file never calculates pricing.
const PLAN_INFO = {
  single_call: {
    name: 'One-Time Call',
    description: 'Single video consultation, valid for 30 days.',
  },
  single_pet_monthly: {
    name: 'Monthly Subscription',
    description: 'Unlimited calls for this pet, cancel anytime.',
    popular: true,
  },
  single_pet_yearly: {
    name: 'Annual Subscription',
    description: 'Billed annually for this pet.',
  },
  multi_pet_monthly: {
    name: 'Monthly Subscription',
    description: 'Unlimited calls across your pets on this plan, cancel anytime.',
    popular: true,
  },
};

function formatPrice(plan) {
  const amount = (plan.price / 100).toFixed(2).replace(/\.00$/, '');
  const interval =
    plan.billing_interval ||
    (plan.plan_key.endsWith('_yearly') ? 'year' : plan.plan_key.endsWith('_monthly') ? 'month' : null);
  const intervalCount = Number(plan.billing_interval_count) || 1;
  if ('month' === interval) return `$${amount}/${intervalCount === 1 ? 'mo' : `${intervalCount} mos`}`;
  if ('year' === interval) return `$${amount}/${intervalCount === 1 ? 'yr' : `${intervalCount} yrs`}`;
  return `$${amount}`;
}

function formatSlot(slot) {
  if (!slot) return '';
  const date = slot.date ? formatSlotDate(slot.date) : '';
  const time = [slot.time_start, slot.time_end].filter(Boolean).join('–');
  return [date, time, slot.vet_name || slot.veterinary_name].filter(Boolean).join(' · ');
}

export default function PlanSelection({
  plans,
  plansStatus,
  plansError,
  error,
  onSelect,
  onRetry,
  checkoutLoading,
  onBack,
  selectedSlot,
  slotHoldExpiresAt,
}) {
  const slotExpired = Boolean(slotHoldExpiresAt && Date.now() >= slotHoldExpiresAt);

  return (
    <Shell
      progress={PROGRESS_BY_STATE.planSelection}
      onBack={onBack}
      eyebrow="Choose your plan"
      title="How would you like to connect?"
      subtitle="Pick the option that fits how often you'll need a vet."
    >
      {selectedSlot && (
        <div className="gv-slot-hold-summary">
          <strong>Selected appointment</strong>
          <span>{formatSlot(selectedSlot)}</span>
        </div>
      )}
      <ErrorBanner code={error} />
      {plansStatus === 'loading' || plansStatus === 'idle' ? (
        <Loading label="Loading plans…" />
      ) : plansStatus === 'error' ? (
        <div>
          <ErrorBanner code={plansError} />
          <LinkButton onClick={onRetry}>Try again</LinkButton>
        </div>
      ) : plans.length === 0 ? (
        <div>
          <p className="gv-subtitle">No plans are available for this pet right now.</p>
          <LinkButton onClick={onRetry}>Try again</LinkButton>
        </div>
      ) : (
        <div className="gv-list">
          {plans.map((plan) => {
            const info = PLAN_INFO[plan.plan_key] || { name: plan.plan_key, description: '' };
            return (
              <button
                key={plan.plan_key}
                type="button"
                className="gv-plan-card"
                disabled={checkoutLoading || slotExpired}
                onClick={() => onSelect(plan.plan_key)}
              >
                <div>
                  <div className="gv-plan-name">
                    {info.name}
                    {info.popular && <span className="gv-plan-popular">Most popular</span>}
                  </div>
                  <div className="gv-pet-meta">{info.description}</div>
                </div>
                <div className="gv-plan-price">{formatPrice(plan)}</div>
              </button>
            );
          })}
        </div>
      )}
      {checkoutLoading && <Loading label="Preparing secure checkout…" />}
    </Shell>
  );
}
