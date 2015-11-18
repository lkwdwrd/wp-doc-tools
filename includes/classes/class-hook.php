<?php
/**
 * Definition for dealing with hook WP Reference objects.
 *
 * @package WP_Documentor
 */
namespace WP_Doc\Tools\Hook;
use WP_Doc\Tools\Reference;

/**
 * Definition for a reference hook, creating a fully defined object.
 */
class Hook extends Reference\Reference {
	/**
	 * Contains the template name to use for rendering this object.
	 * @var string
	 */
	protected $_template = 'reference-item';

	/**
	 * This object represents hook references.
	 * @var string
	 */
	protected $_type = 'hook';

	/**
	 * The post type the reference object should map to.
	 * @var string The post type slug this reference object wraps.
	 */
	protected static $_post_type = 'wp-parser-hook';

	/**
	 * The connectsions to look for when getting used by data.
	 * @var array
	 */
	protected $used_by_types = array( 'functions_to_hooks', 'methods_to_hooks' );

	/**
	 * Prepares data for the templater so it's in the correct format.
	 *
	 * @return array The array of data set up for templating.
	 */
	protected function prepare_data(){
		return array();
	}

	/**
	 * Get information about items that this object uses.
	 *
	 * Since this is not available for hooks, we override it here and return array()
	 * to skip the fancy logic and indicate this object has no uses objects.
	 *
	 * @return array An empty array.
	 */
	public function get_uses() {
		return array();
	}

	/**
	 * Retrieve signature name and arguments data for this reference object.
	 *
	 * @return void
	 */
	protected function _get_signature() {
		// Create the initial signature array.
		$signature    = array( 'name' => get_the_title( $this->_post ), 'args' => array() );

		// parse tags out into args.
		$tags = (array) get_post_meta( $this->_post->ID, '_wp-parser_tags', true );
		foreach ( $tags as $tag ) {
			if ( is_array( $tag ) && 'param' == $tag['name'] ) {
				$signature['args'][] = array(
					'type' => implode( '|', $tag['types'] ),
					'name' => $tag['variable'],
				);
			}
		}

		// Get the proper hook type
		$hook_type = get_post_meta( $this->_post->ID, '_wp-parser_hook_type', true );
		if ( false !== strpos( $hook_type, 'action' ) ) {
			$signature['hook_type'] = ( 'action_reference' === $hook_type ) ? 'do_action_ref_array' : 'do_action';
		} else {
			$signature['hook_type'] = ( 'filter_reference' === $hook_type ) ? 'apply_filters_ref_array' : 'apply_filters';
		}

		// Is this a dynamic or static hook?
		$signature['dynamic'] = ( false !== strpos( $signature['name'], '$' ) );

		// Send back the parsed signature data.
		return $signature;
	}
}
