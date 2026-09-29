import React from 'react';
import { Shell, ErrorBanner, Loading, PROGRESS_BY_STATE } from './shared/UI';

// The API only returns plan_key + price -- friendly copy lives here so it's
// easy to update without touching the machine or the API layer. Keys
// confirmed by the client: single_call, single_pet_monthly,
// multi_pet_monthly, with tiered pet-count discounts (15/25/30%) computed
// entirely server-side -- this file never calculates pricing itself.
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
  multi_pet_monthly: {
    name: 'Monthly Subscription',
    description: 'Unlimited calls across your pets on this plan, cancel anytime.',
    popular: true,
  },
};

function formatPrice(cents, planKey) {
  const amount = (cents / 100).toFixed(2).replace(/\.00$/, '');
  return planKey.includes('monthly') ? `$${amount}/mo` : `$${amount}`;
}

export default function PlanSelection({ plans, loading, error, onSelect, checkoutLoading, onBack }) {
  return (
    <Shell
      progress={PROGRESS_BY_STATE.planSelection}
      onBack={onBack}
      eyebrow="Choose your plan"
      title="How would you like to connect?"
      subtitle="Pick the option that fits how often you'll need a vet."
    >
      <ErrorBanner code={error} />
      {loading && plans.length === 0 ? (
        <Loading label="Loading plans…" />
      ) : (
        <div className="gv-list">
          {plans.map((plan) => {
            const info = PLAN_INFO[plan.plan_key] || { name: plan.plan_key, description: '' };
            return (
              <button
                key={plan.plan_key}
                type="button"
                className="gv-plan-card"
                disabled={checkoutLoading}
                onClick={() => onSelect(plan.plan_key)}
              >
                <div>
                  <div className="gv-plan-name">
                    {info.name}
                    {info.popular && <span className="gv-plan-popular">Most popular</span>}
                  </div>
                  <div className="gv-pet-meta">{info.description}</div>
                </div>
                <div className="gv-plan-price">{formatPrice(plan.price, plan.plan_key)}</div>
              </button>
            );
          })}
        </div>
      )}
      {checkoutLoading && <Loading label="Preparing secure checkout…" />}
    </Shell>
  );
}
