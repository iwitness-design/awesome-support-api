<?php

namespace RCP_Avatax;

use \Iso3166\Codes;

class MemberFields {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * Only make one instance of \RCP_Avatax\MemberFields
	 *
	 * @return MemberFields
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof MemberFields ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {
		$this->hooks();
	}


	public function hooks() {

		// Add address fields to User Forms.
		add_action( 'rcp_before_subscription_form_fields', array( $this, 'address_fields' ) );
		add_action( 'rcp_profile_editor_after',            array( $this, 'address_fields' ) );

		// Process User Forms, Update User address
		add_filter( 'rcp_subscription_data', array( $this, 'subscription_data' ) );
		add_action( 'rcp_user_profile_updated', array( $this, 'user_profile_update' ), 10, 2 );

	}

	public function address_fields( $user_id = NULL ) {

		if( !$user_id ) {
			$user_id = get_current_user_id();
		}

		$countries       = apply_filters( 'rcp_avatax_country_list', Codes::$countries );
		$default_country = apply_filters( 'rcp_avatax_country_default', 'US' );
		$user_country    = get_user_meta( $user_id, 'rcp_country', true );
		$user_vat_number = get_user_meta( $user_id, 'rcp_vat_number', true ); ?>

		<?php if ( apply_filters( 'rcp_avatax_form_styling_show', true ) ) : ?>
			<style>
				@media screen and (min-width:728px) {
					#rcp_avatax_postal_code_wrap,
					#rcp_avatax_country_wrap {
						width: 49%;
						float: left;
					}

					#rcp_avatax_country_wrap  {
						float: right;
					}
				}
			</style>
		<?php endif; ?>

		<fieldset class="rcp_avatax_fieldset">

			<legend><?php echo apply_filters( 'rcp_avatax_address_title', __( 'Billing Address', 'rcp-avatax' ) ); ?></legend>

			<?php if ( apply_filters( 'rcp_avatax_show_vat', rcp_avatax()::get_settings( 'show_vat', false ) ) ): ?>
				<p id="rcp_avatax_vat_wrap">
					<label for="rcp_avatax[vat]"><?php _e( 'VAT ID', 'rcptx' ); ?></label>
					<input name="rcp_avatax[vat]" id="rcp_avatax[vat]" type="text" value="<?php echo esc_attr( $user_vat_number );?>" />
				</p>
			<?php endif; ?>

			<p id="rcp_avatax_address_1_wrap">
				<label for="rcp_avatax[address_1]"><?php echo apply_filters ( 'rcp_avatax_address_1_label', __( 'Address Line 1', 'rcp-avatax' ) ); ?></label>
				<input name="rcp_avatax[address_1]" id="rcp_avatax[address_1]" class="required" type="text" <?php if( isset( $_POST['rcp_avatax[address_1]'] ) ) { echo 'value="' . esc_attr( $_POST['rcp_avatax[address_1]'] ) . '"'; } ?>/>
			</p>
			<p id="rcp_avatax_address_2_wrap">
				<label for="rcp_avatax[address_2]"><?php echo apply_filters ( 'rcp_avatax_address_2_label', __( 'Address Line 2', 'rcp-avatax' ) ); ?></label>
				<input name="rcp_avatax[address_2]" id="rcp_avatax[address_2]" class="required" type="text" <?php if( isset( $_POST['rcp_avatax[address_2]'] ) ) { echo 'value="' . esc_attr( $_POST['rcp_avatax[address_2]'] ) . '"'; } ?>/>
			</p>
			<p id="rcp_avatax_city_wrap">
				<label for="rcp_avatax[city]"><?php echo apply_filters ( 'rcp_avatax_city_label', __( 'City', 'rcp-avatax' ) ); ?></label>
				<input name="rcp_avatax[city]" id="rcp_avatax[city]" class="required" type="text" <?php if( isset( $_POST['rcp_avatax[city]'] ) ) { echo 'value="' . esc_attr( $_POST['rcp_avatax[city]'] ) . '"'; } ?>/>
			</p>
			<p id="rcp_avatax_region_wrap">
				<label for="rcp_avatax[region]"><?php echo apply_filters ( 'rcp_avatax_region_label', __( 'State/Province/Region', 'rcp-avatax' ) ); ?></label>
				<input name="rcp_avatax[region]" id="rcp_avatax[region]" class="required" type="text" <?php if( isset( $_POST['rcp_avatax[region]'] ) ) { echo 'value="' . esc_attr( $_POST['rcp_avatax[region]'] ) . '"'; } ?>/>
			</p>
			<p id="rcp_avatax_postal_code_wrap">
				<label for="rcp_avatax[postal_code]"><?php echo apply_filters ( 'rcp_avatax_postal_code_label', __( 'ZIP/Postal Code', 'rcp-avatax' ) ); ?></label>
				<input name="rcp_avatax[postal_code]" id="rcp_avatax[postal_code]" class="required" type="text" <?php if( isset( $_POST['rcp_avatax[postal_code]'] ) ) { echo 'value="' . esc_attr( $_POST['rcp_avatax[postal_code]'] ) . '"'; } ?>/>
			</p>
			<p id="rcp_avatax_country_wrap">
				<label for="rcp_avatax[country]"><?php echo apply_filters ( 'rcp_avatax_country_label', __( 'Country', 'rcp-avatax' ) ); ?></label>
				<select id="rcp_avatax[country]" name="rcp_avatax[country]">
					<?php foreach( $countries as $code => $country ) : ?>
						<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $default_country, $code ); ?>><?php echo esc_html( $country ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>

		</fieldset><?php

	}

	public function user_profile_update( $user_id, $userdata ) {

		$country = ! empty( $_POST['rcp_country'] ) ? sanitize_text_field( $_POST['rcp_country'] ) : '';
		$vat_number   = ! empty( $_POST['rcp_vat_number'] )   ? sanitize_text_field( $_POST['rcp_vat_number'] ) : '';

		update_user_meta( $user_id, 'rcp_country', $country );
		update_user_meta( $user_id, 'rcp_vat_number', $vat_number );

	}

	public function subscription_data( $subscription_data ) {

		$subscription_data['country'] = sanitize_text_field( $_POST['rcp_country'] );
		$subscription_data['vat_number'] = sanitize_text_field( $_POST['rcp_vat_number'] );
		$subscription_data['taxamo_transaction_key'] = sanitize_text_field( $_POST['rcp_taxamo_transaction_key'] );
		$subscription_data['taxamo_amount'] = sanitize_text_field( $_POST['rcp_taxamo_amount'] );
		$subscription_data['taxamo_tax_amount'] = sanitize_text_field( $_POST['rcp_taxamo_tax_amount'] );
		$subscription_data['taxamo_total_amount'] = sanitize_text_field( $_POST['rcp_taxamo_total_amount'] );

		update_user_meta( $subscription_data['user_id'], 'rcp_vat_number', $subscription_data['vat_number'] );
		update_user_meta( $subscription_data['user_id'], 'rcp_country', $subscription_data['country'] );

		$current_transaction_key = get_user_meta( $subscription_data['user_id'], 'rcp_taxamo_transaction_key', true );

		if(!$current_transaction_key || $subscription_data['taxamo_transaction_key'] != $current_transaction_key) {
			update_user_meta( $subscription_data['user_id'], 'rcp_taxamo_transaction_key', $subscription_data['taxamo_transaction_key'] );
			add_user_meta( $subscription_data['user_id'], 'rcp_taxamo_transaction_key_new', true );

		}

		return $subscription_data;

	}

	public static function get_countries() {

		$countries = array(
			''   => __( 'Select Your Billing Country', 'rcp-avatax' ),
			'US' => 'United States',
			'CA' => 'Canada',
			'GB' => 'United Kingdom',
			'AF' => 'Afghanistan',
			'AL' => 'Albania',
			'DZ' => 'Algeria',
			'AS' => 'American Samoa',
			'AD' => 'Andorra',
			'AO' => 'Angola',
			'AI' => 'Anguilla',
			'AQ' => 'Antarctica',
			'AG' => 'Antigua and Barbuda',
			'AR' => 'Argentina',
			'AM' => 'Armenia',
			'AW' => 'Aruba',
			'AU' => 'Australia',
			'AT' => 'Austria',
			'AZ' => 'Azerbaijan',
			'BS' => 'Bahamas',
			'BH' => 'Bahrain',
			'BD' => 'Bangladesh',
			'BB' => 'Barbados',
			'BY' => 'Belarus',
			'BE' => 'Belgium',
			'BZ' => 'Belize',
			'BJ' => 'Benin',
			'BM' => 'Bermuda',
			'BT' => 'Bhutan',
			'BO' => 'Bolivia',
			'BA' => 'Bosnia and Herzegovina',
			'BW' => 'Botswana',
			'BV' => 'Bouvet Island',
			'BR' => 'Brazil',
			'IO' => 'British Indian Ocean Territory',
			'BN' => 'Brunei Darrussalam',
			'BG' => 'Bulgaria',
			'BF' => 'Burkina Faso',
			'BI' => 'Burundi',
			'KH' => 'Cambodia',
			'CM' => 'Cameroon',
			'CV' => 'Cape Verde',
			'KY' => 'Cayman Islands',
			'CF' => 'Central African Republic',
			'TD' => 'Chad',
			'CL' => 'Chile',
			'CN' => 'China',
			'CX' => 'Christmas Island',
			'CC' => 'Cocos Islands',
			'CO' => 'Colombia',
			'KM' => 'Comoros',
			'CD' => 'Congo, Democratic People\'s Republic',
			'CG' => 'Congo, Republic of',
			'CK' => 'Cook Islands',
			'CR' => 'Costa Rica',
			'CI' => 'Cote d\'Ivoire',
			'HR' => 'Croatia/Hrvatska',
			'CU' => 'Cuba',
			'CY' => 'Cyprus Island',
			'CZ' => 'Czech Republic',
			'DK' => 'Denmark',
			'DJ' => 'Djibouti',
			'DM' => 'Dominica',
			'DO' => 'Dominican Republic',
			'TP' => 'East Timor',
			'EC' => 'Ecuador',
			'EG' => 'Egypt',
			'GQ' => 'Equatorial Guinea',
			'SV' => 'El Salvador',
			'ER' => 'Eritrea',
			'EE' => 'Estonia',
			'ET' => 'Ethiopia',
			'FK' => 'Falkland Islands',
			'FO' => 'Faroe Islands',
			'FJ' => 'Fiji',
			'FI' => 'Finland',
			'FR' => 'France',
			'GF' => 'French Guiana',
			'PF' => 'French Polynesia',
			'TF' => 'French Southern Territories',
			'GA' => 'Gabon',
			'GM' => 'Gambia',
			'GE' => 'Georgia',
			'DE' => 'Germany',
			'GR' => 'Greece',
			'GH' => 'Ghana',
			'GI' => 'Gibraltar',
			'GL' => 'Greenland',
			'GD' => 'Grenada',
			'GP' => 'Guadeloupe',
			'GU' => 'Guam',
			'GT' => 'Guatemala',
			'GG' => 'Guernsey',
			'GN' => 'Guinea',
			'GW' => 'Guinea-Bissau',
			'GY' => 'Guyana',
			'HT' => 'Haiti',
			'HM' => 'Heard and McDonald Islands',
			'VA' => 'Holy See (City Vatican State)',
			'HN' => 'Honduras',
			'HK' => 'Hong Kong',
			'HU' => 'Hungary',
			'IS' => 'Iceland',
			'IN' => 'India',
			'ID' => 'Indonesia',
			'IR' => 'Iran',
			'IQ' => 'Iraq',
			'IE' => 'Ireland',
			'IM' => 'Isle of Man',
			'IL' => 'Israel',
			'IT' => 'Italy',
			'JM' => 'Jamaica',
			'JP' => 'Japan',
			'JE' => 'Jersey',
			'JO' => 'Jordan',
			'KZ' => 'Kazakhstan',
			'KE' => 'Kenya',
			'KI' => 'Kiribati',
			'KW' => 'Kuwait',
			'KG' => 'Kyrgyzstan',
			'LA' => 'Lao People\'s Democratic Republic',
			'LV' => 'Latvia',
			'LB' => 'Lebanon',
			'LS' => 'Lesotho',
			'LR' => 'Liberia',
			'LY' => 'Libyan Arab Jamahiriya',
			'LI' => 'Liechtenstein',
			'LT' => 'Lithuania',
			'LU' => 'Luxembourgh',
			'MO' => 'Macau',
			'MK' => 'Macedonia',
			'MG' => 'Madagascar',
			'MW' => 'Malawi',
			'MY' => 'Malaysia',
			'Mv' => 'Maldives',
			'ML' => 'Mali',
			'MT' => 'Malta',
			'MH' => 'Marshall Islands',
			'MQ' => 'Martinique',
			'MR' => 'Mauritania',
			'MU' => 'Mauritius',
			'YT' => 'Mayotte',
			'MX' => 'Mexico',
			'FM' => 'Micronesia',
			'MD' => 'Moldova, Republic of',
			'MC' => 'Monaco',
			'MN' => 'Mongolia',
			'ME' => 'Montenegro',
			'MS' => 'Montserrat',
			'MA' => 'Morocco',
			'MZ' => 'Mozambique',
			'MM' => 'Myanmar',
			'NA' => 'Namibia',
			'NR' => 'Nauru',
			'NP' => 'Nepal',
			'NL' => 'Netherlands',
			'AN' => 'Netherlands Antilles',
			'NC' => 'New Caledonia',
			'NZ' => 'New Zealand',
			'NI' => 'Nicaragua',
			'NE' => 'Niger',
			'NG' => 'Nigeria',
			'NU' => 'Niue',
			'NF' => 'Norfolk Island',
			'KR' => 'North Korea',
			'MP' => 'Northern Mariana Islands',
			'NO' => 'Norway',
			'OM' => 'Oman',
			'PK' => 'Pakistan',
			'PW' => 'Palau',
			'PS' => 'Palestinian Territories',
			'PA' => 'Panama',
			'PG' => 'Papua New Guinea',
			'PY' => 'Paraguay',
			'PE' => 'Peru',
			'PH' => 'Phillipines',
			'PN' => 'Pitcairn Island',
			'PL' => 'Poland',
			'PT' => 'Portugal',
			'PR' => 'Puerto Rico',
			'QA' => 'Qatar',
			'RE' => 'Reunion Island',
			'RO' => 'Romania',
			'RU' => 'Russian Federation',
			'RW' => 'Rwanda',
			'SH' => 'Saint Helena',
			'KN' => 'Saint Kitts and Nevis',
			'LC' => 'Saint Lucia',
			'PM' => 'Saint Pierre and Miquelon',
			'VC' => 'Saint Vincent and the Grenadines',
			'SM' => 'San Marino',
			'ST' => 'Sao Tome and Principe',
			'SA' => 'Saudi Arabia',
			'SN' => 'Senegal',
			'RS' => 'Serbia',
			'SC' => 'Seychelles',
			'SL' => 'Sierra Leone',
			'SG' => 'Singapore',
			'SK' => 'Slovak Republic',
			'SI' => 'Slovenia',
			'SB' => 'Solomon Islands',
			'SO' => 'Somalia',
			'ZA' => 'South Africa',
			'GS' => 'South Georgia',
			'KP' => 'South Korea',
			'ES' => 'Spain',
			'LK' => 'Sri Lanka',
			'SD' => 'Sudan',
			'SR' => 'Suriname',
			'SJ' => 'Svalbard and Jan Mayen Islands',
			'SZ' => 'Swaziland',
			'SE' => 'Sweden',
			'CH' => 'Switzerland',
			'SY' => 'Syrian Arab Republic',
			'TW' => 'Taiwan',
			'TJ' => 'Tajikistan',
			'TZ' => 'Tanzania',
			'TG' => 'Togo',
			'TK' => 'Tokelau',
			'TO' => 'Tonga',
			'TH' => 'Thailand',
			'TT' => 'Trinidad and Tobago',
			'TN' => 'Tunisia',
			'TR' => 'Turkey',
			'TM' => 'Turkmenistan',
			'TC' => 'Turks and Caicos Islands',
			'TV' => 'Tuvalu',
			'UG' => 'Uganda',
			'UA' => 'Ukraine',
			'AE' => 'United Arab Emirates',
			'UY' => 'Uruguay',
			'UM' => 'US Minor Outlying Islands',
			'UZ' => 'Uzbekistan',
			'VU' => 'Vanuatu',
			'VE' => 'Venezuela',
			'VN' => 'Vietnam',
			'VG' => 'Virgin Islands (British)',
			'VI' => 'Virgin Islands (USA)',
			'WF' => 'Wallis and Futuna Islands',
			'EH' => 'Western Sahara',
			'WS' => 'Western Samoa',
			'YE' => 'Yemen',
			'YU' => 'Yugoslavia',
			'ZM' => 'Zambia',
			'ZW' => 'Zimbabwe'
		);

		return apply_filters( 'rcp_avatax_country_options', $countries );

	}

}