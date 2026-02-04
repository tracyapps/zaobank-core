<?php
/**
 * Register custom taxonomies.
 */
class ZAOBank_Taxonomies {

	/**
	 * Register all custom taxonomies.
	 */
	public function register_taxonomies() {
		$this->register_region_taxonomy();
		$this->register_job_type_taxonomy();
	}

	/**
	 * Register the Region taxonomy.
	 */
	private function register_region_taxonomy() {
		$labels = array(
			'name'                       => _x('Regions', 'taxonomy general name', 'zaobank'),
			'singular_name'              => _x('Region', 'taxonomy singular name', 'zaobank'),
			'search_items'               => __('Search Regions', 'zaobank'),
			'popular_items'              => __('Popular Regions', 'zaobank'),
			'all_items'                  => __('All Regions', 'zaobank'),
			'parent_item'                => __('Parent Region', 'zaobank'),
			'parent_item_colon'          => __('Parent Region:', 'zaobank'),
			'edit_item'                  => __('Edit Region', 'zaobank'),
			'update_item'                => __('Update Region', 'zaobank'),
			'add_new_item'               => __('Add New Region', 'zaobank'),
			'new_item_name'              => __('New Region Name', 'zaobank'),
			'separate_items_with_commas' => __('Separate regions with commas', 'zaobank'),
			'add_or_remove_items'        => __('Add or remove regions', 'zaobank'),
			'choose_from_most_used'      => __('Choose from the most used regions', 'zaobank'),
			'not_found'                  => __('No regions found.', 'zaobank'),
			'menu_name'                  => __('Regions', 'zaobank'),
		);

		$args = array(
			'hierarchical'          => true,
			'labels'                => $labels,
			'show_ui'               => true,
			'show_admin_column'     => true,
			'show_in_rest'          => true,
			'rest_base'             => 'regions',
			'query_var'             => true,
			'rewrite'               => array('slug' => 'region'),
			'capabilities'          => array(
				'manage_terms' => 'manage_zaobank_regions',
				'edit_terms'   => 'manage_zaobank_regions',
				'delete_terms' => 'manage_zaobank_regions',
				'assign_terms' => 'edit_timebank_jobs',
			),
		);

		register_taxonomy('zaobank_region', array('timebank_job'), $args);
	}

	/**
	 * Register the Job Type taxonomy.
	 */
	private function register_job_type_taxonomy() {
		$labels = array(
			'name'                       => _x('Job Types', 'taxonomy general name', 'zaobank'),
			'singular_name'              => _x('Job Type', 'taxonomy singular name', 'zaobank'),
			'search_items'               => __('Search Job Types', 'zaobank'),
			'popular_items'              => __('Popular Job Types', 'zaobank'),
			'all_items'                  => __('All Job Types', 'zaobank'),
			'edit_item'                  => __('Edit Job Type', 'zaobank'),
			'update_item'                => __('Update Job Type', 'zaobank'),
			'add_new_item'               => __('Add New Job Type', 'zaobank'),
			'new_item_name'              => __('New Job Type Name', 'zaobank'),
			'separate_items_with_commas' => __('Separate job types with commas', 'zaobank'),
			'add_or_remove_items'        => __('Add or remove job types', 'zaobank'),
			'choose_from_most_used'      => __('Choose from the most used job types', 'zaobank'),
			'not_found'                  => __('No job types found.', 'zaobank'),
			'menu_name'                  => __('Job Types', 'zaobank'),
		);

		$args = array(
			'hierarchical'          => false,
			'labels'                => $labels,
			'show_ui'               => true,
			'show_admin_column'     => true,
			'show_in_rest'          => true,
			'rest_base'             => 'job-types',
			'query_var'             => true,
			'rewrite'               => array('slug' => 'job-type'),
		);

		register_taxonomy('zaobank_job_type', array('timebank_job'), $args);
	}
}