/**
 * Thin wrapper around fetch() for the govetty/v1 WP REST routes registered
 * by the GoVetty Booking plugin. Every call sends the WP REST nonce and
 * includes cookies, so the HttpOnly customer_token cookie rides along
 * automatically once /otp/verify has succeeded.
 *
 * GovettyBooking.root / GovettyBooking.nonce are localized onto the page
 * by govetty-booking.php (see wp_localize_script in the main plugin file).
 */

function getConfig() {
  if (typeof window === 'undefined' || !window.GovettyBooking) {
    throw new Error(
      'GovettyBooking config not found. Is the govetty-booking-config script enqueued on this page?'
    );
  }
  return window.GovettyBooking;
}

async function call(method, path, body) {
  const { root, nonce } = getConfig();
  const isFormEncoded = typeof body === 'string';

  const init = {
    method,
    credentials: 'same-origin',
    headers: {
      'X-WP-Nonce': nonce,
    },
  };

  if (body !== undefined && method !== 'GET') {
    init.headers['Content-Type'] = 'application/json';
    init.body = isFormEncoded ? body : JSON.stringify(body);
  }

  let url = root + path.replace(/^\//, '');
  if (body !== undefined && method === 'GET') {
    const qs = new URLSearchParams(body).toString();
    if (qs) url += (url.includes('?') ? '&' : '?') + qs;
  }

  const res = await fetch(url, init);
  let data = null;
  try {
    data = await res.json();
  } catch (e) {
    // Non-JSON response (shouldn't normally happen on these routes).
  }

  return { ok: res.ok, status: res.status, data: data || {} };
}

export const api = {
  otpStart: (phone) => call('POST', 'otp/start', { phone }),
  otpVerify: (phone, code) => call('POST', 'otp/verify', { phone, code }),
  register: (payload) => call('POST', 'register', payload),
  infoUser: () => call('GET', 'info/user'),
  dashboard: () => call('GET', 'customer/dashboard'),
  phoneChangeRequest: (requestedPhone) =>
    call('POST', 'customer/phone-change-request', { requested_phone: requestedPhone }),
  infoBreeds: () => call('GET', 'info/breeds'),
  addressValidate: (address) => call('POST', 'address/validate', { address }),
  billingPlans: (petId) => call('GET', 'billing/plans', { pet_id: petId }),
  billingCheckout: (planKey, petIds) =>
    call('POST', 'billing/checkout', { plan_key: planKey, pet_ids: petIds }),
  slotAvailable: (petCount) => call('GET', 'slot/available', { pet_count: petCount }),
  slotBook: (petId, slotId) => call('POST', 'slot/book', { pet_id: petId, slot_id: slotId }),
  attachPet: (payload) => call('POST', 'customer/attach-pet', payload),
  detachPet: (petId) => call('POST', 'customer/detach-pet', { pet_id: petId }),
  conferenceToken: (petId) => call('GET', 'conference-token', { pet_id: petId }),
  imageUpload: (file) => {
    const { root, nonce } = getConfig();
    const form = new FormData();
    form.append('image', file);
    return fetch(root + 'image/upload', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'X-WP-Nonce': nonce },
      body: form,
    }).then(async (res) => {
      const data = await res.json().catch(() => ({}));
      return { ok: res.ok, status: res.status, data };
    });
  },
  imageAssign: (imageId, petId) =>
    call('POST', 'image/assign', { image_id: imageId, pet_id: petId }),
  confirmReturn: (checkoutRef, outcome) =>
    call('POST', 'billing/confirm-return', { checkout_ref: checkoutRef, outcome }),
};

// -----------------------------------------------------------------------
// Continuation storage: survives the full-page redirect to Stripe and back.
// Only holds non-sensitive UI-flow state (which pet, which plan variant) --
// never the customer_token, which stays server-side in the HttpOnly cookie.
// -----------------------------------------------------------------------

const CONTINUATION_KEY = 'govettyBookingContinuation';

export function saveContinuation(data) {
  try {
    sessionStorage.setItem(CONTINUATION_KEY, JSON.stringify(data));
  } catch (e) {
    // sessionStorage unavailable (private browsing etc.) -- non-fatal,
    // the payment-success screen will just fall back to a generic state.
  }
}

export function readContinuation() {
  try {
    const raw = sessionStorage.getItem(CONTINUATION_KEY);
    return raw ? JSON.parse(raw) : null;
  } catch (e) {
    return null;
  }
}

export function clearContinuation() {
  try {
    sessionStorage.removeItem(CONTINUATION_KEY);
  } catch (e) {
    /* noop */
  }
}
