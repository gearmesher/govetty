<?php
/**
 * Go Vetty functions and definitions
 */

// Prevent direct access to the file
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'go_vetty_setup' ) ) :
    function go_vetty_setup() {
        // Core Gutenberg & Layout support
        add_theme_support( 'wp-block-styles' );
        add_theme_support( 'align-wide' );
        add_theme_support( 'title-tag' );
        add_theme_support( 'post-thumbnails' );
        add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
        
        // Elementor full-width & template container compatibility tag
        add_theme_support( 'elementor-theme-compatibility' );

        // Register the dynamic primary navigation menu location for header.php
        register_nav_menus(
            array(
                'menu-1' => esc_html__( 'Primary Header Navigation Menu', 'go-vetty' ),
            )
        );
    }
endif;
add_action( 'after_setup_theme', 'go_vetty_setup' );

/**
 * Super-Fast Asset Optimization
 * Removing block-library margins and emojis to keep scripts minimal.
 */
function go_vetty_purged_assets() {
    wp_enqueue_style(
		'six13-style',
		get_stylesheet_uri(),
		[],
		filemtime(get_stylesheet_directory() . '/style.css')
	);

	wp_enqueue_style(
		'six13-header',
		get_template_directory_uri() . '/header.css',
		['six13-style'],
		filemtime(get_stylesheet_directory() . '/header.css')
	);

	wp_enqueue_style(
		'six13-footer',
		get_template_directory_uri() . '/footer.css',
		['six13-style'],
		filemtime(get_stylesheet_directory() . '/footer.css')
	);
    
    // Remove Gutenberg global inline styles if you want true performance control
    wp_dequeue_style( 'global-styles' ); 
    
    // SAFE SPEED WIN: Only remove jQuery if the user is NOT logged in AND NOT using the Customizer
    if ( ! is_admin() && ! is_user_logged_in() && ! is_customize_preview() ) {
        wp_deregister_script( 'jquery' );
    }
}
add_action( 'wp_enqueue_scripts', 'go_vetty_purged_assets', 100 );

// Strip Emoji tracking scripts
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );

/**
 * Customizer Control for Coming Soon Mode
 */
function go_vetty_customizer_settings( $wp_customize ) {
    $wp_customize->add_section( 'go_vetty_coming_soon_section', array(
        'title'    => __( 'Coming Soon Settings', 'go-vetty' ),
        'priority' => 30,
    ) );

    $wp_customize->add_setting( 'go_vetty_enable_coming_soon', array(
        'default'   => false,
        'transport' => 'refresh',
    ) );

    $wp_customize->add_control( 'go_vetty_enable_coming_soon_control', array(
        'label'    => __( 'Enable Coming Soon Mode', 'go-vetty' ),
        'section'  => 'go_vetty_coming_soon_section',
        'settings' => 'go_vetty_enable_coming_soon',
        'type'     => 'checkbox',
    ) );
}
add_action( 'customize_register', 'go_vetty_customizer_settings' );

/**
 * Intercept Template Redirection for Coming Soon View
 * Shows the coming soon page ONLY to logged-out users when enabled.
 */
function go_vetty_intercept_coming_soon() {
    if ( get_theme_mod( 'go_vetty_enable_coming_soon', false ) && ! is_user_logged_in() ) {
        status_header( 503 ); // Tell search engines it's a temporary maintenance window
        
        // Double-check file exists before including to prevent fatal errors
        $template_path = get_template_directory() . '/coming-soon.php';
        if ( file_exists( $template_path ) ) {
            include( $template_path );
            exit;
        }
    }
}
add_action( 'template_redirect', 'go_vetty_intercept_coming_soon' );

/**
 * Enable SVG Upload Support with Sanitization
 */
function govetty_allow_svg_uploads( $mimes ) {
    // Add SVG to the list of allowed MIME types
    $mimes['svg']  = 'image/svg+xml';
    $mimes['svgz'] = 'image/svg+xml';
    return $mimes;
}
add_filter( 'upload_mimes', 'govetty_allow_svg_uploads' );

/**
 * Fix SVG display in the WordPress Media Library grid view
 */
function govetty_fix_svg_media_library_display() {
    echo '<style>
        .attachment-266x266, .thumbnail img[src$=".svg"] { 
            width: 100% !important; 
            height: auto !important; 
        }
    </style>';
}
add_action( 'admin_head', 'govetty_fix_svg_media_library_display' );

