<?php
$headerData = $args;
$headerTitle    = $headerData['title']       ?? '';
$headerSubtitle = $headerData['subtitle']    ?? '';
$headerDesc     = $headerData['description'] ?? '';
?>

<div class="section-header text-center mb-10 md:mb-[2vw]">
    <h2 class="text-[8vw] md:text-[3.229vw] text-primary font-primary leading-[1.3] <?= $headerSubtitle ? '' : 'mb-[1.042vw]'; ?> text-center">
        <?= esc_html($headerTitle) ?>
    </h2>
    <?php if ($headerSubtitle): ?>
        <h4 class="text-[5vw] md:text-[1.771vw] text-primary font-semibold -mt-[0.5vw] mb-[1.042vw] text-center">
            <?= esc_html($headerSubtitle) ?>
        </h4>
    <?php endif; ?>
    <?php if ($headerDesc): ?>
        <p class="text-primary text-[4vw] md:text-[1.25vw] text-center w-full md:w-[60%] mx-auto leading-[1.8]">
            <?= esc_html($headerDesc) ?>
        </p>
    <?php endif; ?>
</div>
