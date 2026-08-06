<?php
/**
 * Template Name: FAQ Template
 * Post Type: page
 * Description: A premium full-width "FAQ" page template that follows the same
 * structural + spacing conventions as the Home Fullwidth Template, without the
 * default sidebar limitations.
 */

// Safety check to prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header(); ?>

<main class="faq-main">

  <!-- ============ HERO ============ -->
  <section class="hero" aria-label="Vetty FAQ hero image">
    <img
      class="hero__img"
      src="/wp-content/uploads/2026/08/faq-banner-scaled.webp"
      alt="Happy dog and cat together"
      loading="eager"
      fetchpriority="high"
    >
  </section>

  <!-- ============ FAQ CATEGORIES ============ -->
  <section class="faq">
    <div class="faq__inner">

      <div class="faq__category reveal">
        <h2 class="faq__heading">General Questions</h2>
        <img class="faq__leaf" src="https://www.figma.com/api/mcp/asset/6c3ad17b-01a9-40a2-98d3-d012af196c1b.svg" alt="" aria-hidden="true">
        <div class="faq__list">

          <div class="faq-item">
            <button class="faq-item__question" aria-expanded="false">
              <span>What is Vetty?</span>
              <img class="faq-item__icon" src="https://www.figma.com/api/mcp/asset/a5d75726-6da7-4ddf-ae41-2e3a4fd71601.svg" alt="">
            </button>
            <div class="faq-item__answer">
              <p>Vetty is a Canadian virtual veterinary care service that connects you with
                licensed veterinarians by video call, so you can get trusted advice for your pet
                from wherever you are.</p>
            </div>
          </div>

          <div class="faq-item">
            <button class="faq-item__question" aria-expanded="false">
              <span>How does it work?</span>
              <img class="faq-item__icon" src="https://www.figma.com/api/mcp/asset/a5d75726-6da7-4ddf-ae41-2e3a4fd71601.svg" alt="">
            </button>
            <div class="faq-item__answer">
              <p>Book an appointment, join a video call with a licensed Canadian veterinarian,
                and walk away with a personalized care plan &mdash; prescriptions and follow-up
                support included when needed.</p>
            </div>
          </div>

          <div class="faq-item">
            <button class="faq-item__question" aria-expanded="false">
              <span>Who are the veterinarians?</span>
              <img class="faq-item__icon" src="https://www.figma.com/api/mcp/asset/a5d75726-6da7-4ddf-ae41-2e3a4fd71601.svg" alt="">
            </button>
            <div class="faq-item__answer">
              <p>Every Vetty veterinarian is fully licensed in Canada, with hands-on clinical
                experience across general practice, surgery, and preventive care.</p>
            </div>
          </div>

        </div>
      </div>

      <div class="faq__category reveal">
        <h2 class="faq__heading">Services &amp; Consultations</h2>
        <img class="faq__leaf" src="https://www.figma.com/api/mcp/asset/6c3ad17b-01a9-40a2-98d3-d012af196c1b.svg" alt="" aria-hidden="true">
        <div class="faq__list">

          <div class="faq-item">
            <button class="faq-item__question" aria-expanded="false">
              <span>What conditions can be treated?</span>
              <img class="faq-item__icon" src="https://www.figma.com/api/mcp/asset/a5d75726-6da7-4ddf-ae41-2e3a4fd71601.svg" alt="">
            </button>
            <div class="faq-item__answer">
              <p>Vetty can help with everyday concerns like skin issues, digestive upset,
                behavioral questions, and general wellness. For emergencies or conditions
                requiring hands-on treatment, your vet will refer you to an in-person clinic.</p>
            </div>
          </div>

          <div class="faq-item">
            <button class="faq-item__question" aria-expanded="false">
              <span>Can the vet prescribe medication?</span>
              <img class="faq-item__icon" src="https://www.figma.com/api/mcp/asset/a5d75726-6da7-4ddf-ae41-2e3a4fd71601.svg" alt="">
            </button>
            <div class="faq-item__answer">
              <p>Yes &mdash; when clinically appropriate, your vet can prescribe medication and
                route it to a local pharmacy for convenient pickup.</p>
            </div>
          </div>

          <div class="faq-item">
            <button class="faq-item__question" aria-expanded="false">
              <span>How long is a consultation</span>
              <img class="faq-item__icon" src="https://www.figma.com/api/mcp/asset/a5d75726-6da7-4ddf-ae41-2e3a4fd71601.svg" alt="">
            </button>
            <div class="faq-item__answer">
              <p>Most video consultations run about 15&ndash;20 minutes, with extra time
                available for more complex concerns.</p>
            </div>
          </div>

        </div>
      </div>

      <div class="faq__category reveal">
        <h2 class="faq__heading">Booking &amp; Appointments</h2>
        <img class="faq__leaf" src="https://www.figma.com/api/mcp/asset/6c3ad17b-01a9-40a2-98d3-d012af196c1b.svg" alt="" aria-hidden="true">
        <div class="faq__list">

          <div class="faq-item">
            <button class="faq-item__question" aria-expanded="false">
              <span>Do i need to book in advance?</span>
              <img class="faq-item__icon" src="https://www.figma.com/api/mcp/asset/a5d75726-6da7-4ddf-ae41-2e3a4fd71601.svg" alt="">
            </button>
            <div class="faq-item__answer">
              <p>Not necessarily &mdash; same-day appointments are often available, and our
                24/7 access means you can also book ahead for a time that works for you.</p>
            </div>
          </div>

          <div class="faq-item">
            <button class="faq-item__question" aria-expanded="false">
              <span>Can i choose my veterinarian?</span>
              <img class="faq-item__icon" src="https://www.figma.com/api/mcp/asset/a5d75726-6da7-4ddf-ae41-2e3a4fd71601.svg" alt="">
            </button>
            <div class="faq-item__answer">
              <p>Yes &mdash; you can request a specific veterinarian for your appointment, or
                let us match you with the next available licensed vet.</p>
            </div>
          </div>

        </div>
      </div>

      <div class="faq__category reveal">
        <h2 class="faq__heading">Pricing &amp; Payments</h2>
        <img class="faq__leaf" src="https://www.figma.com/api/mcp/asset/6c3ad17b-01a9-40a2-98d3-d012af196c1b.svg" alt="" aria-hidden="true">
        <div class="faq__list">

          <div class="faq-item">
            <button class="faq-item__question" aria-expanded="false">
              <span>What is the cost?</span>
              <img class="faq-item__icon" src="https://www.figma.com/api/mcp/asset/a5d75726-6da7-4ddf-ae41-2e3a4fd71601.svg" alt="">
            </button>
            <div class="faq-item__answer">
              <p>Plans start at $49/month, with annual and premium options available for more
                frequent visits and added savings. See our Pricing page for full details.</p>
            </div>
          </div>

          <div class="faq-item">
            <button class="faq-item__question" aria-expanded="false">
              <span>Do you accept insurance?</span>
              <img class="faq-item__icon" src="https://www.figma.com/api/mcp/asset/a5d75726-6da7-4ddf-ae41-2e3a4fd71601.svg" alt="">
            </button>
            <div class="faq-item__answer">
              <p>We don&rsquo;t bill pet insurance directly, but we&rsquo;ll provide a detailed
                receipt you can submit to your provider for reimbursement.</p>
            </div>
          </div>

        </div>
      </div>

      <div class="faq__category reveal">
        <h2 class="faq__heading">Technical Questions</h2>
        <img class="faq__leaf" src="https://www.figma.com/api/mcp/asset/6c3ad17b-01a9-40a2-98d3-d012af196c1b.svg" alt="" aria-hidden="true">
        <div class="faq__list">

          <div class="faq-item">
            <button class="faq-item__question" aria-expanded="false">
              <span>What are the system requirements?</span>
              <img class="faq-item__icon" src="https://www.figma.com/api/mcp/asset/a5d75726-6da7-4ddf-ae41-2e3a4fd71601.svg" alt="">
            </button>
            <div class="faq-item__answer">
              <p>Vetty works right in your browser on any modern phone, tablet, or computer with
                a camera, microphone, and internet connection &mdash; no app install required.</p>
            </div>
          </div>

          <div class="faq-item">
            <button class="faq-item__question" aria-expanded="false">
              <span>Is my data secure?</span>
              <img class="faq-item__icon" src="https://www.figma.com/api/mcp/asset/a5d75726-6da7-4ddf-ae41-2e3a4fd71601.svg" alt="">
            </button>
            <div class="faq-item__answer">
              <p>Yes &mdash; your information and consultation records are encrypted and handled
                in line with Canadian data protection and privacy standards.</p>
            </div>
          </div>

        </div>
      </div>

    </div>
  </section>

</main>

<script>
/**
 * Vetty — FAQ — Main content interactions
 * - Accordion: each question toggles independently (click or Enter/Space).
 * - Scroll-reveal for section content (respects prefers-reduced-motion).
 */
(function () {
  "use strict";

  /* ---------- Accordion ---------- */
  document.querySelectorAll(".faq-item").forEach((item) => {
    const question = item.querySelector(".faq-item__question");
    if (!question) return;

    question.addEventListener("click", () => {
      const isOpen = item.classList.toggle("is-open");
      question.setAttribute("aria-expanded", String(isOpen));
    });
  });

  /* ---------- Scroll reveal ---------- */
  // The first category is already in (or very near) the initial
  // viewport, so it reveals immediately on load rather than waiting
  // for a scroll-triggered IntersectionObserver hit.
  const firstCategory = document.querySelector(".faq__category");
  if (firstCategory) {
    firstCategory.classList.add("is-visible");
  }

  const prefersReducedMotion = window.matchMedia(
    "(prefers-reduced-motion: reduce)"
  ).matches;

  const revealEls = document.querySelectorAll(
    ".reveal:not(.faq__category:first-of-type)"
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
      threshold: 0.1,
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
