<?php 
$sectionTitle = [
    'title' => 'Klaar om jouw huis aan Memory Lane toe te voegen?',
];
$contactInfo = [
    [
        'icon' => get_template_directory_uri() . '/assets/Message.png',
        'text' => 'info@memorylane.be'
    ],
    [
        'icon' => get_template_directory_uri() . '/assets/Call.png',
        'text' => '016 60 60 60'
    ],
    [
        'icon' => get_template_directory_uri() . '/assets/location-icon.png',
        'text' => 'Heel België',
    ]
];
?>

<section class="contact-us border-t border-white overflow-hidden bg-cover bg-center" style="background-image: url('<?= get_template_directory_uri() ?>/assets/contact-section-bg.png');">
    <div class="container mx-auto py-20">
        <?= get_template_part('template-parts/components/section-header', null, $sectionTitle) ?>

        <div class="flex flex-col items-center justify-center">
            <a href="/" class="btn-primary mb-6">Boek een opname</a>
        </div>

        <div class="grid md:grid-cols-2 grid-cols-1 gap-8">

            <!-- Left: Contact Info -->
            <div class="flex justify-center">
                <ul class="flex flex-col gap-7 list-none p-0 m-0">
                    <?php foreach ($contactInfo as $item) : ?>
                        <li class="flex items-center gap-4">
                            <img 
                                src="<?= esc_url($item['icon']) ?>" 
                                alt="" 
                                class="w-10 h-10 object-contain"
                            />
                            <span class="text-primary text-xl font-medium"><?= esc_html($item['text']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Right: Contact Form -->
            <div class="bg-white/60 backdrop-blur-md rounded-2xl p-9 shadow-lg">
                <h2 class="text-primary font-primary text-xl font-bold text-center mb-6">
                    Een vraag voor ons? Stuur ons een berichtje!
                </h2>
                <div class="flex flex-col gap-3">
                    <input type="text" name="naam" placeholder="Naam"
                        class="w-full px-4 py-3 rounded-xl border border-purple-100 bg-white/70 text-gray-500 text-sm focus:outline-none focus:border-primary placeholder:text-gray-400" />

                    <input type="email" name="email" placeholder="E-mailadres"
                        class="w-full px-4 py-3 rounded-xl border border-purple-100 bg-white/70 text-gray-500 text-sm focus:outline-none focus:border-primary placeholder:text-gray-400" />

                    <input type="tel" name="telefoon" placeholder="Telefoonnummer"
                        class="w-full px-4 py-3 rounded-xl border border-purple-100 bg-white/70 text-gray-500 text-sm focus:outline-none focus:border-primary placeholder:text-gray-400" />

                    <input type="text" name="adres" placeholder="Adres van de woning"
                        class="w-full px-4 py-3 rounded-xl border border-purple-100 bg-white/70 text-gray-500 text-sm focus:outline-none focus:border-primary placeholder:text-gray-400" />

                    <textarea name="bericht" rows="4" placeholder="Bericht / vraag"
                        class="w-full px-4 py-3 rounded-xl border border-purple-100 bg-white/70 text-gray-500 text-sm focus:outline-none focus:border-primary placeholder:text-gray-400 resize-none"></textarea>

                    <button type="submit"
                        class="w-full py-4 mt-1 bg-primary text-white font-primary font-bold text-lg rounded-full hover:opacity-90 transition-opacity">
                        Verzend bericht
                    </button>
                </div>
            </div>

        </div>
    </div>
</section>