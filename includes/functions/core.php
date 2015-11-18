<?php
/**
 * Core functionality for this plugin, including basic setup and WP integration.
 *
 * @package  WP_Documentor
 * @subpackage Tools
 */
namespace WP_Doc\Tools\Core;

/**
 * Load this file on a WP hook to make load order more predictable.
 *
 * @return void
 */
function load() {
	add_action( 'plugins_loaded', __NAMESPACE__ . '\\setup' );
}

/**
 * Default setup routine
 *
 * @return void
 */
function setup() {
	add_action( 'init', __NAMESPACE__ . '\\i18n' );
	add_action( 'init', __NAMESPACE__ . '\\init' );

	add_action( 'widgets_init', array( 'WP_Doc\\Tools\\Widgets\\Reference_Search', 'register' ) );

	do_action( 'wpd_tools_loaded' );
}

/**
 * Registers the default textdomain.
 *
 * @return void
 */
function i18n() {
	$locale = apply_filters( 'plugin_locale', get_locale(), 'wpd_tools' );
	load_textdomain( 'wpd_tools', WP_LANG_DIR . '/wpd_tools/wpd_tools-' . $locale . '.mo' );
	load_plugin_textdomain( 'wpd_tools', false, plugin_basename( WPD_TOOLS_PATH ) . '/languages/' );
}

/**
 * Initializes the plugin and fires an action other plugins can hook into.
 *
 * @return void
 */
function init() {
	do_action( 'wpd_tools_init' );
}
