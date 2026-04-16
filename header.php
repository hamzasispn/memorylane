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

    <header class="site-header absolute top-0 left-0 w-full z-50" x-data="{ mobileOpen: false }">

        <!-- Desktop Header -->
        <div class="w-[90vw] mx-auto px-4 py-6 hidden md:block">
            <?php get_template_part('template-parts/header/header', 'desktop'); ?>
        </div>

        <!-- Mobile Header Bar -->
        <div class="md:hidden flex items-center justify-between px-[5vw] py-5">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="site-logo-mobile">
                <img src="<?= get_template_directory_uri() . '/assets/Logo.png' ?>" alt="Memory Lane" class="!w-28 h-auto">
            </a>

            <button
                @click="mobileOpen = !mobileOpen"
                class="relative w-10 h-10 flex flex-col justify-center items-center gap-[6px] focus:outline-none z-50"
                aria-label="Toggle menu"
                :aria-expanded="mobileOpen">
                <!-- Bar 1 -->
                <span
                    class="block w-7 h-[2px] bg-white rounded-full transition-all duration-300 origin-center"
                    :class="mobileOpen ? 'rotate-45 translate-y-[8px]' : ''">
                </span>
                <!-- Bar 2 -->
                <span
                    class="block w-7 h-[2px] bg-white rounded-full transition-all duration-300"
                    :class="mobileOpen ? 'opacity-0 scale-x-0' : ''">
                </span>
                <!-- Bar 3 -->
                <span
                    class="block w-7 h-[2px] bg-white rounded-full transition-all duration-300 origin-center"
                    :class="mobileOpen ? '-rotate-45 -translate-y-[8px]' : ''">
                </span>
            </button>
        </div>

        <!-- Mobile Dropdown Menu -->
        <div
            x-show="mobileOpen"
            x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="opacity-0 -translate-y-3"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-180"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-3"
            class="md:hidden mobile-menu-drawer absolute top-full left-0 w-full bg-gradient-tb shadow-2xl border-t border-white/30"
            @click.away="mobileOpen = false">

            <?php
                wp_nav_menu(array(
                    'theme_location' => 'primary',
                    'menu_class'     => 'mobile-nav py-4 px-[5vw]',
                    'container'      => false,
                ));
            ?>

            <div class="px-[5vw] pb-6 pt-2">
                <?php if (is_user_logged_in()) : ?>
                    <a href="<?= esc_url(home_url('/profile')) ?>" class="btn-secondary !text-xl !py-3 !px-8 block text-center w-full">
                        Mijn Profiel
                    </a>
                <?php else : ?>
                    <a href="<?= esc_url(home_url('/login')) ?>" class="btn-secondary !text-xl !py-3 !px-8 block text-center w-full">
                        Inloggen
                    </a>
                <?php endif; ?>
            </div>
        </div>

    </header>

    <?php if (is_front_page()) : ?>
        <?php get_template_part('template-parts/sections/hero-section'); ?>
    <?php endif; ?>
