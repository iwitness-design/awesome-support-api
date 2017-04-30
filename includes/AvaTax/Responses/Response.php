<?php
/**
 * Define the Response class
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace RCP_Avatax\AvaTax\Responses;

use SkilledCode\RequestAPI\ResponseJSON;

defined( 'ABSPATH' ) or exit;

/**
 * The AvaTax API request class.
 *
 * @since 1.0.0
 */
class Response extends ResponseJSON {


	/**
	 * Get the details of the voided transaction.
	 *
	 * @since 1.0.0
	 * @return array The voided transaction data
	 */
	public function get_void_data() {

		$data = $this->CancelTaxResult;

		$data = array(
			'transaction_id' => $data->TransactionId,
			'document_id'    => $data->DocId,
		);

		return $data;
	}
}
