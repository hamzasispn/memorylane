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

<section class="package-section bg-gradient-tb border-t border-white">
    <div class="w-[90vw] mx-auto py-20">
        <?= get_template_part('template-parts/components/section-header', null, $packageHeaderData) ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-20">
            <?php foreach ($packagesData as $item): ?>
                <?= get_template_part('template-parts/components/package-card', null, $item); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>