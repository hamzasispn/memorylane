<?php defined( 'ABSPATH' ) || exit;
$id = isset( $id ) ? trim( (string) $id ) : '';

// Detail / edit / new.
if ( $id === 'new' || ctype_digit( $id ) ) {
    $is_new   = $id === 'new';
    $tour     = $is_new ? null : get_post( (int) $id );
    if ( ! $is_new && ( ! $tour || $tour->post_type !== ML_CPT_TOUR ) ) {
        echo '<div class="mla-card"><p class="mla-muted">Tour not found.</p></div>';
        return;
    }
    $title    = $tour ? $tour->post_title : '';
    $user_id  = $tour ? (int) get_post_meta( $tour->ID, ML_META_TOUR_USER, true ) : (int) ( $_GET['user_id'] ?? 0 );
    $embed    = $tour ? (string) get_post_meta( $tour->ID, ML_META_TOUR_EMBED, true ) : '';
    $address  = $tour ? (string) get_post_meta( $tour->ID, ML_META_TOUR_ADDRESS, true ) : '';
    $status   = $tour ? (string) get_post_meta( $tour->ID, ML_META_TOUR_STATUS, true ) : ML_TOUR_STATUS_ACTIVE;
    $customers = get_users( array( 'role__in' => array( ML_ROLE_CUSTOMER, 'administrator' ), 'fields' => array( 'ID', 'user_email', 'display_name' ) ) );
?>
    <a class="mla-btn mla-btn--ghost" href="<?php echo esc_url( home_url( '/admin/tours' ) ); ?>" style="margin-bottom:12px;display:inline-block;">← All tours</a>

    <form class="mla-card" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <?php wp_nonce_field( 'ml_ap_tour_save' ); ?>
        <input type="hidden" name="action" value="ml_ap_tour_save">
        <?php if ( ! $is_new ) : ?>
            <input type="hidden" name="id" value="<?php echo (int) $tour->ID; ?>">
        <?php endif; ?>

        <h2><?php echo $is_new ? 'Add tour' : 'Edit tour'; ?></h2>

        <div class="mla-form-row">
            <label for="t-title">Title</label>
            <div><input id="t-title" type="text" name="title" value="<?php echo esc_attr( $title ); ?>" required>
                <div class="help">Usually the property address.</div>
            </div>
        </div>

        <div class="mla-form-row">
            <label for="t-user">Customer</label>
            <div>
                <select id="t-user" name="user_id" required>
                    <option value="">— Select customer —</option>
                    <?php foreach ( $customers as $c ) : ?>
                        <option value="<?php echo (int) $c->ID; ?>" <?php selected( $user_id, $c->ID ); ?>>
                            <?php echo esc_html( $c->display_name ? "{$c->display_name} ({$c->user_email})" : $c->user_email ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="mla-form-row">
            <label for="t-address">Address</label>
            <div><input id="t-address" type="text" name="address" value="<?php echo esc_attr( $address ); ?>"></div>
        </div>

        <div class="mla-form-row">
            <label for="t-embed">Matterport embed</label>
            <div><textarea id="t-embed" name="embed_code" rows="5" placeholder='<iframe src="https://my.matterport.com/show/?m=…" …></iframe>'><?php echo esc_textarea( $embed ); ?></textarea>
                <div class="help">Paste the &lt;iframe&gt; from Matterport. Only domains on the allowlist (Settings → General) are rendered.</div>
            </div>
        </div>

        <div class="mla-form-row">
            <label for="t-status">Status</label>
            <div>
                <select id="t-status" name="status">
                    <option value="<?php echo esc_attr( ML_TOUR_STATUS_ACTIVE ); ?>"            <?php selected( $status, ML_TOUR_STATUS_ACTIVE ); ?>>Active</option>
                    <option value="<?php echo esc_attr( ML_TOUR_STATUS_PENDING_REACTIVATION ); ?>" <?php selected( $status, ML_TOUR_STATUS_PENDING_REACTIVATION ); ?>>Reactivating (≤ 8h)</option>
                    <option value="<?php echo esc_attr( ML_TOUR_STATUS_ARCHIVED ); ?>"         <?php selected( $status, ML_TOUR_STATUS_ARCHIVED ); ?>>Archived</option>
                    <option value="<?php echo esc_attr( ML_TOUR_STATUS_PENDING_ARCHIVE ); ?>"  <?php selected( $status, ML_TOUR_STATUS_PENDING_ARCHIVE ); ?>>Pending archive</option>
                </select>
            </div>
        </div>

        <div style="margin-top:16px;display:flex;gap:8px;">
            <button class="mla-btn mla-btn--primary" type="submit">Save tour</button>
            <a class="mla-btn mla-btn--ghost" href="<?php echo esc_url( home_url( '/admin/tours' ) ); ?>">Cancel</a>
        </div>
    </form>

    <?php if ( ! $is_new ) : ?>
        <form class="mla-card" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Permanently delete this tour?');" style="border-color:#FCA5A5;">
            <?php wp_nonce_field( 'ml_ap_tour_delete' ); ?>
            <input type="hidden" name="action" value="ml_ap_tour_delete">
            <input type="hidden" name="id" value="<?php echo (int) $tour->ID; ?>">
            <h2 style="color:var(--mla-danger);">Danger zone</h2>
            <button class="mla-btn mla-btn--danger" type="submit">Delete tour</button>
        </form>
    <?php endif; ?>
<?php
    return;
}

// List view.
$per_page = ml_ap_per_page( 25 );
$page     = ml_ap_current_page();
$offset   = ( $page - 1 ) * $per_page;

$total = (int) wp_count_posts( ML_CPT_TOUR )->publish;

$tours = get_posts( array(
    'post_type'   => ML_CPT_TOUR,
    'numberposts' => $per_page,
    'offset'      => $offset,
    'orderby'     => 'date',
    'order'       => 'DESC',
) );
?>

<div class="mla-toolbar">
    <a class="mla-btn mla-btn--primary" href="<?php echo esc_url( home_url( '/admin/tours/new' ) ); ?>">+ Add tour</a>
    <span class="mla-muted" style="margin-left:auto;"><?php echo (int) $total; ?> tours</span>
</div>

<div class="mla-card" style="padding:0;">
    <table class="mla-table">
        <thead><tr><th>Title</th><th>Customer</th><th>Status</th><th>Updated</th><th></th></tr></thead>
        <tbody>
        <?php foreach ( $tours as $t ) :
            $u_id   = (int) get_post_meta( $t->ID, ML_META_TOUR_USER,   true );
            $status = (string) get_post_meta( $t->ID, ML_META_TOUR_STATUS, true );
            $u      = $u_id ? get_userdata( $u_id ) : null;
        ?>
            <tr>
                <td><a href="<?php echo esc_url( home_url( '/admin/tours/' . $t->ID ) ); ?>"><?php echo esc_html( $t->post_title ); ?></a></td>
                <td><?php echo $u ? esc_html( $u->user_email ) : '<span class="mla-muted">—</span>'; ?></td>
                <td><span class="mla-pill mla-pill--<?php echo $status === ML_TOUR_STATUS_ACTIVE ? 'success' : 'neutral'; ?>"><?php echo esc_html( $status ); ?></span></td>
                <td><?php echo esc_html( ml_format_date( $t->post_modified ) ); ?></td>
                <td style="text-align:right;"><a class="mla-btn mla-btn--ghost" href="<?php echo esc_url( home_url( '/admin/tours/' . $t->ID ) ); ?>">Edit →</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if ( empty( $tours ) ) : ?>
            <tr><td colspan="5" class="mla-muted" style="text-align:center;padding:32px;">No tours yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php ml_ap_render_pagination( $total, $page, $per_page, home_url( '/admin/tours' ) ); ?>
