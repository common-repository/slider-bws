<?php
/**
Plugin Name: Slider by BestWebSoft
Plugin URI: https://bestwebsoft.com/products/wordpress/plugins/slider/
Description: Simple and easy to use plugin adds a slider to your web site.
Author: BestWebSoft
Text Domain: slider-bws
Domain Path: /languages
Version: 1.1.0
Author URI: https://bestwebsoft.com/
License: GPLv3 or later
 */

/*
© Copyright 2021 BestWebSoft  ( https://support.bestwebsoft.com )

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * Extends WP_List_Table and WP_Media_List_Table classes.
 */
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
if ( ! class_exists( 'WP_Media_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-media-list-table.php';
}

if ( ! function_exists( 'sldr_add_admin_menu' ) ) {
	/**
	 * Add WordPress page 'bws_plugins' and sub-page of this plugin to admin-panel.
	 */
	function sldr_add_admin_menu() {
		/* Add custom menu page */
		$slider_general_page  = add_menu_page( __( 'Sliders', 'slider-bws' ), __( 'Sliders', 'slider-bws' ), 'manage_options', 'slider.php', 'sldr_table_page_render', 'none', 44.1 );
		$slider_slide_page    = add_submenu_page( 'slider.php', __( 'Add new', 'slider-bws' ), __( 'Add New', 'slider-bws' ), 'manage_options', 'slider-new.php', 'sldr_add_new_render' );
		$slider_category_page = add_submenu_page( 'slider.php', __( 'Slider Categories', 'slider-bws' ), __( 'Slider Categories', 'slider-bws' ), 'manage_options', 'slider-categories.php', 'sldr_categories_render' );
		$slider_settings_page = add_submenu_page( 'slider.php', __( 'Slider Global Settings', 'slider-bws' ), __( 'Global Settings', 'slider-bws' ), 'manage_options', 'slider-settings.php', 'sldr_settings_page' );

		add_submenu_page( 'slider.php', 'BWS Panel', 'BWS Panel', 'manage_options', 'sldr-bws-panel', 'bws_add_menu_render' );

		/* Add help tabs */
		add_action( 'load-' . $slider_settings_page, 'sldr_add_tabs' );
		add_action( 'load-' . $slider_general_page, 'sldr_add_tabs' );
		add_action( 'load-' . $slider_general_page, 'sldr_screen_options' );
		add_action( 'load-' . $slider_slide_page, 'sldr_add_tabs' );
		add_action( 'load-' . $slider_category_page, 'sldr_add_tabs' );
		add_action( 'load-' . $slider_category_page, 'sldr_cat_screen_options' );
	}
}

if ( ! function_exists( 'sldr_init' ) ) {
	/**
	 * Plugin initialization on frontend and backend.
	 */
	function sldr_init() {
		global $sldr_plugin_info;
		/* Add bws menu. Use in slider_admin_menu */
		require_once __DIR__ . '/bws_menu/bws_include.php';
		bws_include_init( plugin_basename( __FILE__ ) );

		/* Get plugin data */
		if ( ! $sldr_plugin_info ) {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$sldr_plugin_info = get_plugin_data( __FILE__ );
		}

		/* Function check if plugin is compatible with current WP version  */
		bws_wp_min_version_check( plugin_basename( __FILE__ ), $sldr_plugin_info, '4.5' );
		/* Call register settings function */
		sldr_settings();
	}
}

if ( ! function_exists( 'sldr_admin_init' ) ) {
	/**
	 * Plugin initialization on backend.
	 */
	function sldr_admin_init() {
		global $bws_plugin_info, $sldr_plugin_info, $bws_shortcode_list;

		/* Add variable for bws_menu. */
		if ( empty( $bws_plugin_info ) ) {
			$bws_plugin_info = array(
				'id'      => '650',
				'version' => $sldr_plugin_info['Version'],
			);
		}
		/* Add slider to global $bws_shortcode_list  */
		$bws_shortcode_list['sldr'] = array(
			'name'        => 'Slider',
			'js_function' => 'sldr_shortcode_init',
		);
	}
}

if ( ! function_exists( 'sldr_plugins_loaded' ) ) {
	/**
	 * Add localization.
	 */
	function sldr_plugins_loaded() {
		/* Internationalization */
		load_plugin_textdomain( 'slider-bws', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
}

if ( ! function_exists( 'sldr_settings' ) ) {
	/**
	 * Register settings.
	 */
	function sldr_settings() {
		global $sldr_options, $sldr_plugin_info;
		$plugin_db_version = '0.1';

		/* Install the option defaults. */
		if ( ! get_option( 'sldr_options' ) ) {
			$option_defaults = sldr_get_options_default();
			add_option( 'sldr_options', $option_defaults );
		}

		/* Get options from the database. */
		$sldr_options = get_option( 'sldr_options' );

		if ( ! isset( $sldr_options['plugin_option_version'] ) || $sldr_options['plugin_option_version'] !== $sldr_plugin_info['Version'] ) {
			$option_defaults                       = sldr_get_options_default();
			$sldr_options                          = array_merge( $option_defaults, $sldr_options );
			$sldr_options['plugin_option_version'] = $sldr_plugin_info['Version'];
			$update_option                         = true;
		}

		/**
		 * Update pugin database and options
		 */
		if ( ! isset( $sldr_options['plugin_db_version'] ) || $sldr_options['plugin_db_version'] !== $plugin_db_version ) {
			sldr_create_table();
			$sldr_options['plugin_db_version'] = $plugin_db_version;
			$update_option                     = true;
		}

		if ( isset( $update_option ) ) {
			update_option( 'sldr_options', $sldr_options );
		}
	}
}

if ( ! function_exists( 'sldr_get_options_default' ) ) {
	/**
	 * Get Plugin default options
	 */
	function sldr_get_options_default() {
		global $sldr_plugin_info;

		$option_defaults = array(
			/* internal general */
			'plugin_option_version'   => $sldr_plugin_info['Version'],
			'first_install'           => strtotime( 'now' ),
			'suggest_feature_banner'  => 1,
			'display_settings_notice' => 1,
			/* general */
			'loop'                    => false,
			'nav'                     => false,
			'dots'                    => false,
			'items'                   => '1',
			'height_type'             => 'auto',
			'height'                  => '300',
			'autoplay'                => false,
			'autoplay_timeout'        => '2000',
			'autoplay_hover_pause'    => false,
			'lazy_load'               => false,
			'auto_height'             => '1',
			'order_by'                => 'meta_value_num',
			'order'                   => 'ASC',
			'bws_booking'             => 0,
			'display_in_front_page'   => 0,
		);
		return $option_defaults;
	}
}

if ( ! function_exists( 'sldr_plugin_activate' ) ) {
	/**
	 * Function for plugin activation.
	 */
	function sldr_plugin_activate() {
		global $wpdb;
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			$old_blog = $wpdb->blogid;
			/* Get all blog ids */
			$blogids = $wpdb->get_col( "SELECT `blog_id` FROM $wpdb->blogs" );
			foreach ( $blogids as $blog_id ) {
				switch_to_blog( $blog_id );
				register_uninstall_hook( __FILE__, 'sldr_plugin_uninstall' );
				restore_current_blog();
			}
			switch_to_blog( $old_blog );
			return;
		}

		register_uninstall_hook( __FILE__, 'sldr_plugin_uninstall' );
	}
}

if ( ! function_exists( 'sldr_create_table' ) ) {
	/**
	 * Function to create a new tables in database.
	 */
	function sldr_create_table() {
		global $wpdb;

		/* Require db Delta */
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		/* Create table for sliders */
		$sql = 'CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'sldr_slider` (
			`slider_id` INT NOT NULL AUTO_INCREMENT,
			`datetime` DATE NOT NULL,
			`title` VARCHAR( 255 ) NOT NULL,
			`settings` BLOB NOT NULL,
			PRIMARY KEY (slider_id)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
		/* Call dbDelta */
		dbDelta( $sql );

		/* Create table for sliders category */
		$sql = 'CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'sldr_category` (
			`category_id` INT NOT NULL AUTO_INCREMENT,
			`title` VARCHAR( 255 ) NOT NULL,
			PRIMARY KEY (category_id)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
		/* Call dbDelta */
		dbDelta( $sql );

		/* Create table for slides */
		$sql = 'CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'sldr_slide` (
			`slide_id` INT NOT NULL AUTO_INCREMENT,
			`attachment_id` INT NOT NULL,
			`title` VARCHAR( 255 ) NOT NULL,
			`description` VARCHAR( 255 ) NOT NULL,
			`url` VARCHAR( 255 ) NOT NULL,
			`button` VARCHAR( 255 ) NOT NULL,
			`order` INT NOT NULL,
			PRIMARY KEY (slide_id)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
		/* Call dbDelta */
		dbDelta( $sql );

		/* create table for sliders meta */
		$sql = 'CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'sldr_relation` (
			`id` INT NOT NULL AUTO_INCREMENT,
			`slider_id` INT  NOT NULL,
			`attachment_id` INT,
			`category_id` INT,
			PRIMARY KEY (id)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
		/* Call dbDelta */
		dbDelta( $sql );
	}
}

if ( ! function_exists( 'sldr_edit_attachment_join' ) ) {
	/**
	 * Function create filter for custom slides sorting
	 *
	 * @param string $join_paged_statement Join string.
	 */
	function sldr_edit_attachment_join( $join_paged_statement ) {
		global $wpdb;

		$join_paged_statement = 'LEFT JOIN `' . $wpdb->prefix . 'sldr_slide` ON `' . $wpdb->prefix . 'sldr_slide`.`attachment_id` = `' . $wpdb->prefix . 'posts`.`ID`';

		return $join_paged_statement;
	}
}

if ( ! function_exists( 'sldr_edit_attachment_orderby' ) ) {
	/**
	 * Function create filter for custom slides sorting
	 *
	 * @param string $orderby_statement Order By string.
	 */
	function sldr_edit_attachment_orderby( $orderby_statement ) {
		global $wpdb;
		$orderby_statement = '( `' . $wpdb->prefix . 'sldr_slide`.order ) ASC';
		return $orderby_statement;
	}
}

if ( ! function_exists( 'sldr_table_page_render' ) ) {
	/**
	 * Function will render slider page.
	 */
	function sldr_table_page_render() {
		if ( ! class_exists( 'Sldr_List_Table' ) ) {
			require_once __DIR__ . '/includes/class-sldr-list-table.php';
		}
		$slider_table = new Sldr_List_Table();
		$slider_table->prepare_items();

		$message = '';
		?>
		<form class="bws_form sldr_form" method="POST" action="admin.php?page=slider.php">
			<?php
			if ( 'delete' === $slider_table->current_action() ) {
				$message = __( 'Slider deleted.', 'slider-bws' );
			}
			?>
			<div class="wrap">
				<?php
				printf(
					'<h1> %s <a class="add-new-h2" href="%s" >%s</a></h1>',
					esc_html__( 'Sliders', 'slider-bws' ),
					esc_url( admin_url( 'admin.php?page=slider-new.php' ) ),
					esc_html__( 'Add New', 'slider-bws' )
				);
				?>
				<div id="sldr_settings_message" class="notice is-dismissible updated below-h2 fade" 
				<?php
				if ( '' === $message ) {
					echo 'style="display:none"';}
				?>
				>
					<p><strong><?php echo esc_html( $message ); ?></strong></p>
				</div>
				<?php
				$slider_table->search_box( __( 'Search Sliders', 'slider-bws' ), 'sldr_slider' );
				$slider_table->display();
				?>
			</div>
			<input type="hidden" name="sldr_form_submit" value="submit" />
			<?php wp_nonce_field( plugin_basename( __FILE__ ), 'sldr_nonce_form_name' ); ?>
		</form>
		<?php
	}
}

if ( ! function_exists( 'sldr_form_handler' ) ) {
	/**
	 * Handler form for slide
	 */
	function sldr_form_handler() {
		global $wpdb, $sldr_id;

		$plugin_basename = plugin_basename( __FILE__ );
		/* Get slider ID. */
		$sldr_id = ! empty( $_REQUEST['sldr_id'] ) ? intval( $_REQUEST['sldr_id'] ) : '';

		/* Handler for slider form. Save by click on "publish" button */
		if ( isset( $_POST['sldr_publish'] ) && check_admin_referer( $plugin_basename, 'sldr_nonce_form_name' ) ) {
			/* Handler for settings */
			$sldr_request_options = array();
			/* Set loop in slideshow. */
			$sldr_request_options['loop'] = ( isset( $_POST['sldr_loop'] ) ) ? true : false;
			/* Display navigation button */
			$sldr_request_options['nav'] = ( isset( $_POST['sldr_nav'] ) ) ? true : false;
			/* Display navigation Dots */
			$sldr_request_options['dots'] = ( isset( $_POST['sldr_dots'] ) ) ? true : false;
			/* Set items per page */
			$sldr_request_options['items'] = ( ! empty( $_POST['sldr_items'] ) ) ? intval( $_POST['sldr_items'] ) : '1';
			/* Set height type */
			$sldr_request_options['height_type'] = ( ! empty( $_POST['sldr_height_type'] ) ) ? sanitize_text_field( wp_unslash( $_POST['sldr_height_type'] ) ) : 'auto';
			/* Set height value */
			$sldr_request_options['height'] = ( ! empty( $_POST['sldr_height'] ) ) ? intval( $_POST['sldr_height'] ) : '300';
			/* Set autoplay */
			$sldr_request_options['autoplay'] = ( isset( $_POST['sldr_autoplay'] ) ) ? true : false;
			/* Autoplay timeout */
			$sldr_request_options['autoplay_timeout'] = ( ! empty( $_POST['sldr_autoplay_timeout'] ) ) ? intval( $_POST['sldr_autoplay_timeout'] ) * 1000 : '2000';
			/* Autoplay hover pause */
			$sldr_request_options['autoplay_hover_pause'] = ( isset( $_POST['sldr_autoplay_hover_pause'] ) ) ? true : false;

			$sldr_request_options = apply_filters( 'sldr_request_options', $sldr_request_options );
			$sldr_options         = serialize( $sldr_request_options );

			/* Slider title */
			$slider_title = isset( $_POST['sldr_slider_title'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['sldr_slider_title'] ) ) ) : '';
			if ( ! empty( $sldr_id ) ) {
				$wpdb->update(
					$wpdb->prefix . 'sldr_slider',
					array(
						'title'    => $slider_title,
						'settings' => $sldr_options,
					),
					array( 'slider_id' => $sldr_id )
				);
			} else {
				$wpdb->insert(
					$wpdb->prefix . 'sldr_slider',
					array(
						'title'    => $slider_title,
						'datetime' => gmdate( 'Y-m-d' ),
						'settings' => $sldr_options,
					)
				);
				/* Get slider ID for new slider. */
				$sldr_id = $wpdb->insert_id;
			}

			/**
			 * Slider category from category metabox.
			 * Get all categories list from hidden input
			 */
			$slider_all_category_ids = isset( $_POST['sldr_category_unchecked_id'] ) ? ( is_array( $_POST['sldr_category_unchecked_id'] ) ? array_map( 'absint', $_POST['sldr_category_unchecked_id'] ) : absint( $_POST['sldr_category_unchecked_id'] ) ) : '';

			/* Save slider category for current slider. */
			if ( isset( $_POST['sldr_category_id'] ) ) {
				$slider_category_id = is_array( $_POST['sldr_category_id'] ) ? array_map( 'absint', $_POST['sldr_category_id'] ) : absint( $_POST['sldr_category_id'] );
				/* Add selected category to DB. */
				foreach ( (array) $slider_all_category_ids as $slider_all_category_id ) {
					if ( ! in_array( $slider_all_category_id, $slider_category_id ) ) {
						$wpdb->delete(
							$wpdb->prefix . 'sldr_relation',
							array(
								'category_id' => $slider_all_category_id,
								'slider_id'   => $sldr_id,
							)
						);
					} else {
						$wpdb->query(
							$wpdb->prepare(
								'INSERT INTO `' . $wpdb->prefix . 'sldr_relation` ( `slider_id`, `category_id` )
							SELECT * FROM (SELECT %d as `slider_id` , %d as `category_id` ) AS tmp
							WHERE NOT EXISTS (
								SELECT `category_id` FROM `' . $wpdb->prefix . 'sldr_relation` WHERE slider_id = %d AND category_id = %d
							) LIMIT 1',
								$sldr_id,
								$slider_all_category_id,
								$sldr_id,
								$slider_all_category_id
							)
						);
					}
				}
				/* Delete selected category from DB. */
			} elseif ( ! empty( $slider_all_category_ids ) ) {
				foreach ( (array) $slider_all_category_ids as $slider_all_category_id ) {
					$wpdb->delete(
						$wpdb->prefix . 'sldr_relation',
						array(
							'category_id' => $slider_all_category_id,
							'slider_id'   => $sldr_id,
						)
					);
				}
			}

			/* Save adding image to DB. */
			if ( isset( $_POST['sldr_new_image'] ) ) {
				$slider_new_attachments = $_POST['sldr_new_image'];

				$slider_new_attachments = array_map( 'absint', $slider_new_attachments );
				/**
				 * Get added images.
				 * Add new image to slider images array
				 */
				foreach ( $slider_new_attachments as $slider_new_attachment ) {

					/* If slide already exist in DB, don't insert slide */
					$wpdb->query(
						$wpdb->prepare(
							'INSERT INTO `' . $wpdb->prefix . 'sldr_relation` ( `slider_id`, `attachment_id` )
						SELECT * FROM ( SELECT %d as `slider_id` , %d as `attachment_id` ) AS tmp
							WHERE NOT EXISTS (
							SELECT `attachment_id` FROM `' . $wpdb->prefix . 'sldr_relation` WHERE slider_id = %d AND attachment_id = %d
							) LIMIT 1',
							$sldr_id,
							$slider_new_attachment,
							$sldr_id,
							$slider_new_attachment
						)
					);
				}
			}

			/* Slide title\description\URL */
			if ( ! empty( $_POST['sldr_image_title'] ) ) {
				foreach ( $_POST['sldr_image_title'] as $slider_attachment_id => $slider_attachment_title ) {
					/* Prepare data to save in DB */
					$slider_attachment_title       = trim( sanitize_text_field( wp_unslash( $slider_attachment_title ) ) );
					$slider_attachment_description = isset( $_POST['sldr_image_description'][ $slider_attachment_id ] ) ? trim( sanitize_text_field( wp_unslash( $_POST['sldr_image_description'][ $slider_attachment_id ] ) ) ) : '';
					$slider_attachment_url         = isset( $_POST['sldr_link_url'][ $slider_attachment_id ] ) ? trim( sanitize_text_field( wp_unslash( $_POST['sldr_link_url'][ $slider_attachment_id ] ) ) ) : '';
					if ( filter_var( $slider_attachment_url, FILTER_VALIDATE_URL ) === false ) {
						$slider_attachment_url = '';
					}
								$slider_attachment_button_text = isset( $_POST['sldr_button_text'][ $slider_attachment_id ] ) ? trim( sanitize_text_field( wp_unslash( $_POST['sldr_button_text'][ $slider_attachment_id ] ) ) ) : '';
								/* Check: if data exist in DB, then update the data. If not exist, then insert the data */
								$slide_id = $wpdb->get_var( $wpdb->prepare( 'SELECT `slide_id` FROM `' . $wpdb->prefix . 'sldr_slide` WHERE `attachment_id` = %d', $slider_attachment_id ) );

					if ( ! empty( $slide_id ) ) {
						$wpdb->update(
							$wpdb->prefix . 'sldr_slide',
							array(
								'title'       => $slider_attachment_title,
								'description' => $slider_attachment_description,
								'url'         => $slider_attachment_url,
								'button'      => $slider_attachment_button_text,
							),
							array( 'attachment_id' => $slider_attachment_id )
						);
					} else {
						$wpdb->insert(
							$wpdb->prefix . 'sldr_slide',
							array(
								'title'         => $slider_attachment_title,
								'description'   => $slider_attachment_description,
								'url'           => $slider_attachment_url,
								'button'        => $slider_attachment_button_text,
								'attachment_id' => $slider_attachment_id,
							)
						);
					}
				}
			}

			/* Slide order */
			if ( isset( $_POST['_sldr_order'] ) ) {
				/* Set counter for slide order */
				$i = 1;
				foreach ( $_POST['_sldr_order'] as $slider_attachment_order_id => $order_id ) {
					$wpdb->update(
						$wpdb->prefix . 'sldr_slide',
						array( 'order' => $i ),
						array( 'attachment_id' => absint( $slider_attachment_order_id ) )
					);
					++$i;
				}
			}
		}

		return $sldr_id;
	}
}

if ( ! function_exists( 'sldr_add_new_render' ) ) {
	/**
	 * Function will render new slider page
	 *
	 * @param array $item Slider item.
	 */
	function sldr_add_new_render( $item ) {
		global $sldr_id, $wpdb, $post;

		$sldr_id         = sldr_form_handler();
		$plugin_basename = plugin_basename( __FILE__ );
		$message         = '';
		$error           = '';

		/* Add admin notice to slider menu. */
		if ( isset( $_REQUEST['sldr_publish'] ) && check_admin_referer( $plugin_basename, 'sldr_nonce_form_name' ) ) {
			$message = __( 'Slider updated.', 'slider-bws' );
		}
		?>
		<div class="wrap sldr_wrap">
			<?php
			/* Add page name and add new button to page */
			if ( ! empty( $sldr_id ) ) {
				echo '<h1>' . esc_html__( 'Edit Slider', 'slider-bws' ) . '<a class="page-title-action" href="' . esc_url( admin_url( 'admin.php?page=slider-new.php' ) ) . '">' . esc_html__( 'Add New', 'slider-bws' ) . '</a></h1>';
			} else {
				echo '<h1>' . esc_html__( 'Add New Slider', 'slider-bws' ) . '</h1>';
			}
			?>
			<form class="bws_form" method="POST" action="admin.php?page=slider-new.php&amp;sldr_id=<?php echo esc_attr( $sldr_id ); ?>">
				<div id="poststuff">
					<div id="post-body" class="metabox-holder columns-<?php echo 1 === absint( get_current_screen()->get_columns() ) ? '1' : '2'; ?>">
						<div id="post-body-content">
							<div id="sldr_settings_message" class="notice is-dismissible updated below-h2 fade" 
							<?php
							if ( '' === $message ) {
								echo 'style="display:none"';}
							?>
							>
								<p><strong><?php echo esc_html( $message ); ?></strong></p>
							</div>
							<div id="titlediv">
								<div id="titlewrap">
									<input name="sldr_slider_title" size="30" value="<?php echo esc_html( $wpdb->get_var( $wpdb->prepare( 'SELECT `title` FROM `' . $wpdb->prefix . 'sldr_slider` WHERE `slider_id` = %d', $sldr_id ) ) ); ?>" id="title" spellcheck="true" autocomplete="off" type="text" placeholder="<?php esc_html_e( 'Enter title here', 'slider-bws' ); ?>" />
								</div>
								<div class="inside"></div>
							</div>
							<?php
							if ( ! class_exists( 'Bws_Settings_Tabs' ) ) {
								require_once __DIR__ . '/bws_menu/class-bws-settings.php';}
							require_once __DIR__ . '/includes/class-sldr-settings.php';
							$page = new Sldr_Settings_Tabs( plugin_basename( __FILE__ ) );
							$page->display_tabs();
							?>
						</div><!-- end .post-body-content -->
						<div id="postbox-container-1" class="postbox-container">
							<?php
							/* Used to save closed meta boxes and their order */
							wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
							wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
							/* Add metaboxes to media list page */
							add_meta_box( 'sldr_submit', __( 'Publish', 'slider-bws' ), 'sldr_submit_metabox', 'sldr_metabox', 'side', 'high' );
							add_meta_box( 'sldr_category', __( 'Slider Categories', 'slider-bws' ), 'sldr_category_metabox', 'sldr_metabox', 'side', 'default' );
							if ( ! empty( $_REQUEST['sldr_id'] ) ) {
								add_meta_box( 'sldr_shortcode', __( 'Slider Shortcode', 'slider-bws' ), 'sldr_shortcode_metabox', 'sldr_metabox', 'side', 'default' );
							}
							do_meta_boxes( 'sldr_metabox', 'side', null );
							?>
						</div>
						<div class="clear"></div>
					</div><!-- end .post-body -->
				</div><!-- end .poststuff -->
			</form>
		</div><!-- end wrap -->
		<?php
	}
}

if ( ! function_exists( 'sldr_categories_render' ) ) {
	/**
	 * Function will render slider category page.
	 */
	function sldr_categories_render() {
		global $wpdb;

		if ( ! class_exists( 'Sldr_Category_List_Table' ) ) {
			require_once __DIR__ . '/includes/class-sldr-category-list-table.php';
		}

		$plugin_basename = plugin_basename( __FILE__ );
		$message         = '';
		$id              = isset( $_REQUEST['sldr_category_id'] ) ? intval( $_REQUEST['sldr_category_id'] ) : '';

		/* Message for deleted categories */
		if ( isset( $_REQUEST['action'] ) && 'delete' === sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) ) {
			$message = __( 'Category deleted.', 'slider-bws' );
		}

		/* Handler for category form */
		if ( isset( $_POST['sldr_category_submit'] ) && check_admin_referer( $plugin_basename, 'sldr_nonce_category_name' ) ) {
			if ( isset( $_POST['sldr_category_title'] ) ) {
				/* Prepare data to insert in DB */
				$slider_category_title = sanitize_text_field( wp_unslash( $_POST['sldr_category_title'] ) );

				/* Check for new category. If category not isset - insert new, else update */
				if ( isset( $id ) ) {
					$slider_get_category_id = $wpdb->get_var( $wpdb->prepare( 'SELECT `category_id` FROM `' . $wpdb->prefix . 'sldr_category` WHERE `category_id` = %d', $id ) );
				}
				/* Send data to DB */
				if ( ! empty( $slider_get_category_id ) ) {
					$wpdb->update( $wpdb->prefix . 'sldr_category', array( 'title' => $slider_category_title ), array( 'category_id' => $id ) );
				} else {
					$wpdb->insert( $wpdb->prefix . 'sldr_category', array( 'title' => $slider_category_title ) );
				}
			}
			$message = __( 'Category updated.', 'slider-bws' );
		}
		?>
		<div class="wrap">
			<?php
			/* If select "Edit category", render edit page with form for slider category */
			if ( isset( $_GET['action'] ) && 'edit' === $_GET['action'] ) {
				$slider_category_title_value = $wpdb->get_var( $wpdb->prepare( 'SELECT `title` FROM `' . $wpdb->prefix . 'sldr_category` WHERE `category_id` = %d', $id ) );
				?>
				<h1 style="line-height: normal;"><?php esc_html_e( 'Edit Category', 'slider-bws' ); ?></h1>
				<div id="sldr_settings_message" class="notice is-dismissible updated below-h2 fade" 
				<?php
				if ( '' === $message ) {
					echo 'style="display:none"';}
				?>
				>
					<p><strong><?php echo esc_html( $message ); ?></strong></p>
				</div>
				<form method="post" action="admin.php?page=slider-categories.php&amp;sldr_category_id=<?php echo esc_attr( $id ); ?>&amp;action=edit">
					<table class="form-table">
						<tr class="form-field form-required term-name-wrap">
							<th><label for="tag-name"><?php esc_html_e( 'Name', 'slider-bws' ); ?></label></th>
							<td>
								<input name="sldr_category_title" id="tag-name" required value="<?php echo esc_html( $slider_category_title_value ); ?>" size="40" type="text">
							</td>
						</tr>
					</table>
					<p class="submit">
						<input type="submit" class="button-primary" value="<?php esc_html_e( 'Update', 'slider-bws' ); ?>" />
						<input type="hidden" name="sldr_category_submit" value="submit" />
						<?php wp_nonce_field( $plugin_basename, 'sldr_nonce_category_name' ); ?>
					</p>
				</form>
			<?php } else { ?>
				<h1><?php esc_html_e( 'Slider Categories', 'slider-bws' ); ?></h1>
				<div id="sldr_settings_message" class="notice is-dismissible updated below-h2 fade" 
				<?php
				if ( '' === $message ) {
					echo 'style="display:none"';}
				?>
				>
					<p><strong><?php echo esc_html( $message ); ?></strong></p>
				</div>
				<div id="col-container" class="wp-clearfix">
					<div id="col-right">
						<form id="sldr_categories_table" method="POST">
							<?php
							/* Display category table */
							$slider_categories_table = new Sldr_Category_List_Table();
							$slider_categories_table->prepare_items();
							$slider_categories_table->search_box( __( 'Search Slider Categories', 'slider-bws' ), 'sldr_categories' );
							$slider_categories_table->display();
							?>
						</form>
					</div><!-- #col-right -->
					<div id="col-left">
						<div class="col-wrap">
							<div class="form-wrap">
								<h2><?php esc_html_e( 'Add New Slider Category', 'slider-bws' ); ?></h2>
								<form id="addtag" method="POST" action="admin.php?page=slider-categories.php">
									<div class="form-field form-required term-name-wrap">
										<label for="tag-name"><?php esc_html_e( 'Name', 'slider-bws' ); ?></label>
										<input name="sldr_category_title" id="tag-name" value="" size="40" required type="text">
									</div>
									<p class="submit">
										<input type="submit" class="button-primary" value="<?php esc_html_e( 'Add New Category', 'slider-bws' ); ?>" />
										<input type="hidden" name="sldr_category_submit" value="submit" />
										<?php wp_nonce_field( $plugin_basename, 'sldr_nonce_category_name' ); ?>
									</p>
								</form>
							</div><!-- .form-wrap -->
						</div><!-- #col-wrap -->
					</div><!-- #col-left -->
				</div><!-- #col-container -->
			<?php } ?>
		</div><!-- .wrap -->
		<?php
	}
}

if ( ! function_exists( 'sldr_shortcode_metabox' ) ) {
	/**
	 * This function will render metabox for shortcode
	 *
	 * @param array  $item Slider Item.
	 * @param string $obj  Object.
	 * @param string $box  Box.
	 */
	function sldr_shortcode_metabox( $item, $obj = '', $box = '' ) {
		if ( ! empty( $_REQUEST['sldr_id'] ) ) {
			$sldr_id = absint( $_REQUEST['sldr_id'] );
		}
		?>
		<div>
			<?php
			esc_html_e( 'Add a slider to your posts, pages, custom post types or widgets by using the following shortcode:', 'slider-bws' );
			bws_shortcode_output( '[print_sldr id=' . $sldr_id . ']' );
			?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'sldr_submit_metabox' ) ) {
	/**
	 * This function will render submit metabox
	 *
	 * @param array $item Slider Item.
	 */
	function sldr_submit_metabox( $item ) {
		?>
		<div class="sldr_submit">
			<div id="publishing-action">
				<span class="spinner"></span>
				<input id="publish" class="button button-primary button-large" name="sldr_publish" value="<?php echo esc_html( isset( $_GET['sldr_id'] ) ? __( 'Update', 'slider-bws' ) : __( 'Publish', 'slider-bws' ) ); ?>" type="submit" />
				<input type="hidden" name="sldr_form_submit" value="submit" />
				<?php wp_nonce_field( plugin_basename( __FILE__ ), 'sldr_nonce_form_name' ); ?>
			</div>
			<div class="clear"></div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'sldr_category_metabox' ) ) {
	/**
	 * This function will render category metabox
	 *
	 * @param array $item Slider Item.
	 */
	function sldr_category_metabox( $item ) {
		global $wpdb, $sldr_id;

		if ( ! empty( $_REQUEST['sldr_id'] ) ) {
			$sldr_id = isset( $_REQUEST['sldr_id'] ) ? intval( $_REQUEST['sldr_id'] ) : '';
		}
		/* Take all category ID from category table */
		$slider_categories = $wpdb->get_results( 'SELECT `category_id`, `title` FROM `' . $wpdb->prefix . 'sldr_category`', ARRAY_A );
		?>
		<div class="categorydiv">
			<ul id="sldr_categories-all" class="categorychecklist form-no-clear">
				<?php
				/* Take category for current slider */
				if ( ! empty( $slider_categories ) ) {
					/* Take current category ID from DB */
					if ( ! empty( $sldr_id ) ) {
						$slider_current_categories = $wpdb->get_col( $wpdb->prepare( 'SELECT `category_id` FROM `' . $wpdb->prefix . 'sldr_relation` WHERE `slider_id` = %d', $sldr_id ) );
					}
					/* Display all categories in metabox */
					foreach ( $slider_categories as $slider_category ) {
						?>
						<li>
							<input type="checkbox" name="sldr_category_id[]" value="<?php echo esc_attr( $slider_category['category_id'] ); ?>"
								<?php
								if ( isset( $slider_current_categories ) && in_array( $slider_category['category_id'], $slider_current_categories ) ) {
									echo 'checked="checked"';
								}
								?>
							/>
							<input id="sldr_hidden_checbox_<?php echo esc_attr( $slider_category['category_id'] ); ?>" type="hidden" value="<?php echo esc_attr( $slider_category['category_id'] ); ?>" name="sldr_category_unchecked_id[]">
							<?php echo esc_html( $slider_category['title'] ); ?>
						</li>
						<?php
					}
				} else {
					?>
					<i><?php esc_html_e( 'No Slider Category Set', 'slider-bws' ); ?></i>
				<?php } ?>
			<ul>
		</div>
		<div id="category-adder" class="wp-hidden-children">
			<a id="category-add-toggle" href="admin.php?page=slider-categories.php" class="taxonomy-add-new">+ <?php esc_html_e( 'Add New Category', 'slider-bws' ); ?></a>
		</div>
		<?php
	}
}

if ( ! function_exists( 'sldr_settings_page' ) ) {
	/**
	 * Slider general settings page.
	 */
	function sldr_settings_page() {
		if ( ! class_exists( 'Bws_Settings_Tabs' ) ) {
			require_once __DIR__ . '/bws_menu/class-bws-settings.php';
		}
		require_once __DIR__ . '/includes/class-sldr-settings.php';
		$page = new Sldr_Settings_Tabs( plugin_basename( __FILE__ ) );
		if ( method_exists( $page, 'add_request_feature' ) ) {
			$page->add_request_feature();
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Slider Global Settings', 'slider-bws' ); ?></h1>
			<noscript>
				<div class="error below-h2">
					<p><strong><?php esc_html_e( 'WARNING', 'slider-bws' ); ?>:</strong> <?php esc_html_e( 'The plugin works correctly only if JavaScript is enabled.', 'slider-bws' ); ?>
					</p>
				</div>
			</noscript>
			<?php $page->display_content(); ?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'sldr_print_media_notice' ) ) {
	/**
	 * Add place for notice in media upoader for slider
	 *
	 * See wp_print_media_templates() in "wp-includes/media-template.php"
	 */
	function sldr_print_media_notice() {
		global $sldr_plugin_info;
		if ( isset( $_GET['page'] ) && 'slider-new.php' === $_GET['page'] ) {
			if ( ! $sldr_plugin_info ) {
				if ( ! function_exists( 'get_plugin_data' ) ) {
					require_once ABSPATH . 'wp-admin/includes/plugin.php';
				}

				$sldr_plugin_info = get_plugin_data( __FILE__ );
			}

			$image_info = '<# sldr_notice_view( data.id ); #><div id="sldr_media_notice" class="upload-errors"></div>';
			$script     = "( function ($) {
					$( '#tmpl-attachment-details' ).html(
						$( '#tmpl-attachment-details' ).html().replace( '<div class=\"attachment-info\"', '" . $image_info . "$&' )
					);
				} )(jQuery);";
			wp_register_script( 'sldr_bws_image_info', '', array( 'jquery' ), $sldr_plugin_info['Version'], true );
			wp_enqueue_script( 'sldr_bws_image_info' );
			wp_add_inline_script( 'sldr_bws_image_info', sprintf( $script ) );
		}
	}
}

if ( ! function_exists( 'sldr_media_check_ajax_action' ) ) {
	/**
	 * Add notises in media upoader for slider
	 */
	function sldr_media_check_ajax_action() {
		check_ajax_referer( plugin_basename( __FILE__ ), 'sldr_ajax_nonce_field' );

		if ( isset( $_POST['thumbnail_id'] ) ) {
			$thumbnail_id = intval( $_POST['thumbnail_id'] );
			/*get information about the selected item */
			$atachment_detail = get_post( $thumbnail_id );
			if ( ! empty( $atachment_detail ) ) {
				if ( ! ( preg_match( '!^image/!', $atachment_detail->post_mime_type ) || preg_match( '!^video/mp4!', $atachment_detail->post_mime_type ) || preg_match( '!^video/ogg!', $atachment_detail->post_mime_type ) || preg_match( '!^video/webm!', $atachment_detail->post_mime_type ) ) ) {
					$notice_attach = "<div class='upload-error'><strong>" . __( 'Warning', 'slider-bws' ) . ': </strong>' . __( 'You can add only images or video (in format: MP4, WebM or Ogg) to the slider.', 'slider-bws' ) . '</div>';
					wp_send_json_success( $notice_attach );
				}
			}
		}
		die();
	}
}

if ( ! function_exists( 'sldr_delete_image' ) ) {
	/**
	 * Delete slide by click on "Delete" button.
	 */
	function sldr_delete_image() {
		global $wpdb;
		check_ajax_referer( plugin_basename( __FILE__ ), 'sldr_ajax_nonce_field' );

		$action          = isset( $_POST['action'] ) ? sanitize_text_field( wp_unslash( $_POST['action'] ) ) : '';
		$delete_id_array = isset( $_POST['delete_id_array'] ) ? sanitize_text_field( wp_unslash( $_POST['delete_id_array'] ) ) : '';
		$slider_id       = isset( $_POST['slider_id'] ) ? absint( $_POST['slider_id'] ) : '';

		if ( 'sldr_delete_image' === $action && ! empty( $delete_id_array ) && ! empty( $slider_id ) ) {
			$delete_ids = explode( ',', trim( $delete_id_array, ',' ) );

			foreach ( $delete_ids as $delete_id ) {
				$wpdb->delete(
					$wpdb->prefix . 'sldr_relation',
					array(
						'slider_id'     => $slider_id,
						'attachment_id' => absint( $delete_id ),
					)
				);
			}
			echo 'updated';
		}
		die();
	}
}


if ( ! function_exists( 'sldr_add_from_media' ) ) {
	/**
	 * Add new media in slider from AJAX.
	 */
	function sldr_add_from_media() {
		check_ajax_referer( plugin_basename( __FILE__ ), 'sldr_ajax_add_nonce' );

		$action = isset( $_POST['action'] ) ? sanitize_text_field( wp_unslash( $_POST['action'] ) ) : '';
		$add_id = isset( $_POST['add_id'] ) ? intval( $_POST['add_id'] ) : '';

		if ( 'sldr_add_from_media' === $action && ! empty( $add_id ) ) {
			if ( ! class_exists( 'Sldr_Media_Table' ) ) {
				require_once __DIR__ . '/includes/class-sldr-media-table.php';
			}
			$post = get_post( $add_id );
			if ( ! empty( $post ) ) {
				if ( preg_match( '!^image/!', $post->post_mime_type ) || preg_match( '!^video/mp4!', $post->post_mime_type ) || preg_match( '!^video/ogg!', $post->post_mime_type ) || preg_match( '!^video/webm!', $post->post_mime_type ) ) {
					$GLOBALS['hook_suffix'] = 'slider';
					$wp_slider_media_table  = new Sldr_Media_Table();
					$wp_slider_media_table->prepare_items();
					$wp_slider_media_table->single_row( $post );
				}
			}
		}
		die();
	}
}

if ( ! function_exists( 'sldr_shortcode_button_content' ) ) {
	/**
	 * Add slider shortcode to BWS plugin shortcode menu.
	 *
	 * @param array  $attr    Shortcode attributes.
	 * @param string $content Shortcode content.
	 */
	function sldr_shortcode_button_content( $attr = array(), $content = '' ) {
		global $wpdb, $sldr_plugin_info;
		if ( ! $sldr_plugin_info ) {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$sldr_plugin_info = get_plugin_data( __FILE__ );
		}
		?>
		<div id="sldr" style="display:none;">
			<fieldset>
				<label for="sldr_shortcode_list">
					<?php
					$slider_id_array = $wpdb->get_col( 'SELECT `slider_id` FROM `' . $wpdb->prefix . 'sldr_slider`' );
					if ( ! empty( $slider_id_array ) ) {
						?>
						<input type="radio" name="sldr_type" class="sldr_radio_shortcode_list" checked="checked">
						<span class="title"><?php esc_html_e( 'slider', 'slider-bws' ); ?></span>
						<select name="sldr_list" id="sldr_shortcode_list" style="max-width: 350px;">
							<?php
							foreach ( $slider_id_array as $slider_id ) {
								/* Get slider title from DB */
								$slider_title = $wpdb->get_var( $wpdb->prepare( 'SELECT `title` FROM `' . $wpdb->prefix . 'sldr_slider` WHERE `slider_id` = %d', $slider_id ) );
								/* If slider don't have title, display "no title" */
								if ( empty( $slider_title ) ) {
									$slider_title = '(' . __( 'no title', 'slider-bws' ) . ')';
								}
								/* Get slider date from DB */
								$slider_date = $wpdb->get_var( $wpdb->prepare( 'SELECT `datetime` FROM `' . $wpdb->prefix . 'sldr_slider` WHERE `slider_id` = %d', $slider_id ) );
								?>
								<option value="<?php echo esc_attr( $slider_id ); ?>"><?php echo esc_html( $slider_title ); ?>(<?php echo esc_html( $slider_date ); ?>)</option>
							<?php } ?>
						</select>
					<?php } else { ?>
						<span class="title"><?php esc_html_e( 'Sorry, no slider found.', 'slider-bws' ); ?></span>
					<?php } ?>
				</label>
				<br/>
				<label for="sldr_category_shortcode_list">
					<?php
					/* Get category ID from DB */
					$slider_category_id_array = $wpdb->get_col( 'SELECT `category_id` FROM `' . $wpdb->prefix . 'sldr_category`' );
					if ( ! empty( $slider_category_id_array ) ) {
						?>
						<input type="radio" name="sldr_type" class="sldr_radio_category_list">
						<span class="title"><?php esc_html_e( 'slider category', 'slider-bws' ); ?></span>
						<select name="sldr_category_list" id="sldr_category_shortcode_list" style="max-width: 350px;">
							<?php
							foreach ( $slider_category_id_array as $slider_category_id ) {
								/* Get slider category title from DB */
								$slider_category_title = $wpdb->get_var( $wpdb->prepare( 'SELECT `title` FROM `' . $wpdb->prefix . 'sldr_category` WHERE `category_id` = %d', $slider_category_id ) );
								?>
								<option value="<?php echo esc_attr( $slider_category_id ); ?>"><?php echo esc_html( $slider_category_title ); ?></option>
								<?php
								unset( $slider_category_id );
							} /* end foreach */
							?>
						</select>
					<?php } else { ?>
						<span class="title"><?php esc_html_e( 'Sorry, no sliders categories found.', 'slider-bws' ); ?></span>
					<?php } ?>
				</label>
			</fieldset>
			<?php
			foreach ( $slider_id_array as $slider_id ) {
				echo '<input class="bws_default_shortcode" type="hidden" name="default" value="[print_sldr id=' . esc_attr( $slider_id ) . ']" />';
			}

			$script = "function sldr_shortcode_init() {
				( function( $ ) {
					$( '.mce-reset #sldr_shortcode_list, .mce-reset #sldr_display_short, .mce-reset .sldr_radio_shortcode_list' ).on( 'click', function() {
						var sldr_list = $( '.mce-reset #sldr_shortcode_list option:selected' ).val();
						var shortcode = '[print_sldr id=' + sldr_list + ']';
						$( '.mce-reset #bws_shortcode_display' ).text( shortcode );
					});

					$( '.mce-reset #sldr_category_shortcode_list, .mce-reset .sldr_radio_category_list' ).on( 'click', function() {
						var sldr_category_list = $( '.mce-reset #sldr_category_shortcode_list option:selected' ).val();
						var shortcode = '[print_sldr cat_id=' + sldr_category_list + ']';
						$( '.mce-reset #bws_shortcode_display' ).text( shortcode );
					});

					$( '[name=\"sldr_type\"]' ).on( 'click', function() {
						$( this ).parent().find( 'select' ).focus();
					} );

					$( '#sldr_shortcode_list, #sldr_category_shortcode_list' ).on( 'focus', function() {
						$( this ).parent().find( '[type=\"radio\"]' ).attr( 'checked', true );
					} );
				} )(jQuery);
			}";
			wp_register_script( 'sldr_bws_shortcode_button', '', array( 'jquery' ), $sldr_plugin_info['Version'], true );
			wp_enqueue_script( 'sldr_bws_shortcode_button' );
			wp_add_inline_script( 'sldr_bws_shortcode_button', sprintf( $script ) );
			?>

			<div class="clear"></div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'sldr_shortcode' ) ) {
	/**
	 * Shortcodes content output function
	 *
	 * @param array $attr Shortcode attributes.
	 */
	function sldr_shortcode( $attr ) {
		global $wpdb, $sldr_options, $sldr_plugin_info;

		if ( ! $sldr_plugin_info ) {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$sldr_plugin_info = get_plugin_data( __FILE__ );
		}

		$shortcode_attributes = shortcode_atts(
			array(
				'id'     => '',
				'cat_id' => '',
			),
			$attr
		);
		extract( $shortcode_attributes );

		ob_start();

		if ( empty( $sldr_options ) ) {
			$sldr_options = get_option( 'sldr_options' );
		}

		/**
		 * Get slider ID by categories
		 * Check: if this category exists in DB
		 */
		$slider_category_id = $wpdb->get_var( $wpdb->prepare( 'SELECT `category_id` FROM `' . $wpdb->prefix . 'sldr_category` WHERE `category_id` = %d', $cat_id ) );

		if ( ! empty( $cat_id ) && isset( $slider_category_id ) ) {
			$slider_categories_ids = $wpdb->get_col( $wpdb->prepare( 'SELECT `slider_id` FROM `' . $wpdb->prefix . 'sldr_relation` WHERE `category_id` = %d', $cat_id ) );
		}

		/* Get media ID for slider shortcode */
		if ( ! empty( $id ) ) {
			$slider_attachment_ids = $wpdb->get_col(
				$wpdb->prepare(
					'SELECT `' . $wpdb->prefix . 'sldr_slide`.`attachment_id` 
				FROM `' . $wpdb->prefix . 'sldr_relation` INNER JOIN `' . $wpdb->prefix . 'sldr_slide`
				WHERE `' . $wpdb->prefix . 'sldr_slide`.`attachment_id` = `' . $wpdb->prefix . 'sldr_relation`.`attachment_id`
					AND `' . $wpdb->prefix . 'sldr_relation`.`attachment_id` IS NOT NULL 
					AND `' . $wpdb->prefix . 'sldr_relation`.`slider_id` = %d
				ORDER BY `' . $wpdb->prefix . 'sldr_slide`.`order` ASC',
					$id
				)
			);
		}

		/* Get slider settings */
		$slider_single_setting  = $wpdb->get_var( $wpdb->prepare( 'SELECT `settings` FROM `' . $wpdb->prefix . 'sldr_slider` WHERE `slider_id` = %d', $id ) );
		$slider_single_settings = unserialize( $slider_single_setting );

		/* Wrapper for the Booking search form */
		if ( is_plugin_active( 'car-rental/car-rental.php' ) || is_plugin_active( 'car-rental-pro/car-rental-pro.php' ) ) {
			echo '<div class="sldr_bkng_wrapper">';
		}
			/* If this shortcode with slider ID */
		if ( ! empty( $slider_attachment_ids ) ) {
			$script = "( function($) {
						$( document ).ready( function() {
							var slider_single_settings = '" . wp_json_encode( $slider_single_settings ) . "';
							slider_single_settings 	= JSON.parse( slider_single_settings );

							var slider_options 		= '" . wp_json_encode( $sldr_options ) . "';
							slider_options 			= JSON.parse( slider_options );

							var id = " . wp_json_encode( $id ) . ";

							if ( $( 'body' ).hasClass( 'rtl' ) ) {
								$( '.sldr_carousel_' + id  ).owlCarousel( {
									loop: 				slider_single_settings.loop,
									nav: 				slider_single_settings.nav,
									dots: 				slider_single_settings.dots,
									items: 				slider_single_settings.items,
									smartSpeed: 		450,
									autoplay: 			slider_single_settings.autoplay,
									autoplayTimeout: 	slider_single_settings.autoplay_timeout,
									autoplayHoverPause: slider_single_settings.autoplay_hover_pause,
									center: 			true,
									lazyLoad: 			slider_options.lazy_load,
									autoHeight: 		slider_options.auto_height,
									navText:[
														\"<i class='dashicons dashicons-arrow-left-alt2'></i>\",
														\"<i class='dashicons dashicons-arrow-right-alt2'></i>\"
									],
									rtl: true
								} );
							} else {
								$( '.sldr_carousel_' + id  ).owlCarousel({
									loop: 				slider_single_settings.loop,
									nav: 				slider_single_settings.nav,
									dots: 				slider_single_settings.dots,
									items: 				slider_single_settings.items,
									smartSpeed: 		450,
									autoplay: 			slider_single_settings.autoplay,
									autoplayTimeout: 	slider_single_settings.autoplay_timeout,
									autoplayHoverPause: slider_single_settings.autoplay_hover_pause,
									center: 			true,
									lazyLoad: 			slider_options.lazy_load,
									autoHeight: 		slider_options.auto_height,
									navText:[
														\"<i class='dashicons dashicons-arrow-left-alt2'></i>\",
														\"<i class='dashicons dashicons-arrow-right-alt2'></i>\"
									]
								});
							}
						});
					}) (jQuery);";

			wp_register_script( 'sldr_slider_settings_' . $id, '', array( 'jquery' ), $sldr_plugin_info['Version'], true );
			wp_enqueue_script( 'sldr_slider_settings_' . $id );
			wp_add_inline_script( 'sldr_slider_settings_' . $id, sprintf( $script ) );

			/* Display images and images attributes from slider. */
			echo '<div class="sldr_wrapper"><div class="owl-carousel owl-theme sldr_carousel_' . esc_attr( $id ) . '">';

			foreach ( $slider_attachment_ids as $slider_attachment_id ) {
				/* Get slides properties */
				$slide_attribute = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM `' . $wpdb->prefix . 'sldr_slide` WHERE `attachment_id` = %d', $slider_attachment_id ) );

				/* Slide title */
				$slide_title = $slide_attribute->title;
				/* Slide description */
				$slide_description = $slide_attribute->description;
				/* Slide url */
				$slide_url = $slide_attribute->url;
				/* Slide button text */
				$slide_button_text = $slide_attribute->button;
				/* Get image for media ID */
				$slider_attachment = wp_get_attachment_url( $slider_attachment_id, 'full' );

				$slider_height = '';

				if ( ! empty( $slider_single_settings['height_type'] ) && 'custom' === $slider_single_settings['height_type'] ) {
					$slider_height = 'style="height: ' . $slider_single_settings['height'] . 'px;"';
				}
				echo '<div class="sldr_textoverlay" ' . wp_kses_post( $slider_height ) . '>';

				if ( ! empty( $slide_title ) ) {
					/* Display slides title if exist */
					echo '<h2>' . esc_html( $slide_title ) . '</h2>';
				}
				if ( ! empty( $slide_description ) ) {
					/* Display slides description if exist */
					echo '<p>' . esc_html( $slide_description ) . '</p>';
				}
				if ( ! empty( $slide_button_text ) && ! empty( $slide_url ) ) {
					/* Display slides button if exist */
					echo '<a href="' . esc_url( $slide_url ) . '">' . esc_html( $slide_button_text ) . '</a>';
				}

				if ( wp_attachment_is_image( $slider_attachment_id ) && $sldr_options['lazy_load'] ) {
					/* If attachment is image and lazy load on */
					echo '<img class="owl-lazy" data-src="' . esc_url( $slider_attachment ) . '" />';
				} elseif ( wp_attachment_is_image( $slider_attachment_id ) ) {
					/* If attachment is image */
					echo '<img src="' . esc_url( $slider_attachment ) . '" />';
				} else {
					/* Else attachment is video */
					$fullsize_path = get_attached_file( $slider_attachment_id );
					echo '<video controls><source src="' . esc_url( $slider_attachment ) . '" type="' . esc_attr( mime_content_type( $fullsize_path ) ) . '">Your browser does not support HTML5 video.</video>';
				}

					echo '</div>';
			}
				echo '</div>';

				/* If this shortcode with slider category ID */
		} elseif ( ! empty( $slider_categories_ids ) ) {

			/* Get slider settings */
			$slider_category_setting  = $wpdb->get_var( $wpdb->prepare( 'SELECT `settings` FROM `' . $wpdb->prefix . 'sldr_slider` WHERE `slider_id` = %d', $slider_categories_ids[0] ) ); /* Get options of first slider */
			$slider_category_settings = unserialize( $slider_category_setting );

			$script = "( function( $ ) {
						$( document ).ready( function() {
							var slider_options = '" . wp_json_encode( $sldr_options ) . "';
							slider_options = JSON.parse( slider_options );

							var slider_category_settings = '" . wp_json_encode( $slider_category_settings ) . "';
							slider_category_settings = JSON.parse( slider_category_settings );

							var cat_id = " . wp_json_encode( $cat_id ) . ";

							$( '.sldr_cat_carousel_'+ cat_id ).find( '.owl-item' );

							if ( $( 'body' ).hasClass( 'rtl' ) ) {
								$( '.sldr_cat_carousel_' + cat_id ).owlCarousel( {
									loop: 				slider_category_settings.loop,
									nav: 				slider_category_settings.nav,
									dots: 				slider_category_settings.dots,
									items: 				slider_category_settings.items,
									autoplay: 			slider_category_settings.autoplay,
									autoplayTimeout: 	slider_category_settings.autoplay_timeout,
									autoplayHoverPause: slider_category_settings.autoplay_hover_pause,
									center: 			true,
									smartSpeed:			450,
									lazyLoad:			slider_options.lazy_load,
									autoHeight: 		slider_options.auto_height,
									navText:[
														\"<i class='dashicons dashicons-arrow-left-alt2'></i>\",
														\"<i class='dashicons dashicons-arrow-right-alt2'></i>\"
									],
									rtl: true
								} );
							} else {
								$( '.sldr_cat_carousel_' + cat_id ).owlCarousel( {
									loop: 				slider_category_settings.loop,
									nav: 				slider_category_settings.nav,
									dots: 				slider_category_settings.dots,
									items: 				slider_category_settings.items,
									autoplay: 			slider_category_settings.autoplay,
									autoplayTimeout: 	slider_category_settings.autoplay_timeout,
									autoplayHoverPause: slider_category_settings.autoplay_hover_pause,
									center: 			true,
									smartSpeed:			450,
									lazyLoad:			slider_options.lazy_load,
									autoHeight: 		slider_options.auto_height,
									navText:[
														\"<i class='dashicons dashicons-arrow-left-alt2'></i>\",
														\"<i class='dashicons dashicons-arrow-right-alt2'></i>\"
									]
								} );
							}
						} );
					} ) (jQuery);";

			wp_register_script( 'sldr_slider_settings_category_' . $cat_id, '', array( 'jquery' ), $sldr_plugin_info['Version'], true );
			wp_enqueue_script( 'sldr_slider_settings_category_' . $cat_id );
			wp_add_inline_script( 'sldr_slider_settings_category_' . $cat_id, sprintf( $script ) );

			echo '<div class="sldr_wrapper"><div class="sldr_cat_carousel_' . esc_attr( $cat_id ) . ' owl-carousel owl-theme">';

			foreach ( $slider_categories_ids as $slider_categories_id ) {

				/* Get media ID for slider category shortcode */
				if ( ! empty( $slider_categories_id ) ) {
					$slider_attachment_ids = $wpdb->get_col(
						$wpdb->prepare(
							'SELECT `' . $wpdb->prefix . 'sldr_slide`.`attachment_id` 
							FROM `' . $wpdb->prefix . 'sldr_relation` INNER JOIN `' . $wpdb->prefix . 'sldr_slide`
							WHERE `' . $wpdb->prefix . 'sldr_slide`.`attachment_id` = `' . $wpdb->prefix . 'sldr_relation`.`attachment_id`
								AND `' . $wpdb->prefix . 'sldr_relation`.`attachment_id` IS NOT NULL 
								AND `' . $wpdb->prefix . 'sldr_relation`.`slider_id` = %d
							ORDER BY `' . $wpdb->prefix . 'sldr_slide`.`order` ASC',
							$slider_categories_id
						)
					);
				}

				/* If slider have image */
				if ( ! empty( $slider_attachment_ids ) ) {

					foreach ( $slider_attachment_ids as $slider_attachment_id ) {
						/* Get slides properties */
						$slide_attribute = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM `' . $wpdb->prefix . 'sldr_slide` WHERE `attachment_id` = %d', $slider_attachment_id ) );
						/* Get image for media ID */
						$slider_attachment = wp_get_attachment_url( $slider_attachment_id, 'full' );

						echo '<div class="sldr_textoverlay">';
						if ( ! empty( $slide_attribute->title ) ) {
							/* Display slides title if exist */
							echo '<h2>' . esc_html( $slide_attribute->title ) . '</h2>';
						}
						if ( ! empty( $slide_attribute->description ) ) {
							/* Display slides description if exist */
							echo '<p>' . esc_html( $slide_attribute->description ) . '</p>';
						}
						if ( ! empty( $slide_attribute->button ) && ! empty( $slide_attribute->url ) ) {
							/* Display slides button if exist */
							echo '<a href="' . esc_url( $slide_attribute->url ) . '">' . esc_html( $slide_attribute->button ) . '</a>';
						}
						if ( wp_attachment_is_image( $slider_attachment_id ) && $sldr_options['lazy_load'] ) {
							/* If attachment is image and lazy load on */
							echo '<img class="owl-lazy" data-src="' . esc_url( $slider_attachment ) . '" />';
						} elseif ( wp_attachment_is_image( $slider_attachment_id ) ) {
							/* If attachment is image */
							echo '<img src="' . esc_url( $slider_attachment ) . '" />';
						} else {
							/* Else attachment is video */
							echo '<video src="' . esc_url( $slider_attachment ) . '" controls></video>';
						}
						echo '</div>';
					}
				}
			}
			echo '</div>';
			/* If nothing found. */
		} else {
			echo '<div class="sldr_wrapper"><p class="not_found">' . esc_html__( 'Sorry, nothing found.', 'slider-bws' ) . '</p></div>';
		}
			$settings = ! empty( $slider_single_settings ) ? $slider_single_settings : ( ! empty( $slider_category_setting ) ? $slider_category_setting : false );
			do_action( 'sldr_after_content', $shortcode_attributes, maybe_unserialize( $settings ) );
			echo '</div>';
		if ( is_plugin_active( 'car-rental/car-rental.php' ) || is_plugin_active( 'car-rental-pro/car-rental-pro.php' ) ) {
			echo '</div>';
			/* end of .sldr_bkng_wrapper */
		}

		$slider_output = ob_get_clean();
		return $slider_output;
	}
}

