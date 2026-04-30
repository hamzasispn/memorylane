<?php
$reasons = [
    [
        'icon'        => get_template_directory_uri() . '/assets/icons/house.svg',
        'title'       => 'Omdat herinneringen verbonden zijn aan plaatsen',
        'description' => 'Herinneringen leven niet alleen in foto\'s of verhalen, maar ook in ruimtes. Soms hoef je maar een gang, een deur of een bepaalde lichtinval te zien om meteen weer iets te voelen. Een gewone foto toont een moment. Een virtuele tour bewaart een plek.',
    ],
    [
        'icon'        => get_template_directory_uri() . '/assets/icons/hourglass.svg',
        'title'       => 'Omdat afscheid nemen vaak sneller gaat dan je denkt',
        'description' => 'Veel mensen denken: dat doen we later nog wel. Tot later plots te laat is. Een woning wordt verkocht. Een verhuisdatum komt dichterbij. De sleutels worden overgedragen. Net daarom is een woning vereeuwigen zo waardevol: je legt ze vast vóór ze verdwijnt.',
    ],
    [
        'icon'        => get_template_directory_uri() . '/assets/icons/memory.svg',
        'title'       => 'Omdat sommige plekken een blijvende emotionele waarde hebben',
        'description' => 'Dat kan het ouderlijk huis zijn. Je eerste eigen woning. Het huis waar je gezin groeide. Of een plek waar je een diepe nostalgische band mee hebt. Een woning vereeuwigen is dan geen rationele keuze, maar een emotionele.',
    ],
    [
        'icon'        => get_template_directory_uri() . '/assets/icons/heart.svg',
        'title'       => 'Omdat je later dankbaar zal zijn dat je het gedaan hebt',
        'description' => 'Een woning digitaal bewaren is een geschenk aan je toekomstige zelf. En soms ook aan je kinderen, familie of geliefden. Wat vandaag vanzelfsprekend lijkt, kan later onbetaalbaar aanvoelen.',
    ],
];
?>

<section class="bg-gradient-lr border-t border-white/30 overflow-hidden">
    <div class="w-[90vw] mx-auto py-16 md:py-24">

        <div class="section-header text-center mb-10 md:mb-[2.5vw]">
            <h2 class="text-[8vw] md:text-[3.229vw] text-primary font-primary leading-[1.3] mb-[1.042vw] text-center">
                Waarom een woning vereeuwigen een waardevolle keuze is
            </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-[2vw] animate-stagger-parent">
            <?php foreach ($reasons as $reason) : ?>
                <div class="animate-stagger-child reason-card group bg-white/70 rounded-[2.604vw] p-8 md:p-[2vw] text-center">
                    <div class="flex justify-center mb-4 text-primary">
                        <img src="<?= esc_url($reason['icon']) ?>" alt="" class="w-12 h-12 md:w-[3vw] md:h-[3vw] transition-transform duration-300 group-hover:scale-125 group-hover:-translate-y-1">
                    </div>
                    <h3 class="font-primary text-primary text-[6vw] md:text-[1.8vw] leading-[1.3] mb-4 md:mb-[0.8vw]">
                        <?= esc_html($reason['title']) ?>
                    </h3>
                    <p class="text-primary text-[4vw] md:text-[1.2vw] leading-[1.8]">
                        <?= esc_html($reason['description']) ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
</section>
