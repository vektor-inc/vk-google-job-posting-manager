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
				'post_id'   => [
					'type'    => 'number',
					'default' => 0,
				],
			],
			'render_callback' => function ( $attributes ) {
				return vgjpm_render_job_posting_info( $attributes['post_id'], $attributes['style'], $attributes['className'] );
			},
		)
	);

}
add_action( 'init', 'vgjpm_block_init' );


/**
 * @param $args | array( 'FULL_TIME', 'PART_TIME', );
 *
 * @return string | 'FULL TIME, PART TIME'
 */
function vgjpm_get_label_of_array( $args ) {

	if ( ! is_array( $args ) ) {
		return false;
	}

	$labels = vgjpm_get_labels( $args );

	return implode( ', ', $labels );

}

/**
 * @param $args | array( 'TELECOMMUTE' );
 *
 * @return array | array( 'Remote Work' );
 */
function vgjpm_get_labels( $args ) {

	if ( ! is_array( $args ) ) {
		return false;
	}

	$VGJPM_CFJP = new VGJPM_Custom_Field_Job_Post;
	$default    = $VGJPM_CFJP->custom_fields_array();
	$return     = array();

	foreach ( $args as $key => $value ) {

		$searched = array_column( $default, 'options' );
		$searched = array_column( $searched, $value );
		$return   = array_merge( $return, $searched );
	}

	return $return;
}


// $args = array(
// 'currency' => 'JPY',
// 'figure'   => '',
// 'before'   => false,
// 'after'    => true,
// );
function vgjpm_salary_and_currency( $args ) {

	$currency_data = array(
		'JPY' => array(
			'before' => '¥',
			'after'  => __( 'YEN', 'vk-google-job-posting-manager' ),
		),
		'USD' => array(
			'before' => '$',
			'after'  => __( 'USD', 'vk-google-job-posting-manager' ),
		),
	);
	$currency_data = apply_filters( 'vgjpm_salary_and_currency_currency_data', $currency_data );

	if ( key_exists( $args['currency'], $currency_data ) ) {

		$target_currency = $currency_data[ $args['currency'] ];

		if ( $args['before'] ) {

			$before = $target_currency['before'] . ' ';

		} else {
			$before = '';
		}

		if ( $args['after'] ) {

			$after = ' ' . $target_currency['after'];

		} else {
			$after = '';
		}

		$return = $before . number_format( intval( $args['figure'] ) ) . $after;

	} else {
		// 通貨記号のリストにない場合
		$return = $args['figure'] . ' (' . $args['currency'] . ')';

	}

	return apply_filters( 'vgjpm_salary_and_currency', $return );
}

