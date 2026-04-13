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
    <div class="w-[90vw] mx-auto py-20">
        <?= get_template_part('template-parts/components/section-header', null, $packageHeaderData) ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-20 justify-center">
            <?php foreach ($packagesData as $item): ?>
                <?= get_template_part('template-parts/components/package-card', null, $item); ?>
            <?php endforeach; ?>
        </div>
        <div class="w-[70%] mx-auto rounded-[2.604vw] bg-white py-[1.563vw] px-[8vw] mt-[1.563vw]">
            <div class="flex items-center justify-center">
                <img src="<?= get_template_directory_uri(); ?>/assets/star-icon.png"
                    alt="Abonnement maandelijks opzegbaar" class="w-16 h-16 mr-4">
                <h2 class="text-primary font-primary text-[2.5vw]">Abonnement maandelijks opzegbaar</h2>
            </div>
            <p class="text-primary text-center text-[1.24vw]">Je beslist zelf of je jouw woning op Memory Lane online
                wilt houden al
                dan niet. Abonnement op elk moment opzegbaar.</p>
        </div>
    </div>
</section>