if ( ! function_exists( 'sldr_admin_head' ) ) {
	/**
	 * Add style to dashboard
	 */
	function sldr_admin_head() {
		global $sldr_plugin_info;

		if ( ! $sldr_plugin_info ) {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$sldr_plugin_info = get_plugin_data( __FILE__ );
		}
		wp_enqueue_style( 'sldr_stylesheet_icon', plugins_url( '/css/style-icon.css', __FILE__ ), $sldr_plugin_info['Version'], array() );

		if ( isset( $_GET['page'] ) && 'slider.php' === $_GET['page'] ) {
			wp_enqueue_style( 'sldr_stylesheet', plugins_url( '/css/style.css', __FILE__ ), $sldr_plugin_info['Version'], array() );
		} elseif ( isset( $_GET['page'] ) && 'slider-new.php' === $_GET['page'] ) {
			wp_enqueue_style( 'editor-buttons' );
			wp_enqueue_style( 'sldr_stylesheet', plugins_url( '/css/style.css', __FILE__ ), $sldr_plugin_info['Version'], array() );

			wp_enqueue_script( 'jquery-ui-sortable' );

			wp_enqueue_media();
			add_thickbox();

			wp_enqueue_script( 'sldr_script', plugins_url( 'js/admin-script.js', __FILE__ ), array( 'jquery' ), $sldr_plugin_info['Version'], true );

			/* Plugin script */
			wp_localize_script(
				'sldr_script',
				'sldr_vars',
				array(
					'sldr_nonce'       => wp_create_nonce( plugin_basename( __FILE__ ), 'sldr_ajax_nonce_field' ),
					'sldr_add_nonce'   => wp_create_nonce( plugin_basename( __FILE__ ), 'sldr_ajax_add_nonce' ),
					'warnBulkDelete'   => __( "You are about to remove these items from this slider.\n 'Cancel' to stop, 'OK' to delete.", 'slider-bws' ),
					'warnSingleDelete' => __( "You are about to remove this item from the slider.\n 'Cancel' to stop, 'OK' to delete.", 'slider-bws' ),
					'wp_media_title'   => __( 'Insert Media', 'slider-bws' ),
					'wp_media_button'  => __( 'Insert', 'slider-bws' ),
					'no_items'         => __( 'No images found', 'slider-bws' ),
				)
			);

			bws_enqueue_settings_scripts();
		} elseif ( isset( $_GET['page'] ) && 'slider-settings.php' === $_GET['page'] ) {
			bws_enqueue_settings_scripts();
			bws_plugins_include_codemirror();
		}
	}
}