function vgjpm_render_job_posting_info( $post_id, $style, $className ) {

	$custom_fields = vgjpm_get_custom_fields( $post_id );

	if ( ! isset( $custom_fields['vkjp_title'] ) ) {

		return '<div>' . __( 'Preview can be enabled after save or publish the content.', 'vk-google-job-posting-manager' ) . '</div>';
	}

	$custom_fields = vgjpm_use_common_values( $custom_fields, 'block' );

	if ( $className !== '' ) {
		$className = ' ' . $className;
	}

	$tags = array(
		'outer_before'   => '<table class="vk_jobInfo_table"><tbody>',
		'title_before'   => '<tr><th>',
		'title_after'    => '</th>',
		'content_before' => '<td>',
		'content_after'  => '</td></tr>',
		'outer_after'    => '</tbody></table>',
	);

	$tags = apply_filters( 'vgjpm_jobInfo_tags', $tags );

	$html  = '<div class="vk_jobInfo vk_jobInfo-type-' . esc_attr( $style ) . esc_attr( $className ) . '">';
	$html .= $tags['outer_before'];

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
	$html .= $tags['content_before'] . nl2br( esc_textarea( $custom_fields['vkjp_description'] ) ) . $tags['content_after'];

	$html .= $tags['title_before'] . __( 'Estimated salary', 'vk-google-job-posting-manager' ) . $tags['title_after'];
	$html .= $tags['content_before'];

	// $args     = array(
	// 'currency' => $custom_fields['vkjp_currency'],
	// 'figure'   => esc_html( $custom_fields['vkjp_value'] ),
	// 'before'   => false,
	// 'after'    => true,
	// );
	//
	// before after がハードコーディングされていて、通貨によって変更したりできないが、
	// 必要な場合は vgjpm_salary_and_currency のフックで対応してもらう
	$args_min = array(
		'currency' => $custom_fields['vkjp_currency'],
		'figure'   => esc_html( $custom_fields['vkjp_minValue'] ),
		'before'   => false,
		'after'    => true,
	);
	$args_max = array(
		'currency' => $custom_fields['vkjp_currency'],
		'figure'   => esc_html( $custom_fields['vkjp_maxValue'] ),
		'before'   => false,
		'after'    => true,
	);

	$html .= esc_html( vgjpm_salary_and_currency( $args_min ) ) . ' - ' . esc_html( vgjpm_salary_and_currency( $args_max ) ) . ' (' . vgjpm_get_label_of_array( array( $custom_fields['vkjp_unitText'] ) ) . ')';
	$html .= $tags['content_after'];

	$html .= $tags['title_before'];
	$html .= __( 'Work Location', 'vk-google-job-posting-manager' );
	$html .= $tags['title_after'];

	$html .= $tags['content_before'];
	if ( vgjpm_get_label_of_array( $custom_fields['vkjp_jobLocationType'] ) ) {
		$html .= vgjpm_get_label_of_array( $custom_fields['vkjp_jobLocationType'] );
	} else {
		$html .= __( 'Postal code', 'vk-google-job-posting-manager' ) . ' : ' . esc_html( $custom_fields['vkjp_postalCode'] );
		// $html .= esc_html( $custom_fields['vkjp_addressCountry'] );
		$html .= '<br>' . esc_html( $custom_fields['vkjp_addressRegion'] ) . esc_html( $custom_fields['vkjp_addressLocality'] ) . esc_html( $custom_fields['vkjp_streetAddress'] );
	}
	$html .= $tags['content_after'];

	$html .= $tags['title_before'] . __( 'Employment Type', 'vk-google-job-posting-manager' ) . $tags['title_after'];
	$html .= $tags['content_before'] . vgjpm_get_label_of_array( $custom_fields['vkjp_employmentType'] ) . $tags['content_after'];

	$html .= $tags['title_before'] . __( 'Incentive Compensation', 'vk-google-job-posting-manager' ) . $tags['title_after'];
	$html .= $tags['content_before'] . esc_html( $custom_fields['vkjp_incentiveCompensation'] ) . $tags['content_after'];

	$html .= $tags['title_before'] . __( 'Salary Raise', 'vk-google-job-posting-manager' ) . $tags['title_after'];
	$html .= $tags['content_before'] . esc_html( $custom_fields['vkjp_salaryRaise'] ) . $tags['content_after'];

	$html .= $tags['title_before'] . __( 'Work Hours', 'vk-google-job-posting-manager' ) . $tags['title_after'];
	$html .= $tags['content_before'] . esc_html( $custom_fields['vkjp_workHours'] ) . $tags['content_after'];

	$html .= $tags['title_before'] . __( 'Experience Requirements', 'vk-google-job-posting-manager' ) . $tags['title_after'];
	$html .= $tags['content_before'] . nl2br( esc_textarea( $custom_fields['vkjp_experienceRequirements'] ) ) . $tags['content_after'];

	$html .= $tags['title_before'] . __( 'Special Commitments', 'vk-google-job-posting-manager' ) . $tags['title_after'];
	$html .= $tags['content_before'] . nl2br( esc_textarea( $custom_fields['vkjp_specialCommitments'] ) ) . $tags['content_after'];

	$html .= $tags['outer_after'];
	$html .= '</div>';

	return apply_filters( 'vgjpm_render_job_posting_info', $html );
}
