<?php
$faqItems = [
    [
        'question' => 'Hoe verloopt een opname van mijn woning?',
        'answer'   => 'We komen langs op het afgesproken moment en maken met professionele 3D-scantechnologie een digitale opname van de woning, inclusief delen van de omgeving errond, zoals de straat, de voortuin, zijtuin en achtertuin. De prijs is geldig voor opnames van het perceel tot max. 7a. Indien je graag je hele tuin wilt laten doen en deze is groter, dan laat je best eerst een offerte opmaken voor het hele perceel.',
    ],
    [
        'question' => 'Hoe lang duurt een opname?',
        'answer'   => 'Dat hangt af van de grootte en indeling van de woning, maar we voorzien hier altijd voldoende tijd voor. Voor een standaard woning moet je ongeveer 1u30 rekenen.',
    ],
    [
        'question' => 'Kan ik mijn Memory Lane op elk moment bekijken?',
        'answer'   => 'Ja, zolang jouw tour online actief blijft, kan je er altijd opnieuw naar terugkeren. Je kan ook kiezen om de tour te archiveren. Dan is deze niet meer te bezichtigen tot je een aanvraag indient om te activeren (betalend) en we de tour terug online hebben gezet (binnen de 8u na aanvraag).',
    ],
    [
        'question' => 'Kan ik mijn woning ook in VR bekijken?',
        'answer'   => 'Ja, in veel gevallen kan dat met een compatibele VR-bril. Bekijken in VR is momenteel enkel met deze brillen mogelijk: Meta Quest 3, Meta Quest 2, Meta Quest, Meta Quest Pro (Oculus), of een Apple Vision Pro.',
    ],
    [
        'question' => 'Wat is het verschil tussen een virtuele tour en gewone foto\'s?',
        'answer'   => 'Foto\'s tonen losse beelden. Een virtuele tour laat je opnieuw door de woning wandelen met een 360° view. Dit geeft een veel completere beleving, je ervaart de indeling, de sfeer en het gevoel van door de ruimtes te bewegen.',
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
                FAQ – Hoe werkt Memory Lane?
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
