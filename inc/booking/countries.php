<?php
/**
 * Memory Lane — ISO 3166-1 country list for the booking address form.
 * code => English name. Used to populate the /boek country <select>.
 */
defined( 'ABSPATH' ) || exit;

function ml_iso_countries() {
    return array(
        'AF' => 'Afghanistan', 'AX' => 'Åland Islands', 'AL' => 'Albania', 'DZ' => 'Algeria',
        'AD' => 'Andorra', 'AO' => 'Angola', 'AI' => 'Anguilla', 'AG' => 'Antigua & Barbuda',
        'AR' => 'Argentina', 'AM' => 'Armenia', 'AW' => 'Aruba', 'AU' => 'Australia',
        'AT' => 'Austria', 'AZ' => 'Azerbaijan', 'BS' => 'Bahamas', 'BH' => 'Bahrain',
        'BD' => 'Bangladesh', 'BB' => 'Barbados', 'BY' => 'Belarus', 'BE' => 'Belgium',
        'BZ' => 'Belize', 'BJ' => 'Benin', 'BM' => 'Bermuda', 'BT' => 'Bhutan',
        'BO' => 'Bolivia', 'BA' => 'Bosnia & Herzegovina', 'BW' => 'Botswana', 'BR' => 'Brazil',
        'BN' => 'Brunei', 'BG' => 'Bulgaria', 'BF' => 'Burkina Faso', 'BI' => 'Burundi',
        'KH' => 'Cambodia', 'CM' => 'Cameroon', 'CA' => 'Canada', 'CV' => 'Cape Verde',
        'KY' => 'Cayman Islands', 'CF' => 'Central African Republic', 'TD' => 'Chad', 'CL' => 'Chile',
        'CN' => 'China', 'CO' => 'Colombia', 'KM' => 'Comoros', 'CG' => 'Congo',
        'CD' => 'Congo (DRC)', 'CR' => 'Costa Rica', 'CI' => "Côte d'Ivoire", 'HR' => 'Croatia',
        'CU' => 'Cuba', 'CW' => 'Curaçao', 'CY' => 'Cyprus', 'CZ' => 'Czechia',
        'DK' => 'Denmark', 'DJ' => 'Djibouti', 'DM' => 'Dominica', 'DO' => 'Dominican Republic',
        'EC' => 'Ecuador', 'EG' => 'Egypt', 'SV' => 'El Salvador', 'GQ' => 'Equatorial Guinea',
        'ER' => 'Eritrea', 'EE' => 'Estonia', 'SZ' => 'Eswatini', 'ET' => 'Ethiopia',
        'FJ' => 'Fiji', 'FI' => 'Finland', 'FR' => 'France', 'GF' => 'French Guiana',
        'PF' => 'French Polynesia', 'GA' => 'Gabon', 'GM' => 'Gambia', 'GE' => 'Georgia',
        'DE' => 'Germany', 'GH' => 'Ghana', 'GI' => 'Gibraltar', 'GR' => 'Greece',
        'GL' => 'Greenland', 'GD' => 'Grenada', 'GP' => 'Guadeloupe', 'GU' => 'Guam',
        'GT' => 'Guatemala', 'GG' => 'Guernsey', 'GN' => 'Guinea', 'GW' => 'Guinea-Bissau',
        'GY' => 'Guyana', 'HT' => 'Haiti', 'HN' => 'Honduras', 'HK' => 'Hong Kong',
        'HU' => 'Hungary', 'IS' => 'Iceland', 'IN' => 'India', 'ID' => 'Indonesia',
        'IR' => 'Iran', 'IQ' => 'Iraq', 'IE' => 'Ireland', 'IM' => 'Isle of Man',
        'IL' => 'Israel', 'IT' => 'Italy', 'JM' => 'Jamaica', 'JP' => 'Japan',
        'JE' => 'Jersey', 'JO' => 'Jordan', 'KZ' => 'Kazakhstan', 'KE' => 'Kenya',
        'KI' => 'Kiribati', 'KW' => 'Kuwait', 'KG' => 'Kyrgyzstan', 'LA' => 'Laos',
        'LV' => 'Latvia', 'LB' => 'Lebanon', 'LS' => 'Lesotho', 'LR' => 'Liberia',
        'LY' => 'Libya', 'LI' => 'Liechtenstein', 'LT' => 'Lithuania', 'LU' => 'Luxembourg',
        'MO' => 'Macao', 'MG' => 'Madagascar', 'MW' => 'Malawi', 'MY' => 'Malaysia',
        'MV' => 'Maldives', 'ML' => 'Mali', 'MT' => 'Malta', 'MH' => 'Marshall Islands',
        'MQ' => 'Martinique', 'MR' => 'Mauritania', 'MU' => 'Mauritius', 'MX' => 'Mexico',
        'FM' => 'Micronesia', 'MD' => 'Moldova', 'MC' => 'Monaco', 'MN' => 'Mongolia',
        'ME' => 'Montenegro', 'MS' => 'Montserrat', 'MA' => 'Morocco', 'MZ' => 'Mozambique',
        'MM' => 'Myanmar', 'NA' => 'Namibia', 'NR' => 'Nauru', 'NP' => 'Nepal',
        'NL' => 'Netherlands', 'NC' => 'New Caledonia', 'NZ' => 'New Zealand', 'NI' => 'Nicaragua',
        'NE' => 'Niger', 'NG' => 'Nigeria', 'MK' => 'North Macedonia', 'NO' => 'Norway',
        'OM' => 'Oman', 'PK' => 'Pakistan', 'PW' => 'Palau', 'PS' => 'Palestine',
        'PA' => 'Panama', 'PG' => 'Papua New Guinea', 'PY' => 'Paraguay', 'PE' => 'Peru',
        'PH' => 'Philippines', 'PL' => 'Poland', 'PT' => 'Portugal', 'PR' => 'Puerto Rico',
        'QA' => 'Qatar', 'RE' => 'Réunion', 'RO' => 'Romania', 'RU' => 'Russia',
        'RW' => 'Rwanda', 'WS' => 'Samoa', 'SM' => 'San Marino', 'SA' => 'Saudi Arabia',
        'SN' => 'Senegal', 'RS' => 'Serbia', 'SC' => 'Seychelles', 'SL' => 'Sierra Leone',
        'SG' => 'Singapore', 'SX' => 'Sint Maarten', 'SK' => 'Slovakia', 'SI' => 'Slovenia',
        'SB' => 'Solomon Islands', 'SO' => 'Somalia', 'ZA' => 'South Africa', 'KR' => 'South Korea',
        'SS' => 'South Sudan', 'ES' => 'Spain', 'LK' => 'Sri Lanka', 'SD' => 'Sudan',
        'SR' => 'Suriname', 'SE' => 'Sweden', 'CH' => 'Switzerland', 'SY' => 'Syria',
        'TW' => 'Taiwan', 'TJ' => 'Tajikistan', 'TZ' => 'Tanzania', 'TH' => 'Thailand',
        'TL' => 'Timor-Leste', 'TG' => 'Togo', 'TO' => 'Tonga', 'TT' => 'Trinidad & Tobago',
        'TN' => 'Tunisia', 'TR' => 'Türkiye', 'TM' => 'Turkmenistan', 'TC' => 'Turks & Caicos',
        'TV' => 'Tuvalu', 'UG' => 'Uganda', 'UA' => 'Ukraine', 'AE' => 'United Arab Emirates',
        'GB' => 'United Kingdom', 'US' => 'United States', 'UY' => 'Uruguay', 'UZ' => 'Uzbekistan',
        'VU' => 'Vanuatu', 'VA' => 'Vatican City', 'VE' => 'Venezuela', 'VN' => 'Vietnam',
        'YE' => 'Yemen', 'ZM' => 'Zambia', 'ZW' => 'Zimbabwe',
    );
}

/**
 * Resolve a country name (or ISO code) to its ISO-2 code, or '' if unknown.
 */
function ml_country_to_code( $value ) {
    $value = trim( (string) $value );
    if ( $value === '' ) return '';
    $upper = strtoupper( $value );
    $countries = ml_iso_countries();
    if ( isset( $countries[ $upper ] ) ) return $upper;
    foreach ( $countries as $code => $name ) {
        if ( strcasecmp( $name, $value ) === 0 ) return $code;
    }
    return '';
}
