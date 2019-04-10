<?php
/**
 * Plugin Name:     VK Google Job Posting Manager
 * Plugin URI:      https://github.com/vektor-inc/vk-google-job-posting-manager
 * Description:     This is the plugin for Google Job posting.
 * Author:          Vektor,Inc.
 * Author URI:      https://www.vektor-inc.co.jp
 * Text Domain:     vk-google-job-posting-manager
 * Domain Path:     /languages
 * Version:         0.2.0
 *
 * @package         Vk_Google_Job_Posting_Manager
 */

 /*
 -------------------------------------------*/
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

function vgjpm_activate() {

	 flush_rewrite_rules();
	 update_option( 'vgjpm_create_jobpost_posttype', 'true' );
	 load_plugin_textdomain( 'vk-google-job-posting-manager', false, basename( dirname( __FILE__ ) ) . '/languages' );

}
register_activation_hook( __FILE__, 'vgjpm_activate' );

$flag_custom_posttype = get_option( 'vgjpm_create_jobpost_posttype' );
if ( isset( $flag_custom_posttype ) && $flag_custom_posttype == 'true' ) {
	require_once( dirname( __FILE__ ) . '/inc/custom-posttype-builder.php' );
}

	/**
	 */
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
	wp_enqueue_style( 'vgjpm-admin-style', plugin_dir_url( __FILE__ ) . 'assets/css/admin.css', array(), VGJPM_VERSION, 'all' );
}
add_action( 'admin_enqueue_scripts', 'vgjpm_admin_css' );



function vgjpm_get_common_customfields_config() {

	$VGJPM_Custom_Field_Job_Post = new VGJPM_Custom_Field_Job_Post;
	$labels                      = $VGJPM_Custom_Field_Job_Post->custom_fields_array();

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

	foreach ( $labels as $key => $value ) {
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
	 * @return [type]                      [description]
	 */
function vgjpm_create_common_form( $common_customfields ) {

	$form = '<div class="vgjpm">';

	$form .= '<h1>' . __( 'Job Posting Manager Settings', 'vk-google-job-posting-manager' ) . '</h1>';

	$form .= '<form method="post" action="">';

	$form .= wp_nonce_field( 'standing_on_the_shoulder_of_giants', 'vgjpm_nonce' );

	$form .= '<h2>' . __( 'Create Job-Posts Post type.', 'vk-google-job-posting-manager' ) . '</h2>';

	$form .= '<p>' . __( 'This plugin automatically create post type for Job Posting.<br>If you have already created custom post type for Job Post, please remove this check and select post type of next check boxes.', 'vk-google-job-posting-manager' ) . '</p>';
	$form .= vgjpm_create_jobpost_posttype();

	$form .= '<h2>' . __( 'Choose the post type to display job posting custom fields.', 'vk-google-job-posting-manager' ) . '</h2>';

	$form .= vgjpm_post_type_check_list();

	$form .= '<h2>' . __( 'Common Fields', 'vk-google-job-posting-manager' ) . '</h2>';

	$form .= '<p>' . __( 'If a single page is filled in, the content of the single page takes precedence.', 'vk-google-job-posting-manager' ) . '</p>';

	$form .= vgjpm_render_form_input( $common_customfields );

	$form .= '<input type="submit" value="Save Changes" class="button button-primary">';

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

		} elseif ( $value['type'] == 'date' ) {

			$form .= '<input type="date" name="common_' . esc_attr( $key ) . '" value="' . get_option( 'common_' . esc_attr( $key ) ) . '">';

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

		if ( $value['type'] == 'text' || $value['type'] == 'select' ) {

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
	$list         .= '<input type="checkbox" name="vgjpm_create_jobpost_posttype" value="true" ' . esc_attr( $checked ) . ' />' . __( 'Create The Post Type.', 'vk-google-job-posting-manager' ) . '</label></li>';

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

function vgjpm_use_common_values( $custom_fields ) {

	foreach ( (array) $custom_fields as $key => $value ) {

		$temp = get_option( 'common_' . $key, null );

		if ( $custom_fields[ $key ] == '' && isset( $temp ) ) {

			$custom_fields[ $key ] = $temp;

		} elseif ( ! isset( $custom_fields[ $key ] ) && ! isset( $temp ) ) {

			$custom_fields[ $key ] = '';
		}
	}

	return $custom_fields;
}


function vgjpm_generate_jsonLD( $custom_fields ) {

	if ( ! isset( $custom_fields['vkjp_title'] ) ) {
		return;
	}

	$custom_fields = vgjpm_use_common_values( $custom_fields );

	$JSON = '<script type="application/ld+json"> {
  "@context" : "https://schema.org/",
  "@type" : "JobPosting",
  "title" : "' . esc_attr( $custom_fields['vkjp_title'] ) . '",
  "description" : "' . esc_attr( $custom_fields['vkjp_description'] ) . '",
  "datePosted" : "' . esc_attr( $custom_fields['vkjp_datePosted'] ) . '",
  "validThrough" : "' . esc_attr( $custom_fields['vkjp_validThrough'] ) . '",
  "employmentType" : ["' . implode( '", "', $custom_fields['vkjp_employmentType'] ) . '"],
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
