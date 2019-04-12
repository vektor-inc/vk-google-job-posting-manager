<?php
/**
 * Functions to register client-side assets (scripts and stylesheets) for the
 * Gutenberg block.
 *
 * @package vk-google-job-posting-manager
 */

/**
 * Registers all block assets so that they can be enqueued through Gutenberg in
 * the corresponding context.
 *
 * @see https://wordpress.org/gutenberg/handbook/blocks/writing-your-first-block-type/#enqueuing-block-scripts
 */

require_once( dirname( dirname( __FILE__ ) ) . '/vk-google-job-posting-manager.php' );
require_once( dirname( dirname( __FILE__ ) ) . '/inc/custom-field-builder/custom-field-builder-config.php' );

function vgjpm_block_init() {
	// Skip block registration if Gutenberg is not enabled/merged.
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}
	$dir = dirname( __FILE__ );

	$index_js = 'create-table/build.js';
	wp_register_script(
		'vk-google-job-posting-manager-block-editor',
		plugins_url( $index_js, __FILE__ ),
		array(
			'wp-blocks',
			'wp-i18n',
			'wp-element',
			'wp-components',
			'wp-editor',
		),
		filemtime( "$dir/$index_js" )
	);

	$editor_css = 'create-table/style.css';
	wp_register_style(
		'vk-google-job-posting-manager-block-editor',
		plugins_url( $editor_css, __FILE__ ),
		array(),
		filemtime( "$dir/$editor_css" )
	);

	$style_css = 'create-table/style.css';
	wp_register_style(
		'vk-google-job-posting-manager-block',
		plugins_url( $style_css, __FILE__ ),
		array(),
		filemtime( "$dir/$style_css" )
	);

	wp_set_script_translations( 'vk-google-job-posting-manager-block-editor', 'vk-google-job-posting-manager', VGJPM_DIR . '/languages/' );

	if ( function_exists( 'wp_set_script_translations' ) ) {
		wp_set_script_translations( 'vk-google-job-posting-manager-block-editor', 'vk-google-job-posting-manager', VGJPM_DIR . '/languages/' );
	}

	register_block_type(
		'vk-google-job-posting-manager/create-table', array(
			'editor_script'   => 'vk-google-job-posting-manager-block-editor',
			'editor_style'    => 'vk-google-job-posting-manager-block-editor',
			'style'           => 'vk-google-job-posting-manager-block',
			'attributes'      => [
				'style'     => [
					'type'    => 'string',
					'default' => 'default',
				],
				'className' => [
					'type'    => 'string',
					'default' => '',
				],
				'post_id' => [
					'type'    => 'number',
				],
			],
			'render_callback' => function ( $attributes ) {
				return vgjpm_render_job_posting_table( $attributes['post_id'],$attributes['style'], $attributes['className'] );
			},
		)
	);

}
add_action( 'init', 'vgjpm_block_init' );

/**
 *
 * @param $custom_fields
 * @param $custom_fileds_key
 *
 * @return array
 */

function vgjpm_get_label( $custom_fields, $custom_fileds_key ) {

	$Job_Posting_Custom_Fields = new VGJPM_Custom_Field_Job_Post;
	$config                    = $Job_Posting_Custom_Fields->custom_fields_array();

	$options_arr = $config[ $custom_fileds_key ]['options'];

	$options_arr_key = $custom_fields[ $custom_fileds_key ];

	if ( is_array( $options_arr_key ) ) {

		$temp = array();
		for ( $i = 0; $i < count( $options_arr_key ); $i++ ) {

			$temp[] = $options_arr[ $options_arr_key[ $i ] ] . '';
		}

		$labels = implode( ' ,', $temp );

	} else {

		$labels = $options_arr[ $options_arr_key ];
	}

	return $labels;

}

