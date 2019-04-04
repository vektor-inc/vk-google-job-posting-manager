<?php

/*-------------------------------------------*/
/*  Load modules
/*-------------------------------------------*/
if ( ! class_exists( 'VK_Custom_Field_Builder' ) ) {
	require_once( dirname( __FILE__ ) . '/package/custom-field-builder.php' );
}

class Job_Posting_Custom_Fields {

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

				Job_Posting_Custom_Fields::add_metabox( 'job-posts' );

			} elseif ( $key != 'attachment' ) {

				$show_metabox = get_option( 'vk_gjpm_post_type_display_customfields' . $key );
				if ( isset( $show_metabox ) && $show_metabox == 'true' ) {
					Job_Posting_Custom_Fields::add_metabox( $key );
				}
			}
		}
	}

	// add meta_box
	public static function add_metabox( $key ) {
		$id            = 'meta_box_job_posting';
		$title         = __( 'Please enter details of your recruitment.', 'vk-job-posting' );
		$callback      = array( __CLASS__, 'fields_form' );
		$screen        = $key;
		$context       = 'advanced';
		$priority      = 'high';
		$callback_args = '';
		add_meta_box( $id, $title, $callback, $screen, $context, $priority, $callback_args );
	}

	public static function fields_form() {
		global $post;
		$custom_fields_array = Job_Posting_Custom_Fields::custom_fields_array();
		$befor_custom_fields = '';
		VK_Custom_Field_Builder::form_table( $custom_fields_array, $befor_custom_fields );
	}

	public static function custom_fields_array() {

		$custom_fields_array = array(
			'vkjp_title'                       => array(
				'label'       => __( 'Job Title', 'vk-job-posting' ),
				'type'        => 'text',
				'description' => __( 'Example: Software Engineer, Barista. Please enter only the name of the job. Please do not include the job code, address, date, salary, company name.', 'vk-job-posting' ),
				'required'    => true,
			),
			'vkjp_description'                 => array(
				'label'       => __( 'Description', 'vk-job-posting' ),
				'type'        => 'textarea',
				'description' => __( 'Please enter specific description of the job.', 'vk-job-posting' ),
				'required'    => true,
			),
			'vkjp_value'                  => array(
				'label'       => __( 'Base Salary', 'vk-job-posting' ),
				'type'        => 'text',
				'description' => __( 'Please enter the base salary in integer. Ex：250000', 'vk-job-posting' ),
				'required'    => false,
			),
			'vkjp_minValue'         => array(
				'label'       => __( 'Minimum Value of Salary', 'vk-job-posting' ),
				'type'        => 'text',
				'description' => __( 'Please enter the minimum value of the salary. Ex：150000', 'vk-job-posting' ),
				'required'    => false,
			),
			'vkjp_maxValue'         => array(
				'label'       => __( 'Max Value of Salary', 'vk-job-posting' ),
				'type'        => 'text',
				'description' => __( 'Please enter the max value of the salary. Ex：250000', 'vk-job-posting' ),
				'required'    => false,
			),
			'vkjp_unitText'                    => array(
				'label'       => __( 'Salary per ?', 'vk-job-posting' ),
				'type'        => 'select',
				'options'     => array( 'HOUR' => __( 'Per hour', 'vk-job-posting' ), 'MONTH' => __( 'Per month', 'vk-job-posting' ), 'YEAR' => __( 'Per year', 'vk-job-posting' ) ),
				'description' => __( 'Please select the term.', 'vk-job-posting' ),
				'required'    => false,
			),
			'vkjp_currency'  => array(
				'label'       => __( 'Currency', 'vk-job-posting' ),
				'type'        => 'select',
				'options'     => array( 'YEN' => __( 'YEN', 'vk-job-posting' ), 'USD' => __( 'USD', 'vk-job-posting' ) ),
				'description' => __( 'Please select the currency', 'vk-job-posting' ),
				'required'    => false,
			),
			'vkjp_incentiveCompensation'       => array(
				'label'       => __( 'Incentive Compensation', 'vk-job-posting' ),
				'type'        => 'text',
				'description' => __( 'Please enter the description of incentive compensation.', 'vk-job-posting' ),
				'required'    => false,
			),
			'vkjp_salaryRaise'       => array(
				'label'       => __( 'Salary Raise', 'vk-job-posting' ),
				'type'        => 'text',
				'description' => __( 'Please enter the description of salary raise.', 'vk-job-posting' ),
				'required'    => false,
			),
			'vkjp_workHours'       => array(
				'label'       => __( 'Work Hours', 'vk-job-posting' ),
				'type'        => 'text',
				'description' => __( 'Please enter the description of work hours.', 'vk-job-posting' ),
				'required'    => false,
			),
			'vkjp_experienceRequirements'       => array(
				'label'       => __( 'Experience Requirements', 'vk-job-posting' ),
				'type'        => 'text',
				'description' => __( 'Please enter the description of experience requirements. If you have the one.', 'vk-job-posting' ),
				'required'    => false,
			),
			'vkjp_specialCommitments'       => array(
				'label'       => __( 'Special Commitments', 'vk-job-posting' ),
				'type'        => 'text',
				'description' => __( 'Please enter the description of special commitments', 'vk-job-posting' ),
				'required'    => false,
			),
			'vkjp_employmentType'                    => array(
				'label'       => __( 'Employment Type', 'vk-job-posting' ),
				'type'        => 'checkbox',
				'options'     => array( 'FULL_TIME' => __( 'FULL TIME', 'vk-job-posting' ), 'PART_TIME' => __( 'PART TIME', 'vk-job-posting' ), 'CONTRACTOR' => __( 'CONTRACTOR', 'vk-job-posting' ), 'TEMPORARY' => __( 'TEMPORARY', 'vk-job-posting' ), 'INTERN' => __( 'INTERN', 'vk-job-posting' ), 'VOLUNTEER' => __( 'VOLUNTEER', 'vk-job-posting' ), 'PER_DIEM' => __( 'PER DIEM', 'vk-job-posting' ), 'OTHER' => __( 'OTHER', 'vk-job-posting' ) ),
				'description' => __( 'Please enter the description of salary raise.', 'vk-job-posting' ),
				'required'    => false,
			),
			'vkjp_name'     => array(
				'label'       => __( 'Hiring Organization Name', 'vk-job-posting' ),
				'type'        => 'text',
				'description' => __( 'Example : Vektor,Inc. Do not include address of organization' , 'vk-job-posting' ),
				'required'    => false,
			),
			'vkjp_sameAs'      => array(
				'label'       => __( 'Hiring Organization Website', 'vk-job-posting' ),
				'type'        => 'text',
				'description' => __( 'Example : https://www.vektor-inc.co.jp/', 'vk-job-posting' ),
				'required'    => false,
			),
			'vkjp_logo'     => array(
				'label'       => __( 'Hiring Organization Logo', 'vk-job-posting' ),
				'type'        => 'text',
				'description' => __( 'Example : https://www.vektor-inc.co.jp/logo.png', 'vk-job-posting' ),
				'required'    => false,
			),
			'vkjp_postalCode'      => array(
				'label'       => __( 'Postal Code of ork Location', 'vk-job-posting' ),
				'type'        => 'text',
				'description' => __( 'Example : 94043. Do not include hyphens. ', 'vk-job-posting' ),
				'required'    => false,
			),
			'vkjp_addressCountry'  => array(
				'label'       => __( 'Country of Work Location', 'vk-job-posting' ),
				'type'        => 'text',
				'description' => __( 'Please enter country code. Example : US', 'vk-job-posting' ),
				'required'    => false,
			),
			'vkjp_addressRegion'   => array(
				'label'       => __( 'Address Region of Work Location', 'vk-job-posting' ),
				'type'        => 'text',
				'description' => __( 'Example : CA', 'vk-job-posting' ),
				'required'    => false,
			),
			'vkjp_addressLocality' => array(
				'label'       => __( 'Address Locality of Work Location', 'vk-job-posting' ),
				'type'        => 'text',
				'description' => __( 'Example : Mountain View', 'vk-job-posting' ),
				'required'    => false,
			),
			'vkjp_streetAddress'  => array(
				'label'       => __( 'Street Address of Work Location', 'vk-job-posting' ),
				'type'        => 'text',
				'description' => __( 'Example : 1600 Amphitheatre Pkwy', 'vk-job-posting' ),
				'required'    => false,
			),
			'vkjp_validThrough'  => array(
				'label'       => __( 'Expiry Date', 'vk-job-posting' ),
				'type'        => 'date',
				'description' => __( 'Please enter expiry date, If you have one. Example : "2017-02-24"、"2017-02-24T19:33:17+00:00". If you are not sure about expiry date, please leave it blank.', 'vk-job-posting' ),
				'required'    => false,
			)
		);

		return $custom_fields_array;
	}

	public static function save_custom_fields() {
		$custom_fields_array = Job_Posting_Custom_Fields::custom_fields_array();
		VK_Custom_Field_Builder::save_cf_value( $custom_fields_array );
	}
}

Job_Posting_Custom_Fields::init();
