<?php
/**
 * Plugin Name:     VK Google Job Posting Manager
 * Plugin URI:      https://github.com/vektor-inc/vk-google-job-posting-manager
 * Description:     This is the plugin for Google Job posting
 * Author:          Vektor,Inc
 * Author URI:      https://www.vektor-inc.co.jp
 * Text Domain:     vk-google-job-posting-manager
 * Domain Path:     /languages
 * Version:         1.2.23
 * Requires at least: 6.6
 * License:         GPLv2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package         Vk_Google_Job_Posting_Manager
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/*
Setting & load file
/*-------------------------------------------*/
$vgjpm_prefix = 'common_';
$vgjpm_data   = get_file_data(
	__FILE__,
	array(
		'version'    => 'Version',
		'textdomain' => 'Text Domain',
	)
);
define( 'VGJPM_VERSION', $vgjpm_data['version'] );
define( 'VGJPM_BASENAME', plugin_basename( __FILE__ ) );
define( 'VGJPM_URL', plugin_dir_url( __FILE__ ) );
define( 'VGJPM_DIR', plugin_dir_path( __FILE__ ) );

require_once __DIR__ . '/functions-tags.php';
require_once __DIR__ . '/inc/custom-field-builder/package/custom-field-builder.php';
require_once __DIR__ . '/inc/custom-field-builder/custom-field-builder-config.php';
require_once __DIR__ . '/blocks/vk-google-job-posting-manager-block.php';

if ( ! function_exists( 'vgjpm_set_script_translations' ) ) {
	/**
	 * Set text domain for JavaScript translations.
	 */
	function vgjpm_set_script_translations() {
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'vk-google-job-posting-manager-block-editor', 'vk-google-job-posting-manager' );
		}
	}
	add_action( 'enqueue_block_editor_assets', 'vgjpm_set_script_translations' );
}

function vgjpm_activate() {
	update_option( 'vgjpm_create_jobpost_posttype', 'true' );
}
register_activation_hook( __FILE__, 'vgjpm_activate' );

$vgjpm_flag_custom_posttype = get_option( 'vgjpm_create_jobpost_posttype' );
if ( isset( $vgjpm_flag_custom_posttype ) && $vgjpm_flag_custom_posttype == 'true' ) {
	require_once __DIR__ . '/inc/custom-posttype-builder.php';
}

function vgjpm_add_setting_menu() {
	$custom_page = add_submenu_page(
		'/options-general.php',
		__( 'VK Google Job Posting Manager', 'vk-google-job-posting-manager' ),
		__( 'VK Google Job Posting Manager', 'vk-google-job-posting-manager' ),
		'edit_others_posts',
		'vgjpm_settings',
		'vgjpm_render_settings'
	);
}
add_action( 'admin_menu', 'vgjpm_add_setting_menu' );


