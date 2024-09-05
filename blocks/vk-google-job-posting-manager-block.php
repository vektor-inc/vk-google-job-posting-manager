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

require_once dirname( dirname( __FILE__ ) ) . '/vk-google-job-posting-manager.php';
require_once dirname( dirname( __FILE__ ) ) . '/inc/custom-field-builder/custom-field-builder-config.php';

/**
 * Chack block category exist
 *
 * @param array  $categories
 * @param string $slug
 * @return boolian
 */
if ( ! function_exists( 'vgjpm_is_block_category_exist' ) ) {
	function vgjpm_is_block_category_exist( $categories, $slug ) {
		$keys = array();
		foreach ( $categories as $key => $value ) {
			$keys[] = $value['slug'];
		}
		if ( in_array( $slug, $keys ) ) {
			return true;
		} else {
			return false;
		}
	}
}

function vgjpm_block_init() {
	$dir        = dirname( __FILE__ );
	$asset_file = include plugin_dir_path( __FILE__ ) . '/create-table/build/block-build.asset.php';
	$index_js   = '/create-table/build/block-build.js';
	wp_register_script(
		'vk-google-job-posting-manager-block-editor',
		plugins_url( $index_js, __FILE__ ),
		$asset_file['dependencies'],
		$asset_file['version']
	);

	$editor_css = '/create-table/build/editor.css';
	wp_register_style(
		'vk-google-job-posting-manager-block-editor',
		plugins_url( $editor_css, __FILE__ ),
		array(),
		$asset_file['version']
	);

	$style_css = '/create-table/build/style.css';
	wp_register_style(
		'vk-google-job-posting-manager-block',
		plugins_url( $style_css, __FILE__ ),
		array(),
		$asset_file['version']
	);

	// WordPress 6.5 以下の対策
	if ( ! wp_script_is( 'react-jsx-runtime', 'registered' ) ) {
		wp_register_script(
			'react-jsx-runtime',
			VGJPM_URL . '/assets/js/react-jsx-runtime.js',
			array( 'react' ),
			'18.3.1',
			true
		);
	}

	register_block_type(
		'vk-google-job-posting-manager/create-table',
		array(
			'editor_script'   => 'vk-google-job-posting-manager-block-editor',
			'editor_style'    => 'vk-google-job-posting-manager-block-editor',
			'style'           => 'vk-google-job-posting-manager-block',
			'attributes'      => array(
				'style'     => array(
					'type'    => 'string',
					'default' => 'default',
				),
				'className' => array(
					'type'    => 'string',
					'default' => '',
				),
				'post_id'   => array(
					'type'    => 'number',
					'default' => 0,
				),
			),
			'render_callback' => function ( $attributes ) {
				return vgjpm_render_job_posting_info( $attributes['post_id'], $attributes['style'], $attributes['className'] );
			},
		)
	);
}
add_action( 'init', 'vgjpm_block_init' );


/**
 * Add vk-block's category.
 */
if ( ! function_exists( 'vkblocks_blocks_categories' ) ) {
	function vkblocks_blocks_categories( $categories, $post ) {
		if ( ! vgjpm_is_block_category_exist( $categories, 'vk-blocks-cat' ) ) {
			$categories = array_merge(
				$categories,
				array(
					array(
						'slug'  => 'vk-blocks-cat',
						'title' => apply_filters( 'vk_blocks_prefix', 'VK ' ) . __( 'Blocks', 'vk-blocks' ),
						'icon'  => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path fill="none" d="M0 0h24v24H0V0z" /><path d="M19 13H5v-2h14v2z" /></svg>',
					),
				)
			);
		}
		return $categories;
	}
	// ver5.8.0 block_categories_all
	if ( function_exists( 'get_default_block_categories' ) && function_exists( 'get_block_editor_settings' ) ) {
		add_filter( 'block_categories_all', 'vkblocks_blocks_categories', 10, 2 );
	} else {
		add_filter( 'block_categories', 'vkblocks_blocks_categories', 10, 2 );
	}
}


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

	$VGJPM_CFJP = new VGJPM_Custom_Field_Job_Post();
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

	if ( empty( $args['figure'] ) && $args['empty_expression'] == 'no_display' ) {
			$return = '';
	} elseif ( key_exists( $args['currency'], $currency_data ) ) {
		$target_currency = $currency_data[ $args['currency'] ];

		if ( $args['before'] ) {
			$before = $target_currency['before'];
		} else {
			$before = '';
		}

		if ( $args['after'] ) {
			$after = '<span class="vk_jobInfo_amount_before">' . $target_currency['after'] . '</span>';
		} else {
			$after = '';
		}

		$return = $before . '<span class="vk_jobInfo_amount_figure">' . number_format( intval( $args['figure'] ) ) . '</span>' . $after;
	} else {
		// 通貨記号のリストにない場合
		$return = '<span class="vk_jobInfo_amount_after">' . $args['figure'] . '</span>' . ' (' . $args['currency'] . ')';
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
	$html .= $tags['content_before'] . wp_kses_post( $custom_fields['vkjp_description'] ) . $tags['content_after'];

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
		'currency'         => $custom_fields['vkjp_currency'],
		'figure'           => esc_html( $custom_fields['vkjp_minValue'] ),
		'before'           => false,
		'after'            => true,
		'empty_expression' => 'no_display',
	);
	$args_max = array(
		'currency'         => $custom_fields['vkjp_currency'],
		'figure'           => esc_html( $custom_fields['vkjp_maxValue'] ),
		'before'           => false,
		'after'            => true,
		'empty_expression' => 'no_display',
	);

	// salaly amount min to max separator
	$separater = __( ' - ', 'vk-google-job-posting-manager' );
	$separater = apply_filters( 'vgjpm_salary_separater', $separater );

	$html .= vgjpm_get_label_of_array( array( $custom_fields['vkjp_unitText'] ) ) . ' ';
	$html .= vgjpm_salary_and_currency( $args_min ) . $separater . vgjpm_salary_and_currency( $args_max );
	$html .= $tags['content_after'];

	$html .= $tags['title_before'];
	$html .= __( 'Work Location', 'vk-google-job-posting-manager' );
	$html .= $tags['title_after'];

	$html .= $tags['content_before'];
	if ( vgjpm_get_label_of_array( $custom_fields['vkjp_jobLocationType'] ) ) {
		$html .= vgjpm_get_label_of_array( $custom_fields['vkjp_jobLocationType'] );
	} else {
		$html .= __( 'Postal code : ', 'vk-google-job-posting-manager' ) . esc_html( $custom_fields['vkjp_postalCode'] );
		// $html .= esc_html( $custom_fields['vkjp_addressCountry'] );
		$html .= '<br>' . esc_html( $custom_fields['vkjp_addressRegion'] ) . esc_html( $custom_fields['vkjp_addressLocality'] ) . esc_html( $custom_fields['vkjp_streetAddress'] );
	}
	$html .= $tags['content_after'];

	$html .= $tags['title_before'] . __( 'Employment Type', 'vk-google-job-posting-manager' ) . $tags['title_after'];
	$html .= $tags['content_before'] . vgjpm_get_label_of_array( $custom_fields['vkjp_employmentType'] ) . $tags['content_after'];

	$html .= $tags['outer_after'];
	$html .= '</div>';

	return apply_filters( 'vgjpm_render_job_posting_info', $html );
}
