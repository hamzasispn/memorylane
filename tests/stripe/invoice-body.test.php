<?php
define( 'ABSPATH', __DIR__ );
if ( ! function_exists( 'add_action' ) ) { function add_action() {} }
require __DIR__ . '/../../inc/teamleader/invoicing.php';

$fail = 0;
function check( $l, $g, $w ) { global $fail; if ( $g === $w ) echo "PASS  $l\n"; else { echo "FAIL  $l — got " . var_export($g,true) . " want " . var_export($w,true) . "\n"; $GLOBALS['fail']++; } }

$b = ml_tl_build_invoice_body( 'contact_1', 'dept_1', 'tax_1', 'Memory Lane — Activation', 29900, 'eur' );
check( 'invoicee contact', $b['invoicee']['customer']['id'],   'contact_1' );
check( 'invoicee type',    $b['invoicee']['customer']['type'], 'contact' );
check( 'department',       $b['department_id'],                'dept_1' );
$li = $b['grouped_lines'][0]['line_items'][0];
check( 'qty',              $li['quantity'],                    1 );
check( 'description',      $li['description'],                 'Memory Lane — Activation' );
check( 'amount decimal',   $li['unit_price']['amount'],        '299.00' );
check( 'currency upper',   $li['unit_price']['currency'],      'EUR' );
check( 'tax including',    $li['unit_price']['tax'],           'including' );
check( 'tax rate id',      $li['tax_rate_id'],                 'tax_1' );

exit( $fail === 0 ? 0 : 1 );
