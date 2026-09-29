import React from 'react';
import { Shell, PROGRESS_BY_STATE } from './shared/UI';

export default function PetSelection({ customer, pets, onSelect }) {
  return (
    <Shell
      progress={PROGRESS_BY_STATE.petSelection}
      eyebrow={customer ? `Welcome back, ${customer.first_name}` : 'Welcome back'}
      title="Which pet is this call for?"
      subtitle="Each pet has its own call balance, so pick the one you need help with."
    >
      <div className="gv-list">
        {pets.map((pet) => (
          <button key={pet.id} type="button" className="gv-pet-card" onClick={() => onSelect(pet.id)}>
            <div>
              <div className="gv-pet-name">{pet.name}</div>
              <div className="gv-pet-meta" style={{ textTransform: 'capitalize' }}>
                {pet.type}
              </div>
            </div>
            {pet.token_amount > 0 ? (
              <span className="gv-badge gv-badge-balance">{pet.token_amount} call{pet.token_amount === 1 ? '' : 's'} left</span>
            ) : (
              <span className="gv-badge gv-badge-empty">No balance</span>
            )}
          </button>
        ))}
      </div>
    </Shell>
  );
}
