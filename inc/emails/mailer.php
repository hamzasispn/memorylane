<?php
/**
 * Memory Lane — email sender + template loader + retry.
 */
defined( 'ABSPATH' ) || exit;

/**
 * Send an email via WP's wp_mail with our template system.
 * Always inserts a row in wp_ml_email_log (queued → sent / failed).
 */
function ml_mail_send( $to, $template, $vars = array(), $user_id = null ) {
    global $wpdb;
    $tbl = ml_table( 'email_log' );

    $now = current_time( 'mysql', true );
    $wpdb->insert( $tbl, array(
        'user_id'    => $user_id ? (int) $user_id : null,
        'template'   => substr( (string) $template, 0, 80 ),
        'to_email'   => $to,
        'subject'    => '',
        'status'     => 'queued',
        'created_at' => $now,
    ) );
    $row_id = $wpdb->insert_id;

    try {
        $lang = $user_id ? ( get_user_meta( $user_id, ML_META_LANG, true ) ?: 'nl' ) : ml_current_lang();
        if ( ! in_array( $lang, array( 'nl', 'en' ), true ) ) $lang = 'nl';

        list( $subject, $html ) = ml_render_email_template( $template, $vars, $lang );
        if ( ! $subject ) $subject = $template;

        $from = ml_email_from();
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . sprintf( '%s <%s>', $from['name'], $from['address'] ),
        );

        $sent = wp_mail( $to, $subject, $html, $headers );

        $wpdb->update( $tbl, array(
            'subject' => $subject,
            'status'  => $sent ? 'sent' : 'failed',
            'sent_at' => $sent ? current_time( 'mysql', true ) : null,
            'error_msg' => $sent ? null : 'wp_mail returned false',
        ), array( 'id' => $row_id ) );

        return $sent;
    } catch ( \Throwable $e ) {
        $wpdb->update( $tbl, array(
            'status' => 'failed',
            'error_msg' => substr( $e->getMessage(), 0, 1000 ),
        ), array( 'id' => $row_id ) );
        return false;
    }
}

/**
 * Render a template. Returns array( subject, html ).
 * Templates live in inc/emails/templates/{nl|en}/{template}.php and must $subject + echo body.
 */
function ml_render_email_template( $template, $vars, $lang ) {
    $template = preg_replace( '/[^a-z0-9_]/', '', $template );
    $file = ML_INC . "emails/templates/{$lang}/{$template}.php";
    if ( ! file_exists( $file ) ) {
        $file = ML_INC . "emails/templates/nl/{$template}.php";
    }
    if ( ! file_exists( $file ) ) {
        return array( '', '<p>' . esc_html( $template ) . '</p>' );
    }
    extract( $vars, EXTR_SKIP );
    $subject = '';
    ob_start();
    include $file;
    $body = ob_get_clean();
    return array( $subject, ml_email_wrap( $body, $lang ) );
}

/**
 * Wrap body content in shared header/footer HTML for consistent branding.
 */
function ml_email_wrap( $body, $lang ) {
    $brand = ml_t( 'common.brand' );
    return '<!DOCTYPE html><html lang="' . esc_attr( $lang ) . '"><head><meta charset="utf-8"></head>'
        . '<body style="margin:0;padding:0;background:#FAFAFA;font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif;color:#18181B;">'
        . '<table width="100%" cellpadding="0" cellspacing="0" style="background:#FAFAFA;padding:24px 0;"><tr><td align="center">'
        . '<table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border:1px solid #E4E4E7;border-radius:12px;overflow:hidden;">'
        . '<tr><td style="padding:24px 32px;border-bottom:1px solid #E4E4E7;font-weight:600;font-size:16px;">' . esc_html( $brand ) . '</td></tr>'
        . '<tr><td style="padding:32px;font-size:14px;line-height:1.6;">' . $body . '</td></tr>'
        . '<tr><td style="padding:16px 32px;border-top:1px solid #E4E4E7;font-size:12px;color:#71717A;">'
        . esc_html__( 'You received this email because you have an account at Memory Lane.', 'memorylane' )
        . '</td></tr></table>'
        . '</td></tr></table></body></html>';
}

/**
 * Retry a logged email by id.
 */
function ml_mail_retry( $log_id ) {
    global $wpdb;
    $tbl = ml_table( 'email_log' );
    $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tbl} WHERE id=%d", (int) $log_id ) );
    if ( ! $row ) return false;
    $wpdb->update( $tbl, array( 'retry_count' => $row->retry_count + 1 ), array( 'id' => $log_id ) );
    return ml_mail_send( $row->to_email, $row->template, array(), $row->user_id );
}

/**
 * Configure wp_mail From globally so plugins respect our settings.
 */
add_filter( 'wp_mail_from', function ( $email ) {
    $from = ml_email_from();
    return $from['address'] ?: $email;
} );
add_filter( 'wp_mail_from_name', function ( $name ) {
    $from = ml_email_from();
    return $from['name'] ?: $name;
} );
