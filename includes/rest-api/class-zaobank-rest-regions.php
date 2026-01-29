<?php
/**
 * REST API: Regions endpoints.
 */
class ZAOBank_REST_Regions extends ZAOBank_REST_Controller {

	public function register_routes() {
		register_rest_route($this->namespace, '/regions', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array($this, 'get_regions'),
			'permission_callback' => '__return_true'
		));
	}

	public function get_regions($request) {
		$terms = get_terms(array(
			'taxonomy' => 'zaobank_region',
			'hide_empty' => false,
			'hierarchical' => true
		));

		if (is_wp_error($terms)) {
			return $this->error_response('regions_error', $terms->get_error_message());
		}

		$regions = array_map(function($term) {
			return array(
				'id' => $term->term_id,
				'name' => $term->name,
				'slug' => $term->slug,
				'parent' => $term->parent,
				'count' => $term->count
			);
		}, $terms);

		return $this->success_response($regions);
	}
}