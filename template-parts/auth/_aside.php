<?php
/**
 * Right-side promo panel on auth pages (desktop only).
 */
defined( 'ABSPATH' ) || exit;
?>
<aside class="ml-auth__aside">
    <div class="ml-auth__aside-inner">
        <h2><?php ml_e( 'auth.aside.title', 'Jouw woning, voor altijd bewaard.' ); ?></h2>
        <p><?php ml_e( 'auth.aside.body', 'Toegang tot je persoonlijke klantenzone, je virtuele tours en je abonnement — op één plek, veilig en privé.' ); ?></p>
        <ul>
            <li><?php ml_e( 'auth.aside.point1', 'Bekijk je 3D-tour op elk moment' ); ?></li>
            <li><?php ml_e( 'auth.aside.point2', 'Beheer je abonnement zelf' ); ?></li>
            <li><?php ml_e( 'auth.aside.point3', 'Plan je opname-afspraak in' ); ?></li>
        </ul>
    </div>
</aside>
