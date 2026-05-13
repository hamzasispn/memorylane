<?php
/**
 * Memory Lane — bootstrap loader.
 * Loads all subsystems in correct order. Called from functions.php.
 */
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

// Stripe SDK.
if ( file_exists( __DIR__ . '/stripe-php-master/init.php' ) ) {
    require_once __DIR__ . '/stripe-php-master/init.php';
}

// i18n.
require_once __DIR__ . '/i18n/translator.php';

// Database.
require_once __DIR__ . '/db/install.php';

// Auth.
require_once __DIR__ . '/auth/routes.php';
require_once __DIR__ . '/auth/handlers.php';
require_once __DIR__ . '/auth/password-reset.php';

// Stripe.
require_once __DIR__ . '/stripe/client.php';
require_once __DIR__ . '/stripe/checkout.php';
require_once __DIR__ . '/stripe/webhooks.php';
require_once __DIR__ . '/stripe/schedule.php';
require_once __DIR__ . '/stripe/customer-portal.php';
require_once __DIR__ . '/stripe/plans.php';

foreach ( glob( __DIR__ . '/stripe/events/*.php' ) as $event_file ) {
    require_once $event_file;
}

// Subscriptions.
require_once __DIR__ . '/subscriptions/access-gate.php';
require_once __DIR__ . '/subscriptions/status.php';
require_once __DIR__ . '/subscriptions/sync.php';

// Tours (Phase 3).
require_once __DIR__ . '/tours/cpt.php';
require_once __DIR__ . '/tours/admin-meta.php';
require_once __DIR__ . '/tours/viewer.php';

// Booking (Phase 4).
require_once __DIR__ . '/booking/slots.php';
require_once __DIR__ . '/booking/bookings.php';
require_once __DIR__ . '/booking/admin.php';

// Admin (Phase 5).
require_once __DIR__ . '/admin/menu.php';
require_once __DIR__ . '/admin/settings.php';
require_once __DIR__ . '/admin/customers.php';
require_once __DIR__ . '/admin/subscriptions-page.php';
require_once __DIR__ . '/admin/notifications-log.php';
require_once __DIR__ . '/admin/webhooks-log.php';
require_once __DIR__ . '/admin/manual-actions.php';
require_once __DIR__ . '/admin/approve-access.php';

// Emails + Cron (Phase 6).
require_once __DIR__ . '/emails/mailer.php';
require_once __DIR__ . '/cron/schedule.php';
require_once __DIR__ . '/cron/check-expirations.php';
require_once __DIR__ . '/cron/send-renewal-warnings.php';
require_once __DIR__ . '/cron/retry-failed-webhooks.php';
require_once __DIR__ . '/cron/orphan-payment-check.php';
require_once __DIR__ . '/cron/booking-reminders.php';
require_once __DIR__ . '/cron/finalize-schedules.php';
require_once __DIR__ . '/cron/pending-approval-reminder.php';

/**
 * Theme activation hook — runs once when theme is switched in.
 */
add_action( 'after_switch_theme', function () {
    ml_install_role();
    ml_db_install();
    flush_rewrite_rules();
} );

/**
 * Custom role install (idempotent).
 */
function ml_install_role() {
    if ( ! get_role( ML_ROLE_CUSTOMER ) ) {
        add_role( ML_ROLE_CUSTOMER, __( 'Memory Lane Customer', 'memorylane' ), array(
            'read' => true,
        ) );
    }
    // Give administrators the manage capability.
    $admin = get_role( 'administrator' );
    if ( $admin && ! $admin->has_cap( ML_CAP_MANAGE ) ) {
        $admin->add_cap( ML_CAP_MANAGE );
    }
}

/**
 * Lazy DB version check on every admin load (cheap option read).
 */
add_action( 'admin_init', function () {
    if ( get_option( ML_OPT_DB_VERSION ) !== ML_DB_VERSION ) {
        ml_db_install();
        update_option( ML_OPT_DB_VERSION, ML_DB_VERSION, false );
    }
} );
