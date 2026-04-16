<?php
$headerData = $args;
$headerTitle = $headerData['title'] ?? '';
$headerSubtitle = $headerData['subtitle'] ?? '';
$headerDesc = $headerData['description'] ?? '';
?>

<div class="section-header text-center mb-10">
    <h2 class="text-[3.229vw] text-primary font-primary leading-[1.3] <?= $headerSubtitle ? '' : 'mb-[1.042vw]'; ?> text-center">
        <?= $headerTitle ?>
    </h2>
    <?php if ($headerSubtitle): ?>
        <h4 class="text-[1.771vw] text-white font-semibold -mt-[1.042vw] mb-[1.042vw] text-center">
            <?= $headerSubtitle ?>
        </h4>
    <?php endif; ?>
    <p class="text-white text-[1.25vw] text-center w-[60%] mx-auto">
        <?= $headerDesc ?>
    </p>
</div>