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
	 * Test to get array of label correspond to data.
	 */
	function test_01() {
		$input = array(
			array(
				'FULL_TIME',
				'PART_TIME',
				'CONTRACTOR',
				'TEMPORARY',
			),
			array(
				'TELECOMMUTE',
			),
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
			),
		);

		foreach ( $output as $key => $value ) {
			$expected = $output[ $key ];
			$actual   = vgjpm_get_labels( $input[ $key ] );

			$this->assertSame( $expected, $actual );
		}
	}

	/**
	 * Test to return false, when argument is empty.
	 */
	function test_01_2() {
		$input    = '';
		$expected = false;
		$actual   = vgjpm_get_labels( $input );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * Test to get string of concat label.
	 */
	function test_02() {
		$input = array(
			array(
				'FULL_TIME',
				'PART_TIME',
			),
			array(
				'TELECOMMUTE',
			),
		);

		$output = array(
			array(
				'FULL TIME, PART TIME',
			),
			array(
				'Remote Work',
			),
		);

		foreach ( $input as $key => $value ) {
			$expected = $output[ $key ][0];
			$actual   = vgjpm_get_label_of_array( $value );

			$this->assertSame( $expected, $actual );
		}
	}

	/**
	 * Test to return false, when argument is empty.
	 */
	function test_02_2() {
		$input    = '';
		$expected = false;
		$actual   = vgjpm_get_label_of_array( $input );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * Test vgjpm_generate_jsonLD outputs valid JSON with sanitized fields.
	 */
	function test_generate_jsonLD_basic() {
		$custom_fields = array(
			'vkjp_title'                           => '株式会社ベクトル',
			'vkjp_description'                     => '<h1>技術者絶賛募集中！</h1>',
			'vkjp_datePosted'                      => '2025-12-11',
			'vkjp_validThrough'                    => '2025-12-27',
			'vkjp_employmentType'                  => 'FULL_TIME, PART_TIME',
			'vkjp_name'                            => '株式会社ベクトル',
			'vkjp_identifier'                      => '41566456',
			'vkjp_sameAs'                          => 'https://www.vektor-inc.co.jp/',
			'vkjp_logo'                            => 'https://example.com/logo.png',
			'vkjp_streetAddress'                   => '中区栄1-1-1',
			'vkjp_addressLocality'                 => '名古屋市',
			'vkjp_addressRegion'                   => '愛知県',
			'vkjp_postalCode'                      => '94043',
			'vkjp_addressCountry'                  => 'JP',
			'vkjp_currency'                        => 'JPY',
			'vkjp_unitText'                        => 'MONTH',
			'vkjp_minValue'                        => '150000',
			'vkjp_maxValue'                        => '250000',
			'vkjp_jobLocationType'                 => 'TELECOMMUTE',
			'vkjp_applicantLocationRequirements_name' => 'JAPAN',
			'vkjp_directApply'                     => true,
		);

		$json_ld = vgjpm_generate_jsonLD( $custom_fields );
		$json    = preg_replace( '#</?script[^>]*>#i', '', $json_ld );
		$decoded = json_decode( trim( $json ), true );

		$this->assertIsArray( $decoded );
		$this->assertSame( '株式会社ベクトル', $decoded['title'] );
		$this->assertSame( '技術者絶賛募集中！', $decoded['description'] );
		$this->assertSame( array( 'FULL_TIME', 'PART_TIME' ), $decoded['employmentType'] );
		$this->assertSame( 150000, $decoded['baseSalary']['value']['minValue'] );
		$this->assertSame( 250000, $decoded['baseSalary']['value']['maxValue'] );
		$this->assertArrayHasKey( 'jobLocationType', $decoded );
		$this->assertArrayHasKey( 'directApply', $decoded );
	}

	/**
	 * Verifies that JSON-LD generation omits non-numeric or empty salary bounds.
	 *
	 * Constructs custom job fields where minValue is non-numeric and maxValue is empty,
	 * generates JSON-LD via vgjpm_generate_jsonLD, and asserts that neither
	 * `minValue` nor `maxValue` appear under `baseSalary.value`.
	 */
	function test_generate_jsonLD_ignores_non_numeric_salary() {
		$custom_fields = array(
			'vkjp_title'                           => 'タイトル',
			'vkjp_description'                     => '説明',
			'vkjp_datePosted'                      => '2025-12-11',
			'vkjp_validThrough'                    => '2025-12-27',
			'vkjp_employmentType'                  => 'FULL_TIME',
			'vkjp_name'                            => '社名',
			'vkjp_identifier'                      => '12345',
			'vkjp_sameAs'                          => 'https://example.com',
			'vkjp_logo'                            => 'https://example.com/logo.png',
			'vkjp_streetAddress'                   => '1-1',
			'vkjp_addressLocality'                 => 'city',
			'vkjp_addressRegion'                   => 'pref',
			'vkjp_postalCode'                      => '000',
			'vkjp_addressCountry'                  => 'JP',
			'vkjp_currency'                        => 'JPY',
			'vkjp_unitText'                        => 'MONTH',
			'vkjp_minValue'                        => 'abc', // non-numeric
			'vkjp_maxValue'                        => '',    // empty
			'vkjp_jobLocationType'                 => '',
			'vkjp_applicantLocationRequirements_name' => '',
			'vkjp_directApply'                     => false,
		);

		$json_ld = vgjpm_generate_jsonLD( $custom_fields );
		$json    = preg_replace( '#</?script[^>]*>#i', '', $json_ld );
		$decoded = json_decode( trim( $json ), true );

		$this->assertIsArray( $decoded );
		$this->assertArrayNotHasKey( 'minValue', $decoded['baseSalary']['value'] );
		$this->assertArrayNotHasKey( 'maxValue', $decoded['baseSalary']['value'] );
	}

	/**
	 * Verifies that a title containing <script> tags is sanitized in the generated JSON-LD.
	 *
	 * Asserts the resulting `title` value has script tags removed and preserves the inner text.
	 */
	function test_generate_jsonLD_strips_title_script() {
		$custom_fields = array(
			'vkjp_title'                           => '<script>alert(1)</script>タイトル',
			'vkjp_description'                     => '説明',
			'vkjp_datePosted'                      => '2025-12-11',
			'vkjp_validThrough'                    => '2025-12-27',
			'vkjp_employmentType'                  => 'FULL_TIME',
			'vkjp_name'                            => '社名',
			'vkjp_identifier'                      => '12345',
			'vkjp_sameAs'                          => 'https://example.com',
			'vkjp_logo'                            => 'https://example.com/logo.png',
			'vkjp_streetAddress'                   => '1-1',
			'vkjp_addressLocality'                 => 'city',
			'vkjp_addressRegion'                   => 'pref',
			'vkjp_postalCode'                      => '000',
			'vkjp_addressCountry'                  => 'JP',
			'vkjp_currency'                        => 'JPY',
			'vkjp_unitText'                        => 'MONTH',
			'vkjp_minValue'                        => '',
			'vkjp_maxValue'                        => '',
			'vkjp_jobLocationType'                 => '',
			'vkjp_applicantLocationRequirements_name' => '',
			'vkjp_directApply'                     => false,
		);

		$json_ld = vgjpm_generate_jsonLD( $custom_fields );
		$json    = preg_replace( '#</?script[^>]*>#i', '', $json_ld );
		$decoded = json_decode( trim( $json ), true );

		$this->assertSame( 'alert(1)タイトル', $decoded['title'] );
	}

	/**
	 * JSON special characters should remain valid and unbroken.
	 */
	function test_generate_jsonLD_escapes_special_chars() {
		$custom_fields = array(
			'vkjp_title'                           => 'ダブル"クォート & アポ\'ス <タグ>',
			'vkjp_description'                     => '説明 " & < >',
			'vkjp_datePosted'                      => '2025-12-11',
			'vkjp_validThrough'                    => '2025-12-27',
			'vkjp_employmentType'                  => 'FULL_TIME',
			'vkjp_name'                            => '社名',
			'vkjp_identifier'                      => '12345',
			'vkjp_sameAs'                          => 'https://example.com',
			'vkjp_logo'                            => 'https://example.com/logo.png',
			'vkjp_streetAddress'                   => '1-1',
			'vkjp_addressLocality'                 => 'city',
			'vkjp_addressRegion'                   => 'pref',
			'vkjp_postalCode'                      => '000',
			'vkjp_addressCountry'                  => 'JP',
			'vkjp_currency'                        => 'JPY',
			'vkjp_unitText'                        => 'MONTH',
			'vkjp_minValue'                        => '',
			'vkjp_maxValue'                        => '',
			'vkjp_jobLocationType'                 => '',
			'vkjp_applicantLocationRequirements_name' => '',
			'vkjp_directApply'                     => false,
		);

		$json_ld = vgjpm_generate_jsonLD( $custom_fields );
		$json    = preg_replace( '#</?script[^>]*>#i', '', $json_ld );
		$decoded = json_decode( trim( $json ), true );

		$this->assertSame( 'ダブル"クォート & アポ\'ス', $decoded['title'] );
		$this->assertSame( '説明 " & < >', $decoded['description'] );
	}

	/**
	 * employmentType should strip stray quotes.
	 */
	function test_generate_jsonLD_employmentType_strips_quotes() {
		$custom_fields = array(
			'vkjp_title'                           => 'タイトル',
			'vkjp_description'                     => '説明',
			'vkjp_datePosted'                      => '2025-12-11',
			'vkjp_validThrough'                    => '2025-12-27',
			'vkjp_employmentType'                  => '"FULL_TIME","PART_TIME"',
			'vkjp_name'                            => '社名',
			'vkjp_identifier'                      => '12345',
			'vkjp_sameAs'                          => 'https://example.com',
			'vkjp_logo'                            => 'https://example.com/logo.png',
			'vkjp_streetAddress'                   => '1-1',
			'vkjp_addressLocality'                 => 'city',
			'vkjp_addressRegion'                   => 'pref',
			'vkjp_postalCode'                      => '000',
			'vkjp_addressCountry'                  => 'JP',
			'vkjp_currency'                        => 'JPY',
			'vkjp_unitText'                        => 'MONTH',
			'vkjp_minValue'                        => '',
			'vkjp_maxValue'                        => '',
			'vkjp_jobLocationType'                 => '',
			'vkjp_applicantLocationRequirements_name' => '',
			'vkjp_directApply'                     => false,
		);

		$json_ld = vgjpm_generate_jsonLD( $custom_fields );
		$json    = preg_replace( '#</?script[^>]*>#i', '', $json_ld );
		$decoded = json_decode( trim( $json ), true );

		$this->assertSame( array( 'FULL_TIME', 'PART_TIME' ), $decoded['employmentType'] );
	}
}