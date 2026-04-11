<?php
$headerData = [
    'title' => 'Hoe werkt het?',
    'description' => 'Het bewaren van jouw woning als digitale herinnering is eenvoudiger dan je denkt. In drie stappen maken we jouw woning een virtuele twin. '
];

$itemData = [
    [
        'step' => '1',
        'title' => 'Boek een opname',
        'description' => 'Bestel een opname en plan eenvoudig een moment in via onze online boekingstool waarop we jouw woning in kaart komen brengen met onze 3D scanningsapparatuur. '
    ],
    [
        'step' => '2',
        'title' => 'D-scan van je woning',
        'description' => 'Met ons professioneel 3D-scanningsmateriaal maken we een volledige digitale opname van jouw woning.'
    ],
    [
        'step' => '3',
        'title' => 'Jouw eigen Memory Lane',
        'description' => 'Via jouw persoonlijke klantenzone, kan je op elk moment jouw woning virtueel bezoeken. (Je kan dit ook met een VR-bril doen.)'
    ]
];
?>

<section class="how-its-work bg-gradient-lr overflow-hidden border-t border-white">
    <div class="w-[90vw] mx-auto py-20">
        <?= get_template_part('template-parts/components/section-header', null, $headerData) ?>
        <div class="grid grid-cols-1 row-gap-20 relative">
            <img src="<?= get_template_directory_uri(); ?>/assets/Aerrow.png"
                class="absolute -top-[2vw] right-[20vw] w-[18%] h-auto" />
            <a href="" class="btn-primary absolute top-[3vw] right-[10vw]">Boek hier</a>
            <?php foreach ($itemData as $index => $item): ?>
                <?= get_template_part('template-parts/components/how-its-work-card', null, $item) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>