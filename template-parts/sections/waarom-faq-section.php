<?php
$faqItems = [
    [
        'question' => 'Waarom zou ik een woning vereeuwigen?',
        'answer'   => 'Omdat sommige woningen meer zijn dan alleen een huis. Ze dragen herinneringen, emoties en levensmomenten die je niet wilt verliezen. Door een woning te vereeuwigen, blijft die plek digitaal bewaard, voor jezelf, voor later, voor wie je dierbaar is. En ook jouw gemoedsrust speelt daarin een grote rol: het idee dat die woning nog altijd virtueel bestaat en dat je er later opnieuw naar kan terugkeren, geeft veel mensen rust. Vaak besef je pas achteraf hoeveel een plek voor je betekende. Net daarom kiezen veel mensen ervoor om ze nu al vast te leggen, zodat ze later geen spijt hoeven te hebben dat ze die kans hebben laten voorbijgaan.',
    ],
    [
        'question' => 'Is een woning via Memory Lane vereeuwigen hetzelfde als foto\'s nemen?',
        'answer'   => 'Neen, toch niet. Foto\'s tonen losse beelden, terwijl een virtuele tour de woning als geheel bewaart met 360° beelden. Je kan later opnieuw door de ruimtes wandelen en de plek veel realistischer beleven in 360°. Dat maakt de ervaring veel realistischer en completer dan een gewone fotoreeks.',
    ],
    [
        'question' => 'Voor wie is een woning vereeuwigen interessant?',
        'answer'   => 'Voor iedereen die een betekenisvolle woning wil bewaren. Bijvoorbeeld wanneer het ouderlijk huis verkocht wordt, wanneer je zelf verhuist of wanneer je een plek met emotionele waarde niet wilt vergeten.',
    ],
    [
        'question' => 'Kan ik mijn woning later opnieuw bekijken?',
        'answer'   => 'Ja. Dat is net het idee van Memory Lane: een woning digitaal bewaren zodat je er later altijd opnieuw naar kan terugkeren, hoe vaak en wanneer je maar wilt.',
    ],
    [
        'question' => 'Kan ik mijn Memory Lane ook in VR bekijken?',
        'answer'   => 'Ja, met een compatibele VR-bril kan de beleving nog realistischer worden. Compatibele brillen: Meta Quest 3, Meta Quest 2, Meta Quest, Meta Quest Pro (Oculus), of een Apple Vision Pro.',
    ],
];
?>

<section class="bg-gradient-tb border-t border-white/30 overflow-hidden">
    <div class="w-[90vw] mx-auto py-16 md:py-24">

        <div class="section-header text-center mb-10 md:mb-[2.5vw]">
            <h2 class="text-[8vw] md:text-[3.229vw] text-primary font-primary leading-[1.3] mb-[1.042vw] text-center">
                Veelgestelde vragen
            </h2>
            <p class="text-primary text-[4vw] md:text-[1.25vw] text-center w-full md:w-[55%] mx-auto leading-[1.8]">
                Veelgestelde vragen over een woning vereeuwigen
            </p>
        </div>

        <div class="max-w-[90%] md:max-w-[60vw] mx-auto animate-fade-up" x-data="{ activeItem: null }">
            <?php foreach ($faqItems as $index => $item) : ?>
                <div class="border-b border-primary/20">
                    <button
                        class="w-full flex justify-between items-center py-5 md:py-[1.2vw] text-left cursor-pointer gap-4"
                        @click="activeItem = activeItem === <?= $index ?> ? null : <?= $index ?>">
                        <span class="font-primary text-primary text-[5vw] md:text-[1.5vw] leading-[1.4] pr-4">
                            <?= esc_html($item['question']) ?>
                        </span>
                        <svg
                            class="w-5 h-5 md:w-[1.2vw] md:h-[1.2vw] text-primary flex-shrink-0 transition-transform duration-300"
                            :class="{ 'rotate-180': activeItem === <?= $index ?> }"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div
                        x-show="activeItem === <?= $index ?>"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-1"
                        class="pb-5 md:pb-[1.2vw] text-primary text-[4vw] md:text-[1.2vw] leading-[1.8]">
                        <?= esc_html($item['answer']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
</section>
