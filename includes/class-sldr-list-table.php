<?php
/**
 * Class for display Slider table
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_List_Table extends for render of slider table
 */
class Sldr_List_Table extends WP_List_Table {

	/**
	 * Declare constructor
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'slider', 'slider-bws' ),
				'plural'   => __( 'sliders', 'slider-bws' ),
			)
		);
	}

	/**
	 * Declare column renderer
	 *
	 * @param array  $item        Row (key, value array).
	 * @param string $column_name Column name.
	 * @return HTML
	 */
	public function column_default( $item, $column_name ) {
		global $wpdb;

		/* Get array of sliders image */
		$slider_attachment_ids = $wpdb->get_col( $wpdb->prepare( 'SELECT `attachment_id` FROM `' . $wpdb->prefix . 'sldr_relation` WHERE `attachment_id` IS NOT NULL AND `slider_id` = %d', $item['slider_id'] ) );

		$slider_attachment_ids = array_map( 'esc_attr', $slider_attachment_ids );

		switch ( $column_name ) {
			case 'thumbnail':
				/* Thumbnail is first sliders picture */
				$thumbnail = wp_get_attachment_image( array_shift( $slider_attachment_ids ), array( 100, 100 ) );

				if ( ! empty( $thumbnail ) ) {
					echo '<a href="?page=slider-new.php&sldr_id=' . esc_attr( $item['slider_id'] ) . '"><span class="sldr_media_icon fixed column-format">' . wp_kses_post( $thumbnail ) . '</span></a>';
				}
				break;
			case 'shortcode':
				bws_shortcode_output( '[print_sldr id=' . $item['slider_id'] . ']' );
				break;
			case 'images_count':
				if ( ! empty( $slider_attachment_ids ) ) {
					echo count( $slider_attachment_ids );
				} else {
					echo '0';
				}
				break;
			case 'category':
				/* Get category list for slider with current ID in table. */
				$slider_current_categories_ids = $wpdb->get_col( $wpdb->prepare( 'SELECT `category_id` FROM `' . $wpdb->prefix . 'sldr_relation` WHERE `slider_id` = %d', $item['slider_id'] ) );

				$slider_current_categories_ids = array_map( 'esc_attr', $slider_current_categories_ids );

				/* Get category title for selected category ID. */
				$slider_category_title_array = array();

				foreach ( $slider_current_categories_ids as $slider_current_categories_id ) {

					if ( ! empty( $slider_current_categories_id ) ) {
						$slider_category_title         = $wpdb->get_var( $wpdb->prepare( 'SELECT `title` FROM `' . $wpdb->prefix . 'sldr_category` WHERE `category_id` = %d', $slider_current_categories_id ) );
						$slider_category_title_array[] = array(
							'id'    => $slider_current_categories_id,
							'title' => $slider_category_title,
						);
					}
				}
				unset( $slider_current_categories_id );

				if ( ! empty( $slider_category_title_array ) ) {
					/* Display category with comma. */
					foreach ( $slider_category_title_array as $slider_category ) {
						echo '<a href="?page=slider-categories.php&sldr_category_id=' . esc_attr( $slider_category['id'] ) . '&action=edit">' . esc_html( $slider_category['title'] ) . '</a><br>';
					}
				} else {
					echo '<p>—</p>';
				}
				break;
			case 'datetime':
				echo esc_attr( str_replace( '-', '/', $item[ $column_name ] ) );
				break;
			case 'title':
				return $item[ $column_name ];
			default:
				return print_r( $item, true );
		}
	}

	/**
	 * Render column with actions
	 *
	 * @param array $item Row (key, value array).
	 * @return HTML
	 */
	public function column_title( $item ) {
		$actions = array(
			'edit'   => sprintf( '<a href="?page=slider-new.php&sldr_id=%d">%s</a>', $item['slider_id'], __( 'Edit', 'slider-bws' ) ),
			'delete' => sprintf( '<a href="?page=%s&action=delete&sldr_id=%s">%s</a>', sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ), $item['slider_id'], __( 'Delete', 'slider-bws' ) ),
		);

		$title = empty( $item['title'] ) ? '(' . __( 'no title', 'slider-bws' ) . ')' : $item['title'];

