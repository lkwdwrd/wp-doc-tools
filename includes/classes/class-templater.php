<?php
/**
 * Output template parts, ensuring sub-templates also get rendered.
 *
 * Created as abstraction so that it can be overriden with other templating engines. By
 * default this uses the basic WordPress finding mechanism, looking for templates in the
 * templates/wp-doc namespace. This can be filtered and overridden as needed for other
 * template organizations and themes.
 *
 * @package WP_Documentor
 */
namespace WP_Doc\Tools\Templater;

/**
 * Defines the templater class, used for turning reference data into views.
 */
class Basic {
	/**
	 * Template directory
	 * @var string
	 */
	protected $_template_dir;

	/**
	 * Prepares a handlebars templater object.
	 *
	 * @param  string $name The name of the template to use.
	 * @return Basic        The basic templating object.
	 */
	public function __construct( $template_dir = null ) {
		/**
		 * The template directory where the the templater will look for wpd templates.
		 *
		 * @param string $template_dir The template directory where wpd templates are stored.
		 */
		$default_dir = apply_filters( 'wpd_templater_default_dir', 'templates/wp-doc/' );
		$this->_template_dir = ( is_string( $template_dir ) ) ? $template_dir : $default_dir;
	}

	/**
	 * Creates the handlebars output string based on the passed data.
	 *
	 * @param  string $name Render the template based on the name provided.
	 * @param  array  $data The array of data to pass to the template.
	 * @return string       The HTML string rednered by the template.
	 */
	public function render( $name, $data ) {
		$template = locate_template( $this->_template_dir . $name . '.php' );
		if ( $template ) {
			ob_start();
			include $template;
			return ob_get_clean();
		} else {
			return '';
		}
	}
}
