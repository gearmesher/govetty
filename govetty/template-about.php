<?php
/**
 * Template Name: About Us Template
 * Post Type: page
 * Description: A premium full-width "About Us" page template that follows the same
 * structural + spacing conventions as the Home Fullwidth Template, without the
 * default sidebar limitations.
 */

// Safety check to prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header(); ?>

<main class="about-main">

  <!-- ============ HERO ============ -->
  <section class="hero" aria-label="Vetty veterinary team with a pet and pet owner">
    <img
      class="hero__img"
      src="https://www.figma.com/api/mcp/asset/ba0c76ef-713e-4f24-90a5-4aa4ce15a11f.png"
      alt="Veterinary team examining a dog alongside its owner in a bright clinic room"
      loading="eager"
      fetchpriority="high"
    >
  </section>

  <!-- ============ MISSION & VISION ============ -->
  <section class="mission-vision" aria-labelledby="mission-heading vision-heading">
    <div class="mission-vision__inner">

      <div class="mission-vision__media reveal">
        <img
          src="https://www.figma.com/api/mcp/asset/e4c23502-29d0-4c8e-a85b-0cf7e1b08a73.png"
          alt="Two veterinarians examining a dog and a cat together in an exam room"
          loading="lazy"
          width="683"
          height="1180"
        >
      </div>

      <div class="mission-vision__cards">
        <article class="mv-card reveal">
          <h2 class="mv-card__title" id="mission-heading">Our Mission</h2>
          <p class="mv-card__text">
            To deliver compassionate, high-quality, and accessible veterinary care for every pet.
            We are dedicated to building lifelong relationships with pet parents by providing
            personalized treatment plans, innovative healthcare solutions, and continuous support
            through every stage of a pet&rsquo;s life.
          </p>
        </article>

        <article class="mv-card reveal">
          <h2 class="mv-card__title" id="vision-heading">Our Vision</h2>
          <p class="mv-card__text">
            To redefine modern pet care by making expert veterinary services seamless, inclusive,
            and stress-free. We envision a future where every animal receives the highest standard
            of preventive and clinical care, empowering pet owners through education, empathy,
            and cutting-edge medicine.
          </p>
        </article>
      </div>

    </div>
  </section>

  <!-- ============ STORY & VALUES + PARTNERS ============ -->
  <section class="story-values">
    <div class="story-values__inner">

      <h2 class="section-heading section-heading--center reveal">Our Story &amp; Values</h2>

      <ul class="values-grid" role="list">

        <li class="value-card reveal">
          <span class="value-card__icon">
            <img class="value-card__circle" src="https://www.figma.com/api/mcp/asset/b23f05ce-b4aa-4a79-9b6d-f0600461633b.svg" alt="" aria-hidden="true">
            <img class="value-card__glyph" src="https://www.figma.com/api/mcp/asset/ebfb6acd-e345-44f1-9f01-a091afadd93f.svg" alt="" aria-hidden="true">
          </span>
          <h3 class="value-card__title">Specialized Care<br>for Every Pet</h3>
          <p class="value-card__desc">Convenient prescription<br>pickup near you.</p>
        </li>

        <li class="value-card reveal">
          <span class="value-card__icon">
            <img class="value-card__circle" src="https://www.figma.com/api/mcp/asset/b23f05ce-b4aa-4a79-9b6d-f0600461633b.svg" alt="" aria-hidden="true">
            <img class="value-card__glyph" src="https://www.figma.com/api/mcp/asset/56b8e343-f3b6-4591-a39d-144736919edf.svg" alt="" aria-hidden="true">
          </span>
          <h3 class="value-card__title">Compassion</h3>
          <p class="value-card__desc">Passionate about simplifying pet care access with empathy, care, and accessibility at the core.</p>
        </li>

        <li class="value-card reveal">
          <span class="value-card__icon">
            <img class="value-card__circle" src="https://www.figma.com/api/mcp/asset/b23f05ce-b4aa-4a79-9b6d-f0600461633b.svg" alt="" aria-hidden="true">
            <img class="value-card__glyph" src="https://www.figma.com/api/mcp/asset/8d3a1349-a9ef-4cf7-bd98-c132cb523632.svg" alt="" aria-hidden="true">
          </span>
          <h3 class="value-card__title">Accessibility</h3>
          <p class="value-card__desc">Committed to creating accessible solutions and delivering seamless care for pet owners everywhere.</p>
        </li>

        <li class="value-card reveal">
          <span class="value-card__icon">
            <img class="value-card__circle" src="https://www.figma.com/api/mcp/asset/b23f05ce-b4aa-4a79-9b6d-f0600461633b.svg" alt="" aria-hidden="true">
            <img class="value-card__glyph" src="https://www.figma.com/api/mcp/asset/bd75ef81-088c-4c9a-b856-3649be227210.svg" alt="" aria-hidden="true">
          </span>
          <h3 class="value-card__title">Innovation</h3>
          <p class="value-card__desc">Pioneering modern solutions and innovative connections to enhance potential health outcomes.</p>
        </li>

      </ul>

      <div class="partners">
        <h2 class="section-heading section-heading--center reveal">Meet our Partners</h2>
        <p class="partners__intro reveal">
          Vetty&rsquo;s network is supported by key partners, connecting Canadians to trusted
          pet health organizations and integrated care across the country.
        </p>

        <ul class="partners__logos reveal" role="list">
          <li>
            <img src="https://www.figma.com/api/mcp/asset/8b70d3b1-aca6-457b-a187-576378da54e2.png" alt="Canadian Veterinary Medical Association" loading="lazy">
          </li>
          <li>
            <img src="https://www.figma.com/api/mcp/asset/6f05fd56-62f0-48b0-8edd-26afafef9bae.png" alt="American Animal Hospital Association (AAHA)" loading="lazy">
          </li>
          <li>
            <img src="https://www.figma.com/api/mcp/asset/f96d96d3-fcca-4c93-959f-1956582ba256.png" alt="College of Veterinarians of Ontario" loading="lazy">
          </li>
          <li>
            <img src="https://www.figma.com/api/mcp/asset/5a29ea2a-da55-49b5-a146-81ba7a993338.png" alt="Fear Free Certified Professional" loading="lazy">
          </li>
        </ul>
      </div>

    </div>
  </section>

</main>

<script>
/**
 * Vetty — About Us — Main content interactions
 * Currently: subtle scroll-reveal for section content.
 * Respects prefers-reduced-motion (also enforced in CSS as a fallback).
 */
(function () {
  "use strict";

  // Elements right below the hero are already in (or very near) the
  // initial viewport, so they reveal immediately on load rather than
  // waiting for a scroll-triggered IntersectionObserver hit.
  document
    .querySelectorAll(".mission-vision .reveal")
    .forEach((el) => el.classList.add("is-visible"));

  const prefersReducedMotion = window.matchMedia(
    "(prefers-reduced-motion: reduce)"
  ).matches;

  const revealEls = document.querySelectorAll(
    ".reveal:not(.mission-vision .reveal)"
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
    // Stagger reveal slightly within the same section for a nicer cascade.
    el.style.transitionDelay = `${(i % 4) * 60}ms`;
    observer.observe(el);
  });
})();

</script>

<?php get_footer(); ?>
