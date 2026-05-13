<?php
/**
 * Shared <head> + opening body for auth/dashboard pages.
 * Sets up clean SaaS look — no public-site gradient header.
 *
 * Required vars (caller sets before include):
 *  - $page_title (string)
 */
defined( 'ABSPATH' ) || exit;
$lang = ml_current_lang();
?><!DOCTYPE html>
<html lang="<?php echo esc_attr( $lang ); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html( ( $page_title ?? '' ) . ' — ' . ml_t( 'common.brand' ) ); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="<?php echo esc_url( ML_URI . 'assets/src/css/dashboard.css' ); ?>?v=<?php echo esc_attr( ML_VERSION ); ?>">
    <?php wp_head(); ?>
</head>
<body class="ml-app">
<?php // language switcher (absolute-positioned, top-right) ?>
<div class="ml-lang" data-ml-lang>
    <button type="button" data-lang="nl" class="<?php echo $lang === 'nl' ? 'is-active' : ''; ?>">NL</button>
    <button type="button" data-lang="en" class="<?php echo $lang === 'en' ? 'is-active' : ''; ?>">EN</button>
</div>
<script>
(function(){
    document.querySelectorAll('[data-ml-lang] button').forEach(function(b){
        b.addEventListener('click', function(){
            fetch('<?php echo esc_url_raw( rest_url( 'memorylane/v1/lang' ) ); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>' },
                body: JSON.stringify({ lang: b.dataset.lang })
            }).then(function(){ window.location.reload(); });
        });
    });
})();
</script>
