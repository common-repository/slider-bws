<?php
/**
 * Class for display Slider Media table
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Media_List_Table extends for render of slider media
 */
class Sldr_Media_Table extends WP_Media_List_Table {

	/**
	 * Declare constructor
	 *
	 * @param array $args Args for class.
	 */
	public function __construct( $args = array() ) {
		parent::__construct(
			array(
				'plural' => 'media',
				'screen' => isset( $args['screen'] ) ? $args['screen'] : '',
			)
		);
	}

	/**
	 * Display text for no_items
	 */
	public function no_items() {
		esc_html_e( 'No images found.', 'slider-bws' );
	}

	/**
	 * Generate the table navigation above or below the table
	 *
	 * @param string $which Flag for position.
	 */
	public function display_tablenav( $which ) {
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">
			<?php $this->extra_tablenav( $which ); ?>
			<br class="clear" />
		</div>
		<?php
	}

	/**
	 * Display rows
	 */
	public function display_rows() {
		global $post, $wp_query, $sldr_id, $wpdb;

		/* Get slider images */
		$slider_attachment_ids = $wpdb->get_col( $wpdb->prepare( 'SELECT `attachment_id` FROM `' . $wpdb->prefix . 'sldr_relation` WHERE `attachment_id` IS NOT NULL AND `slider_id` = %d', $sldr_id ) );
		if ( ! empty( $slider_attachment_ids ) ) {
			add_filter( 'posts_orderby', 'sldr_edit_attachment_orderby' );
			add_filter( 'posts_join', 'sldr_edit_attachment_join' );

			/* Loop for media items */
			query_posts(
				array(
					'order'          => 'asc',
					'post__in'       => $slider_attachment_ids,
					'post_type'      => 'attachment',
					'posts_per_page' => -1,
					'post_status'    => 'inherit',
				)
			);

			while ( have_posts() ) {
				the_post();
				$this->single_row( $post );
			}

			wp_reset_postdata();
			wp_reset_query();

			remove_filter( 'posts_orderby', 'sldr_edit_attachment_orderby' );
			remove_filter( 'posts_join', 'sldr_edit_attachment_join' );
		}
	}

	/**
	 * Disable views
	 */
	public function get_views() {
		return false;
	}

	/**
	 * Display custom views
	 */
	public function views() {
		?>
		<div class="sldr-wp-filter hide-if-no-js">
			<a href="#" class="button media-button sldr-media-bulk-select-button hide-if-no-js"><?php esc_html_e( 'Bulk Select', 'slider-bws' ); ?></a>
			<a href="#" class="button media-button sldr-media-bulk-cansel-select-button hide-if-no-js"><?php esc_html_e( 'Cancel Selection', 'slider-bws' ); ?></a>
			<a href="#" class="button media-button button-primary sldr-media-bulk-delete-selected-button hide-if-no-js" disabled="disabled"><?php esc_html_e( 'Delete Selected', 'slider-bws' ); ?></a>
		</div>
		<?php
	}