if ( ! function_exists( 'sldr_register_scripts' ) ) {
	/**
	 * List of JavaScript / CSS files
	 */
	function sldr_register_scripts() {
		global $sldr_plugin_info;
		if ( ! $sldr_plugin_info ) {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$sldr_plugin_info = get_plugin_data( __FILE__ );
		}
		/* Owl carousel style */
		wp_enqueue_style( 'owl.carousel.css', plugins_url( '/css/owl.carousel.css', __FILE__ ), array(), '2.3.4' );
		wp_enqueue_style( 'owl.theme.default.css', plugins_url( '/css/owl.theme.default.css', __FILE__ ), array(), '2.3.4' );
		/* Include dashicons */
		wp_enqueue_style( 'dashicons' );
		/* Plugin style */
		wp_enqueue_style( 'sldr_stylesheet', plugins_url( '/css/frontend_style.css', __FILE__ ), array(), $sldr_plugin_info['Version'] );
		/* Include jquery */
		wp_enqueue_script( 'jquery' );
		/* Slider script */
		wp_enqueue_script( 'owl.carousel.js', plugins_url( '/js/owl.carousel/owl.carousel.js', __FILE__ ), array(), '2.3.4', true );
		wp_enqueue_script( 'owl.animate.js', plugins_url( '/js/owl.carousel/owl.animate.js', __FILE__ ), array(), '2.3.4', true );
		wp_enqueue_script( 'owl.autoheight.js', plugins_url( '/js/owl.carousel/owl.autoheight.js', __FILE__ ), array(), '2.3.4', true );
		wp_enqueue_script( 'owl.autoplay.js', plugins_url( '/js/owl.carousel/owl.autoplay.js', __FILE__ ), array(), '2.3.4', true );
		wp_enqueue_script( 'owl.autorefresh.js', plugins_url( '/js/owl.carousel/owl.autorefresh.js', __FILE__ ), array(), '2.3.4', true );
		wp_enqueue_script( 'owl.hash.js', plugins_url( '/js/owl.carousel/owl.hash.js', __FILE__ ), array(), '2.3.4', true );
		wp_enqueue_script( 'owl.lazyload.js', plugins_url( '/js/owl.carousel/owl.lazyload.js', __FILE__ ), array(), '2.3.4', true );
		wp_enqueue_script( 'owl.navigation.js', plugins_url( '/js/owl.carousel/owl.navigation.js', __FILE__ ), array(), '2.3.4', true );
		wp_enqueue_script( 'owl.support.js', plugins_url( '/js/owl.carousel/owl.support.js', __FILE__ ), array(), '2.3.4', true );
		wp_enqueue_script( 'owl.video.js', plugins_url( '/js/owl.carousel/owl.video.js', __FILE__ ), array(), '2.3.4', true );
		/* Frontend script */
		wp_enqueue_script( 'sldr_front_script', plugins_url( 'js/script.js', __FILE__ ), array(), $sldr_plugin_info['Version'], true );
	}
}

