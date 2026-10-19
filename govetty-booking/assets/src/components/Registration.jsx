import React, { useState } from 'react';
import { Shell, Button, LinkButton, ErrorBanner, PROGRESS_BY_STATE } from './shared/UI';
import { api } from '../api';
import AddressAutocomplete from './AddressAutocomplete';

const PROVINCES = [
  ['AB', 'Alberta'],
  ['BC', 'British Columbia'],
  ['MB', 'Manitoba'],
  ['NB', 'New Brunswick'],
  ['NL', 'Newfoundland and Labrador'],
  ['NS', 'Nova Scotia'],
  ['NT', 'Northwest Territories'],
  ['NU', 'Nunavut'],
  ['ON', 'Ontario'],
  ['PE', 'Prince Edward Island'],
  ['QC', 'Quebec'],
  ['SK', 'Saskatchewan'],
  ['YT', 'Yukon'],
];
const POSTAL_PATTERN = '[A-Za-z][0-9][A-Za-z] ?[0-9][A-Za-z][0-9]';

// /register takes the street in `address` plus separate optional flat_number,
// city, province and country (confirmed by the client), with zip_code. The
// "Check address" button still validates one full line, so that line is
// composed here ("81 Rimmington Drive, Unit 4B, Thornhill, ON L4J6J9, Canada").
function composeAddress(a) {
  const street = [a.street.trim(), a.unit.trim()].filter(Boolean).join(', ');
  return `${street}, ${a.city.trim()}, ${a.province} ${a.postal_code.trim().toUpperCase()}, Canada`;
}

const emptyPet = { name: '', dob: '', chip: '', gender: 'female', type: 'dog', breed_id: '' };

