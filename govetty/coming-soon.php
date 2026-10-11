<?php
/**
 * The template for displaying the minimal Coming Soon layout with Logo.
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php bloginfo( 'name' ); ?> | Coming Soon</title>
    <link rel="stylesheet" href="<?php echo get_stylesheet_uri(); ?>" type="text/css" media="all" />
    <?php wp_head(); ?>
    <style>
        /* Inline styles specifically for the logo layout to avoid cache layout delays */
        @font-face {
            font-family: "Archivo";
            font-style: normal;
            font-display: swap;
            font-weight: 100 900;
            src: url("<?php echo esc_url( get_template_directory_uri() . '/assets/fonts/archivo-latin-wght.woff2' ); ?>") format("woff2");
        }
        .coming-soon-wrapper {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            text-align: center;
            padding: 20px;
            background: #fff8f0; /* cream-50 */
            box-sizing: border-box;
        }
        .coming-soon-logo {
            max-width: 240px; /* Adjust this value depending on your logo's natural dimensions */
            height: auto;
            margin-bottom: 24px;
            display: block;
        }
        .coming-soon-wrapper p {
            font-size: 1.1rem;
            color: #525353; /* ink-500 */
            margin: 0;
            font-family: "Archivo", "Helvetica Neue", Arial, sans-serif;
            font-weight: 300;
        }
    </style>
</head>
<body>
    <div class="coming-soon-wrapper">
        <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/brand/vetty-logo-maroon.svg' ); ?>" width="195" height="79" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> Logo" class="coming-soon-logo">
        <p>Our new website is currently under construction. We will be launching soon!</p>
    </div>
    <?php wp_footer(); ?>
</body>
</html>