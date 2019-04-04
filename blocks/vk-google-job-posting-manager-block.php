<?php
/**
 * Functions to register client-side assets (scripts and stylesheets) for the
 * Gutenberg block.
 *
 * @package vk-job-posting
 */

/**
 * Registers all block assets so that they can be enqueued through Gutenberg in
 * the corresponding context.
 *
 * @see https://wordpress.org/gutenberg/handbook/blocks/writing-your-first-block-type/#enqueuing-block-scripts
 */

require_once( dirname( dirname( __FILE__ ) ) . '/vk-google-job-posting-manager.php' );
require_once( dirname( dirname( __FILE__ ) ) . '/inc/custom-field-builder/custom-field-builder-config.php' );

function vk_job_posting_block_init() {
	// Skip block registration if Gutenberg is not enabled/merged.
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}
	$dir = dirname( __FILE__ );

	$index_js = 'vk-job-posting/build.js';
	wp_register_script(
		'vk-job-posting-block-editor',
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

	$editor_css = 'vk-job-posting/style.css';
	wp_register_style(
		'vk-job-posting-block-editor',
		plugins_url( $editor_css, __FILE__ ),
		array(),
		filemtime( "$dir/$editor_css" )
	);

	$style_css = 'vk-job-posting/style.css';
	wp_register_style(
		'vk-job-posting-block',
		plugins_url( $style_css, __FILE__ ),
		array(),
		filemtime( "$dir/$style_css" )
	);

	register_block_type( 'vk-blocks/job-posting', array(
		'editor_script'   => 'vk-job-posting-block-editor',
		'editor_style'    => 'vk-job-posting-block-editor',
		'style'           => 'vk-job-posting-block',
		'attributes'      => [
			'id'    => [
				'type' => 'integer',
				'default'   => 0,
			],
			'style' => [
				'type' => 'string',
				'default'   => 'default',
			],
			'className' => [
				'type' => 'string',
				'default'   => '',
			]
		],
		'render_callback' => function ( $attributes ) {
			return vk_job_posts_render_job_posting_table( $attributes['id'], $attributes['style'], $attributes['className'] );
		},
	) );
}

add_action( 'init', 'vk_job_posting_block_init' );


// Add Block Category,
if ( ! has_filter( 'block_categories', 'vkblocks_blocks_categories' ) ) {

	add_filter( 'block_categories', 'vkblocks_blocks_categories', 10, 2 );

	function vkblocks_blocks_categories( $categories ) {
		global $vk_blocks_prefix;

		return array_merge(
			$categories,
			array(
				array(
					'slug'  => 'vk-blocks-cat',
					'title' => $vk_blocks_prefix . __( 'Blocks（Beta）', 'vk-blocks' ),
					'icon'  => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path fill="none" d="M0 0h24v24H0V0z" /><path d="M19 13H5v-2h14v2z" /></svg>',
				),
				array(
					'slug'  => 'vk-blocks-cat-layout',
					'title' => $vk_blocks_prefix . __( 'Blocks Layout（Beta）', 'vk-blocks' ),
					'icon'  => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path fill="none" d="M0 0h24v24H0V0z" /><path d="M19 13H5v-2h14v2z" /></svg>',
				),
			)
		);
	}
}


/**
 *
 * @param $custom_fields
 * @param $custom_fileds_key
 *
 * @return array
 */

function vk_job_posts_get_label( $custom_fields, $custom_fileds_key ) {

	$Job_Posting_Custom_Fields = new Job_Posting_Custom_Fields;
	$config                    = $Job_Posting_Custom_Fields->custom_fields_array();

	$options_arr = $config[ $custom_fileds_key ]['options'];

	$options_arr_key = $custom_fields[ $custom_fileds_key ];

	if(is_array($options_arr_key)){


		$temp = array();
		for ($i=0; $i < count($options_arr_key); $i++){

			$temp[] = $options_arr[$options_arr_key[$i]] . '';
		}

		$labels = implode(' ,',$temp);


	}else{

		$labels = $options_arr[ $options_arr_key ];
	}

	return $labels;

}

