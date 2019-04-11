<?php

/*-------------------------------------------*/
/*  Load modules
/*-------------------------------------------*/
if ( ! class_exists( 'VK_Custom_Field_Builder' ) ) {
	require_once( dirname( __FILE__ ) . '/package/custom-field-builder.php' );
}

class VGJPM_Custom_Field_Job_Post extends VK_Custom_Field_Builder{

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
		$title         = __( 'Please enter details of your recruitment.', 'vk-google-job-posting-manager' );
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
		self::form_table( $custom_fields_array, $befor_custom_fields );
	}

	public static function custom_fields_array() {

		$custom_fields_array = array(
			'vkjp_title'                       => array(
				'label'       => __( 'Job Title', 'vk-google-job-posting-manager' ),
				'type'        => 'text',
				'description' => __( 'Example: Software Engineer, Barista. Please enter only the name of the job. Please do not include the job code, address, date, salary, company name.', 'vk-google-job-posting-manager' ),
				'required'    => true,
			),
			'vkjp_description'                 => array(
				'label'       => __( 'Description', 'vk-google-job-posting-manager' ),
				'type'        => 'textarea',
				'description' => __( 'Please enter specific description of the job.', 'vk-google-job-posting-manager' ),
				'required'    => true,
			),
			'vkjp_value'                  => array(
				'label'       => __( 'Base Salary', 'vk-google-job-posting-manager' ),
				'type'        => 'text',
				'description' => __( 'Please enter the base salary in integer. Ex：250000', 'vk-google-job-posting-manager' ),
				'required'    => false,
			),
			'vkjp_minValue'         => array(
				'label'       => __( 'Minimum Value of Salary', 'vk-google-job-posting-manager' ),
				'type'        => 'text',
				'description' => __( 'Please enter the minimum value of the salary. Ex：150000', 'vk-google-job-posting-manager' ),
				'required'    => false,
			),
			'vkjp_maxValue'         => array(
				'label'       => __( 'Max Value of Salary', 'vk-google-job-posting-manager' ),
				'type'        => 'text',
				'description' => __( 'Please enter the max value of the salary. Ex：250000', 'vk-google-job-posting-manager' ),
				'required'    => false,
			),
			'vkjp_unitText'                    => array(
				'label'       => __( 'The Cycle of Salary Payment', 'vk-google-job-posting-manager' ),
				'type'        => 'select',
				'options'     => array( 'HOUR' => __( 'Per hour', 'vk-google-job-posting-manager' ), 'MONTH' => __( 'Per month', 'vk-google-job-posting-manager' ), 'YEAR' => __( 'Per year', 'vk-google-job-posting-manager' ) ),
				'description' => __( 'Please select from above', 'vk-google-job-posting-manager' ),
				'required'    => false,
			),
			'vkjp_currency'  => array(
				'label'       => __( 'Currency', 'vk-google-job-posting-manager' ),
				'type'        => 'select',
				'options'     => array( 'YEN' => __( 'YEN', 'vk-google-job-posting-manager' ), 'USD' => __( 'USD', 'vk-google-job-posting-manager' ) ),
				'description' => __( 'Please select the currency', 'vk-google-job-posting-manager' ),
				'required'    => false,
			),
			'vkjp_incentiveCompensation'       => array(
				'label'       => __( 'Incentive Compensation', 'vk-google-job-posting-manager' ),
				'type'        => 'text',
				'description' => __( 'Please enter the description of incentive compensation.', 'vk-google-job-posting-manager' ),
				'required'    => false,
			),
			'vkjp_salaryRaise'       => array(
				'label'       => __( 'Salary Raise', 'vk-google-job-posting-manager' ),
				'type'        => 'text',
				'description' => __( 'Please enter the description of salary raise.', 'vk-google-job-posting-manager' ),
				'required'    => false,
			),
			'vkjp_workHours'       => array(
				'label'       => __( 'Work Hours', 'vk-google-job-posting-manager' ),
				'type'        => 'text',
				'description' => __( 'Please enter the description of work hours.', 'vk-google-job-posting-manager' ),
				'required'    => false,
			),
			'vkjp_experienceRequirements'       => array(
				'label'       => __( 'Experience Requirements', 'vk-google-job-posting-manager' ),
				'type'        => 'text',
				'description' => __( 'Please enter the description of experience requirements. If you have the one.', 'vk-google-job-posting-manager' ),
				'required'    => false,
			),
			'vkjp_specialCommitments'       => array(
				'label'       => __( 'Special Commitments', 'vk-google-job-posting-manager' ),
				'type'        => 'text',
				'description' => __( 'Please enter the description of special commitments', 'vk-google-job-posting-manager' ),
				'required'    => false,
			),
			'vkjp_employmentType'                    => array(
				'label'       => __( 'Employment Type', 'vk-google-job-posting-manager' ),
				'type'        => 'checkbox',
				'options'     => array( 'FULL_TIME' => __( 'FULL TIME', 'vk-google-job-posting-manager' ), 'PART_TIME' => __( 'PART TIME', 'vk-google-job-posting-manager' ), 'CONTRACTOR' => __( 'CONTRACTOR', 'vk-google-job-posting-manager' ), 'TEMPORARY' => __( 'TEMPORARY', 'vk-google-job-posting-manager' ), 'INTERN' => __( 'INTERN', 'vk-google-job-posting-manager' ), 'VOLUNTEER' => __( 'VOLUNTEER', 'vk-google-job-posting-manager' ), 'PER_DIEM' => __( 'PER DIEM', 'vk-google-job-posting-manager' ), 'OTHER' => __( 'OTHER', 'vk-google-job-posting-manager' ) ),
				'description' => __( 'Please enter the description of salary raise.', 'vk-google-job-posting-manager' ),
				'required'    => false,
			),
			'vkjp_name'     => array(
				'label'       => __( 'Hiring Organization Name', 'vk-google-job-posting-manager' ),
				'type'        => 'text',
				'description' => __( 'Example : Vektor,Inc. Do not include address of organization' , 'vk-google-job-posting-manager' ),
				'required'    => false,
			),
			'vkjp_sameAs'      => array(
				'label'       => __( 'Hiring Organization Website', 'vk-google-job-posting-manager' ),
				'type'        => 'text',
				'description' => __( 'Example : https://www.vektor-inc.co.jp/', 'vk-google-job-posting-manager' ),
				'required'    => false,
			),
			'vkjp_logo'     => array(
				'label'       => __( 'Hiring Organization Logo', 'vk-google-job-posting-manager' ),
				'type'        => 'text',
				'description' => __( 'Example : https://www.vektor-inc.co.jp/logo.png', 'vk-google-job-posting-manager' ),
				'required'    => false,
			),
			'vkjp_postalCode'      => array(
				'label'       => __( 'Postal Code of ork Location', 'vk-google-job-posting-manager' ),
				'type'        => 'text',
				'description' => __( 'Example : 94043. Do not include hyphens. ', 'vk-google-job-posting-manager' ),
				'required'    => false,
			),
			'vkjp_addressCountry'  => array(
				'label'       => __( 'Country of Work Location', 'vk-google-job-posting-manager' ),
				'type'        => 'text',
				'description' => __( 'Please enter country code. Example : US', 'vk-google-job-posting-manager' ),
				'required'    => false,
			),
			'vkjp_addressRegion'   => array(
				'label'       => __( 'Address Region of Work Location', 'vk-google-job-posting-manager' ),
				'type'        => 'text',
				'description' => __( 'Example : CA', 'vk-google-job-posting-manager' ),
				'required'    => false,
			),
			'vkjp_addressLocality' => array(
				'label'       => __( 'Address Locality of Work Location', 'vk-google-job-posting-manager' ),
				'type'        => 'text',
				'description' => __( 'Example : Mountain View', 'vk-google-job-posting-manager' ),
				'required'    => false,
			),
			'vkjp_streetAddress'  => array(
				'label'       => __( 'Street Address of Work Location', 'vk-google-job-posting-manager' ),
				'type'        => 'text',
				'description' => __( 'Example : 1600 Amphitheatre Pkwy', 'vk-google-job-posting-manager' ),
				'required'    => false,
			),
			'vkjp_validThrough'  => array(
				'label'       => __( 'Expiry Date', 'vk-google-job-posting-manager' ),
				'type'        => 'date',
				'description' => __( 'Please enter expiry date. If you are not sure about expiry date, please leave it blank.', 'vk-google-job-posting-manager' ),
				'required'    => false,
			)
		);

		return $custom_fields_array;
	}

	public static function save_custom_fields() {
		$custom_fields_array = self::custom_fields_array();
		self::save_cf_value( $custom_fields_array );
	}
}

VGJPM_Custom_Field_Job_Post::init();
