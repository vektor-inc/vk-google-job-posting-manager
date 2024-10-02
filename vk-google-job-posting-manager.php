<?php
/**
 * Plugin Name:     VK Google Job Posting Manager
 * Plugin URI:      https://github.com/vektor-inc/vk-google-job-posting-manager
 * Description:     This is the plugin for Google Job posting
 * Author:          Vektor,Inc
 * Author URI:      https://www.vektor-inc.co.jp
 * Text Domain:     vk-google-job-posting-manager
 * Domain Path:     /languages
 * Version:         1.2.18
 * Requires at least: 6.3
 *
 * @package         Vk_Google_Job_Posting_Manager
 */
/*
Setting & load file
/*-------------------------------------------*/
$vgjpm_prefix = 'common_';
$data         = get_file_data(
	__FILE__,
	array(
		'version'    => 'Version',
		'textdomain' => 'Text Domain',
	)
);
define( 'VGJPM_VERSION', $data['version'] );
define( 'VGJPM_BASENAME', plugin_basename( __FILE__ ) );
define( 'VGJPM_URL', plugin_dir_url( __FILE__ ) );
define( 'VGJPM_DIR', plugin_dir_path( __FILE__ ) );

require_once __DIR__ . '/functions-tags.php';
require_once __DIR__ . '/inc/custom-field-builder/package/custom-field-builder.php';
require_once __DIR__ . '/inc/custom-field-builder/custom-field-builder-config.php';
require_once __DIR__ . '/blocks/vk-google-job-posting-manager-block.php';

function vgjpm_load_textdomain() {
	load_plugin_textdomain( 'vk-google-job-posting-manager', false, 'vk-google-job-posting-manager/languages' );
}
add_action( 'plugins_loaded', 'vgjpm_load_textdomain' );

function vgjpm_activate() {
	update_option( 'vgjpm_create_jobpost_posttype', 'true' );
}
register_activation_hook( __FILE__, 'vgjpm_activate' );

