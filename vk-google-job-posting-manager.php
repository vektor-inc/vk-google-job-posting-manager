<?php
/**
 * Plugin Name:     VK Google Job Posting Manager
 * Plugin URI:      https://github.com/vektor-inc/vk-google-job-posting-manager
 * Description:     This is the plugin for Google Job posting.
 * Author:          Vektor,Inc.
 * Author URI:      https://www.vektor-inc.co.jp
 * Text Domain:     vk-google-job-posting-manager
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Vk_Google_Job_Posting_Manager
 */

require_once( dirname( __FILE__ ) . '/inc/custom-field-builder/package/custom-field-builder.php' );
require_once( dirname( __FILE__ ) . '/inc/custom-field-builder/custom-field-builder-config.php' );
require_once( dirname( __FILE__ ) . '/blocks/vk-google-job-posting-manager-block.php' );

function vk_gjpm_activate( ) {

	flush_rewrite_rules();
	update_option( 'vk_gjpm_create_jobpost_posttype', 'true' );
}
register_activation_hook( __FILE__, 'vk_gjpm_activate' );

$flag_custom_posttype = get_option('vk_gjpm_create_jobpost_posttype');
if(isset($flag_custom_posttype) && $flag_custom_posttype == 'true'){
	require_once( dirname( __FILE__ ) . '/inc/custom-posttype-builder.php' );
}

/**
 */
function vk_gjpm_add_setting_menu() {
	$custom_page = add_submenu_page(
		'/options-general.php',
		__( 'VK Job Posting Settings', 'vk-google-job-posting-manager' ),
		__( 'VK Job Posting Settings', 'vk-google-job-posting-manager' ),
		'activate_plugins',
		'vk_gjpm_settings',
		'vk_gjpm_render_settings'
	);
}
add_action( 'admin_menu', 'vk_gjpm_add_setting_menu' );

