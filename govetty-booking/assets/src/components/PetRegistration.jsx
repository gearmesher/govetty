import React, { useState } from 'react';
import { api } from '../api';
import { Button, ErrorBanner, Shell, PROGRESS_BY_STATE } from './shared/UI';

const EMPTY_PET = { name: '', dob: '', chip: '', gender: 'female', type: 'dog', breed_id: '' };

export default function PetRegistration({ breeds, loading, error, onSubmit, onBack }) {
  const [pet, setPet] = useState(EMPTY_PET);
  const [imageId, setImageId] = useState(null);
  const [imagePreview, setImagePreview] = useState(null);
  const [uploading, setUploading] = useState(false);
  const [uploadError, setUploadError] = useState(null);
  const breedList = pet.type === 'dog' ? breeds.dog_breeds : breeds.cat_breeds;

  const updatePet = (field) => (event) =>
    setPet((current) => ({
      ...current,
      [field]: event.target.value,
      ...(field === 'type' ? { breed_id: '' } : {}),
    }));

  const handleImageChange = async (event) => {
    const file = event.target.files[0];
    if (!file) return;
    setUploading(true);
    setUploadError(null);
    try {
      const result = await api.imageUpload(file);
      if (result.ok) {
        setImageId(result.data.image_id);
        setImagePreview(URL.createObjectURL(file));
      } else {
        setUploadError(result.data.message || 'upload_failed');
      }
    } catch (e) {
      setUploadError('upload_failed');
    } finally {
      setUploading(false);
    }
  };

  const handleSubmit = (event) => {
    event.preventDefault();
    onSubmit({
      ...pet,
      breed_id: Number(pet.breed_id),
      ...(imageId ? { image_id: imageId } : {}),
    });
  };

  return (
    <Shell
      progress={PROGRESS_BY_STATE.registration}
      onBack={loading ? undefined : onBack}
      eyebrow="Add a pet"
      title="Tell us about your pet"
      subtitle="Add your pet's details, then choose an appointment time."
    >
      <ErrorBanner code={error} />
      <form onSubmit={handleSubmit}>
        <div className="gv-field">
          <label className="gv-label" htmlFor="gv-new-pet-name">
            Pet's name
          </label>
          <input
            id="gv-new-pet-name"
            className="gv-input"
            required
            value={pet.name}
            onChange={updatePet('name')}
          />
        </div>

        <div className="gv-row-2">
          <div className="gv-field">
            <label className="gv-label" htmlFor="gv-new-pet-type">
              Species
            </label>
            <select
              id="gv-new-pet-type"
              className="gv-select"
              required
              value={pet.type}
              onChange={updatePet('type')}
            >
              <option value="dog">Dog</option>
              <option value="cat">Cat</option>
            </select>
          </div>
          <div className="gv-field">
            <label className="gv-label" htmlFor="gv-new-pet-breed">
              Breed
            </label>
            <select
              id="gv-new-pet-breed"
              className="gv-select"
              required
              value={pet.breed_id}
              onChange={updatePet('breed_id')}
            >
              <option value="" disabled>
                Select a breed
              </option>
              {breedList.map((breed) => (
                <option key={breed.id} value={breed.id}>
                  {breed.label}
                </option>
              ))}
            </select>
          </div>
        </div>

        <div className="gv-row-2">
          <div className="gv-field">
            <label className="gv-label" htmlFor="gv-new-pet-dob">
              Date of birth
            </label>
            <input
              id="gv-new-pet-dob"
              className="gv-input"
              type="date"
              required
              value={pet.dob}
              onChange={updatePet('dob')}
            />
          </div>
          <div className="gv-field">
            <label className="gv-label" htmlFor="gv-new-pet-gender">
              Gender
            </label>
            <select
              id="gv-new-pet-gender"
              className="gv-select"
              value={pet.gender}
              onChange={updatePet('gender')}
            >
              <option value="female">Female</option>
              <option value="male">Male</option>
            </select>
          </div>
        </div>

        <div className="gv-field">
          <label className="gv-label" htmlFor="gv-new-pet-chip">
            Microchip number
          </label>
          <input
            id="gv-new-pet-chip"
            className="gv-input"
            required
            value={pet.chip}
            onChange={updatePet('chip')}
          />
        </div>

        <div className="gv-field">
          <label className="gv-label" htmlFor="gv-new-pet-photo">
            Photo (optional)
          </label>
          {imagePreview && (
            <img
              src={imagePreview}
              alt="Pet preview"
              className="gv-upload-preview"
              style={{ margin: '0 0 10px' }}
            />
          )}
          <label className="gv-upload-dropzone" htmlFor="gv-new-pet-photo">
            {uploading ? 'Uploading…' : imageId ? 'Photo added — tap to replace' : 'Add a photo or skip for now'}
          </label>
          <input
            id="gv-new-pet-photo"
            type="file"
            accept="image/jpeg,image/png"
            style={{ display: 'none' }}
            onChange={handleImageChange}
          />
          {uploadError && (
            <p className="gv-hint">Couldn't upload that image. You can try again or add a photo later.</p>
          )}
        </div>

        <div className="gv-actions">
          <Button type="submit" disabled={loading || uploading}>
            {loading ? 'Adding your pet…' : 'Continue to appointment times'}
          </Button>
        </div>
      </form>
    </Shell>
  );
}
