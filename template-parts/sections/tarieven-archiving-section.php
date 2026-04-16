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
                    ['emoji' => '🟢', 'label' => 'Online & actief', 'desc' => 'Jouw tour is zichtbaar en toegankelijk via de klantenzone. Maandelijks abonnement actief.'],
                    ['emoji' => '📦', 'label' => 'Gearchiveerd', 'desc' => 'Tour is niet toegankelijk maar blijft veilig bewaard. Abonnementskosten gestopt. Heractiveerbaar tegen eenmalige kost.'],
                    ['emoji' => '🔄', 'label' => 'Hergeactiveerd', 'desc' => 'Via een eenmalige activatiekost wordt de tour manueel opnieuw online gezet. Dit kan tot 8 uur duren.'],
                ];
                foreach ($states as $state) : ?>
                    <div class="bg-white/80 rounded-[1.5vw] p-5 md:p-[1.2vw] flex items-start gap-4">
                        <span class="text-3xl md:text-[1.8vw] flex-shrink-0 leading-none mt-0.5"><?= $state['emoji'] ?></span>
                        <div>
                            <strong class="block font-primary text-primary text-[4.5vw] md:text-[1.3vw] mb-1"><?= esc_html($state['label']) ?></strong>
                            <p class="text-primary text-[3.5vw] md:text-[1.1vw] leading-[1.6]"><?= esc_html($state['desc']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>

    </div>
</section>