		return sprintf(
			'<strong><a href="?page=slider-new.php&sldr_id=%d">%s</strong></a>%s',
			esc_attr( $item['slider_id'] ),
			esc_html( $title ),
			wp_kses_post( $this->row_actions( $actions ) )
		);
	}

	/**
	 * Checkbox column renders
	 *
	 * @param array $item Row (key, value array).
	 * @return HTML
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="sldr_id[]" value="%s" />',
			esc_attr( $item['slider_id'] )
		);
	}

	/**
	 * Return columns to display in table
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'           => '<input type="checkbox" />',
			'thumbnail'    => __( 'Thumbnail', 'slider-bws' ),
			'title'        => __( 'Title', 'slider-bws' ),
			'images_count' => __( 'Images', 'slider-bws' ),
			'category'     => __( 'Category', 'slider-bws' ),
			'shortcode'    => __( 'Shortcode', 'slider-bws' ),
			'datetime'     => __( 'Date', 'slider-bws' ),
		);
		return $columns;
	}

	/**
	 * Display text for no_items
	 */
	public function no_items() {
		esc_html_e( 'No Sliders Found', 'slider-bws' );
	}

	/**
	 * Generate the table navigation above or below the table
	 *
	 * @param string $which Flag for position.
	 */
	public function display_tablenav( $which ) {
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">
			<div class="alignleft actions bulkactions">
				<?php $this->bulk_actions( $which ); ?>
			</div>
			<?php
			$this->extra_tablenav( $which );
			$this->pagination( $which );
			?>
			<br class="clear" />
		</div>
		<?php
	}

	/**
	 * Add Dropdown categories for slider filter
	 *
	 * @param string $which Flag for position.
	 */
	public function extra_tablenav( $which ) {
		if ( 'top' === $which ) {
			global $wpdb;

			$slider_categories = $wpdb->get_results( 'SELECT `category_id`, `title` FROM `' . $wpdb->prefix . 'sldr_category`', ARRAY_A );
			/* Get selected category for current slider */
			$slider_category_selected = isset( $_POST['sldr_cat_select'] ) ? intval( $_POST['sldr_cat_select'] ) : '';
			?>
			<div class="alignleft actions">
				<select name="sldr_cat_select">
					<option value=""><?php esc_html_e( 'All Categories', 'slider-bws' ); ?></option>
					<?php
					foreach ( $slider_categories as $slider_category_value ) {
						$selected = ( $slider_category_value['category_id'] === $slider_category_selected ) ? ' selected="selected"' : '';
						echo '<option value="' . esc_attr( $slider_category_value['category_id'] ) . '"' . esc_html( $selected ) . '>' . esc_html( $slider_category_value['title'] ) . '</option>';
					}
					?>
				</select>
				<input name="sldr_filter_action" type="submit" class="button" value="<?php esc_html_e( 'Filter', 'slider-bws' ); ?>" />
			</div>
			<?php
		}
	}

	/**
	 * Return array of bulk actions if has any
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'delete' => __( 'Delete', 'slider-bws' ),
		);
		return $actions;
	}

	/**
	 * Processes bulk actions
	 */
	public function process_bulk_action() {
		global $wpdb;

		$slider_deleted_id = isset( $_REQUEST['sldr_id'] ) ? (array) $_REQUEST['sldr_id'] : array();

		$slider_deleted_id = array_map( 'absint', $slider_deleted_id );

		if ( 'delete' === $this->current_action() ) {
			/* If deleted some slider */
			if ( ! empty( $slider_deleted_id ) && is_array( $slider_deleted_id ) ) {
				/* If delete more 1 slider */
				foreach ( $slider_deleted_id as $slider_id ) {
					$wpdb->delete( $wpdb->prefix . 'sldr_slider', array( 'slider_id' => $slider_id ) );
					$wpdb->delete( $wpdb->prefix . 'sldr_relation', array( 'slider_id' => $slider_id ) );
				}
			} elseif ( ! empty( $slider_deleted_id ) ) {
				$wpdb->delete( $wpdb->prefix . 'sldr_slider', array( 'slider_id' => $slider_deleted_id ) );
				$wpdb->delete( $wpdb->prefix . 'sldr_relation', array( 'slider_id' => $slider_deleted_id ) );
			}
		}
	}

	/**
	 * Get rows from database and prepare them to be showed in table
	 */
	public function prepare_items() {
		global $wpdb;

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		/* Configure table headers, defined in this methods */
		$this->_column_headers = array( $columns, $hidden, $sortable, 'title' );

		/* Process bulk action if any */
		$this->process_bulk_action();

		$per_page_option = get_current_screen()->get_option( 'per_page' );
		$current_page    = $this->get_pagenum();

		/* Display selected category  */
		$search = ( isset( $_POST['s'] ) ) ? '%' . sanitize_text_field( wp_unslash( $_POST['s'] ) ) . '%' : '%%';

		if ( ! empty( $_POST['sldr_cat_select'] ) ) {

			/* Show sliders by selected category */
			$slider_category = intval( $_POST['sldr_cat_select'] );
			$per_page        = $per_page_option['default'];
			/* Prepare query params, as usual current page, order by and order direction */
			$paged = 0;

			/* Show selected slider categories */
			$this->items = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT `' . $wpdb->prefix . 'sldr_slider`.`slider_id`, `' . $wpdb->prefix . 'sldr_slider`.`title`, `' . $wpdb->prefix . 'sldr_slider`.`datetime` 
				FROM `' . $wpdb->prefix . 'sldr_slider` INNER JOIN `' . $wpdb->prefix . 'sldr_relation` 
				WHERE `' . $wpdb->prefix . 'sldr_slider`.`slider_id` = `' . $wpdb->prefix . 'sldr_relation`.`slider_id`
					AND `' . $wpdb->prefix . 'sldr_relation`.`category_id` = %d
					AND `' . $wpdb->prefix . 'sldr_relation`.`attachment_id` IS NULL
					AND `' . $wpdb->prefix . 'sldr_slider`.`title` LIKE %s 
				LIMIT %d OFFSET %d',
					$slider_category,
					$search,
					$per_page,
					$paged
				),
				ARRAY_A
			);

			/* Will be used in pagination settings */
			$total_items = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT( `' . $wpdb->prefix . 'sldr_slider`.`slider_id` )
				FROM `' . $wpdb->prefix . 'sldr_slider` INNER JOIN `' . $wpdb->prefix . 'sldr_relation` 
				WHERE `' . $wpdb->prefix . 'sldr_slider`.`slider_id` = `' . $wpdb->prefix . 'sldr_relation`.`slider_id`
					AND `' . $wpdb->prefix . 'sldr_relation`.`category_id` = %d
					AND `' . $wpdb->prefix . 'sldr_relation`.`attachment_id` IS NULL
					AND `' . $wpdb->prefix . 'sldr_slider`.`title` LIKE %s',
					$slider_category,
					$search
				)
			);

			/* If no category selected, display all */
		} else {
			/* Show all categories */
			$per_page_query = get_user_meta( get_current_user_id(), $per_page_option['option'] );

			$per_page_value = intval( implode( ',', $per_page_query ) );

			$per_page = ! empty( $per_page_value ) ? $per_page_value : $per_page_option['default'];

			/* Prepare query params, as usual current page, order by and order direction */
			$paged = isset( $_REQUEST['paged'] ) ? max( 0, intval( $_REQUEST['paged'] * $per_page ) - $per_page ) : 0;
			/* Will be used in pagination settings */
			$total_items = $wpdb->get_var( 'SELECT COUNT( slider_id ) FROM  `' . $wpdb->prefix . 'sldr_slider`' );

			/* Show all slider categories */
			$this->items = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT `slider_id`, `title`, `datetime` 
				FROM `' . $wpdb->prefix . 'sldr_slider`
				WHERE `title` LIKE %s 
				LIMIT %d OFFSET %d',
					$search,
					$per_page,
					$paged
				),
				ARRAY_A
			);
		}

		/* Сonfigure pagination */
		$this->set_pagination_args(
			array(
				'total_items' => intval( $total_items ), /* total items defined above */
				'per_page'    => $per_page, /* per page constant defined at top of method */
				'total_pages' => ceil( $total_items / $per_page ), /* calculate pages count */
			)
		);
	}
}
