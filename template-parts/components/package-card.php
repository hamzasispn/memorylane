<?php
$itemData = $args;
?>

<div class="bg-white flex flex-col items-center justify-between text-center py-10 md:py-[3vw] px-6 md:px-[1.563vw] border rounded-[2.604vw] relative">
    <h3 class="text-[6vw] md:text-[2vw] text-primary font-primary mb-1 leading-[1.3]">
        <?= esc_html($itemData['title']) ?>
    </h3>
    <?php if (!empty($itemData['subtitle'])): ?>
        <p class="text-primary italic text-[3.5vw] md:text-[1vw] mb-4 md:mb-[1vw]"><?= esc_html($itemData['subtitle']) ?></p>
    <?php else: ?>
        <div class="mb-5 md:mb-[1.5vw]"></div>
    <?php endif; ?>
    <img src="<?= esc_url($itemData['icon']) ?>" alt="<?= esc_attr($itemData['title']) ?>"
        class="package-icon !w-[24vw] md:!w-[10vw] h-auto mb-5 md:mb-[1.563vw] object-contain text-primary">

    <?php if (isset($itemData['description'])): ?>
        <p class="text-primary text-[4vw] md:text-[1.2vw] text-left leading-[1.8] mb-4">
            <?= esc_html($itemData['description']) ?>
        </p>
    <?php endif; ?>

    <?php if (isset($itemData['features'])): ?>
        <ul class="text-primary text-[4vw] md:text-[1.2vw] leading-[1.8] list-none w-full mb-4">
            <?php foreach ($itemData['features'] as $feature): ?>
                <li class="flex items-start text-left gap-2 mb-1">
                    <img src="<?= get_template_directory_uri() ?>/assets/check-icon.png" alt=""
                        class="!w-[4vw] md:!w-[1.1vw] !h-auto flex-shrink-0 mt-[0.4vw]">
                    <span><?= esc_html($feature) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <p class="text-primary text-[6vw] md:text-[1.6vw] mt-auto pt-4 border-t border-primary/10 w-full font-bold font-primary">
        <?= esc_html($itemData['price']) ?>
    </p>

    <?php if (isset($itemData['extra'])): ?>
        <p class="text-primary font-bold text-[4vw] md:text-[1.1vw] mt-2">
            <?= esc_html($itemData['extra']) ?>
        </p>
    <?php endif; ?>
</div>
