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
        .coming-soon-wrapper {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            text-align: center;
            padding: 20px;
            background: #f9f9f9;
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
            color: #555;
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
    </style>
</head>
<body>
    <div class="coming-soon-wrapper">
        <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/site-logo.png' ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> Logo" class="coming-soon-logo">
        <p>Our new website is currently under construction. We will be launching soon!</p>
    </div>
    <?php wp_footer(); ?>
</body>
</html>