import React, { useEffect, useState } from 'react';
import { api } from './api';
import { Shell, ErrorBanner, Loading } from './components/shared/UI';

// Mirrors PlanSelection.jsx's PLAN_INFO names -- kept as a small local copy
// rather than a shared import since this only needs the display name, not
// the fuller pricing-screen copy. Keep these two in sync if plan naming
// changes.
const PLAN_NAMES = {
  single_call: 'One-Time Call',
  single_pet_monthly: 'Monthly Subscription',
  multi_pet_monthly: 'Monthly Subscription',
};

// The local checkout-events log records what this widget saw happen, not a
// confirmed payment (see Govetty_Data_Store's docblock) -- label statuses
// honestly rather than implying a verified charge.
const STATUS_LABELS = {
  initiated: 'Checkout started',
  returned_success: 'Checkout completed',
  returned_cancelled: 'Checkout cancelled',
};

function formatPrice(cents) {
  if (cents === null || cents === undefined) return '—';
  return `$${(cents / 100).toFixed(2)}`;
}

function formatDate(mysqlDatetime) {
  if (!mysqlDatetime) return '—';
  // WP's current_time('mysql') string, e.g. "2026-09-29 14:32:00" -- treat
  // as local (site) time, which is close enough for a display date here.
  const iso = mysqlDatetime.replace(' ', 'T');
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return mysqlDatetime;
  return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

function PetImageUpload({ pet, onUpdated }) {
  const [uploading, setUploading] = useState(false);
  const [error, setError] = useState(null);
  const [preview, setPreview] = useState(null);
  const hasImage = !!pet.image_id;

  const handleFile = async (e) => {
    const file = e.target.files[0];
    if (!file) return;
    setUploading(true);
    setError(null);

    const uploadRes = await api.imageUpload(file);
    if (!uploadRes.ok) {
      setUploading(false);
      setError(uploadRes.data.message || 'upload_failed');
      return;
    }

    const assignRes = await api.imageAssign(uploadRes.data.image_id, pet.id);
    setUploading(false);
    if (assignRes.ok) {
      setPreview(URL.createObjectURL(file));
      onUpdated(pet.id, uploadRes.data.image_id);
    } else {
      setError(assignRes.data.message || 'assign_failed');
    }
  };

  return (
    <div className="gv-pet-photo">
      {preview ? (
        <img src={preview} alt="" className="gv-upload-preview gv-upload-preview-sm" />
      ) : null}
      <label className="gv-upload-dropzone gv-upload-dropzone-sm" htmlFor={`gv-pa-photo-${pet.id}`}>
        {uploading ? 'Uploading…' : hasImage || preview ? 'Photo on file -- tap to replace' : 'Add a photo'}
      </label>
      <input
        id={`gv-pa-photo-${pet.id}`}
        type="file"
        accept="image/jpeg,image/png"
        style={{ display: 'none' }}
        onChange={handleFile}
      />
      {error && <p className="gv-hint">Couldn't upload that image -- try again in a moment.</p>}
    </div>
  );
}

export default function PersonalArea() {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [customer, setCustomer] = useState(null);
  const [pets, setPets] = useState([]);
  const [payments, setPayments] = useState([]);

  useEffect(() => {
    (async () => {
      const res = await api.dashboard();
      setLoading(false);
      if (res.ok) {
        setCustomer(res.data.customer || null);
        setPets(res.data.pets || []);
        setPayments(res.data.payments || []);
      } else {
        setError(res.data.message || 'not_logged_in');
      }
    })();
  }, []);

  const handlePetImageUpdated = (petId, imageId) => {
    setPets((prev) => prev.map((p) => (p.id === petId ? { ...p, image_id: imageId } : p)));
  };

  return (
    <Shell eyebrow="Personal Area" title="Your account" wide>
      {loading ? (
        <Loading label="Loading your account…" />
      ) : error ? (
        <ErrorBanner code={error} />
      ) : (
        <>
          <section className="gv-pa-section">
            <h2 className="gv-pa-heading">Account</h2>
            <dl className="gv-pa-account-grid">
              <dt>Name</dt>
              <dd>
                {customer?.first_name} {customer?.last_name}
              </dd>
              {customer?.phone ? (
                <>
                  <dt>Phone</dt>
                  <dd>{customer.phone}</dd>
                </>
              ) : null}
            </dl>
          </section>

          <section className="gv-pa-section">
            <h2 className="gv-pa-heading">Your pets</h2>
            {pets.length === 0 ? (
              <p className="gv-subtitle">We couldn't find any pets on your account.</p>
            ) : (
              <div className="gv-pa-pet-list">
                {pets.map((pet) => (
                  <div key={pet.id} className="gv-pa-pet-card">
                    <div className="gv-pa-pet-info">
                      <p className="gv-pa-pet-name">{pet.name}</p>
                      <p className="gv-pa-pet-meta">
                        {pet.type === 'dog' ? 'Dog' : pet.type === 'cat' ? 'Cat' : pet.type}
                        {' · '}
                        {pet.token_amount > 0
                          ? `${pet.token_amount} call${pet.token_amount === 1 ? '' : 's'} available`
                          : 'No calls available'}
                      </p>
                    </div>
                    <PetImageUpload pet={pet} onUpdated={handlePetImageUpdated} />
                  </div>
                ))}
              </div>
            )}
          </section>

          <section className="gv-pa-section">
            <h2 className="gv-pa-heading">Payments &amp; subscriptions</h2>
            {payments.length === 0 ? (
              <p className="gv-subtitle">No payment history on this account yet.</p>
            ) : (
              <div className="gv-pa-table-wrap">
                <table className="gv-pa-table">
                  <thead>
                    <tr>
                      <th>Date</th>
                      <th>Pet</th>
                      <th>Plan</th>
                      <th>Amount</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    {payments.map((row, i) => (
                      <tr key={i}>
                        <td>{formatDate(row.created_at)}</td>
                        <td>{row.pet_name || '—'}</td>
                        <td>{PLAN_NAMES[row.plan_key] || row.plan_key}</td>
                        <td>{formatPrice(row.price_cents)}</td>
                        <td>
                          <span className={`gv-pa-status gv-pa-status-${row.status}`}>
                            {STATUS_LABELS[row.status] || row.status}
                          </span>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
            <p className="gv-hint">
              This reflects checkout attempts made through this site, not a verified payment ledger -- for billing
              questions, contact support.
            </p>
          </section>
        </>
      )}
    </Shell>
  );
}
