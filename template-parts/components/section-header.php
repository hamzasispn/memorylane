<?php
    $headerData = $args;
    $headerTitle = $headerData['title'] ?? '';
    $headerSubtitle = $headerData['subtitle'] ?? '';
    $headerDesc = $headerData['description'] ?? '';
?>

<div class="text-center mb-10">
    <h2 class="text-[62px] text-primary font-primary leading-[1.3] mb-3 text-center"><?= $headerTitle ?></h2>
    <h4 class="text-[28px] text-white font-semibold mb-3 text-center"><?= $headerSubtitle ?></h4>
    <p class="text-white text-[24px] text-center w-[60%] mx-auto"><?= $headerDesc ?></p>
</div>