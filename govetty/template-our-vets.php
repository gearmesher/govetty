<?php
/**
 * Template Name: Our Vets Template
 * Post Type: page
 * Description: A premium full-width "Our Vets" page template that follows the same
 * structural + spacing conventions as the Home Fullwidth Template, without the
 * default sidebar limitations.
 */

// Safety check to prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header(); ?>

<main class="vets-main">

  <!-- ============ HERO ============ -->
  <section class="hero" aria-label="Vetty veterinary team hero image">
    <img
      class="hero__img"
      src="/wp-content/uploads/2026/08/our-vets-banner-scaled.webp"
      alt="Vetty's veterinary team standing together in a clinic with a golden retriever"
      loading="eager"
      fetchpriority="high"
    >
  </section>

  <!-- ============ INTRO ============ -->
  <section class="intro">
    <p class="intro__eyebrow reveal">Meet Our Veterinarians</p>
    <h1 class="intro__title reveal">Experienced. Compassionate. Canadian.</h1>
  </section>

  <!-- ============ VET PROFILES ============ -->
  <section class="vets" aria-label="Veterinarian profiles">
    <ul class="vets-list" role="list">

      <li class="vet reveal">
        <div class="vet__media">
          <img class="vet__leaf" src="/wp-content/uploads/2026/08/icon-maple-leaf.svg" alt="" aria-hidden="true">
          <img class="vet__photo" src="/wp-content/uploads/2026/08/vet-sarah-mitchell.webp" alt="Dr. Sarah Mitchell, DVM" loading="lazy">
        </div>
        <div class="vet__info">
          <h2 class="vet__name">Dr. Sarah Mitchell</h2>
          <p class="vet__credential">DVM</p>
          <p class="vet__experience">10+ years experience</p>
          <p class="vet__bio">
            Dedicated and compassionate Veterinarian with over 10 years of experience in small
            animal medicine, preventive care, and emergency triage. Proven track record in
            surgical procedures, diagnostic imaging, and developing customized, long-term health
            plans for pets. Passionate about client education and improving animal welfare
            through accessible veterinary care.
          </p>
        </div>
      </li>

      <li class="vet reveal">
        <div class="vet__media">
          <img class="vet__leaf" src="/wp-content/uploads/2026/08/icon-maple-leaf.svg" alt="" aria-hidden="true">
          <img class="vet__photo" src="/wp-content/uploads/2026/08/vet-michael-lee.webp" alt="Dr. Michael Lee, DVM" loading="lazy">
        </div>
        <div class="vet__info">
          <h2 class="vet__name">Dr. Michael Lee</h2>
          <p class="vet__credential">DVM</p>
          <p class="vet__experience">12+ years experience</p>
          <p class="vet__bio">
            Compassionate and detail-oriented Veterinarian with over 9 years of experience in
            small animal practice, surgical care, and emergency medicine. Adept at diagnosing
            complex medical conditions, performing advanced soft-tissue surgeries, and crafting
            individualized wellness plans to maximize pet longevity. Dedicated to building
            strong, trusting relationships with pet owners through clear communication and
            empathetic, high-quality clinical care.
          </p>
        </div>
      </li>

      <li class="vet reveal">
        <div class="vet__media">
          <img class="vet__leaf" src="/wp-content/uploads/2026/08/icon-maple-leaf.svg" alt="" aria-hidden="true">
          <img class="vet__photo" src="/wp-content/uploads/2026/08/vet-james-carter.webp" alt="Dr. James Carter, DVM" loading="lazy">
        </div>
        <div class="vet__info">
          <h2 class="vet__name">Dr. James Carter</h2>
          <p class="vet__credential">DVM</p>
          <p class="vet__experience">15+ years experience</p>
          <p class="vet__bio">
            Highly skilled and empathetic Veterinarian with over 15+ years of experience in
            veterinary general practice, diagnostic imaging, and preventive medicine. Specializes
            in managing chronic illness, complex internal medicine cases, and senior pet care.
            Committed to delivering evidence-based clinical treatment while providing clear,
            comforting support to pet parents to ensure optimal long-term health outcomes for
            their animals.
          </p>
        </div>
      </li>

      <li class="vet reveal">
        <div class="vet__media">
          <img class="vet__leaf" src="/wp-content/uploads/2026/08/icon-maple-leaf.svg" alt="" aria-hidden="true">
          <img class="vet__photo" src="/wp-content/uploads/2026/08/vet-priya-sharma.webp" alt="Dr. Priya Sharma, DVM" loading="lazy">
        </div>
        <div class="vet__info">
          <h2 class="vet__name">Dr. Priya Sharma</h2>
          <p class="vet__credential">DVM</p>
          <p class="vet__experience">8+ years experience</p>
          <p class="vet__bio">
            Dedicated and intuitive Veterinarian with over 8 years of expertise in preventative
            pet care, feline medicine, and clinical pathology. Known for taking a gentle,
            low-stress approach to patient handling and creating clear, accessible wellness
            programs tailored to each pet&rsquo;s lifestyle. Passionate about empowering pet
            parents through education, early disease detection, and holistic post-treatment
            follow-up care.
          </p>
        </div>
      </li>

    </ul>
  </section>

</main>


<script>
/**
 * Vetty — Our Vets — Main content interactions
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
