<?php
/**
 * Go Vetty functions and definitions
 */

// Prevent direct access to the file
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Theme version — read from the "Version:" header in style.css, so bumping it
 * there is the single place to version a release.
 */
if ( ! defined( 'GO_VETTY_VERSION' ) ) {
    $go_vetty_theme = wp_get_theme( get_template() );
    define( 'GO_VETTY_VERSION', $go_vetty_theme->get( 'Version' ) ? $go_vetty_theme->get( 'Version' ) : '1.1.0' );
}

/**
 * Asset version string: "<theme version>.<file mtime>".
 * The theme version busts caches on every release even when a deploy preserves
 * file timestamps; the mtime part still catches hot-fixes made between releases.
 *
 * @param string $path Absolute path to the asset file.
 * @return string
 */
function go_vetty_asset_version( $path ) {
    return file_exists( $path ) ? GO_VETTY_VERSION . '.' . filemtime( $path ) : GO_VETTY_VERSION;
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
    $theme_dir = get_stylesheet_directory();
    $theme_uri = get_template_directory_uri();

    if ( is_front_page() || is_home() ) {
		// Front page: brand tokens, header, footer and home styles are all printed inline (no render-blocking requests).
		add_action( 'wp_head', 'go_vetty_print_inline_home_css', 5 );
	} else {  
        $style_css  = $theme_dir . '/style.css';
        $brand_css  = $theme_dir . '/brand.css';
        $header_css = $theme_dir . '/header.css';
        $footer_css = $theme_dir . '/footer.css';

        wp_enqueue_style( 
            'govetty-style', 
            get_stylesheet_uri(), 
            [], 
            go_vetty_asset_version( $style_css )
        ); 

        // Vetty brand tokens (colours, fonts) — loads after style.css so it wins over any legacy --x-* values.
        wp_enqueue_style(
            'govetty-brand',
            $theme_uri . '/brand.css',
            [ 'govetty-style' ],
            go_vetty_asset_version( $brand_css )
        );

        wp_enqueue_style( 
            'govetty-header', 
            $theme_uri . '/header.css', 
            [ 'govetty-brand' ], 
            go_vetty_asset_version( $header_css )
        ); 
         
        wp_enqueue_style( 
            'govetty-footer', 
            $theme_uri . '/footer.css', 
            [ 'govetty-brand' ], 
            go_vetty_asset_version( $footer_css )
        ); 

        // Page-specific stylesheets (previously hard-coded <link> tags in header.php).
        // Enqueued here so they load after the brand/header/footer CSS and get a
        // filemtime() version — browsers fetch the new file as soon as it changes.
        $page_styles = array(
            'about-us'     => 'about.css',
            'prices'       => 'prices.css',
            'how-it-works' => 'how-it-works.css',
            'our-vets'     => 'our-vets.css',
            'faq'          => 'faq.css',
        );

        foreach ( $page_styles as $slug => $file ) {
            if ( is_page( $slug ) ) {
                $path = $theme_dir . '/' . $file;
                wp_enqueue_style(
                    'govetty-page-' . $slug,
                    $theme_uri . '/' . $file,
                    [ 'govetty-header', 'govetty-footer' ],
                    go_vetty_asset_version( $path )
                );
            }
        }
    } 

    // Remove Gutenberg global inline styles
    wp_dequeue_style( 'global-styles' );  
     
    // Safe jQuery Removal
    if ( ! is_admin() && ! is_user_logged_in() && ! is_customize_preview() ) { 
        wp_deregister_script( 'jquery' ); 
    } 
} 
add_action( 'wp_enqueue_scripts', 'go_vetty_purged_assets', 100 );

/**
 * Front page: print brand + header + footer + home CSS inline (minified copies of the .css sources).
 * Relative font URLs in brand CSS ("assets/fonts/…") are rewritten to absolute theme URLs,
 * because inline CSS resolves relative URLs against the page, not the theme folder.
 */
