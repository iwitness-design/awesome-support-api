<?php

namespace WPAS_API\Auth;

// Load the parent class if it doesn't exist.
if ( ! class_exists( 'WP_User' ) ) {
	require_once ABSPATH . 'wp-includes/class-wp-user.php';
}

class User extends \WP_User {

	/**
	 * The user meta application password key.
	 * @type string
	 */
	const USERMETA_KEY_API_PASSWORDS = '_api_passwords';

	/**
	 * The length of generated application passwords.
	 *
	 * @type integer
	 */
	const PW_LENGTH = 24;

	/**
	 * Get a users application passwords.
	 *
	 * @return array
	 */
	public function get_api_passwords() {
		$passwords = get_user_meta( $this->ID, self::USERMETA_KEY_API_PASSWORDS, true );

		if ( ! is_array( $passwords ) ) {
			return array();
		}

		return $passwords;
	}

	/**
	 * Set a users application passwords.
	 *
	 * @param array $passwords Application passwords.
	 *
	 * @return bool
	 */
	public function set_api_passwords( $passwords ) {
		return update_user_meta( $this->ID, self::USERMETA_KEY_API_PASSWORDS, $passwords );
	}

	/**
	 * Generate a new application password.
	 *
	 * @param string $name    Password name.
	 * @return array          The first key in the array is the new password, the second is its row in the table.
	 */
	public function create_new_api_password( $name ) {
		$new_password    = wp_generate_password( self::PW_LENGTH, false );
		$hashed_password = wp_hash_password( $new_password );

		$new_item = array(
			'name'      => $name,
			'password'  => $hashed_password,
			'created'   => time(),
			'last_used' => null,
			'last_ip'   => null,
		);

		$passwords = $this->get_api_passwords();
		if ( ! $passwords ) {
			$passwords = array();
		}

		$passwords[] = $new_item;
		$this->set_api_passwords( $passwords );

		return array( $new_password, $new_item );
	}

	/**
	 * Delete a specified application password.
	 *
	 * @param string $slug The generated slug of the password in question.
	 * @return bool Whether the password was successfully found and deleted.
	 */
	public function delete_api_password( $slug ) {
		$passwords = $this->get_api_passwords();

		foreach ( $passwords as $key => $item ) {
			if ( self::password_unique_slug( $item ) === $slug ) {
				unset( $passwords[ $key ] );
				$this->set_api_passwords( $passwords );
				return true;
			}
		}

		// Specified Application Password not found!
		return false;
	}

	/**
	 * Deletes all application passwords for the given user.
	 *
	 * @return int   The number of passwords that were deleted.
	 */
	public function delete_all_api_passwords() {
		$passwords = $this->get_api_passwords();;

		if ( is_array( $passwords ) ) {
			$this->set_api_passwords( array() );
			return sizeof( $passwords );
		}

		return 0;
	}

	/**
	 * Check the user's API keys and validate the provided password against them
	 *
	 * @param $password - The password to validate
	 *
	 * @return bool - whether or not the user was authenticated
	 */
	public function authenticate( $password ) {

		$authenticated = false;

		/**
		 * Strip out anything non-alphanumeric. This is so passwords can be used with
		 * or without spaces to indicate the groupings for readability.
		 *
		 * Generated application passwords are exclusively alphanumeric.
		 */
		$password = preg_replace( '/[^a-z\d]/i', '', $password );

		$hashed_passwords = get_user_meta( $this->ID, self::USERMETA_KEY_API_PASSWORDS, true );

		// If there aren't any, there's nothing to return.  Avoid the foreach.
		if ( empty( $hashed_passwords ) ) {
			$hashed_passwords = array();
		}

		foreach ( $hashed_passwords as $key => $item ) {
			if ( wp_check_password( $password, $item['password'], $this->ID ) ) {
				$item['last_used']        = time();
				$item['last_ip']          = $_SERVER['REMOTE_ADDR'];
				$hashed_passwords[ $key ] = $item;
				update_user_meta( $this->ID, self::USERMETA_KEY_API_PASSWORDS, $hashed_passwords );

				$authenticated = true;
				break;
			}
		}

		return apply_filters( 'wpas_user_authenticate', $authenticated, $password, $this );
	}

	/**
	 * Generate a unique repeatable slug from the hashed password, name, and when it was created.
	 *
	 * @param array $item The current item.
	 * @return string
	 */
	public static function password_unique_slug( $item ) {
		$concat = $item['name'] . '|' . $item['password'] . '|' . $item['created'];
		$hash   = md5( $concat );
		return substr( $hash, 0, 12 );
	}

	/**
	 * Sanitize and then split a password into smaller chunks.
	 *
	 * @param string $raw_password Users raw password.
	 * @return string
	 */
	public static function chunk_password( $raw_password ) {
		$raw_password = preg_replace( '/[^a-z\d]/i', '', $raw_password );
		return trim( chunk_split( $raw_password, 4, ' ' ) );
	}

}