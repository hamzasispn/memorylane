<?php
$pricingCards = [
    [
        'icon'        => get_template_directory_uri() . '/assets/icons/camera-around-icon.png',
        'title'       => 'Jouw woning op Memory Lane',
        'price'       => '€ XX eenmalig',
        'badge'       => null,
        'highlight'   => true,
        'features'    => [
            'Professionele 3D-scan van jouw volledige woning en perceel',
            'Verwerking tot een virtuele tour',
            'Toegang tot jouw persoonlijke klantenzone',
            '1 jaar online beschikbaarheid inbegrepen',
        ],
        'note'        => 'Prijs geldig tot max. 300 m² totale vloeroppervlakte en 7a perceelsoppervlakte. Voor grotere woningen of percelen kan je steeds een offerte aanvragen.',
    ],
    [
        'icon'        => get_template_directory_uri() . '/assets/icons/cloud-icon.png',
        'title'       => 'Je woning online actief houden',
        'subtitle'    => '(na 1 jaar)',
        'price'       => '€ XX / maand',
        'badge'       => null,
        'highlight'   => false,
        'features'    => [
            'Jouw virtuele tour blijft online beschikbaar',
            'Toegang via jouw klantenzone',
            'Op elk moment opnieuw je woning bekijken',
            'Opzegbaar wanneer je wil',
        ],
        'note'        => 'Na het eerste jaar kies je zelf of je jouw woning op Memory Lane actief wilt houden.',
    ],
    [
        'icon'        => get_template_directory_uri() . '/assets/icons/activate-icon.png',
        'title'       => 'Heractivatie van een gearchiveerde tour',
        'price'       => '€ XX eenmalig',
        'badge'       => null,
        'highlight'   => false,
        'features'    => [
            'Heractivatie binnen de 8 uur',
            'Tour opnieuw zichtbaar in jouw klantenzone',
            'Melding bij activatie',
        ],
        'note'        => 'Heractivatie gebeurt manueel door één van onze collega\'s en kan tot 8 uur duren.',
    ],
];
?>

<section class="package-section bg-gradient-bt border-t border-white overflow-hidden">
    <div class="w-[90vw] mx-auto py-16 md:py-20">

        <div class="section-header text-center mb-10 md:mb-[2.5vw]">
            <h2 class="text-[8vw] md:text-[3.229vw] text-primary font-primary leading-[1.3] mb-[1.042vw] text-center">
                Duidelijke tarieven, zonder verrassingen
            </h2>
            <p class="text-primary text-[4vw] md:text-[1.25vw] text-center w-full md:w-[55%] mx-auto leading-[1.8]">
                Eerlijk, duidelijk en zonder verborgen kosten.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-[1.8vw] animate-stagger-parent">
            <?php foreach ($pricingCards as $card) : ?>
                <div class="animate-stagger-child flex flex-col bg-white rounded-[2.604vw] p-8 md:p-[2vw] relative overflow-hidden <?= $card['highlight'] ? 'border-2 border-primary/40' : '' ?>">

                    <?php if ($card['badge']) : ?>
                        <div class="absolute top-0 right-0 bg-primary text-white text-[3vw] md:text-[0.9vw] font-semibold px-4 py-1 rounded-bl-[1.5vw]">
                            <?= esc_html($card['badge']) ?>
                        </div>
                    <?php endif; ?>

                    <img src="<?= esc_url($card['icon']) ?>" alt="<?= esc_attr($card['title']) ?>" class="!w-[20vw] md:!w-[7vw] h-auto mb-5 md:mb-[1.2vw] object-contain mx-auto">

                    <h3 class="font-primary text-primary text-[6vw] md:text-[1.8vw] leading-[1.3] text-center mb-1">
                        <?= esc_html($card['title']) ?>
                    </h3>
                    <?php if (!empty($card['subtitle'])): ?>
                        <p class="text-primary italic text-[3.5vw] md:text-[1vw] text-center mb-4 md:mb-[1vw]"><?= esc_html($card['subtitle']) ?></p>
                    <?php else: ?>
                        <div class="mb-4 md:mb-[1vw]"></div>
                    <?php endif; ?>

                    <ul class="flex flex-col gap-2 md:gap-[0.5vw] list-none mb-5 md:mb-[1.2vw] flex-1">
                        <?php foreach ($card['features'] as $feature) : ?>
                            <li class="flex items-start gap-2 text-primary text-[3.5vw] md:text-[1.1vw] leading-[1.6]">
                                <img src="<?= get_template_directory_uri() ?>/assets/check-icon.png" alt="" class="!w-[4vw] md:!w-[1.1vw] !h-auto flex-shrink-0 mt-0.5">
                                <span><?= esc_html($feature) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <p class="text-primary text-[6vw] md:text-[1.8vw] font-primary font-bold text-center mt-auto pt-4 border-t border-primary/10">
                        <?= esc_html($card['price']) ?>
                    </p>

                    <?php if ($card['note']) : ?>
                        <p class="text-primary/50 text-[3vw] md:text-[0.85vw] leading-[1.6] text-center mt-3 italic">
                            <?= esc_html($card['note']) ?>
                        </p>
                    <?php endif; ?>

                </div>
            <?php endforeach; ?>
        </div>

        <div class="w-full md:w-[70%] mx-auto rounded-[2.604vw] bg-white py-5 md:py-[1.563vw] px-8 md:px-[4vw] mt-6 md:mt-[1.563vw]">
            <div class="flex items-center justify-center gap-4">
                <img src="<?= get_template_directory_uri(); ?>/assets/icons/cloud-icon.png"
                    alt="Abonnement maandelijks opzegbaar"
                    class="!w-12 !h-12 md:!w-[2.5vw] md:!h-[2.5vw] object-contain flex-shrink-0">
                <h4 class="text-primary font-primary text-[5.5vw] md:text-[1.8vw] leading-[1.3]">
                    Abonnement maandelijks opzegbaar
                </h4>
            </div>
            <p class="text-primary text-center text-[3.5vw] md:text-[1.1vw] leading-[1.8] mt-2">
                Je beslist zelf of je jouw woning op Memory Lane online wilt houden. Het abonnement is op elk moment opzegbaar.
            </p>
        </div>

        <div class="flex justify-center mt-8 md:mt-[2vw]">
            <a href="<?php echo esc_url(home_url('/tarieven')); ?>" class="btn-secondary animate-fade-up">
                Meer informatie over tarieven
            </a>
        </div>

    </div>
</section>
