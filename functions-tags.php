<?php
/*
vgjpm_create_jobpost_posttype()
vgjpm_post_type_check_list();
vgjpm_get_custom_fields();
vgjpm_use_common_values();
vgjpm_array_to_string();
vgjpm_image_filter_id_to_url();
vgjpm_sanitize_arr();
*/

function vgjpm_create_jobpost_posttype() {
	$list          = '<ul>';
	$checked_saved = get_option( 'vgjpm_create_jobpost_posttype' );
	$checked       = ( isset( $checked_saved ) && $checked_saved == 'true' ) ? ' checked' : '';
	$list         .= '<li><label>';
	$list         .= '<input type="checkbox" name="vgjpm_create_jobpost_posttype" value="true" ' . esc_attr( $checked ) . ' />' . __( 'Create The Post Type', 'vk-google-job-posting-manager' ) . '</label></li>';

	$list .= '</ul>';

	return $list;
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

function vgjpm_get_custom_fields( $post_id ) {
	$post          = get_post( $post_id );
	$custom_fields = get_post_custom( $post_id );

	if ( ! $custom_fields ) {
		return array();
	}

	foreach ( (array) $custom_fields as $key => $value ) {
		$custom_fields[ $key ] = maybe_unserialize( $value[0] );

		if ( substr_count( $key, 'vkjp_' ) == 0 ) {
			unset( $custom_fields[ $key ] );
		}
	}

	if ( isset( $post->post_date ) ) {
		$custom_fields['vkjp_datePosted'] = date( 'Y-m-d', strtotime( $post->post_date ) );
	}

	return $custom_fields;
}

function vgjpm_use_common_values( $custom_fields, $output_type ) {
	global $vgjpm_prefix;

	$VGJPM_Custom_Field_Job_Post = new VGJPM_Custom_Field_Job_Post();
	$default_custom_fields       = $VGJPM_Custom_Field_Job_Post->custom_fields_array();

	foreach ( $default_custom_fields as $key => $value ) {

		$options = vkjpm_get_common_field_options();

		$custom_fields = vgjpm_image_filter_id_to_url( $custom_fields, $key, $options );

		if ( ! isset( $custom_fields[ $key ] ) && isset( $options[ $key ] ) ) {

			$custom_fields[ $key ] = $options[ $key ];

		} elseif ( ! isset( $custom_fields[ $key ] ) && ! isset( $options[ $key ] ) ) {

			$custom_fields[ $key ] = '';
		}
	}

	if ( $output_type == 'json' ) {
		// Array to string.
		$custom_fields = vgjpm_array_to_string( $custom_fields );
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

function vgjpm_image_filter_id_to_url( $custom_fields, $key, $options ) {

	if ( $key == 'vkjp_logo' ) {
		if ( isset( $custom_fields[ $key ] ) ) {
			$each_post_attachment_url = wp_get_attachment_url( $custom_fields[ $key ] );

			if ( $each_post_attachment_url ) {
				$custom_fields[ $key ] = $each_post_attachment_url;
			}
		} elseif ( isset( $options[ $key ] ) ) {

			$common_attachment_url = wp_get_attachment_url( $options[ $key ] );

			if ( $common_attachment_url ) {
				$custom_fields[ $key ] = $common_attachment_url;
			}
		}
	}

	return $custom_fields;
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
