<?php

namespace RCP_Avatax\Admin;

class License {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * Only make one instance of \RCP_Avatax\License
	 *
	 * @return License
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof License ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	protected function __construct() {
		add_action( 'admin_init', array( $this, 'check_license'      ) );
		add_action( 'admin_init', array( $this, 'activate_license'   ) );
		add_action( 'admin_init', array( $this, 'deactivate_license' ) );
	}

	/**
	 * Handle License activation
	 */
	public function activate_license() {

		// listen for our activate button to be clicked
		if ( ! isset( $_POST['rcp_avatax_license_activate'], $_POST['rcp_avatax_nonce'] ) ) {
			return;
		}

		// run a quick security check
		if ( ! check_admin_referer( 'rcp_avatax_nonce', 'rcp_avatax_nonce' ) ) {
			return;
		} // get out if we didn't click the Activate button

		// retrieve the license from the database
		$license = trim( get_option( 'rcp_avatax_license_key' ) );

		// data to send in our API request
		$api_params = array(
			'edd_action' => 'activate_license',
			'license'    => $license,
			'item_name'  => urlencode( RCPTX_ITEM_NAME ), // the name of our product in EDD
			'url'        => home_url()
		);

		// Call the custom API.
		$response = wp_remote_post( RCPTX_STORE_URL, array(
			'timeout'   => 15,
			'sslverify' => false,
			'body'      => $api_params
		) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) ) {
			return;
		}

		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		// $license_data->license will be either "valid" or "invalid"
		update_option( 'rcp_avatax_license_status', $license_data->license );
		delete_transient( 'rcp_avatax_license_check' );

	}

	/**
	 * Handle License deactivation
	 */
	public function deactivate_license() {

		// listen for our activate button to be clicked
		if ( ! isset( $_POST['rcp_avatax_license_deactivate'], $_POST['rcp_avatax_nonce'] ) ) {
			return;
		}

		// run a quick security check
		if ( ! check_admin_referer( 'rcp_avatax_nonce', 'rcp_avatax_nonce' ) ) {
			return;
		}

		// retrieve the license from the database
		$license = trim( get_option( 'rcp_avatax_license_key' ) );

		// data to send in our API request
		$api_params = array(
			'edd_action' => 'deactivate_license',
			'license'    => $license,
			'item_name'  => urlencode( RCPTX_ITEM_NAME ), // the name of our product in EDD
			'url'        => home_url()
		);

		// Call the custom API.
		$response = wp_remote_post( RCPTX_STORE_URL, array(
			'timeout'   => 15,
			'sslverify' => false,
			'body'      => $api_params
		) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) ) {
			return;
		}

		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		// $license_data->license will be either "deactivated" or "failed"
		if ( $license_data->license == 'deactivated' ) {
			delete_option( 'rcp_avatax_license_status' );
			delete_transient( 'rcp_avatax_license_check' );
		}

	}

	/**
	 * Check license
	 *
	 * @since       1.0.0
	 */
	public function check_license() {

		// Don't fire when saving settings
		if ( ! empty( $_POST['rcp_avatax_nonce'] ) ) {
			return;
		}

		$license = get_option( 'rcp_avatax_license_key' );
		$status  = get_transient( 'rcp_avatax_license_check' );

		if ( $status === false && $license ) {

			$api_params = array(
				'edd_action' => 'check_license',
				'license'    => trim( $license ),
				'item_name'  => urlencode( RCP_AVATAX_ITEM_NAME ),
				'url'        => home_url()
			);

			$response = wp_remote_post( RCP_AVATAX_STORE_URL, array( 'timeout' => 35, 'sslverify' => false, 'body' => $api_params ) );

			if ( is_wp_error( $response ) ) {
				return;
			}

			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			$status = $license_data->license;

			update_option( 'rcp_avatax_license_status', $status );

			set_transient( 'rcp_avatax_license_check', $license_data->license, DAY_IN_SECONDS );

			if ( $status !== 'valid' ) {
				delete_option( 'rcp_avatax_license_status' );
			}
		}

	}

}