if ( ! function_exists( 'sldr_cat_screen_options' ) ) {
	/**
	 * Function to display table screen options.
	 */
	function sldr_cat_screen_options() {
		sldr_add_tabs();
		$args = array(
			'label'   => __( 'Category per page', 'slider-bws' ),
			'default' => 10,
			'option'  => 'sldr_cat_per_page',
		);
		add_screen_option( 'per_page', $args );
	}
}

if ( ! function_exists( 'sldr_screen_options' ) ) {
	/**
	 * Function for screen options
	 */
	function sldr_screen_options() {
		sldr_add_tabs();
		$args = array(
			'label'   => __( 'Sliders per page', 'slider-bws' ),
			'default' => 10,
			'option'  => 'sldr_per_page',
		);
		add_screen_option( 'per_page', $args );
	}
}

if ( ! function_exists( 'sldr_set_screen_options' ) ) {
	/**
	 * Function to set up table screen options.
	 *
	 * @param string $status Status.
	 * @param string $option Option.
	 * @param string $value  Value.
	 */
	function sldr_set_screen_options( $status, $option, $value ) {
		if ( 'sldr_cat_per_page' === $option || 'sldr_per_page' === $option ) {
			return $value;
		}
		return $status;
	}
}

if ( ! function_exists( 'sldr_register_plugin_links' ) ) {
	/**
	 * Add links to plugin.
	 *
	 * @param  array  $links Links array.
	 * @param  string $file  Plugin file.
	 * @return array  $links.
	 */
	function sldr_register_plugin_links( $links, $file ) {
		$base = plugin_basename( __FILE__ );
		if ( $file === $base ) {
			$links[] = '<a href="admin.php?page=slider-settings.php">' . __( 'Settings', 'slider-bws' ) . '</a>';
			$links[] = '<a href="http://wordpress.org/plugins/slider-bws/faq/" target="_blank">' . __( 'FAQ', 'slider-bws' ) . '</a>';
			$links[] = '<a href="https://support.bestwebsoft.com">' . __( 'Support', 'slider-bws' ) . '</a>';
		}
		return $links;
	}
}

