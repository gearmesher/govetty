<?php
/**
 * Template Name: Prices Template
 * Post Type: page
 * Description: A premium full-width "Prices" page template that follows the same
 * structural + spacing conventions as the Home Fullwidth Template, without the
 * default sidebar limitations.
 */

// Safety check to prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header(); ?>

<main class="prices-main">

  <!-- ============ HERO ============ -->
  <section class="hero" aria-label="Vetty pricing hero image">
    <img
      class="hero__img"
      src="https://www.figma.com/api/mcp/asset/923f2961-9b82-4c59-9538-c45fbc90d94f.png"
      alt="Veterinarian caring for a pet in a bright clinic room"
      loading="eager"
      fetchpriority="high"
    >
  </section>

  <!-- ============ PRICING PLANS ============ -->
  <section class="pricing" aria-label="Pricing plans">
    <div class="pricing__inner">
      <ul class="pricing-grid" role="list">

        <!-- Monthly -->
        <li class="plan plan--monthly reveal">
          <img class="plan__leaf" src="https://www.figma.com/api/mcp/asset/a16e23dd-0ffb-48c4-911a-ef7446d69515.svg" alt="" aria-hidden="true">
          <h3 class="plan__name">Monthly Plan</h3>
          <p class="plan__subtitle">4 virtual vet appointments</p>
          <p class="plan__price">
            <span class="plan__currency">$</span><span class="plan__amount">49</span><span class="plan__period">/month</span>
          </p>

          <ul class="plan__features" role="list">
            <li><img src="https://www.figma.com/api/mcp/asset/a524645e-388e-477b-94e4-0479fb71bcbe.svg" alt="" aria-hidden="true">4 Virtual vet appointments</li>
            <li><img src="https://www.figma.com/api/mcp/asset/a524645e-388e-477b-94e4-0479fb71bcbe.svg" alt="" aria-hidden="true">Accident &amp; Illness Coverage</li>
            <li><img src="https://www.figma.com/api/mcp/asset/a524645e-388e-477b-94e4-0479fb71bcbe.svg" alt="" aria-hidden="true">Access to 24/7 Vet Consultation</li>
            <li><img src="https://www.figma.com/api/mcp/asset/a524645e-388e-477b-94e4-0479fb71bcbe.svg" alt="" aria-hidden="true">Medications Coverage</li>
            <li><img src="https://www.figma.com/api/mcp/asset/a524645e-388e-477b-94e4-0479fb71bcbe.svg" alt="" aria-hidden="true">Claims &amp; Reimbursements</li>
            <li><img src="https://www.figma.com/api/mcp/asset/a524645e-388e-477b-94e4-0479fb71bcbe.svg" alt="" aria-hidden="true">Cancel Anytime</li>
          </ul>

          <a class="plan__cta plan__cta--light" href="#">CHOOSE MONTHLY</a>
        </li>

        <!-- Annual (featured) -->
        <li class="plan plan--annual plan--featured reveal">
          <img class="plan__leaf" src="https://www.figma.com/api/mcp/asset/8f3a563b-7bb6-40c5-a246-b49acef158db.svg" alt="" aria-hidden="true">
          <h3 class="plan__name">Annual Plan</h3>
          <p class="plan__subtitle">6 virtual vet appointments</p>
          <p class="plan__price">
            <span class="plan__currency">$</span><span class="plan__amount">499</span><span class="plan__period">/year</span>
          </p>

          <p class="plan__badge plan__badge--solid">Save 15% compared to monthly</p>

          <ul class="plan__features" role="list">
            <li><img src="https://www.figma.com/api/mcp/asset/01c902a5-da25-41fe-bdde-fa8ae550a806.svg" alt="" aria-hidden="true">6 Virtual vet appointments</li>
            <li><img src="https://www.figma.com/api/mcp/asset/01c902a5-da25-41fe-bdde-fa8ae550a806.svg" alt="" aria-hidden="true">Everything in Monthly Plan</li>
            <li><img src="https://www.figma.com/api/mcp/asset/01c902a5-da25-41fe-bdde-fa8ae550a806.svg" alt="" aria-hidden="true">1 Month Free (Save 15%)</li>
            <li><img src="https://www.figma.com/api/mcp/asset/01c902a5-da25-41fe-bdde-fa8ae550a806.svg" alt="" aria-hidden="true">Priority Claim Processing</li>
            <li><img src="https://www.figma.com/api/mcp/asset/01c902a5-da25-41fe-bdde-fa8ae550a806.svg" alt="" aria-hidden="true">Annual Check-up Benefit</li>
            <li><img src="https://www.figma.com/api/mcp/asset/01c902a5-da25-41fe-bdde-fa8ae550a806.svg" alt="" aria-hidden="true">Cancel Anytime</li>
          </ul>

          <a class="plan__cta plan__cta--solid" href="#">CHOOSE ANNUAL</a>
        </li>

        <!-- Premium -->
        <li class="plan plan--premium reveal">
          <img class="plan__leaf" src="https://www.figma.com/api/mcp/asset/8f3a563b-7bb6-40c5-a246-b49acef158db.svg" alt="" aria-hidden="true">
          <h3 class="plan__name">Premium Plan</h3>
          <p class="plan__subtitle">8 virtual vet appointments</p>
          <p class="plan__price">
            <span class="plan__currency">$</span><span class="plan__amount">699</span><span class="plan__period">/year</span>
          </p>

          <p class="plan__badge plan__badge--outline">Save 15% compared to monthly</p>

          <ul class="plan__features" role="list">
            <li><img src="https://www.figma.com/api/mcp/asset/01c902a5-da25-41fe-bdde-fa8ae550a806.svg" alt="" aria-hidden="true">8 Virtual vet appointments</li>
            <li><img src="https://www.figma.com/api/mcp/asset/01c902a5-da25-41fe-bdde-fa8ae550a806.svg" alt="" aria-hidden="true">Claims &amp; Reimbursements</li>
            <li><img src="https://www.figma.com/api/mcp/asset/01c902a5-da25-41fe-bdde-fa8ae550a806.svg" alt="" aria-hidden="true">Everything in Annual Plan</li>
            <li><img src="https://www.figma.com/api/mcp/asset/01c902a5-da25-41fe-bdde-fa8ae550a806.svg" alt="" aria-hidden="true">Accident &amp; Illness Coverage</li>
            <li><img src="https://www.figma.com/api/mcp/asset/01c902a5-da25-41fe-bdde-fa8ae550a806.svg" alt="" aria-hidden="true">Access to 24/7 Vet Consultation</li>
            <li><img src="https://www.figma.com/api/mcp/asset/01c902a5-da25-41fe-bdde-fa8ae550a806.svg" alt="" aria-hidden="true">Medications Coverage</li>
            <li><img src="https://www.figma.com/api/mcp/asset/01c902a5-da25-41fe-bdde-fa8ae550a806.svg" alt="" aria-hidden="true">Cancel Anytime</li>
          </ul>

          <a class="plan__cta plan__cta--solid" href="#">CHOOSE PREMIUM</a>
        </li>

      </ul>
    </div>
  </section>

  <!-- ============ INCLUDED IN EVERY VISIT ============ -->
  <section class="included">
    <div class="included__inner">
      <h2 class="section-heading section-heading--center reveal">Included in Every Visit</h2>

      <ul class="included-grid" role="list">
        <li class="included-item reveal">
          <span class="included-item__icon">
            <img class="included-item__circle" src="https://www.figma.com/api/mcp/asset/914278e0-50a5-409b-bd70-eefcaa0d0d37.svg" alt="" aria-hidden="true">
            <img class="included-item__glyph" src="https://www.figma.com/api/mcp/asset/96dabb22-a5fa-49f1-a08c-5a9d3fae037b.svg" alt="" aria-hidden="true">
          </span>
          <h3 class="included-item__title">Video Call with Vet</h3>
          <p class="included-item__desc">Daily and alias schedule your video call with Vet.</p>
        </li>

        <li class="included-item reveal">
          <span class="included-item__icon">
            <img class="included-item__circle" src="https://www.figma.com/api/mcp/asset/914278e0-50a5-409b-bd70-eefcaa0d0d37.svg" alt="" aria-hidden="true">
            <img class="included-item__glyph" src="https://www.figma.com/api/mcp/asset/f236361d-3d3c-4051-90e9-af8d8d01a3ae.svg" alt="" aria-hidden="true">
          </span>
          <h3 class="included-item__title">Follow-up Support</h3>
          <p class="included-item__desc">We have to up support your follow-up support.</p>
        </li>

        <li class="included-item reveal">
          <span class="included-item__icon">
            <img class="included-item__circle" src="https://www.figma.com/api/mcp/asset/914278e0-50a5-409b-bd70-eefcaa0d0d37.svg" alt="" aria-hidden="true">
            <img class="included-item__glyph" src="https://www.figma.com/api/mcp/asset/1e525558-d915-4255-a49b-ca7db2eed7e5.svg" alt="" aria-hidden="true">
          </span>
          <h3 class="included-item__title">Prescription Routing</h3>
          <p class="included-item__desc">Prescribe the medication and manage prescription routing.</p>
        </li>

        <li class="included-item reveal">
          <span class="included-item__icon">
            <img class="included-item__circle" src="https://www.figma.com/api/mcp/asset/914278e0-50a5-409b-bd70-eefcaa0d0d37.svg" alt="" aria-hidden="true">
            <img class="included-item__glyph" src="https://www.figma.com/api/mcp/asset/1fc4d4fc-3df5-4a60-93a2-0cb37b017899.svg" alt="" aria-hidden="true">
          </span>
          <h3 class="included-item__title">Care Summary PDF</h3>
          <p class="included-item__desc">Get access to answers and your care summary PDF.</p>
        </li>
      </ul>
    </div>
  </section>

  <!-- ============ TESTIMONIAL ============ -->
  <section class="testimonial">
    <div class="testimonial__inner">
      <img class="testimonial__leaf reveal" src="https://www.figma.com/api/mcp/asset/a16e23dd-0ffb-48c4-911a-ef7446d69515.svg" alt="" aria-hidden="true">

      <h2 class="testimonial__heading reveal">Pet parents says it best</h2>

      <div class="testimonial__stars reveal" role="img" aria-label="5 out of 5 stars">
        <img src="https://www.figma.com/api/mcp/asset/a44bdcc0-1278-4969-a2c0-7d0572ff7c2f.svg" alt="" aria-hidden="true">
        <img src="https://www.figma.com/api/mcp/asset/a44bdcc0-1278-4969-a2c0-7d0572ff7c2f.svg" alt="" aria-hidden="true">
        <img src="https://www.figma.com/api/mcp/asset/a44bdcc0-1278-4969-a2c0-7d0572ff7c2f.svg" alt="" aria-hidden="true">
        <img src="https://www.figma.com/api/mcp/asset/a44bdcc0-1278-4969-a2c0-7d0572ff7c2f.svg" alt="" aria-hidden="true">
        <img src="https://www.figma.com/api/mcp/asset/a44bdcc0-1278-4969-a2c0-7d0572ff7c2f.svg" alt="" aria-hidden="true">
      </div>

      <blockquote class="testimonial__quote reveal">
        <p class="testimonial__lead">&ldquo;Fast, reliable and affordable!&rdquo;</p>
        <p>&ldquo;I&rsquo;ve been using Canadian Vet Online for over a year now for my two golden
          retrievers. The process is so easy &mdash; I just upload my pet&rsquo;s records and a
          licensed vet responds within hours. Saved me so much time and money compared to
          in-clinic visits. Highly recommend!&rdquo;</p>
        <cite class="testimonial__cite">&mdash; Sarah M., Ontario</cite>
      </blockquote>
    </div>
  </section>