// Add a link to this plugin's settings page
function vgjpm_set_plugin_meta( $links ) {
	$settings_link = '<a href="options-general.php?page=vgjpm_settings">' . __( 'Setting', 'vk-google-job-posting-manager' ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'vgjpm_set_plugin_meta', 10, 1 );

// Add Admin Setting Page css
function vgjpm_admin_css() {
	wp_enqueue_media();
	wp_enqueue_style( 'vgjpm-admin-style', plugin_dir_url( __FILE__ ) . 'assets/css/admin.css', array(), VGJPM_VERSION, 'all' );
}
add_action( 'admin_enqueue_scripts', 'vgjpm_admin_css' );

/**
 * 新旧オプション値を変換しつつ古いオプション値を削除
 */
function vgjpm_get_common_field_options() {
	global $vgjpm_prefix;
	$options = get_option( 'vkjpm_common_fields' );
	if ( empty( $options ) ) {
		$old_options_array = array(
			'vkjp_name',
			'vkjp_sameAs',
			'vkjp_logo',
			'vkjp_postalCode',
			'vkjp_addressCountry',
			'vkjp_addressRegion',
			'vkjp_addressLocality',
			'vkjp_streetAddress',
			'vkjp_currency',
			'vkjp_applicantLocationRequirements_name',
		);

		$new_options = array();
		foreach ( $old_options_array as $old_option ) {
			$new_options[ $old_option ] = get_option( $vgjpm_prefix . esc_attr( $old_option ) );
			delete_option( $vgjpm_prefix . esc_attr( $old_option ) );
		}
		update_option( 'vkjpm_common_fields', $new_options );
		$options = $new_options;
	}
	return $options;
}

/**
 * @deprecated Use vgjpm_get_common_field_options() instead.
 */
function vkjpm_get_common_field_options() {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Used for deprecation notice.
	_deprecated_function( __FUNCTION__, VGJPM_VERSION, 'vgjpm_get_common_field_options' );
	return vgjpm_get_common_field_options();
}

function vgjpm_get_common_customfields_config() {
	$VGJPM_Custom_Field_Job_Post = new VGJPM_Custom_Field_Job_Post();
	$labels                      = $VGJPM_Custom_Field_Job_Post->custom_fields_array();

	// 共通設定ページで、共通になりそうな項目が上になるように並び替え
	$common_page_field_order = array(
		'vkjp_name',
		'vkjp_sameAs',
		'vkjp_logo',
		'vkjp_postalCode',
		'vkjp_addressCountry',
		'vkjp_addressRegion',
		'vkjp_addressLocality',
		'vkjp_streetAddress',
		'vkjp_currency',
		'vkjp_applicantLocationRequirements_name',
	);
	$labels_ordered          = array();
	foreach ( $common_page_field_order as $key => $value ) {
		if ( isset( $labels[ $value ] ) ) {
			$labels_ordered[ $value ] = $labels[ $value ];
		}
	}

	$common_customfields = array(
		'vkjp_currency',
		'vkjp_name',
		'vkjp_sameAs',
		'vkjp_logo',
		'vkjp_postalCode',
		'vkjp_addressCountry',
		'vkjp_addressRegion',
		'vkjp_addressLocality',
		'vkjp_streetAddress',
		'vkjp_applicantLocationRequirements_name',
	);

	foreach ( $labels_ordered as $key => $value ) {
		if ( in_array( $key, $common_customfields ) ) {
			$new_array = array(
				'label'       => $value['label'],
				'type'        => $value['type'],
				'description' => $value['description'],
			);

			if ( isset( $value['options'] ) ) {
				$new_array['options'] = $value['options'];
			}

			$label_option_name_pair_arr[ $key ] = $new_array;
		}
	}

	return $label_option_name_pair_arr;
}

function vgjpm_render_settings() {
	$common_custom_fields = vgjpm_get_common_customfields_config();

	vgjpm_save_data( $common_custom_fields );

	echo wp_kses( vgjpm_create_common_form( $common_custom_fields ), vgjpm_allowed_form_html() );
}

/**
 * Common setting page
 *
 * @param  [type] $common_customfields [description]
 *
 * @return [type]                      [description]
 */
function vgjpm_create_common_form( $common_customfields ) {
	$form = '<div class="vgjpm">';

	$form .= '<h1>' . __( 'Job Posting Manager Settings', 'vk-google-job-posting-manager' ) . '</h1>';

	$form .= '<form method="post" action="">';

	$form .= wp_nonce_field( 'standing_on_the_shoulder_of_giants', 'vgjpm_nonce', true, false );

	$form .= '<h2>' . __( 'Create Job-Posts Post type', 'vk-google-job-posting-manager' ) . '</h2>';

	$form .= '<p>' . __( 'This plugin automatically create post type for Job Posting.<br>If you have already created custom post type for Job Post, please remove this check and select post type of next check boxes.', 'vk-google-job-posting-manager' ) . '</p>';
	$form .= vgjpm_create_jobpost_posttype();

	$form .= '<h2>' . __( 'Choose the post type to display job posting custom fields', 'vk-google-job-posting-manager' ) . '</h2>';

	$form .= vgjpm_post_type_check_list();

	$form .= '<h2>' . __( 'Common Fields', 'vk-google-job-posting-manager' ) . '</h2>';

	$form .= '<p>' . __( 'If a single page is filled in, the content of the single page takes precedence.', 'vk-google-job-posting-manager' ) . '</p>';

	$form .= vgjpm_render_form_input( $common_customfields );

	$form .= '<input type="submit" value="' . __( 'Save Changes', 'vk-google-job-posting-manager' ) . '" class="button button-primary">';

	$form .= '</form>';

	$form .= '<div class="footer-logo"><a href="' . esc_url( 'https://www.vektor-inc.co.jp' ) . '"><img src="' . esc_url( plugin_dir_url( __FILE__ ) . 'assets/images/vektor_logo.png' ) . '" alt="Vektor,Inc." /></a></div>';
	$form .= '</div>';

	return $form;
}


/**
 * Common setting page form
 *
 * @param  [type] $common_customfields [description]
 * @return [type]                      [description]
 */
function vgjpm_render_form_input( $common_customfields ) {
	global $vgjpm_prefix;
	$field_prefix = 'vkjpm_common_fields';
	$options      = vgjpm_get_common_field_options();

	$form = '<table class="admin-table">';

	foreach ( $common_customfields as $key => $value ) {
		$form .= '<tr>';
		$form .= '<th>' . esc_html( $value['label'] ) . '</th>';
		$form .= '<td>';

		if ( $value['type'] == 'text' ) {
			$stored = isset( $options[ $key ] ) ? $options[ $key ] : '';
			$form  .= '<input type="text" name="' . $field_prefix . '[' . esc_attr( $key ) . ']' . '" value="' . esc_attr( $stored ) . '">';

		} elseif ( $value['type'] == 'textarea' ) {

			$stored = isset( $options[ $key ] ) ? $options[ $key ] : '';
			$form  .= '<textarea class="form-control cf_textarea_wysiwyg" name="' . $field_prefix . '[' . esc_attr( $key ) . ']' . '" cols="70" rows="3">' . esc_html( $stored ) . '</textarea>';

		} elseif ( $value['type'] == 'datepicker' ) {

			$stored = isset( $options[ $key ] ) ? $options[ $key ] : '';
			$form  .= '<input class="form-control datepicker" type="text" name="' . $field_prefix . '[' . esc_attr( $key ) . ']' . '" value="' . esc_attr( $stored ) . '" size="70">';

		} elseif ( $value['type'] == 'image' ) {

			$saved = isset( $options[ $key ] ) ? $options[ $key ] : '';

			if ( ! empty( $saved ) ) {
				$thumb_image_url = wp_get_attachment_url( $saved );
			} else {
				$thumb_image_url = VGJPM_URL . 'inc/custom-field-builder/package/images/no_image.png';
			}

			// ダミー & プレビュー画像
			$form .= '<img src="' . esc_url( $thumb_image_url ) . '" id="thumb_' . esc_attr( $key ) . '" alt="" class="input_thumb" style="width:200px;height:auto;"> ';
			// 実際に送信する値
			$form .= '<input type="hidden" name="' . $field_prefix . '[' . esc_attr( $key ) . ']' . '" id="' . esc_attr( $key ) . '" value="' . esc_attr( $saved ) . '" style="width:60%;" />';
			// $form .= '<input type="hidden" name="' . $key . '" id="' . $key . '" value="' . self::form_post_value( $key ) . '" style="width:60%;" />';
			// 画像選択ボタン
			// .media_btn がトリガーでメディアアップローダーが起動する
			// id名から media_ を削除した id 名の input 要素に返り値が反映される。
			// id名が media_src_ で始まる場合はURLを返す
			$form .= '<button id="media_' . $key . '" class="cfb_media_btn btn btn-default button button-default">' . esc_html__( 'Choose Image', 'vk-google-job-posting-manager' ) . '</button> ';

			// 削除ボタン
			// ボタンタグだとその場でページが再読込されてしまうのでaタグに変更
			$form .= '<a id="media_reset_' . $key . '" class="media_reset_btn btn btn-default button button-default">' . esc_html__( 'Delete Image', 'vk-google-job-posting-manager' ) . '</a>';
		} elseif ( $value['type'] == 'select' ) {

			$form .= '<select name="' . $field_prefix . '[' . esc_attr( $key ) . ']' . '"  >';

			foreach ( $value['options'] as $option_value => $option_label ) {

				$saved = isset( $options[ $key ] ) ? $options[ $key ] : '';

				if ( $saved == $option_value ) {
					$selected = ' selected="selected"';
				} else {
					$selected = '';
				}

				$form .= '<option value="' . esc_attr( $option_value ) . '" ' . esc_attr( $selected ) . '>' . esc_html( $option_label ) . '</option>';
			}
			$form .= '</select>';
		} elseif ( $value['type'] == 'checkbox' ) {
			$form .= '<ul>';

			$saved = isset( $options[ $key ] ) ? $options[ $key ] : array();

			if ( $value['type'] == 'checkbox' ) {
				foreach ( $value['options'] as $option_value => $option_label ) {
					if ( is_array( $saved ) && in_array( $option_value, $saved ) ) {
						$selected = ' checked';
					} else {
						$selected = '';
					}
					$form .= '<li style="list-style: none"><label><input type="checkbox" name="' . $vgjpm_prefix . esc_attr( $key ) . '[]" value="' . esc_attr( $option_value ) . '" ' . esc_attr( $selected ) . '  /><span>' . esc_html( $option_label ) . '</span></label></li>';
				}
				$form .= '</ul>';
			}
		}
		$form .= '<div>' . wp_kses_post( $value['description'] ) . '</div>';
		$form .= '</td>';
		$form .= '</tr>';
	} // foreach ( $common_customfields as $key => $value ) {
	$form .= '</table>';

	return $form;
}


function vgjpm_save_data( $common_customfields ) {
	global $vgjpm_prefix;
	$options      = vgjpm_get_common_field_options();
	$field_prefix = 'vkjpm_common_fields';

	// nonce
	if ( ! isset( $_POST['vgjpm_nonce'] ) ) {
		return;
	}
	$vgjpm_nonce = sanitize_text_field( wp_unslash( $_POST['vgjpm_nonce'] ) );
	if ( ! wp_verify_nonce( $vgjpm_nonce, 'standing_on_the_shoulder_of_giants' ) ) {
		return;
	}

	if ( ! isset( $common_customfields ) ) {
		return;
	}

	foreach ( $common_customfields as $key => $value ) {
		$posted_fields = null;
		if ( isset( $_POST[ $field_prefix ] ) && is_array( $_POST[ $field_prefix ] ) ) {
			$posted_fields = wp_unslash( $_POST[ $field_prefix ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below.
		}

		if ( $value['type'] == 'text' || $value['type'] == 'select' || $value['type'] == 'image' || $value['type'] == 'datepicker' ) {

			$posted_value = null;
			if ( is_array( $posted_fields ) && array_key_exists( $key, $posted_fields ) ) {
				$posted_value = $posted_fields[ $key ];
			}
			if ( null !== $posted_value ) {
				$options[ $key ] = vgjpm_sanitize_arr( $posted_value );
			}

		} elseif ( $value['type'] == 'textarea' ) {

			$posted_value = null;
			if ( is_array( $posted_fields ) && array_key_exists( $key, $posted_fields ) ) {
				$posted_value = $posted_fields[ $key ];
			}
			if ( null !== $posted_value ) {
				$options[ $key ] = sanitize_textarea_field( $posted_value );
			}

		} elseif ( $value['type'] == 'checkbox' ) {

			$posted_value = null;
			if ( is_array( $posted_fields ) && array_key_exists( $key, $posted_fields ) ) {
				$posted_value = $posted_fields[ $key ];
			}
			if ( is_array( $posted_value ) ) {
				$options[ $key ] = vgjpm_sanitize_arr( $posted_value );

			} else {
				$options[ $key ] = array();

			}
		}

		vgjpm_save_check_list();

		vgjpm_save_create_jobpost_posttype();
	}
	update_option( $field_prefix, $options );
}

function vgjpm_save_create_jobpost_posttype() {
	$name = 'vgjpm_create_jobpost_posttype';

	if ( ! isset( $_POST['vgjpm_nonce'] ) ) {
		return;
	}
	$vgjpm_nonce = sanitize_text_field( wp_unslash( $_POST['vgjpm_nonce'] ) );
	if ( ! wp_verify_nonce( $vgjpm_nonce, 'standing_on_the_shoulder_of_giants' ) ) {
		return;
	}

	if ( isset( $_POST[ $name ] ) ) {
		update_option( $name, sanitize_text_field( wp_unslash( $_POST[ $name ] ) ) );
	} else {
		update_option( $name, false );
	}
}

function vgjpm_save_check_list() {
	$args       = array(
		'public' => true,
	);
	$post_types = get_post_types( $args, 'object' );

	if ( ! isset( $_POST['vgjpm_nonce'] ) ) {
		return;
	}
	$vgjpm_nonce = sanitize_text_field( wp_unslash( $_POST['vgjpm_nonce'] ) );
	if ( ! wp_verify_nonce( $vgjpm_nonce, 'standing_on_the_shoulder_of_giants' ) ) {
		return;
	}

	foreach ( array_keys( $post_types ) as $key ) {
		if ( $key != 'attachment' ) {
			$name = 'vgjpm_post_type_display_customfields' . sanitize_text_field( $key );

			if ( isset( $_POST[ $name ] ) ) {
				update_option( $name, sanitize_text_field( wp_unslash( $_POST[ $name ] ) ) );
			} else {
				update_option( $name, 'false' );
			}
		}
	}
}

function vgjpm_print_jsonLD_in_footer() {
	$post_id       = get_the_ID();
	$custom_fields = vgjpm_get_custom_fields( $post_id );
	$json_ld = vgjpm_generate_jsonLD( $custom_fields );
	if ( $json_ld ) {
		echo wp_kses(
			$json_ld,
			array(
				'script' => array(
					'type' => true,
				),
			)
		);
	}
}
add_action( 'wp_head', 'vgjpm_print_jsonLD_in_footer', 9999 );


/**
 * Send sitemap.xml to google when it's existed.
 *
 * @param $post_id
 *
 * @return bool
 */
function vgjpm_send_sitemap_to_google( $post_id ) {

	// postmeta(vkjp_title)が空の時リターン。（値が存在しても、初めてtitleに値入力した時は弾かれる）
	$result = get_post_meta( $post_id, 'vkjp_title', true );
	if ( empty( $result ) ) {
		return false;
	}

	$google_url  = 'http://www.google.com/ping?sitemap=';
	$sitemap_url = home_url() . '/sitemap.xml';
	$status_code = wp_remote_retrieve_response_code( wp_remote_get( $sitemap_url ) );

	if ( $status_code === 200 ) {
		wp_remote_get( $google_url . $sitemap_url );
	}
}

add_action( 'wp_insert_post', 'vgjpm_send_sitemap_to_google', 10, 1 );

/**
 * Escape Javascript. Remove <script></script> from target html.
 *
 * @param $html
 *
 * @return mixed
 */
function vgjpm_esc_script( $html ) {
	$needles = array( '<script>', '</script>', 'script' );
	$return  = str_replace( $needles, '', $html );
	return $return;
}

/**
 * Remove newline character.
 *
 * @param $html
 *
 * @return mixed
 */
function vgjpm_esc_newline( $html ) {
	$return = str_replace( array( "\r\n", "\n", "\r" ), '', $html );
	return $return;
}

/****
 * Build a JobPosting JSON-LD script tag from provided custom field values.
 *
 * Converts job-related fields into a schema.org JobPosting JSON-LD payload and wraps it in a
 * <script type="application/ld+json"> tag suitable for output in the document head.
 *
 * @param array $custom_fields Associative array of custom field values keyed by field names
 *                             (e.g. 'vkjp_title', 'vkjp_description', 'vkjp_datePosted', etc.).
 * @return string|null The complete <script> tag containing the encoded JSON-LD, or `null` if
 *                     required data (title) is missing.
 ****/
function vgjpm_generate_jsonLD( $custom_fields ) {
	if ( ! isset( $custom_fields['vkjp_title'] ) ) {
		return;
	}

	// Use wp_json_encode for safe script output.
	$custom_fields = vgjpm_use_common_values( $custom_fields, 'json' );

	if ( ! empty( $custom_fields['vkjp_validThrough'] ) ) {
		$valid_through_timestamp = strtotime( $custom_fields['vkjp_validThrough'] );
		if ( false !== $valid_through_timestamp ) {
			$custom_fields['vkjp_validThrough'] = wp_date( 'Y-m-d', $valid_through_timestamp, wp_timezone() );
		}
	}

	$employment_types = array();
	if ( isset( $custom_fields['vkjp_employmentType'] ) ) {
		$employment_types = array_filter(
			array_map(
				function( $value ) {
					// Remove stray quotes from checkbox values and trim.
					return trim( str_replace( '"', '', $value ) );
				},
				explode( ',', $custom_fields['vkjp_employmentType'] )
			),
			'strlen'
		);
	}

	$base_salary_value = array(
		'@type'    => 'QuantitativeValue',
		'unitText' => $custom_fields['vkjp_unitText'],
	);

	if ( '' !== $custom_fields['vkjp_minValue'] && is_numeric( $custom_fields['vkjp_minValue'] ) ) {
		$base_salary_value['minValue'] = (float) $custom_fields['vkjp_minValue'];
	}

	if ( '' !== $custom_fields['vkjp_maxValue'] && is_numeric( $custom_fields['vkjp_maxValue'] ) ) {
		$base_salary_value['maxValue'] = (float) $custom_fields['vkjp_maxValue'];
	}

	// HTML として解釈されない素の山括弧を保持しつつ、許可タグだけを残す。
	$description_raw = vgjpm_esc_newline( vgjpm_esc_script( $custom_fields['vkjp_description'] ) );
	// タグとして始まらない "<" は kses に消されないよう &lt; にしておく。
	$description_raw = preg_replace( '/<(?![\\/!a-zA-Z])/', '&lt;', $description_raw );
	$description     = wp_kses_post( $description_raw );
	// エンティティを戻して、JSON 上で入力どおりの文字 (<, >, &) を保持する。
	$description     = htmlspecialchars_decode( $description, ENT_QUOTES | ENT_HTML5 );

	$json_array = array(
		'@context'        => 'https://schema.org/',
		'@type'           => 'JobPosting',
		'title'           => wp_strip_all_tags( vgjpm_esc_newline( vgjpm_esc_script( $custom_fields['vkjp_title'] ) ) ),
		'description'     => $description,
		'datePosted'      => $custom_fields['vkjp_datePosted'],
		'validThrough'    => $custom_fields['vkjp_validThrough'],
		'employmentType'  => $employment_types,
		'identifier'      => array(
			'@type' => 'PropertyValue',
			'name'  => wp_strip_all_tags( $custom_fields['vkjp_name'] ),
			'value' => wp_strip_all_tags( $custom_fields['vkjp_identifier'] ),
		),
		'hiringOrganization' => array(
			'@type'  => 'Organization',
			'name'   => wp_strip_all_tags( $custom_fields['vkjp_name'] ),
			'sameAs' => esc_url_raw( $custom_fields['vkjp_sameAs'] ),
			'logo'   => esc_url_raw( $custom_fields['vkjp_logo'] ),
		),
		'jobLocation' => array(
			'@type'   => 'Place',
			'address' => array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => wp_strip_all_tags( $custom_fields['vkjp_streetAddress'] ),
				'addressLocality' => wp_strip_all_tags( $custom_fields['vkjp_addressLocality'] ),
				'addressRegion'   => wp_strip_all_tags( $custom_fields['vkjp_addressRegion'] ),
				'postalCode'      => wp_strip_all_tags( $custom_fields['vkjp_postalCode'] ),
				'addressCountry'  => wp_strip_all_tags( $custom_fields['vkjp_addressCountry'] ),
			),
		),
		'baseSalary' => array(
			'@type'   => 'MonetaryAmount',
			'currency' => wp_strip_all_tags( $custom_fields['vkjp_currency'] ),
			'value'   => $base_salary_value,
		),
	);

	if ( isset( $custom_fields['vkjp_jobLocationType'] ) && '' !== $custom_fields['vkjp_jobLocationType'] ) {
		$json_array['jobLocationType'] = wp_strip_all_tags( $custom_fields['vkjp_jobLocationType'] );
		$json_array['applicantLocationRequirements'] = array(
			'@type' => 'Country',
			'name'  => isset( $custom_fields['vkjp_applicantLocationRequirements_name'] ) ? wp_strip_all_tags( $custom_fields['vkjp_applicantLocationRequirements_name'] ) : '',
		);
	}

	if ( isset( $custom_fields['vkjp_directApply'] ) && $custom_fields['vkjp_directApply'] ) {
		$json_array['directApply'] = true;
	}

	$json_ld = wp_json_encode(
		$json_array,
		JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
	);

	return '<script type="application/ld+json">' . "\n" . $json_ld . "\n" . '</script>' . "\n";
}

function vgjpm_allowed_form_html() {
	return array(
		'div'    => array( 'class' => true, 'id' => true, 'style' => true ),
		'h1'     => array(),
		'h2'     => array(),
		'p'      => array( 'class' => true ),
		'form'   => array( 'method' => true, 'action' => true ),
		'input'  => array(
			'type'  => true,
			'name'  => true,
			'value' => true,
			'id'    => true,
			'class' => true,
			'style' => true,
			'size'  => true,
			'checked' => true,
		),
		'textarea' => array(
			'name'  => true,
			'cols'  => true,
			'rows'  => true,
			'class' => true,
		),
		'select' => array( 'name' => true, 'class' => true ),
		'option' => array( 'value' => true, 'selected' => true ),
		'button' => array( 'id' => true, 'class' => true, 'type' => true ),
		'table'  => array( 'class' => true ),
		'tbody'  => array(),
		'tr'     => array(),
		'th'     => array(),
		'td'     => array(),
		'ul'     => array(),
		'li'     => array( 'style' => true ),
		'label'  => array(),
		'img'    => array(
			'src'   => true,
			'alt'   => true,
			'class' => true,
			'id'    => true,
			'style' => true,
		),
		'a'      => array( 'href' => true, 'class' => true, 'id' => true, 'target' => true ),
		'span'   => array( 'class' => true ),
		'br'     => array(),
	);
}