if ( ! function_exists( 'sldr_plugin_action_links' ) ) {
	/**
	 * Add links to plugin.
	 *
	 * @param  array  $links Links array.
	 * @param  string $file  Plugin file.
	 * @return array  $links.
	 */
	function sldr_plugin_action_links( $links, $file ) {
		/* Static so we don't call plugin_basename on every plugin row. */
		static $this_plugin;
		if ( ! $this_plugin ) {
			$this_plugin = plugin_basename( __FILE__ );
		}

		if ( $file === $this_plugin ) {
			$settings_link = '<a href="admin.php?page=slider-settings.php">' . __( 'Settings', 'slider-bws' ) . '</a>';
			array_unshift( $links, $settings_link );
		}
		return $links;
	}
}

if ( ! function_exists( 'sldr_admin_notices' ) ) {
	/**
	 * Add BWS notice to plugin page.
	 */
	function sldr_admin_notices() {
		global $hook_suffix, $sldr_plugin_info, $sldr_options;

		if ( 'plugins.php' === $hook_suffix || ( isset( $_GET['page'] ) && 'slider-settings.php' === $_GET['page'] ) ) {
			if ( 'plugins.php' === $hook_suffix ) {
				if ( isset( $sldr_options['first_install'] ) && strtotime( '-1 week' ) > $sldr_options['first_install'] ) {
					bws_plugin_banner_to_settings( $sldr_plugin_info, 'sldr_options', 'slider-bws', 'admin.php?page=slider-settings.php', 'admin.php?page=slider-new.php' );
				}
			} else {
				bws_plugin_suggest_feature_banner( $sldr_plugin_info, 'sldr_options', 'slider' );
			}
		}
	}
}

