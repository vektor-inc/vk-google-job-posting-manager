<?php
/*
vgjpm_create_jobpost_posttype()
vgjpm_post_type_check_list();
vgjpm_get_custom_fields();
vgjpm_get_raw_post_meta();
vgjpm_maybe_unserialize_without_object();
vgjpm_contains_object();
vgjpm_use_common_values();
vgjpm_array_to_string();
vgjpm_image_filter_id_to_url();
vgjpm_sanitize_arr();
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
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
	$post       = get_post( $post_id );
	$stored_all = get_post_custom( $post_id );

	if ( ! $stored_all ) {
		return array();
	}

	$custom_fields = array();

	foreach ( (array) $stored_all as $key => $stored_values ) {
		// Keep only this plugin's own fields. Unrelated meta is skipped before
		// its value is touched, so other plugins' data is never restored here.
		// このプラグイン自身のフィールドのみを残す。無関係なメタは値に触れる前に
		// 除外するため、他プラグインのデータをここで復元することはない。
		if ( 0 !== strpos( $key, 'vkjp_' ) ) {
			continue;
		}

		$stored_value = isset( $stored_values[0] ) ? $stored_values[0] : '';

		// Checkbox fields are stored as a serialized array, so the value has to
		// be restored. Restoring must not create PHP objects.
		// チェックボックスの項目はシリアライズされた配列として保存されているため復元が必要。
		// 復元時に PHP オブジェクトを生成させてはならない。
		$custom_fields[ $key ] = vgjpm_maybe_unserialize_without_object( $stored_value );
	}

	if ( isset( $post->post_date ) ) {
		$custom_fields['vkjp_datePosted'] = gmdate( 'Y-m-d', strtotime( $post->post_date ) );
	}

	// Sanitize text-based fields for consistency.
	$sanitize_keys = array(
		'vkjp_title',
		'vkjp_name',
		'vkjp_identifier',
		'vkjp_sameAs',
		'vkjp_logo',
		'vkjp_streetAddress',
		'vkjp_addressLocality',
		'vkjp_addressRegion',
		'vkjp_postalCode',
		'vkjp_addressCountry',
		'vkjp_jobLocationType',
		'vkjp_applicantLocationRequirements_name',
		'vkjp_currency',
		'vkjp_unitText',
		'vkjp_employmentType',
	);

	foreach ( $sanitize_keys as $sanitize_key ) {
		if ( isset( $custom_fields[ $sanitize_key ] ) && is_string( $custom_fields[ $sanitize_key ] ) ) {
			$custom_fields[ $sanitize_key ] = sanitize_text_field( $custom_fields[ $sanitize_key ] );
		}
	}

	return $custom_fields;
}

/**
 * Get a post meta value exactly as it is stored, without restoring it.
 * 投稿メタの値を、復元せず保存されているままの形で取得する。
 *
 * get_post_meta() restores a serialized value by itself, which creates a PHP
 * object when a serialized object is stored. Reading the stored value first
 * lets the caller restore it safely instead.
 * get_post_meta() は保存値を自前で復元するため、シリアライズされたオブジェクトが
 * 保存されていた場合は PHP オブジェクトを生成してしまう。保存値をそのまま取得して
 * おけば、呼び出し側が安全に復元できる。
 *
 * @param int    $post_id  Post ID to read the value of.
 * @param string $meta_key Meta key to read.
 * @return string Stored value, or an empty string when the key holds no string value.
 */
function vgjpm_get_raw_post_meta( $post_id, $meta_key ) {
	// get_post_custom() returns every value as stored, because it asks for all
	// meta at once instead of a single key.
	// get_post_custom() は単一キーではなく全メタをまとめて要求するため、
	// どの値も保存されているまま返る。
	$stored_all = get_post_custom( $post_id );

	if ( ! isset( $stored_all[ $meta_key ][0] ) || ! is_string( $stored_all[ $meta_key ][0] ) ) {
		return '';
	}

	return $stored_all[ $meta_key ][0];
}

