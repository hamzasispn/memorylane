<section class="page-hero-video-wrap relative overflow-hidden min-h-[70vh] flex items-center">

    <!-- Background video: desktop -->
    <video autoplay muted loop playsinline
           class="page-hero-video hidden md:block absolute inset-0 w-full h-full object-cover">
        <source src="<?= get_template_directory_uri() ?>/assets/videos/hero-video-16-9.mp4" type="video/mp4">
    </video>

    <!-- Background video: mobile -->
    <video autoplay muted loop playsinline
           class="page-hero-video md:hidden block absolute inset-0 w-full h-full object-cover">
        <source src="<?= get_template_directory_uri() ?>/assets/videos/hero-video-9-16.mp4" type="video/mp4">
    </video>

    <!-- Gradient overlay -->
    <div class="absolute inset-0 bg-gradient-to-b from-primary/80 via-primary/65 to-primary/75 pointer-events-none"></div>

    <!-- Content -->
    <div class="page-hero-content relative z-10 w-[90vw] mx-auto pt-36 md:pt-44 pb-20 md:pb-28 text-center">

        <h1 class="page-hero-title font-primary text-white text-[10vw] md:text-[3.8vw] leading-[1.2] mb-6 md:mb-[1.5vw]">
            Neem contact op
        </h1>

        <div class="max-w-[70vw] md:max-w-[52vw] mx-auto flex flex-col gap-5 md:gap-[1.2vw] mt-6 md:mt-[2vw]">
            <p class="page-description text-white/90 text-[4.5vw] md:text-[1.3vw] leading-[1.8]">
                We helpen je graag verder. Heb je een vraag over de tarieven, voorwaarden of wil je een offerte aanvragen? Stuur ons gerust een bericht of boek meteen een opname in.
            </p>
        </div>

    </div>

</section>
