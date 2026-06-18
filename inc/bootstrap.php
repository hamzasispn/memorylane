<?php
/**
 * Memory Lane — bootstrap loader.
 * Loads all subsystems in correct order. Called from functions.php.
 */
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

// i18n.
require_once __DIR__ . '/i18n/translator.php';

// Database.
require_once __DIR__ . '/db/install.php';

// Auth.
require_once __DIR__ . '/auth/routes.php';
require_once __DIR__ . '/auth/handlers.php';
require_once __DIR__ . '/auth/password-reset.php';

// Access gate (booking-only: logged-in customer = access).
require_once __DIR__ . '/subscriptions/access-gate.php';

// Tours.
require_once __DIR__ . '/tours/cpt.php';
require_once __DIR__ . '/tours/admin-meta.php';
require_once __DIR__ . '/tours/viewer.php';

// Booking.
require_once __DIR__ . '/booking/working-hours.php';
require_once __DIR__ . '/booking/availability.php';
require_once __DIR__ . '/booking/slots.php';
require_once __DIR__ . '/booking/bookings.php';
require_once __DIR__ . '/booking/admin.php';
require_once __DIR__ . '/booking/countries.php';
require_once __DIR__ . '/booking/boek-checkout.php';
require_once __DIR__ . '/booking/booking-rest.php';

// Teamleader CRM integration.
require_once __DIR__ . '/teamleader/oauth.php';
require_once __DIR__ . '/teamleader/client.php';
require_once __DIR__ . '/teamleader/booking-sync.php';
require_once __DIR__ . '/teamleader/settings.php';

// Custom admin panel (slim).
require_once __DIR__ . '/admin-panel/pagination.php';
require_once __DIR__ . '/admin-panel/handlers.php';

// Emails + Cron.
require_once __DIR__ . '/emails/mailer.php';
require_once __DIR__ . '/cron/schedule.php';
require_once __DIR__ . '/cron/booking-reminders.php';

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
