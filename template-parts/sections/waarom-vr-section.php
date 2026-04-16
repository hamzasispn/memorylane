<section class="bg-gradient-lr border-t border-white/30 overflow-hidden">
    <div class="w-[90vw] mx-auto py-16 md:py-24">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-10 md:gap-[4vw] items-center">

            <!-- Left: VR content -->
            <div>
                <div class="section-header text-left mb-6 md:mb-[1.5vw]">
                    <h2 class="text-[8vw] md:text-[3.229vw] text-primary font-primary leading-[1.3]">
                        Een woning vereeuwigen met virtual reality
                    </h2>
                </div>

                <div class="flex flex-col gap-4 md:gap-[1vw]">
                    <p class="page-description text-primary text-[4vw] md:text-[1.3vw] leading-[1.8]">
                        Voor wie de herinnering nog intenser wil beleven, is er ook de mogelijkheid om de woning in virtual reality te bekijken.
                    </p>
                    <p class="animate-words text-primary text-[4vw] md:text-[1.3vw] leading-[1.8]">
                        Dat geeft een extra laag aan de ervaring. Je kijkt niet alleen naar een woning, je stapt er als het ware opnieuw binnen. Voor sommige mensen maakt dat het verschil tussen herinneren en echt opnieuw voelen.
                    </p>
                    <p class="animate-words text-primary text-[4vw] md:text-[1.3vw] leading-[1.8]">
                        Virtual reality is geen must om van je Memory Lane te genieten, maar het toont wel hoe krachtig het kan zijn om een woning op een hedendaagse manier te bewaren.
                    </p>
                </div>

                <div class="mt-8 md:mt-[1.5vw] bg-white/60 rounded-[1.5vw] p-5 md:p-[1.2vw]">
                    <p class="text-primary text-[3.5vw] md:text-[1vw] leading-[1.8]">
                        <strong>Compatibele VR-brillen:</strong> Meta Quest 3, Meta Quest 2, Meta Quest, Meta Quest Pro (Oculus), of een Apple Vision Pro.
                    </p>
                </div>
            </div>

            <!-- Right: "Waarom mensen blij zijn" -->
            <div class="animate-fade-up">
                <div class="bg-white/80 rounded-[2.604vw] p-8 md:p-[2.5vw]">
                    <h3 class="font-primary text-primary text-[6.5vw] md:text-[2vw] leading-[1.3] mb-5 md:mb-[1.2vw]">
                        Waarom mensen achteraf vooral blij zijn dat ze het gedaan hebben
                    </h3>

                    <div class="flex flex-col gap-4 md:gap-[0.8vw] mb-6 md:mb-[1.2vw]">
                        <p class="text-primary text-[4vw] md:text-[1.2vw] leading-[1.8] italic">
                            Is dit nodig? Ga ik hier later echt naar terugkeren? Is het de moeite waard?
                        </p>
                        <p class="text-primary text-[4vw] md:text-[1.2vw] leading-[1.8]">
                            De echte vraag is: hoe zou het voelen als ik het niet doe, en later besef dat ik die kans voorbij heb laten gaan?
                        </p>
                        <p class="text-primary text-[4vw] md:text-[1.2vw] leading-[1.8]">
                            Een betekenisvolle woning kan je maar één keer vastleggen zoals ze écht was. Voor ze leeg is. Voor er verbouwd wordt. Voor iemand anders er woont.
                        </p>
                    </div>

                    <ul class="flex flex-col gap-2 md:gap-[0.4vw] list-none">
                        <?php
                        $reasons = [
                            'Omdat ze het toch gedaan hebben.',
                            'Omdat die plek bewaard bleef.',
                            'Omdat ze nog altijd kunnen terugkeren.',
                        ];
                        foreach ($reasons as $reason) : ?>
                            <li class="flex items-center gap-3 text-primary text-[4vw] md:text-[1.2vw]">
                                <span class="w-2 h-2 rounded-full bg-primary flex-shrink-0"></span>
                                <?= esc_html($reason) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

        </div>

    </div>
</section>
