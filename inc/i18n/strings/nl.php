<?php
defined( 'ABSPATH' ) || exit;

return array(

    // Generic.
    'common.brand'                => 'Memory Lane',
    'common.save'                 => 'Opslaan',
    'common.cancel'               => 'Annuleren',
    'common.confirm'              => 'Bevestigen',
    'common.continue'             => 'Doorgaan',
    'common.back'                 => 'Terug',
    'common.loading'              => 'Laden...',
    'common.error_generic'        => 'Er is iets misgegaan. Probeer het opnieuw.',
    'common.success'              => 'Gelukt',

    // Auth.
    'auth.login.title'            => 'Inloggen',
    'auth.login.subtitle'         => 'Welkom terug bij Memory Lane',
    'auth.login.email'            => 'E-mailadres',
    'auth.login.password'         => 'Wachtwoord',
    'auth.login.remember'         => 'Onthoud mij',
    'auth.login.submit'           => 'Inloggen',
    'auth.login.forgot'           => 'Wachtwoord vergeten?',
    'auth.login.error_generic'    => 'E-mailadres of wachtwoord onjuist.',
    'auth.login.error_locked'     => 'Te veel pogingen. Probeer over een uur opnieuw.',
    'auth.forgot.title'           => 'Wachtwoord vergeten',
    'auth.forgot.subtitle'        => 'Vul je e-mailadres in en ontvang een herstellink.',
    'auth.forgot.submit'          => 'Verstuur link',
    'auth.forgot.success'         => 'Als dit e-mailadres bij ons bekend is, hebben we zojuist een link verstuurd.',
    'auth.reset.title'            => 'Nieuw wachtwoord instellen',
    'auth.reset.new_password'     => 'Nieuw wachtwoord',
    'auth.reset.confirm_password' => 'Bevestig wachtwoord',
    'auth.reset.submit'           => 'Wachtwoord opslaan',
    'auth.reset.error_token'      => 'Deze link is ongeldig of verlopen.',
    'auth.reset.error_match'      => 'De wachtwoorden komen niet overeen.',
    'auth.reset.error_short'      => 'Wachtwoord moet minstens 10 tekens bevatten.',
    'auth.reset.success'          => 'Wachtwoord is bijgewerkt. Je kan nu inloggen.',
    'auth.welcome.title'          => 'Welkom bij Memory Lane',
    'auth.welcome.subtitle'       => 'Stel je wachtwoord in om toegang te krijgen tot je klantenzone.',
    'auth.logout'                 => 'Uitloggen',

    // Dashboard nav.
    'nav.overview'                => 'Overzicht',
    'nav.tours'                   => 'Tours',
    'nav.booking'                 => 'Boeking',
    'nav.subscription'            => 'Abonnement',
    'nav.settings'                => 'Instellingen',
    'nav.profile'                 => 'Mijn profiel',

    // Overview.
    'overview.title'              => 'Welkom terug',
    'overview.subtitle'           => 'Hier is een overzicht van jouw Memory Lane.',
    'overview.card.subscription'  => 'Abonnement',
    'overview.card.tours'         => 'Mijn tours',
    'overview.card.booking'       => 'Eerstvolgende afspraak',
    'overview.empty.tours'        => 'Nog geen tours toegewezen.',
    'overview.empty.booking'      => 'Geen geplande afspraken.',

    // Tours.
    'tours.title'                 => 'Mijn tours',
    'tours.empty'                 => 'Nog geen tours beschikbaar. Boek eerst een opname.',
    'tours.view'                  => 'Bekijk tour',
    'tours.status.active'         => 'Actief',
    'tours.status.archived'       => 'Gearchiveerd',
    'tours.status.pending'        => 'In afwachting',
    'tours.access_expired.title'  => 'Toegang verlopen',
    'tours.access_expired.body'   => 'Je abonnement is niet actief. Verleng het abonnement om je tour opnieuw te bekijken.',
    'tours.access_expired.cta'    => 'Abonnement vernieuwen',

    // Booking.
    'booking.title'               => 'Een opname inplannen',
    'booking.subtitle'            => 'Kies een datum en tijd voor jouw 3D-scan.',
    'booking.no_slots'            => 'Op dit moment zijn er geen vrije momenten. Kom later terug of contacteer ons.',
    'booking.confirm.title'       => 'Bevestig je afspraak',
    'booking.confirm.body'        => 'We sturen je een bevestigingsmail zodra de afspraak is goedgekeurd.',
    'booking.status.requested'    => 'Aangevraagd',
    'booking.status.confirmed'    => 'Bevestigd',
    'booking.status.completed'    => 'Voltooid',
    'booking.status.cancelled'    => 'Geannuleerd',
    'booking.cancel'              => 'Afspraak annuleren',
    'booking.reschedule'          => 'Verzetten',

    // Subscription.
    'sub.title'                   => 'Abonnement',
    'sub.status.active'           => 'Actief',
    'sub.status.past_due'         => 'Betaling mislukt',
    'sub.status.cancelled'        => 'Geannuleerd',
    'sub.status.canceling'        => 'Loopt af',
    'sub.phase.year_one'          => 'Jaar 1 inbegrepen',
    'sub.phase.monthly'           => 'Maandelijks abonnement',
    'sub.next_billing'            => 'Volgende afschrijving',
    'sub.year_one_ends'           => 'Jaar 1 eindigt op',
    'sub.cancel'                  => 'Abonnement opzeggen',
    'sub.cancel_confirm'          => 'Weet je het zeker? Je behoudt toegang tot het einde van de huidige periode.',
    'sub.manage_in_stripe'        => 'Beheren in Stripe',
    'sub.cancelled_msg'           => 'Je abonnement loopt af op {date}.',

    // Settings.
    'settings.title'              => 'Instellingen',
    'settings.tab.profile'        => 'Profiel',
    'settings.tab.security'       => 'Beveiliging',
    'settings.tab.language'       => 'Taal',
    'settings.email'              => 'E-mailadres',
    'settings.phone'              => 'Telefoonnummer',
    'settings.address'            => 'Adres',
    'settings.change_password'    => 'Wachtwoord wijzigen',
    'settings.current_password'   => 'Huidig wachtwoord',
    'settings.language_label'     => 'Voorkeurstaal',

    // Errors.
    'error.access_denied'         => 'Je hebt geen toegang tot deze pagina.',
    'error.login_required'        => 'Log in om door te gaan.',
    'error.invalid_request'       => 'Ongeldig verzoek.',
);
