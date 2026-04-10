<div class="grid grid-cols-12 justify-between items-center">
    <div class="col-span-2">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="site-logo">
            <img src="<?= get_template_directory_uri() . '/assets/Logo.png' ?>" alt="Site Logo">
        </a>
    </div>

    <div class="col-span-8">
        <?php
            wp_nav_menu(
                array(
                    'theme_location' => 'primary',
                    'menu_class' => 'flex flex-wrap gap-6 justify-center items-center font-primary text-2xl text-white',
                )
            );
        ?>
    </div>

    <div class="col-span-2 justify-end flex gap-4">
        <?php if (is_user_logged_in()) : ?>
            <a href="<?= esc_url(home_url('/profile')) ?>" class="btn-secondary">
                Mijn Profiel
            </a>
        <?php else : ?>
            <a href="<?= esc_url(home_url('/login')) ?>" class="btn-secondary">
                Inloggen
            </a>
        <?php endif; ?>
    </div>


</div>