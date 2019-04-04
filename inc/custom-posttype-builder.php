<?php

function job_posts_init() {
	register_post_type( 'job-posts', array(
		'labels'                => array(
			'name'                  => __( 'Job posts', 'vk-job-posting' ),
			'singular_name'         => __( 'Job posts', 'vk-job-posting' ),
			'all_items'             => __( 'All Job posts', 'vk-job-posting' ),
			'archives'              => __( 'Job posts Archives', 'vk-job-posting' ),
			'attributes'            => __( 'Job posts Attributes', 'vk-job-posting' ),
			'insert_into_item'      => __( 'Insert into job posts', 'vk-job-posting' ),
			'uploaded_to_this_item' => __( 'Uploaded to this job posts', 'vk-job-posting' ),
			'featured_image'        => _x( 'Featured Image', 'job-posts', 'vk-job-posting' ),
			'set_featured_image'    => _x( 'Set featured image', 'job-posts', 'vk-job-posting' ),
			'remove_featured_image' => _x( 'Remove featured image', 'job-posts', 'vk-job-posting' ),
			'use_featured_image'    => _x( 'Use as featured image', 'job-posts', 'vk-job-posting' ),
			'filter_items_list'     => __( 'Filter job posts list', 'vk-job-posting' ),
			'items_list_navigation' => __( 'Job posts list navigation', 'vk-job-posting' ),
			'items_list'            => __( 'Job posts list', 'vk-job-posting' ),
			'new_item'              => __( 'New Job posts', 'vk-job-posting' ),
			'add_new'               => __( 'Add New', 'vk-job-posting' ),
			'add_new_item'          => __( 'Add New Job posts', 'vk-job-posting' ),
			'edit_item'             => __( 'Edit Job posts', 'vk-job-posting' ),
			'view_item'             => __( 'View Job posts', 'vk-job-posting' ),
			'view_items'            => __( 'View Job posts', 'vk-job-posting' ),
			'search_items'          => __( 'Search job posts', 'vk-job-posting' ),
			'not_found'             => __( 'No job posts found', 'vk-job-posting' ),
			'not_found_in_trash'    => __( 'No job posts found in trash', 'vk-job-posting' ),
			'parent_item_colon'     => __( 'Parent Job posts:', 'vk-job-posting' ),
			'menu_name'             => __( 'Job posts', 'vk-job-posting' ),
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
			'custom-fields'
		),
		'has_archive'           => true,
		'rewrite'               => true,
		'query_var'             => true,
		'menu_icon'             => 'dashicons-admin-post',
		'show_in_rest'          => true,
		'rest_base'             => 'job-posts',
		'rest_controller_class' => 'WP_REST_Posts_Controller',
	) );

}
add_action( 'init', 'job_posts_init' );

/**
 *
 * @param  array $messages Post updated messages.
 *
 * @return array Messages for the `job_posts` post type.
 */
function vk_gjpm_posts_updated_messages( $messages ) {
	global $post;

	$permalink = get_permalink( $post );

	$messages['job-posts'] = array(
		0  => '', // Unused. Messages start at index 1.
		/* translators: %s: post permalink */
		1  => sprintf( __( 'Job posts updated. <a target="_blank" href="%s">View job posts</a>', 'vk-job-posting' ), esc_url( $permalink ) ),
		2  => __( 'Custom field updated.', 'vk-job-posting' ),
		3  => __( 'Custom field deleted.', 'vk-job-posting' ),
		4  => __( 'Job posts updated.', 'vk-job-posting' ),
		/* translators: %s: date and time of the revision */
		5  => isset( $_GET['revision'] ) ? sprintf( __( 'Job posts restored to revision from %s', 'vk-job-posting' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		/* translators: %s: post permalink */
		6  => sprintf( __( 'Job posts published. <a href="%s">View job posts</a>', 'vk-job-posting' ), esc_url( $permalink ) ),
		7  => __( 'Job posts saved.', 'vk-job-posting' ),
		/* translators: %s: post permalink */
		8  => sprintf( __( 'Job posts submitted. <a target="_blank" href="%s">Preview job posts</a>', 'vk-job-posting' ), esc_url( add_query_arg( 'preview', 'true', $permalink ) ) ),
		/* translators: 1: Publish box date format, see https://secure.php.net/date 2: Post permalink */
		9  => sprintf( __( 'Job posts scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview job posts</a>', 'vk-job-posting' ),
			date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( $permalink ) ),
		/* translators: %s: post permalink */
		10 => sprintf( __( 'Job posts draft updated. <a target="_blank" href="%s">Preview job posts</a>', 'vk-job-posting' ), esc_url( add_query_arg( 'preview', 'true', $permalink ) ) ),
	);

	return $messages;
}
add_filter( 'post_updated_messages', 'vk_gjpm_posts_updated_messages' );
