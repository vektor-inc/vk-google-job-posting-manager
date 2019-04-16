<?php
/**
 * Plugin Name:     VK Google Job Posting Manager
 * Plugin URI:      https://github.com/vektor-inc/vk-google-job-posting-manager
 * Description:     This is the plugin for Google Job posting
 * Author:          Vektor,Inc
 * Author URI:      https://www.vektor-inc.co.jp
 * Text Domain:     vk-google-job-posting-manager
 * Domain Path:     /languages
 * Version:         0.4.0
 *
 * @package         Vk_Google_Job_Posting_Manager
 */


 /*
  Setting & load file
 /*-------------------------------------------*/

$data = get_file_data(
	__FILE__, array(
		'version'    => 'Version',
		'textdomain' => 'Text Domain',
	)
);
 define( 'VGJPM_VERSION', $data['version'] );
 define( 'VGJPM_BASENAME', plugin_basename( __FILE__ ) );
 define( 'VGJPM_URL', plugin_dir_url( __FILE__ ) );
 define( 'VGJPM_DIR', plugin_dir_path( __FILE__ ) );

require_once( dirname( __FILE__ ) . '/inc/custom-field-builder/package/custom-field-builder.php' );
require_once( dirname( __FILE__ ) . '/inc/custom-field-builder/custom-field-builder-config.php' );
require_once( dirname( __FILE__ ) . '/blocks/vk-google-job-posting-manager-block.php' );


function vgjpm_load_textdomain() {
	load_plugin_textdomain( 'vk-google-job-posting-manager', false, 'vk-google-job-posting-manager/languages' );
}
add_action( 'plugins_loaded', 'vgjpm_load_textdomain' );

function vgjpm_activate() {

	load_plugin_textdomain( 'vk-google-job-posting-manager', false, 'vk-google-job-posting-manager/languages' );
	flush_rewrite_rules();
	update_option( 'vgjpm_create_jobpost_posttype', 'true' );
}
register_activation_hook( __FILE__, 'vgjpm_activate' );

