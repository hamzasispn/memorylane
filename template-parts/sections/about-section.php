<section class="about-us relative bg-gradient-lr overflow-hidden z-10">
    <img src="<?= get_template_directory_uri() . '/assets/sign-pole-img.png' ?>"
        class="absolute -top-12 left-[52%] transform -translate-x-1/2 object-contain w-1/3 h-auto -z-10"
        alt="Over Memory Lane">
    <div class="w-[90vw] mx-auto py-40">
        <div class="grid grid-cols-2">
            <div>
                <h2 class="text-[45px] text-primary font-primary w-[67%] leading-[1.3] mb-3">Sommige plekken verdienen
                    het om voor altijd te blijven
                    bestaan.</h2>
                <div class="text-[28px] text-primary mb-8 flex flex-col gap-4 w-[90%]">
                    <p>
                        De plek waar je zoveel herinneringen maakte.
                        Waar geleefd, gelachen en samengekomen
                        werd.</p>
                    <p>
                        Een huis dat meer betekende dan alleen
                        bakstenen en muren.
                    </p>

                    <p>
                        Op Memory Lane blijft die plek bewaard als
                        een virtuele herinnering waar je altijd opnieuw
                        naar can terugkeren.
                    </p>

                    <p>
                        Met een professionele 3D-scan maken we
                        een virtuele tour van jouw woning, zodat je
                        er altijd opnieuw doorheen can wandelen,
                        zelfs jaren later.
                    </p>
                </div>

                <a href="<?php echo esc_url(home_url('/')); ?>" class="btn-secondary">
                    Vereeuwig jouw woning
                </a>
            </div>
            <div>
                <img src="<?= get_template_directory_uri();?>/assets/about-us-section.png"
                    class="w-full h-full" alt="Over Memory Lane">
            </div>
        </div>
    </div>
</section>