if ( ! function_exists( 'sldr_add_tabs' ) ) {
	/**
	 * Add help tab to plugins page.
	 */
	function sldr_add_tabs() {
		$screen = get_current_screen();
		$args   = array(
			'id'      => 'sldr',
			'section' => '115000726446',
		);
		bws_help_tab( $screen, $args );
	}
}

if ( ! function_exists( 'sldr_plugin_uninstall' ) ) {
	/**
	 * Perform at uninstall
	 */
	function sldr_plugin_uninstall() {
		global $wpdb;

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			$old_blog = $wpdb->blogid;
			/* Get all blog ids */
			$blogids = $wpdb->get_col( "SELECT `blog_id` FROM $wpdb->blogs" );
			foreach ( $blogids as $blog_id ) {
				switch_to_blog( $blog_id );
				$wpdb->query( 'DROP TABLE IF EXISTS  `' . $wpdb->prefix . 'sldr_slider`, `' . $wpdb->prefix . 'sldr_slide`, `' . $wpdb->prefix . 'sldr_relation`, `' . $wpdb->prefix . 'sldr_category` ' );
				delete_option( 'sldr_options' );
			}
			switch_to_blog( $old_blog );
		} else {
			$wpdb->query( 'DROP TABLE IF EXISTS  `' . $wpdb->prefix . 'sldr_slider`, `' . $wpdb->prefix . 'sldr_slide`, `' . $wpdb->prefix . 'sldr_relation`, `' . $wpdb->prefix . 'sldr_category` ' );
			delete_option( 'sldr_options' );
		}

		require_once __DIR__ . '/bws_menu/bws_include.php';
		bws_include_init( plugin_basename( __FILE__ ) );
		bws_delete_plugin( plugin_basename( __FILE__ ) );
	}
}

