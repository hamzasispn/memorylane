<section class="h-screen relative">
    <video autoplay muted loop class="md:block hidden w-full h-full object-cover">
        <source src="<?= get_template_directory_uri() . '/assets/videos/hero-video-16-9.mp4' ?>" type="video/mp4">
        Your browser does not support the video tag.
    </video>
    <video autoplay muted loop class="md:hidden block w-full h-full object-cover">
        <source src="<?= get_template_directory_uri() . '/assets/videos/hero-video-9-16.mp4' ?>" type="video/mp4">
        Your browser does not support the video tag.
    </video>
    <a href="<?php echo esc_url(home_url('/')); ?>" class="absolute bottom-20 left-1/2 transform -translate-x-1/2 btn-primary">
            Boek een adada
    </a>
</section>