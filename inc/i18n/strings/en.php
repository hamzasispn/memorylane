<?php
defined( 'ABSPATH' ) || exit;

return array(

    // Generic.
    'common.brand'                => 'Memory Lane',
    'common.save'                 => 'Save',
    'common.cancel'               => 'Cancel',
    'common.confirm'              => 'Confirm',
    'common.continue'             => 'Continue',
    'common.back'                 => 'Back',
    'common.loading'              => 'Loading...',
    'common.error_generic'        => 'Something went wrong. Please try again.',
    'common.success'              => 'Success',

    // Auth.
    'auth.login.title'            => 'Sign in',
    'auth.login.subtitle'         => 'Welcome back to Memory Lane',
    'auth.login.email'            => 'Email address',
    'auth.login.password'         => 'Password',
    'auth.login.remember'         => 'Remember me',
    'auth.login.submit'           => 'Sign in',
    'auth.login.forgot'           => 'Forgot your password?',
    'auth.login.error_generic'    => 'Email or password incorrect.',
    'auth.login.error_locked'     => 'Too many attempts. Try again in an hour.',
    'auth.forgot.title'           => 'Forgot password',
    'auth.forgot.subtitle'        => 'Enter your email address and we will send you a reset link.',
    'auth.forgot.submit'          => 'Send link',
    'auth.forgot.success'         => 'If this email exists in our system, a link has been sent.',
    'auth.reset.title'            => 'Set a new password',
    'auth.reset.new_password'     => 'New password',
    'auth.reset.confirm_password' => 'Confirm password',
    'auth.reset.submit'           => 'Save password',
    'auth.reset.error_token'      => 'This link is invalid or expired.',
    'auth.reset.error_match'      => 'The passwords do not match.',
    'auth.reset.error_short'      => 'Password must be at least 10 characters.',
    'auth.reset.success'          => 'Password updated. You can now sign in.',
    'auth.welcome.title'          => 'Welcome to Memory Lane',
    'auth.welcome.subtitle'       => 'Set your password to access your customer area.',
    'auth.logout'                 => 'Sign out',

    // Dashboard nav.
    'nav.overview'                => 'Overview',
    'nav.tours'                   => 'Tours',
    'nav.booking'                 => 'Booking',
    'nav.subscription'            => 'Subscription',
    'nav.settings'                => 'Settings',
    'nav.profile'                 => 'My profile',

    // Overview.
    'overview.title'              => 'Welcome back',
    'overview.subtitle'           => "Here is an overview of your Memory Lane.",
    'overview.card.subscription'  => 'Subscription',
    'overview.card.tours'         => 'My tours',
    'overview.card.booking'       => 'Next appointment',
    'overview.empty.tours'        => 'No tours assigned yet.',
    'overview.empty.booking'      => 'No scheduled appointments.',

    // Tours.
    'tours.title'                 => 'My tours',
    'tours.empty'                 => 'No tours available yet. Book a scan first.',
    'tours.view'                  => 'View tour',
    'tours.status.active'         => 'Active',
    'tours.status.archived'       => 'Archived',
    'tours.status.pending'        => 'Pending',
    'tours.access_expired.title'  => 'Access expired',
    'tours.access_expired.body'   => 'Your subscription is not active. Renew it to view your tour again.',
    'tours.access_expired.cta'    => 'Renew subscription',

    // Booking.
    'booking.title'               => 'Schedule a scan',
    'booking.subtitle'            => 'Pick a date and time for your 3D scan.',
    'booking.no_slots'            => 'No free slots right now. Please check back later or contact us.',
    'booking.confirm.title'       => 'Confirm your appointment',
    'booking.confirm.body'        => 'We will send you a confirmation email once the appointment is approved.',
    'booking.status.requested'    => 'Requested',
    'booking.status.confirmed'    => 'Confirmed',
    'booking.status.completed'    => 'Completed',
    'booking.status.cancelled'    => 'Cancelled',
    'booking.cancel'              => 'Cancel appointment',
    'booking.reschedule'          => 'Reschedule',

    // Subscription.
    'sub.title'                   => 'Subscription',
    'sub.status.active'           => 'Active',
    'sub.status.past_due'         => 'Payment failed',
    'sub.status.cancelled'        => 'Cancelled',
    'sub.status.canceling'        => 'Ending soon',
    'sub.phase.year_one'          => 'Year 1 included',
    'sub.phase.monthly'           => 'Monthly subscription',
    'sub.next_billing'            => 'Next billing date',
    'sub.year_one_ends'           => 'Year 1 ends on',
    'sub.cancel'                  => 'Cancel subscription',
    'sub.cancel_confirm'          => 'Are you sure? You keep access until the current period ends.',
    'sub.manage_in_stripe'        => 'Manage in Stripe',
    'sub.cancelled_msg'           => 'Your subscription ends on {date}.',

    // Settings.
    'settings.title'              => 'Settings',
    'settings.tab.profile'        => 'Profile',
    'settings.tab.security'       => 'Security',
    'settings.tab.language'       => 'Language',
    'settings.email'              => 'Email address',
    'settings.phone'              => 'Phone number',
    'settings.address'            => 'Address',
    'settings.change_password'    => 'Change password',
    'settings.current_password'   => 'Current password',
    'settings.language_label'     => 'Preferred language',

    // Errors.
    'error.access_denied'         => 'You do not have access to this page.',
    'error.login_required'        => 'Please sign in to continue.',
    'error.invalid_request'       => 'Invalid request.',
);
