<?php
$faqItems = [
    [
        'question' => 'Wat zit inbegrepen in de opnamekost?',
        'answer'   => 'De opnamekost omvat de 3D-scan van de woning en het perceel (tot 300m² vloeroppervlakte en 7a perceelsoppervlakte), de verwerking tot een virtuele tour, toegang tot de klantenzone en het eerste jaar online beschikbaarheid.',
    ],
    [
        'question' => 'Wat gebeurt er na het eerste jaar?',
        'answer'   => 'Na het eerste jaar kies je zelf of je jouw woning op Memory Lane online actief wilt houden via een maandelijks abonnement of om jouw woning te archiveren met de optie te heractiveren op elk gewenst moment.',
    ],
    [
        'question' => 'Ben ik verplicht om het abonnement te verlengen?',
        'answer'   => 'Nee, na het eerste jaar beslis je zelf of je jouw woning op Memory Lane actief online wilt houden. Je kan op elk moment stoppen met het abonnement.',
    ],
    [
        'question' => 'Wat gebeurt er als ik stop met het abonnement?',
        'answer'   => 'Dan is jouw woning op Memory Lane niet langer actief toegankelijk in jouw klantenzone en wordt deze gearchiveerd. Jouw woning blijft echter bewaard en kan later opnieuw geactiveerd worden mits een eenmalige activatiekost. Daarna betaal je gewoon de maandelijkse abonnementskost.',
    ],
    [
        'question' => 'Betekent archivering dat mijn woning weg is?',
        'answer'   => 'Nee. Bij archivering blijft jouw woning op Memory Lane bewaard, maar is ze niet actief toegankelijk. Ze wordt veilig opgeslagen en kan op elk moment opnieuw geactiveerd worden via een eenmalige activatiekost.',
    ],
    [
        'question' => 'Hoe lang duurt een heractivatie?',
        'answer'   => 'Een heractivatie kan tot 8 uur duren. Ze wordt manueel uitgevoerd door één van onze collega\'s. Zodra jouw woning op Memory Lane opnieuw online staat, krijg je een melding in jouw klantenzone.',
    ],
    [
        'question' => 'Waarom kost het geld om de tour online te houden?',
        'answer'   => 'Een virtuele tour online beschikbaar houden brengt kosten met zich mee, zoals opslag, hosting en het actief beschikbaar houden van de tour. De prijsstructuur bestaat daarom uit twee delen: een eenmalige kost voor de opname en creatie, en een doorlopende kost wanneer je de tour actief online wilt houden.',
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
                Alles over tarieven, abonnementen en archivering.
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
