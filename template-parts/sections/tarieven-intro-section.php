<section class="page-hero-video-wrap relative overflow-hidden min-h-[90vh] flex items-center">

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
    <div class="page-hero-content relative z-10 w-[90vw] mx-auto pt-36 md:pt-44 pb-24 md:pb-36 text-center">

        <h1 class="page-hero-title font-primary text-white text-[10vw] md:text-[3.8vw] leading-[1.2] mb-6 md:mb-[1.5vw]">
            Prijsoverzicht
        </h1>

        <div class="max-w-[70vw] md:max-w-[52vw] mx-auto flex flex-col gap-5 md:gap-[1.2vw] mt-6 md:mt-[2vw]">
            <p class="page-description text-white/90 text-[4.5vw] md:text-[1.3vw] leading-[1.8]">
                Bij Memory Lane houden we het graag helder en transparant.
            </p>
            <p class="animate-words text-white/90 text-[4.5vw] md:text-[1.3vw] leading-[1.8]">
                Wanneer je een woning laat vereeuwigen, betaal je in eerste instantie voor de opname en de creatie van jouw virtuele tour. In die prijs is ook het <strong class="text-white">eerste jaar online beschikbaarheid inbegrepen</strong>.
            </p>
            <p class="animate-words text-white/90 text-[4.5vw] md:text-[1.3vw] leading-[1.8]">
                Na dat eerste jaar kies je zelf of je jouw woning op Memory Lane online actief wil houden via een maandelijks abonnement. Zo blijft het systeem flexibel, duidelijk en afgestemd op wat voor jou het meest waardevol is.
            </p>
        </div>

    </div>

    <!-- Scroll-down indicator -->
    <div class="absolute bottom-8 left-1/2 -translate-x-1/2 z-10 animate-hero-scroll-hint">
        <div class="w-[1.5px] h-12 md:h-16 bg-white/50 mx-auto rounded-full overflow-hidden">
            <div class="w-full h-1/2 bg-white rounded-full animate-scroll-line"></div>
        </div>
    </div>

</section>
