<?php
/**
 * Editor Panel - Register post meta for REST API and enqueue block editor panel script.
 * エディターパネル - REST API用のポストメタ登録とブロックエディターパネルスクリプトの読み込み。
 *
 * @package Vk_Google_Job_Posting_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the list of post types that should have the job posting metabox.
 * 求人情報メタボックスを表示すべき投稿タイプの一覧を取得する。
 *
 * @return array Array of post type slugs.
 */
function vgjpm_get_meta_post_types() {
	$post_types = array();

	// Always include the built-in job-posts post type if it exists.
	// ビルトインの job-posts 投稿タイプが存在する場合は常に含める。
	$vgjpm_flag_custom_posttype = get_option( 'vgjpm_create_jobpost_posttype' );
	if ( $vgjpm_flag_custom_posttype === 'true' ) {
		$post_types[] = 'job-posts';
	}

	// Check other public post types for the metabox setting.
	// 他の公開投稿タイプのメタボックス設定を確認する。
	$public_post_types = get_post_types( array( 'public' => true ), 'object' );
	foreach ( array_keys( $public_post_types ) as $key ) {
		if ( 'attachment' === $key || 'job-posts' === $key ) {
			continue;
		}
		$show = get_option( 'vgjpm_post_type_display_customfields' . $key );
		if ( $show === 'true' ) {
			$post_types[] = $key;
		}
	}

	return $post_types;
}

/**
 * Make REST responses of the target post types include `meta`.
 * 対象の投稿タイプの REST レスポンスに `meta` を含める。
 *
 * WordPress core only exposes `meta` for post types that support `custom-fields`.
 * Post types registered by other plugins or themes may not declare it, so the editor panel would load empty values.
 * The support is added only while the REST API is built, so the classic editor does not get an extra "Custom Fields" meta box.
 * WordPress コアは `custom-fields` をサポートする投稿タイプにだけ `meta` を出す。
 * 他のプラグインやテーマが登録した投稿タイプは宣言していないことがあり、その場合エディターパネルが空欄になる。
 * クラシックエディターに標準の「カスタムフィールド」メタボックスが増えないよう、REST API を組み立てる間だけ追加する。
 *
 * @return void
 */
function vgjpm_add_rest_custom_fields_support() {
	foreach ( vgjpm_get_meta_post_types() as $post_type ) {
		if ( ! post_type_supports( $post_type, 'custom-fields' ) ) {
			add_post_type_support( $post_type, 'custom-fields' );
		}
	}
}
// Run before the REST routes are created from the post type settings (priority 10).
// 投稿タイプの設定から REST ルートが作られる（優先度 10）より前に実行する。
add_action( 'rest_api_init', 'vgjpm_add_rest_custom_fields_support', 5 );

/**
 * Register post meta for all job posting fields so they are available via REST API.
 * すべての求人情報フィールドのポストメタを登録し、REST API経由でアクセス可能にする。
 *
 * @return void
 */