function go_vetty_print_inline_home_css() {
	$theme_dir = get_stylesheet_directory();
	$theme_uri = trailingslashit( get_template_directory_uri() );
	$css       = '';

	// Order matters: tokens/fonts → header → footer → page.
	foreach ( array( 'brand.min.css', 'header.min.css', 'footer.min.css', 'home.min.css' ) as $file ) {
		$path = $theme_dir . '/' . $file;
		if ( file_exists( $path ) ) {
			$css .= file_get_contents( $path );
		}
	}

	if ( '' === $css ) {
		return;
	}

	$css = str_replace(
		array( 'url("assets/', "url('assets/", 'url(assets/' ),
		array( 'url("' . $theme_uri . 'assets/', "url('" . $theme_uri . 'assets/', 'url(' . $theme_uri . 'assets/' ),
		$css
	);

	echo '<style id="govetty-home-inline-css">' . $css . '</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- theme-owned CSS file.
}

/**
 * Preload the Archivo variable font (used for every weight: 300 body, 600 UI, 800 display).
 */
function go_vetty_resource_hints() {
	printf(
		'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
		esc_url( get_template_directory_uri() . '/assets/fonts/archivo-latin-wght.woff2' )
	);
}
add_action( 'wp_head', 'go_vetty_resource_hints', 1 );

/**
 * Output the Vetty wordmark from the theme's assets/brand folder.
 *
 * @param string $variant  maroon | black | white | white-on-maroon
 * @param string $class    CSS class for the <img>.
 * @param bool   $priority True for the above-the-fold header logo (fetchpriority=high, eager).
 */
function go_vetty_brand_logo( $variant = 'maroon', $class = '', $priority = false ) {
	$allowed = array( 'maroon', 'black', 'white', 'white-on-maroon' );
	if ( ! in_array( $variant, $allowed, true ) ) {
		$variant = 'maroon';
	}

	// Intrinsic size of the SVG artboards (keeps CLS at zero).
	$dims = ( 'white-on-maroon' === $variant ) ? array( 231, 115 ) : array( 195, 79 );

	printf(
		'<img class="%1$s" src="%2$s" width="%3$d" height="%4$d" alt="%5$s" decoding="async"%6$s />',
		esc_attr( $class ),
		esc_url( add_query_arg( 'ver', GO_VETTY_VERSION, get_template_directory_uri() . '/assets/brand/vetty-logo-' . $variant . '.svg' ) ),
		(int) $dims[0],
		(int) $dims[1],
		esc_attr__( 'Vetty', 'go-vetty' ),
		$priority ? ' fetchpriority="high" loading="eager"' : ' loading="lazy"'
	);
}

/**
 * Favicons — only used when no Site Icon is set in Appearance → Customize → Site Identity
 * (WordPress' own Site Icon always takes precedence).
 */
function go_vetty_favicons() {
	if ( has_site_icon() ) {
		return;
	}
	$base = get_template_directory_uri() . '/assets/brand/';
	printf( '<link rel="icon" href="%s" sizes="any">' . "\n", esc_url( add_query_arg( 'ver', GO_VETTY_VERSION, $base . 'vetty-favicon-red.ico' ) ) );
	printf( '<link rel="icon" href="%s" type="image/svg+xml">' . "\n", esc_url( add_query_arg( 'ver', GO_VETTY_VERSION, $base . 'vetty-favicon-red.svg' ) ) );
	printf( '<link rel="apple-touch-icon" href="%s">' . "\n", esc_url( add_query_arg( 'ver', GO_VETTY_VERSION, $base . 'vetty-favicon-red-180.png' ) ) );
	echo '<meta name="theme-color" content="#5e0710">' . "\n";
}
add_action( 'wp_head', 'go_vetty_favicons', 2 );

remove_action('wp_head', 'wp_oembed_add_discovery_links');
remove_action('wp_head', 'wp_oembed_add_host_js');
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'wp_shortlink_wp_head');
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('admin_print_scripts', 'print_emoji_detection_script');
remove_action('admin_print_styles', 'print_emoji_styles');
remove_filter('the_content_feed', 'wp_staticize_emoji');
remove_filter('comment_text_rss', 'wp_staticize_emoji');
remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
remove_action( 'wp_enqueue_scripts', 'wp_enqueue_classic_theme_styles' );

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

// Set booking path to "talk to a vet" link
add_filter( 'marpet_booking_page_path', function () {
    return '/talk-to-a-vet/'; // whatever slug you actually used
} );