$flag_custom_posttype = get_option( 'vgjpm_create_jobpost_posttype' );
if ( isset( $flag_custom_posttype ) && $flag_custom_posttype == 'true' ) {
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
	$settings_link = '<a href="options-general.php?page=vgjpm_settings">' . __( 'Setting', 'vvk-google-job-posting-manager' ) . '</a>';
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
function vkjpm_get_common_field_options() {
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
	}
	return $options;
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

	echo vgjpm_create_common_form( $common_custom_fields );
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

	$form .= wp_nonce_field( 'standing_on_the_shoulder_of_giants', 'vgjpm_nonce' );

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

	$form .= '<div class="footer-logo"><a href="https://www.vektor-inc.co.jp"><img src="' . plugin_dir_url( __FILE__ ) . 'assets/images/vektor_logo.png" alt="Vektor,Inc." /></a></div>';
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
	$options      = vkjpm_get_common_field_options();

	$form = '<table class="admin-table">';

	foreach ( $common_customfields as $key => $value ) {
		$form .= '<tr>';
		$form .= '<th>' . esc_html( $value['label'] ) . '</th>';
		$form .= '<td>';

		if ( $value['type'] == 'text' ) {
			$form .= '<input type="text" name="' . $field_prefix . '[' . esc_attr( $key ) . ']' . '" value="' . $options[ esc_attr( $key ) ] . '">';

		} elseif ( $value['type'] == 'textarea' ) {

			$form .= '<textarea class="form-control" class="cf_textarea_wysiwyg" name="' . $field_prefix . '[' . esc_attr( $key ) . ']' . '" cols="70" rows="3">' . esc_html( $options[ esc_attr( $key ) ] ) . '</textarea>';

		} elseif ( $value['type'] == 'datepicker' ) {

			$form .= '<input class="form-control datepicker" type="text" " name="' . $field_prefix . '[' . esc_attr( $key ) . ']' . '" value="' . $options[ esc_attr( $key ) ] . '" size="70">';

		} elseif ( $value['type'] == 'image' ) {

			$saved = $options[ esc_attr( $key ) ];

			if ( ! empty( $saved ) ) {
				$thumb_image_url = wp_get_attachment_url( $saved );
			} else {
				$thumb_image_url = VGJPM_URL . 'inc/custom-field-builder/package/images/no_image.png';
			}

			// ダミー & プレビュー画像
			$form .= '<img src="' . $thumb_image_url . '" id="thumb_' . esc_attr( $key ) . '" alt="" class="input_thumb" style="width:200px;height:auto;"> ';
			// 実際に送信する値
			$form .= '<input type="hidden" name="' . $field_prefix . '[' . esc_attr( $key ) . ']' . '" id="' . esc_attr( $key ) . '" value="' . $options[ esc_attr( $key ) ] . '" style="width:60%;" />';
			// $form .= '<input type="hidden" name="' . $key . '" id="' . $key . '" value="' . self::form_post_value( $key ) . '" style="width:60%;" />';
			// 画像選択ボタン
			// .media_btn がトリガーでメディアアップローダーが起動する
			// id名から media_ を削除した id 名の input 要素に返り値が反映される。
			// id名が media_src_ で始まる場合はURLを返す
			$form .= '<button id="media_' . $key . '" class="cfb_media_btn btn btn-default button button-default">' . __( 'Choose Image', 'vk-google-job-posting-manager' ) . '</button> ';

			// 削除ボタン
			// ボタンタグだとその場でページが再読込されてしまうのでaタグに変更
			$form .= '<a id="media_reset_' . $key . '" class="media_reset_btn btn btn-default button button-default">' . __( 'Delete Image', 'vk-google-job-posting-manager' ) . '</a>';
		} elseif ( $value['type'] == 'select' ) {

			$form .= '<select name="' . $field_prefix . '[' . esc_attr( $key ) . ']' . '"  >';

			foreach ( $value['options'] as $option_value => $option_label ) {

				$saved = $options[ esc_attr( $key ) ];

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

			$saved = $options[ esc_attr( $key ) ];

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
	$options      = vkjpm_get_common_field_options();
	$field_prefix = 'vkjpm_common_fields';

	// nonce
	if ( ! isset( $_POST['vgjpm_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( $_POST['vgjpm_nonce'], 'standing_on_the_shoulder_of_giants' ) ) {
		return;
	}

	if ( ! isset( $common_customfields ) ) {
		return;
	}

	foreach ( $common_customfields as $key => $value ) {
		if ( $value['type'] == 'text' || $value['type'] == 'select' || $value['type'] == 'image' || $value['type'] == 'datepicker' ) {

			$options[ $key ] = vgjpm_sanitize_arr( $_POST[ $field_prefix ][ $key ] );

		} elseif ( $value['type'] == 'textarea' ) {

			$options[ $key ] = sanitize_textarea_field( $_POST[ $field_prefix ][ $key ] );

		} elseif ( $value['type'] == 'checkbox' ) {

			if ( isset( $_POST[ $field_prefix ][ $key ] ) && is_array( $_POST[ $field_prefix ][ $key ] ) ) {

				$options[ $key ] = vgjpm_sanitize_arr( $_POST[ $field_prefix ][ $key ] );

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

	if ( isset( $_POST[ $name ] ) ) {
		update_option( $name, sanitize_text_field( $_POST[ $name ] ) );
	} else {
		update_option( $name, false );
	}
}

function vgjpm_save_check_list() {
	$args       = array(
		'public' => true,
	);
	$post_types = get_post_types( $args, 'object' );

	foreach ( $post_types as $key => $value ) {
		if ( $key != 'attachment' ) {
			$name = 'vgjpm_post_type_display_customfields' . sanitize_text_field( $key );

			if ( isset( $_POST[ $name ] ) ) {
				update_option( $name, sanitize_text_field( $_POST[ $name ] ) );
			} else {
				update_option( $name, 'false' );
			}
		}
	}
}

function vgjpm_print_jsonLD_in_footer() {
	$post_id       = get_the_ID();
	$custom_fields = vgjpm_get_custom_fields( $post_id );
	echo vgjpm_generate_jsonLD( $custom_fields );
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

function vgjpm_generate_jsonLD( $custom_fields ) {
	if ( ! isset( $custom_fields['vkjp_title'] ) ) {
		return;
	}

	$custom_fields = vgjpm_use_common_values( $custom_fields, 'json' );

	if ( $custom_fields['vkjp_validThrough'] ) {
		$custom_fields['vkjp_validThrough'] = date( 'Y-m-d', strtotime( $custom_fields['vkjp_validThrough'] ) );
	}

	if ( isset( $custom_fields['vkjp_employmentType'] ) && strpos( $custom_fields['vkjp_employmentType'], ',' ) === false ) {
		$custom_fields['vkjp_employmentType'] = '"' . $custom_fields['vkjp_employmentType'] . '"';
	} else {
		$custom_fields['vkjp_employmentType'] = '["' . $custom_fields['vkjp_employmentType'] . '"]';
	}

	$JSON = '
<script type="application/ld+json">
{
	"@context" : "https://schema.org/",
	"@type" : "JobPosting",
	"title" : "' . esc_attr( $custom_fields['vkjp_title'] ) . '",
	"description" : "' . vgjpm_esc_newline( vgjpm_esc_script( $custom_fields['vkjp_description'] ) ) . '",
	"datePosted" : "' . esc_attr( $custom_fields['vkjp_datePosted'] ) . '",
	"validThrough" : "' . esc_attr( $custom_fields['vkjp_validThrough'] ) . '",
	"employmentType" : ' . $custom_fields['vkjp_employmentType'] . ',
	"identifier": {
		"@type": "PropertyValue",
		"name":  "' . esc_attr( $custom_fields['vkjp_name'] ) . '",
		"value": "' . esc_attr( $custom_fields['vkjp_identifier'] ) . '"
	},
	"hiringOrganization" : {
		"@type" : "Organization",
		"name" : "' . esc_attr( $custom_fields['vkjp_name'] ) . '",
		"sameAs" : "' . esc_url( $custom_fields['vkjp_sameAs'] ) . '",
		"logo" : "' . esc_url( $custom_fields['vkjp_logo'] ) . '"
	},
	"jobLocation": {
		"@type": "Place",
		"address": {
			"@type": "PostalAddress",
			"streetAddress": "' . esc_attr( $custom_fields['vkjp_streetAddress'] ) . '",
			"addressLocality": "' . esc_attr( $custom_fields['vkjp_addressLocality'] ) . '",
			"addressRegion": "' . esc_attr( $custom_fields['vkjp_addressRegion'] ) . '",
			"postalCode": "' . esc_attr( $custom_fields['vkjp_postalCode'] ) . '",
			"addressCountry": "' . esc_attr( $custom_fields['vkjp_addressCountry'] ) . '"
		}
	}';
	if ( $custom_fields['vkjp_jobLocationType'] ) {
		$JSON .= ',
	"jobLocationType": "' . esc_attr( $custom_fields['vkjp_jobLocationType'] ) . '",
	"applicantLocationRequirements": {
		"@type": "Country",
		"name": "' . esc_attr( $custom_fields['vkjp_applicantLocationRequirements_name'] ) . '"
	}';
	}
	$JSON .= ',
	"baseSalary": {
		"@type": "MonetaryAmount",
		"currency": "' . esc_attr( $custom_fields['vkjp_currency'] ) . '",
		"value": {
			"@type": "QuantitativeValue",
			';
	if ( null !== $custom_fields['vkjp_minValue'] && '' !== $custom_fields['vkjp_minValue'] ) {
		$JSON .= '"minValue": ' . esc_attr( $custom_fields['vkjp_minValue'] ) . ',';
	}
	if ( null !== $custom_fields['vkjp_maxValue'] && '' !== $custom_fields['vkjp_maxValue'] ) {
		$JSON .= '"maxValue": ' . esc_attr( $custom_fields['vkjp_maxValue'] ) . ',';
	}
		$JSON .= '
			"unitText": "' . esc_attr( $custom_fields['vkjp_unitText'] ) . '"
		}
	}';
	if ( $custom_fields['vkjp_directApply'] ) {
		$JSON .= ',
	"directApply": true';
	}
	$JSON .= '
}
</script>
';

	return $JSON;
}