if ( ! function_exists( 'crrntl_homepage_slider' ) ) {
	/**
	 * Slider for Homepage
	 */
	function sldr_homepage_slider() {
		if (
			is_home() ||
			is_front_page() ||
			is_page_template( 'page-homev1.php' ) ||
			is_page_template( 'page-homev2.php' ) ||
			is_page_template( 'page-homev3.php' )
		) {
			sldr_display_slider_before_content();
		}
	}
}

if ( ! function_exists( 'sldr_display_slider_before_content' ) ) {
	/**
	 * Display slider on home page
	 */
	function sldr_display_slider_before_content() {
		global $sldr_options;
		/* Find single slider which need to display in the front page of the Renty theme */
		$id = $sldr_options['display_in_front_page'];
		if ( 0 !== absint( $id ) ) {
			echo do_shortcode( '[print_sldr id=' . $id . ']' );
		}
	}
}


if ( ! function_exists( 'sldr_homepage_slider_renty' ) ) {
	/**
	 * Display slider on home page
	 *
	 * @param bool $slider_activ Flag for active slider.
	 */
	function sldr_homepage_slider_renty( $slider_activ ) {
		global $sldr_options, $wpdb, $sldr_plugin_info;
		if ( ! $sldr_plugin_info ) {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$sldr_plugin_info = get_plugin_data( __FILE__ );
		}

		$slider_activ = false;
		$id           = $sldr_options['display_in_front_page'];

		/* Find single slider which need to display in the front page of the Renty theme */

		if ( 0 !== absint( $id ) ) {
			wp_enqueue_style( 'sldr_stylesheet_home', plugins_url( '/css/home_search_form.css', __FILE__ ), array(), $sldr_plugin_info['Version'] );
			echo do_shortcode( '[print_sldr id=' . $id . ']' );
			$slider_activ = true;
		}
		return $slider_activ;
	}
}

