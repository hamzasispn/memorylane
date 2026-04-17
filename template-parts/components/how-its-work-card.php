<?php
$itemData = $args;
?>

<div class="how-its-work-card flex flex-col gap-4 justify-center mb-8 md:mb-[2vw]">
    <div class="flex items-center gap-4 w-full md:w-[40%] mx-auto mb-2">
        <span class="inline-flex rounded-full w-14 h-14 md:w-[3.5vw] md:h-[3.5vw] flex-shrink-0 items-center justify-center text-white bg-primary font-primary text-2xl md:text-[1.8vw] leading-none">
            <?= esc_html($itemData['step']) ?>
        </span>
        <h3 class="text-[6vw] md:text-[1.8vw] text-primary font-primary leading-[1.3]">
            <?= esc_html($itemData['title']) ?>
        </h3>
    </div>
    <div class="bg-white rounded-full p-6 md:p-[1.5vw] w-full md:w-[60%] mx-auto">
        <p class="text-primary text-[4vw] md:text-[1.2vw] text-center leading-[1.8] font-medium">
            <?= esc_html($itemData['description']) ?>
        </p>
    </div>
</div>
