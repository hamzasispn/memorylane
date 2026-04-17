<?php
$whyChooseHeaderData = [
    'title' => 'Waarom je woning vereeuwigen op Memory Lane?',
    'description' => 'Sommige plekken verdwijnen uit je leven, maar dat betekent niet dat je ze volledig moet loslaten. Met Memory Lane wordt een woning digitaal bewaard, zodat je er altijd opnieuw naar kan terugkeren: vandaag, morgen en zelfs over tientallen jaren.'
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
        'description' => 'Met een VR-bril kan je jouw woning nog realistischer beleven.'
    ]
];
?>

<section class="why-choose-us bg-gradient-tb overflow-hidden border-t border-white">
    <div class="w-[90vw] mx-auto pt-16 md:pt-20 pb-16 md:pb-24">

        <div class="section-header text-center mb-10 md:mb-[2.5vw]">
            <h2 class="text-[8vw] md:text-[3.229vw] text-primary font-primary leading-[1.3] mb-[1.042vw] text-center">
                <?= esc_html($whyChooseHeaderData['title']) ?>
            </h2>
            <p class="text-primary text-[4vw] md:text-[1.25vw] text-center w-full md:w-[65%] mx-auto leading-[1.8]">
                <?= esc_html($whyChooseHeaderData['description']) ?>
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 md:gap-[2vw]">
            <?php foreach ($whyChooseData as $item): ?>
                <div class="bg-white flex flex-col items-center justify-between text-center py-10 md:py-[3vw] px-6 md:px-[1.5vw] border border-white/60 rounded-[2.604vw] relative">
                    <h3 class="text-[6vw] md:text-[1.8vw] text-primary font-primary mb-6 md:mb-[1.5vw] leading-[1.3]">
                        <?= esc_html($item['title']) ?>
                    </h3>
                    <img src="<?= esc_url($item['icon']) ?>" alt="<?= esc_attr($item['title']) ?>"
                        class="!w-[20vw] md:!w-[5vw] h-auto mb-6 md:mb-[1.5vw] object-contain">
                    <p class="text-primary text-[4vw] md:text-[1.2vw] text-center leading-[1.8]">
                        <?= esc_html($item['description']) ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="flex justify-end mt-6 md:mt-[1.5vw]">
            <p class="text-primary text-[3.5vw] md:text-[1.1vw] leading-[2] w-full md:w-[30%]">
                *VR enkel met: Meta Quest 3, Meta Quest 2, Meta Quest, Meta Quest Pro (Oculus), of een Apple Vision Pro.
            </p>
        </div>

        <div class="flex justify-center mt-8 md:mt-[2vw]">
            <a href="<?php echo esc_url(home_url('/waarom')); ?>" class="btn-secondary animate-fade-up">
                Meer informatie
            </a>
        </div>

    </div>
</section>
