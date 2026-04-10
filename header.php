<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
    <?php get_template_part('template-parts/dynamic-css/global-inline-css'); ?>
</head>

<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>

    <header class="site-header absolute top-0 left-0 w-full z-50">
        <!-- Desktop Header -->
        <div class="w-[90vw] mx-auto px-4 py-6 hidden md:block">
            <?php get_template_part('template-parts/header/header', 'desktop'); ?>
        </div>
    </header>

    <?php if (is_front_page()) : ?>
        <?php get_template_part('template-parts/sections/hero-section'); ?>
    <?php endif; ?>