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

		add_filter( 'rcp_get_template_part', array( $this, 'short_card_form' ), 10, 3 );
	}

	public function address_fields( $user_id = NULL ) {

		if( !$user_id ) {
			$user_id = get_current_user_id();
		}

		$countries       = apply_filters( 'rcp_avatax_country_list', Codes::$countries );
		$default_country = apply_filters( 'rcp_avatax_country_default', 'US' );
		$user_country    = get_user_meta( $user_id, 'rcp_country', true );
		$user_vat_number = get_user_meta( $user_id, 'rcp_vat_id', true ); ?>

		<?php if ( apply_filters( 'rcp_avatax_form_styling_show', true ) ) : ?>
			<style>
				@media screen and (min-width:728px) {
					#rcp_card_state_wrap,
					#rcp_card_country_wrap {
						width: 49%;
						float: left;
					}

					#rcp_card_country_wrap  {
						float: right;
					}
				}
			</style>
		<?php endif; ?>

		<fieldset class="rcp_avatax_fieldset">

			<legend><?php echo apply_filters( 'rcp_avatax_address_title', __( 'Billing Address', 'rcp-avatax' ) ); ?></legend>

			<?php if ( apply_filters( 'rcp_avatax_show_vat', rcp_avatax()::get_settings( 'show_vat', false ) ) ): ?>
				<p id="rcp_vat_id_wrap">
					<label for="rcp_vat_id"><?php _e( 'VAT ID', 'rcptx' ); ?></label>
					<input name="rcp_vat_id" id="rcp_vat_id" type="text" value="<?php echo esc_attr( $user_vat_number );?>" />
				</p>
			<?php endif; ?>

			<p id="rcp_card_address_wrap">
				<label for="rcp_card_address"><?php echo apply_filters ( 'rcp_card_address_label', __( 'Address Line 1', 'rcp-avatax' ) ); ?></label>
				<input name="rcp_card_address" id="rcp_card_address" class="required rcp_card_address card-address" type="text" <?php if( isset( $_POST['rcp_card_address'] ) ) { echo 'value="' . esc_attr( $_POST['rcp_card_address'] ) . '"'; } ?>/>
			</p>
			<p id="rcp_card_address_2_wrap">
				<label for="rcp_card_address_2"><?php echo apply_filters ( 'rcp_card_address_2_label', __( 'Address Line 2', 'rcp-avatax' ) ); ?></label>
				<input name="rcp_card_address_2" id="rcp_card_address_2" class="rcp_card_address_2 card-address-2" type="text" <?php if( isset( $_POST['rcp_card_address_2'] ) ) { echo 'value="' . esc_attr( $_POST['rcp_card_address_2'] ) . '"'; } ?>/>
			</p>
			<p id="rcp_card_city_wrap">
				<label for="rcp_card_city"><?php echo apply_filters ( 'rcp_card_city_label', __( 'City', 'rcp-avatax' ) ); ?></label>
				<input name="rcp_card_city" id="rcp_card_city" class="required rcp_card_city card-city" type="text" <?php if( isset( $_POST['rcp_card_city'] ) ) { echo 'value="' . esc_attr( $_POST['rcp_card_city'] ) . '"'; } ?>/>
			</p>
			<p id="rcp_card_state_wrap">
				<label for="rcp_card_state"><?php echo apply_filters ( 'rcp_card_state_label', __( 'State or Providence', 'rcp-avatax' ) ); ?></label>
				<input name="rcp_card_state" id="rcp_card_state" class="required rcp_card_state card-state" type="text" <?php if( isset( $_POST['rcp_card_state'] ) ) { echo 'value="' . esc_attr( $_POST['rcp_card_state'] ) . '"'; } ?>/>
			</p>
			<p id="rcp_card_country_wrap">
				<label for="rcp_card_country"><?php echo apply_filters ( 'rcp_card_country_label', __( 'Country', 'rcp-avatax' ) ); ?></label>
				<select id="rcp_card_country" name="rcp_card_country">
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

	/**
	 * Don't use the full card form when we are printing out the address fields separate
	 *
	 * @param $templates
	 * @param $slug
	 * @param $name
	 *
	 * @return mixed
	 */
	public function short_card_form( $templates, $slug, $name ) {

		if ( 'card-form-full' !== $slug ) {
			return $templates;
		}

		$key = array_search( 'card-form-full.php', $templates );

		if ( false === $key ) {
			return $templates;
		}

		$templates[ $key ] = 'card-form.php';

		return $templates;

	}

}