	/**
	 * Display single row
	 *
	 * @param object $post Attachment.
	 */
	public function single_row( $post ) {
		global $sldr_options, $sldr_plugin_info, $wpdb, $sldr_id;

		$slide_attribute         = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM `' . $wpdb->prefix . 'sldr_slide` WHERE `attachment_id` = %d', $post->ID ), ARRAY_A );
		$attachment_metadata     = wp_get_attachment_metadata( $post->ID );
		$image_attributes_medium = wp_get_attachment_image_src( $post->ID, 'medium' );
		$image_attributes_large  = wp_get_attachment_image_src( $post->ID, 'large' );
		?>
		<li tabindex="0" id="post-<?php echo esc_attr( $post->ID ); ?>" class="sldr-media-attachment">
			<div class="sldr-media-attachment-preview">
				<div class="sldr-media-thumbnail">
					<div class="centered">
						<img src="<?php echo isset( $image_attributes_medium[0] ) ? esc_url( $image_attributes_medium[0] ) : ''; ?>" class="thumbnail" draggable="false" />
						<input type="hidden" name="_sldr_order[<?php echo esc_attr( $post->ID ); ?>]" value="<?php echo esc_attr( $slide_attribute['order'] ); ?>" />
					</div>
				</div>
				<div class="sldr-media-attachment-details">
					<?php echo esc_html( $post->post_title ); ?>
				</div>
			</div>
			<a href="#" class="sldr-media-actions-delete dashicons dashicons-trash" title="<?php esc_html_e( 'Remove Image from Slider', 'slider-bws' ); ?>"></a>
			<input type="hidden" class="sldr_attachment_id" name="_sldr_attachment_id" value="<?php echo esc_attr( $post->ID ); ?>" />
			<input type="hidden" class="sldr_slider_id" name="_sldr_slider_id" value="<?php echo esc_attr( $sldr_id ); ?>" />
			<a class="thickbox sldr-media-actions-edit dashicons dashicons-edit" href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>#TB_inline?width=800&height=450&inlineId=sldr-media-attachment-details-box-<?php echo esc_attr( $post->ID ); ?>" title="<?php esc_html_e( 'Edit Image Info', 'slider-bws' ); ?>"></a>
			<a class="sldr-media-check" tabindex="-1" title="<?php esc_html_e( 'Deselect', 'slider-bws' ); ?>" href="#"><div class="media-modal-icon"></div></a>
			<div id="sldr-media-attachment-details-box-<?php echo esc_attr( $post->ID ); ?>" class="sldr-media-attachment-details-box">
				<div class="sldr-media-attachment-details-box-left">
					<img src="<?php echo isset( $image_attributes_large[0] ) ? esc_url( $image_attributes_large[0] ) : ''; ?>" alt="<?php echo esc_html( $post->post_title ); ?>" title="<?php echo esc_html( $post->post_title ); ?>" height="auto" width="<?php echo isset( $image_attributes_large[1] ) ? esc_attr( $image_attributes_large[1] ) : ''; ?>" />
				</div>
				<div class="sldr-media-attachment-details-box-right">
					<div class="attachment-details">
						<div class="attachment-info">
							<div class="details">
								<div><?php esc_html_e( 'File name', 'slider-bws' ); ?>: <?php echo esc_html( $post->post_title ); ?></div>
								<div><?php esc_html_e( 'File type', 'slider-bws' ); ?>: <?php echo esc_attr( get_post_mime_type( $post->ID ) ); ?></div>
								<?php if ( ! empty( $attachment_metadata ) ) { ?>
									<div><?php esc_html_e( 'Dimensions', 'slider-bws' ); ?>: <?php echo esc_attr( $attachment_metadata['width'] ); ?> &times; <?php echo esc_attr( $attachment_metadata['height'] ); ?></div>
								<?php } ?>
							</div>
						</div>
						<label class="setting" data-setting="title">
							<span class="name">
								<?php esc_html_e( 'Title', 'slider-bws' ); ?>
							</span>
							<input type="text" name="sldr_image_title[<?php echo esc_attr( $post->ID ); ?>]" value="<?php echo esc_html( $slide_attribute['title'] ); ?>" />
						</label>
						<label class="setting" data-setting="description">
							<span class="name"><?php esc_html_e( 'Description', 'slider-bws' ); ?></span>
							<textarea name="sldr_image_description[<?php echo esc_attr( $post->ID ); ?>]"><?php echo esc_html( $slide_attribute['description'] ); ?></textarea>
						</label>
						<label class="setting" data-setting="alt">
							<span class="name"><?php esc_html_e( 'Button URL', 'slider-bws' ); ?></span>
							<input type="text" name="sldr_link_url[<?php echo esc_attr( $post->ID ); ?>]" value="<?php echo esc_url( $slide_attribute['url'] ); ?>" />
						</label>
						<label class="setting">
							<span class="name">
								<?php esc_html_e( 'Button Text', 'slider-bws' ); ?>
							</span>
							<input type="text" name="sldr_button_text[<?php echo esc_attr( $post->ID ); ?>]" value="<?php echo esc_html( $slide_attribute['button'] ); ?>" />
						</label>
						<div class="clear"></div>
						<div class="sldr-media-attachment-actions">
							<a href="post.php?post=<?php echo esc_attr( $post->ID ); ?>&amp;action=edit"><?php esc_html_e( 'Edit more details', 'slider-bws' ); ?></a>
							<span class="sldr-separator">|</span>
							<a href="#" class="sldr-media-actions-delete"><?php esc_html_e( 'Remove from Slider', 'slider-bws' ); ?></a>
							<input type="hidden" class="sldr_attachment_id" name="_sldr_attachment_id" value="<?php echo esc_attr( $post->ID ); ?>" />
							<input type="hidden" class="sldr_slider_id" name="_sldr_slider_id" value="<?php echo esc_attr( $sldr_id ); ?>" />
						</div>
					</div>
					<div class="clear"></div>
				</div>
			</div>
		</li>
		<?php
	}
}