function vgjpm_register_panel_meta() {
	$post_types = vgjpm_get_meta_post_types();
	if ( empty( $post_types ) ) {
		return;
	}

	$auth_callback = function ( $_allowed, $_meta_key, $object_id ) {
		return current_user_can( 'edit_post', $object_id );
	};

	// String fields / 文字列フィールド
	$string_fields = array(
		'vkjp_title',
		'vkjp_minValue',
		'vkjp_maxValue',
		'vkjp_applicantLocationRequirements_name',
		'vkjp_name',
		'vkjp_sameAs',
		'vkjp_logo',
		'vkjp_postalCode',
		'vkjp_addressCountry',
		'vkjp_addressRegion',
		'vkjp_addressLocality',
		'vkjp_streetAddress',
		'vkjp_validThrough',
		'vkjp_identifier',
	);

	// Select fields (stored as string) / セレクトフィールド（文字列として保存）
	$select_fields = array(
		'vkjp_unitText',
		'vkjp_currency',
	);

	// Array fields (checkbox, stored as serialized array) / 配列フィールド（チェックボックス、シリアライズ配列で保存）
	$array_fields = array(
		'vkjp_employmentType',
		'vkjp_jobLocationType',
		'vkjp_directApply',
	);

	foreach ( $post_types as $post_type ) {

		// Strip job posting fields from the REST response for viewers who may not see the post.
		// 閲覧できない閲覧者への REST 応答から求人情報フィールドを取り除く。
		add_filter( 'rest_prepare_' . $post_type, 'vgjpm_filter_rest_job_meta', 10, 3 );

		// Register string fields / 文字列フィールドの登録
		foreach ( $string_fields as $key ) {
			register_post_meta(
				$post_type,
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'sanitize_text_field',
					'auth_callback'     => $auth_callback,
				)
			);
		}

		// Register description field with wp_kses_post sanitizer.
		// description フィールドは HTML を許可するため wp_kses_post でサニタイズする。
		register_post_meta(
			$post_type,
			'vkjp_description',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'wp_kses_post',
				'auth_callback'     => $auth_callback,
			)
		);

		// Register select fields (stored as string) / セレクトフィールドの登録（文字列として保存）
		foreach ( $select_fields as $key ) {
			register_post_meta(
				$post_type,
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'sanitize_text_field',
					'auth_callback'     => $auth_callback,
				)
			);
		}

		// Register array fields (checkbox) / 配列フィールドの登録（チェックボックス）
		foreach ( $array_fields as $key ) {
			register_post_meta(
				$post_type,
				$key,
				array(
					'type'         => 'array',
					'single'       => true,
					'show_in_rest' => array(
						'schema' => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
					),
					'sanitize_callback' => function ( $value ) {
						if ( ! is_array( $value ) ) {
							return array();
						}
						return array_map( 'sanitize_text_field', $value );
					},
					'auth_callback'     => $auth_callback,
				)
			);
		}
	}
}
// Run late so that post types registered by other plugins or themes on `init` already exist.
// 他のプラグインやテーマが `init` で登録する投稿タイプが出そろってから実行する。
add_action( 'init', 'vgjpm_register_panel_meta', 100 );

/**
 * Remove job posting fields from the REST response when the viewer may not see the post.
 * 閲覧者がその投稿を見られない場合に、REST 応答から求人情報フィールドを取り除く。
 *
 * Users who can edit the post always get the fields, so the editor panel keeps working.
 * 投稿を編集できるユーザーには常に返すため、エディターパネルは従来どおり動作する。
 *
 * Viewers who send the correct post password with the request also keep the fields.
 * リクエストで正しい投稿パスワードを送った閲覧者にも項目を残す。
 *
 * @param WP_REST_Response $response REST response. REST 応答。
 * @param WP_Post          $post     Post object. 投稿オブジェクト。
 * @param WP_REST_Request  $request  REST request. REST リクエスト。
 * @return WP_REST_Response REST response. REST 応答。
 */
function vgjpm_filter_rest_job_meta( $response, $post, $request ) {
	// Editors keep every field.
	// 編集権限のあるユーザーは全項目を取得できる。
	if ( current_user_can( 'edit_post', $post->ID ) ) {
		return $response;
	}

	// Viewers who may see the post keep every field.
	// 閲覧できる閲覧者は全項目を取得できる。
	if ( vgjpm_can_view_job_post( $post->ID ) ) {
		return $response;
	}

	// Viewers who sent the correct post password keep every field.
	// 正しい投稿パスワードを送った閲覧者は全項目を取得できる。
	$request_password = $request['password'];
	if ( '' !== $post->post_password && is_string( $request_password ) && hash_equals( $post->post_password, $request_password ) ) {
		return $response;
	}

	// Remove only this plugin's fields from meta.
	// meta から、このプラグインの項目だけを取り除く。
	$data = $response->get_data();
	if ( isset( $data['meta'] ) && is_array( $data['meta'] ) ) {
		foreach ( array_keys( $data['meta'] ) as $key ) {
			if ( 0 === strpos( $key, 'vkjp_' ) ) {
				unset( $data['meta'][ $key ] );
			}
		}
		$response->set_data( $data );
	}

	return $response;
}

/**
 * Enqueue the block editor panel script for job posting fields.
 * 求人情報フィールド用のブロックエディターパネルスクリプトを読み込む。
 *
 * Only loads on post types that have the job posting metabox enabled.
 * 求人情報メタボックスが有効な投稿タイプでのみ読み込む。
 *
 * @return void
 */
