<?php
/*
このファイルの元ファイルは
https://github.com/vektor-inc/vektor-wp-libraries
にあります。
修正の際は上記リポジトリのデータを修正してください。
編集権限を持っていない方で何か修正要望などありましたら
各プラグインのリポジトリにプルリクエストで結構です。
*/

if ( ! class_exists( 'VK_Custom_Field_Builder' ) ) {

	class VK_Custom_Field_Builder {

		public static $version = '0.2.0';

		public static function init() {
			add_action( 'admin_footer', array( __CLASS__, 'print_script' ), 10, 2 );
		}

		static function admin_directory_url() {
			global $custom_field_builder_url;
			$direcrory_url = $custom_field_builder_url;
			return $direcrory_url;
		}

		public static function print_script() {
			wp_register_script( 'datepicker', self::admin_directory_url() . 'js/datepicker.js', array( 'jquery', 'jquery-ui-datepicker' ), self::$version, true );
			wp_enqueue_script( 'datepicker' );
			wp_register_script( 'vk_mediauploader', self::admin_directory_url() . 'js/mediauploader.js', array( 'jquery' ), self::$version, true );
			wp_enqueue_script( 'vk_mediauploader' );
			wp_enqueue_script( 'flexible-table', self::admin_directory_url() . 'js/flexible-table.js', array( 'jquery', 'jquery-ui-sortable' ), self::$version, true );
			wp_enqueue_style( 'cf-builder-style', self::admin_directory_url() . 'css/cf-builder.css', array(), self::$version, 'all' );
		}

		public static function form_post_value( $post_field = '', $type = false ) {
			$value = '';
			global $post;
			$value = esc_attr( get_post_meta( $post->ID, $post_field, true ) );
			if ( isset( $_POST[ $post_field ] ) && $_POST[ $post_field ] ) {
				if ( isset( $type ) && $type == 'textarea' ) {
					$value = esc_textarea( $_POST[ $post_field ] );
				} else {
					$value = esc_attr( $_POST[ $post_field ] );
				}
			}
			return $value;
		}

		public static function form_required() {
			$required = '<span class="required">Required</span>';
			return $required;
		}

		public static function form_table( $custom_fields_array, $befor_items = '', $echo = true ) {

			wp_nonce_field( wp_create_nonce( __FILE__ ), 'noncename__fields' );

			global $post;
			global $custom_field_builder_url;

			$form_html = '';

			$form_html .= '<div class="vk-custom-field-builder">';
			$form_html .= '<table class="table table-striped table-bordered">';

			$form_html .= $befor_items;

			foreach ( $custom_fields_array as $key => $value ) {
				$form_html .= '<tr class="cf_item"><th class="text-nowrap"><label>' . $value['label'] . '</label>';
				$form_html .= ( isset( $value['required'] ) && $value['required'] ) ? self::form_required() : '';
				$form_html .= '</th><td>';

				if ( $value['type'] == 'text' || $value['type'] == 'url' ) {


					if ( isset( $value['before_text'] ) && $value['before_text'] ) {
						$form_html .= esc_html( $value['before_text'] ) . ' ';
					}

					$form_html .= '<input class="form-control" type="text" id="' . $key . '" name="' . $key . '" value="' . self::form_post_value( $key ) . '" size="70">';

					if ( isset( $value['after_text'] ) && $value['after_text'] ) {
						$form_html .= ' ' . esc_html( $value['after_text'] );
					}


				} elseif ( $value['type'] == 'date' ) {
					$form_html .= '<input class="form-control" type="date" id="' . $key . '" name="' . $key . '" value="' . self::form_post_value( $key ) . '" size="70">';

				} elseif ( $value['type'] == 'textarea' ) {
					$form_html .= '<textarea class="form-control" class="cf_textarea_wysiwyg" name="' . $key . '" cols="70" rows="3">' . self::form_post_value( $key, 'textarea' ) . '</textarea>';

				} elseif ( $value['type'] == 'select' ) {
					$form_html .= '<select id="' . $key . '" class="form-control" name="' . $key . '"  >';

					foreach ( $value['options'] as $option_value => $option_label ) {
						if ( self::form_post_value( $key ) == $option_value ) {
							$selected = ' selected="selected"';
						} else {
							$selected = '';
						}

						$form_html .= '<option value="' . esc_attr( $option_value ) . '"' . $selected . '>' . esc_html( $option_label ) . '</option>';
					}
					$form_html .= '</select>';

				} elseif ( $value['type'] == 'checkbox' || $value['type'] == 'radio' ) {
					$field_value = get_post_meta( $post->ID, $key, true );
					$form_html  .= '<ul>';

					if ( $value['type'] == 'checkbox' ) {
						if ( ! is_array( $field_value ) ) {
							$field_value = unserialize( get_post_meta( $post->ID, $key, true ) );
						}
					}

					foreach ( $value['options'] as $option_value => $option_label ) {
						$selected = '';
						if ( $value['type'] == 'checkbox' ) {

							if ( is_array( $field_value ) && in_array( $option_value, $field_value ) ) {
								$selected = ' checked';
							}

							$form_html .= '<li><label><input type="checkbox" name="' . esc_attr( $key ) . '[]" id="' . esc_attr( $key ) . '" value="' . esc_attr( $option_value ) . '"' . $selected . '  /><span>' . esc_html( $option_label ) . '</span></label></li>';

						} elseif ( $value['type'] == 'radio' ) {
							if ( $option_value == $field_value ) {
								$selected = ' checked';
							}
							$form_html .= '<li><label><input type="radio" name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" value="' . esc_attr( $option_value ) . '"' . $selected . '  /><span>' . esc_html( $option_label ) . '</span></label></li>';
						}
					} // foreach ($value['options'] as $option_value => $option_label) {

					$form_html .= '</ul>';

				} elseif ( $value['type'] == 'image' ) {
					if ( $post->$key ) {
								$thumb_image     = wp_get_attachment_image_src( $post->$key, 'medium', false );
								$thumb_image_url = $thumb_image[0];
						// } elseif ( isset( $_POST[ $key ] ) && $_POST[ $key ] ) {
						// $thumb_image     = wp_get_attachment_image_src( $image_key, 'medium', false );
						// $thumb_image_url = $thumb_image[0];
					} else {
								$thumb_image_url = $custom_field_builder_url . 'images/no_image.png';
					}
					$form_html .= '<img src="' . $thumb_image_url . '" id="thumb_' . $key . '" alt="" class="input_thumb" style="width:200px;height:auto;"> ';

					$form_html .= '<input type="hidden" name="' . $key . '" id="' . $key . '" value="' . self::form_post_value( $key ) . '" style="width:60%;" />';

					$form_html .= '<button id="media_' . $key . '" class="cfb_media_btn btn btn-default button button-default">' . __( 'Choose Image', 'custom_field_builder_textdomain' ) . '</button> ';

					$form_html .= '<a id="media_reset_' . $key . '" class="media_reset_btn btn btn-default button button-default">' . __( 'Delete Image', 'custom_field_builder_textdomain' ) . '</a>';

				} elseif ( $value['type'] == 'file' ) {
					$form_html .= '<input name="' . $key . '" id="' . $key . '" value="' . self::form_post_value( $key ) . '" style="width:60%;" />
<button id="media_src_' . $key . '" class="cfb_media_btn btn btn-default button button-default">' . __( 'Select file', 'custom_field_builder_textdomain' ) . '</button> ';
					if ( $post->$key ) {
						$form_html .= '<a href="' . esc_url( $post->$key ) . '" target="_blank" class="btn btn-default button button-default">' . __( 'View file', 'custom_field_builder_textdomain' ) . '</a>';
					}
				}
				if ( $value['description'] ) {
					$form_html .= '<div class="description">' . apply_filters( 'the_content', $value['description'] ) . '</div>';
				}
				$form_html .= '</td></tr>';
			}
			$form_html .= '</table>';
			$form_html .= '</div>';
			if ( $echo ) {
				wp_enqueue_media();
				echo $form_html;
			} else {
				wp_enqueue_media();
				return $form_html;
			}

		} // public static function form_table( $custom_fields_array, $befor_items, $echo = true ){


		public static function save_cf_value( $custom_fields_array ) {

			global $post;

			$noncename__fields = isset( $_POST['noncename__fields'] ) ? $_POST['noncename__fields'] : null;

			if ( ! wp_verify_nonce( $noncename__fields, wp_create_nonce( __FILE__ ) ) ) {
				return;
			}

			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return $post_id; }

			foreach ( $custom_fields_array as $key => $value ) {

				$field_value = ( isset( $_POST[ $key ] ) ) ? $_POST[ $key ] : '';

				$parent_key = $value['parent'];
				$new_array= array();


				if ( !empty( $parent_key ) ) {

					$save_key = 'vkjp_'.$parent_key;
					$key = str_replace('vkjp_', '', $key);

					$pre_value = get_post_meta( $post->ID, $save_key, false );
					$pre_value[ $key ] = $field_value;

					update_post_meta( $post->ID, $save_key, $pre_value );

				} else {

					if ( get_post_meta( $post->ID, $key ) == '' ) {

						add_post_meta( $post->ID, $key, $field_value, false );

					} elseif ( $field_value != get_post_meta( $post->ID, $key, false ) ) {

						update_post_meta( $post->ID, $key, $field_value );

					} elseif ( $field_value == '' ) {
						delete_post_meta( $post->ID, $key, get_post_meta( $post->ID, $key, false ) );
					}
				}

			} // foreach ($custom_fields_all_array as $key => $value) {
		}

	} // class Vk_custom_field_builder

	VK_Custom_Field_Builder::init();

	require_once( 'custom-field-flexible-table.php' );

} // if ( ! class_exists( 'VK_Custom_Field_Builder' ) ) {
