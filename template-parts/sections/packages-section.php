<?php
$packageHeaderData = [
    'title' => 'Tarieven',
    'subtitle' => 'Transparant en Eenvoudig',
    'description' => 'Je betaalt één keer voor de opname (1 jaar abonnement inbegrepen) en daarna kan je kiezen om maandelijks een klein bedrag te betalen om de virtuele tour van jouw woning online te houden.'
];
$packagesData = [
    [
        'title' => 'Opname woning',
        'price' => '€00 eenmalig',
        'icon' => get_template_directory_uri() . '/assets/Camera.png',
        'features' => [
            '3D-scan van jouw woning',
            'Virtuele Tour',
            'Persoonlijke Klantenzone',
            '1 jaar abonnement inbegrepen'
        ]
    ],
    [
        'title' => 'Woning online houden',
        'price' => '€0,0/maand',
        'icon' => get_template_directory_uri() . '/assets/Cloud.png',
        'features' => [
            '24/7 toegang tot jouw tour',
            'Veilig opgeslagen in de cloud',
            'Gemakkelijk te bekijken via jouw klantenzone'
        ]
    ],
    [
        'title' => 'Opnieuw activeren',
        'price' => '€00 eenmalig',
        'icon' => get_template_directory_uri() . '/assets/rotate-icon.png',
        'description' => 'Abonnement stopgezet, maar wil je toch graag jouw tour bekijken? Dat kan via een her-activatie, waarna jouw tour binnen de 6 uur opnieuw toegankelijk is.',
        'extra' => '+ €0,0/maand'
    ],
];
?>

<section class="package-section bg-gradient-bt border-t border-white">
    <div class="w-[90vw] mx-auto py-16 md:py-20">

        <div class="section-header text-center mb-10 md:mb-[2.5vw]">
            <h2 class="text-[8vw] md:text-[3.229vw] text-primary font-primary leading-[1.3] mb-[0.5vw] text-center">
                <?= esc_html($packageHeaderData['title']) ?>
            </h2>
            <h4 class="text-[5vw] md:text-[1.771vw] text-primary font-semibold mb-[1.042vw] text-center">
                <?= esc_html($packageHeaderData['subtitle']) ?>
            </h4>
            <p class="text-primary text-[4vw] md:text-[1.25vw] text-center w-full md:w-[60%] mx-auto leading-[1.8]">
                <?= esc_html($packageHeaderData['description']) ?>
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 md:gap-[2vw]">
            <?php foreach ($packagesData as $item): ?>
                <?= get_template_part('template-parts/components/package-card', null, $item); ?>
            <?php endforeach; ?>
        </div>

        <div class="w-full md:w-[70%] mx-auto rounded-[2.604vw] bg-white py-5 md:py-[1.563vw] px-8 md:px-[4vw] mt-6 md:mt-[1.563vw]">
            <div class="flex items-center justify-center gap-4">
                <img src="<?= get_template_directory_uri(); ?>/assets/star-icon.png"
                    alt="Abonnement maandelijks opzegbaar"
                    class="!w-12 !h-12 md:!w-[2.5vw] md:!h-[2.5vw] object-contain flex-shrink-0">
                <h4 class="text-primary font-primary text-[5.5vw] md:text-[1.8vw] leading-[1.3]">
                    Abonnement maandelijks opzegbaar
                </h4>
            </div>
            <p class="text-primary text-center text-[3.5vw] md:text-[1.1vw] leading-[1.8] mt-2">
                Je beslist zelf of je jouw woning op Memory Lane online wilt houden al dan niet. Abonnement op elk moment opzegbaar.
            </p>
        </div>

        <div class="flex justify-center mt-8 md:mt-[2vw]">
            <a href="<?php echo esc_url(home_url('/tarieven')); ?>" class="btn-secondary animate-fade-up">
                Meer informatie over tarieven
            </a>
        </div>

    </div>
</section>
