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

	$auth_callback = function ( $allowed, $meta_key, $object_id ) {
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
				'auth_callback'     => function ( $allowed, $meta_key, $object_id ) {
					return current_user_can( 'edit_post', $object_id );
				},
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
add_action( 'init', 'vgjpm_register_panel_meta' );

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
