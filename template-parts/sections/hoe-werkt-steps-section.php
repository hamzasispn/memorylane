<?php
$steps = [
    [
        'number'      => '1',
        'title'       => 'Je boekt een opname',
        'description' => 'Alles begint met het inplannen van een moment waarop jouw woning digitaal wordt vastgelegd. Via onze online boekingstool kies je eenvoudig een geschikt moment. Zodra je boeking bevestigd is, weten wij precies wanneer we jouw woning mogen komen scannen.',
        'note'        => 'Het zou kunnen dat we je later nog contacteren om het uur aan te passen, naargelang onze planning van die dag.',
    ],
    [
        'number'      => '2',
        'title'       => 'We maken een professionele 3D-scan van jouw woning',
        'description' => 'Op de afgesproken dag en uur komen we langs om jouw woning digitaal in kaart te brengen. Met professionele 3D-scanningstechnologie leggen we de ruimtes vast zoals ze op dat moment zijn — niet als losse foto\'s, maar als een volledige 360° digitale weergave.',
        'note'        => 'We leggen ook de omgeving vast: een stukje van jouw straat, de volledige voortuin en eventueel ook achtertuin. Bij percelen groter dan 5a geldt een meerprijs.',
    ],
    [
        'number'      => '3',
        'title'       => 'We bouwen jouw persoonlijke Memory Lane',
        'description' => 'Na de opname verwerken we alles tot een virtuele tour van jouw woning. Die digitale tour wordt gekoppeld aan jouw persoonlijke klantenzone, waar je veilig toegang krijgt tot jouw eigen Memory Lane. Zo blijft jouw woning privé en eenvoudig bereikbaar wanneer jij dat wilt.',
        'note'        => null,
    ],
    [
        'number'      => '4',
        'title'       => 'Je kan altijd opnieuw terugkeren',
        'description' => 'Zodra jouw woning op Memory Lane staat, kan je de woning op elk moment opnieuw bekijken. Op je computer, tablet of smartphone. En zelfs ook in virtual reality, voor een nog sterkere en realistischere beleving.',
        'note'        => 'VR enkel met: Meta Quest 3, Meta Quest 2, Meta Quest, Meta Quest Pro (Oculus), of een Apple Vision Pro.',
    ],
];
?>

<section class="bg-gradient-lr border-t border-white/30 overflow-hidden">
    <div class="w-[90vw] mx-auto py-16 md:py-24">

        <div class="section-header text-center mb-12 md:mb-[3vw]">
            <h2 class="text-[8vw] md:text-[3.229vw] text-primary font-primary leading-[1.3] mb-[1.042vw] text-center">
                In 4 stappen naar jouw Memory Lane
            </h2>
            <p class="text-primary text-[4vw] md:text-[1.25vw] text-center w-full md:w-[55%] mx-auto leading-[1.8]">
                Een eenvoudig proces met een blijvende emotionele waarde.
            </p>
        </div>

        <div class="flex flex-col gap-10 md:gap-[2.5vw] animate-stagger-parent">
            <?php foreach ($steps as $index => $step) : ?>
                <div class="animate-stagger-child grid grid-cols-1 md:grid-cols-[auto_1fr] gap-5 md:gap-[2vw] items-start">

                    <!-- Step number circle -->
                    <div class="flex items-center gap-4 md:flex-col md:items-center md:gap-2">
                        <span class="flex-shrink-0 w-14 h-14 md:w-[3.5vw] md:h-[3.5vw] rounded-full bg-primary text-white flex items-center justify-center font-primary text-2xl md:text-[1.8vw] leading-none">
                            <?= $step['number'] ?>
                        </span>
                        <?php if ($index < count($steps) - 1) : ?>
                            <div class="hidden md:block w-[2px] flex-1 bg-primary/20 mt-1 h-[4vw]"></div>
                        <?php endif; ?>
                    </div>

                    <!-- Step content -->
                    <div class="bg-white/70 rounded-[2vw] p-7 md:p-[1.8vw]">
                        <h3 class="font-primary text-primary text-[6vw] md:text-[1.8vw] leading-[1.3] mb-3 md:mb-[0.6vw]">
                            <?= esc_html($step['title']) ?>
                        </h3>
                        <p class="text-primary text-[4vw] md:text-[1.2vw] leading-[1.8] mb-3">
                            <?= esc_html($step['description']) ?>
                        </p>
                        <?php if ($step['note']) : ?>
                            <p class="text-primary/60 text-[3.5vw] md:text-[1vw] leading-[1.6] italic border-t border-primary/10 pt-3">
                                <?= esc_html($step['note']) ?>
                            </p>
                        <?php endif; ?>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>

        <div class="flex justify-center mt-12 md:mt-[3vw]">
            <a href="<?php echo esc_url(home_url('/boek')); ?>" class="btn-primary animate-fade-up">
                Boek jouw opname
            </a>
        </div>

    </div>
</section>
