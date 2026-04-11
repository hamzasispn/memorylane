<?php

$whyChooseHeaderData = [
    'title' => 'Waarom je woning vereeuwigen op Memory Lane?',
    'description' => 'Sommige plekken verdwijnen uit je leven, maar dat betekent niet dat je ze volledig moet loslaten. Met Memory Lane wordt een woning digitaal bewaard, zodat je er altijd opnieuw naar can terugkeren: vandaag, morgen en zelfs over tientallen jaren.'
];

$whyChooseData = [
    [
        'icon' => get_template_directory_uri() . '/assets/Home.png',
        'title' => 'Bewaar een plek die belangrijk voor je is',
        'description' => 'Sommige plekken betekenen gewoon te veel om los te laten. Memory Lane helpt je om die plek digitaal te bewaren voor later.'
    ],
    [
        'icon' => get_template_directory_uri() . '/assets/360.png',
        'title' => 'Alsof je er weer even bent',
        'description' => 'Dankzij de 360° virtuele tour kan je telkens opnieuw door de ruimtes wandelen die je niet wilt vergeten.'
    ],
    [
        'icon' => get_template_directory_uri() . '/assets/vr-icon.png',
        'title' => 'Bezoek je huis in VR',
        'description' => 'Met een *VR-bril kan je jouw woning nog realistischer beleven.'
    ]
];
?>

<section class="why-choose-us bg-gradient-tb overflow-hidden border-t border-white">
    <div class="w-[90vw] mx-auto pt-20 pb-32">
        <?= get_template_part('template-parts/components/section-header', null, $whyChooseHeaderData) ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-20">
            <?php foreach ($whyChooseData as $item): ?>
                <?= get_template_part('template-parts/components/why-choose-card', null, $item); ?>
            <?php endforeach; ?>
        </div>
        <div class="flex justify-end mt-10">
            <p class="text-primary text-xl leading-[2] w-[30%]">
                *enkel met deze brillen mogelijk: Meta Quest 3, Meta
                Quest 2, Meta Quest, or Meta Quest Pro ( Oculus), of een
                Apple Vision Pro,
            </p>
        </div>
    </div>
</section>