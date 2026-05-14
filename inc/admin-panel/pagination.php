<?php
/**
 * Memory Lane — admin panel pagination helper.
 */
defined( 'ABSPATH' ) || exit;

/**
 * Parse current page from $_GET['p'] with sane bounds.
 */
function ml_ap_current_page() {
    return max( 1, (int) ( $_GET['p'] ?? 1 ) );
}

function ml_ap_per_page( $default = 25 ) {
    $v = (int) ( $_GET['pp'] ?? $default );
    return max( 5, min( 200, $v ) );
}

/**
 * Render Prev / Page x of y / Next under a table. $total is the row count.
 */
function ml_ap_render_pagination( $total, $current_page, $per_page, $base_url ) {
    $pages = max( 1, (int) ceil( $total / max( 1, $per_page ) ) );
    if ( $pages <= 1 ) return;
    ?>
    <div class="mla-pagination">
        <?php if ( $current_page > 1 ) : ?>
            <a class="mla-btn mla-btn--ghost" href="<?php echo esc_url( add_query_arg( 'p', $current_page - 1, $base_url ) ); ?>">← <?php echo esc_html( ml_t( 'common.prev', 'Previous' ) ); ?></a>
        <?php else : ?>
            <span class="mla-btn mla-btn--ghost is-disabled">← <?php echo esc_html( ml_t( 'common.prev', 'Previous' ) ); ?></span>
        <?php endif; ?>
        <span class="mla-muted"><?php echo esc_html( sprintf( ml_t( 'common.page_x_of_y', 'Page %1$d of %2$d' ), $current_page, $pages ) ); ?></span>
        <?php if ( $current_page < $pages ) : ?>
            <a class="mla-btn mla-btn--ghost" href="<?php echo esc_url( add_query_arg( 'p', $current_page + 1, $base_url ) ); ?>"><?php echo esc_html( ml_t( 'common.next', 'Next' ) ); ?> →</a>
        <?php else : ?>
            <span class="mla-btn mla-btn--ghost is-disabled"><?php echo esc_html( ml_t( 'common.next', 'Next' ) ); ?> →</span>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Helper to compute LIMIT/OFFSET for a query.
 */
function ml_ap_limit_offset( $per_page, $page ) {
    return array(
        (int) $per_page,
        (int) ( ( max( 1, $page ) - 1 ) * $per_page ),
    );
}
