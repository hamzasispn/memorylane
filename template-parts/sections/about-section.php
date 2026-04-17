<section class="about-us relative bg-gradient-lr overflow-hidden z-10">
    <img src="<?= get_template_directory_uri() . '/assets/sign-pole-img.png' ?>"
        class="hidden md:block absolute -top-12 left-[52%] transform -translate-x-1/2 object-contain w-1/3 h-auto -z-10"
        alt="Over Memory Lane">
    <div class="w-[90vw] mx-auto py-16 md:py-32">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-10 md:gap-[3vw] items-center">
            <div>
                <h2 class="text-[9vw] md:text-[2.8vw] text-primary font-primary w-full md:w-[85%] leading-[1.3] mb-4 md:mb-[0.8vw]">
                    Sommige plekken verdienen het om voor altijd te blijven bestaan.
                </h2>
                <div class="text-[4.5vw] md:text-[1.35vw] text-primary mb-8 md:mb-[1.5vw] flex flex-col gap-4 md:gap-[0.8vw] w-full md:w-[90%] leading-[1.8]">
                    <p>De plek waar je zoveel herinneringen maakte. Waar geleefd, gelachen en samengekomen werd.</p>
                    <p>Een huis dat meer betekende dan alleen bakstenen en muren.</p>
                    <p>Op Memory Lane blijft die plek bewaard als een virtuele herinnering waar je altijd opnieuw naar kan terugkeren.</p>
                    <p>Met een professionele 3D-scan maken we een virtuele tour van jouw woning, zodat je er altijd opnieuw doorheen kan wandelen, zelfs jaren later.</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="btn-secondary">
                        Vereeuwig jouw woning
                    </a>
                    <a href="<?php echo esc_url(home_url('/waarom')); ?>" class="btn-secondary">
                        Meer informatie
                    </a>
                </div>
            </div>
            <div>
                <img src="<?= get_template_directory_uri();?>/assets/about-us-section.png"
                    class="w-full h-auto rounded-[2vw]" alt="Over Memory Lane">
            </div>
        </div>
    </div>
</section>
