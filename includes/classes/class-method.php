<?php
/**
 * Definition for dealing with method WP Reference objects.
 *
 * @package WP_Documentor
 */
namespace WP_Doc\Tools\Meth;
use WP_Doc\Tools\Reference;

/**
 * Definition for a reference method, creating a fully defined object.
 */
class Meth extends Reference\Reference {
	/**
	 * Contains the template name to use for rendering this object.
	 * @var string
	 */
	protected $_template = 'reference-item';

	/**
	 * This object represents method references.
	 * @var string
	 */
	protected $_type = 'method';

	/**
	 * The post type the reference object should map to.
	 * @var string The post type slug this reference object wraps.
	 */
	protected static $_post_type = 'wp-parser-method';

	/**
	 * Whether this reference object is callable or not.
	 * @var bool If the reference object is callable.
	 */
	protected $_callable = true;

	/**
	 * The connections to look for when getting uses data.
	 * @var array
	 */
	protected $_uses_types = array( 'methods_to_functions', 'methods_to_methods', 'methods_to_hooks' );

	/**
	 * The connectsions to look for when getting used by data.
	 * @var array
	 */
	protected $used_by_types = array( 'functions_to_methods', 'methods_to_methods' );

	/**
	 * Prepares data for the templater so it's in the correct format.
	 *
	 * @return array The array of data set up for templating.
	 */
	protected function prepare_data(){
		return array();
	}
}
