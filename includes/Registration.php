<?php

namespace RCP_Avatax;

use RCP_Avatax\AvaTax\API;

class Registration {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * Only make one instance of \RCP_Avatax\Registration
	 *
	 * @return Registration
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof Registration ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	protected function __construct() {
		add_action( 'rcp_setup_registration', array( $this, 'calculate_tax' ), 500 );
	}

	public function calculate_tax() {

		if ( empty( $_POST['rcp_card_address'] ) ) {
			return;
		}

		$request = rcp_avatax()::calculate_registration_tax();

		if ( $request instanceof \SkilledCode\Exception ) {
			return;
		}

		if ( empty( $request->response_data->totalTaxCalculated ) ) {
			return;
		}

		rcp_get_registration()->add_fee( $request->response_data->totalTaxCalculated, __( 'Tax', 'rcp-avatax' ), true, false );
		rcp_get_registration()->add_fee( $request->response_data->totalTaxCalculated, __( 'Tax Recurring', 'rcp-avatax' ), false, false );

		return;

		$address = array(
			'type'    => 'SalesOrder',
			'companyCode' => 'DEFAULT',
			'date'        => date( 'r', time() ),
			'customerCode' => 99,
			'addresses' => array(
				'SingleLocation' => array(
					'line1'   => '102 S Bogart Ave',
					'city'    => 'Granite Falls',
					'region'  => 'WA',
					'country' => 'US',
				),
			),
			'lines' => array(
				array(
					'number'   => '1',
					'quantity' => 1,
					'amount'  => 35,
					'itemCode' => '10 Dollar',
				),
			),
		);

		$args = array(
			'body' => json_encode( $address ),
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( rcp_avatax()::get_settings( 'avatax_account_number' ) . ':' . rcp_avatax()::get_settings( 'avatax_license_key' ) ),
				'Content-Type' => 'application/json; charset=utf-8',
			),
		);

		$response = wp_safe_remote_post( 'https://sandbox-rest.avatax.com/api/v2/transactions/create/', $args );
//		$response = wp_safe_remote_post( 'https://sandbox-rest.avatax.com/api/v2/addresses/resolve', $args );
//		$response = wp_safe_remote_get( add_query_arg( $address, 'https://sandbox-rest.avatax.com/api/v2/taxrates/byaddress' ), $args );

		$response = wp_remote_retrieve_body( $response );

		$response = json_decode( $response );

		$service_url    = rcp_avatax()::get_service_url();
		$account_number = rcp_avatax()::get_settings( 'avatax_account_number' );
		$license_key    = rcp_avatax()::get_settings( 'avatax_license_key' );

		return;
	}

}