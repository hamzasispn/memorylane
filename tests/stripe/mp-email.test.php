<?php
// Standalone unit test for the Matterport label→email extractor.
define( 'ABSPATH', __DIR__ );
if ( ! function_exists( 'add_action' ) ) { function add_action() {} }
require __DIR__ . '/../../inc/matterport/sync.php';

$fail = 0;
function check( $l, $g, $w ) { global $fail; if ( $g === $w ) echo "PASS  $l\n"; else { echo "FAIL  $l — got " . var_export($g,true) . " want " . var_export($w,true) . "\n"; $GLOBALS['fail']++; } }

check( 'plain email in label',  ml_mp_extract_email( 'Villa Bianca john@example.com' ), 'john@example.com' );
check( 'lowercased',            ml_mp_extract_email( 'MARY.SMITH@Foo.CO.uk home' ),      'mary.smith@foo.co.uk' );
check( 'plus + dashes',         ml_mp_extract_email( 'a-b+c@sub-domain.example.com' ),   'a-b+c@sub-domain.example.com' );
check( 'no email → empty',      ml_mp_extract_email( 'Villa Bianca (no email)' ),        '' );
check( 'empty → empty',         ml_mp_extract_email( '' ),                               '' );

exit( $fail === 0 ? 0 : 1 );
