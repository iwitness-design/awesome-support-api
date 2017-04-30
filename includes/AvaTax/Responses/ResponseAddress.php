<?php
/**
 * Define the ResponseAddress class
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace RCP_Avatax\AvaTax\Responses;

defined( 'ABSPATH' ) or exit;

/**
 * The AvaTax API address response class.
 *
 * @since 1.0.0
 */
class ResponseAddress extends Response {


	/**
	 * Get the normalized address data.
	 *
	 * @since 1.0.0
	 * @return array The normalized address data.
	 */
	public function get_normalized_address() {

		$data = $this->Address;

		// The AvaTax API returns addresses with lines 1 & 2 reversed if a line 2 was included.
		if ( isset( $data->Line2 ) ) {
			$line_1 = $data->Line2;
			$line_2 = $data->Line1;
		} else {
			$line_1 = $data->Line1;
			$line_2 = '';
		}

		// Map the API response to their proper keys
		$address = array(
			'address_1' => $line_1,
			'address_2' => $line_2,
			'city'      => $data->City,
			'state'     => $data->Region,
			'country'   => $data->Country,
			'postcode'  => $data->PostalCode,
		);

		// Make sure the address values are squeaky clean
		$address = array_map( 'wc_clean', $address );

		return $address;
	}
}
