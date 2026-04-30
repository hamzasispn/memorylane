<?php
$situations = [
    [
        'icon'        => get_template_directory_uri() . '/assets/icons/house-icon.png',
        'title'       => 'Het ouderlijk huis wordt verkocht',
        'description' => 'Voor veel mensen is dit het meest herkenbare en emotionele moment. Het huis waar je opgroeide verdwijnt uit de familie, krijgt nieuwe bewoners en wordt langzaam een plek waar je alleen nog aan terugdenkt.',
    ],
    [
        'icon'        => get_template_directory_uri() . '/assets/icons/memory-icon.png',
        'title'       => 'Je neemt afscheid van een woning waar je zelf woonde',
        'description' => 'Je eerste appartement. Het huis waar jullie samen begonnen. De plek waar een belangrijk hoofdstuk van je leven zich afspeelde. Sommige woningen laat je niet zomaar los.',
    ],
    [
        'icon'        => get_template_directory_uri() . '/assets/icons/heart-icon.png',
        'title'       => 'Je wil een betekenisvolle plek bewaren voor later',
        'description' => 'Sommige mensen voelen gewoon intuïtief dat een plek te waardevol is om te vergeten. Niet omdat ze vandaag al afscheid nemen, maar omdat ze nu al weten dat die woning later veel voor hen zal blijven betekenen.',
    ],
];
?>

<section class="bg-gradient-tb border-t border-white/30 overflow-hidden">
    <div class="w-[90vw] mx-auto py-16 md:py-24">

        <div class="section-header text-center mb-10 md:mb-[2.5vw]">
            <h2 class="text-[8vw] md:text-[3.229vw] text-primary font-primary leading-[1.3] mb-[1.042vw] text-center">
                Voor welke situaties is een woning vereeuwigen interessant?
            </h2>
            <p class="text-primary text-[4vw] md:text-[1.25vw] text-center w-full md:w-[60%] mx-auto leading-[1.8]">
                Niet elke woning heeft dezelfde betekenis — maar sommige plekken dragen zoveel gevoel in zich dat het idee om ze volledig kwijt te raken simpelweg te zwaar voelt.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-[1.8vw] animate-stagger-parent">
            <?php foreach ($situations as $item) : ?>
                <div class="situation-card group animate-stagger-child bg-white/80 rounded-[2.604vw] p-8 md:p-[2vw] flex gap-5 items-start">
                    <span class="flex-shrink-0 mt-1 text-primary">
                        <img src="<?= esc_url($item['icon']) ?>" alt="" class="w-14 h-14 md:w-[5vw] md:h-[5vw] object-contain transition-transform duration-300 group-hover:scale-125 group-hover:-translate-y-1">
                    </span>
                    <div>
                        <h3 class="font-primary text-primary text-[6vw] md:text-[1.8vw] leading-[1.3] mb-3 md:mb-[0.6vw]">
                            <?= esc_html($item['title']) ?>
                        </h3>
                        <p class="text-primary text-[4vw] md:text-[1.2vw] leading-[1.8]">
                            <?= esc_html($item['description']) ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
</section>
