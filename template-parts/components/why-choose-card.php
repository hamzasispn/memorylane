<?php
$itemData = $args;
?>

<div class="bg-white flex flex-col items-center justify-between text-center py-10 md:py-[3vw] px-6 md:px-[1.5vw] border border-white/60 rounded-[2.604vw] relative">
    <h3 class="text-[6vw] md:text-[1.8vw] text-primary font-primary mb-6 md:mb-[1.5vw] leading-[1.3]">
        <?= esc_html($itemData['title']) ?>
    </h3>
    <img src="<?= esc_url($itemData['icon']) ?>" alt="<?= esc_attr($itemData['title']) ?>"
        class="!w-[20vw] md:!w-[5vw] h-auto mb-6 md:mb-[1.5vw] object-contain">
    <p class="text-primary text-[4vw] md:text-[1.2vw] text-center leading-[1.8]">
        <?= esc_html($itemData['description']) ?>
    </p>
</div>
