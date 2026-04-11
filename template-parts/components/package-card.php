<?php
$itemData = $args;
?>

<div class="bg-white flex flex-col items-center justify-between text-center py-12 px-6 border rounded-[50px] relative">
    <h3 class="text-5xl text-primary font-primary mb-8"><?= $itemData['title'] ?></h3>
    <img src="<?= $itemData['icon'] ?>" alt="<?= $itemData['title'] ?>" class="w-32 h-auto mb-8 object-contain">
    <?php if (isset($itemData['description'])): ?>
        <p class="text-primary text-2xl text-center leading-[2]">
            <?= $itemData['description'] ?>
        </p>
    <?php endif; ?>
    <?php if (isset($itemData['features'])): ?>
            <ul class="text-primary text-2xl leading-[2]  list-none w-full">
                <?php foreach ($itemData['features'] as $feature): ?>
                    <li class="flex items-center text-left">
                        <span class="w-2 h-2 bg-primary rounded-full mr-2 flex-1/12"></span>
                        <span class="flex-11/12"><?= $feature ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
    <?php endif; ?>
    <p class="text-primary text-3xl mt-8 font-bold"><?= $itemData['price'] ?></p>
    <?php if (isset($itemData['extra'])): ?>
            <p class="text-primary font-bold text-lg absolute bottom-4 right-4"><?= $itemData['extra'] ?></p>
    <?php endif; ?>
</div>