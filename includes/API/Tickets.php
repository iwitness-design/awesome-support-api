<?php

namespace WPAS_API\API;

use WP_REST_Posts_Controller;
use WP_Error;

class Tickets extends WP_REST_Posts_Controller {


	public function __construct( $post_type ) {
		parent::__construct( $post_type );

		$this->namespace = wpas_api()->get_api_namespace();
	}

	/**
	 * Determines the allowed query_vars for a get_items() response and prepares
	 * them for WP_Query.
	 *
	 * @since 4.7.0
	 * @access protected
	 *
	 * @param array           $prepared_args Optional. Prepared WP_Query arguments. Default empty array.
	 * @param \WP_REST_Request $request       Optional. Full details about the request.
	 * @return array Items query arguments.
	 */
	protected function prepare_items_query( $prepared_args = array(), $request = null ) {
		$query_args = parent::prepare_items_query( $prepared_args, $request );

		$state = empty( $request['state'] ) ? 'open' : $request['state'];
		$meta_query = empty( $query_args['meta_query'] ) ? array() : $query_args['meta_query'];

		if ( in_array( 'any', $state ) ) {

			$meta_query['meta_query'][] = array(
				'relation' => 'OR',
				array(
					'key'     => '_wpas_status',
					'value'   => 'open',
					'compare' => '=',
					'type'    => 'CHAR',
				),
				array(
					'key'     => '_wpas_status',
					'value'   => 'closed',
					'compare' => '=',
					'type'    => 'CHAR',
				),
			);
		}

		if ( in_array( 'open', $state ) ) {

			$meta_query[] = array(
				'key'     => '_wpas_status',
				'value'   => 'open',
				'compare' => '=',
				'type'    => 'CHAR',
			);

		}

		if ( in_array( 'closed', $state ) ) {

			$meta_query[] = array(
				'key'     => '_wpas_status',
				'value'   => 'closed',
				'compare' => '=',
				'type'    => 'CHAR',
			);
		}

		$query_args['meta_query'] = $meta_query;

		return apply_filters( 'wpas_api_tickets_prepare_item_query', $query_args, $prepared_args, $request, $this );
	}

	/**
	 * Retrieves the query params for the posts collection.
	 *
	 * @since 4.7.0
	 * @access public
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {
		$query_params = parent::get_collection_params();

		$query_params['status'] = array(
			'default'           => 'any',
			'description'       => __( 'Limit result set to tickets assigned one or more statuses.', 'awesome-support-api' ),
			'type'              => 'array',
			'items'             => array(
				'enum'          => array_merge( array_keys( wpas_get_post_status() ), array( 'read', 'unread', 'any' ) ),
				'type'          => 'string',
			),
			'sanitize_callback' => array( $this, 'sanitize_ticket_param' ),
		);

		$query_params['state'] = array(
			'default'           => 'open',
			'description'       => __( 'Limit result set to tickets in the specified state.' ),
			'type'              => 'array',
			'items'             => array(
				'enum'          => array( 'open', 'closed', 'any' ),
				'type'          => 'string',
			),
			'sanitize_callback' => array( $this, 'sanitize_ticket_param' ),
		);

		$taxonomies = wp_list_filter( get_object_taxonomies( $this->post_type, 'objects' ), array( 'show_in_rest' => true ) );

		foreach ( $taxonomies as $taxonomy ) {
			$base = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;

			$query_params[ $base ] = array(
				/* translators: %s: taxonomy name */
				'description'       => sprintf( __( 'Limit result set to all items that have the specified term assigned in the %s taxonomy.' ), $base ),
				'type'              => 'array',
				'items'             => array(
					'type'          => 'integer',
				),
				'default'           => array(),
			);

			$query_params[ $base . '_exclude' ] = array(
				/* translators: %s: taxonomy name */
				'description' => sprintf( __( 'Limit result set to all items except those that have the specified term assigned in the %s taxonomy.' ), $base ),
				'type'        => 'array',
				'items'       => array(
					'type'    => 'integer',
				),
				'default'           => array(),
			);
		}

		/**
		 * Filter collection parameters for the posts controller.
		 *
		 * @param array   $query_params JSON Schema-formatted collection parameters.
		 * @param object  Tickets
		 */
		return apply_filters( "wpas_api_tickets_collection_params", $query_params, $this );
	}

	/**
	 * Sanitizes and validates a list of arguments against the provided attributes.
	 *
	 * @param  string|array    $statuses  One or more post statuses.
	 * @param  \WP_REST_Request $request   Full details about the request.
	 * @param  string          $parameter Additional parameter to pass to validation.
	 * @return array|WP_Error A list of valid statuses, otherwise WP_Error object.
	 */
	public function sanitize_ticket_param( $statuses, $request, $parameter ) {
		$items = wp_parse_slug_list( $statuses );

		// The default status is different in WP_REST_Attachments_Controller
		$attributes = $request->get_attributes();
		$default    = isset( $attributes['args'][ $parameter ]['default'] ) ? $attributes['args'][ $parameter ]['default'] : '' ;

		foreach ( $items as $item ) {
			if ( $item === $default ) {
				continue;
			}

			$result = rest_validate_request_arg( $item, $request, $parameter );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		return $items;
	}

}