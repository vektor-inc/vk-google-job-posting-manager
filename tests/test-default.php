<?php
/**
 * Class SampleTest
 *
 * @package Vk_Job_Posting
 */
require_once dirname( dirname( __FILE__ ) ) . '/vk-google-job-posting-manager.php';
require_once dirname( dirname( __FILE__ ) ) . '/inc/custom-field-builder/custom-field-builder-config.php';
require_once dirname( dirname( __FILE__ ) ) . '/inc/custom-field-builder/package/custom-field-builder.php';

/**
 * Sample test case.
 */
class DefaultTest extends WP_UnitTestCase {


	/**
	 *	//カスタムフィールドを生成するファイル（custom-field-builder-config.php）から、説明文を抜き出したダミーデータを作成
	 *  array(option_name => 説明文)の形で返す。
	 */
	public function get_dummy_custom_fields() {

		$custom_fields_arr = Job_Posting_Custom_Fields::custom_fields_array();

		//多重配列にforeachで処理
		// array(option_name => 説明文)に整形
		foreach ( $custom_fields_arr as $key => $value ) {
			$custom_fields_arr[ $key ] = $value['description'];
		}

		unset($custom_fields_arr['vkjp_name']);
		unset($custom_fields_arr['vkjp_url']);
		unset($custom_fields_arr['vkjp_logo']);
		unset($custom_fields_arr['vkjp_name']);
		unset($custom_fields_arr['vkjp_streetAddress']);
		unset($custom_fields_arr['vkjp_addressLocality']);
		unset($custom_fields_arr['vkjp_addressRegion']);
		unset($custom_fields_arr['vkjp_postalCode']);
		unset($custom_fields_arr['vkjp_addressCountry']);
		unset($custom_fields_arr['vkjp_currency']);
		unset($custom_fields_arr['vkjp_value']);
		unset($custom_fields_arr['vkjp_minValue']);
		unset($custom_fields_arr['vkjp_maxValue']);
		unset($custom_fields_arr['vkjp_unitText']);

		$custom_fields_arr['vkjp_hiringOrganization'] = array(
			'name' => 'Vektor,Inc',
			'url'  => 'https://example.com',
			'logo' => 'https://example.com/logo.png',
		);
		$custom_fields_arr['vkjp_jobLocation']        = array(
			'streetAddress'   => '名駅4丁目17番3号 メイヨンビル2F',
			'addressLocality' => '名古屋市中村区',
			'addressRegion'   => '愛知県',
			'postalCode'      => '4500002',
			'addressCountry'  => 'JP',
		);
		$custom_fields_arr['vkjp_baseSalary']         = array(
			'currency' => 'YEN',
			'value'    => '250000',
			'minValue' => '150000',
			'maxValue' => '250000',
			'unitText' => 'MONTH',
		);

		return $custom_fields_arr;
	}

	//渡した配列をpostmetaに保存したpostを返す
	public function save_dummy_custom_fields($custom_fields) {

		//postを作成
		$post_id = self::factory()->post->create();

		//引数で渡したデータをpost_metaに保存。
		foreach ( $custom_fields as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}

		//postを返す
		$post = get_post( $post_id );
		return $post;

	}

	public function test_vk_job_posts_get_custom_fields(){

		$customfields = self::get_dummy_custom_fields();
		$post = self::save_dummy_custom_fields($customfields);

		$actual = vk_job_posts_get_custom_fields($post->ID);

		$this->assertSame($actual,$customfields);
	}

	public function test_vk_job_posts_generate_jsonLD(){
		$customfields                            = self::get_dummy_custom_fields();
		$JSON = vk_job_posts_generate_jsonLD( $customfields );

//		$this->assertTrue(true);
		//下のアサーテーションを使うと、整形後のJSONをターミナルで確認できる。
		$this->assertSame($JSON,1);
	}

	/**
	 *　カスタムフィールドの値と共通設定の値が空の時に、''を値に挿入するテスト
	 */
	public function test_use_common_values_no_common_value(){

		$actual_custom_fields = self::get_dummy_custom_fields();

		//ダミーデータのvkjp_titleを空にセット
		$actual_custom_fields['vkjp_title'] = null;

		//関数実行
		$actual = use_common_values($actual_custom_fields);

		$this->assertSame($actual['vkjp_title'],'');
	}

	/**
	 *　カスタムフィールドの値が空の時に、共通設定を取得するテスト
	 */
	public function test_use_common_values(){

		$actual_custom_fields = self::get_dummy_custom_fields();
		$expected_custom_fields = self::get_dummy_custom_fields();

		//共通形式に保存するデータ
		$data = 'test';

		//option_nameとoption_valueを保存を、共通形式で保存。
		update_option('common_vkjp_title',$data);

		//ダミーデータのvkjp_titleを空にセット
		$actual_custom_fields['vkjp_title'] = null;

		//関数実行
		$actual = use_common_values($actual_custom_fields);

		//比較する配列のvkjp_titleにも同じデータをセット。
		$expected_custom_fields['vkjp_title'] = $data;
		$expected = $expected_custom_fields;

		$this->assertSame($actual,$expected);

	}

	public function test_PostType_checkbox_works_good(){

		//postにチェック
		$post_type = 'post';
		update_option( 'vk_job_posts_post_type_display_customfields'.$post_type, "true" );

		//カスタムフィールドを指定の投稿タイプに追加。
		$Job_Posting_Custom_Fields = new Job_Posting_Custom_Fields;
		$Job_Posting_Custom_Fields->check_post_type_show_metabox();

		//posttypeのpostを作成、post
		$post_id = self::factory()->post->create();

		//これで、titleフィールドがついているか確認
		$result = get_post_meta( $post_id, 'vkjp_title');

		$this->assertTrue(isset($result));
	}

	/**
	 *テスト用のユーザーを作成する
	 */
	public function test_Sample_create_user() {
		$user_id = self::factory()->user->create( array(
			'role' => 'editor',
		) );

		$this->assertTrue( user_can( $user_id, 'edit_others_posts' ) );
	}

}
