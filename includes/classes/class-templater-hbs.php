<?php
/**
 * Output handlebars templates, ensuring sub-templates also get rendered.
 *
 * @package WP_Documentor
 */
namespace WP_Doc\Hbs_Templater;

/**
 * Defines the templater class, used for turning reference data into views.
 */
class Templater {
	/**
	 * Holds reference to the handlebars object for templating.
	 * @var Handlebars
	 */
	protected static $_handlebars;

	/**
	 * Gets a copy of the handlebars object, cached if available.
	 *
	 * @return Handlebars
	 */
	protected static get_handlebars() {
		if ( ! self::$_handlebars ) {
			/**
			 * Filters the arguments passed to the handlebars templater so they can
			 * be customized as needed to load other templates, etc.
			 *
			 * @var array
			 */
			$args = apply_filters( 'wpd_hbs_args',  array(
				'loader' => new Loader(
					WPDOC_PATH . 'templates/hbs',
					array(
						'extension' => 'hbs',
					)
				),
			) );
			self::$_handlebars = new Handlebars( $args );
		}
	}

	/**
	 * The name of the default template to render in this instance.
	 * @var string
	 */
	protected $_name;

	/**
	 * Prepares a handlebars templater object.
	 *
	 * @param  string              $name The name of the handlebars template to use.
	 * @return WPDoc_Hbs_Templater       The contstructed object.
	 */
	public function __construct( $name ) {
		$this->_name = (string) $name;
	}

	/**
	 * Creates the handlebars output string based on the passed data.
	 *
	 * @param  array  $data The array of data to pass to the template.
	 * @param  string $name Optionally render a different template than the default.
	 * @return string       The HTML string rednered by the template.
	 */
	public function render( $data, $name = '' ) {
		$template = ( $name ) ? $name : $this->_name;
		$subrender_regex = '/<!-- subrender:([^\s]+) -->/';
		$subrender = $this->subrenderer( $data );

		$markup = self::get_handlebars()->render( $template, $data );
		return preg_replace_callback( $subrender_regex, $subrenderer, $markup );
	}

	/**
	 * Creates a closure to be used as the subrender callback function in renders.
	 *
	 * The subrenderer is expecting a set of nested objects at the same key as the
	 * matched subrender request. It is expecting an array of objects that each in
	 * turn have a render function to get their data.
	 *
	 * The function leaves teh subrender comment token in place for future reference.
	 *
	 * @param  array $data The array of data to use in rendering the main object.
	 * @return string       The subrender for the nested set of objects.
	 */
	protected function subrenderer( $data ) {
		return function( $subrender ) use( $data ) {
			$submarkup = "<!-- subrender:{$subrender} -->" . PHP_EOL;
			if ( isset( $data[ $subrender ] ) && is_array( $data[ $subrender ] ) {
				foreach $data[ $subrender ] as $subrender {
					$submarkup .= $subrender->render();
				}
			}
			return $submarkup;
		}
	}
}