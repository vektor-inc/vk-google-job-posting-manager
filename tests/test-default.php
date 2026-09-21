<?php
if ( ! defined( 'ABSPATH' ) && ( getenv( 'WP_TESTS_DIR' ) || getenv( 'WP_PHPUNIT__DIR' ) ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
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
 * Probe class used to detect PHP object injection.
 * PHP オブジェクトインジェクションの検出に使うプローブ用クラス。
 *
 * __wakeup() is called when unserialize() instantiates this class, so a true
 * value in $woken means a stored value reached a class's magic method.
 * unserialize() がこのクラスをインスタンス化すると __wakeup() が呼ばれる。
 * $woken が true になった場合、保存された値がクラスのマジックメソッドに到達したことを意味する。
 */
class VGJPM_Injection_Probe {

	/**
	 * Whether __wakeup() has been called.
	 * __wakeup() が呼ばれたかどうか。
	 *
	 * @var bool
	 */
	public static $woken = false;

	/**
	 * Record that this class was instantiated by unserialize().
	 * unserialize() によってインスタンス化されたことを記録する。
	 *
	 * @return void
	 */
	public function __wakeup() {
		self::$woken = true;
	}
}

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
		$this->assertSame( '<h1>技術者絶賛募集中！</h1>', $decoded['description'] );
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
	 * Verifies that allowed HTML tags in description are preserved in JSON-LD output.
	 */
	function test_generate_jsonLD_keeps_allowed_html_in_description() {
		$custom_fields = array(
			'vkjp_title'                           => 'タイトル',
			'vkjp_description'                     => '<h1><strong>募集</strong> <em>詳細</em></h1>',
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

		$this->assertSame( '<h1><strong>募集</strong> <em>詳細</em></h1>', $decoded['description'] );
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
	 * Empty title must suppress JSON-LD output entirely.
	 *
	 * Sidebar panel may persist empty-string post meta for vkjp_title via
	 * REST. The generator must reject blank titles to avoid emitting
	 * payload-less JSON-LD that triggers Search Console errors.
	 * サイドバーパネル経由で REST に空文字の vkjp_title が保存され得るため、
	 * 空タイトルでは JSON-LD を一切返さないことを検証する。
	 */
	function test_generate_jsonLD_returns_null_when_title_is_empty_string() {
		$custom_fields = array(
			'vkjp_title' => '',
		);
		$this->assertNull( vgjpm_generate_jsonLD( $custom_fields ) );
	}

	/**
	 * Missing title must also suppress JSON-LD output (preserves prior behavior).
	 * vkjp_title キーが存在しない場合も従来通り出力されないことを検証する。
	 */
	function test_generate_jsonLD_returns_null_when_title_is_missing() {
		$custom_fields = array();
		$this->assertNull( vgjpm_generate_jsonLD( $custom_fields ) );
	}

	/**
	 * Title that is null should not produce JSON-LD either.
	 * null タイトルでも JSON-LD を出さないことを検証する。
	 */
	function test_generate_jsonLD_returns_null_when_title_is_null() {
		$custom_fields = array(
			'vkjp_title' => null,
		);
		$this->assertNull( vgjpm_generate_jsonLD( $custom_fields ) );
	}

	/**
	 * Whitespace-only titles must not produce JSON-LD.
	 *
	 * Covers half-width space, tab, newline, and full-width space (U+3000)
	 * which is a common copy-paste artifact in Japanese input.
	 * 半角スペース・タブ・改行・全角スペース (U+3000) のみのタイトルでも
	 * JSON-LD が出力されないことを検証する。
	 *
	 * @dataProvider provide_whitespace_titles
	 *
	 * @param string $title 検証するタイトル文字列。
	 */
	function test_generate_jsonLD_returns_null_when_title_is_whitespace_only( $title ) {
		$custom_fields = array(
			'vkjp_title' => $title,
		);
		$this->assertNull( vgjpm_generate_jsonLD( $custom_fields ) );
	}

	/**
	 * Data provider for whitespace-only titles.
	 * 空白のみタイトルのデータプロバイダ。
	 *
	 * @return array
	 */
	public function provide_whitespace_titles() {
		return array(
			'spaces only'         => array( '   ' ),
			'tab only'            => array( "\t" ),
			'newline only'        => array( "\n" ),
			'mixed whitespace'    => array( " \t\n " ),
			'full-width space'    => array( '　' ),
			'mixed full and half' => array( " 　\t" ),
		);
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

	/**
	 * A value that is not a serialized string is returned untouched.
	 * シリアライズされた文字列でない値はそのまま返る。
	 */
	function test_maybe_unserialize_without_object_returns_plain_value_as_is() {
		$this->assertSame( 'FULL_TIME', vgjpm_maybe_unserialize_without_object( 'FULL_TIME' ) );
		$this->assertSame( 123, vgjpm_maybe_unserialize_without_object( 123 ) );
		$this->assertSame( array( 'a' ), vgjpm_maybe_unserialize_without_object( array( 'a' ) ) );
	}

	/**
	 * A serialized array is restored as an array.
	 * シリアライズされた配列は配列として復元される。
	 */
	function test_maybe_unserialize_without_object_restores_array() {
		$expected = array( 'FULL_TIME', 'PART_TIME' );
		$actual   = vgjpm_maybe_unserialize_without_object( serialize( $expected ) );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * A serialized boolean false is restored as false, not treated as a failure.
	 * シリアライズされた false は失敗扱いにせず false として復元される。
	 */
	function test_maybe_unserialize_without_object_restores_serialized_false() {
		$this->assertFalse( vgjpm_maybe_unserialize_without_object( 'b:0;' ) );
	}

	/**
	 * A serialized object is discarded instead of being restored.
	 * シリアライズされたオブジェクトは復元されず破棄される。
	 */
	function test_maybe_unserialize_without_object_discards_object() {
		$this->assertSame( '', vgjpm_maybe_unserialize_without_object( 'O:8:"stdClass":1:{s:3:"foo";s:3:"bar";}' ) );
	}

	/**
	 * A serialized object nested inside an array is discarded as well.
	 * 配列の中に入れ込まれたオブジェクトも同様に破棄される。
	 */
	function test_maybe_unserialize_without_object_discards_nested_object() {
		$payload = 'a:1:{i:0;a:1:{s:5:"inner";O:8:"stdClass":0:{}}}';

		$this->assertSame( '', vgjpm_maybe_unserialize_without_object( $payload ) );
	}

	/**
	 * Broken serialized data results in an empty string.
	 * 壊れたシリアライズデータは空文字になる。
	 */
	function test_maybe_unserialize_without_object_discards_broken_data() {
		$this->assertSame( '', vgjpm_maybe_unserialize_without_object( 'a:2:{i:0;s:1:"a";}' ) );
	}

	/**
	 * Objects are detected at the top level and at any depth of an array.
	 * オブジェクトは最上位でも配列の何階層目でも検出される。
	 */
	function test_contains_object() {
		$this->assertTrue( vgjpm_contains_object( new stdClass() ) );
		$this->assertTrue( vgjpm_contains_object( array( 'a' => array( 'b' => new stdClass() ) ) ) );
		$this->assertFalse( vgjpm_contains_object( array( 'a' => array( 'b' => 'c' ) ) ) );
		$this->assertFalse( vgjpm_contains_object( 'string' ) );
	}

	/**
	 * A serialized object written straight into the database stays out of the
	 * returned fields.
	 * データベースに直接書き込まれたシリアライズ済みオブジェクトは、返り値に入らない。
	 */
	function test_get_custom_fields_does_not_restore_object_from_meta() {
		$post_id = self::factory()->post->create();

		// Prime the meta cache with a value that is serialized exactly once,
		// which is how an importer or a direct database write leaves it.
		// シリアライズが 1 回だけかかった値をメタキャッシュに積む。
		// インポーターや DB への直接書き込みで残る状態がこれに当たる。
		wp_cache_set(
			$post_id,
			array( 'vkjp_title' => array( 'O:8:"stdClass":1:{s:3:"foo";s:3:"bar";}' ) ),
			'post_meta'
		);

		$custom_fields = vgjpm_get_custom_fields( $post_id );

		$this->assertArrayHasKey( 'vkjp_title', $custom_fields );
		$this->assertFalse( vgjpm_contains_object( $custom_fields ) );
		$this->assertSame( '', $custom_fields['vkjp_title'] );
	}

	/**
	 * Restoring a value must not reach an object's magic methods.
	 * 値の復元でオブジェクトのマジックメソッドに到達してはならない。
	 */
	function test_maybe_unserialize_without_object_does_not_wake_object() {
		$payload = serialize( new VGJPM_Injection_Probe() );

		VGJPM_Injection_Probe::$woken = false;
		$restored                     = vgjpm_maybe_unserialize_without_object( $payload );

		$this->assertFalse( VGJPM_Injection_Probe::$woken );
		$this->assertSame( '', $restored );
	}

	/**
	 * Rendering the job posting meta box must not instantiate an object from a
	 * checkbox field, however the stored value was crafted.
	 * 求人情報メタボックスの表示時に、チェックボックスの項目の値から
	 * オブジェクトを生成してはならない（どのような値が保存されていても）。
	 */
	function test_meta_box_form_does_not_instantiate_object_from_checkbox_meta() {
		global $post;

		$post_id = self::factory()->post->create();

		// Reproduce what the post edit screen stores when a string is submitted
		// for a checkbox field instead of the expected array.
		// チェックボックスの項目に、想定される配列ではなく文字列が送信されたときに
		// 投稿編集画面が保存する状態を再現する。
		update_post_meta( $post_id, 'vkjp_employmentType', serialize( new VGJPM_Injection_Probe() ) );

		$post = get_post( $post_id );

		VGJPM_Injection_Probe::$woken = false;

		// form_table() prints a nonce field of its own, so the output is buffered
		// to keep it out of the test report.
		// form_table() は自前で nonce フィールドを出力するため、
		// テスト結果に混ざらないようバッファに取る。
		ob_start();
		$form_html = VK_Custom_Field_Builder::form_table( VGJPM_Custom_Field_Job_Post::custom_fields_array(), '', false );
		ob_end_clean();

		$this->assertFalse( VGJPM_Injection_Probe::$woken );
		$this->assertIsString( $form_html );
	}

	/**
	 * Meta keys that do not belong to this plugin are excluded.
	 * このプラグインのものではないメタキーは除外される。
	 */
	function test_get_custom_fields_excludes_other_meta_keys() {
		$post_id = self::factory()->post->create();
		add_post_meta( $post_id, 'vkjp_title', 'Job title' );
		add_post_meta( $post_id, 'other_plugin_data', 'O:8:"stdClass":0:{}' );

		$custom_fields = vgjpm_get_custom_fields( $post_id );

		$this->assertSame( 'Job title', $custom_fields['vkjp_title'] );
		$this->assertArrayNotHasKey( 'other_plugin_data', $custom_fields );
	}

	/**
	 * A checkbox field saved as a serialized array is still restored.
	 * シリアライズされた配列として保存されたチェックボックスの項目は従来どおり復元される。
	 */
	function test_get_custom_fields_restores_checkbox_array() {
		$post_id = self::factory()->post->create();
		add_post_meta( $post_id, 'vkjp_employmentType', array( 'FULL_TIME', 'PART_TIME' ) );

		$custom_fields = vgjpm_get_custom_fields( $post_id );

		$this->assertSame( array( 'FULL_TIME', 'PART_TIME' ), $custom_fields['vkjp_employmentType'] );
	}
}
