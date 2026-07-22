<?php
// Standalone unit test — runs without WordPress.
define( 'ABSPATH', __DIR__ );
require __DIR__ . '/../../inc/stripe/plans.php';

$fail = 0;
function check( $label, $got, $want ) {
    global $fail;
    if ( $got === $want ) { echo "PASS  $label\n"; }
    else { echo "FAIL  $label — got " . var_export( $got, true ) . " want " . var_export( $want, true ) . "\n"; $GLOBALS['fail']++; }
}

check( 'euros to cents',        ml_to_minor_units( '299.00' ), 29900 );
check( 'comma decimal',         ml_to_minor_units( '9,50' ),    950 );
check( 'blank is zero',         ml_to_minor_units( '' ),          0 );
check( 'cents to euros string', ml_from_minor_units( 29900 ),  '299.00' );

exit( $fail === 0 ? 0 : 1 );