function vk_job_posts_render_job_posting_table( $id, $style, $className ) {

	$custom_fields = vk_job_posts_get_custom_fields( $id );

	if ( ! isset( $custom_fields['vkjp_title'] ) ) {
		return;
	}
	$custom_fields = use_common_values( $custom_fields );

	if($className !== ''){
		$className .= ' '.$className;
	}

	$html = '
	<div class="vk_job-posting'.$className.'">
	<table class="vk_job-posting_table-'. $style .'">
    <tbody>
    <tr>
        <td>' . __( 'Posted Date', 'vk-job-posting' ) . '</td>
        <td>' . esc_html( date( 'Y-m-d', strtotime($custom_fields['vkjp_datePosted']) ) ) . '</td>
    </tr>
    <tr>
    	<td>' . __( 'Expiry Date', 'vk-job-posting' ) . '</td>
    	<td>' . esc_html( date( 'Y-m-d', strtotime($custom_fields['vkjp_validThrough']) ) ) . '</td>
    </tr>
    <tr>
        <td>' . __( 'Job Title', 'vk-job-posting' ) . '</td>
        <td>' . esc_html( $custom_fields['vkjp_title'] ) . '</td>
    </tr>
    <tr>
        <td>' . __( 'Description', 'vk-job-posting' ) . '</td>
        <td>' . esc_html( $custom_fields['vkjp_description'] ) . '</td>
    </tr>
    <tr>
        <td>' . __( 'Base Salary', 'vk-job-posting' ) . '</td>
        
        <td>' . __( 'Average Value', 'vk-job-posting' ) . '：' . esc_html( $custom_fields['vkjp_value'] ) . '(' . vk_job_posts_get_label( $custom_fields, 'vkjp_unitText' ) . ')' . '<br>' . esc_html( $custom_fields['vkjp_minValue'] ) . '~' . esc_html( $custom_fields['vkjp_maxValue'] ) . '(' . vk_job_posts_get_label( $custom_fields, 'vkjp_currency' ) . ')' . '</td>
    </tr>
    <tr>
        <td>' . __( 'Work Location', 'vk-job-posting' ) . '</td>
        <td>〒' . esc_html( $custom_fields['vkjp_postalCode'] ) . '<br>' . esc_html( $custom_fields['vkjp_addressCountry'] ) . esc_html( $custom_fields['vkjp_addressRegion'] ) . esc_html( $custom_fields['vkjp_addressLocality'] ) . esc_html( $custom_fields['vkjp_streetAddress'] ) . '</td>
    </tr>
    <tr>
        <td>' . __( 'Employment Type', 'vk-job-posting' ) . '</td>
        <td>' . vk_job_posts_get_label( $custom_fields, 'vkjp_employmentType' ) . '</td>
    </tr>
    <tr>
        <td>' . __( 'Incentive Compensation', 'vk-job-posting' ) . '</td>
        <td>' . esc_html( $custom_fields['vkjp_incentiveCompensation'] ) . '</td>
    </tr>
    <tr>
        <td>' . __( 'Salary Raise', 'vk-job-posting' ) . '</td>
        <td>' . esc_html( $custom_fields['vkjp_salaryRaise'] ) . '</td>
    </tr>
    <tr>
        <td>' . __( 'Work Hours', 'vk-job-posting' ) . '</td>
        <td>' . esc_html( $custom_fields['vkjp_workHours'] ) . '</td>
    </tr>
   
    <tr>
        <td>' . __( 'Experience Requirements', 'vk-job-posting' ) . '</td>
        <td>' . esc_html( $custom_fields['vkjp_experienceRequirements'] ) . '</td>
    </tr>
    <tr>
        <td>' . __( 'Special Commitments', 'vk-job-posting' ) . '</td>
        <td>' . esc_html( $custom_fields['vkjp_specialCommitments'] ) . '</td>
    </tr>
    <tr>
        <td>' . __( 'Hiring Organization Name', 'vk-job-posting' ) . '</td>
        <td> ' . esc_html( $custom_fields['vkjp_name'] ) . '</td>
    </tr>
    <tr>
        <td>' . __( 'Hiring Organization Website', 'vk-job-posting' ) . '</td>
        <td><a href="' . esc_attr( $custom_fields['vkjp_sameAs'] ) . '">' . esc_html( $custom_fields['vkjp_sameAs'] ) . '</a>' . '</td>
    </tr>
    <tr>
        <td>' . __( 'Hiring Organization Logo', 'vk-job-posting' ) . '</td>
        <td> <img src="' . esc_attr( $custom_fields['vkjp_logo'] ) . '" alt="Company Logo" /></td>
    </tr>
  
    </tbody>
    </table>
    </div>';
	return $html;
}

