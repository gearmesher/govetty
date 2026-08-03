<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the #content div and all content after.
 *
 * @package Go_Vetty
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

    <footer class="frame-54">
        <div class="frame-55">
            <!-- Branding Area -->
            <div class="frame-56 footer-branding">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php bloginfo( 'name' ); ?> home">
                    <img class="group-3" src="https://c.animaapp.com/9gvlQcgi/img/group-2@2x.png" alt="<?php bloginfo( 'name' ); ?> logo" />
                </a>                
            </div>

            <div class="frame-56" aria-label="<?php esc_html_e( 'Trusted veterinary care. Anytime. Anywhere.', 'go-vetty' ); ?>">
                <div class="text-wrapper-28">
                    <p class="text-wrapper-26 footer-tagline"><?php esc_html_e( 'Trusted veterinary care.', 'go-vetty' ); ?></p>
                    <p class="text-wrapper-26 footer-tagline"><?php esc_html_e( 'Anytime. Anywhere.', 'go-vetty' ); ?></p>
                </div>
            </div>

            <!-- Dynamic Column 1: Company -->
            <nav class="frame-56" aria-label="<?php esc_attr_e( 'Company', 'go-vetty' ); ?>">
                <div class="text-wrapper-27"><?php esc_html_e( 'COMPANY', 'go-vetty' ); ?></div>
                <div class="frame-57">
                    <?php
                    wp_nav_menu(
                        array(
                            'theme_location' => 'footer-company',
                            'container'      => false,
                            'items_wrap'     => '%3$s',
                            'fallback_cb'    => 'go_vetty_footer_company_fallback',
                        )
                    );
                    ?>
                </div>
            </nav>

            <!-- Dynamic Column 2: Support -->
            <nav class="frame-56" aria-label="<?php esc_attr_e( 'Support', 'go-vetty' ); ?>">
                <div class="text-wrapper-27"><?php esc_html_e( 'SUPPORT', 'go-vetty' ); ?></div>
                <div class="frame-57">
                    <?php
                    wp_nav_menu(
                        array(
                            'theme_location' => 'footer-support',
                            'container'      => false,
                            'items_wrap'     => '%3$s',
                            'fallback_cb'    => 'go_vetty_footer_support_fallback',
                        )
                    );
                    ?>
                </div>
            </nav>

            <!-- Dynamic Column 3: Legal -->
            <nav class="frame-56" aria-label="<?php esc_attr_e( 'Legal', 'go-vetty' ); ?>">
                <div class="text-wrapper-27"><?php esc_html_e( 'LEGAL', 'go-vetty' ); ?></div>
                <div class="frame-57">
                    <?php
                    wp_nav_menu(
                        array(
                            'theme_location' => 'footer-legal',
                            'container'      => false,
                            'items_wrap'     => '%3$s',
                            'fallback_cb'    => 'go_vetty_footer_legal_fallback',
                        )
                    );
                    ?>
                </div>
            </nav>

            <!-- Socials & Localized Identity -->
            <div class="frame-58">
                <div class="text-wrapper-27"><?php esc_html_e( 'FOLLOW US', 'go-vetty' ); ?></div>
                <div class="frame-7">
                    <img class="frame-59" src="https://c.animaapp.com/9gvlQcgi/img/frame-1707481337.svg" alt="Social media icons" />
                    <div class="frame-60">
                        <img class="fi-5" src="https://c.animaapp.com/9gvlQcgi/img/fi-5699119-1.svg" alt="" aria-hidden="true" />
                        <div class="frame-61">
                            <div class="text-wrapper-30"><?php esc_html_e( 'Proudly Canadian.', 'go-vetty' ); ?></div>
                            <p class="text-wrapper-29"><?php esc_html_e( 'Service & support in Canada', 'go-vetty' ); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dynamic Copyright Tracker Engine -->
        <p class="copyright">&copy; <?php echo date( 'Y' ); ?> <?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'All rights reserved.', 'go-vetty' ); ?></p>
    </footer>

</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
