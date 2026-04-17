<section class="bg-gradient-bt border-t border-white/30 overflow-hidden">
    <div class="w-[90vw] mx-auto py-16 md:py-24">

        <div class="section-header text-center mb-10 md:mb-[2.5vw]">
            <h2 class="text-[8vw] md:text-[3.229vw] text-primary font-primary leading-[1.3] mb-[1.042vw] text-center">
                Wat betekent archivering?
            </h2>
            <p class="text-primary text-[4vw] md:text-[1.25vw] text-center w-full md:w-[60%] mx-auto leading-[1.8]">
                Je kiest niet tussen "voor altijd online" of "helemaal weg". Er bestaat ook een tussenstap.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-[3vw] items-start animate-stagger-parent">

            <!-- Left: archiving explained -->
            <div class="animate-stagger-child flex flex-col gap-5 md:gap-[1.2vw]">
                <div class="bg-white/70 rounded-[2vw] p-7 md:p-[1.8vw]">
                    <h3 class="font-primary text-primary text-[6vw] md:text-[1.8vw] leading-[1.3] mb-3 md:mb-[0.6vw]">
                        Tour gearchiveerd
                    </h3>
                    <p class="text-primary text-[4vw] md:text-[1.2vw] leading-[1.8] mb-3">
                        Wanneer je beslist om jouw abonnement stop te zetten, wordt jouw tour niet langer actief online gehouden. De tour wordt gearchiveerd.
                    </p>
                    <p class="text-primary text-[4vw] md:text-[1.2vw] leading-[1.8]">
                        <strong>Archivering betekent:</strong> de tour is niet meer toegankelijk in jouw klantenzone, maar ook niet volledig verloren. Ze wordt bewaard in een niet-actieve toestand, zodat ze later opnieuw geactiveerd kan worden.
                    </p>
                </div>

                <div class="bg-white/70 rounded-[2vw] p-7 md:p-[1.8vw]">
                    <h3 class="font-primary text-primary text-[6vw] md:text-[1.8vw] leading-[1.3] mb-3 md:mb-[0.6vw]">
                        Gemoedsrust
                    </h3>
                    <p class="text-primary text-[4vw] md:text-[1.2vw] leading-[1.8]">
                        Voor veel mensen geeft dat extra gemoedsrust. Omdat ze weten dat de woning nog altijd bestaat op hun Memory Lane, ook al houden ze ze op dat moment niet actief online.
                    </p>
                </div>
            </div>

            <!-- Right: the 3 states -->
            <div class="animate-stagger-child flex flex-col gap-4 md:gap-[1vw]">
                <h3 class="font-primary text-primary text-[6vw] md:text-[1.8vw] leading-[1.3] mb-2">
                    De drie statussen van jouw tour
                </h3>

                <?php
                $states = [
                    [
                        'svg'   => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/>',
                        'color' => '#16a34a',
                        'label' => 'Online &amp; actief',
                        'desc'  => 'Jouw tour is zichtbaar en toegankelijk via de klantenzone. Maandelijks abonnement actief.',
                    ],
                    [
                        'svg'   => '<path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>',
                        'color' => '#152751',
                        'label' => 'Gearchiveerd',
                        'desc'  => 'Tour is niet toegankelijk maar blijft veilig bewaard. Abonnementskosten gestopt. Heractiveerbaar tegen eenmalige kost.',
                    ],
                    [
                        'svg'   => '<path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/>',
                        'color' => '#7c3aed',
                        'label' => 'Hergeactiveerd',
                        'desc'  => 'Via een eenmalige activatiekost wordt de tour manueel opnieuw online gezet. Dit kan tot 8 uur duren.',
                    ],
                ];
                foreach ($states as $state) : ?>
                    <div class="bg-white/80 rounded-[1.5vw] p-5 md:p-[1.2vw] flex items-start gap-4">
                        <span class="flex-shrink-0 mt-0.5">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                 stroke="<?= esc_attr($state['color']) ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                 class="w-7 h-7 md:w-[1.8vw] md:h-[1.8vw]">
                                <?= $state['svg'] ?>
                            </svg>
                        </span>
                        <div>
                            <strong class="block font-primary text-primary text-[4.5vw] md:text-[1.3vw] mb-1"><?= $state['label'] ?></strong>
                            <p class="text-primary text-[3.5vw] md:text-[1.1vw] leading-[1.6]"><?= esc_html($state['desc']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>

    </div>
</section>