$flag_custom_posttype = get_option( 'vgjpm_create_jobpost_posttype' );
if ( isset( $flag_custom_posttype ) && $flag_custom_posttype == 'true' ) {
	require_once( dirname( __FILE__ ) . '/inc/custom-posttype-builder.php' );
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

function vgjpm_get_common_customfields_config() {

	$VGJPM_Custom_Field_Job_Post = new VGJPM_Custom_Field_Job_Post;
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
		'vkjp_workHours',
		'vkjp_specialCommitments',
		'vkjp_currency',
		'vkjp_employmentType',
		'vkjp_value',
		'vkjp_minValue',
		'vkjp_maxValue',
		'vkjp_incentiveCompensation',
		'vkjp_salaryRaise',
		'vkjp_unitText',
		'vkjp_validThrough',
		// 'vkjp_experienceRequirements',
	);
	$labels_ordered = array();
	foreach ( $common_page_field_order as $key => $value ) {
		if ( isset( $labels[ $value ] ) ) {
			$labels_ordered[ $value ] = $labels[ $value ];
		}
	}

	$common_customfields = array(
		'vkjp_value',
		'vkjp_minValue',
		'vkjp_maxValue',
		'vkjp_unitText',
		'vkjp_currency',
		'vkjp_salaryCurrency',
		'vkjp_incentiveCompensation',
		'vkjp_salaryRaise',
		'vkjp_workHours',
		'vkjp_employmentType',
		'vkjp_experienceRequirements',
		'vkjp_specialCommitments',
		'vkjp_name',
		'vkjp_sameAs',
		'vkjp_logo',
		'vkjp_postalCode',
		'vkjp_addressCountry',
		'vkjp_addressRegion',
		'vkjp_addressLocality',
		'vkjp_streetAddress',
		'vkjp_validThrough',
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

	$common_customfields = vgjpm_get_common_customfields_config();

	vgjpm_save_data( $common_customfields );

	echo vgjpm_create_common_form( $common_customfields );

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

	$form = '<table class="admin-table">';

	foreach ( $common_customfields as $key => $value ) {

		$form .= '<tr>';
		$form .= '<th>' . esc_html( $value['label'] ) . '</th>';
		$form .= '<td>';

		if ( $value['type'] == 'text' ) {
			$form .= '<input type="text" name="common_' . esc_attr( $key ) . '" value="' . get_option( 'common_' . esc_attr( $key ) ) . '">';

		} elseif ( $value['type'] == 'datepicker' ) {

			$form .= '<input class="form-control datepicker" type="text" " name="common_' . esc_attr( $key ) . '" value="' . get_option( 'common_' . esc_attr( $key ) ) . '" size="70">';

		} elseif ( $value['type'] == 'image' ) {

			$saved = get_option( 'common_' . esc_attr( $key ) );

			if ( ! empty( $saved ) ) {
				$thumb_image_url = wp_get_attachment_url( $saved );
			} else {
				$thumb_image_url = VGJPM_URL . 'inc/custom-field-builder/package/images/no_image.png';
			}

			// ダミー & プレビュー画像
			$form .= '<img src="' . $thumb_image_url . '" id="thumb_' . esc_attr( $key ) . '" alt="" class="input_thumb" style="width:200px;height:auto;"> ';
			// 実際に送信する値
			$form .= '<input type="hidden" name="common_' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" value="' . $thumb_image_url . '" style="width:60%;" />';
			//                  $form .= '<input type="hidden" name="' . $key . '" id="' . $key . '" value="' . self::form_post_value( $key ) . '" style="width:60%;" />';

			// 画像選択ボタン
			// .media_btn がトリガーでメディアアップローダーが起動する
			// id名から media_ を削除した id 名の input 要素に返り値が反映される。
			// id名が media_src_ で始まる場合はURLを返す
			$form .= '<button id="media_' . $key . '" class="cfb_media_btn btn btn-default button button-default">' . __( 'Choose Image', 'vk-google-job-posting-manager' ) . '</button> ';

			// 削除ボタン
			// ボタンタグだとその場でページが再読込されてしまうのでaタグに変更
			$form .= '<a id="media_reset_' . $key . '" class="media_reset_btn btn btn-default button button-default">' . __( 'Delete Image', 'vk-google-job-posting-manager' ) . '</a>';

		} elseif ( $value['type'] == 'select' ) {

			$form .= '<select name="common_' . esc_attr( $key ) . '"  >';

			foreach ( $value['options'] as $option_value => $option_label ) {

				$saved = get_option( 'common_' . esc_attr( $key ) );

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

			$saved = get_option( 'common_' . esc_attr( $key ) );

			if ( $value['type'] == 'checkbox' ) {

				foreach ( $value['options'] as $option_value => $option_label ) {

					if ( is_array( $saved ) && in_array( $option_value, $saved ) ) {
						$selected = ' checked';
					} else {
						$selected = '';
					}
					$form .= '<li style="list-style: none"><label><input type="checkbox" name="common_' . esc_attr( $key ) . '[]" value="' . esc_attr( $option_value ) . '" ' . esc_attr( $selected ) . '  /><span>' . esc_html( $option_label ) . '</span></label></li>';

				}
				$form .= '</ul>';

			}
		}
		$form .= wp_kses_post( $value['description'] );
		$form .= '</td>';
		$form .= '</tr>';

	} // foreach ( $common_customfields as $key => $value ) {
	$form .= '</table>';

	return $form;
}


function vgjpm_save_data( $common_customfields ) {

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

			update_option( 'common_' . sanitize_text_field( $key ), vgjpm_sanitize_arr( $_POST[ 'common_' . $key ] ) );

		} elseif ( $value['type'] == 'checkbox' ) {

			$checkbox_key = 'common_' . sanitize_text_field( $key );

			if ( isset( $_POST[ $checkbox_key ] ) && is_array( $_POST[ $checkbox_key ] ) ) {

				update_option( $checkbox_key, vgjpm_sanitize_arr( $_POST[ $checkbox_key ] ) );

			} else {
				update_option( $checkbox_key, [] );

			}
		}

		vgjpm_save_check_list();

		vgjpm_save_create_jobpost_posttype();
	}
}

