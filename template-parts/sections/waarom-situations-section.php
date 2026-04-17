<?php
$situations = [
    [
        'svg'         => '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
        'title'       => 'Het ouderlijk huis wordt verkocht',
        'description' => 'Voor veel mensen is dit het meest herkenbare en emotionele moment. Het huis waar je opgroeide verdwijnt uit de familie, krijgt nieuwe bewoners en wordt langzaam een plek waar je alleen nog aan terugdenkt.',
    ],
    [
        'svg'         => '<path d="m21 2-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3m-3.5 3.5L19 4"/>',
        'title'       => 'Je neemt afscheid van een woning waar je zelf woonde',
        'description' => 'Je eerste appartement. Het huis waar jullie samen begonnen. De plek waar een belangrijk hoofdstuk van je leven zich afspeelde.',
    ],
    [
        'svg'         => '<path d="M7 20h10"/><path d="M10 20c5.5-2.5.8-6.4 3-10"/><path d="M9.5 9.4c1.1.8 1.8 2.2 2.3 3.7-2 .4-3.5.4-4.8-.3-1.2-.6-2.3-1.9-3-4.2 2.8-.5 4.4 0 5.5.8z"/><path d="M14.1 6a7 7 0 0 0-1.1 4c1.9-.1 3.3-.6 4.3-1.4 1-1 1.6-2.3 1.7-4.6-2.7.1-4 1-4.9 2z"/>',
        'title'       => 'Je verhuist naar een nieuwe levensfase',
        'description' => 'Soms neem je niet alleen afscheid van een huis, maar ook van een periode in je leven. Een verhuis markeert vaak een overgang. Net dan kan het bijzonder waardevol zijn om de woning zoals ze was te bewaren.',
    ],
    [
        'svg'         => '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>',
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

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-[1.8vw] animate-stagger-parent">
            <?php foreach ($situations as $item) : ?>
                <div class="animate-stagger-child bg-white/80 rounded-[2.604vw] p-8 md:p-[2vw] flex gap-5 items-start">
                    <span class="flex-shrink-0 mt-1 text-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-10 h-10 md:w-[2.2vw] md:h-[2.2vw]">
                            <?= $item['svg'] ?>
                        </svg>
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
