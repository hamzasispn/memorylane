<?php
define( 'ABSPATH', __DIR__ );
require __DIR__ . '/../../inc/subscriptions/access-gate.php';

$fail = 0;
function check( $l, $g, $w ) { global $fail; if ( $g === $w ) echo "PASS  $l\n"; else { echo "FAIL  $l — got " . var_export($g,true) . " want " . var_export($w,true) . "\n"; $GLOBALS['fail']++; } }

$now   = 1700000000;
$grace = 7 * 86400;
$mk = function ( $status, $cpe_offset ) { return (object) array( 'status' => $status, 'current_period_end' => gmdate( 'Y-m-d H:i:s', 1700000000 + $cpe_offset ) ); };

check( 'active grants',         ml_access_from_sub_row( $mk('active',   86400),   $grace, $now ), true );
check( 'trialing grants',       ml_access_from_sub_row( $mk('trialing', 86400),   $grace, $now ), true );
check( 'past_due within grace', ml_access_from_sub_row( $mk('past_due', -86400),   $grace, $now ), true );
check( 'past_due beyond grace', ml_access_from_sub_row( $mk('past_due', -8*86400), $grace, $now ), false );
check( 'cancelled denies',      ml_access_from_sub_row( $mk('cancelled', 86400),   $grace, $now ), false );
check( 'null row denies',       ml_access_from_sub_row( null, $grace, $now ), false );

exit( $fail === 0 ? 0 : 1 );
