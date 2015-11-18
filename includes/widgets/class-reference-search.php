<?php
/**
 * Declares a widget class for outputting a reference search box.
 *
 * @package WP_Documentor
 */

namespace WP_Doc\Tools\Widgets;

use WP_Doc\Template;
use WP_Doc\Reference_List;

/**
 * A reference search widget for placing reference search in sidebars.
 */
class Reference_Search extends \WP_Widget {

	/**
	 * Registers the widget with the WordPress Widget API.
	 * 
	 * @return void
	 */
	public static function register() {
		register_widget( __CLASS__ );
	}

	/**
	 * Sets up the widget in the system.
	 *
	 * @return Reference_Search An instance of this widget class.
	 */
	public function __construct() {
		$widget_ops = array(
			'classname' => 'wpd_reference_search',
			'description' => __( 'Reference search input and list for your site.', 'wpd' ),
		);
		parent::__construct(
			'wpd_reference_search',
			_x( 'Reference Search', 'Reference Search Widget', 'wpd' ),
			$widget_ops
		);
	}

	/**
	 * Outputs the widget admin form.
	 *
	 * @return void
	 */
	public function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '' ) );
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">
				<?php esc_html_e( 'Title:', 'wpd' ); ?>
				<input
					class="widefat"
					id="<?php echo $this->get_field_id( 'title' ); ?>"
					name="<?php echo $this->get_field_name( 'title' ); ?>"
					type="text"
					value="<?php echo esc_attr( $instance['title'] ); ?>"
				/>
			</label>
		</p>
		<?php
	}

	/**
	 * Validates and sanitizes the widget form input.
	 *
	 * @param  array $new_instance The input values from the widget form.
	 * @param  array $old_instance The previous values from the widget form.
	 * @return array               The sanitized and updated values.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$new_instance = wp_parse_args( (array) $new_instance, array( 'title' => '' ) );
		$instance['title'] = wp_strip_all_tags( $new_instance['title'] );
		return $instance;
	}

	/**
	 * Outputs the widget front end markup and data.
	 *
	 * @return void.
	 */
	public function widget( $args, $instance ) {
		/** This filter is documented in wp-includes/default-widgets.php */
		$title = apply_filters(
			'widget_title',
			( empty( $instance['title'] ) ) ? '' : $instance['title'],
			$instance,
			$this->id_base
		);

		echo $args['before_widget'];

		if ( $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		}

		// Output the reference search form if available.
		echo apply_filters( 'wpd_search_form', '' );

		// Render a type list template if available.
		echo apply_filters(
			'wpd_render_ref_list',
			'',
			'type-list',
			array(),
			array(
				'posts_per_page' => 100,
				'post_type' => 'reference',
				'order_by' => 'title',
				'order' => 'ASC',
			)
		);

		echo $args['after_widget'];
	}
}