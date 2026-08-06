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
        src="https://www.figma.com/api/mcp/asset/ed56b256-8326-4340-8d8c-686f9ec82a68.png"
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
      <img class="benefits__leaf reveal" src="https://www.figma.com/api/mcp/asset/b28a4b51-2764-4778-9939-0eff925477d5.svg" alt="" aria-hidden="true">
      <h2 class="section-heading section-heading--center reveal">Key Benefits of Vetty Care</h2>

      <ul class="benefits-grid" role="list">
        <li class="benefit reveal">
          <img class="benefit__icon" src="https://www.figma.com/api/mcp/asset/ecb9b8c4-3d43-4982-bb39-6d9b73969aad.svg" alt="" aria-hidden="true">
          <h3 class="benefit__title">24/7 Access</h3>
          <p class="benefit__desc">Daily and alias schedule your video call with Vet.</p>
        </li>

        <li class="benefit reveal">
          <img class="benefit__icon" src="https://www.figma.com/api/mcp/asset/dad8a060-2434-4e77-a11b-527c18438793.svg" alt="" aria-hidden="true">
          <h3 class="benefit__title">Local Pharmacy Pickup</h3>
          <p class="benefit__desc">Convenient prescription pickup near you.</p>
        </li>

        <li class="benefit reveal">
          <img class="benefit__icon" src="https://www.figma.com/api/mcp/asset/d95929e7-6ffd-4385-b77b-e604898441d2.svg" alt="" aria-hidden="true">
          <h3 class="benefit__title">Verified Canadian Vets</h3>
          <p class="benefit__desc">Connect with fully licensed and experienced Canadian professionals.</p>
        </li>

        <li class="benefit reveal">
          <img class="benefit__icon" src="https://www.figma.com/api/mcp/asset/a762b381-f7e4-4474-983f-af3270fc7332.svg" alt="" aria-hidden="true">
          <h3 class="benefit__title">Follow-up Works</h3>
          <p class="benefit__desc">We are here to support your follow-up care.</p>
        </li>

        <li class="benefit reveal">
          <img class="benefit__icon" src="https://www.figma.com/api/mcp/asset/60349fb8-7ae1-4597-b1d1-fe095cbd622e.svg" alt="" aria-hidden="true">
          <h3 class="benefit__title">Personalized Care Plans</h3>
          <p class="benefit__desc">Get a clear plan for your pet&rsquo;s recovery and long-term health.</p>
        </li>

        <li class="benefit reveal">
          <img class="benefit__icon" src="https://www.figma.com/api/mcp/asset/5916dbce-e303-4dbc-906b-d891a470dfde.svg" alt="" aria-hidden="true">
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
