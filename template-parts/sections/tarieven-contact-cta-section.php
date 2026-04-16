<?php
$sectionTitle = [
    'title'       => 'Wil je meer weten over de tarieven of voorwaarden?',
    'description' => 'We vinden het belangrijk dat je met een gerust gevoel kiest voor een Memory Lane. Heb je vragen over de opnamekost, het abonnement, archivering of heractivatie? Of wil je graag een offerte opvragen?',
];
$contactInfo = [
    [
        'icon' => get_template_directory_uri() . '/assets/Message.png',
        'text' => 'info@memorylane.be',
    ],
    [
        'icon' => get_template_directory_uri() . '/assets/Call.png',
        'text' => '016 60 60 60',
    ],
    [
        'icon' => get_template_directory_uri() . '/assets/location-icon.png',
        'text' => 'Heel België',
    ],
];
?>

<section class="contact-us border-t border-white overflow-hidden bg-cover bg-center"
    style="background-image: url('<?= get_template_directory_uri() ?>/assets/contact-section-bg.png');">
    <div class="container mx-auto py-20">

        <div class="section-header text-center mb-10">
            <h2 class="text-[8vw] md:text-[3.229vw] text-primary font-primary leading-[1.3] mb-[1.042vw] text-center">
                <?= esc_html($sectionTitle['title']) ?>
            </h2>
            <p class="text-primary text-[4vw] md:text-[1.25vw] text-center w-full md:w-[60%] mx-auto leading-[1.8]">
                <?= esc_html($sectionTitle['description']) ?>
            </p>
        </div>

        <div class="flex flex-col items-center justify-center mb-8">
            <a href="<?php echo esc_url(home_url('/boek')); ?>" class="btn-primary">Boek een opname</a>
        </div>

        <div class="grid md:grid-cols-2 grid-cols-1 gap-8">

            <!-- Left: Contact Info -->
            <div class="flex justify-center">
                <ul class="flex flex-col gap-7 list-none p-0 m-0">
                    <?php foreach ($contactInfo as $item) : ?>
                        <li class="flex items-center gap-4">
                            <img src="<?= esc_url($item['icon']) ?>" alt="" class="!w-10 !h-10 object-contain">
                            <span class="text-primary text-xl font-medium"><?= esc_html($item['text']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Right: Contact Form -->
            <div class="bg-white/60 backdrop-blur-md rounded-2xl p-9 shadow-lg">
                <h3 class="text-primary font-primary text-xl font-bold text-center mb-6">
                    Een vraag of offerte aanvragen?
                </h3>
                <div class="flex flex-col gap-3">
                    <input type="text" name="naam" placeholder="Naam"
                        class="w-full px-4 py-3 rounded-xl border border-purple-100 bg-white/70 text-gray-500 text-sm focus:outline-none focus:border-primary placeholder:text-gray-400">

                    <input type="email" name="email" placeholder="E-mailadres"
                        class="w-full px-4 py-3 rounded-xl border border-purple-100 bg-white/70 text-gray-500 text-sm focus:outline-none focus:border-primary placeholder:text-gray-400">

                    <input type="tel" name="telefoon" placeholder="Telefoonnummer"
                        class="w-full px-4 py-3 rounded-xl border border-purple-100 bg-white/70 text-gray-500 text-sm focus:outline-none focus:border-primary placeholder:text-gray-400">

                    <textarea name="bericht" rows="4" placeholder="Jouw vraag of opmerking"
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
