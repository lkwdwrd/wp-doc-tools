<?php
/**
 * Plugin Name: WP Documentor Tools
 * Plugin URI:  http://wordpress.org/plugins
 * Description: Tools for working with the WP_Parser post types and data.
 * Version:     0.1.0
 * Author:      Luke Woodward
 * Author URI:  https://lkwdwrd.com
 * License:     GPLv2+
 * Text Domain: wpd_tools
 * Domain Path: /languages
 */

/**
 * Copyright (c) 2015 10up (email : info@10up.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * Built using yo wp-make:plugin
 * Copyright (c) 2015 10up, LLC
 * https://github.com/10up/generator-wp-make
 */

namespace WP_Doc\Tools;

// Useful global constants
define( 'WPD_TOOLS_VERSION', '0.1.0' );
define( 'WPD_TOOLS_URL',     plugin_dir_url( __FILE__ ) );
define( 'WPD_TOOLS_PATH',    dirname( __FILE__ ) . '/' );
define( 'WPD_TOOLS_INC',     WPD_TOOLS_PATH . 'includes/' );

// Include files
require_once WPD_TOOLS_INC . 'functions/core.php';
require_once WPD_TOOLS_INC . 'functions/helpers.php';

// Inlude classes
require_once WPD_TOOLS_INC . 'classes/class-reference-list.php';
require_once WPD_TOOLS_INC . 'classes/class-reference.php';
require_once WPD_TOOLS_INC . 'classes/class-function.php';
require_once WPD_TOOLS_INC . 'classes/class-hook.php';
require_once WPD_TOOLS_INC . 'classes/class-class.php';
require_once WPD_TOOLS_INC . 'classes/class-method.php';
require_once WPD_TOOLS_INC . 'classes/class-templater.php';

//Include Widgets
require_once WPD_TOOLS_INC . 'widgets/class-reference-search.php';

// Bootstrap
\WP_Doc\Tools\Core\load();
\WP_Doc\Tools\Helpers\load();
