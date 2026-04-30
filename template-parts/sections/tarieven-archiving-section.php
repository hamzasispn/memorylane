<?php
$archiveCards = [
    [
        'icon'  => get_template_directory_uri() . '/assets/icons/archive-icon.png',
        'title' => 'Woning archiveren',
        'desc'  => 'Wanneer je beslist om jouw abonnement stop te zetten, wordt jouw woning niet langer actief online gehouden, maar gearchiveerd. De woning is dan niet meer toegankelijk in jouw klantenzone, maar ook niet volledig verloren. Ze wordt bewaard in een niet-actieve toestand, zodat ze later opnieuw geactiveerd kan worden.',
    ],
    [
        'icon'  => get_template_directory_uri() . '/assets/icons/heart-icon.png',
        'title' => 'Gemoedsrust',
        'desc'  => 'Voor veel mensen geeft dat extra gemoedsrust. Omdat ze weten dat de woning nog altijd bestaat op hun Memory Lane, ook al houden ze ze op dat moment niet actief online.',
    ],
    [
        'icon'  => get_template_directory_uri() . '/assets/icons/activate-icon.png',
        'title' => 'Heractivatie mogelijk',
        'desc'  => 'Via een eenmalige activatiekost wordt de tour manueel opnieuw online gezet. Dit kan tot 8 uur duren.',
    ],
];
?>

<section class="bg-gradient-bt border-t border-white/30 overflow-hidden">
    <div class="w-[90vw] mx-auto py-16 md:py-24">

        <div class="section-header text-center mb-10 md:mb-[2.5vw]">
            <h2 class="text-[8vw] md:text-[3.229vw] text-primary font-primary leading-[1.3] mb-[1.042vw] text-center">
                Wat betekent archivering?
            </h2>
            <p class="text-primary text-[4vw] md:text-[1.25vw] text-center w-full md:w-[60%] mx-auto leading-[1.8]">
                Je kiest niet tussen "online" of "volledig weg", er bestaat een tussenstap.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-[1.8vw] animate-stagger-parent">
            <?php foreach ($archiveCards as $card): ?>
                <div class="archive-card group animate-stagger-child bg-white/80 rounded-[2.604vw] p-8 md:p-[2vw] text-center">
                    <div class="flex justify-center mb-4 text-primary">
                        <img src="<?= esc_url($card['icon']) ?>" alt="<?= esc_attr($card['title']) ?>"
                            class="w-12 h-12 md:w-[10vw] md:h-[10vw] transition-transform duration-300 group-hover:scale-125 group-hover:-translate-y-1">
                    </div>
                    <h3 class="font-primary text-primary text-[6vw] md:text-[1.8vw] leading-[1.3] mb-3">
                        <?= esc_html($card['title']) ?>
                    </h3>
                    <p class="text-primary text-[4vw] md:text-[1.2vw] leading-[1.8]">
                        <?= esc_html($card['desc']) ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
</section>
