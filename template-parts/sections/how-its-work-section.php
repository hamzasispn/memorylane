<?php
$headerData = [
    'title'       => 'Hoe werkt het?',
    'description' => 'Het bewaren van jouw woning als digitale herinnering is eenvoudiger dan je denkt. In drie stappen maken we jouw woning een virtuele twin. '
];

$itemData = [
    [
        'step'        => '1',
        'title'       => 'Boek een opname',
        'description' => 'Bestel een opname en plan eenvoudig een moment in via onze online boekingstool waarop we jouw woning in kaart komen brengen met onze 3D scanningsapparatuur. '
    ],
    [
        'step'        => '2',
        'title'       => 'D-scan van je woning',
        'description' => 'Met ons professioneel 3D-scanningsmateriaal maken we een volledige digitale opname van jouw woning.'
    ],
    [
        'step'        => '3',
        'title'       => 'Jouw eigen Memory Lane',
        'description' => 'Via jouw persoonlijke klantenzone, kan je op elk moment jouw woning virtueel bezoeken. (Je kan dit ook met een VR-bril doen.)'
    ]
];
?>

<!-- hw-scroll-outer: NO overflow-hidden — required for sticky to work -->
<div class="hw-scroll-outer bg-gradient-lr border-t border-white">

    <!-- Section header scrolls away normally -->
    <div class="w-[90vw] mx-auto pt-16 md:pt-20">
        <?= get_template_part('template-parts/components/section-header', null, $headerData) ?>
    </div>

    <!-- Sticky inner — sticks below fixed header on desktop -->
    <div class="hw-sticky-inner md:sticky md:top-[5.5vw]">
        <div class="w-[90vw] mx-auto pb-16 md:pb-20">
            <div class="grid grid-cols-1 relative">
                <img src="<?= get_template_directory_uri(); ?>/assets/Aerrow.png"
                    class="absolute -top-[2vw] right-[20vw] w-[18%] h-auto hidden md:block" />
                <a href="<?= esc_url(home_url('/boek')) ?>" class="btn-primary absolute top-[3vw] right-[10vw] hidden md:inline-flex">Boek hier</a>

                <?php foreach ($itemData as $item): ?>
                    <div class="hw-scroll-step" data-vis="">
                        <?= get_template_part('template-parts/components/how-its-work-card', null, $item) ?>
                    </div>
                <?php endforeach; ?>

                <!-- Mobile CTA -->
                <div class="flex justify-center mt-6 md:hidden">
                    <a href="<?= esc_url(home_url('/boek')) ?>" class="btn-primary">Boek hier</a>
                </div>
            </div>
        </div>
    </div>

</div>
