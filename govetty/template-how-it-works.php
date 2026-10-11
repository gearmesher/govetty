<?php
/**
 * Template Name: How It Works Template
 * Post Type: page
 * Description: A premium full-width "How It Works" page template that follows the same
 * structural + spacing conventions as the Home Fullwidth Template, without the
 * default sidebar limitations.
 */

// Safety check to prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header(); ?>

<main class="how-main">

  <!-- ============ HERO ============ -->
  <section class="hero" aria-label="Vetty how-it-works hero image">
    <img
      class="hero__img"
      src="/wp-content/uploads/2026/08/hiw-banner-scaled.webp"
      alt="Pet owner using a laptop to video call a Vetty veterinarian"
      loading="eager"
      fetchpriority="high"
    >
  </section>

  <!-- ============ INTRO ============ -->
  <section class="intro">
    <h1 class="intro__title reveal">
      Simple, Seamless Care<br>
      in 4 Easy Steps
    </h1>
    <p class="intro__subtitle reveal">
      Everything you need for your pet&rsquo;s health,<br>
      from scheduling to recovery.
    </p>
  </section>

  <!-- ============ 4-STEP PROCESS GRAPHIC ============ -->
  <section class="steps">
    <div class="steps__frame reveal">
      <img
        class="steps__img"
        src="/wp-content/uploads/2026/08/steps-graphic.webp"
        alt="Illustration of Vetty's 4-step process: book an appointment, video call a licensed vet, receive a treatment plan, and follow up on your pet's recovery"
        loading="lazy"
      >
      <!-- Checkmarks overlaid on a features list drawn within the graphic above -->
      <ul class="steps__checks" role="list" aria-hidden="true">
        <li style="--pos: 22.58%"></li>
        <li style="--pos: 24.63%"></li>
        <li style="--pos: 26.76%"></li>
        <li style="--pos: 28.87%"></li>
        <li style="--pos: 30.98%"></li>
        <li style="--pos: 33.13%"></li>
      </ul>
    </div>
  </section>

  <!-- ============ KEY BENEFITS ============ -->
  <section class="benefits">
    <div class="benefits__inner">
      <img class="benefits__leaf reveal" src="/wp-content/uploads/2026/08/leaf.svg" alt="" aria-hidden="true">
      <h2 class="section-heading section-heading--center reveal">Key Benefits of Vetty Care</h2>

      <ul class="benefits-grid" role="list">
        <li class="benefit reveal">
          <img class="benefit__icon" src="/wp-content/uploads/2026/08/icon-24-7-access.svg" alt="" aria-hidden="true">
          <h3 class="benefit__title">24/7 Access</h3>
          <p class="benefit__desc">Daily and alias schedule your video call with Vet.</p>
        </li>

        <li class="benefit reveal">
          <img class="benefit__icon" src="/wp-content/uploads/2026/08/icon-pharmacy-pickup.svg" alt="" aria-hidden="true">
          <h3 class="benefit__title">Local Pharmacy Pickup</h3>
          <p class="benefit__desc">Convenient prescription pickup near you.</p>
        </li>

        <li class="benefit reveal">
          <img class="benefit__icon" src="/wp-content/uploads/2026/08/icon-verified-vets-1.svg" alt="" aria-hidden="true">
          <h3 class="benefit__title">Verified Canadian Vets</h3>
          <p class="benefit__desc">Connect with fully licensed and experienced Canadian professionals.</p>
        </li>

        <li class="benefit reveal">
          <img class="benefit__icon" src="/wp-content/uploads/2026/08/icon-followup.svg" alt="" aria-hidden="true">
          <h3 class="benefit__title">Follow-up Works</h3>
          <p class="benefit__desc">We are here to support your follow-up care.</p>
        </li>

        <li class="benefit reveal">
          <img class="benefit__icon" src="/wp-content/uploads/2026/08/icon-care-plans.svg" alt="" aria-hidden="true">
          <h3 class="benefit__title">Personalized Care Plans</h3>
          <p class="benefit__desc">Get a clear plan for your pet&rsquo;s recovery and long-term health.</p>
        </li>

        <li class="benefit reveal">
          <img class="benefit__icon" src="/wp-content/uploads/2026/08/icon-verified-vets-2.svg" alt="" aria-hidden="true">
          <h3 class="benefit__title">Verified Canadian Vets</h3>
          <p class="benefit__desc">Connect with a fully licensed professional for you.</p>
        </li>
      </ul>
    </div>
  </section>

</main>

<script>
/**
 * Vetty — How It Works — Main content interactions
 * Currently: subtle scroll-reveal for section content.
 * Respects prefers-reduced-motion (also enforced in CSS as a fallback).
 */
(function () {
  "use strict";

  // Elements right below the hero are already in (or very near) the
  // initial viewport, so they reveal immediately on load rather than
  // waiting for a scroll-triggered IntersectionObserver hit.
  document
    .querySelectorAll(".intro .reveal")
    .forEach((el) => el.classList.add("is-visible"));

  const prefersReducedMotion = window.matchMedia(
    "(prefers-reduced-motion: reduce)"
  ).matches;

  const revealEls = document.querySelectorAll(
    ".reveal:not(.intro .reveal)"
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
