<?php
/**
 * Class SampleTest
 *
 * @package Vk_Job_Posting
 */
require_once dirname( dirname( __FILE__ ) ) . '/vk-google-job-posting-manager.php';
require_once dirname( dirname( __FILE__ ) ) . '/blocks/vk-google-job-posting-manager-block.php';
require_once dirname( dirname( __FILE__ ) ) . '/inc/custom-field-builder/custom-field-builder-config.php';
require_once dirname( dirname( __FILE__ ) ) . '/inc/custom-field-builder/package/custom-field-builder.php';

/**
 * Sample test case.
 */
class DefaultTest extends WP_UnitTestCase {

	/**
	 *
	 */
	function test_01() {

		$custom_fields = array(
			array(
				'FULL_TIME',
				'PART_TIME',
				'CONTRACTOR',
				'TEMPORARY',
			),
			array(
				'TELECOMMUTE',
			)
		);

		$output = array(
			array(
				'FULL TIME',
				'PART TIME',
				'CONTRACTOR',
				'TEMPORARY',
			),
			array(
				'Remote Work',
			)
		);

		foreach ( $output as $key => $value ) {

			$expected = $output[ $key ];
			$actual   = vgjpm_get_labels( $custom_fields[ $key ] );

			$this->assertSame( $expected, $actual );

		}
	}

	/**
	 *
	 */
	function test_02() {

		$custom_fields = array(
			array(
				'FULL_TIME',
				'PART_TIME',
			),
			array(
				'TELECOMMUTE',
			)
		);

		$output = array(
			array(
				'FULL TIME, PART TIME',
			),
			array(
				'Remote Work',
			),
		);

		foreach ( $custom_fields as $key => $value ) {

			$expected = $output[ $key ][0];
			$actual   = vgjpm_get_label_of_array( $value );

			$this->assertSame( $expected, $actual );

		}
	}

}
