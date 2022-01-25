<?php
/*
-------------------------------------------*/
/*
  Load modules
/*
-------------------------------------------*/
// autoloadを読み込む
require dirname( dirname( dirname( __FILE__ ) ) ) . '/vendor/autoload.php';

if ( ! class_exists( 'VK_Custom_Field_Builder' ) ) {
	require_once dirname( __FILE__ ) . '/package/custom-field-builder.php';
}

global $custom_field_builder_url;
$custom_field_builder_url = plugin_dir_url( __FILE__ ) . 'package/';

class VGJPM_Custom_Field_Job_Post extends VK_Custom_Field_Builder {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'check_post_type_show_metabox' ), 10, 2 );
		add_action( 'save_post', array( __CLASS__, 'save_custom_fields' ), 10, 2 );
	}

	public static function check_post_type_show_metabox() {
		$args       = array(
			'public' => true,
		);
		$post_types = get_post_types( $args, 'object' );

		foreach ( $post_types as $key => $value ) {
			if ( $key == 'job-posts' ) {
				self::add_metabox( 'job-posts' );
			} elseif ( $key != 'attachment' ) {
				$show_metabox = get_option( 'vgjpm_post_type_display_customfields' . $key );
				if ( isset( $show_metabox ) && $show_metabox == 'true' ) {
					self::add_metabox( $key );
				}
			}
		}
	}

	// add meta_box
	public static function add_metabox( $key ) {
		$id            = 'meta_box_job_posting';
		$title         = __( 'Google Job Posting Registration Information', 'vk-google-job-posting-manager' );
		$callback      = array( __CLASS__, 'fields_form' );
		$screen        = $key;
		$context       = 'advanced';
		$priority      = 'high';
		$callback_args = '';
		add_meta_box( $id, $title, $callback, $screen, $context, $priority, $callback_args );
	}

	public static function fields_form() {
		global $post;
		$custom_fields_array = self::custom_fields_array();
		$befor_custom_fields = '';
		$field_options = vkjpm_get_common_field_options();
		echo '<ul>';
		echo '<li>' . __( 'Please fill in recruitment information for Google Job Posting.', 'vk-google-job-posting-manager' ) . '</li>';
		echo '<li>' . __( 'If you do not fill in this form that, common settings will apply.', 'vk-google-job-posting-manager' ) . ' [ <a href="' . admin_url() . 'options-general.php?page=vgjpm_settings" target="_blank">' . __( 'Common Settings', 'vk-google-job-posting-manager' ) . '</a> ]</li>';
		echo '<li>' . __( 'If you want to display these items table to publish page, you use to the Job Posting Block set to content area.', 'vk-google-job-posting-manager' ) . '</li>';
		echo '</ul>';
		self::form_table( $custom_fields_array, $befor_custom_fields, true, $field_options );
	}

	public static function custom_fields_array() {
		$iso4217          = new Alcohol\ISO4217();
		$currency_list    = $iso4217->getAll();
		$currency_options = array();

		foreach ( $currency_list as $key => $value ) {
			$name   = $currency_list[ $key ]['name'];
			$alpha3 = $currency_list[ $key ]['alpha3'];

			$currency_options[ $alpha3 ] = __( $name, 'vk-google-job-posting-manager' );
		}

		$custom_fields_array = array(
			'vkjp_title'                              => array(
				'label'       => __( 'Job Title', 'vk-google-job-posting-manager' ),
				'type'        => 'text',
				'description' => __( 'Please enter ONLY the name of the job. Please DO NOT include the job description, extra catch copy, etc. <br> ○ : Software Engineer / Barista. </br> × : Software Engineer in awesome startup / Barista who brews tasty coffee. </br></br>Also, please avoid to use special characters like "!", "?", etc.', 'vk-google-job-posting-manager' ),
				'required'    => true,
			),
			'vkjp_description'                        => array(
				'label'       => __( 'Description', 'vk-google-job-posting-manager' ),
				'type'        => 'textarea',
				'description' => __( 'Please enter specific description of the job by HTML. You can use the templates from  <a href="https://www.vektor-inc.co.jp/service/wordpress-plugins/vk-google-jog-posting-manager/#vk-google-job-template" target="_blank">here</a>.', 'vk-google-job-posting-manager' ),
				'required'    => true,
			),
			'vkjp_minValue'                           => array(
				'label'       => __( 'Minimum Value of Salary', 'vk-google-job-posting-manager' ),
				'type'        => 'text',
				'description' => __( 'Please enter the minimum value of the salary. Ex：150000', 'vk-google-job-posting-manager' ),
				'required'    => false,
			),
			'vkjp_maxValue'                           => array(
				'label'       => __( 'Max Value of Salary', 'vk-google-job-posting-manager' ),
				'type'        => 'text',
				'description' => __( 'Please enter the max value of the salary. Ex：250000', 'vk-google-job-posting-manager' ),
				'required'    => false,
			),
			'vkjp_unitText'                           => array(
				'label'       => __( 'The Cycle of Salary Payment', 'vk-google-job-posting-manager' ),
				'type'        => 'select',
				'options'     => array(
					'HOUR'  => __( 'Per hour', 'vk-google-job-posting-manager' ),
					'DAY'   => __( 'Per Day', 'vk-google-job-posting-manager' ),
					'WEEK'  => __( 'Per Week', 'vk-google-job-posting-manager' ),
					'MONTH' => __( 'Per month', 'vk-google-job-posting-manager' ),
					'YEAR'  => __( 'Per year', 'vk-google-job-posting-manager' ),
				),
				'description' => '',
				'required'    => false,
			),
			'vkjp_currency'                           => array(
				'label'       => __( 'Currency', 'vk-google-job-posting-manager' ),
				'type'        => 'select',
				'options'     => apply_filters( 'vkjp_currency_options', $currency_options ),
				'description' => __( 'Example : Japanese Yen', 'vk-google-job-posting-manager' ),
				'required'    => false,
			),
			'vkjp_employmentType'                     => array(
				'label'       => __( 'Employment Type', 'vk-google-job-posting-manager' ),
				'type'        => 'checkbox',
				'options'     => array(
					'FULL_TIME'  => __( 'FULL TIME', 'vk-google-job-posting-manager' ),
					'PART_TIME'  => __( 'PART TIME', 'vk-google-job-posting-manager' ),
					'CONTRACTOR' => __( 'CONTRACTOR', 'vk-google-job-posting-manager' ),
					'TEMPORARY'  => __( 'TEMPORARY', 'vk-google-job-posting-manager' ),
					'INTERN'     => __( 'INTERN', 'vk-google-job-posting-manager' ),
					'VOLUNTEER'  => __( 'VOLUNTEER', 'vk-google-job-posting-manager' ),
					'PER_DIEM'   => __( 'PER DIEM', 'vk-google-job-posting-manager' ),
					'OTHER'      => __( 'OTHER', 'vk-google-job-posting-manager' ),
				),
				'description' => '',
				'required'    => false,
			),
			'vkjp_jobLocationType'                    => array(
				'label'       => __( 'Remote Work', 'vk-google-job-posting-manager' ),
				'type'        => 'checkbox',
				'options'     => array(
					'TELECOMMUTE' => __( 'Remote Work', 'vk-google-job-posting-manager' ),
				),
				'description' => __( 'Please check it, only if you allow employees full remote work.', 'vk-google-job-posting-manager' ),
				'required'    => false,
			),
			'vkjp_applicantLocationRequirements_name'        => array(
				'label'       => __( 'Countries that allow remote work', 'vk-google-job-posting-manager' ),
				'type'        => 'text',
				'description' => __( 'Example : USA', 'vk-google-job-posting-manager' ),
				'required'    => false,
			),
			'vkjp_name'                               => array(
				'label'       => __( 'Hiring Organization Name', 'vk-google-job-posting-manager' ),
				'type'        => 'text',
				'description' => __( 'Example : Vektor,Inc. Do not include address of organization', 'vk-google-job-posting-manager' ),
				'required'    => true,
			),
			'vkjp_sameAs'                             => array(
				'label'       => __( 'Hiring Organization Website', 'vk-google-job-posting-manager' ),
				'type'        => 'text',
				'description' => __( 'Example : https://www.vektor-inc.co.jp/', 'vk-google-job-posting-manager' ),
				'required'    => false,
			),
			'vkjp_logo'                               => array(
				'label'       => __( 'Hiring Organization Logo', 'vk-google-job-posting-manager' ),
				'type'        => 'image',
				'description' => '',
				'required'    => false,
			),
			'vkjp_postalCode'                         => array(
				'label'       => __( 'Postal Code of work Location', 'vk-google-job-posting-manager' ),
				'type'        => 'text',
				'description' => __( 'Example : 94043. Do not include hyphens. ', 'vk-google-job-posting-manager' ),
				'required'    => false,
			),
			'vkjp_addressCountry'                     => array(
				'label'       => __( 'Country of Work Location', 'vk-google-job-posting-manager' ),
				'type'        => 'text',
				'description' => __( 'Example : US [ <a href="https://en.wikipedia.org/wiki/ISO_3166-1" target="_blank">Country code (Alpha-2)</a> ]', 'vk-google-job-posting-manager' ),
				'required'    => false,
			),
			'vkjp_addressRegion'                      => array(
				'label'       => __( 'Address Region of Work Location', 'vk-google-job-posting-manager' ),
				'type'        => 'text',
				'description' => __( 'Example : CA', 'vk-google-job-posting-manager' ),
				'required'    => false,
			),
			'vkjp_addressLocality'                    => array(
				'label'       => __( 'Address Locality of Work Location', 'vk-google-job-posting-manager' ),
				'type'        => 'text',
				'description' => __( 'Example : Mountain View', 'vk-google-job-posting-manager' ),
				'required'    => false,
			),
			'vkjp_streetAddress'                      => array(
				'label'       => __( 'Street Address of Work Location', 'vk-google-job-posting-manager' ),
				'type'        => 'text',
				'description' => __( 'Example : 1600 Amphitheatre Pkwy', 'vk-google-job-posting-manager' ),
				'required'    => false,
			),
			'vkjp_validThrough'                       => array(
				'label'       => __( 'Expiry Date', 'vk-google-job-posting-manager' ),
				'type'        => 'datepicker',
				'description' => __( 'Please enter expiry date. If you are not sure about expiry date, please leave it blank.', 'vk-google-job-posting-manager' ),
				'required'    => false,
			),
			'vkjp_identifier'                         => array(
				'label'       => __( 'Company Identifier Number', 'vk-google-job-posting-manager' ),
				'type'        => 'text',
				'description' => __( 'The hiring organization\'s unique identifier number for the job posting. <br> Please enter a unique number id whatever you want. Example : 1234567', 'vk-google-job-posting-manager' ),
				'required'    => false,
			),
			'vkjp_directApply'                        => array(
				'label'       => __( 'Direct Apply', 'vk-google-job-posting-manager' ),
				'type'        => 'checkbox',
				'options'     => array(
					'true' => __( 'You can apply from this page', 'vk-google-job-posting-manager' ),
				),
				'description' => __( 'Please check it, only if you set apply form on this page.', 'vk-google-job-posting-manager' ),
				'required'    => false,
			),
		);

		return $custom_fields_array;
	}

	public static function save_custom_fields() {
		$custom_fields_array = self::custom_fields_array();
		self::save_cf_value( $custom_fields_array );
	}
}

VGJPM_Custom_Field_Job_Post::init();
