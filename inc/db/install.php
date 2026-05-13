<?php
/**
 * Memory Lane — custom DB tables.
 * Idempotent via dbDelta. Called on theme activation + admin_init when version bumps.
 */
defined( 'ABSPATH' ) || exit;

function ml_db_install() {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset = $wpdb->get_charset_collate();
    $p = $wpdb->prefix;

    // Webhook events — idempotency log.
    $sql_webhook_events = "CREATE TABLE {$p}ml_webhook_events (
        id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        event_id     VARCHAR(191) NOT NULL,
        type         VARCHAR(120) NOT NULL,
        status       VARCHAR(40)  NOT NULL DEFAULT 'pending',
        retry_count  INT          NOT NULL DEFAULT 0,
        error_msg    TEXT         NULL,
        payload      LONGTEXT     NULL,
        received_at  DATETIME     NOT NULL,
        processed_at DATETIME     NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY uk_event_id (event_id),
        KEY idx_status (status),
        KEY idx_type (type)
    ) $charset;";

    // Subscriptions — local mirror of Stripe state.
    $sql_subscriptions = "CREATE TABLE {$p}ml_subscriptions (
        id                     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id                BIGINT UNSIGNED NOT NULL,
        stripe_customer_id     VARCHAR(120) NOT NULL,
        stripe_sub_id          VARCHAR(120) NOT NULL,
        stripe_schedule_id     VARCHAR(120) NULL,
        status                 VARCHAR(40)  NOT NULL,
        current_period_end     DATETIME     NULL,
        year_one_end_date      DATETIME     NULL,
        cancel_at_period_end   TINYINT(1)   NOT NULL DEFAULT 0,
        raw_json               LONGTEXT     NULL,
        created_at             DATETIME     NOT NULL,
        updated_at             DATETIME     NOT NULL,
        PRIMARY KEY (id),
        KEY idx_user (user_id),
        UNIQUE KEY uk_sub (stripe_sub_id),
        KEY idx_status (status)
    ) $charset;";

    // Availability slots.
    $sql_slots = "CREATE TABLE {$p}ml_availability_slots (
        id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        slot_start_datetime DATETIME    NOT NULL,
        slot_end_datetime   DATETIME    NOT NULL,
        capacity            INT         NOT NULL DEFAULT 1,
        booked_count        INT         NOT NULL DEFAULT 0,
        status              VARCHAR(40) NOT NULL DEFAULT 'open',
        notes               TEXT        NULL,
        created_by          BIGINT UNSIGNED NULL,
        created_at          DATETIME    NOT NULL,
        PRIMARY KEY (id),
        KEY idx_slot_start (slot_start_datetime),
        KEY idx_status (status)
    ) $charset;";

    // Bookings.
    $sql_bookings = "CREATE TABLE {$p}ml_bookings (
        id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id         BIGINT UNSIGNED NOT NULL,
        slot_id         BIGINT UNSIGNED NULL,
        service_type    VARCHAR(40)  NOT NULL DEFAULT 'initial_scan',
        status          VARCHAR(40)  NOT NULL DEFAULT 'requested',
        customer_notes  TEXT         NULL,
        admin_notes     TEXT         NULL,
        scheduled_for   DATETIME     NULL,
        created_at      DATETIME     NOT NULL,
        updated_at      DATETIME     NOT NULL,
        cancelled_at    DATETIME     NULL,
        completed_at    DATETIME     NULL,
        PRIMARY KEY (id),
        KEY idx_user (user_id),
        KEY idx_slot (slot_id),
        KEY idx_status (status)
    ) $charset;";

    // Email log.
    $sql_email_log = "CREATE TABLE {$p}ml_email_log (
        id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id      BIGINT UNSIGNED NULL,
        template     VARCHAR(80)  NOT NULL,
        to_email     VARCHAR(191) NOT NULL,
        subject      VARCHAR(191) NOT NULL,
        status       VARCHAR(40)  NOT NULL DEFAULT 'queued',
        retry_count  INT          NOT NULL DEFAULT 0,
        error_msg    TEXT         NULL,
        sent_at      DATETIME     NULL,
        created_at   DATETIME     NOT NULL,
        PRIMARY KEY (id),
        KEY idx_status (status),
        KEY idx_user (user_id),
        KEY idx_template (template)
    ) $charset;";

    // Admin action audit log.
    $sql_admin_actions = "CREATE TABLE {$p}ml_admin_actions_log (
        id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        admin_id       BIGINT UNSIGNED NOT NULL,
        target_user_id BIGINT UNSIGNED NULL,
        action         VARCHAR(120) NOT NULL,
        before_state   TEXT         NULL,
        after_state    TEXT         NULL,
        reason         TEXT         NULL,
        created_at     DATETIME     NOT NULL,
        PRIMARY KEY (id),
        KEY idx_target (target_user_id),
        KEY idx_action (action)
    ) $charset;";

    // Login rate-limit table (simple, separate from transients for visibility).
    $sql_login_attempts = "CREATE TABLE {$p}ml_login_attempts (
        id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        ip_hash      VARCHAR(64)  NOT NULL,
        username     VARCHAR(191) NULL,
        attempted_at DATETIME     NOT NULL,
        success      TINYINT(1)   NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        KEY idx_ip (ip_hash),
        KEY idx_when (attempted_at)
    ) $charset;";

    // Reactivation cycles — one row per customer-driven reactivation.
    // Unique stripe_checkout_session_id provides webhook idempotency.
    $sql_reactivations = "CREATE TABLE {$p}ml_reactivations (
        id                            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id                       BIGINT UNSIGNED NOT NULL,
        cycle_number                  INT          NOT NULL DEFAULT 1,
        plan_chosen                   VARCHAR(20)  NOT NULL DEFAULT 'monthly',
        activation_fee_paid_cents     INT          NOT NULL DEFAULT 0,
        activation_fee_currency       VARCHAR(3)   NOT NULL DEFAULT 'eur',
        stripe_checkout_session_id    VARCHAR(191) NOT NULL,
        stripe_payment_intent_id      VARCHAR(191) NULL,
        stripe_subscription_id        VARCHAR(191) NULL,
        requested_at                  DATETIME     NOT NULL,
        completed_at                  DATETIME     NULL,
        completed_by                  BIGINT UNSIGNED NULL,
        status                        VARCHAR(20)  NOT NULL DEFAULT 'pending',
        raw_json                      LONGTEXT     NULL,
        created_at                    DATETIME     NOT NULL,
        updated_at                    DATETIME     NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uk_session (stripe_checkout_session_id),
        KEY idx_user (user_id),
        KEY idx_status (status),
        KEY idx_requested (requested_at)
    ) $charset;";

    dbDelta( $sql_webhook_events );
    dbDelta( $sql_subscriptions );
    dbDelta( $sql_slots );
    dbDelta( $sql_bookings );
    dbDelta( $sql_email_log );
    dbDelta( $sql_admin_actions );
    dbDelta( $sql_login_attempts );
    dbDelta( $sql_reactivations );

    update_option( ML_OPT_DB_VERSION, ML_DB_VERSION, false );
}

/**
 * Helper accessors for table names.
 */
function ml_table( $short ) {
    global $wpdb;
    return $wpdb->prefix . 'ml_' . $short;
}
