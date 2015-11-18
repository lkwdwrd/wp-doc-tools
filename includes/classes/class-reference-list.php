<?php
/**
 * Abstract definition for querying and dealing with multiple WP Reference objects.
 *
 * @package WP_Documentor
 */

namespace WP_Doc\Tools\Reference_List;

/**
 * Definition for creating a WP Reference object list which provides easier data access.
 *
 * The reference list object wraps a WP_Query object of reference post types. This helps
 * create easier access to the reference level data on the reference objects.
 */
class Reference_List implements \JsonSerializable {
	/**
	 * Holds the templating object used to render strings for output.
	 * @var object
	 */
	protected $_templater;

	/**
	 * Holds a reference to the WP_Post object so it can be referenced for data.
	 * @var WP_Post
	 */
	protected $_query;

	/**
	 * Various metadata about this list. Useful in templating.
	 * @var string
	 */
	protected $_meta = array();

	/**
	 * Protected constructor to force use of the factory as well as create the templater.
	 *
	 * @return object The constructed reference object.
	 */
	public function __construct( $meta = array(), $query = null ) {
		// Set this object's metadata
		if ( ! empty( $meta ) && is_array( $meta ) ) {
			$this->_meta = $meta;
		}

		// Set this object's query.
		if ( is_null( $query ) ) {
			global $wp_query;
			$this->_query = $wp_query;
		} elseif( $query instanceof \WP_Query ) {
			$this->_query = $query;
		} else {
			$this->_query = new \WP_Query( $query );
		}
	}

	/**
	 * Gets the templater to use for outputting this object's data.
	 *
	 * @return WPDoc_Hbs_Templater The prepared templating object.
	 */
	protected function _get_templater() {
		if ( ! $this->_templater ) {
			/**
			 * Sets the template engine to use for wpd list objected.
			 *
			 * @param string $templater The templater class to use for wpd lists.
			 */
			$engine = apply_filters(
				'wpd_list_templater_object',
				'WP_Doc\\Tools\\Templater\\Basic'
			);
			$this->_templater = new $engine();
		}
		return $this->_templater;
	}

	/**
	 * Renders out this object based on the set template and the templater.
	 *
	 * @param  string $template The name of the template to render out.
	 * @return string           The rendered output for this object.
	 */
	public function render( $template ) {
		/**
		 * Filters the data sent to the templater template for wpd list objects.
		 *
		 * @param object $wpd_list The reference list object for processing.
		 */
		$data = apply_filters( 'wpd_list_template_data', $this );
		/**
		 * Fires right before a reference list template is rendered.
		 *
		 * @param string $template The name of the template being rendered.
		 * @param object $wpd_list The reference template object for reference.
		 */
		do_action( 'wpd_render_list_template', $template, $this );
		return $this->_get_templater()->render( $template, $data );
	}

	/**
	 * Maps all found reference objects to a specific, typed template call.
	 *
	 * You can pass a single string to map all items to the same template, or
	 * you can pass an array to template certain items to specific templates
	 * based on type. The first template in the array will be considered the
	 * default template. Mapping is assumed to be 'type' => 'template'.
	 *
	 * @param string|array $template_map What template to map the items to.
	 * @return string                    HTML based on the template calls.
	 */
	public function map_render( $template ) {
		$default = ( is_array( $template ) ) ? reset( $template ) : $template;
		$template_map = ( is_array( $template ) ) ? $template : array();
		$reference_list = $this->reference_objects();
		$render = '';

		foreach( $reference_list as $reference ) {
			if ( isset( $template_map[ $reference->type ] ) ) {
				$render .= $reference->render( $template_map[ $reference->type ] );
			} else {
				$render .= $reference->render( $default );
			}
		}

		return $render;
	}

	/**
	 * Get an array of all posts wrapped in the appropriate reference object.
	 *
	 * @return array An array of reference objects.
	 */
	public function reference_objects() {
		return array_map(
			'\\WP_Doc\\Tools\\Helpers\\_get_reference_object', 
			$this->_query->posts
		);
	}

	/**
	 * Gets the current WP_Query post object wrapped as a reference object.
	 *
	 * Useful if needing to get a reference object while running a WP loop.
	 *
	 * @return mixed The appropriate reference object for the current post.
	 */
	public function reference() {
		return \WP_Doc\Tools\Helpers\_get_reference_object( $this->_query->post );
	}

	/**
	 * Controls the JSON serialization of this object to make it eaiser to send to JS.
	 *
	 * @return array An array of data for JSON representation.
	 */
	public function jsonSerialize() {
		return array(
			'meta' => $this->_meta,
			'references' => $this->reference_objects(),
		);
	}

	/**
	 * Support calling methods on the WP_Query object through this object.
	 *
	 * @param  string $function The function name to call on the WP_Query object.
	 * @param  string $args     The arguments to send to the WP_Query object.
	 * @return mixed            The return value from the WP_Query object.
	 */
	public function __call( $function, $args ) {
		return call_user_func_array( array( $this->_query, $function ), $args );
	}

	/**
	 * Support getting meta and WP_Query data through this object.
	 *
	 * @param  string $key The key to get on the WP_Query object.
	 * @return mixed       The value from the WP_Query object.
	 */
	public function __get( $key ) {
		if ( property_exists( $this->_query, $key ) ) {
			return $this->_query->$key;
		} elseif ( isset( $this->_meta[ $key ] ) ) {
			return $this->_meta[ $key ];
		} else {
			return null;
		}
	}

	/**
	 * Support setting values on the WP_Query object through this object.
	 *
	 * @param  string $function The key to set on the WP_Query object.
	 * @param  string $args     The value to set on the WP_Query object.
	 * @return void
	 */
	public function __set( $key, $value ) {
		if ( property_exists( $this->_query, $key ) ) {
			$this->_query->$key = $value;
		} else {
			$this->_meta[ $key ] = $value;
		}
	}
}
