import React, { useEffect, useState } from 'react';
import { api } from './api';
import { Shell, Button, ErrorBanner, Loading } from './components/shared/UI';

/**
 * Standalone widget for the "Personal Area: Set Image Upload Window" /
 * "One-Time Image Upload" pair of nodes on the board. Deliberately kept
 * separate from the main booking machine -- it's reached from a customer's
 * account area, not from the linear booking flow, and relies on the
 * customer_token cookie already being set from an earlier OTP login.
 */
export default function PersonalAreaImageUpload() {
  const [loading, setLoading] = useState(true);
  const [pets, setPets] = useState([]);
  const [selectedPetId, setSelectedPetId] = useState(null);
  const [uploading, setUploading] = useState(false);
  const [preview, setPreview] = useState(null);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(false);

  useEffect(() => {
    (async () => {
      const res = await api.infoUser();
      setLoading(false);
      if (res.ok) {
        setPets(res.data.pets || []);
        setSelectedPetId(res.data.pets?.[0]?.id ?? null);
      } else {
        setError(res.data.message || 'not_logged_in');
      }
    })();
  }, []);

  const handleFile = async (e) => {
    const file = e.target.files[0];
    if (!file || !selectedPetId) return;
    setUploading(true);
    setError(null);
    setSuccess(false);

    const uploadRes = await api.imageUpload(file);
    if (!uploadRes.ok) {
      setUploading(false);
      setError(uploadRes.data.message || 'upload_failed');
      return;
    }

    const assignRes = await api.imageAssign(uploadRes.data.image_id, selectedPetId);
    setUploading(false);
    if (assignRes.ok) {
      setPreview(URL.createObjectURL(file));
      setSuccess(true);
      setPets((prev) => prev.map((p) => (p.id === selectedPetId ? { ...p, image_id: uploadRes.data.image_id } : p)));
    } else {
      setError(assignRes.data.message || 'assign_failed');
    }
  };

  const selectedPet = pets.find((p) => p.id === selectedPetId);
  const alreadyHasImage = !!selectedPet?.image_id;

  return (
    <Shell eyebrow="Personal Area" title="Add a photo of your pet" progress={undefined}>
      {loading ? (
        <Loading label="Loading your pets…" />
      ) : (
        <>
          <ErrorBanner code={error} />
          {pets.length === 0 ? (
            <p className="gv-subtitle">We couldn't find any pets on your account.</p>
          ) : (
            <>
              <div className="gv-field">
                <label className="gv-label" htmlFor="gv-pa-pet">
                  Pet
                </label>
                <select
                  id="gv-pa-pet"
                  className="gv-select"
                  value={selectedPetId || ''}
                  onChange={(e) => {
                    setSelectedPetId(Number(e.target.value));
                    setSuccess(false);
                    setPreview(null);
                  }}
                >
                  {pets.map((p) => (
                    <option key={p.id} value={p.id}>
                      {p.name}
                    </option>
                  ))}
                </select>
              </div>

              {preview && <img src={preview} alt="" className="gv-upload-preview" />}

              <label className="gv-upload-dropzone" htmlFor="gv-pa-photo">
                {uploading
                  ? 'Uploading…'
                  : alreadyHasImage
                  ? 'A photo is already on file -- tap to replace it'
                  : 'Tap to choose a photo'}
              </label>
              <input
                id="gv-pa-photo"
                type="file"
                accept="image/jpeg,image/png"
                style={{ display: 'none' }}
                onChange={handleFile}
              />
              <p className="gv-hint">
                This is a one-time upload within your account's allowed window. Photos are used to help your vet
                recognize your pet during calls.
              </p>
              {success && <p className="gv-hint" style={{ color: 'var(--gv-accent)' }}>Photo saved.</p>}
            </>
          )}
        </>
      )}
    </Shell>
  );
}
