<?php

function vgjpm_job_post_init() {
	register_post_type(
		'job-posts',
		array(
			'labels'                => array(
				'name'                  => __( 'Job posts', 'vk-google-job-posting-manager' ),
				'singular_name'         => __( 'Job posts', 'vk-google-job-posting-manager' ),
				'all_items'             => __( 'All Job posts', 'vk-google-job-posting-manager' ),
				'archives'              => __( 'Job posts Archives', 'vk-google-job-posting-manager' ),
				'attributes'            => __( 'Job posts Attributes', 'vk-google-job-posting-manager' ),
				'insert_into_item'      => __( 'Insert into job posts', 'vk-google-job-posting-manager' ),
				'uploaded_to_this_item' => __( 'Uploaded to this job posts', 'vk-google-job-posting-manager' ),
				'featured_image'        => _x( 'Featured Image', 'job-posts', 'vk-google-job-posting-manager' ),
				'set_featured_image'    => _x( 'Set featured image', 'job-posts', 'vk-google-job-posting-manager' ),
				'remove_featured_image' => _x( 'Remove featured image', 'job-posts', 'vk-google-job-posting-manager' ),
				'use_featured_image'    => _x( 'Use as featured image', 'job-posts', 'vk-google-job-posting-manager' ),
				'filter_items_list'     => __( 'Filter job posts list', 'vk-google-job-posting-manager' ),
				'items_list_navigation' => __( 'Job posts list navigation', 'vk-google-job-posting-manager' ),
				'items_list'            => __( 'Job posts list', 'vk-google-job-posting-manager' ),
				'new_item'              => __( 'New Job posts', 'vk-google-job-posting-manager' ),
				'add_new'               => __( 'Add New', 'vk-google-job-posting-manager' ),
				'add_new_item'          => __( 'Add New Job posts', 'vk-google-job-posting-manager' ),
				'edit_item'             => __( 'Edit Job posts', 'vk-google-job-posting-manager' ),
				'view_item'             => __( 'View Job posts', 'vk-google-job-posting-manager' ),
				'view_items'            => __( 'View Job posts', 'vk-google-job-posting-manager' ),
				'search_items'          => __( 'Search job posts', 'vk-google-job-posting-manager' ),
				'not_found'             => __( 'No job posts found', 'vk-google-job-posting-manager' ),
				'not_found_in_trash'    => __( 'No job posts found in trash', 'vk-google-job-posting-manager' ),
				'parent_item_colon'     => __( 'Parent Job posts:', 'vk-google-job-posting-manager' ),
				'menu_name'             => __( 'Job posts', 'vk-google-job-posting-manager' ),
			),
			'public'                => true,
			'hierarchical'          => false,
			'show_ui'               => true,
			'show_in_nav_menus'     => true,
			'supports'              => array(
				'title',
				'editor',
				'author',
				'thumbnail',
				'excerpt',
				'revisions',
				'custom-fields',
			),
			'has_archive'           => true,
			'rewrite'               => true,
			'query_var'             => true,
			'menu_icon'             => 'dashicons-admin-post',
			'show_in_rest'          => true,
			'rest_base'             => 'job-posts',
			'rest_controller_class' => 'WP_REST_Posts_Controller',
		)
	);
}
add_action( 'init', 'vgjpm_job_post_init' );

/**
 *
 * @param  array $messages Post updated messages.
 *
 * @return array Messages for the `job_posts` post type.
 */
function vgjpm_posts_updated_messages( $messages ) {
	global $post;

	$permalink = get_permalink( $post );

	$messages['job-posts'] = array(
		0  => '', // Unused. Messages start at index 1.
		/* translators: %s: post permalink */
		1  => sprintf( __( 'Job posts updated <a target="_blank" href="%s">View job posts</a>', 'vk-google-job-posting-manager' ), esc_url( $permalink ) ),
		2  => __( 'Custom field updated', 'vk-google-job-posting-manager' ),
		3  => __( 'Custom field deleted', 'vk-google-job-posting-manager' ),
		4  => __( 'Job posts updated', 'vk-google-job-posting-manager' ),
		/* translators: %s: date and time of the revision */
		5  => isset( $_GET['revision'] ) ? sprintf( __( 'Job posts restored to revision from %s', 'vk-google-job-posting-manager' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		/* translators: %s: post permalink */
		6  => sprintf( __( 'Job posts published <a href="%s">View job posts</a>', 'vk-google-job-posting-manager' ), esc_url( $permalink ) ),
		7  => __( 'Job posts saved', 'vk-google-job-posting-manager' ),
		/* translators: %s: post permalink */
		8  => sprintf( __( 'Job posts submitted <a target="_blank" href="%s">Preview job posts</a>', 'vk-google-job-posting-manager' ), esc_url( add_query_arg( 'preview', 'true', $permalink ) ) ),
		/* translators: 1: Publish box date format, see https://secure.php.net/date 2: Post permalink */
		9  => sprintf(
			__( 'Job posts scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview job posts</a>', 'vk-google-job-posting-manager' ),
			date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ),
			esc_url( $permalink )
		),
		/* translators: %s: post permalink */
		10 => sprintf( __( 'Job posts draft updated <a target="_blank" href="%s">Preview job posts</a>', 'vk-google-job-posting-manager' ), esc_url( add_query_arg( 'preview', 'true', $permalink ) ) ),
	);

	return $messages;
}
add_filter( 'post_updated_messages', 'vgjpm_posts_updated_messages' );
