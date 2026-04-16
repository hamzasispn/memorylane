<?php
$itemData = $args;
?>


<div class="how-its-work-card flex flex-col gap-4 justify-center mb-10">
    <div class="flex items-center gap-4 w-[32%] mx-auto mb-2">
        <span class="text-4xl inline-block rounded-full w-[70px] h-[70px] flex items-center justify-center text-white bg-primary">
            <?= $itemData['step'] ?>
        </span>
        <h3 class="text-5xl text-primary font-primary"><?= $itemData['title'] ?></h3>
    </div>
    <div class="bg-white rounded-full p-6 w-[50%] mx-auto">
        <p class="text-primary text-2xl text-center leading-[1.4] font-medium"><?= $itemData['description'] ?></p>
    </div>
</div>