function vgjpm_render_job_posting_table( $post_id, $style, $className ) {

	$custom_fields = vgjpm_get_custom_fields( $post_id );

	if ( ! isset( $custom_fields['vkjp_title'] ) ) {

		return '<div>' . __( 'Preview can be enabled after save or publish the content.', 'vk-google-job-posting-manager' ).'</div>' ;
	}

	$custom_fields = vgjpm_use_common_values( $custom_fields );

	if ( $className !== '' ) {
		$className .= ' ' . $className;
	}

	$tags = array(
		'outer_before'   => '<div class="vk_jobInfo' . esc_attr( $className ) . '"><table class="vk_jobInfo_table vk_jobInfo_table-style-' . esc_attr( $style ) . '"><tbody>',
		'title_before'   => '<tr><th>',
		'title_after'    => '</th>',
		'content_before' => '<td>',
		'content_after'  => '</td></tr>',
		'outer_after'    => '</tbody></table></div>',
	);

	$html = $tags['outer_before'];

		// // ポータルサイトなどで必要になる可能性があるので削除しない
		// $html .= '
		// <tr>
		// <td>' . __( 'Hiring Organization Logo', 'vk-google-job-posting-manager' ) . '</td>
		// <td> <img src="' . esc_attr( $custom_fields['vkjp_logo'] ) . '" alt="Company Logo" /></td>
		// </tr>
		// <tr>
		// <td>' . __( 'Hiring Organization Name', 'vk-google-job-posting-manager' ) . '</td>
		// <td> ' . esc_html( $custom_fields['vkjp_name'] ) . '</td>
		// </tr>
		// <tr>
		// <td>' . __( 'Hiring Organization Website', 'vk-google-job-posting-manager' ) . '</td>
		// <td><a href="' . esc_attr( $custom_fields['vkjp_sameAs'] ) . '">' . esc_html( $custom_fields['vkjp_sameAs'] ) . '</a>' . '</td>
		// </tr>
		// <tr>
		// <td>' . __( 'Posted Date', 'vk-google-job-posting-manager' ) . '</td>
		// <td>' . esc_html( date( 'Y-m-d', strtotime( $custom_fields['vkjp_datePosted'] ) ) ) . '</td>
		// </tr>
		// <tr>
		// <td>' . __( 'Expiry Date', 'vk-google-job-posting-manager' ) . '</td>
		// <td>' . esc_html( date( 'Y-m-d', strtotime( $custom_fields['vkjp_validThrough'] ) ) ) . '</td>
		// </tr>';

	$html .= $tags['title_before'] . __( 'Job Title', 'vk-google-job-posting-manager' ) . $tags['title_after'];
	$html .= $tags['content_before'] . esc_html( $custom_fields['vkjp_title'] ) . $tags['content_after'];

	$html .= $tags['title_before'] . __( 'Description', 'vk-google-job-posting-manager' ) . $tags['title_after'];
	$html .= $tags['content_before'] . esc_html( $custom_fields['vkjp_description'] ) . $tags['content_after'];

	$html .= $tags['title_before'] . __( 'Base Salary', 'vk-google-job-posting-manager' ) . $tags['title_after'];
	$html .= $tags['content_before'];
	$html .= esc_html( $custom_fields['vkjp_value'] ) . '(' . vgjpm_get_label( $custom_fields, 'vkjp_unitText' ) . ')';
	// $html .= '<br>' . esc_html( $custom_fields['vkjp_minValue'] ) . ' - ' . esc_html( $custom_fields['vkjp_maxValue'] ) . '(' . vgjpm_get_label( $custom_fields, 'vkjp_currency' ) . ')';
	$html .= $tags['content_after'];
	//
	$html .= $tags['title_before'] . __( 'Work Location', 'vk-google-job-posting-manager' ) . $tags['title_after'];
	$html .= $tags['content_before'] . __( 'Postal code', 'vk-google-job-posting-manager' ) . ' : ' . esc_html( $custom_fields['vkjp_postalCode'] );
	// $html .= esc_html( $custom_fields['vkjp_addressCountry'] );
	$html .= '<br>' . esc_html( $custom_fields['vkjp_addressRegion'] ) . esc_html( $custom_fields['vkjp_addressLocality'] ) . esc_html( $custom_fields['vkjp_streetAddress'] ) . $tags['content_after'];

	$html .= $tags['title_before'] . __( 'Employment Type', 'vk-google-job-posting-manager' ) . $tags['title_after'];
	$html .= $tags['content_before'] . vgjpm_get_label( $custom_fields, 'vkjp_employmentType' ) . $tags['content_after'];

	$html .= $tags['title_before'] . __( 'JobLocation Type', 'vk-google-job-posting-manager' ) . $tags['title_after'];
	$html .= $tags['content_before'] . vgjpm_get_label( $custom_fields, 'vkjp_jobLocationType' ) . $tags['content_after'];

	$html .= $tags['title_before'] . __( 'Incentive Compensation', 'vk-google-job-posting-manager' ) . $tags['title_after'];
	$html .= $tags['content_before'] . esc_html( $custom_fields['vkjp_incentiveCompensation'] ) . $tags['content_after'];

	$html .= $tags['title_before'] . __( 'Salary Raise', 'vk-google-job-posting-manager' ) . $tags['title_after'];
	$html .= $tags['content_before'] . esc_html( $custom_fields['vkjp_salaryRaise'] ) . $tags['content_after'];

	$html .= $tags['title_before'] . __( 'Work Hours', 'vk-google-job-posting-manager' ) . $tags['title_after'];
	$html .= $tags['content_before'] . esc_html( $custom_fields['vkjp_workHours'] ) . $tags['content_after'];

	$html .= $tags['title_before'] . __( 'Experience Requirements', 'vk-google-job-posting-manager' ) . $tags['title_after'];
	$html .= $tags['content_before'] . esc_html( $custom_fields['vkjp_experienceRequirements'] ) . $tags['content_after'];

	$html .= $tags['title_before'] . __( 'Special Commitments', 'vk-google-job-posting-manager' ) . $tags['title_after'];
	$html .= $tags['content_before'] . esc_html( $custom_fields['vkjp_specialCommitments'] ) . $tags['content_after'];

	$html .= $tags['outer_after'];

	return $html;
}