function vgjpm_enqueue_editor_panel() {
	$screen = get_current_screen();
	if ( ! $screen || ! $screen->is_block_editor || empty( $screen->post_type ) ) {
		return;
	}

	// Use the same source of truth as REST meta registration.
	// REST メタ登録と同じ関数で対象投稿タイプを判定する。
	if ( ! in_array( $screen->post_type, vgjpm_get_meta_post_types(), true ) ) {
		return;
	}

	// Load the build asset file. / ビルドアセットファイルを読み込む。
	$asset_path = VGJPM_DIR . 'build/editor-panel/index.asset.php';
	if ( ! file_exists( $asset_path ) ) {
		return;
	}
	$asset = include $asset_path;

	wp_enqueue_script(
		'vgjpm-editor-panel',
		VGJPM_URL . 'build/editor-panel/index.js',
		$asset['dependencies'],
		$asset['version'],
		true
	);

	// Build currency list from ISO4217 library.
	// ISO4217ライブラリから通貨一覧を構築する。
	$iso4217    = new Alcohol\ISO4217();
	$currencies = array();
	foreach ( $iso4217->getAll() as $c ) {
		$currencies[] = array(
			'value' => $c['alpha3'],
			'label' => $c['name'],
		);
	}

	// 通貨が未設定のときの初期値（言語に対応する通貨）と、共通設定の通貨。
	// Default currency for unset state, and the common-settings currency.
	$currency_map = array();
	foreach ( $currencies as $c ) {
		$currency_map[ $c['value'] ] = $c['label'];
	}
	$common_options   = vgjpm_get_common_field_options();
	$common_currency  = isset( $common_options['vkjp_currency'] ) && is_string( $common_options['vkjp_currency'] ) ? $common_options['vkjp_currency'] : '';
	$default_currency = vgjpm_get_default_currency( apply_filters( 'vgjpm_currency_options', $currency_map ) );

	// Add an empty choice when an unset currency is shown as empty; without it, the first currency (UAE Dirham) is shown although nothing is saved.
	// It is not added when the language default (JPY) is shown, because choosing it would switch back to JPY.
	// Keep this condition in line with the shown value in src/editor-panel/index.js ( commonCurrency ? '' : defaultCurrency ).
	// 未設定の通貨を空として表示する場合だけ、空の選択肢を足す（無いと、何も保存されていないのに先頭の通貨 UAE Dirham が表示される）。
	// 言語に応じた初期値（JPY）を表示する場合は足さない（選んでも JPY の表示に戻るため）。
	// この条件は src/editor-panel/index.js の表示値の式（commonCurrency ? '' : defaultCurrency）とそろえること。
	if ( '' !== $common_currency || '' === $default_currency ) {
		array_unshift(
			$currencies,
			array(
				'value' => '',
				'label' => '—',
			)
		);
	}

	// Pass i18n strings and field options to JavaScript.
	// i18n文字列とフィールドオプションをJavaScriptに渡す。
	wp_localize_script(
		'vgjpm-editor-panel',
		'vgjpmPanelData',
		array(
			'i18n'       => array(
				// Sidebar title / サイドバータイトル
				'sidebarTitle'        => __( 'Google Job Posting Registration Information', 'vk-google-job-posting-manager' ),
				// Section titles / セクションタイトル
				'jobInfo'             => __( 'Job Information', 'vk-google-job-posting-manager' ),
				'salary'              => __( 'Salary', 'vk-google-job-posting-manager' ),
				'employment'          => __( 'Employment', 'vk-google-job-posting-manager' ),
				'hiringOrganization'  => __( 'Hiring Organization', 'vk-google-job-posting-manager' ),
				'workLocation'        => __( 'Work Location', 'vk-google-job-posting-manager' ),
				'other'               => __( 'Other', 'vk-google-job-posting-manager' ),
				// Field labels / フィールドラベル
				'jobTitle'            => __( 'Job Title', 'vk-google-job-posting-manager' ),
				'jobDescription'      => __( 'Description', 'vk-google-job-posting-manager' ),
				'minSalary'           => __( 'Minimum Value of Salary', 'vk-google-job-posting-manager' ),
				'maxSalary'           => __( 'Max Value of Salary', 'vk-google-job-posting-manager' ),
				'salaryCycle'         => __( 'The Cycle of Salary Payment', 'vk-google-job-posting-manager' ),
				'currency'            => __( 'Currency', 'vk-google-job-posting-manager' ),
				'employmentType'      => __( 'Employment Type', 'vk-google-job-posting-manager' ),
				'telecommute'         => __( 'Remote Work', 'vk-google-job-posting-manager' ),
				'applicantLocationRequirements' => __( 'Countries that allow remote work', 'vk-google-job-posting-manager' ),
				'directApply'         => __( 'Direct Apply', 'vk-google-job-posting-manager' ),
				'organizationName'    => __( 'Hiring Organization Name', 'vk-google-job-posting-manager' ),
				'organizationUrl'     => __( 'Hiring Organization Website', 'vk-google-job-posting-manager' ),
				'logo'                => __( 'Hiring Organization Logo', 'vk-google-job-posting-manager' ),
				'postalCode'          => __( 'Postal Code of work Location', 'vk-google-job-posting-manager' ),
				'country'             => __( 'Country of Work Location', 'vk-google-job-posting-manager' ),
				'region'              => __( 'Address Region of Work Location', 'vk-google-job-posting-manager' ),
				'locality'            => __( 'Address Locality of Work Location', 'vk-google-job-posting-manager' ),
				'streetAddress'       => __( 'Street Address of Work Location', 'vk-google-job-posting-manager' ),
				'validThrough'        => __( 'Expiry Date', 'vk-google-job-posting-manager' ),
				'identifier'          => __( 'Company Identifier Number', 'vk-google-job-posting-manager' ),
				'chooseImage'         => __( 'Choose Image', 'vk-google-job-posting-manager' ),
				'changeImage'         => __( 'Change', 'vk-google-job-posting-manager' ),
				'removeImage'         => __( 'Delete Image', 'vk-google-job-posting-manager' ),
				// Unit text options / 給与サイクルの選択肢
				'perHour'             => __( 'Per hour', 'vk-google-job-posting-manager' ),
				'perDay'              => __( 'Per Day', 'vk-google-job-posting-manager' ),
				'perWeek'             => __( 'Per Week', 'vk-google-job-posting-manager' ),
				'perMonth'            => __( 'Per month', 'vk-google-job-posting-manager' ),
				'perYear'             => __( 'Per year', 'vk-google-job-posting-manager' ),
				// Employment type options / 雇用形態の選択肢
				'fullTime'            => __( 'FULL TIME', 'vk-google-job-posting-manager' ),
				'partTime'            => __( 'PART TIME', 'vk-google-job-posting-manager' ),
				'contractor'          => __( 'CONTRACTOR', 'vk-google-job-posting-manager' ),
				'temporary'           => __( 'TEMPORARY', 'vk-google-job-posting-manager' ),
				'intern'              => __( 'INTERN', 'vk-google-job-posting-manager' ),
				'volunteer'           => __( 'VOLUNTEER', 'vk-google-job-posting-manager' ),
				'perDiem'             => __( 'PER DIEM', 'vk-google-job-posting-manager' ),
				'otherType'           => __( 'OTHER', 'vk-google-job-posting-manager' ),
			),
			'currencies' => $currencies,
			'defaultCurrency' => $default_currency,
			'commonCurrency'  => $common_currency,
		)
	);
}
add_action( 'enqueue_block_editor_assets', 'vgjpm_enqueue_editor_panel' );

/**
 * Remove the legacy metabox on block editor screens.
 * ブロックエディタ画面では旧メタボックスを非表示にする。
 *
 * The new sidebar panel replaces the metabox in the block editor.
 * Classic Editor users will still see the original metabox.
 * 新しいサイドバーパネルがブロックエディタでメタボックスの代わりになる。
 * クラシックエディタのユーザーには従来のメタボックスがそのまま表示される。
 *
 * @return void
 */
function vgjpm_remove_legacy_metabox_on_block_editor() {
	$screen = get_current_screen();
	if ( ! $screen || ! $screen->is_block_editor || empty( $screen->post_type ) ) {
		return;
	}
	if ( ! in_array( $screen->post_type, vgjpm_get_meta_post_types(), true ) ) {
		return;
	}
	remove_meta_box( 'meta_box_job_posting', $screen->post_type, 'advanced' );
}
add_action( 'add_meta_boxes', 'vgjpm_remove_legacy_metabox_on_block_editor', 20 );
