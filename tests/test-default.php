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
	 * Post meta keys that a test registered for posts, to be unregistered in tear_down().
	 * テストが投稿（post）に新しく登録し、tear_down() で外すメタキー。
	 *
	 * @var array
	 */
	private $registered_post_meta_keys = array();

	/**
	 * Previous value of the option that enables the job posting fields for posts, or false if it did not exist.
	 * 投稿（post）で求人情報の入力を有効にするオプションの元の値。無かったときは false。
	 *
	 * @var string|false|null
	 */
	private $post_fields_option_backup = null;

	/**
	 * Undo what register_panel_meta_for_posts() changed, even when a test fails.
	 * テストが失敗しても、register_panel_meta_for_posts() で変えたものを元に戻す。
	 *
	 * Registered meta is not restored by WP_UnitTestCase, so it is unregistered here.
	 * 登録したメタは WP_UnitTestCase が自動で戻さないため、ここで外す。
	 *
	 * @return void
	 */
	public function tear_down() {
		// このテストで新しく登録したメタだけを外す。
		// Unregister only the meta that this test newly registered.
		foreach ( $this->registered_post_meta_keys as $meta_key ) {
			unregister_post_meta( 'post', $meta_key );
		}
		$this->registered_post_meta_keys = array();

		// オプションを元の値に戻す。
		// Restore the option.
		if ( null !== $this->post_fields_option_backup ) {
			if ( false === $this->post_fields_option_backup ) {
				delete_option( 'vgjpm_post_type_display_customfieldspost' );
			} else {
				update_option( 'vgjpm_post_type_display_customfieldspost', $this->post_fields_option_backup );
			}
			$this->post_fields_option_backup = null;
		}

		parent::tear_down();
	}

	/**
	 * Enable the job posting fields for posts and register their post meta, as on real sites.
	 * 実際のサイトと同じく、投稿（post）で求人情報の入力を有効にして投稿メタを登録する。
	 *
	 * What is changed here is undone in tear_down().
	 * ここで変えたものは tear_down() で元に戻す。
	 *
	 * @return void
	 */
	private function register_panel_meta_for_posts() {
		global $wp_meta_keys;

		// 登録前のメタキーとオプションを控える。
		// Keep the meta keys and the option before registering.
		$before = isset( $wp_meta_keys['post']['post'] ) ? array_keys( $wp_meta_keys['post']['post'] ) : array();
		if ( null === $this->post_fields_option_backup ) {
			$this->post_fields_option_backup = get_option( 'vgjpm_post_type_display_customfieldspost' );
		}

		update_option( 'vgjpm_post_type_display_customfieldspost', 'true' );
		vgjpm_register_panel_meta();

		// 新しく登録されたメタキーを、tear_down() で外す対象にする。
		// Mark the newly registered meta keys to be unregistered in tear_down().
		$after                           = isset( $wp_meta_keys['post']['post'] ) ? array_keys( $wp_meta_keys['post']['post'] ) : array();
		$this->registered_post_meta_keys = array_unique( array_merge( $this->registered_post_meta_keys, array_diff( $after, $before ) ) );
	}

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

		// An array that points at itself is reported as unusable instead of
		// being walked forever.
		// 自分自身を指す配列は、たどり続けずに使えない値として報告される。
		$self_referencing   = array();
		$self_referencing[] = &$self_referencing;
		$this->assertTrue( vgjpm_contains_object( $self_referencing ) );

		// A nesting depth within the limit is still walked to the end.
		// 上限内の入れ子は最後までたどられる。
		$nested = 'leaf';
		for ( $i = 0; $i < 60; $i++ ) {
			$nested = array( $nested );
		}
		$this->assertFalse( vgjpm_contains_object( $nested ) );
	}

	/**
	 * A stored value that points at itself is discarded without running out of
	 * memory.
	 * 自分自身を指す保存値は、メモリを使い切ることなく破棄される。
	 */
	function test_maybe_unserialize_without_object_discards_self_referencing_array() {
		// A value serialized exactly once, as an importer or a direct database
		// write leaves it, whose only element points back at the array itself.
		// インポーターや DB への直接書き込みで残る、シリアライズが 1 回だけかかった値。
		// 唯一の要素が配列自身を指し返している。
		$payload = 'a:1:{i:0;R:1;}';

		$this->assertSame( '', vgjpm_maybe_unserialize_without_object( $payload ) );
	}

	/**
	 * Job posting meta must be readable and writable through REST for every target
	 * post type, even when the post type does not declare `custom-fields` support.
	 * 求人情報のメタは、`custom-fields` サポートを宣言していない投稿タイプを含め、
	 * すべての対象投稿タイプで REST から読み書きできなければならない。
	 */
	function test_vgjpm_register_panel_meta() {
		global $wp_rest_server, $_wp_post_type_features;

		$features_backup = $_wp_post_type_features;
		$meta_keys       = array( 'vkjp_title', 'vkjp_description' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		// 他所の投稿タイプ登録（init 優先度 10〜99）より後に実行されること。
		// It must run after other plugins register their post types on init (priority 10 to 99).
		$this->assertGreaterThan( 99, has_action( 'init', 'vgjpm_register_panel_meta' ) );

		$test_cases = array(
			array(
				'test_condition_name' => '投稿（標準で custom-fields 対応）の場合 => meta が出て保存できる',
				'post_type'           => 'post',
				'register_args'       => null,
				'expected_read'       => 'タイトル',
				'expected_saved'      => 'saved',
			),
			array(
				'test_condition_name' => '固定ページ（標準で custom-fields 対応）の場合 => meta が出て保存できる',
				'post_type'           => 'page',
				'register_args'       => null,
				'expected_read'       => 'タイトル',
				'expected_saved'      => 'saved',
			),
			array(
				'test_condition_name' => '組み込みの job-posts（custom-fields 対応）の場合 => meta が出て保存できる',
				'post_type'           => 'job-posts',
				'register_args'       => 'builtin',
				'expected_read'       => 'タイトル',
				'expected_saved'      => 'saved',
			),
			array(
				'test_condition_name' => 'custom-fields 非対応の他所で登録された投稿タイプの場合 => meta が出て保存できる',
				'post_type'           => 'job_offer',
				'register_args'       => array( 'supports' => array( 'title', 'editor' ) ),
				'expected_read'       => 'タイトル',
				'expected_saved'      => 'saved',
			),
			array(
				'test_condition_name' => 'custom-fields 対応の他所で登録された投稿タイプの場合 => meta が出て保存できる',
				'post_type'           => 'job_offer_cf',
				'register_args'       => array( 'supports' => array( 'title', 'editor', 'custom-fields' ) ),
				'expected_read'       => 'タイトル',
				'expected_saved'      => 'saved',
			),
		);

		foreach ( $test_cases as $case ) {
			$post_type = $case['post_type'];

			// 投稿タイプを登録して対象にする。
			// Register the post type and make it a target.
			if ( 'builtin' === $case['register_args'] ) {
				update_option( 'vgjpm_create_jobpost_posttype', 'true' );
				require_once dirname( __DIR__ ) . '/inc/custom-posttype-builder.php';
				vgjpm_job_post_init();
			} elseif ( is_array( $case['register_args'] ) ) {
				register_post_type(
					$post_type,
					array_merge(
						array(
							'public'       => true,
							'show_in_rest' => true,
							'rest_base'    => $post_type,
						),
						$case['register_args']
					)
				);
				update_option( 'vgjpm_post_type_display_customfields' . $post_type, 'true' );
			} else {
				update_option( 'vgjpm_post_type_display_customfields' . $post_type, 'true' );
			}

			vgjpm_register_panel_meta();

			// REST サーバーを作り直す。
			// Rebuild the REST server.
			$wp_rest_server = null;
			rest_get_server();

			$post_id = self::factory()->post->create( array( 'post_type' => $post_type ) );
			update_post_meta( $post_id, 'vkjp_title', $case['expected_read'] );
			$route = '/wp/v2/' . get_post_type_object( $post_type )->rest_base . '/' . $post_id;

			// 読み出し: meta に求人情報が含まれる。
			// Read: the job posting meta is included.
			$request = new WP_REST_Request( 'GET', $route );
			$request->set_param( 'context', 'edit' );
			$data = rest_do_request( $request )->get_data();
			$this->assertArrayHasKey( 'meta', $data, $case['test_condition_name'] . '（meta キー）' );
			$this->assertSame( $case['expected_read'], isset( $data['meta']['vkjp_title'] ) ? $data['meta']['vkjp_title'] : null, $case['test_condition_name'] . '（読み出し）' );

			// 保存: meta を送るとサニタイズされて保存される。
			// Save: meta sent in the request is sanitized and stored.
			$request = new WP_REST_Request( 'POST', $route );
			$request->set_body_params( array( 'meta' => array( 'vkjp_title' => '<b>saved</b>' ) ) );
			rest_do_request( $request );
			$this->assertSame( $case['expected_saved'], get_post_meta( $post_id, 'vkjp_title', true ), $case['test_condition_name'] . '（保存）' );

			// 後片付け。
			// Clean up.
			foreach ( $meta_keys as $meta_key ) {
				unregister_meta_key( 'post', $meta_key, $post_type );
			}
			wp_delete_post( $post_id, true );
			delete_option( 'vgjpm_post_type_display_customfields' . $post_type );
			delete_option( 'vgjpm_create_jobpost_posttype' );
			if ( ! in_array( $post_type, array( 'post', 'page' ), true ) ) {
				unregister_post_type( $post_type );
			}
			$_wp_post_type_features = $features_backup;
		}

		$wp_rest_server = null;
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
	 * The same holds for a value that is serialized exactly once, which is how
	 * an importer or a direct database write leaves it.
	 * シリアライズが 1 回だけかかった値でも同じであること。
	 * インポーターや DB への直接書き込みで残る状態がこれに当たる。
	 */
	function test_meta_box_form_does_not_instantiate_object_from_single_serialized_meta() {
		global $post;

		$post_id = self::factory()->post->create();
		$post    = get_post( $post_id );

		wp_cache_set(
			$post_id,
			array( 'vkjp_employmentType' => array( serialize( new VGJPM_Injection_Probe() ) ) ),
			'post_meta'
		);

		VGJPM_Injection_Probe::$woken = false;

		ob_start();
		$form_html = VK_Custom_Field_Builder::form_table( VGJPM_Custom_Field_Job_Post::custom_fields_array(), '', false );
		ob_end_clean();

		$this->assertFalse( VGJPM_Injection_Probe::$woken );
		$this->assertIsString( $form_html );
	}

	/**
	 * Test that save_cf_value() keeps an attachment ID or an http / https URL in the image field, and saves nothing else.
	 * save_cf_value() が、画像フィールド（会社ロゴ）の添付 ID と http / https の URL は保存し、それ以外は保存しないこと。
	 *
	 * @return void
	 */
	public function test_save_cf_value() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$old_url  = 'https://example.com/old-logo.png';

		// 会社ロゴの項目だけを、プラグインの設定のまま保存対象にする。
		// Save only the logo field, using the plugin's own field settings.
		$all_fields = VGJPM_Custom_Field_Job_Post::custom_fields_array();
		$fields     = array( 'vkjp_logo' => $all_fields['vkjp_logo'] );

		$test_cases = array(
			array(
				'test_condition_name' => 'URL が保存済みの投稿を、同じ URL のまま更新 => URL が残る',
				'saved'               => 'https://example.com/logo.png',
				'posted'              => 'https://example.com/logo.png',
				'expected'            => 'https://example.com/logo.png',
			),
			array(
				'test_condition_name' => 'http の URL を送信 => そのまま保存される',
				'saved'               => '',
				'posted'              => 'http://example.com/logo.png',
				'expected'            => 'http://example.com/logo.png',
			),
			array(
				'test_condition_name' => '%20 とクエリとフラグメントを含む URL を送信 => そのまま保存される',
				'saved'               => '',
				'posted'              => 'https://example.com/a%20b.png?x=1#y',
				'expected'            => 'https://example.com/a%20b.png?x=1#y',
			),
			array(
				'test_condition_name' => '半角スペースを含む URL を送信 => スペースを %20 にして保存される',
				'saved'               => '',
				'posted'              => 'https://example.com/a b.png',
				'expected'            => 'https://example.com/a%20b.png',
			),
			array(
				'test_condition_name' => '日本語のファイル名を % で表した URL を送信 => そのまま保存される',
				'saved'               => '',
				'posted'              => 'https://example.com/%E3%83%AD%E3%82%B4.png',
				'expected'            => 'https://example.com/%E3%83%AD%E3%82%B4.png',
			),
			array(
				'test_condition_name' => '前後に空白がある URL を送信 => 空白を除いて保存される',
				'saved'               => '',
				'posted'              => ' https://example.com/logo.png ',
				'expected'            => 'https://example.com/logo.png',
			),
			array(
				'test_condition_name' => '前後にタブや改行がある URL を送信 => タブと改行を除いて保存される',
				'saved'               => '',
				'posted'              => "\thttps://example.com/a.png\n",
				'expected'            => 'https://example.com/a.png',
			),
			array(
				'test_condition_name' => 'ホスト名が制御文字（0x7F）だけの URL を送信 => 保存されない（空になる）',
				'saved'               => $old_url,
				'posted'              => "https://\x7f/logo.png",
				'expected'            => '',
			),
			array(
				'test_condition_name' => 'ホスト名の途中にタブを含む URL を送信 => 保存されない（空になる）',
				'saved'               => $old_url,
				'posted'              => "https://exa\tmple.com/logo.png",
				'expected'            => '',
			),
			array(
				'test_condition_name' => 'ホスト名が整えると消える記号だけの URL を送信 => 保存されない（空になる）',
				'saved'               => $old_url,
				'posted'              => 'https://<>/logo.png',
				'expected'            => '',
			),
			array(
				'test_condition_name' => '添付 ID が保存済みの投稿を、同じ ID のまま更新 => ID が残る',
				'saved'               => '4',
				'posted'              => '4',
				'expected'            => '4',
			),
			array(
				'test_condition_name' => '添付 ID を送信 => ID が保存される',
				'saved'               => $old_url,
				'posted'              => '12',
				'expected'            => '12',
			),
			array(
				'test_condition_name' => '添付 ID に 0 を送信 => 保存されない（空になる）',
				'saved'               => $old_url,
				'posted'              => '0',
				'expected'            => '',
			),
			array(
				'test_condition_name' => 'script タグを送信 => 保存されない（空になる）',
				'saved'               => $old_url,
				'posted'              => '<script>alert(1)</script>',
				'expected'            => '',
			),
			array(
				'test_condition_name' => 'javascript: で始まる値を送信 => 保存されない（空になる）',
				'saved'               => $old_url,
				'posted'              => 'javascript:alert(1)',
				'expected'            => '',
			),
			array(
				'test_condition_name' => 'http / https 以外のスキームの URL を送信 => 保存されない（空になる）',
				'saved'               => $old_url,
				'posted'              => 'ftp://example.com/logo.png',
				'expected'            => '',
			),
			array(
				'test_condition_name' => 'スキームの無い URL を送信 => 保存されない（空になる）',
				'saved'               => $old_url,
				'posted'              => '//example.com/logo.png',
				'expected'            => '',
			),
			array(
				'test_condition_name' => 'URL でも添付 ID でもない文字列を送信 => 保存されない（空になる）',
				'saved'               => $old_url,
				'posted'              => 'not-a-logo',
				'expected'            => '',
			),
			array(
				'test_condition_name' => '空で送信 => 空になる',
				'saved'               => $old_url,
				'posted'              => '',
				'expected'            => '',
			),
		);

		// 同じケースを、投稿メタが未登録の状態と、投稿タイプを有効にしてメタを登録した状態（実際のサイトと同じ）の両方で確かめる。
		// 登録したメタの整形は update_post_meta() の中でも走るため、結果が変わらないことを確かめる。
		// Check the same cases without the registered meta and with it, as on real sites where the post type is enabled.
		// The registered sanitize callback also runs inside update_post_meta(), so the result must not change.
		foreach ( array( '投稿メタ未登録', '投稿メタ登録済み' ) as $state ) {
			if ( '投稿メタ登録済み' === $state ) {
				$this->register_panel_meta_for_posts();
			}

			foreach ( $test_cases as $case ) {
				// 保存済みの値を入れた投稿を用意する。
				// Prepare a post that already has the saved value.
				$post_id = self::factory()->post->create( array( 'post_author' => $admin_id ) );
				if ( '' !== $case['saved'] ) {
					update_post_meta( $post_id, 'vkjp_logo', $case['saved'] );
				}

				global $post;
				$post = get_post( $post_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

				// クラシックエディターの「更新」と同じく、nonce 付きで値を送信して保存する。
				// Submit the value with the nonce, as the classic editor does when the post is updated.
				wp_set_current_user( $admin_id );
				$_POST = array(
					'noncename__fields' => wp_create_nonce( 'vk_custom_field_builder' ),
					'vkjp_logo'         => wp_slash( $case['posted'] ),
				);
				VK_Custom_Field_Builder::save_cf_value( $fields );

				$this->assertSame( $case['expected'], get_post_meta( $post_id, 'vkjp_logo', true ), $state . ' / ' . $case['test_condition_name'] );
			}
		}

		// 送信値と現在のユーザーを戻す。
		// Restore the submitted values and the current user.
		$_POST = array();
		wp_set_current_user( 0 );
	}

	/**
	 * A stored value is read without being restored, so no object is created.
	 * 保存値は復元せずに取得されるため、オブジェクトは生成されない。
	 */
	function test_get_raw_post_meta_returns_stored_value_without_restoring_it() {
		$post_id = self::factory()->post->create();
		$payload = serialize( new VGJPM_Injection_Probe() );

		wp_cache_set(
			$post_id,
			array( 'vkjp_employmentType' => array( $payload ) ),
			'post_meta'
		);

		VGJPM_Injection_Probe::$woken = false;
		$stored_value                 = vgjpm_get_raw_post_meta( $post_id, 'vkjp_employmentType' );

		$this->assertFalse( VGJPM_Injection_Probe::$woken );
		$this->assertSame( $payload, $stored_value );
	}

	/**
	 * An unknown key gives an empty string rather than a notice.
	 * 存在しないキーでは通知を出さず空文字を返す。
	 */
	function test_get_raw_post_meta_returns_empty_string_for_missing_key() {
		$post_id = self::factory()->post->create();

		$this->assertSame( '', vgjpm_get_raw_post_meta( $post_id, 'vkjp_does_not_exist' ) );
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

	/**
	 * Cases for the viewer visibility check: post state x viewer role.
	 * 閲覧可否の確認用ケース（投稿の状態 × 閲覧者の権限）。
	 *
	 * @return array
	 */
	public function provide_job_post_visibility_cases() {
		return array(
			'publish / guest'           => array(
				array( 'post_status' => 'publish' ),
				'guest',
				true,
			),
			'publish / subscriber'      => array(
				array( 'post_status' => 'publish' ),
				'subscriber',
				true,
			),
			'draft / guest'             => array(
				array( 'post_status' => 'draft' ),
				'guest',
				false,
			),
			'draft / subscriber'        => array(
				array( 'post_status' => 'draft' ),
				'subscriber',
				false,
			),
			'draft / editor'            => array(
				array( 'post_status' => 'draft' ),
				'editor',
				true,
			),
			'private / guest'           => array(
				array( 'post_status' => 'private' ),
				'guest',
				false,
			),
			'private / subscriber'      => array(
				array( 'post_status' => 'private' ),
				'subscriber',
				false,
			),
			'private / editor'          => array(
				array( 'post_status' => 'private' ),
				'editor',
				true,
			),
			'password / guest'          => array(
				array(
					'post_status'   => 'publish',
					'post_password' => 'secret',
				),
				'guest',
				false,
			),
			'password / subscriber'     => array(
				array(
					'post_status'   => 'publish',
					'post_password' => 'secret',
				),
				'subscriber',
				false,
			),
			'password / unlocked guest' => array(
				array(
					'post_status'   => 'publish',
					'post_password' => 'secret',
				),
				'unlocked',
				true,
			),
			'draft + password / editor' => array(
				array(
					'post_status'   => 'draft',
					'post_password' => 'secret',
				),
				'editor',
				false,
			),
		);
	}

	/**
	 * Prepare the viewer for the visibility cases.
	 * 閲覧可否のケース用に閲覧者を用意する。
	 *
	 * @param string $viewer guest | subscriber | editor | unlocked.
	 */
	private function set_job_post_viewer( $viewer ) {
		if ( in_array( $viewer, array( 'subscriber', 'editor' ), true ) ) {
			wp_set_current_user( self::factory()->user->create( array( 'role' => $viewer ) ) );
		} else {
			wp_set_current_user( 0 );
		}
		if ( 'unlocked' === $viewer ) {
			// Simulate the cookie set after the correct password was entered.
			// 正しいパスワード入力後に設定される Cookie を再現する。
			require_once ABSPATH . WPINC . '/class-phpass.php';
			$hasher                                 = new PasswordHash( 8, true );
			$_COOKIE[ 'wp-postpass_' . COOKIEHASH ] = $hasher->HashPassword( 'secret' );
		}
	}

	/**
	 * Visibility check of a job post, and the block output that uses it.
	 * 求人投稿の閲覧可否の確認と、それを使うブロック出力の確認。
	 *
	 * @dataProvider provide_job_post_visibility_cases
	 *
	 * @param array  $post_args Post arguments. 投稿の引数。
	 * @param string $viewer    Viewer type. 閲覧者の種類。
	 * @param bool   $expected  Expected result. 期待する結果。
	 */
	function test_can_view_job_post( $post_args, $viewer, $expected ) {
		$post_args['post_author'] = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$post_id                  = self::factory()->post->create( $post_args );
		add_post_meta( $post_id, 'vkjp_title', 'Secret job title' );

		$this->set_job_post_viewer( $viewer );

		$this->assertSame( $expected, vgjpm_can_view_job_post( $post_id ) );

		// The block output follows the same result.
		// ブロックの出力も同じ結果に従う。
		$html = vgjpm_render_job_posting_info( $post_id, 'default', '' );
		$this->assertSame( $expected, false !== strpos( $html, 'Secret job title' ) );

		// post_id 0 points to the current post.
		// post_id 0 は現在の投稿を指す。
		$GLOBALS['post'] = get_post( $post_id );
		$this->assertSame( $expected, vgjpm_can_view_job_post( 0 ) );
		$this->assertSame( $expected, false !== strpos( vgjpm_render_job_posting_info( 0, 'default', '' ), 'Secret job title' ) );

		unset( $_COOKIE[ 'wp-postpass_' . COOKIEHASH ] );
		wp_set_current_user( 0 );
	}

	/**
	 * A missing post is not visible.
	 * 存在しない投稿は閲覧できない。
	 */
	function test_can_view_job_post_returns_false_for_missing_post() {
		$this->assertFalse( vgjpm_can_view_job_post( 99999999 ) );
	}

	/**
	 * The JSON-LD in the head follows the same visibility check.
	 * head の JSON-LD も同じ閲覧可否の確認に従う。
	 *
	 * @dataProvider provide_job_post_visibility_cases
	 *
	 * @param array  $post_args Post arguments. 投稿の引数。
	 * @param string $viewer    Viewer type. 閲覧者の種類。
	 * @param bool   $expected  Expected result. 期待する結果。
	 */
	function test_print_jsonLD_in_footer_follows_visibility( $post_args, $viewer, $expected ) {
		$post_args['post_author'] = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$post_id                  = self::factory()->post->create( $post_args );
		add_post_meta( $post_id, 'vkjp_title', 'Secret job title' );
		update_option( 'vgjpm_post_type_display_customfieldspost', 'true' );

		$this->set_job_post_viewer( $viewer );

		// A post the viewer cannot read becomes a 404 with go_to(), so build the
		// singular view by hand to reach the output code.
		// 読めない投稿は go_to() だと 404 になるため、出力処理に到達するよう単一表示を手で作る。
		global $wp_query;
		$post                         = get_post( $post_id );
		$GLOBALS['post']              = $post;
		$wp_query                     = new WP_Query();
		$wp_query->is_singular        = true;
		$wp_query->is_single          = true;
		$wp_query->queried_object     = $post;
		$wp_query->queried_object_id  = $post_id;
		$wp_query->posts              = array( $post );
		$wp_query->post               = $post;
		$wp_query->post_count         = 1;
		$this->assertTrue( is_singular() );

		ob_start();
		vgjpm_print_jsonLD_in_footer();
		$output = ob_get_clean();

		$this->assertSame( $expected, false !== strpos( $output, 'Secret job title' ) );

		unset( $_COOKIE[ 'wp-postpass_' . COOKIEHASH ] );
		wp_set_current_user( 0 );
	}

	/**
	 * The REST response keeps job posting fields only for viewers who may see the post.
	 * REST 応答の求人情報フィールドは、投稿を見られる閲覧者にだけ返る。
	 *
	 * @dataProvider provide_rest_job_meta_cases
	 *
	 * @param array  $post_args   Post arguments. 投稿の引数。
	 * @param string $viewer      Viewer type. 閲覧者の種類。
	 * @param bool   $expected    Whether vkjp_ fields are returned. vkjp_ 項目が返るか。
	 * @param mixed  $password    Password sent with the request. リクエストで送るパスワード。
	 * @param string $post_type   Post type. 投稿タイプ。
	 * @param int    $view_status Expected status of the view context. view コンテキストで期待するステータス。
	 */
	function test_filter_rest_job_meta( $post_args, $viewer, $expected, $password = null, $post_type = 'post', $view_status = 200 ) {
		global $wp_rest_server, $_wp_post_type_features;

		$features_backup = $_wp_post_type_features;

		// 投稿タイプを登録して対象にする。
		// Register the post type and make it a target.
		if ( 'job-posts' === $post_type ) {
			update_option( 'vgjpm_create_jobpost_posttype', 'true' );
			require_once dirname( __DIR__ ) . '/inc/custom-posttype-builder.php';
			vgjpm_job_post_init();
		} elseif ( 'job_offer' === $post_type ) {
			// custom-fields 非対応の他所で登録された投稿タイプ。
			// A post type registered elsewhere without `custom-fields` support.
			register_post_type(
				$post_type,
				array(
					'public'       => true,
					'show_in_rest' => true,
					'rest_base'    => $post_type,
					'supports'     => array( 'title', 'editor' ),
				)
			);
		}
		update_option( 'vgjpm_post_type_display_customfields' . $post_type, 'true' );
		vgjpm_register_panel_meta();
		register_post_meta(
			$post_type,
			'vgjpm_test_other_meta',
			array(
				'type'         => 'string',
				'single'       => true,
				'show_in_rest' => true,
			)
		);

		$post_args['post_type']   = $post_type;
		$post_args['post_author'] = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$post_id                  = self::factory()->post->create( $post_args );
		add_post_meta( $post_id, 'vkjp_title', 'Secret job title' );
		add_post_meta( $post_id, 'vgjpm_test_other_meta', 'other value' );

		$this->set_job_post_viewer( $viewer );

		$wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init' );

		$rest_base = get_post_type_object( $post_type )->rest_base;
		$this->assertNotEmpty( $rest_base );

		foreach ( array( 'view', 'edit' ) as $context ) {
			$request = new WP_REST_Request( 'GET', '/wp/v2/' . $rest_base . '/' . $post_id );
			$request->set_param( 'context', $context );
			if ( null !== $password ) {
				$request->set_param( 'password', $password );
			}
			$response = $wp_rest_server->dispatch( $request );
			$data     = $response->get_data();

			// Pin the status code so an error response cannot pass by accident.
			// エラー応答で偶然通らないよう、ステータスコードを固定する。
			if ( 'view' === $context ) {
				$this->assertSame( $view_status, $response->get_status(), $context . ' status' );
			}

			// A viewer without edit rights cannot use the edit context at all.
			// 編集権限のない閲覧者は edit コンテキストを使えない。
			if ( 'edit' === $context && 'editor' !== $viewer ) {
				$this->assertArrayNotHasKey( 'meta', $data );
				continue;
			}

			// An error response (e.g. a draft for a guest) has no meta to check.
			// エラー応答（ゲストへの下書き等）は確認する meta を持たない。
			if ( 200 !== $view_status ) {
				$this->assertArrayNotHasKey( 'meta', $data );
				continue;
			}

			$this->assertArrayHasKey( 'meta', $data, $context );
			$has_field = isset( $data['meta']['vkjp_title'] ) && 'Secret job title' === $data['meta']['vkjp_title'];
			$this->assertSame( $expected, $has_field, $context );

			// Other meta stays in the response.
			// 求人情報以外の meta は応答に残る。
			$this->assertSame( 'other value', $data['meta']['vgjpm_test_other_meta'] );
		}

		// Clean up.
		// 後片付け。
		unset( $_COOKIE[ 'wp-postpass_' . COOKIEHASH ] );
		wp_set_current_user( 0 );
		wp_delete_post( $post_id, true );
		remove_filter( 'rest_prepare_' . $post_type, 'vgjpm_filter_rest_job_meta', 10 );
		foreach ( array( 'vkjp_title', 'vkjp_description', 'vgjpm_test_other_meta' ) as $meta_key ) {
			unregister_meta_key( 'post', $meta_key, $post_type );
		}
		delete_option( 'vgjpm_post_type_display_customfields' . $post_type );
		delete_option( 'vgjpm_create_jobpost_posttype' );
		if ( 'post' !== $post_type ) {
			unregister_post_type( $post_type );
		}
		$_wp_post_type_features = $features_backup;
		$wp_rest_server         = null;
	}

	/**
	 * Cases for the REST response: post type x post state x viewer.
	 * REST 応答の確認用ケース（投稿タイプ × 投稿の状態 × 閲覧者）。
	 *
	 * @return array
	 */
	public function provide_rest_job_meta_cases() {
		$password = array(
			'post_status'   => 'publish',
			'post_password' => 'secret',
		);
		$publish  = array( 'post_status' => 'publish' );
		$draft    = array( 'post_status' => 'draft' );
		$cases    = array();

		foreach ( array( 'post', 'job-posts', 'job_offer' ) as $post_type ) {
			$cases[ $post_type . ' / publish / guest' ]               = array( $publish, 'guest', true, null, $post_type );
			$cases[ $post_type . ' / publish / subscriber' ]          = array( $publish, 'subscriber', true, null, $post_type );
			$cases[ $post_type . ' / publish / editor' ]              = array( $publish, 'editor', true, null, $post_type );
			$cases[ $post_type . ' / draft / guest' ]                 = array( $draft, 'guest', false, null, $post_type, 401 );
			$cases[ $post_type . ' / draft / editor' ]                = array( $draft, 'editor', true, null, $post_type );
			$cases[ $post_type . ' / password / guest' ]              = array( $password, 'guest', false, null, $post_type );
			$cases[ $post_type . ' / password / subscriber' ]         = array( $password, 'subscriber', false, null, $post_type );
			$cases[ $post_type . ' / password / editor' ]             = array( $password, 'editor', true, null, $post_type );
			$cases[ $post_type . ' / password / unlocked guest' ]     = array( $password, 'unlocked', true, null, $post_type );
			$cases[ $post_type . ' / password / guest + correct pw' ] = array( $password, 'guest', true, 'secret', $post_type );
			$cases[ $post_type . ' / password / guest + wrong pw' ]   = array( $password, 'guest', false, 'wrong', $post_type, 403 );
			$cases[ $post_type . ' / password / guest + array pw' ]   = array( $password, 'guest', false, array( 'secret' ), $post_type, 400 );
		}

		return $cases;
	}

	/**
	 * Set the site locale for a test (reset automatically by WP_UnitTestCase, which restores $wp_filter).
	 * テスト用にサイトの言語を設定する（WP_UnitTestCase が $wp_filter を自動復元する）。
	 *
	 * @param string $locale ロケール。
	 * @return void
	 */
	private function set_site_locale( $locale ) {
		add_filter(
			'locale',
			function () use ( $locale ) {
				return $locale;
			}
		);
	}

	/**
	 * Provide locale x options cases for the default currency.
	 * 言語 × 選択肢の組み合わせ表を返す。
	 *
	 * @return array
	 */
	public function provide_default_currency_cases() {
		$full    = array(
			'AED' => 'UAE Dirham',
			'JPY' => 'Japanese Yen',
			'USD' => 'US Dollar',
		);
		$no_jpy  = array(
			'AED' => 'UAE Dirham',
			'USD' => 'US Dollar',
		);
		$cases   = array();
		$locales = array(
			'ja'    => 'JPY',
			'ja_JP' => 'JPY',
			'en_US' => '',
			'fr_FR' => '',
			'de_DE' => '',
		);
		foreach ( $locales as $locale => $expected ) {
			$cases[ $locale . ' / full options' ]   = array( $locale, $full, $expected );
			$cases[ $locale . ' / options w/o JPY' ] = array( $locale, $no_jpy, '' );
			$cases[ $locale . ' / empty options' ]   = array( $locale, array(), '' );
		}
		return $cases;
	}

	/**
	 * Test vgjpm_get_default_currency() maps the site language to a currency.
	 * サイトの言語から通貨の初期値が引けること（ja のみ JPY・選択肢に無ければ空）。
	 *
	 * @dataProvider provide_default_currency_cases
	 *
	 * @param string $locale   ロケール。
	 * @param array  $options  選択肢。
	 * @param string $expected 期待する通貨コード。
	 */
	public function test_vgjpm_get_default_currency( $locale, $options, $expected ) {
		$this->set_site_locale( $locale );
		$this->assertSame( $expected, vgjpm_get_default_currency( $options ) );

		// 対応表はフィルターで書き換えられる。
		add_filter(
			'vgjpm_default_currency_map',
			function () {
				return array( 'fr' => 'USD' );
			}
		);
		$this->assertSame( 0 === strpos( $locale, 'fr' ) && isset( $options['USD'] ) ? 'USD' : '', vgjpm_get_default_currency( $options ) );
	}

	/**
	 * Provide locale x post value x common value cases.
	 * 言語 × 投稿の保存値 × 共通設定の保存値の組み合わせ表を返す。
	 * 期待値: 初期値が入るのは「日本語・投稿も共通設定も通貨が空」のときだけ。
	 * ブロックエディターのサイドバーに空の選択肢が無いのは「日本語・共通設定が空」のときだけ（初期値の JPY を表示するため）。
	 * Expected: the block editor sidebar has no empty choice only on Japanese sites without a common currency, because JPY is shown.
	 *
	 * @return array
	 */
	public function provide_currency_combination_cases() {
		$cases = array();
		foreach ( array( 'ja', 'en_US' ) as $locale ) {
			foreach ( array( 'unsaved', '', 'AED', 'USD' ) as $post_value ) {
				foreach ( array( 'unsaved', 'AED', 'USD' ) as $common_value ) {
					$is_empty_both = ( 'unsaved' === $post_value || '' === $post_value ) && 'unsaved' === $common_value;
					$cases[ "$locale / post:$post_value / common:$common_value" ] = array(
						$locale,
						$post_value,
						$common_value,
						( 'ja' === $locale && $is_empty_both ) ? 'JPY' : '',
						! ( 'ja' === $locale && 'unsaved' === $common_value ),
					);
				}
			}
		}
		return $cases;
	}

	/**
	 * Test vgjpm_get_initial_currency() and the selected currency on four screens.
	 * The four screens are the common settings page, the classic editor, the block editor sidebar, and the public output.
	 * 初期値が入るのは日本語かつ両方空のときだけで、保存済みの値・他の言語・公開側の出力は変わらないこと。
	 * 4 画面（共通設定ページ・クラシックエディター・ブロックエディターのサイドバー・公開側の出力）で確かめる。
	 *
	 * @dataProvider provide_currency_combination_cases
	 *
	 * @param string $locale   ロケール。
	 * @param string $post_value   投稿の保存値（unsaved は未保存）。
	 * @param string $common_value 共通設定の保存値（unsaved は未保存）。
	 * @param string $expected_initial 期待する初期値。
	 * @param bool   $expected_has_empty_option Whether the sidebar currency field has an empty choice. サイドバーの通貨欄に空の選択肢があるか。
	 */
	public function test_vgjpm_get_initial_currency( $locale, $post_value, $common_value, $expected_initial, $expected_has_empty_option ) {
		$this->set_site_locale( $locale );

		// 共通設定（unsaved のときは通貨以外の項目だけ保存して、旧形式の移行を避ける）。
		$common = array( 'vkjp_name' => 'Company' );
		if ( 'unsaved' !== $common_value ) {
			$common['vkjp_currency'] = $common_value;
		}
		update_option( 'vkjpm_common_fields', $common );

		// 投稿。
		$post_id = $this->factory->post->create( array( 'post_type' => 'post' ) );
		if ( 'unsaved' !== $post_value ) {
			update_post_meta( $post_id, 'vkjp_currency', $post_value );
		}

		$options = array(
			'AED' => 'UAE Dirham',
			'JPY' => 'Japanese Yen',
			'USD' => 'US Dollar',
		);
		$post_currency   = get_post_meta( $post_id, 'vkjp_currency', true );
		$common_currency = isset( $common['vkjp_currency'] ) ? $common['vkjp_currency'] : '';
		$this->assertSame( $expected_initial, vgjpm_get_initial_currency( $post_currency, $common_currency, $options ) );

		// 共通設定ページ: 選択状態は、保存値があればその値、無ければ初期値。
		$common_fields = vgjpm_get_common_customfields_config();
		$common_html   = vgjpm_render_form_input( array( 'vkjp_currency' => $common_fields['vkjp_currency'] ) );
		$common_expected = '' !== $common_currency ? array( $common_currency ) : ( 'ja' === $locale ? array( 'JPY' ) : array() );
		$this->assertSame( $common_expected, $this->get_selected_values( $common_html, 'name="vkjpm_common_fields[vkjp_currency]"' ) );

		// クラシックエディター: 保存済みの値は変えず、両方空のときだけ初期値が選択状態になる。
		global $post;
		$post = get_post( $post_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		ob_start();
		VGJPM_Custom_Field_Job_Post::fields_form();
		$classic_html = ob_get_clean();
		$classic_expected = array_values( array_unique( array_filter( array( $post_currency, $common_currency ) ) ) );
		if ( '' !== $expected_initial ) {
			$classic_expected = array( $expected_initial );
		}
		sort( $classic_expected );
		$classic_selected = $this->get_selected_values( $classic_html, 'id="vkjp_currency"' );
		sort( $classic_selected );
		$this->assertSame( $classic_expected, $classic_selected );

		// ブロックエディター: 空の選択肢の有無と、JS と同じ式で求めた表示値が選択肢にあること（無いと先頭の通貨が表示される）。
		// Block editor: whether the empty choice exists, and that the value shown by the same expression as JS is one of the choices.
		$panel_data = $this->get_editor_panel_data();
		$this->assertArrayHasKey( 'currencies', $panel_data );
		$panel_values = wp_list_pluck( $panel_data['currencies'], 'value' );
		$panel_shown  = '' !== $post_currency ? $post_currency : ( '' !== $panel_data['commonCurrency'] ? '' : $panel_data['defaultCurrency'] );
		$this->assertSame( $expected_has_empty_option, in_array( '', $panel_values, true ) );
		$this->assertContains( $panel_shown, $panel_values );
		if ( $expected_has_empty_option ) {
			// 空の選択肢は、給与サイクルと同じ表記で先頭に 1 つだけ置く。
			// The empty choice is placed only once at the top, with the same label as the salary cycle.
			$this->assertSame(
				array(
					'value' => '',
					'label' => '—',
				),
				$panel_data['currencies'][0]
			);
			$this->assertSame( 1, count( array_keys( $panel_values, '', true ) ) );
		}

		// 公開側の出力は変わらない（両方空のとき通貨は空のまま）。
		$output = vgjpm_use_common_values( vgjpm_get_custom_fields( $post_id ), 'json' );
		// 投稿に空文字が保存されているときは、従来どおり空文字が出力される（共通設定は使われない）。
		$this->assertSame( 'unsaved' === $post_value ? $common_currency : $post_value, $output['vkjp_currency'] );
	}

	/**
	 * Run vgjpm_enqueue_editor_panel() on the block editor screen for posts and return the data passed to JavaScript.
	 * 投稿のブロックエディター画面で vgjpm_enqueue_editor_panel() を実行し、JavaScript に渡すデータを返す。
	 *
	 * The registered script and the current screen are restored before returning, so that other tests are not affected.
	 * 他のテストに影響しないよう、登録したスクリプトと現在の画面は戻してから返す。
	 *
	 * @return array Data passed to JavaScript (vgjpmPanelData), or an empty array if it is not found. JavaScript に渡すデータ（vgjpmPanelData）。見つからなければ空の配列。
	 */
	private function get_editor_panel_data() {
		// 現在の画面を退避して、投稿のブロックエディター画面にする。
		// Back up the current screen and switch to the block editor screen for posts.
		$screen_backup = array(
			'current_screen' => isset( $GLOBALS['current_screen'] ) ? $GLOBALS['current_screen'] : null,
			'typenow'        => isset( $GLOBALS['typenow'] ) ? $GLOBALS['typenow'] : null,
			'taxnow'         => isset( $GLOBALS['taxnow'] ) ? $GLOBALS['taxnow'] : null,
		);
		set_current_screen( 'post' );
		get_current_screen()->is_block_editor( true );
		update_option( 'vgjpm_post_type_display_customfieldspost', 'true' );

		// パネルのスクリプトを読み込み、JavaScript に渡すデータを取り出す。
		// Enqueue the panel script and read the data passed to JavaScript.
		vgjpm_enqueue_editor_panel();
		$script = wp_scripts()->get_data( 'vgjpm-editor-panel', 'data' );

		// 登録したスクリプトと現在の画面を戻す（スクリプトが残ると、次のテストでデータが追記されるため）。
		// Restore the registered script and the current screen; a left script would get its data appended in the next test.
		wp_dequeue_script( 'vgjpm-editor-panel' );
		wp_deregister_script( 'vgjpm-editor-panel' );
		foreach ( $screen_backup as $name => $value ) {
			$GLOBALS[ $name ] = $value; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restores the screen globals changed by set_current_screen().
		}

		// 「var vgjpmPanelData = {...};」から JSON を取り出して配列に戻す。
		// Extract the JSON from "var vgjpmPanelData = {...};" and decode it.
		if ( ! is_string( $script ) || ! preg_match( '/^var vgjpmPanelData = (.*);$/s', $script, $matches ) ) {
			return array();
		}
		$data = json_decode( $matches[1], true );
		return is_array( $data ) ? $data : array();
	}

	/**
	 * Get the selected option values of the select that contains the given attribute.
	 * 指定した属性を持つ select の、選択状態の option の値を返す。
	 *
	 * @param string $html      HTML。
	 * @param string $select_attr select タグに含まれる属性。
	 * @return array 選択状態の値。
	 */
	private function get_selected_values( $html, $select_attr ) {
		$selected = array();
		if ( ! preg_match( '/<select[^>]*' . preg_quote( $select_attr, '/' ) . '[^>]*>(.*?)<\/select>/s', $html, $m ) ) {
			return $selected;
		}
		preg_match_all( '/<option value="([^"]*)"[^>]*selected/', $m[1], $opts );
		return $opts[1];
	}
}
