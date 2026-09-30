import React from 'react';
import { createRoot } from 'react-dom/client';

import App from './App';
import PostPaymentApp from './PostPaymentApp';
import ResumePlanSelection from './ResumePlanSelection';
import PersonalArea from './PersonalArea';
import { readContinuation, clearContinuation } from './api';

function mountBookingRoot() {
  const el = document.getElementById('govetty-booking-root');
  if (!el) return;

  const params = new URLSearchParams(window.location.search);
  const step = params.get('govetty_step');
  const checkoutRef = params.get('checkout_ref');

  if (step === 'payment-success') {
    const continuation = readContinuation();
    clearContinuation();
    createRoot(el).render(<PostPaymentApp continuation={continuation} checkoutRef={checkoutRef} />);
    return;
  }

  if (step === 'payment-cancelled') {
    const continuation = readContinuation();
    // Deliberately not cleared here -- ResumePlanSelection may need it
    // again if the customer picks a plan and gets sent to Stripe a second
    // time. It's cleared once they actually succeed (payment-success) or
    // when the tab is closed (sessionStorage).
    createRoot(el).render(<ResumePlanSelection continuation={continuation} checkoutRef={checkoutRef} />);
    return;
  }

  createRoot(el).render(<App />);
}

function mountPersonalArea() {
  const el = document.getElementById('govetty-personal-area-root');
  if (!el) return;
  createRoot(el).render(<PersonalArea />);
}

mountBookingRoot();
mountPersonalArea();
