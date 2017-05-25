<?php

namespace WPAS_API\API;

use WPAS_API\API\TicketBase;
use WP_REST_Posts_Controller;
use WP_Error;

class Tickets extends TicketBase {

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

		// set assignee
		if ( ! empty( $request['assignee'] ) ) {
			$meta_query[] = array(
				'key'     => '_wpas_assignee',
				'value'   => absint( $request['assignee'] ),
				'compare' => '=',
				'type'    => 'NUMERIC',
			);
		}

		if ( ! isset( $meta_query['relation'] ) ) {
			$meta_query['relation'] = 'AND';
		}

		$query_args['meta_query'] = $meta_query;

		return apply_filters( "wpas_api_{$this->rest_base}_prepare_items_query", $query_args, $prepared_args, $request, $this );
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
		$user         = wp_get_current_user();

		// set the default assignee to the current user, if the current user is an agent
		$query_params['assignee'] = array(
			'default'           => 0,
			'description'       => __( 'Limit result set to tickets assigned to this user id.' ),
			'type'              => 'integer',
		);

		// if the user is logged in then set the assignee to the user ID if the user is an agent
		// if the user is not an agent then set the default author to the user ID to show the user's tickets
		if ( $user->has_cap( 'edit_ticket' ) ) {
			$query_params['assignee']['default'] = $user->ID;
		} elseif ( $user->ID ) {
			$query_params['author']['default'] = $user->ID;
		}

		$query_params['status']['items']['enum'] = array_merge( array_keys( wpas_get_post_status() ), array( 'read', 'unread', 'any' ) );

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
		return apply_filters( "wpas_api_{$this->rest_base}_get_collection_params", $query_params, $this );
	}

	/**
	 * Prepares links for the request.
	 *
	 * @param \WP_Post $post Post object.
	 * @return array Links for the given post.
	 */
	protected function prepare_links( $post ) {
		$history         = get_post_type_object( 'ticket_history' );
		$replies         = get_post_type_object( 'ticket_reply' );
		$base            = sprintf( '%s/%s', $this->namespace, $this->rest_base );
		$attachments_url = rest_url( 'wp/v2/media' );
		$attachments_url = add_query_arg( 'parent', $post->ID, $attachments_url );

		// Entity meta.
		$links = array(
			'self'                         => array(
				'href' => rest_url( trailingslashit( $base ) . $post->ID ),
			),
			'collection'                   => array(
				'href' => rest_url( $base ),
			),
			'replies'                      => array(
				'href'       => rest_url( trailingslashit( $base ) . $post->ID . '/' . $replies->rest_base ),
				'embeddable' => true,
			),
			'history'                      => array(
				'href'       => rest_url( trailingslashit( $base ) . $post->ID . '/' . $history->rest_base ),
				'embeddable' => true,
			),
			'author'                       => array(
				'href'       => rest_url( 'wp/v2/users/' . $post->post_author ),
				'embeddable' => true,
			),
			'https://api.w.org/attachment' => array(
				'href'       => $attachments_url,
				'embeddable' => true,
			),
			'about'                        => array(
				'href' => rest_url( 'wp/v2/types/' . $this->post_type ),
			),
		);

		$taxonomies = get_object_taxonomies( $post->post_type );

		if ( ! empty( $taxonomies ) ) {
			$links['https://api.w.org/term'] = array();

			foreach ( $taxonomies as $tax ) {
				$taxonomy_obj = get_taxonomy( $tax );

				// Skip taxonomies that are not public.
				if ( empty( $taxonomy_obj->show_in_rest ) ) {
					continue;
				}

				$tax_base = ! empty( $taxonomy_obj->rest_base ) ? $taxonomy_obj->rest_base : $tax;

				$terms_url = add_query_arg(
					'post',
					$post->ID,
					rest_url( 'wp/v2/' . $tax_base )
				);

				$links['https://api.w.org/term'][] = array(
					'href'       => $terms_url,
					'taxonomy'   => $tax,
					'embeddable' => true,
				);
			}
		}

		return apply_filters( "wpas_api_{$this->rest_base}_prepare_links", $links, $post, $this );
	}

}