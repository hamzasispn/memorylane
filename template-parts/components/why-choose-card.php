<?php
$itemData = $args;
?>

<div class="bg-white flex flex-col items-center justify-between text-center py-12 px-6 border rounded-[50px]">
    <h3 class="text-5xl text-primary font-primary mb-8"><?= $itemData['title'] ?></h3>
    <img src="<?= $itemData['icon'] ?>" alt="<?= $itemData['title'] ?>" class="w-[50%] h-auto mb-8 object-contain">
    <p class="text-primary text-2xl text-center leading-[2]"><?= $itemData['description'] ?></p>
</div>