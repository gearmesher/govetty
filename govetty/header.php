<?php 
/** * The Header for our theme. 
 * * Displays all of the <head> section and everything up till <main> 
 * * @package Go_Vetty 
 */ 

// Exit if accessed directly. 
if ( ! defined( 'ABSPATH' ) ) { 
    exit; 
} 
?><!DOCTYPE html> 
<html <?php language_attributes(); ?>> 
<head> 
    <meta charset="<?php bloginfo( 'charset' ); ?>"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <link rel="profile" href="https://gmpg.org/xfn/11"> 
    <?php wp_head(); ?> 
    <?php if ( is_front_page() ) : ?> 
        <link rel="stylesheet" href="<?php echo esc_url( get_template_directory_uri() . '/home.css' ); ?>"> 
    <?php endif; ?>
    <?php if ( is_page( 'about-us' ) ) : ?> 
        <link rel="stylesheet" href="<?php echo esc_url( get_template_directory_uri() . '/about.css' ); ?>"> 
    <?php endif; ?>   
    <?php if ( is_page( 'prices' ) ) : ?> 
        <link rel="stylesheet" href="<?php echo esc_url( get_template_directory_uri() . '/prices.css' ); ?>"> 
    <?php endif; ?> 
    <?php if ( is_page( 'how-it-works' ) ) : ?> 
        <link rel="stylesheet" href="<?php echo esc_url( get_template_directory_uri() . '/how-it-works.css' ); ?>"> 
    <?php endif; ?>  
    <?php if ( is_page( 'our-vets' ) ) : ?> 
        <link rel="stylesheet" href="<?php echo esc_url( get_template_directory_uri() . '/our-vets.css' ); ?>"> 
    <?php endif; ?>  
    <?php if ( is_page( 'faq' ) ) : ?> 
        <link rel="stylesheet" href="<?php echo esc_url( get_template_directory_uri() . '/faq.css' ); ?>"> 
    <?php endif; ?> 
</head> 

<body <?php body_class(); ?>> 
<?php wp_body_open(); ?> 

<div id="page" class="site"> 
    <a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'Skip to content', 'go-vetty' ); ?></a> 

    <header class="frame-62"> 
        <div class="frame-63"> 
            
            <input type="checkbox" id="menu-toggle" class="menu-toggle"> 

            <a class="group-wrapper" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php bloginfo( 'name' ); ?> home"> 
                <div class="group-4"> 
                    <img class="group-5" src="https://c.animaapp.com/9gvlQcgi/img/group-3@2x.png" alt="<?php bloginfo( 'name' ); ?> logo icon" /> 
                    <img class="canda" src="https://c.animaapp.com/9gvlQcgi/img/canda2-2.png" alt="<?php bloginfo( 'name' ); ?> typography brand mark" /> 
                </div> 
            </a> 

            <div class="frame-64-parent"> 
                <div class="frame-64"> 
                    <div class="frame-8 mobile-actions"> 
                        <a class="frame-66" href="<?php echo esc_url( wp_login_url() ); ?>" aria-label="<?php esc_attr_e( 'Log in', 'go-vetty' ); ?>"> 
                            <span class="btn-text text-wrapper-25"><?php esc_html_e( 'Log in', 'go-vetty' ); ?></span> 
                            <svg class="mobile-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"> 
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 4c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm0 14c-2.03 0-4.43-.82-6.14-2.88C7.55 15.8 10 15 12 15s4.45.8 6.14 2.12C16.43 19.18 14.03 20 12 20z"/> 
                            </svg> 
                        </a> 
                         
                        <a class="frame-67" href="#" aria-label="<?php esc_attr_e( 'Talk to a vet now', 'go-vetty' ); ?>"> 
                            <span class="btn-text text-wrapper-18"><?php esc_html_e( 'Talk to a Vet Now', 'go-vetty' ); ?></span> 
                            <svg class="mobile-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"> 
                                <path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 9h-3v3h-2v-3H10V9h3V6h2v3h3v2z"/> 
                            </svg> 
                        </a> 
                    </div> 

                    <label for="menu-toggle" class="hamburger-label" aria-label="<?php esc_attr_e( 'Toggle navigation menu', 'go-vetty' ); ?>"> 
                        <svg class="hamburger-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#600209"> 
                            <path class="menu-bars" d="M3 4h18v2H3zm0 7h18v2H3zm0 7h18v2H3z"/> 
                            <path class="menu-close" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/> 
                        </svg> 
                    </label> 
                </div> 

                <nav class="frame-65" aria-label="<?php esc_attr_e( 'Primary navigation', 'go-vetty' ); ?>"> 
                    <?php 
                    wp_nav_menu( 
                        array( 
                            'theme_location' => 'menu-1', 
                            'menu_id'        => 'primary-menu', 
                            'container'      => false, 
                            'items_wrap'     => '%3$s', 
                            'fallback_cb'    => 'go_vetty_menu_fallback', 
                        ) 
                    ); 
                    ?> 
                </nav> 
            </div> 
        </div> 
    </header>
