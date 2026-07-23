<?php
define( 'ABSPATH', __DIR__ );
if ( ! function_exists( 'wp_json_encode' ) ) { function wp_json_encode( $d ) { return json_encode( $d ); } }
require __DIR__ . '/../../inc/subscriptions/sync.php';

$fail = 0;
function check( $l, $g, $w ) { global $fail; if ( $g === $w ) echo "PASS  $l\n"; else { echo "FAIL  $l — got " . var_export($g,true) . " want " . var_export($w,true) . "\n"; $GLOBALS['fail']++; } }

// Minimal fake of a \Stripe\Subscription (property access as used by the mapper).
$sub = (object) array(
    'id'                   => 'sub_123',
    'customer'             => 'cus_123',
    'status'               => 'active',
    'current_period_end'   => 1893456000,          // 2030-01-01 UTC
    'cancel_at_period_end' => false,
);
$f = ml_sub_fields_from_stripe( $sub );
check( 'sub id',         $f['stripe_sub_id'],        'sub_123' );
check( 'customer id',    $f['stripe_customer_id'],   'cus_123' );
check( 'status',         $f['status'],               'active' );
check( 'period end utc', $f['current_period_end'],   '2030-01-01 00:00:00' );
check( 'cancel flag',    $f['cancel_at_period_end'], 0 );

exit( $fail === 0 ? 0 : 1 );
