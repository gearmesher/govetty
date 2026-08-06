<?php
/**
 * Template Name: Home Fullwidth Template
 * Post Type: page
 * Description: A premium full-width page template built for the custom home setup without default sidebar limitations.
 */

// Safety check to prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header(); ?>

<div style="width: 100%; overflow: hidden; position: relative;">
    <main id="primary" class="site-main home">
        <section class="group" aria-labelledby="hero-heading">
			<?php 
				// 1. Pass the FULL URL to attachment_url_to_postid()
				$hero_image_relative_path = '/wp-content/uploads/2026/08/HeroImage.webp';
				$hero_image_full_url      = site_url( $hero_image_relative_path );

				$image_id = attachment_url_to_postid( $hero_image_full_url );

				if ( $image_id ) {
					// Media Library match found: Outputs optimized <img> with full srcset & sizes
					echo wp_get_attachment_image( $image_id, 'govetty-hero', false, array(
						'class'         => 'img',
						'fetchpriority' => 'high',
						'decoding'      => 'async',
						'alt'           => 'Pet owner holding a dog on a video consultation inspired hero image',
						'sizes' 		=> '(max-width: 768px) 100vw, 480px',
					) );
				} else {
					// Fallback: Uses site_url() to ensure valid full URL path
					?>
					<img
						class="img"
						src="<?php echo esc_url( $hero_image_full_url ); ?>"
						width="480"
						height="300"
						fetchpriority="high"
						loading="eager"
						decoding="async"
						alt="Pet owner holding a dog on a video consultation inspired hero image"
					/>
					<?php
				}
			?>
            <div class="frame-5">
                <div class="frame-6">
                <div class="frame-7">
                    <h1 class="veterinary-care" id="hero-heading">Veterinary care, whenever you <br />need it.</h1>
                    <p class="p">Connect with licensed Canadian veterinarians 24/7 from the comfort of your home.</p>
                </div>
                <div class="frame-8">
                    <a class="frame-9" href="#" aria-label="Talk to a vet now">
                    <p class="text-wrapper-3">Talk to a Vet Now</p>
                    <img class="img-2" src="/wp-content/uploads/2026/07/ArrowRightWhite.svg" alt="" aria-hidden="true" />
                    </a>
                    <a class="frame-10" href="#how-it-works" aria-label="Learn how it works">
                    <div class="text-wrapper-4">How It Works</div>
                    <img class="img-2" src="/wp-content/uploads/2026/07/PlayCircle.webp" alt="" aria-hidden="true" />
                    </a>
                </div>
                </div>
                <div class="frame-11">
                <img class="fi-2" src="/wp-content/uploads/2026/07/MapleLeaf.webp" alt="" aria-hidden="true" />
                <p class="text-wrapper-5">Proudly Canadian. Always here for you and your pet.</p>
                </div>
            </div>
        </section>

        <section class="frame" aria-label="Key service highlights">
            <div class="div">
                <div class="frame-2">
                    <img class="fi" src="/wp-content/uploads/2026/07/Clock.webp" alt="" aria-hidden="true" />
                    <div class="frame-3">
                        <h2 class="text-wrapper">24/7 Available</h2>
                        <p class="day-or-night-we-re">Day or night, we're here.</p>
                    </div>
                </div>
                <img class="line" src="/wp-content/uploads/2026/07/Line.svg" alt="" aria-hidden="true" />
                <div class="frame-2">
                    <img class="fi" src="/wp-content/uploads/2026/07/ShieldCross.webp" alt="" aria-hidden="true" />
                    <div class="frame-4">
                        <h2 class="text-wrapper">Licensed Vets</h2>
                        <p class="text-wrapper-2">All veterinarians<br />are licensed in Canada.</p>
                    </div>
                </div>
                <img class="line" src="/wp-content/uploads/2026/07/Line.svg" alt="" aria-hidden="true" />
                <div class="frame-2">
                    <img class="fi" src="/wp-content/uploads/2026/07/House.webp" alt="" aria-hidden="true" />
                    <div class="frame-4">
                        <h2 class="text-wrapper">From Home</h2>
                        <p class="text-wrapper-2">No travel. No waiting room. Just expert care.</p>
                    </div>
                </div>
                <img class="line" src="/wp-content/uploads/2026/07/Line.svg" alt="" aria-hidden="true" />
                <div class="frame-2">
                    <img class="fi" src="/wp-content/uploads/2026/07/Lock.webp" alt="" aria-hidden="true" />
                    <div class="frame-4">
                        <h2 class="text-wrapper">Secure &amp; Private</h2>
                        <p class="text-wrapper-2">Your pet's health informa-tion is always protected.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="frame-12" id="how-it-works" aria-labelledby="how-it-works-heading">
            <div class="frame-13">
                <p class="text-wrapper-6">HOW IT WORKS</p>
                <h2 class="text-wrapper-7" id="how-it-works-heading">Expert care in 4 simple steps</h2>
            </div>
            <div class="frame-14">
                <article class="frame-15">
                    <img class="frame-16" src="/wp-content/uploads/2026/07/CopyPaperCircle.webp" alt="Illustration for pet's profile creation" loading="lazy" />
                    <div class="frame-17">
                        <h3 class="text-wrapper-8">Create your pet's profile</h3>
                        <p class="text-wrapper-9">Tell us what's going on <br>with your pet.</p>
                    </div>
                </article>
                <article class="frame-18">
                    <img class="frame-19" src="/wp-content/uploads/2026/08/CalendarCircle.webp" alt="Illustration for video consult schedule" loading="lazy" />
                    <div class="frame-17">
                        <h3 class="text-wrapper-8">Schedule a video consult</h3>
                        <p class="text-wrapper-10">Choose the time that works <br>for you.</p>
                    </div>
                </article>
                <article class="frame-15">
                    <img class="frame-16" src="/wp-content/uploads/2026/08/CameraCircle.webp" alt="Illustration for licensed vet consultation" loading="lazy" />
                    <div class="frame-17">
                        <h3 class="text-wrapper-8">Talk to a licensed vet</h3>
                        <p class="text-wrapper-9">Talk to a licensed veterinarian via secure video.</p>
                    </div>
                </article>
                <article class="frame-18">
                    <img class="frame-16" src="/wp-content/uploads/2026/08/CheckListCircle.webp" alt="Illustration for getting a care plan" loading="lazy" />
                    <div class="frame-17">
                        <h3 class="text-wrapper-8">Get a care summary &amp; routing</h3>
                        <p class="text-wrapper-9">Receive personalized guidance, recommendations, and next steps.</p>
                    </div>
                </article>
                <img class="object-20" src="/wp-content/uploads/2026/07/Vector_6_2.webp" alt="" aria-hidden="true" />
                <img class="object-21" src="/wp-content/uploads/2026/07/Vector_6_2.webp" alt="" aria-hidden="true" />
                <img class="object-22" src="/wp-content/uploads/2026/07/Vector_6_2.webp" alt="" aria-hidden="true" />
            </div>
        </section>

        <section class="frame-22" aria-labelledby="why-choose-heading">
            <div class="frame-23">
                <div class="frame-6">
                    <div class="frame-24">
                        <p class="text-wrapper-11">WHY CHOOSE CANADIAN PET PROTECT?</p>
                        <h2 class="text-wrapper-12" id="why-choose-heading">Trusted care. Anytime. Anywhere.</h2>
                    </div>
                    <p class="text-wrapper-13">We make it easy to get professional veterin advice when you need it most.</p>
                </div>
                <div class="frame-25" role="list" aria-label="Benefits">
                    <div class="frame-26" role="listitem">
                        <img class="fi-3" src="/wp-content/uploads/2026/07/CheckRed.svg" alt="" aria-hidden="true" />
                        <p class="text-wrapper-14">Real veterinarians, not call centers</p>
                    </div>
                    <div class="frame-26" role="listitem">
                        <img class="fi-3" src="/wp-content/uploads/2026/07/CheckRed.svg" alt="" aria-hidden="true" />
                        <div class="text-wrapper-14">Face-to-face video consultations</div>
                    </div>
                    <div class="frame-26" role="listitem">
                        <img class="fi-3" src="/wp-content/uploads/2026/07/CheckRed.svg" alt="" aria-hidden="true" />
                        <p class="text-wrapper-14">Personalized care for every pet</p>
                    </div>
                    <div class="frame-26" role="listitem">
                        <img class="fi-3" src="/wp-content/uploads/2026/07/CheckRed.svg" alt="" aria-hidden="true" />
                        <p class="text-wrapper-14">Detailed visit summaries &amp; records</p>
                    </div>
                    <div class="frame-26" role="listitem">
                        <img class="fi-3" src="/wp-content/uploads/2026/07/CheckRed.svg" alt="" aria-hidden="true" />
                        <div class="text-wrapper-14">Seamless follow-up support</div>
                    </div>
                </div>
            </div>

            <div class="frame-22-media">
                <img class="ellipse" src="/wp-content/uploads/2026/07/Ellipse_3.webp" alt="" aria-hidden="true" />
                <img class="ellipse-2" src="/wp-content/uploads/2026/07/Ellipse_4.webp" alt="" aria-hidden="true" />
                <?php 
					// 1. Pass the FULL URL to attachment_url_to_postid()
					$blurb_image_relative_path = '/wp-content/uploads/2026/07/Rectangle_12.webp';
					$blurb_image_full_url      = site_url( $blurb_image_relative_path );

					$bimage_id = attachment_url_to_postid( $blurb_image_full_url );

					if ( $bimage_id ) {
						// Media Library match found: Outputs optimized <img> with full srcset & sizes
						echo wp_get_attachment_image( $bimage_id, 'govetty-blurb', false, array(
							'class'         => 'rectangle',
							'decoding'      => 'async',
							'loading' 		=> 'lazy',
							'alt'           => 'Woman interacting with a dog during a pet care moment',
							'sizes' 		=> '(max-width:768px) 100vw,372px',
						) );
					} else {
						// Fallback: Uses site_url() to ensure valid full URL path
						?>
						<img
							class="rectangle"
							src="<?php echo esc_url( $blurb_image_full_url ); ?>"
							width="372"
							height="273"
							loading="lazy"
							decoding="async"
							alt="Woman interacting with a dog during a pet care moment"
						/>
						<?php
					}
				?>
                <img class="frame-27" src="/wp-content/uploads/2026/07/Section3OverlapBox.webp" alt="Vetty consultation card overlay" loading="lazy" />
            </div>
        </section>

        <section class="frame-28" id="our-veterinarians" aria-labelledby="veterinarians-heading">
            <div class="frame-29">
                <p class="text-wrapper-11">MEET OUR VETERINARIANS</p>
                <h2 class="text-wrapper-7" id="veterinarians-heading">Experienced. Compassionate. Canadian.</h2>
            </div>
            <div class="frame-30">
                <button class="frame-31" type="button" aria-label="Previous veterinarians">
                    <img class="frame-31" src="/wp-content/uploads/2026/07/Vector_13.svg" alt="" aria-hidden="true" />
                </button>
                <div class="frame-32">
                    <article class="frame-33">
                        <div class="img-wrapper">
                            <img class="professional-doctor" src="/wp-content/uploads/2026/07/professional-doctor-nurse-surgeon.webp" alt="Portrait of Dr. Sarah Mitchell" loading="lazy" />
                        </div>
                        <div class="frame-34">
                            <h3 class="text-wrapper-15">Dr. Sarah Mitchell</h3>
                            <div class="frame-24">
                            <div class="text-wrapper-16">DVM</div>
                            <div class="text-wrapper-17">10+ years experience</div>
                            </div>
                        </div>
                    </article>
                    <article class="frame-33">
                        <div class="frame-35">
                            <img class="portrait-smiling" src="/wp-content/uploads/2026/07/portrait-smiling-handsome-male-doctor-man.webp" alt="Portrait of Dr. James Carter" loading="lazy" />
                        </div>
                        <div class="frame-34">
                            <h3 class="text-wrapper-15">Dr. James Carter</h3>
                            <div class="frame-24">
                            <div class="text-wrapper-16">DVM</div>
                            <div class="text-wrapper-17">15+ years experience</div>
                            </div>
                        </div>
                    </article>
                    <article class="frame-33">
                        <div class="img-wrapper">
                            <img class="portrait-beautiful" src="/wp-content/uploads/2026/07/portrait-beautiful-smiling-woman-doctor-isolated-background.webp" alt="Portrait of Dr. Priya Sharma" loading="lazy" />
                        </div>
                        <div class="frame-34">
                            <h3 class="text-wrapper-15">Dr. Priya Sharma</h3>
                            <div class="frame-24">
                            <div class="text-wrapper-16">DVM</div>
                            <div class="text-wrapper-17">8+ years experience</div>
                            </div>
                        </div>
                    </article>
                    <article class="frame-33">
                        <div class="frame-35">
                            <img class="doctors-day-handsome" src="/wp-content/uploads/2026/07/doctors-day-handsome-brunette-cute-guy-medical-gown-with-crossed-hands.webp" alt="Portrait of Dr. Michael Lee" loading="lazy" />
                        </div>
                        <div class="frame-34">
                            <h3 class="text-wrapper-15">Dr. Michael Lee</h3>
                            <div class="frame-24">
                            <div class="text-wrapper-16">DVM</div>
                            <div class="text-wrapper-17">12+ years experience</div>
                            </div>
                        </div>
                    </article>
                    <article class="frame-33">
                        <div class="img-wrapper">
                            <img class="professional-doctor" src="/wp-content/uploads/2026/07/professional-doctor-nurse-surgeon.webp" alt="Portrait of Dr. Sarah Mitchell" loading="lazy" />
                        </div>
                        <div class="frame-34">
                            <h3 class="text-wrapper-15">Dr. Sarah Mitchell</h3>
                            <div class="frame-24">
                            <div class="text-wrapper-16">DVM</div>
                            <div class="text-wrapper-17">10+ years experience</div>
                            </div>
                        </div>
                    </article>
                    <article class="frame-33">
                        <div class="frame-35">
                            <img class="portrait-smiling" src="/wp-content/uploads/2026/07/portrait-smiling-handsome-male-doctor-man.webp" alt="Portrait of Dr. James Carter" loading="lazy" />
                        </div>
                        <div class="frame-34">
                            <h3 class="text-wrapper-15">Dr. James Carter</h3>
                            <div class="frame-24">
                            <div class="text-wrapper-16">DVM</div>
                            <div class="text-wrapper-17">15+ years experience</div>
                            </div>
                        </div>
                    </article>
                </div>
                <aside class="frame-wrapper" aria-label="Veterinarian credentials highlight">
                    <div class="frame-36">
                        <div class="frame-7">
                            <img class="fi-4" src="/wp-content/uploads/2026/07/Vector_8.svg" alt="" aria-hidden="true" />
                            <p class="all-our">All our veterinarians are licensed in Canada<br />and passionate about<br />animal health.</p>
                        </div>
                        <a class="div-wrapper" href="#" aria-label="View all veterinarians">
                            <div class="text-wrapper-18">View all Vets</div>
                        </a>
                    </div>
                </aside>
                <button class="frame-31" type="button" aria-label="Next veterinarians">
                    <img class="frame-31" src="/wp-content/uploads/2026/07/Vector_3.svg" alt="" aria-hidden="true" />
                </button>               
            </div>
        </section>

        <section class="frame-42" aria-labelledby="testimonials-heading">
            <img class="vector" src="/wp-content/uploads/2026/07/Vector_7.svg" alt="" aria-hidden="true" />
            <div class="frame-43">
                <p class="text-wrapper-6">WHAT PET OWNERS ARE SAYING</p>
                <div class="frame-30">
                    <article class="frame-44">
                        <div class="frame-45">
                            <div class="group-2" aria-label="5 out of 5 stars">
                                <img class="star" src="/wp-content/uploads/2026/07/Star_1.svg" alt="" aria-hidden="true" />
                                <img class="star-2" src="/wp-content/uploads/2026/07/Star_1.svg" alt="" aria-hidden="true" />
                                <img class="star-3" src="/wp-content/uploads/2026/07/Star_1.svg" alt="" aria-hidden="true" />
                                <img class="star-4" src="/wp-content/uploads/2026/07/Star_1.svg" alt="" aria-hidden="true" />
                                <img class="star-5" src="/wp-content/uploads/2026/07/Star_1.svg" alt="" aria-hidden="true" />
                            </div>
                            <blockquote class="text-wrapper-22">“The vet was amazing. So thorough and kind. <br />I got exactly what I needed at 2AM when everything else was closed.”</blockquote>
                            <div class="frame-46">
                                <img class="ellipse-3" src="https://c.animaapp.com/9gvlQcgi/img/ellipse-5@2x.png" alt="Emily R. and Milo" loading="lazy" />
                                <div class="frame-47">
                                <div class="text-wrapper-23">Emily R. &amp; Milo</div>
                                <div class="text-wrapper-2">Toronto, ON</div>
                                </div>
                            </div>
                        </div>
                    </article>
                    <article class="frame-44">
                        <div class="frame-45">
                            <div class="group-2" aria-label="5 out of 5 stars">
                                <img class="star" src="/wp-content/uploads/2026/07/Star_1.svg" alt="" aria-hidden="true" />
                                <img class="star-2" src="/wp-content/uploads/2026/07/Star_1.svg" alt="" aria-hidden="true" />
                                <img class="star-3" src="/wp-content/uploads/2026/07/Star_1.svg" alt="" aria-hidden="true" />
                                <img class="star-4" src="/wp-content/uploads/2026/07/Star_1.svg" alt="" aria-hidden="true" />
                                <img class="star-5" src="/wp-content/uploads/2026/07/Star_1.svg" alt="" aria-hidden="true" />
                            </div>
                            <blockquote class="text-wrapper-22">"It was so easy and convenient. I love that I can talk to a real vet from home."</blockquote>
                            <div class="frame-46">
                                <img class="ellipse-3" src="https://c.animaapp.com/9gvlQcgi/img/ellipse-5-1@2x.png" alt="Jason T. and Luna" loading="lazy" />
                                <div class="frame-47">
                                <div class="text-wrapper-23">Jason T. &amp; Luna</div>
                                <div class="text-wrapper-2">Calgary, AB</div>
                                </div>
                            </div>
                        </div>
                    </article>
                    <article class="frame-44">
                        <div class="frame-45">
                            <div class="group-2" aria-label="5 out of 5 stars">
                                <img class="star" src="/wp-content/uploads/2026/07/Star_1.svg" alt="" aria-hidden="true" />
                                <img class="star-2" src="/wp-content/uploads/2026/07/Star_1.svg" alt="" aria-hidden="true" />
                                <img class="star-3" src="/wp-content/uploads/2026/07/Star_1.svg" alt="" aria-hidden="true" />
                                <img class="star-4" src="/wp-content/uploads/2026/07/Star_1.svg" alt="" aria-hidden="true" />
                                <img class="star-5" src="/wp-content/uploads/2026/07/Star_1.svg" alt="" aria-hidden="true" />
                            </div>
                            <blockquote class="text-wrapper-22">"We got clear answers and a follow-up plan. Highly recommend Canadian Pet Protect."</blockquote>
                            <div class="frame-46">
                                <img class="ellipse-3" src="https://c.animaapp.com/9gvlQcgi/img/ellipse-5-2@2x.png" alt="Sophie L. and Charlie" loading="lazy" />
                                <div class="frame-47">
                                <div class="text-wrapper-23">Sophie L. &amp; Charlie</div>
                                <div class="text-wrapper-2">Vancouver, BC</div>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
                <div class="frame-8" aria-label="Testimonial pagination">
                    <div class="ellipse-4" aria-current="true"></div>
                    <div class="ellipse-5"></div>
                    <div class="ellipse-5"></div>
                    <div class="ellipse-5"></div>
                    <div class="ellipse-5"></div>
                </div>
            </div>
        </section>

        <section class="frame-37" id="faq" aria-labelledby="faq-heading">
            <div class="frame-29">
                <p class="text-wrapper-11">FREQUENTLY ASKED QUESTIONS</p>
                <h2 class="text-wrapper-7" id="faq-heading">Have questions? We've got answers.</h2>
            </div>
            <div class="frame-38">
                <div class="frame-30">
                    <details class="frame-39">
                        <summary class="frame-40">
                            <p class="text-wrapper-19">Is this a replacement for<br />an in-person visit?</p>
                            <img class="img-2" src="/wp-content/uploads/2026/07/CaretDownRed.svg" alt="" aria-hidden="true" />
                        </summary>
                    </details>
                    <details class="frame-39">
                        <summary class="frame-40">
                            <p class="text-wrapper-19">Are your veterinarians licensed in Canada?</p>
                            <img class="img-2" src="/wp-content/uploads/2026/07/CaretDownRed.svg" alt="" aria-hidden="true" />
                        </summary>
                    </details>
                    <details class="frame-39">
                        <summary class="frame-40">
                            <div class="text-wrapper-19">What happens after<br />the consultation?</div>
                            <img class="img-2" src="/wp-content/uploads/2026/07/CaretDownRed.svg" alt="" aria-hidden="true" />
                        </summary>
                    </details>
                    <details class="frame-39">
                        <summary class="frame-41">
                            <p class="how-much-does-a"><span class="span">How much does a consultation cost?</span><span class="text-wrapper-20">(Taxes?)</span></p>
                            <img class="img-2" src="/wp-content/uploads/2026/07/CaretDownRed.svg" alt="" aria-hidden="true" />
                        </summary>
                    </details>
                </div>
                <p class="still-have-questions"><span class="span">Still have questions? Visit our </span><a class="text-wrapper-21" href="#"><span class="text-wrapper-21">FAQ page</span></a></p>
            </div>
        </section>

        <section class="frame-48" id="pricing" aria-labelledby="cta-heading">
            <div class="frame-49">
                <div class="frame-50">
                    <img class="frame-51" src="/wp-content/uploads/2026/07/WhiteHeartCircle.webp" alt="" aria-hidden="true" />
                    <div class="frame-52">
                        <h2 class="we-re-here-for-you" id="cta-heading">We're here for you and your pet.</h2>
                        <p class="text-wrapper-24">Talk to a licensed Canadian veterinaian 24/7.</p>
                    </div>
                </div>
                <a class="frame-53" href="#" aria-label="Talk to a vet now">
                    <p class="text-wrapper-25">Talk to a Vet Now</p>
                    <img class="img-2" src="/wp-content/uploads/2026/07/ArrowRighRed.svg" alt="" aria-hidden="true" />
                </a>
            </div>
        </section>
    </main>
</div>

<script defer>
  document.addEventListener("DOMContentLoaded",()=>{const s=document.querySelector(".frame-32"),b=document.querySelectorAll("button.frame-31");if(!s)return;let d=0,x,l;s.onmousedown=e=>(d=1,x=e.pageX-s.offsetLeft,l=s.scrollLeft),s.onmouseleave=s.onmouseup=()=>d=0,s.onmousemove=e=>d&&(e.preventDefault(),s.scrollLeft=l-(e.pageX-s.offsetLeft-x)*1.5);const g=()=>{let c=s.querySelector(".frame-33");return c?c.offsetWidth+20:250};b[1]&&(b[1].onclick=()=>s.scrollBy({left:g(),behavior:"smooth"}));b[0]&&(b[0].onclick=()=>s.scrollBy({left:-g(),behavior:"smooth"}));
});
</script>

<?php get_footer(); ?>