function get_common_customfields_config() {

	$Job_Posting_Custom_Fields = new Job_Posting_Custom_Fields;
	$labels =$Job_Posting_Custom_Fields->custom_fields_array();

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
		'vkjp_validThrough'
	);

	foreach ( $labels as $key => $value ) {
		if(in_array($key,$common_customfields)){


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

function vk_gjpm_render_settings() {

	$common_customfields = get_common_customfields_config();

	vk_gjpm_save_data( $common_customfields );

	echo vk_gjpm_create_common_form( $common_customfields );

}

function vk_gjpm_create_common_form( $common_customfields ) {

	$form = '<h1>' . __( 'Settings', 'vk-google-job-posting-manager' ) . '</h1>';
	$form .= '<form method="post" action="">';
	$form .= wp_nonce_field( 'standing_on_the_shoulder_of_giants', '_nonce_vk_job_posts' );
	$form .= '<h2>' . __( 'Common Fields', 'vk-google-job-posting-manager' ) . '</h2>';

	$form .= vk_gjpm_render_form_input( $common_customfields );

	$form .= '<h2>' . __( 'Choose the post type to display job posting custom fields.', 'vk-google-job-posting-manager' ) . '</h2>';
	$form .= vk_gjpm_post_type_check_list();

	$form .= '<h2>' . __( 'Create Job-Posts Post type.', 'vk-google-job-posting-manager' ) . '</h2>';
	$form .= vk_gjpm_create_jobpost_posttype();
	$form .= '<input type="submit" value="Save Changes">';
	$form .= '</form>';

	return $form;
}

function vk_gjpm_render_form_input( $common_customfields ) {

	$form = '';

	foreach ( $common_customfields as $key => $value ) {

		if ( $value['type'] == 'text' ) {
			$form .= esc_html( $value['label'] ) . ':<br> <input type="text" name="common_' . $key . '" value="' . get_option( 'common_' . $key ) . '"><br>';

		} elseif ( $value['type'] == 'date' ) {

			$form .= esc_html( $value['label'] ) . ':<br> <input type="date" name="common_' . $key . '" value="' . get_option( 'common_' . $key ) . '"><br>';

		} elseif ( $value['type'] == 'select' ) {

			$form .= '<label>' . esc_html( $value['label'] ) . ':<br>';
			$form .= '<select name="common_' . $key . '"  >';

			foreach ( $value['options'] as $option_value => $option_label ) {

				$saved = get_option( 'common_' . $key );

				if ( $saved == $option_value ) {
					$selected = ' selected="selected"';
				} else {
					$selected = '';

				}

				$form .= '<option value="' . esc_attr( $option_value ) . '" ' . $selected . '>' . esc_html( $option_label ) . '</option>';
			}
			$form .= '</select>';
			$form .= '</label><br>';


		} elseif ( $value['type'] == 'checkbox' ) {

			$form .= '<p>' . esc_html( $value['label'] ) . ':</p>';

			$form .= '<ul>';

			$saved = get_option( 'common_' . $key );


			if ( $value['type'] == 'checkbox' ) {

				foreach ( $value['options'] as $option_value => $option_label ) {

					if ( is_array($saved) && in_array( $option_value, $saved ) ) {
						$selected = ' checked';
					} else {
						$selected = '';
					}
					$form .= '<li style="list-style: none"><label><input type="checkbox" name="common_' . esc_attr( $key ) . '[]" value="' . esc_attr( $option_value ) . '" ' . $selected . '  /><span>' . esc_html( $option_label ) . '</span></label></li>';

				}
				$form .= '</ul>';

			}
		}

	}
	return $form;
}


function vk_gjpm_save_data( $common_customfields ) {

	// nonce
	if ( ! isset( $_POST['_nonce_vk_job_posts'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( $_POST['_nonce_vk_job_posts'], 'standing_on_the_shoulder_of_giants' ) ) {
		return;
	}

	if ( ! isset( $common_customfields ) ) {
		return;
	}

	foreach ( $common_customfields as $key => $value ) {

		if ( $value['type'] == 'text' || $value['type'] == 'select' ) {

			update_option( 'common_' . $key, $_POST[ 'common_' . $key ] );

		} elseif ( $value['type'] == 'checkbox' ) {

			$checkbox_key = 'common_' . $key;

			if ( isset( $_POST[ $checkbox_key ] ) && is_array( $_POST[ $checkbox_key ] ) ) {

				update_option( $checkbox_key, $_POST[ $checkbox_key ] );

			}else{
				update_option( $checkbox_key, [] );

			}
		}

		vk_gjpm_save_check_list( );

		vk_gjpm_save_create_jobpost_posttype( );
	}
}


function vk_gjpm_create_jobpost_posttype() {

	$list          = '<ul>';
	$checked_saved = get_option( 'vk_gjpm_create_jobpost_posttype' );
	$checked       = ( isset( $checked_saved ) && $checked_saved == 'true' ) ? ' checked' : '';
	$list          .= '<li><label>';
	$list          .= '<input type="checkbox" name="vk_gjpm_create_jobpost_posttype" value="true" ' . $checked . ' />' . __( 'Create The Post Type.', 'vk-google-job-posting-manager' ) . '</label></li>';

	$list .= '</ul>';

	return $list;
}

function vk_gjpm_save_create_jobpost_posttype(  ) {

	$name = 'vk_gjpm_create_jobpost_posttype';

	if ( isset( $_POST[ $name ] ) ) {
		update_option( $name, $_POST[ $name ] );
	} else {
		update_option( $name, false );
	}

}

function vk_gjpm_post_type_check_list() {

	$args       = array(
		'public' => true,
	);
	$post_types = get_post_types( $args, 'object' );

	$list = '<ul>';
	foreach ( $post_types as $key => $value ) {
		if ( $key != 'attachment' && $key != 'job-posts' ) {

			$checked_saved = get_option( 'vk_gjpm_post_type_display_customfields' . $key );
			$checked       = ( isset( $checked_saved ) && $checked_saved == 'true' ) ? ' checked' : '';
			$list          .= '<li><label>';
			$list          .= '<input type="checkbox" name="vk_gjpm_post_type_display_customfields' . $key . '" value="true"' . $checked . ' />' . esc_html( $value->label );
			$list          .= '</label></li>';
		}
	}
	$list .= '</ul>';

	return $list;
}

function vk_gjpm_save_check_list(  ) {

	$args       = array(
		'public' => true,
	);
	$post_types = get_post_types( $args, 'object' );

	foreach ( $post_types as $key => $value ) {
		if ( $key != 'attachment' ) {

			$name = 'vk_gjpm_post_type_display_customfields' . $key;

			if ( isset( $_POST[ $name ] ) ) {
				update_option( $name, $_POST[ $name ] );
			} else {
				update_option( $name, 'false' );
			}
		}
	}
}

function vk_gjpm_print_jsonLD_in_footer() {

	$post_id       = get_the_ID();

	$custom_fields = vk_gjpm_get_custom_fields( $post_id );

	echo vk_gjpm_generate_jsonLD( $custom_fields );

}
add_action( 'wp_print_footer_scripts', 'vk_gjpm_print_jsonLD_in_footer' );

function vk_gjpm_get_custom_fields( $post_id ) {

	$post          = get_post( $post_id );
	$custom_fields = get_post_custom( $post_id );

	foreach ( (array) $custom_fields as $key => $value ) {

		$custom_fields[$key] = maybe_unserialize($value[0]);

		if ( substr_count( $key, 'vkjp_' ) == 0 ) {
			unset($custom_fields[$key]);
		}
	}

	if(isset($post->post_date)){
		$custom_fields['vkjp_datePosted'] = $post->post_date;
	}

	return $custom_fields;
}

function use_common_values( $custom_fields ) {

	foreach ( (array) $custom_fields as $key => $value ) {

		$temp = get_option( 'common_' . $key, null );

		if ( $custom_fields[ $key ] == ''  && isset( $temp ) ) {

			$custom_fields[ $key ] = $temp;

		} elseif ( ! isset( $custom_fields[ $key ] ) && ! isset( $temp ) ) {

			$custom_fields[ $key ] = '';
		}
	}

	return $custom_fields;
}


function vk_gjpm_generate_jsonLD( $custom_fields ) {

	if ( ! isset( $custom_fields['vkjp_title'] ) ) {
		return;
	}

	$custom_fields = use_common_values( $custom_fields );

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
    "sameAs" : "' . esc_attr( $custom_fields['vkjp_sameAs'] ) . '",
    "logo" : "' . esc_attr( $custom_fields['vkjp_logo'] ) . '"
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