/**
 * Restore a stored value without allowing PHP objects to be created.
 * 保存されている値を復元する。その際 PHP オブジェクトの生成は許可しない。
 *
 * Meta and option values written by this plugin are plain strings or
 * serialized arrays. A serialized object is therefore discarded instead of
 * being restored, because restoring one hands the stored data to another
 * class's magic methods.
 * このプラグインが書き込むメタ値・オプション値は、素の文字列かシリアライズされた配列。
 * そのためシリアライズされたオブジェクトは復元せず破棄する。復元すると保存されていた
 * データが別クラスのマジックメソッドに渡ってしまうため。
 *
 * @param mixed $value Stored value as it is saved in the database.
 * @return mixed Restored value, the value itself when it is not serialized, or an empty string when it cannot be restored safely.
 */
function vgjpm_maybe_unserialize_without_object( $value ) {
	// There is nothing to restore unless the value is a serialized string.
	// シリアライズされた文字列でなければ復元するものはない。
	if ( ! is_string( $value ) || ! is_serialized( $value ) ) {
		return $value;
	}

	// A serialized boolean false is the only input whose restored value is
	// legitimately false, so it is handled before the failure check below.
	// シリアライズされた false だけは復元結果が正しく false になるため、
	// 後続の失敗判定より前に処理する。
	if ( 'b:0;' === $value ) {
		return false;
	}

	// allowed_classes => false stops PHP from instantiating any class while
	// reading the data, so no class's magic methods can be reached.
	// max_depth => 64 stops the restore itself at 64 levels: checking the depth
	// only after the restore has finished still lets the restore spend stack and
	// memory up to that point.
	// The warning for malformed data is silenced on purpose: the value comes
	// from the database and a broken one is handled right below, so it must not
	// fill the error log every time the page is viewed.
	// allowed_classes => false により、読み込み中に PHP がどのクラスもインスタンス化しないため、
	// どのクラスのマジックメソッドにも到達できない。max_depth => 64 は復元そのものを
	// 64 階層で打ち切る。復元し終えてから階層を見るだけでは、打ち切るまでの復元に
	// スタックとメモリを使うため、復元の時点で止める。壊れたデータの警告は意図的に抑制している。
	// 値はデータベース由来であり、壊れていた場合は直後で処理するため、
	// ページ表示のたびにエラーログを埋めてはならない。
	// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize -- allowed_classes => false prevents PHP object injection, and a malformed value is handled below.
	$restored = @unserialize(
		$value,
		array(
			'allowed_classes' => false,
			'max_depth'       => 64,
		)
	);

	// Broken data, and data that still carries an object, are both unusable.
	// 壊れたデータ、およびオブジェクトを含んだままのデータは、どちらも利用できない。
	if ( false === $restored || vgjpm_contains_object( $restored ) ) {
		return '';
	}

	return $restored;
}

/**
 * Tell whether a value is an object, or an array holding one at any depth.
 * 値がオブジェクトか、あるいは何階層目かにオブジェクトを含む配列かを判定する。
 *
 * @param mixed $value Value to inspect.
 * @param int   $depth Current recursion depth.
 * @return bool True when an object is found, or when the value nests deeper than the limit.
 */
function vgjpm_contains_object( $value, $depth = 0 ) {
	// An array that points at itself never ends the recursion and exhausts the
	// memory limit, so the walk is cut off at a nesting limit.
	// The value stored here is the array of selected checkbox values, which is
	// nested only shallowly. A value deeper than the limit is not a normal
	// stored value, so it is discarded just like a value holding an object.
	// 自分自身を指す配列を渡されると再帰が止まらずメモリを使い切るため、階層の上限で打ち切る。
	// ここで保存するのはチェックボックスの選択値の配列で、入れ子はごく浅い。
	// 上限を超えた値は正常な保存値ではないので、オブジェクトを含む場合と同じく使わずに破棄する。
	if ( 64 < $depth ) {
		return true;
	}

	if ( is_object( $value ) ) {
		return true;
	}

	if ( is_array( $value ) ) {
		foreach ( $value as $item ) {
			if ( vgjpm_contains_object( $item, $depth + 1 ) ) {
				return true;
			}
		}
	}

	return false;
}

function vgjpm_use_common_values( $custom_fields, $output_type ) {
	global $vgjpm_prefix;

	$VGJPM_Custom_Field_Job_Post = new VGJPM_Custom_Field_Job_Post();
	$default_custom_fields       = $VGJPM_Custom_Field_Job_Post->custom_fields_array();

	foreach ( $default_custom_fields as $key => $value ) {

		$options = vgjpm_get_common_field_options();

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