</main>

<script>
/**
 * Vetty — Pricing — Main content interactions
 * Currently: subtle scroll-reveal for section content.
 * Respects prefers-reduced-motion (also enforced in CSS as a fallback).
 */
(function () {
  "use strict";

  // Elements right below the hero are already in (or very near) the
  // initial viewport, so they reveal immediately on load rather than
  // waiting for a scroll-triggered IntersectionObserver hit.
  document
    .querySelectorAll(".pricing .reveal")
    .forEach((el) => el.classList.add("is-visible"));

  const prefersReducedMotion = window.matchMedia(
    "(prefers-reduced-motion: reduce)"
  ).matches;

  const revealEls = document.querySelectorAll(
    ".reveal:not(.pricing .reveal)"
  );

  if (!revealEls.length) return;

  if (prefersReducedMotion || !("IntersectionObserver" in window)) {
    revealEls.forEach((el) => el.classList.add("is-visible"));
    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("is-visible");
          observer.unobserve(entry.target);
        }
      });
    },
    {
      threshold: 0.15,
      rootMargin: "0px 0px -60px 0px",
    }
  );

  revealEls.forEach((el, i) => {
    el.style.transitionDelay = `${(i % 4) * 60}ms`;
    observer.observe(el);
  });
})();
</script>

<?php get_footer(); ?>
