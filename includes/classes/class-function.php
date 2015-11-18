<?php
/**
 * Definition for dealing with unction WP Reference objects.
 *
 * @package WP_Documentor
 */
namespace WP_Doc\Tools\Func;
use WP_Doc\Tools\Reference;

/**
 * Definition for a reference function, creating a fully defined object.
 */
class Func extends Reference\Reference {
	/**
	 * Contains the template name to use for rendering this object.
	 * @var string
	 */
	protected $_template = 'reference-item';

	/**
	 * This object represents function references.
	 * @var string
	 */
	protected $_type = 'function';

	/**
	 * Whether this reference object is callable or not.
	 * @var bool If the reference object is callable.
	 */
	protected $_callable = true;

	/**
	 * The post type the reference object should map to.
	 * @var string The post type slug this reference object wraps.
	 */
	protected static $_post_type = 'wp-parser-function';

	/**
	 * The connection to look for when getting uses data.
	 * @var array
	 */
	protected $_uses_types = array( 'functions_to_functions', 'functions_to_methods', 'functions_to_hooks' );

	/**
	 * The connectsions to look for when getting used by data.
	 * @var array
	 */
	protected $_used_by_types = array( 'functions_to_functions', 'methods_to_functions' );

	/**
	 * Prepares data for the templater so it's in the correct format.
	 *
	 * @return array The array of data set up for templating.
	 */
	protected function prepare_data(){
		return array();
	}
}
