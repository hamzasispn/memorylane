<?php
// Template Name: Front Page
get_header();
?>

<?= get_template_part('template-parts/sections/about-section') ?>
<?= get_template_part('template-parts/sections/why-choose-section') ?>
<?= get_template_part('template-parts/sections/how-its-work-section') ?>
<?= get_template_part('template-parts/sections/video-section') ?>
<?= get_template_part('template-parts/sections/packages-section') ?>

<?php get_footer();?>