<?php
$videoSectionHeaderData = [
    'title' => 'Bezoek deze voorbeeldwoning op Memory Lane',
];
?>

<section class="video-section border-t bg-gradient-lr overflow-hidden">
    <div class="container mx-auto py-20">
        <?= get_template_part('template-parts/components/section-header', null, $videoSectionHeaderData) ?>
        <div class="relative pt-[56.25%]">
            <iframe class="absolute top-0 left-0 w-full h-full rounded-lg"
                src="https://my.matterport.com/show/?m=JckUBqkdayB" frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen></iframe>
        </div>
    </div>
</section>