if ( ! function_exists( 'sldr_get_slider_data' ) ) {
	/**
	 * Get Slider data objects
	 *
	 * @param string $number     Number for slider.
	 * @param string $categories Slider categories.
	 * @param string $id         Slider ID.
	 */
	function sldr_get_slider_data( $number = '', $categories = '', $id = '' ) {
		global $sldr_options, $wpdb;

		if ( empty( $sldr_options ) ) {
			sldr_settings();
		}
		$sliders = $wpdb->get_results(
			'SELECT *
			FROM ' . $wpdb->prefix . 'sldr_slider',
			ARRAY_A
		);

		if ( '' !== absint( $id ) ) {
			foreach ( $sliders as $item ) {
				foreach ( $item as $key => $val ) {
					if ( 'title' === $key && absint( $id ) !== absint( $val ) ) {
						unset( $sliders[ $key ] );
					}
				}
			}
		}

		if ( '' !== $number ) {
			$sliders = array_splice( $sliders, $number );
		}

		/* get sliders categories from db */
		if ( '' === $categories ) {
			$slider_categories = $wpdb->get_results(
				'SELECT 
					' . $wpdb->prefix . 'sldr_relation.category_id, 
					' . $wpdb->prefix . 'sldr_category.title,
					' . $wpdb->prefix . 'sldr_relation.slider_id
				FROM ' . $wpdb->prefix . 'sldr_relation
					LEFT JOIN ' . $wpdb->prefix . 'sldr_category
						ON ' . $wpdb->prefix . 'sldr_relation.category_id = ' . $wpdb->prefix . 'sldr_category.category_id
				WHERE ' . $wpdb->prefix . 'sldr_relation.category_id <> ""',
				ARRAY_A
			);
		} else {
			if ( is_array( $categories ) ) {
				foreach ( $categories as $val ) {
					$categories = implode( ',', $val );
				}
			}
			$slider_categories = $wpdb->get_results(
				'SELECT 
					' . $wpdb->prefix . 'sldr_relation.category_id, 
					' . $wpdb->prefix . 'sldr_category.title,
					' . $wpdb->prefix . 'sldr_relation.slider_id
				FROM ' . $wpdb->prefix . 'sldr_relation
					LEFT JOIN ' . $wpdb->prefix . 'sldr_category
						ON ' . $wpdb->prefix . 'sldr_relation.category_id = ' . $wpdb->prefix . 'sldr_category.category_id
				WHERE ' . $wpdb->prefix . 'sldr_relation.category_id <> ""
					AND ' . $wpdb->prefix . 'sldr_category.title IN ( ' . $categories . ' )',
				ARRAY_A
			);
		}

		/* Get slides from db */
		$slides = $wpdb->get_results(
			'SELECT 
				' . $wpdb->prefix . 'sldr_slide.slide_id,
				' . $wpdb->prefix . 'sldr_slide.attachment_id,
				' . $wpdb->prefix . 'sldr_slide.title,
				' . $wpdb->prefix . 'sldr_slide.description,
				' . $wpdb->prefix . 'sldr_slide.url,
				' . $wpdb->prefix . 'sldr_slide.button,
				' . $wpdb->prefix . 'sldr_slide.order,
				' . $wpdb->prefix . 'sldr_relation.slider_id
			FROM ' . $wpdb->prefix . 'sldr_slide
				LEFT JOIN ' . $wpdb->prefix . 'sldr_relation
					ON ' . $wpdb->prefix . 'sldr_relation.attachment_id = ' . $wpdb->prefix . 'sldr_slide.attachment_id',
			ARRAY_A
		);

		/* Loop sliders, add sliders settigns to the array */
		foreach ( $sliders as $item => $arr ) {
			foreach ( $arr as $key => $val ) {
				if ( 'settings' === $key ) {
					$sliders[ $item ][ $key ] = maybe_unserialize( $val );
				}
			}
		}

		/* Loop categories if exists, add to the main array */
		foreach ( (array) $slider_categories as $sldr_ctgr_arr ) {
			foreach ( $sldr_ctgr_arr as $sldr_ctgr_key => $sldr_ctgr_val ) {
				foreach ( $sliders as $sliders_arr => $slider_arr ) {
					foreach ( $slider_arr as $slider_key => $slider_val ) {

						if ( $sldr_ctgr_key === $slider_key && $sldr_ctgr_val === $slider_val ) {
							$sliders[ $sliders_arr ]['categories'][] = $sldr_ctgr_arr;
						}
					}
				}
			}
		}

		/* Loop sliders and slides, add slides to the main array */
		foreach ( $sliders as $sliders_arr => $slider_arr ) {
			foreach ( $slider_arr as $slider_key => $slider_val ) {
				foreach ( $slides as $slide_arr ) {
					foreach ( $slide_arr as $slide_key => $slide_val ) {

						if ( $slide_key === $slider_key && $slide_val === $slider_val ) {
							$sliders[ $sliders_arr ]['slides'][] = $slide_arr;
						}
					}
				}
			}
		}

		return $sliders;
	}
}

register_activation_hook( __FILE__, 'sldr_plugin_activate' );

add_action( 'admin_menu', 'sldr_add_admin_menu' );

add_action( 'init', 'sldr_init' );
add_action( 'admin_init', 'sldr_admin_init' );

add_action( 'plugins_loaded', 'sldr_plugins_loaded' );

add_action( 'admin_enqueue_scripts', 'sldr_admin_head' );
add_action( 'wp_enqueue_scripts', 'sldr_register_scripts' );

/* Additional links on the plugin page */
add_filter( 'plugin_row_meta', 'sldr_register_plugin_links', 10, 2 );
add_filter( 'plugin_action_links', 'sldr_plugin_action_links', 10, 2 );

add_action( 'admin_notices', 'sldr_admin_notices' );

add_filter( 'set-screen-option', 'sldr_set_screen_options', 10, 3 );

/*  Add place for notice in media upoader for portfolio */
add_action( 'print_media_templates', 'sldr_print_media_notice', 11 );
/*  Add notises in media upoader for slider */
add_action( 'wp_ajax_sldr_media_check', 'sldr_media_check_ajax_action' );

add_action( 'wp_ajax_sldr_delete_image', 'sldr_delete_image' );
add_action( 'wp_ajax_sldr_add_from_media', 'sldr_add_from_media' );

add_filter( 'bws_shortcode_button_content', 'sldr_shortcode_button_content' );

add_shortcode( 'print_sldr', 'sldr_shortcode' );

add_filter( 'widget_text', 'do_shortcode' );

/* Display slider on home page of the Renty theme */
add_action( 'sldr_display_slider', 'sldr_homepage_slider' );
add_filter( 'template_homepage_slider', 'sldr_homepage_slider_renty' );