export default function Registration({ phone, breeds, loading, error, onSubmit }) {
  const [owner, setOwner] = useState({ first_name: '', last_name: '', email: '' });
  const [addr, setAddr] = useState({ street: '', unit: '', city: '', province: '', postal_code: '' });
  const autocompleteOn = !!(window.GovettyBooking && window.GovettyBooking.addressAutocomplete);
  const [pet, setPet] = useState(emptyPet);
  const [imageId, setImageId] = useState(null);
  const [imagePreview, setImagePreview] = useState(null);
  const [uploading, setUploading] = useState(false);
  const [uploadError, setUploadError] = useState(null);
  const [addressCheck, setAddressCheck] = useState(null); // {checking, result}

  const breedList = pet.type === 'dog' ? breeds.dog_breeds : breeds.cat_breeds;

  const updateOwner = (field) => (e) => setOwner((o) => ({ ...o, [field]: e.target.value }));
  const updateAddr = (field) => (e) => {
    setAddressCheck(null);
    setAddr((a) => ({ ...a, [field]: e.target.value }));
  };
  const updatePet = (field) => (e) => setPet((p) => ({ ...p, [field]: e.target.value, ...(field === 'type' ? { breed_id: '' } : {}) }));

  const handleImageChange = async (e) => {
    const file = e.target.files[0];
    if (!file) return;
    setUploading(true);
    setUploadError(null);
    const res = await api.imageUpload(file);
    setUploading(false);
    if (res.ok) {
      setImageId(res.data.image_id);
      setImagePreview(URL.createObjectURL(file));
    } else {
      setUploadError(res.data.message || 'upload_failed');
    }
  };

  const checkAddress = async () => {
    if (!addr.street.trim()) return;
    setAddressCheck({ checking: true, result: null });
    const res = await api.addressValidate(composeAddress(addr));
    setAddressCheck({ checking: false, result: res.data });
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    const payload = {
      ...owner,
      address: addr.street.trim(),
      // Optional on the API side: omitted entirely when empty.
      ...(addr.unit.trim() ? { flat_number: addr.unit.trim() } : {}),
      city: addr.city.trim(),
      province: addr.province,
      country: 'Canada',
      zip_code: addr.postal_code.trim().toUpperCase().replace(/[\s-]/g, ''),
      phone,
      pets: [
        {
          ...pet,
          breed_id: pet.breed_id ? Number(pet.breed_id) : undefined,
          // image_id is optional and confirmed by the client -- omitted
          // entirely when no photo was taken, rather than sent as null.
          ...(imageId ? { image_id: imageId } : {}),
        },
      ],
    };
    onSubmit(payload);
  };

  return (
    <Shell
      progress={PROGRESS_BY_STATE.registration}
      eyebrow="Almost there"
      title="Tell us about you and your pet"
      subtitle="This is the info GoVetty has on file for your account."
    >
      <ErrorBanner code={error} />
      <form onSubmit={handleSubmit}>
        <div className="gv-row-2">
          <div className="gv-field">
            <label className="gv-label" htmlFor="gv-first-name">
              First name
            </label>
            <input id="gv-first-name" className="gv-input" required value={owner.first_name} onChange={updateOwner('first_name')} />
          </div>
          <div className="gv-field">
            <label className="gv-label" htmlFor="gv-last-name">
              Last name
            </label>
            <input id="gv-last-name" className="gv-input" required value={owner.last_name} onChange={updateOwner('last_name')} />
          </div>
        </div>

        <div className="gv-field">
          <label className="gv-label" htmlFor="gv-email">
            Email
          </label>
          <input id="gv-email" className="gv-input" type="email" required value={owner.email} onChange={updateOwner('email')} />
        </div>

        <div className="gv-field">
          <label className="gv-label" htmlFor="gv-address">
            Street address
          </label>
          <AddressAutocomplete
            id="gv-address"
            enabled={autocompleteOn}
            value={addr.street}
            onChange={(street) => {
              setAddressCheck(null);
              setAddr((a) => ({ ...a, street }));
            }}
            onPick={(p) => {
              setAddressCheck(null);
              setAddr((a) => ({
                street: p.street || a.street,
                unit: p.unit || a.unit,
                city: p.city || '',
                province: PROVINCES.some(([code]) => code === p.province) ? p.province : '',
                postal_code: p.postal_code || '',
              }));
            }}
          />
          {autocompleteOn && <p className="gv-hint">Start typing and pick your address from the list.</p>}
        </div>

        <div className="gv-field">
          <label className="gv-label" htmlFor="gv-unit">
            Apartment / unit / suite (optional)
          </label>
          <input
            id="gv-unit"
            className="gv-input"
            autoComplete="off"
            maxLength={50}
            value={addr.unit}
            onChange={updateAddr('unit')}
          />
        </div>

        <div className="gv-row-2">
          <div className="gv-field">
            <label className="gv-label" htmlFor="gv-city">
              City
            </label>
            <input id="gv-city" className="gv-input" required maxLength={255} value={addr.city} onChange={updateAddr('city')} />
          </div>
          <div className="gv-field">
            <label className="gv-label" htmlFor="gv-province">
              Province
            </label>
            <select id="gv-province" className="gv-select" required value={addr.province} onChange={updateAddr('province')}>
              <option value="" disabled>
                Select a province
              </option>
              {PROVINCES.map(([code, name]) => (
                <option key={code} value={code}>
                  {name}
                </option>
              ))}
            </select>
          </div>
        </div>

        <div className="gv-row-2">
          <div className="gv-field">
            <label className="gv-label" htmlFor="gv-zip">
              Postal code
            </label>
            <input
              id="gv-zip"
              className="gv-input"
              required
              pattern={POSTAL_PATTERN}
              title="Canadian postal code, for example L3R 6G2"
              autoComplete="postal-code"
              value={addr.postal_code}
              onChange={updateAddr('postal_code')}
            />
          </div>
          <div className="gv-field">
            <label className="gv-label" htmlFor="gv-country">
              Country
            </label>
            <input id="gv-country" className="gv-input" value="Canada" disabled readOnly />
          </div>
        </div>

        <div className="gv-field">
          <LinkButton
            type="button"
            onClick={checkAddress}
            disabled={!addr.street.trim() || !addr.city.trim() || !addr.province || addressCheck?.checking}
          >
            {addressCheck?.checking ? 'Checking address…' : 'Check address'}
          </LinkButton>
          {addressCheck?.result && (
            <p className="gv-hint">
              {addressCheck.result.valid
                ? `Looks good: ${addressCheck.result.formatted_address}`
                : "We couldn't confirm that address -- double-check it before continuing."}
            </p>
          )}
        </div>

        <hr style={{ border: 'none', borderTop: '1px solid var(--gv-border)', margin: '22px 0' }} />

        <div className="gv-field">
          <label className="gv-label" htmlFor="gv-pet-name">
            Pet's name
          </label>
          <input id="gv-pet-name" className="gv-input" required value={pet.name} onChange={updatePet('name')} />
        </div>

        <div className="gv-row-2">
          <div className="gv-field">
            <label className="gv-label" htmlFor="gv-pet-type">
              Species
            </label>
            <select id="gv-pet-type" className="gv-select" required value={pet.type} onChange={updatePet('type')}>
              <option value="dog">Dog</option>
              <option value="cat">Cat</option>
            </select>
          </div>
          <div className="gv-field">
            <label className="gv-label" htmlFor="gv-pet-breed">
              Breed
            </label>
            <select id="gv-pet-breed" className="gv-select" required value={pet.breed_id} onChange={updatePet('breed_id')}>
              <option value="" disabled>
                Select a breed
              </option>
              {breedList.map((b) => (
                <option key={b.id} value={b.id}>
                  {b.label}
                </option>
              ))}
            </select>
          </div>
        </div>

        <div className="gv-row-2">
          <div className="gv-field">
            <label className="gv-label" htmlFor="gv-pet-dob">
              Date of birth
            </label>
            <input id="gv-pet-dob" className="gv-input" type="date" required value={pet.dob} onChange={updatePet('dob')} />
          </div>
          <div className="gv-field">
            <label className="gv-label" htmlFor="gv-pet-gender">
              Gender
            </label>
            <select id="gv-pet-gender" className="gv-select" value={pet.gender} onChange={updatePet('gender')}>
              <option value="female">Female</option>
              <option value="male">Male</option>
            </select>
          </div>
        </div>

        <div className="gv-field">
          <label className="gv-label" htmlFor="gv-pet-chip">
            Microchip number
          </label>
          <input id="gv-pet-chip" className="gv-input" required value={pet.chip} onChange={updatePet('chip')} />
        </div>

        <div className="gv-field">
          <label className="gv-label">Photo (optional)</label>
          {imagePreview ? (
            <img src={imagePreview} alt="Pet preview" className="gv-upload-preview" style={{ margin: '0 0 10px' }} />
          ) : null}
          <label className="gv-upload-dropzone" htmlFor="gv-pet-photo">
            {uploading ? 'Uploading…' : imageId ? 'Photo added -- tap to replace' : 'Tap to add a photo, or skip for now'}
          </label>
          <input id="gv-pet-photo" type="file" accept="image/jpeg,image/png" style={{ display: 'none' }} onChange={handleImageChange} />
          {uploadError && <p className="gv-hint">Couldn't upload that image -- you can add one later from your Personal Area.</p>}
          <p className="gv-hint gv-hint--spaced">You can also add or change this anytime from your Personal Area, within the allowed window.</p>
        </div>

        <div className="gv-actions">
          <Button type="submit" disabled={loading || uploading}>
            {loading ? 'Creating your account…' : 'Continue'}
          </Button>
        </div>
      </form>
    </Shell>
  );
}