function vgjpm_create_jobpost_posttype() {

	$list          = '<ul>';
	$checked_saved = get_option( 'vgjpm_create_jobpost_posttype' );
	$checked       = ( isset( $checked_saved ) && $checked_saved == 'true' ) ? ' checked' : '';
	$list         .= '<li><label>';
	$list         .= '<input type="checkbox" name="vgjpm_create_jobpost_posttype" value="true" ' . esc_attr( $checked ) . ' />' . __( 'Create The Post Type', 'vk-google-job-posting-manager' ) . '</label></li>';

	$list .= '</ul>';

	return $list;
}

function vgjpm_save_create_jobpost_posttype() {

	$name = 'vgjpm_create_jobpost_posttype';

	if ( isset( $_POST[ $name ] ) ) {
		update_option( $name, sanitize_text_field( $_POST[ $name ] ) );
	} else {
		update_option( $name, false );
	}

}

function vgjpm_post_type_check_list() {

	$args       = array(
		'public' => true,
	);
	$post_types = get_post_types( $args, 'object' );

	$list = '<ul>';
	foreach ( $post_types as $key => $value ) {
		if ( $key != 'attachment' && $key != 'job-posts' ) {

			$checked_saved = get_option( 'vgjpm_post_type_display_customfields' . $key );
			$checked       = ( isset( $checked_saved ) && $checked_saved == 'true' ) ? ' checked' : '';
			$list         .= '<li><label>';
			$list         .= '<input type="checkbox" name="vgjpm_post_type_display_customfields' . esc_attr( $key ) . '" value="true"' . esc_attr( $checked ) . ' />' . esc_html( $value->label );
			$list         .= '</label></li>';
		}
	}
	$list .= '</ul>';

	return $list;
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

	$post_id = get_the_ID();

	$custom_fields = vgjpm_get_custom_fields( $post_id );

	echo vgjpm_generate_jsonLD( $custom_fields );

}
add_action( 'wp_print_footer_scripts', 'vgjpm_print_jsonLD_in_footer' );

function vgjpm_get_custom_fields( $post_id ) {

	$post          = get_post( $post_id );
	$custom_fields = get_post_custom( $post_id );

	foreach ( (array) $custom_fields as $key => $value ) {

		$custom_fields[ $key ] = maybe_unserialize( $value[0] );

		if ( substr_count( $key, 'vkjp_' ) == 0 ) {
			unset( $custom_fields[ $key ] );
		}
	}

	if ( isset( $post->post_date ) ) {
		$custom_fields['vkjp_datePosted'] = $post->post_date;
	}

	return $custom_fields;
}

function vgjpm_use_common_values( $custom_fields, $output_type ) {

	$VGJPM_Custom_Field_Job_Post = new VGJPM_Custom_Field_Job_Post;
	$default_custom_fields       = $VGJPM_Custom_Field_Job_Post->custom_fields_array();

	foreach ( $default_custom_fields as $key => $value ) {

		$temp = get_option( 'common_' . $key, null );

		$custom_fields = vgjpm_image_filter_id_to_url( $custom_fields, $key, $temp );

		if ( ! isset( $custom_fields[ $key ] ) && isset( $temp ) ) {

			$custom_fields[ $key ] = $temp;

		} elseif ( ! isset( $custom_fields[ $key ] ) && ! isset( $temp ) ) {

			$custom_fields[ $key ] = '';
		}
	}

	if ( $output_type == 'json' ) {
		//Array to string.
		$custom_fields = vgjpm_array_to_string( $custom_fields );

	} elseif ( $output_type == 'block' ) {

	}

	return $custom_fields;
}

function vgjpm_array_to_string( $custom_fields ) {

	foreach ( $custom_fields as $key => $value ) {

		if ( is_array( $value ) ) {

			$custom_fields[ $key ] = implode( '" ,"', $value );

		}
	}

	return $custom_fields;
}