/**
 * Optional Safety Filter: Basic SVG XML Sanitization on Upload
 */
function govetty_sanitize_svg_upload( $file ) {
    if ( $file['type'] === 'image/svg+xml' && file_exists( $file['tmp_name'] ) ) {
        $svg_content = file_get_contents( $file['tmp_name'] );
        
        // Simple check to reject common script injections
        if ( stripos( $svg_content, '<script' ) !== false || stripos( $svg_content, 'javascript:' ) !== false ) {
            $file['error'] = __( 'Security Check: This SVG file contains scripts or unsafe code and was blocked.', 'go-vetty' );
        }
    }
    return $file;
}
add_filter( 'wp_handle_upload_prefilter', 'govetty_sanitize_svg_upload' );

/**
 * Filter dynamic navigation link outputs to retain your structural layout design
 * Automatically hooks into link attributes and maps the original .text-wrapper-31 styling
 */
function go_vetty_nav_menu_link_attributes( $atts, $item, $args, $depth ) {
    if ( isset( $args->theme_location ) && 'menu-1' === $args->theme_location ) {
        $atts['class'] = 'text-wrapper-31';
    }
    return $atts;
}
add_filter( 'nav_menu_link_attributes', 'go_vetty_nav_menu_link_attributes', 10, 4 );

/**
 * Clean fallback menu loop if a menu hasn't been set yet in the WP admin panel
 */
function go_vetty_menu_fallback() {
    echo '<a class="text-wrapper-31" href="#how-it-works">How It Works</a>';
    echo '<a class="text-wrapper-31" href="#our-veterinarians">Our Veterinarians</a>';
    echo '<a class="text-wrapper-31" href="#pricing">Pricing</a>';
    echo '<a class="text-wrapper-31" href="#faq">FAQ</a>';
    echo '<a class="text-wrapper-31" href="#about-us">About Us</a>';
}

/**
 * Register Footer Navigation Locations
 */
function go_vetty_register_footer_menus() {
    register_nav_menus(
        array(
            'footer-company' => esc_html__( 'Footer: Company Column', 'go-vetty' ),
            'footer-support' => esc_html__( 'Footer: Support Column', 'go-vetty' ),
            'footer-legal'   => esc_html__( 'Footer: Legal Column', 'go-vetty' ),
        )
    );
}
add_action( 'init', 'go_vetty_register_footer_menus' );

/**
 * Filter Footer Nav Links to inject Anima layout classes dynamically
 */
function go_vetty_footer_link_classes( $atts, $item, $args, $depth ) {
    $footer_locations = array( 'footer-company', 'footer-support', 'footer-legal' );
    
    if ( isset( $args->theme_location ) && in_array( $args->theme_location, $footer_locations ) ) {
        // First item gets the primary class wrapper, subsequent ones get the secondary layout variation
        $atts['class'] = ( $item->menu_order === 1 ) ? 'text-wrapper-28' : 'text-wrapper-29';
    }
    return $atts;
}
add_filter( 'nav_menu_link_attributes', 'go_vetty_footer_link_classes', 10, 4 );

/**
 * Fallback Loops if custom menus aren't designated in dashboard options yet
 */
function go_vetty_footer_company_fallback() {
    echo '<a class="text-wrapper-28" href="/about-us">About Us</a>';
    echo '<a class="text-wrapper-29" href="/our-vets">Our Veterinarians</a>';
    echo '<a class="text-wrapper-29" href="#">Careers</a>';
    echo '<a class="text-wrapper-29" href="#">Contact Us</a>';
}

function go_vetty_footer_support_fallback() {
    echo '<a class="text-wrapper-28" href="/faq">FAQ</a>';
    echo '<a class="text-wrapper-29" href="/how-it-works">How It Works</a>';
    echo '<a class="text-wrapper-29" href="/prices">Pricing</a>';
    echo '<a class="text-wrapper-29" href="#">Privacy Policy</a>';
}

function go_vetty_footer_legal_fallback() {
    echo '<a class="text-wrapper-28" href="#">Terms of Service</a>';
    echo '<a class="text-wrapper-29" href="#">Privacy Policy</a>';
    echo '<a class="text-wrapper-29" href="#">Data Security</a>';
    echo '<a class="text-wrapper-29" href="#">Cookies Policy</a>';
}
