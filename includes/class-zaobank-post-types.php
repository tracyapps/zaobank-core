<?php
/**
 * Register custom post types.
 */
class ZAOBank_Post_Types {

	/**
	 * Register all custom post types.
	 */
	public function register_post_types() {
		$this->register_job_post_type();
	}

	/**
	 * Register the Job custom post type.
	 */
	private function register_job_post_type() {
		$labels = array(
			'name'                  => _x('Jobs', 'Post type general name', 'zaobank'),
			'singular_name'         => _x('Job', 'Post type singular name', 'zaobank'),
			'menu_name'             => _x('Time Bank Jobs', 'Admin Menu text', 'zaobank'),
			'name_admin_bar'        => _x('Job', 'Add New on Toolbar', 'zaobank'),
			'add_new'               => __('Add New', 'zaobank'),
			'add_new_item'          => __('Add New Job', 'zaobank'),
			'new_item'              => __('New Job', 'zaobank'),
			'edit_item'             => __('Edit Job', 'zaobank'),
			'view_item'             => __('View Job', 'zaobank'),
			'all_items'             => __('All Jobs', 'zaobank'),
			'search_items'          => __('Search Jobs', 'zaobank'),
			'parent_item_colon'     => __('Parent Jobs:', 'zaobank'),
			'not_found'             => __('No jobs found.', 'zaobank'),
			'not_found_in_trash'    => __('No jobs found in Trash.', 'zaobank'),
			'featured_image'        => _x('Job Cover Image', 'Overrides featured image', 'zaobank'),
			'set_featured_image'    => _x('Set cover image', 'Overrides set featured image', 'zaobank'),
			'remove_featured_image' => _x('Remove cover image', 'Overrides remove featured image', 'zaobank'),
			'use_featured_image'    => _x('Use as cover image', 'Overrides use featured image', 'zaobank'),
			'archives'              => _x('Job archives', 'The post type archive label', 'zaobank'),
			'insert_into_item'      => _x('Insert into job', 'Overrides insert into item', 'zaobank'),
			'uploaded_to_this_item' => _x('Uploaded to this job', 'Overrides uploaded to this item', 'zaobank'),
			'filter_items_list'     => _x('Filter jobs list', 'Screen reader text', 'zaobank'),
			'items_list_navigation' => _x('Jobs list navigation', 'Screen reader text', 'zaobank'),
			'items_list'            => _x('Jobs list', 'Screen reader text', 'zaobank'),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array('slug' => 'jobs'),
			'capability_type'    => array('timebank_job', 'timebank_jobs'),
			'map_meta_cap'       => true,
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => 20,
			'menu_icon'          => 'dashicons-clock',
			'show_in_rest'       => true,
			'rest_base'          => 'timebank_jobs',
			'supports'           => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments'),
			'taxonomies'         => array('zaobank_region'),
		);

		register_post_type('timebank_job', $args);
	}
}