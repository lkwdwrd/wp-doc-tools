<?php
/**
 * Definition for dealing with hook WP Reference objects.
 *
 * @package WP_Documentor
 */
namespace WP_Doc\Tools\Cls;
use WP_Doc\Tools\Reference;

/**
 * Definition for a reference hook, creating a fully defined object.
 */
class Cls extends Reference\Reference {
	/**
	 * Contains the template name to use for rendering this object.
	 * @var string
	 */
	protected $_template = 'reference-item';

	/**
	 * This object represents class references.
	 * @var string
	 */
	protected $_type = 'class';

	/**
	 * The post type the reference object should map to.
	 * @var string The post type slug this reference object wraps.
	 */
	protected static $_post_type = 'wp-parser-class';

	/**
	 * Prepares data for the templater so it's in the correct format.
	 *
	 * @return array The array of data set up for templating.
	 */
	protected function prepare_data(){
		return array();
	}

	/**
	 * Magic getter with methods key added and wired up.
	 *
	 * @param  string $key The requested object key to get.
	 * @return mixed       The data based on the requested key, or null.
	 */
	public function __get( $key ) {
		if ( 'methods' === $key ) {
			return $this->_cached_call( '_get_methods' );
		}

		return parent::__get( $key );
	}

	/**
	 * Overrides the json serialization so object have methods as well.
	 *
	 * @return array An array of data for JSON representation.
	 */
	public function jsonSerialize() {
		$representation = parent::jsonSerialize();
		
		// Loop through the classes methods and get the nested item data.
		$representation['methods'] = array();
		foreach( $this->methods as $method ) {
			$representation['methods'][] = apply_filters( 'wpd_json_nested_item', $method->get_basic_data(), $this );
		}

		return $representation;
	}

	/**
	 * Gets the methods in this class mapped to reference objects.
	 *
	 * @return array An array of method objects in this class.
	 */
	protected function _get_methods() {
		$items = new \WP_Query( array(
			'posts_per_page' => 1000,
			'post_type'      => 'wp-parser-method',
			'post_parent'    => $this->_post->ID,
			'post_status'    => 'publish',
			'nopaging'       => true,
			'fields'          => 'ids',
		) );

		if ( $items->have_posts() ) {
			$methods = array_map( '\\WP_Doc\\Tools\\Helpers\\_get_reference_object', $items->posts );
		} else {
			$methods = array();
		}
		usort( $methods, array( $this, '_sort_methods' ) );

		return $methods;
	}

	/**
	 * Sorts methods in this class alphabetically.
	 *
	 * The method is public so that PHP can call it in usort, but it is intended
	 * for internal use only.
	 *
	 * @param  Meth $a A method object to compare.
	 * @param  Meth $b A method object to compare.
	 * @return int     0, 1, or -1 depending on post name comparison.
	 */
	public function _sort_methods( $a, $b ) {
		return strcmp( $a->_post->post_name, $b->_post->post_name );
	}

	/**
	 * Get information about items that this object uses.
	 *
	 * Since this is not available for classes, we override it here and return array()
	 * to skip the fancy logic and indicate this object has no uses objects.
	 *
	 * @return array An empty array.
	 */
	public function get_uses() {
		return array();
	}

	/**
	 * Get information about items that this object is used by.
	 *
	 * Since this is not available for classes, we override it here and return array()
	 * to skip the fancy logic and indicate this object doesn't have used by objects.
	 *
	 * @return array An empty array.
	 */
	public function get_used_by() {
		return array();
	}
}
