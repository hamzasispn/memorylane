<?php
$itemData = $args;
?>

<div class="bg-white flex flex-col items-center justify-between text-center py-[4vw] px-[1.563vw] border rounded-[2.604vw] relative">
    <h3 class="text-[2.5vw] text-primary font-primary mb-8"><?= $itemData['title'] ?></h3>
    <img src="<?= $itemData['icon'] ?>" alt="<?= $itemData['title'] ?>" class="w-[4vw] h-auto mb-[1.563vw] object-contain">
    <?php if (isset($itemData['description'])): ?>
        <p class="text-primary text-[1.30vw] text-left leading-[1.5]">
            <?= $itemData['description'] ?>
        </p>
    <?php endif; ?>
    <?php if (isset($itemData['features'])): ?>
            <ul class="text-primary text-[1.30vw] leading-[1.5]  list-none w-full">
                <?php foreach ($itemData['features'] as $feature): ?>
                    <li class="flex items-center text-left">
                        <img src="<?= get_template_directory_uri() ?>/assets/check-icon.png" alt="Check" class="mr-2 w-[1.25vw] h-[1.25vw] flex-1/12">
                        <span class="flex-11/12"><?= $feature ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
    <?php endif; ?>
    <p class="text-primary text-[1.25vw] mt-[2.63vw] font-bold"><?= $itemData['price'] ?></p>
    <?php if (isset($itemData['extra'])): ?>
            <p class="text-primary font-bold text-[1.25vw] absolute bottom-4 right-4"><?= $itemData['extra'] ?></p>
    <?php endif; ?>
</div>