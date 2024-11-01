<?php
/**
 * Class for display Slider Category table
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_List_Table extends for render of slider categories
 */
class Sldr_Category_List_Table extends WP_List_Table {

	/**
	 * Declare constructor
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'category',
				'plural'   => 'categories',
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

		switch ( $column_name ) {
			case 'count':
				/* Count items with current category */
				$sliders_with_current_category_count = absint(
					$wpdb->get_var(
						$wpdb->prepare(
							'SELECT COUNT( category_id )
							FROM `' . $wpdb->prefix . 'sldr_relation` WHERE `category_id` = %d',
							$item['category_id']
						)
					)
				);
				echo esc_attr( $sliders_with_current_category_count );
				break;
			case 'shortcode':
				bws_shortcode_output( '[print_sldr cat_id=' . $item['category_id'] . ']' );
				break;
			case 'name':
				return $item['title'];
			default:
				return print_r( $item, true );
		}
	}

	/**
	 * Render column with actions,
	 * when you hover row "Edit | Delete" links showed
	 *
	 * @param array $item Row (key, value array).
	 * @return HTML
	 */
	public function column_name( $item ) {
		$actions = array(
			'edit'   => sprintf( '<a href="?page=slider-categories.php&action=edit&sldr_category_id=%s">%s</a>', $item['category_id'], __( 'Edit', 'slider-bws' ) ),
			'delete' => sprintf( '<a href="?page=slider-categories.php&action=delete&sldr_category_id=%s">%s</a>', $item['category_id'], __( 'Delete', 'slider-bws' ) ),
		);

		return sprintf(
			'<strong><a href="?page=slider-categories.php&sldr_category_id=%d&action=edit">%s</strong></a>%s',
			esc_attr( $item['category_id'] ),
			esc_html( $item['title'] ),
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
			'<input type="checkbox" name="sldr_category_id[]" value="%s" />',
			esc_attr( $item['category_id'] )
		);
	}

	/**
	 * Return columns to display in table
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'        => '<input type="checkbox" />',
			'name'      => __( 'Name', 'slider-bws' ),
			'count'     => __( 'Count', 'slider-bws' ),
			'shortcode' => __( 'Shortcode', 'slider-bws' ),
		);
		return $columns;
	}

	/**
	 * Display text for no_items
	 */
	public function no_items() {
		esc_html_e( 'No Categories Found', 'slider-bws' );
	}

	/**
	 * Return columns that may be used to sort table
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'name' => array( 'name', true ), /* default sort */
		);
		return $sortable_columns;
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

		$slider_category_deleted_id = isset( $_REQUEST['sldr_category_id'] ) ? (array) $_REQUEST['sldr_category_id'] : array();

		$slider_category_deleted_id = array_map( 'absint', $slider_category_deleted_id );

		if ( 'delete' === $this->current_action() ) {
			/* If deleted more one category */
			if ( ! empty( $slider_category_deleted_id ) && is_array( $slider_category_deleted_id ) ) {
				foreach ( $slider_category_deleted_id as $slider_deleted_id ) {
					$wpdb->delete( $wpdb->prefix . 'sldr_category', array( 'category_id' => $slider_deleted_id ) );
					$wpdb->delete( $wpdb->prefix . 'sldr_relation', array( 'category_id' => $slider_deleted_id ) );
				}
				unset( $slider_deleted_id );
				/* If deleted one category */
			} elseif ( ! empty( $slider_category_deleted_id ) ) {
				$wpdb->delete( $wpdb->prefix . 'sldr_category', array( 'category_id' => $slider_category_deleted_id ) );
				$wpdb->delete( $wpdb->prefix . 'sldr_relation', array( 'category_id' => $slider_category_deleted_id ) );
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
		$this->_column_headers = array( $columns, $hidden, $sortable );

		/* Process bulk action if any */
		$this->process_bulk_action();

		/* Pagination */
		$per_page_option = get_current_screen()->get_option( 'per_page' );
		$current_page    = $this->get_pagenum();
		/* Prepare query params, as usual current page, order by and order direction */
		$order = isset( $_REQUEST['order'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : 'asc';

		/* Pagination */
		$per_page_query = get_user_meta( get_current_user_id(), $per_page_option['option'] );
		$per_page_value = intval( implode( ',', $per_page_query ) );
		$per_page       = ! empty( $per_page_value ) ? $per_page_value : $per_page_option['default'];
		$paged          = isset( $_REQUEST['paged'] ) ? max( 0, intval( $_REQUEST['paged'] * $per_page ) - $per_page ) : 0;

		/* Search result for categories */
		if ( isset( $_REQUEST['s'] ) ) {
			/* Get search query */
			$cat_search_title = '%' . sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) . '%';
			/* Display total items for search results */
			$total_items = count( $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM `' . $wpdb->prefix . 'sldr_category` WHERE `title` LIKE %s', $cat_search_title ), ARRAY_A ) );
		} else {
			/* If search query is empty, display all table content */
			$cat_search_title = '%%';
			/* Will be used in pagination settings */
			$total_items = $wpdb->get_var( 'SELECT COUNT( category_id ) FROM  `' . $wpdb->prefix . 'sldr_category`' );
		}

		/**
		 * Define $items array
		 * notice that last argument is ARRAY_A, so we will retrieve array
		 */
		$this->items = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * 
				FROM `' . $wpdb->prefix . 'sldr_category`
				WHERE `title` LIKE %s 
				ORDER BY `title` ' . $order . '
				LIMIT %d OFFSET %d',
				$cat_search_title,
				$per_page,
				$paged
			),
			ARRAY_A
		);

		/* Сonfigure pagination */
		$this->set_pagination_args(
			array(
				'total_items' => intval( $total_items ), /* total items defined above */
				'per_page'    => $per_page, /* per page constant defined at top of method */
			)
		);
	}
}
