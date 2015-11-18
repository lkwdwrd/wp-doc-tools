<?php
/**
 * Helpers to make use of parser tools a little more decoupled during template use.
 *
 * These helpers are tied to hooks so that rather than call them directly, themes can
 * instead invoke the hook. If this plugin is not active, no fatal error is called, it
 * just runs the hook with nothing attached.
 *
 * @package WP_Documentor
 * @subpackage Tools
 */

namespace WP_Doc\Tools\Helpers;

use WP_Doc\Tools\Reference_List;

/**
 * Load this file into the WP API.
 *
 * This file is loaded on a WP Hook so that it is easy to control the load
 * order relative to this file if needed. Or, this entire file can be
 * unloaded by simply unhooking this function.
 *
 * @return void
 */
function load() {
	add_action( 'plugins_loaded', __NAMESPACE__ . '\\setup' );
}

/**
 * Hooks up various functions in this file with the WP API.
 *
 * This function is called on the plugins_loaded action. To customize the hooks
 * tied here, hook in late on plugins_loaded and modify as needed. Similarly, to
 * remove all of these hooks, hook in early and unhook the load function.
 *
 * @return void.
 */
function setup() {
	add_filter( 'wpd_render_ref',      __NAMESPACE__ . '\\render_reference',      10, 3 );
	add_filter( 'wpd_render_ref_list', __NAMESPACE__ . '\\render_reference_list', 10, 4 );
	add_filter( 'wpd_render_ref_map',  __NAMESPACE__ . '\\render_reference_map',  10, 3 );
}

/**
 * Render a reference template using a reference object render method.
 *
 * This function is tied to a filter so that it can be invoked indirectly by themes
 * keeping them decoupled from the plugin itself. They will not throw errors when
 * calling the function and the plugin is disabled, they will simply output an empty
 * string instead because this function is never hooked in.
 *
 * ```
 * echo apply_filters( 'wpd_render_ref', '', $template, $post );
 * ```
 *
 * Note the first passed value is an empty string. The second passed value is the
 * name of the template to use. The final parameter is a WP_Post object. The post
 * object will be converted into a reference object and the template function will be
 * invoke, returning the result to the filter.
 *
 * @param  string                 $result   The result of the render
 * @param  string                 $template The name of the template to render.
 * @param  array                  $post     A WP_Post object.
 * @return string                           The rendered markup.
 */
function render_reference( $result, $template, $post = null ) {
	$reference = _get_reference_object( $post );
	$result = $reference->render( $template );
	return $result;
}

/**
 * Render out a reference list object using the reference list render method.
 *
 * This function is tied to a filter so that it can be invoked indirectly by themes
 * keeping them decoupled from the plugin itself. They will not throw errors when
 * calling the function and the plugin is disabled, they will simply output an empty
 * string instead because this function is never hooked in.
 *
 * ```
 * echo apply_filters( 'wpd_render_ref_list', '', $template, $meta, $wp_query );
 * ```
 *
 * Note the first passed value is an empty string. The second passed value is a template
 * name. The third vlue is a set of metadata for templating or other uses. The final
 * parameter is either an array, query string, or WP_Query object. In the event it's
 * an array or query string, it will get turned into a new WP_Query object before
 * rendering.
 *
 * @param  string                 $result   The result of the render opperation,
 *                                          generally empty.
 * @param  string                 $template The name of the template to render.
 * @param  array                  $meta     The meta data to add to the reference list
 *                                          object.
 * @param  array|string|\WP_Query $query    Either a WP_Query object, or arguments to
 *                                          create one.
 * @return string                           The rendered markup.
 */
function render_reference_list( $result, $template, $meta, $query ) {
	$reference_list = new Reference_List\Reference_List( $meta, $query );
	$result = $reference_list->render( $template );
	return $result;
}

/**
 * Render out set of reference types in a query to a specific reference templates.
 *
 * This function is tied to a filter so that it can be invoked indirectly by themes
 * keeping them decoupled from the plugin itself. They will not throw errors when
 * calling the function and the plugin is disabled, they will simply output an empty
 * string instead because this function is never hooked in.
 *
 * ```
 * echo apply_filters( 'wpd_render_ref_map', '', $template, $wp_query );
 * ```
 *
 * Note the first passed value is an empty string. The second passed value is a
 * template name. The final parameter is either an array, query string, or WP_Query
 * object. In the event it's an array or query string, it will get turned into a
 * new WP_Query object before rendering.
 *
 * @param  string                 $result   The result of the render opperation,
 *                                          generally empty.
 * @param  string                 $template The name of the template to render.
 * @param  array|string|\WP_Query $query    Either a WP_Query object, or arguments to
 *                                          create one.
 * @return string                           The rendered markup.
 */
function render_reference_map( $result, $template, $query ) {
	$reference_list = new Reference_List\Reference_List( array(), $query );
	$result = $reference_list->map_render( $template );
	return $result;
}

/**
 * Constructs the appropriate helper object based on post type.
 *
 * @internal This function is intended for internal use only.
 *
 * @param  WP_Post|int $post Either a reference post object or ID.
 * @return object|bool       The object this post maps to or false on failure.
 */
function _get_reference_object( $post = null ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return;
	}

	/**
	 * Maps WP_Post obects to reference wrappers.
	 *
	 * This filter can be used to add additional reference wrappers to the list of
	 * available ones, or change the objects used to wrap the reference objects for
	 * other ones if needed.
	 *
	 * @param array $map The map of post_type => object classes.
	 */
	$object_map = apply_filters( 'wpd_object_map', array(
		'wp-parser-function' => '\\WP_Doc\\Tools\\Func\\Func',
		'wp-parser-hook'     => '\\WP_Doc\\Tools\\Hook\\Hook',
		'wp-parser-class'    => '\\WP_Doc\\Tools\\Cls\\Cls',
		'wp-parser-method'   => '\\WP_Doc\\Tools\\Meth\\Meth',
	) );

	if ( isset( $object_map[ get_post_type( $post ) ] ) ) {
		return call_user_func(
			array( $object_map[ get_post_type( $post ) ], 'get_reference' ),
			$post
		);
	} else {
		return false;
	}
}