function vgjpm_image_filter_id_to_url( $custom_fields, $key, $common_attachment_id ) {

	if ( $key == 'vkjp_logo' ) {

		if ( isset( $custom_fields[ $key ] ) && isset( $common_attachment_id ) ) {

			//If attachment exists return attachment's url, else return false.
			$each_post_attachment_url = wp_get_attachment_url( $custom_fields[ $key ] );
			$common_attachment_url    = wp_get_attachment_url( $common_attachment_id );

			if ( $each_post_attachment_url ) {

				$custom_fields[ $key ] = $each_post_attachment_url;

			} elseif ( $common_attachment_url ) {

				$custom_fields[ $key ] = $common_attachment_url;

			}
		}
	}

	return $custom_fields;
}


function vgjpm_generate_jsonLD( $custom_fields ) {

	if ( ! isset( $custom_fields['vkjp_title'] ) ) {
		return;
	}

	$custom_fields = vgjpm_use_common_values( $custom_fields, 'json' );

	$JSON = '<script type="application/ld+json"> {
  "@context" : "https://schema.org/",
  "@type" : "JobPosting",
  "title" : "' . esc_attr( $custom_fields['vkjp_title'] ) . '",
  "description" : "' . esc_attr( $custom_fields['vkjp_description'] ) . '",
  "datePosted" : "' . esc_attr( $custom_fields['vkjp_datePosted'] ) . '",
  "validThrough" : "' . esc_attr( $custom_fields['vkjp_validThrough'] ) . '",
  "employmentType" : ["' . $custom_fields['vkjp_employmentType'] . '"],
  "specialCommitments" : "' . esc_attr( $custom_fields['vkjp_specialCommitments'] ) . '",
  "experienceRequirements" : "' . esc_attr( $custom_fields['vkjp_experienceRequirements'] ) . '",
  "workHours" : "' . esc_attr( $custom_fields['vkjp_workHours'] ) . '",
  "incentiveCompensation" : "' . esc_attr( $custom_fields['vkjp_incentiveCompensation'] ) . '",
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
  },
  "jobLocationType": "' . $custom_fields['vkjp_jobLocationType'] . '",
  "baseSalary": {
    "@type": "MonetaryAmount",
    "currency": "' . esc_attr( $custom_fields['vkjp_currency'] ) . '",
    "value": {
      "@type": "QuantitativeValue",
      "value": ' . esc_attr( $custom_fields['vkjp_value'] ) . ',
      "minValue": ' . esc_attr( $custom_fields['vkjp_minValue'] ) . ',
      "maxValue": ' . esc_attr( $custom_fields['vkjp_maxValue'] ) . ',
      "unitText": "' . esc_attr( $custom_fields['vkjp_unitText'] ) . '"
    }
  }
}
</script>';

	return $JSON;
}

function vgjpm_sanitize_arr( $target_arr ) {

	if ( is_array( $target_arr ) ) {
		foreach ( $target_arr as $cva_key => $cva_value ) {
			$target_arr[ sanitize_text_field( $cva_key ) ] = sanitize_text_field( $cva_value );
		}

		return $target_arr;

	} else {

		return sanitize_text_field( $target_arr );
	}
}


//currencyと,before,afterを指定するとそれに合わせたstringを返す。

$args = array(
	'currency' => 'JPY',
	'figure'   => '',
	'before'   => false,
	'after'    => true,
);

function vgjpm_filter_currency( $args ) {

	$currency_data = array(
		'JPY' => array(
			'before' => __( '¥', 'vk-google-job-posting-manager' ),
			'after'  => __( '円', 'vk-google-job-posting-manager' ),
		),
	);
//	$currency_data = apply_filters( 'vgjpm_amount_currency_data', $return );

	if ( in_array( $args['currency'], $currency_data ) ) {

		$target_currency = $currency_data[ $args['currency'] ];


		if ( in_array( $args['before'], $currency_data ) ) {

			$before = $target_currency['before'];

		} else {
			$before = '';
		}

		if ( in_array( $args['after'], $currency_data ) ) {

			$after = $target_currency['after'];

		} else {
			$after = '';
		}

		$return = $before . $args['figure'] . $after;

	} else {
		$return = $args['figure'] . '(' . $args['currency'] . ')';
	}

	return apply_filters( 'vgjpm_filter_currency', $return );
}
