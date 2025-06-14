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

		public static $version = '0.2.4';

		// define( 'Bill_URL', get_template_directory_uri() );
		public static function init() {
			add_action( 'admin_footer', array( __CLASS__, 'print_script' ), 10, 2 );
		}

		static function admin_directory_url() {
			global $custom_field_builder_url; // configファイルで指定
			$direcrory_url = $custom_field_builder_url;
			return $direcrory_url;
		}

		/*
		-------------------------------------------
		管理画面用共通js読み込み（記述場所によっては動作しないので注意）
		-------------------------------------------
		*/
		public static function print_script( $hook_suffix ) {
			wp_register_script( 'datepicker', self::admin_directory_url() . 'js/datepicker.js', array( 'jquery', 'jquery-ui-datepicker' ), self::$version, true );
			wp_enqueue_script( 'datepicker' );
			wp_enqueue_script( 'vk_mediauploader', self::admin_directory_url() . 'js/mediauploader.js', array( 'jquery' ), self::$version, true );
			wp_localize_script(
				'vk_mediauploader',
				'vk_cfb',
				array(
					'select_image' => __( 'Select image', 'vk-google-job-posting-manager' ),
				)
			);

			// flexible-table の js が NestedPagesのjsと干渉して正常に動かなくなるので、NestedPagesのページで読み込まないように.
			global $hook_suffix;
			$cfb_flexible_table_excludes = array( 'toplevel_page_nestedpages' );
			$cfb_flexible_table_excludes = apply_filters( 'cfb_flexible_table_excludes', $cfb_flexible_table_excludes );

			if ( ! in_array( $hook_suffix, $cfb_flexible_table_excludes, true ) ) {
				wp_enqueue_script( 'flexible-table', self::admin_directory_url() . 'js/flexible-table.js', array( 'jquery', 'jquery-ui-sortable' ), self::$version, true );
			}

			wp_enqueue_style( 'cf-builder-style', self::admin_directory_url() . 'css/cf-builder.css', array(), self::$version, 'all' );

			// Contact form 7　など jQuery ui のクラス名を使っていて干渉するので除外 .
			$cfb_jquery_ui_excludes = array( 'toplevel_page_wpcf7', 'toplevel_page_gf_edit_forms' );
			$cfb_jquery_ui_excludes = apply_filters( 'cfb_jquery_ui_excludes', $cfb_jquery_ui_excludes );
			if ( ! in_array( $hook_suffix, $cfb_jquery_ui_excludes, true ) ) {
				wp_enqueue_style( 'cf-builder-jquery-ui-style', self::admin_directory_url() . 'css/jquery-ui.css', array( 'cf-builder-style' ), self::$version, 'all' );
			}
		}

		public static function form_post_value( $post_field = '', $type = false ) {
			$value = '';
			global $post;
			$value = esc_attr( get_post_meta( $post->ID, $post_field, true ) );
			if ( isset( $_POST[ $post_field ] ) && $_POST[ $post_field ] ) {
				if ( isset( $type ) && $type == 'textarea' ) {
					// n2brはフォームにbrがそのまま入ってしまうので入れない
					$value = esc_textarea( $_POST[ $post_field ] );
				} else {
					$value = esc_attr( $_POST[ $post_field ] );
				}
			}
			return $value;
		}

		public static function form_required() {
			$required = '<span class="required">' . __( 'Required', 'vk-google-job-posting-manager' ) . '</span>';
			return $required;
		}

		/*
		-------------------------------------------
		フォームテーブル
		-------------------------------------------
		*/
		public static function form_table( $custom_fields_array, $befor_items = '', $echo = true, $options = array() ) {

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

					$post_value = '';
					$form_post_value = self::form_post_value( $key );
					if ( ! empty( $form_post_value ) || '0' === $form_post_value ) {
						$post_value = $form_post_value;
					} elseif ( ! empty( $options[ $key ] ) ) {
						$post_value = $options[ $key ];
					}

					$form_html .= '<input class="form-control" type="text" id="' . $key . '" name="' . $key . '" value="' . $post_value . '" size="70">';

					if ( isset( $value['after_text'] ) && $value['after_text'] ) {
						$form_html .= ' ' . esc_html( $value['after_text'] );
					}
				} elseif ( $value['type'] == 'datepicker' ) {

					$post_value = '';
					if ( ! empty( self::form_post_value( $key ) ) ) {
						$post_value = self::form_post_value( $key );
					} elseif ( ! empty( $options[ $key ] ) ) {
						$post_value = $options[ $key ];
					}

					$form_html .= '<input class="form-control datepicker" type="text" id="' . $key . '" name="' . $key . '" value="' . $post_value . '" size="70">';

				} elseif ( $value['type'] == 'textarea' ) {

					$post_value = '';
					if ( ! empty( self::form_post_value( $key, 'textarea' ) ) ) {
						$post_value = self::form_post_value( $key, 'textarea' );
					} elseif ( ! empty( $options[ $key ] ) ) {
						$post_value = $options[ $key ];
					}

					$form_html .= '<textarea class="form-control" class="cf_textarea_wysiwyg" name="' . $key . '" cols="70" rows="3">' . $post_value . '</textarea>';

				} elseif ( $value['type'] == 'select' ) {
					$form_html .= '<select id="' . $key . '" class="form-control" name="' . $key . '"  >';

					foreach ( $value['options'] as $option_value => $option_label ) {
						if ( self::form_post_value( $key ) == $option_value ) {
							$selected = ' selected="selected"';
						} elseif ( ! empty( $options[ $key ] ) && $options[ $key ] === $option_value ) {
							$selected = ' selected="selected"';
						} else {
							$selected = '';
						}

						$form_html .= '<option value="' . esc_attr( $option_value ) . '"' . $selected . '>' . esc_html( $option_label ) . '</option>';
					}
					$form_html .= '</select>';

				} elseif ( $value['type'] == 'checkbox' || $value['type'] == 'radio' ) {
					$field_value = array();
					if ( ! empty( get_post_meta( $post->ID, $key, true ) ) ) {
						$field_value = get_post_meta( $post->ID, $key, true );
					} elseif ( ! empty( $options[ $key ] ) ) {
						$field_value = $options[ $key ];
					}
					$form_html .= '<ul>';

					// シリアライズして保存されてたら戻す
					if ( $value['type'] == 'checkbox' ) {
						if ( ! is_array( $field_value ) ) {
							$field_value = unserialize( get_post_meta( $post->ID, $key, true ) );
						}
					}

					foreach ( $value['options'] as $option_value => $option_label ) {
						$selected = '';
						// print '<pre style="text-align:left">';print_r( $option_value );print '</pre>';
						// print '<pre style="text-align:left">';print_r($field_value);print '</pre>';
						// チェックボックス
						if ( $value['type'] == 'checkbox' ) {

							if ( is_array( $field_value ) && in_array( $option_value, $field_value ) ) {
								$selected = ' checked';
							}

							$form_html .= '<li><label><input type="checkbox" name="' . esc_attr( $key ) . '[]" id="' . esc_attr( $key ) . '" value="' . esc_attr( $option_value ) . '"' . $selected . '  /><span>' . esc_html( $option_label ) . '</span></label></li>';

							// ラジオボタン
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
						$thumb_image = wp_get_attachment_image_src( $post->$key, 'medium', false );
						if ( is_array( $thumb_image ) && ! empty( $thumb_image[0] ) ) {
							$thumb_image_url = $thumb_image[0];
						} else {
							$thumb_image_url = $custom_field_builder_url . 'images/no_image.png';
						}
					} elseif ( ! empty( $options[ $key ] ) ) {
						$thumb_image = wp_get_attachment_image_src( $options[ $key ], 'medium', false );
						if ( is_array( $thumb_image ) && ! empty( $thumb_image[0] ) ) {
							$thumb_image_url = $thumb_image[0];
						} else {
							$thumb_image_url = $custom_field_builder_url . 'images/no_image.png';
						}
					} else {
						$thumb_image_url = $custom_field_builder_url . 'images/no_image.png';
					}

					$post_value = '';
					if ( ! empty( self::form_post_value( $key ) ) ) {
						$post_value = self::form_post_value( $key );
					} elseif ( ! empty( $options[ $key ] ) ) {
						$post_value = $options[ $key ];
					}
					// ダミー & プレビュー画像
					$form_html .= '<img src="' . $thumb_image_url . '" id="thumb_' . $key . '" alt="" class="input_thumb" style="width:200px;height:auto;"> ';

					// 実際に送信する値
					$form_html .= '<input type="hidden" name="' . $key . '" id="' . $key . '" value="' . $post_value . '" style="width:60%;" />';

					// 画像選択ボタン
					// .media_btn がトリガーでメディアアップローダーが起動する
					// id名から media_ を削除した id 名の input 要素に返り値が反映される。
					// id名が media_src_ で始まる場合はURLを返す
					$form_html .= '<button id="media_' . $key . '" class="cfb_media_btn btn btn-default button button-default">' . __( 'Choose Image', 'vk-google-job-posting-manager' ) . '</button> ';

					// 削除ボタン
					// ボタンタグだとその場でページが再読込されてしまうのでaタグに変更
					$form_html .= '<a id="media_reset_' . $key . '" class="media_reset_btn btn btn-default button button-default">' . __( 'Delete Image', 'vk-google-job-posting-manager' ) . '</a>';

				} elseif ( 'file' === $value['type'] ) {

					$post_value = '';
					if ( ! empty( self::form_post_value( $key ) ) ) {
						$post_value = self::form_post_value( $key );
					} elseif ( ! empty( $options[ $key ] ) ) {
						$post_value = $options[ $key ];
					}

					$form_html .= '<input name="' . $key . '" id="' . $key . '" value="' . $post_value . '" style="width:60%;" />
<button id="media_src_' . $key . '" class="cfb_media_btn btn btn-default button button-default">' . __( 'Select file', 'vk-google-job-posting-manager' ) . '</button> ';
					if ( $post_value ) {
						$form_html .= '<a href="' . esc_url( $post_value ) . '" target="_blank" class="btn btn-default button button-default">' . __( 'View file', 'vk-google-job-posting-manager' ) . '</a>';
					}
				}
				if ( $value['description'] ) {
					$form_html .= '<div class="description">' . wp_kses_post( $value['description'] ) . '</div>';
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

		/*
		-------------------------------------------
		入力された値の保存
		-------------------------------------------
		*/
		public static function save_cf_value( $custom_fields_array ) {

			global $post;

			// 設定したnonce を取得（CSRF対策）
			$noncename__fields = isset( $_POST['noncename__fields'] ) ? $_POST['noncename__fields'] : null;

			// nonce を確認し、値が書き換えられていれば、何もしない（CSRF対策）
			if ( ! wp_verify_nonce( $noncename__fields, wp_create_nonce( __FILE__ ) ) ) {
				return;
			}

			// 自動保存ルーチンかどうかチェック。そうだった場合は何もしない（記事の自動保存処理として呼び出された場合の対策）
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return $post_id; }

			foreach ( $custom_fields_array as $key => $value ) {

				$field_value = ( isset( $_POST[ $key ] ) ) ? $_POST[ $key ] : '';

				// データが空だったら入れる
				if ( get_post_meta( $post->ID, $key ) == '' ) {
					add_post_meta( $post->ID, $key, $field_value, true );
					// 今入ってる値と違ってたらアップデートする
				} elseif ( $field_value != get_post_meta( $post->ID, $key, true ) ) {
					update_post_meta( $post->ID, $key, $field_value );
					// 入力がなかったら消す
				} elseif ( $field_value == '' ) {
					delete_post_meta( $post->ID, $key, get_post_meta( $post->ID, $key, true ) );
				}
			} // foreach ($custom_fields_all_array as $key => $value) {
		}
	} // class Vk_custom_field_builder

	VK_Custom_Field_Builder::init();

	require_once 'custom-field-flexible-table.php';

} // if ( ! class_exists( 'VK_Custom_Field_Builder' ) ) {
