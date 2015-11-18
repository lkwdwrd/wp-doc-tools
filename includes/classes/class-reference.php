<?php
/**
 * Abstract definition for dealing with the WP Reference objects.
 *
 * @package WP_Documentor
 */
namespace WP_Doc\Tools\Reference;

/**
 * Definition for creating a WP Reference object which provides easier data access.
 *
 * The reference objects are custom WP Post objects, but these lack many of the
 * methods required to effectively work with these items. This defines a general
 * method of accessing and working with these objects that can be extended to create
 * full featured objects for each of the reference types.
 */
abstract class Reference implements \JsonSerializable {
	/**
	 * Hold created instances of reference objects so they only need to be created once.
	 * @var array Holds the cache of created reference objects.
	 */
	private static $_object_cache = array();

	/**
	 * The post type the reference object should map to.
	 * @var string The post type slug this reference object wraps.
	 */
	protected static $_post_type = '';

	/**
	 * Factory method for constructing objects based on post ID or post object.
	 *
	 * @param WP_Post|int $post A WP_Post object or a post ID.
	 * @return object|bool      The constructed reference object, or false on fail.
	 */
	public static function get_reference( $post = null ) {
		// Ensure we have a post.
		$post = get_post( $post );
		$cache_key = get_called_class();

		if ( ! $post ) {
			return false;
		}
		// Make sure we know this post type.
		if ( self::$_post_type === get_post_type( $post ) ) {
			return false;
		}
		// Make sure the cache has this object type as an array.
		if ( ! isset( self::$_object_cache[ $cache_key ] ) ) {
			self::$_object_cache[ $cache_key ] = array();
		}
		// Check for cached version of requested object, construct it if its not.
		if ( ! isset( self::$_object_cache[ $cache_key ][ $post->ID ] ) ) {
			self::$_object_cache[ $cache_key ][ $post->ID ] = new static( $post );
		}
		// Return the requested object.
		return self::$_object_cache[ $cache_key ][ $post->ID ];
	}

	/**
	 * Prevents display of the inline use of {@internal}} as it is not meant to be shown.
	 *
	 * @param  string      $content   The post content.
	 * @param  null|string $post_type Optional. The post type. Default null.
	 * @return string
	 */
	public static function remove_inline_internal( $content ) {
		return preg_replace( '/\{@internal (.+)\}\}/', '', $content );
	}

	/**
	 * Makes phpDoc @see and @link references clickable.
	 *
	 * Handles these six different types of links:
	 *
	 * - {@link http://en.wikipedia.org/wiki/ISO_8601}
	 * - {@see WP_Rewrite::$index}
	 * - {@see WP_Query::query()}
	 * - {@see esc_attr()}
	 * - {@see 'pre_get_search_form'}
	 * - {@link http://codex.wordpress.org/The_Loop Use new WordPress Loop}
	 *
	 * Note: Though @see and @link are semantically different in meaning, that isn't always
	 * the case with use so this function handles them identically.
	 *
	 * @param  string $content The content.
	 * @return string
	 */
	public static function make_doclink_clickable( $content ) {

		// Nothing to change unless a @link or @see reference is in the text.
		if ( false === strpos( $content, '{@link ' ) && false === strpos( $content, '{@see ' ) ) {
			return $content;
		}

		return preg_replace_callback(
			'/\{@(?:link|see) ([^\}]+)\}/',
			function ( $matches ) {

				$link = $matches[1];

				// Undo links made clickable during initial parsing
				if ( 0 === strpos( $link, '<a ' ) ) {

					if ( preg_match( '/^<a .*href=[\'\"]([^\'\"]+)[\'\"]>(.*)<\/a>(.*)$/', $link, $parts ) ) {
						$link = $parts[1];
						if ( $parts[3] ) {
							$link .= ' ' . $parts[3];
						}
					}

				}

				// Link to an external resource.
				if ( 0 === strpos( $link, 'http' ) ) {

					$parts = explode( ' ', $link, 2 );

					// Link without linked text: {@link http://en.wikipedia.org/wiki/ISO_8601}
					if ( 1 === count( $parts ) ) {
						$link = '<a href="' . esc_url( $link ) . '">' . esc_html( $link ) . '</a>';
					}

					// Link with linked text: {@link http://codex.wordpress.org/The_Loop Use new WordPress Loop}
					else {
						$link = '<a href="' . esc_url( $parts[0] ) . '">' . esc_html( $parts[1] ) . '</a>';
					}

				}

				// Link to an internal resource.
				else {

					// Link to class variable: {@see WP_Rewrite::$index}
					if ( false !== strpos( $link, '::$' ) ) {
						// Nothing to link to currently.
					}

					// Link to class method: {@see WP_Query::query()}
					elseif ( false !== strpos( $link, '::' ) ) {
						$link = '<a href="' .
							get_post_type_archive_link( 'wp-parser-class' ) .
							str_replace( array( '::', '()' ), array( '/', '' ), $link ) .
							'">' . esc_html( $link ) . '</a>';
					}

					// Link to hook: {@see 'pre_get_search_form'}
					elseif ( 1 === preg_match( '/^(&#8216;)\w+(&#8217;)$/', $link, $hook ) ) {
						if ( ! empty( $hook[0] ) ) {
							$link = '<a href="' .
								get_post_type_archive_link( 'wp-parser-hook' ) .
								str_replace( array( '&#8216;', '&#8217;' ), '', $link ) .
								'">' . esc_html( $link ) . '</a>';
						}
					}

					// Link to function: {@see esc_attr()}
					else {
						$link = '<a href="' .
							get_post_type_archive_link( 'wp-parser-function' ) .
							str_replace( '()', '', $link ) .
							'">' . esc_html( $link ) . '</a>';
					}

				}

				return $link;
			},
			$content
		);
	}

	/**
	 * Contains the template name to use for rendering this object.
	 * @var string
	 */
	protected $_template = '';

	/**
	 * Holds the templating object used to render strings for output.
	 * @var object
	 */
	protected $_templater;

	/**
	 * Holds a reference to the WP_Post object so it can be referenced for data.
	 * @var WP_Post
	 */
	protected $_post;

	/**
	 * The type of this reference object. Should be overriden in child classes. e.g. 'function'
	 * @var string
	 */
	protected $_type = '';

	/**
	 * Whether this reference object is callable or not.
	 * @var bool If the reference object is callable.
	 */
	protected $_callable = false;

	/**
	 * Internal cache for holding various calculated properties in memory for a load.
	 * @var array
	 */
	protected $_cache = array();

	/**
	 * The connections to look for when getting uses data.
	 * @var array
	 */
	protected $_uses_types = array();

	/**
	 * The connectsions to look for when getting used by data.
	 * @var array
	 */
	protected $used_by_types = array();

	/**
	 * Protected constructor to force use of the factory as well as create the templater.
	 *
	 * @return object The constructed reference object.
	 */
	protected function __construct( $post ) {
		$this->_post = $post;
	}

	/**
	 * Gets the templater to use for outputting this object's data.
	 *
	 * @return WPDoc_Hbs_Templater The prepared templating object.
	 */
	protected function _get_templater() {
		if ( ! $this->_templater ) {
			$engine = apply_filters( 'wpd_templater_object', 'WP_Doc\\Tools\\Templater\\Basic' );
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
		$data = apply_filters( 'wpd_template_data', $this );
		do_action( 'wpd_render_template', $template, $this );
		return $this->_get_templater()->render( $template, $data );
	}

	/**
	 * Magic getter for object-style access to various useful properties.
	 *
	 * @param  string $key The requested object key.
	 * @return mixed       A value corresponding to the requested key.
	 */
	public function __get( $key ) {
		switch ( $key ) {
			case 'post':
				return $this->_post;
				break;
			case 'callable':
				return $this->_callable;
				break;
			case 'url':
				return get_the_permalink( $this->_post );
				break;
			case 'type':
				return $this->_type;
				break;
			case 'summary':
				return $this->_cached_call( '_get_summary' );
				break;
			case 'description':
				return $this->_cached_call( '_get_description' );
				break;
			case 'namespace':
				return $this->_cached_call( '_get_namespace' );
				break;
			case 'return':
				return $this->_cached_call( '_get_return' );
				break;
			case 'signature':
				return $this->_cached_call( '_get_signature' );
				break;
			case 'params':
				return $this->_cached_call( '_get_params' );
				break;
			case 'source_file':
				return $this->_get_source_file();
				break;
			case 'start_line':
				return (int) get_post_meta( $this->_post->ID, '_wp-parser_line_num', true );
				break;
			case 'end_line':
				return (int) get_post_meta( $this->_post->ID, '_wp-parser_end_line_num', true );
				break;
			case 'uses':
				return $this->_cached_call( '_get_uses' );
				break;
			case 'used_by':
				return $this->_cached_call( '_get_used_by' );
				break;
			case 'changelog':
				return $this->_cached_call( '_get_changelog' );
				break;
			case 'deprecated':
				return $this->_cached_call( '_get_deprecated' );
				break;
			default:
				/**
				 * Allows hooking in to add possible keys on this object.
				 */
				return apply_filters( "wpd_ref_getter_$key", null, $this, $this->_post );
				break;
		}
	}

	/**
	 * Controls the JSON serialization of this object to make it eaiser to send to JS.
	 *
	 * @return array An array of data for JSON representation.
	 */
	public function jsonSerialize() {
		$representation = $this->get_basic_data();

		$advanced_keys = apply_filters( 'wpd_json_keys_advanced', array(
			'uses',
			'used_by',
		) );

		// Special treatment for nested items to ONLY get the basic keys.
		foreach ( $advanced_keys as $key ) {
			$representation[ $key ] = array();
			foreach( $this->$key as $nested_item ) {
				$representation[ $key ][] = apply_filters( 'wpd_json_nested_item', $nested_item->get_basic_data(), $this );
			}
		}

		return $representation;
	}

	/**
	 * Gets an array representation of the basic data for this object.
	 *
	 * Basic data includes keys that do not contain nested reference objects. This
	 * is useful in serializing these objects so that we don't find ourselves trying
	 * to serialize a very large tree that has recursion and will eventually cause
	 * php to fail due to memory usage.
	 *
	 * @return array The basic data for this object.
	 */
	public function get_basic_data() {
		$basic_keys = apply_filters( 'wpd_json_keys_basic', array(
			'callable',
			'url',
			'type',
			'summary',
			'description',
			'namespace',
			'return',
			'signature',
			'params',
			'source_file',
			'start_line',
			'end_line',
			'changelog',
			'deprecated',
		) );

		$basic_data = array();
		foreach ( $basic_keys as $key ) {
			$basic_data[ $key ] = $this->$key;
		}

		return apply_filters( 'wpd_json_basic_data', $basic_data, $this );
	}

	/**
	 * Proxies method calls, running them once per load and storing results in a cache.
	 *
	 * Arguments can be sent after the method name argument just as if you were sending
	 * those args to the called function itself. This funciton will take a variable
	 * argument list based on the needs. The only required arg is the method name itself.
	 *
	 * @param  string $method The method name.
	 * @return mixed          The return of the method call.
	 */
	protected function _cached_call( $method ) {
		$args = func_get_args();
		array_shift( $args );
		$key = md5( $method . serialize( $args ) );
		if ( ! isset( $this->_cache[ $key ] ) ) {
			$this->_cache[ $key ] = call_user_func_array( array( $this, $method ), $args );
		}
		return $this->_cache[ $key ];
	}

	/**
	 * Gets the summary of a reference object.
	 *
	 * The summary (aka short description) is stored in the 'post_excerpt' field.
	 *
	 * @return string             The summary for the reference object.
	 */
	protected function _get_summary() {
		$summary = $this->_post->post_excerpt;

		if ( $summary ) {
			add_filter( 'the_excerpt', 'htmlentities', 9 ); // Run before wpautop
			add_filter( 'the_excerpt', array( __CLASS__, 'remove_inline_internal' ) );
			$summary = apply_filters( 'get_the_excerpt', $summary );
			remove_filter( 'the_excerpt', 'htmlentities', 9 );
			remove_filter( 'the_excerpt', array( __CLASS__, 'remove_inline_internal' ) );
		}

		return $summary;
	}

	/**
	 * Gets the description of this reference object.
	 *
	 * The description is the post content. This is parsed out of the main long
	 * description of the reference object and supports markdown. Some cleanup
	 * is required to for the description content so we'll do that here making
	 * use of the_content filter and some other static processors.
	 *
	 * @return string The description of this reference object.
	 */
	protected function _get_description() {
		$description = apply_filters( 'the_content', get_the_content( $this->_post ) );
		$description = self::make_doclink_clickable( $description );
		$description = self::remove_inline_internal( $description );
		return $description;
	}

	/**
	 * Retrieve return type and description if available.
	 *
	 * If there is no explicit return value, or it is explicitly "void", then
	 * an empty string is returned. This rules out display of return type for
	 * classes, hooks, and non-returning functions.
	 *
	 * @return string
	 */
	protected function _get_return() {
		$post = $this->_post;
		$tags   = get_post_meta( $this->_post->ID, '_wp-parser_tags', true );
		$return = wp_filter_object_list( $tags, array( 'name' => 'return' ) );


		// If there is no explicit or non-"void" return value, don't display one.
		if ( empty( $return ) ) {
			return '';
		} else {
			$return      = array_shift( $return );
			$description = empty( $return['content'] ) ? '' : esc_html( $return['content'] );
			$type        = empty( $return['types'] ) ? '' : esc_html( implode( '|', $return['types'] ) );

			return array(
				'type' => $type,
				'description' => $description,
			);
		}
	}

	/**
	 * Retrieve signature name and arguments data for this reference object.
	 *
	 * @return void
	 */
	protected function _get_signature() {
		$args         = (array) get_post_meta( $this->_post->ID, '_wp-parser_args', true );
		$tags         = (array) get_post_meta( $this->_post->ID, '_wp-parser_tags', true );
		$signature    = array( 'name' => get_the_title( $this->_post ), 'args' => array(), 'return' => '' );

		// parse tags out into arg types.
		$types        = array();
		foreach ( $tags as $tag ) {
			if ( is_array( $tag ) && 'param' === $tag['name'] ) {
				$types[ $tag['variable'] ] = implode( '|', $tag['types'] );
			}
		}

		// parse the signature args to add types.
		foreach ( $args as $arg ) {
			// Validate arg is an array.
			if ( ! is_array( $arg ) ) {
				continue;
			}
			// Add the arg types if available.
			if ( ! empty( $arg['name'] ) && ! empty( $types[ $arg['name'] ] ) ) {
				$arg = array_merge( $arg, array( 'type' => $types[ $arg['name'] ] ) );
			}

			// Add this arg to the parsed args array
			$signature['args'][] = $arg;
		}

		return $signature;
	}

	/**
	 * Retrieve parameters as an array
	 *
	 * @return array              An array of the params for the reference object.
	 */
	protected function _get_params() {

		$post = $this->_post;
		$params = array();
		$args = get_post_meta( $post->ID, '_wp-parser_args', true );
		$tags = get_post_meta( $post->ID, '_wp-parser_tags', true );

		if ( $tags ) {
			$encountered_optional = false;
			foreach ( $tags as $tag ) {
				// Fix unintended markup introduced by parser.
				$tag = str_replace( array( '<strong>', '</strong>' ), '__', $tag );

				if ( ! empty( $tag['name'] ) && 'param' == $tag['name'] ) {
					$params[ $tag['variable'] ] = $tag;

					// Normalize spacing at beginning of hash notation params.
					if ( $tag['content'] && '{' == $tag['content'][0] ) {
						$tag['content'] = '{ ' . trim( substr( $tag['content'], 1 ) );
					}

					if ( strtolower( substr( $tag['content'], 0, 8 ) ) == "optional" ) {
						$params[ $tag['variable'] ]['required'] = 'Optional';
						$params[ $tag['variable'] ]['content'] = substr( $tag['content'], 9 );
						$encountered_optional = true;
					} elseif ( strtolower( substr( $tag['content'], 2, 9 ) ) == "optional." ) { // Hash notation param
						$params[ $tag['variable'] ]['required'] = 'Optional';
						$params[ $tag['variable'] ]['content'] = '{ ' . substr( $tag['content'], 12 );
						$encountered_optional = true;
					} elseif ( $encountered_optional ) {
						$params[ $tag['variable'] ]['required'] = 'Optional';
					} else {
						$params[ $tag['variable'] ]['required'] = 'Required';
					}
					$params[ $tag['variable'] ]['content'] = esc_html( $params[ $tag['variable'] ]['content'] );
					$params[ $tag['variable'] ]['content'] = self::make_doclink_clickable( $params[ $tag['variable'] ]['content'] );
					$params[ $tag['variable'] ]['content'] = $this->_parse_type_params( $params[ $tag['variable'] ]['content'] );
				}
			}
		}

		// Set up default args if available. If so try to clean up descriptions a bit.
		if ( $args ) {
			foreach ( $args as $arg ) {
				if ( ! empty( $arg['name'] ) && ! empty( $params[ $arg['name'] ] ) ) {
					$params[ $arg['name'] ]['default'] = $arg['default'];

					// If a default value was supplied and content is still a string
					if ( ! empty( $arg['default'] ) && ! is_string( $params[ $arg['name'] ]['content'] ) ) {
						// Ensure the parameter was marked as optional (sometimes they aren't
						// properly and explicitly documented as such)
						$params[ $arg['name'] ]['required'] = 'Optional';

						// If a known default is stated in the parameter's description, try to remove it
						// since the actual default value is displayed immediately following description.
						$default = htmlentities( $arg['default'] );
						$params[ $arg['name'] ]['content'] = str_replace( "default is {$default}.", '', $params[ $arg['name'] ]['content'] );
						$params[ $arg['name'] ]['content'] = str_replace( "Default {$default}.", '', $params[ $arg['name'] ]['content'] );

						// When the default is '', docs sometimes say "Default empty." or similar.
						if ( "''" == $arg['default'] ) {
							$params[ $arg['name'] ]['content'] = str_replace( "Default empty.", '', $params[ $arg['name'] ]['content'] );
							$params[ $arg['name'] ]['content'] = str_replace( "Default empty string.", '', $params[ $arg['name'] ]['content'] );

							// Only a few cases of this. Remove once core is fixed.
							$params[ $arg['name'] ]['content'] = str_replace( "default is empty string.", '', $params[ $arg['name'] ]['content'] );
						}
						// When the default is array(), docs sometimes say "Default empty array." or similar.
						elseif (  'array()' == $arg['default'] ) {
							$params[ $arg['name'] ]['content'] = str_replace( "Default empty array.", '', $params[ $arg['name'] ]['content'] );
							// Not as common.
							$params[ $arg['name'] ]['content'] = str_replace( "Default empty.", '', $params[ $arg['name'] ]['content'] );
						}
					}
				}

			}
		}

		return $params;
	}

	/**
	 * Parses array parameters set up with hash notation and the @type flag.
	 *
	 * This is a temporary measure until the parser parses the hash notation
	 * into component elements that the theme could then handle and style
	 * properly.
	 *
	 * @param  string       $text The content for the param.
	 * @return string|array       Untouched param content or an array of parsed content.
	 */
	protected function _parse_type_params( $text ) {
		// Don't do anything if this isn't a hash notation string.
		if ( empty( $text ) || '{' !== $text[0] ) {
			return $text;
		}

		$content  = array();
		$text     = trim( substr( $text, 1, -1 ) );
		$text     = str_replace( '@type', "\n@type", $text );

		$lines = explode( "\n", $text );
		for ( $i = 0; $i < count( $lines ); $i++ ) {
			$lines[ $i ] = preg_replace( '/\s+/', ' ', $lines[ $i ] );
			// Check if we have 4 pieces of content, and if not, this is not
			// a type line, so process differently.
			$pieces = explode( ' ', $lines[ $i ], 4 );
			if ( 4 > count( $pieces ) || '@type' !== $pieces[0] ) {
				if ( 1 < count( $content ) && '}' !== trim( $lines[ $i ] ) ) {
					// If we have a previous line and this line is not the closing
					// bracket, add this line to the previous line's description.
					$content[ $i - 1 ][ 'description'] .= $lines[ $i ];
				} else {
					continue;
				}
			}

			// Organize parsed content for consumption
			$content[ $i ] = array(
				'type' => $pieces[1],
				'name' => $pieces[2],
				'description' => $pieces[3],
			);
		}

		return $content;
	}

	/**
	 * Retrieve an array of reference objects for the posts the current post uses
	 *
	 * @return array An array of reference object for the posts the current post uses
	 */
	protected function _get_uses() {
		$items = new \WP_Query( array(
			'post_type'           => array( 'wp-parser-function', 'wp-parser-method', 'wp-parser-hook' ),
			'connected_type'      => $this->_uses_types,
			'connected_direction' => array( 'from', 'from', 'from' ),
			'connected_items'     => $this->_post->ID,
			'nopaging'            => true,
			'fields'              => 'ids',
		) );

		if ( $items->have_posts() ) {
			return array_map( '\\WP_Doc\\Tools\\Helpers\\_get_reference_object', $items->posts );
		} else {
			return array();
		}
	}

	/**
	 * Retrieve an array of reference objects for the posts the current post uses
	 *
	 * @return array An array of reference object for the posts the current post uses
	 */
	protected function _get_used_by() {
		$items = new \WP_Query( array(
			'post_type'           => array( 'wp-parser-function', 'wp-parser-method' ),
			'connected_type'      => $this->_used_by_types,
			'connected_direction' => array( 'to', 'to' ),
			'connected_items'     => $this->_post->ID,
			'nopaging'            => true,
			'fields'              => 'ids',
		) );

		if ( $items->have_posts() ) {
			return array_map( '\\WP_Doc\\Tools\\Helpers\\_get_reference_object', $items->posts );
		} else {
			return array();
		}
	}

	/**
	 * Retrieve name data for the current post.
	 *
	 * @return array Associative array of changelog data.
	 */
	protected function _get_namespace() {
		$post_id = $this->_post->ID;

		// Since terms assigned to the post.
		$namespace_terms = wp_get_post_terms( $post_id, 'wp-parser-namespace' );

		$data = array();
		if ( ! is_wp_error( $namespace_terms ) ) {
			$namespace_terms = $this->_sort_namespace( $namespace_terms );
			$data['text'] = implode( '\\', wp_list_pluck( $namespace_terms, 'name' ) );
			$data['terms'] = $namespace_terms;
		}

		return $data;
	}

	/**
	 * Sorts namespace terms based on post parent, removing terms not in the chain.
	 *
	 * @param  array $terms An array of namespace term objects.
	 * @return array        The sorted, filtered array of namespace terms.
	 */
	protected function _sort_namespace( $terms ) {
		$sorted_terms = array();
		$rejected_terms = array();
		$parent = 0;

		// Sorts the list of terms from 0 to last child naturally dropping off
		// terms that don't fall into that hierarchy.
		while( ! is_null( $term = array_shift( $terms ) ) ) {
			if ( $term->parent === $parent ) {
				$sorted_terms[] = $term;
				$parent = $term->term_id;
				// put the previously rejected terms back into play.
				$terms = array_merge( $rejected_terms, $terms );
			} else {
				// This is not the term we're looking for.
				$rejected_terms[] = $term;
			}
		}

		return $sorted_terms;
	}


	/**
	 * Retrieve changelog data for the current post.
	 *
	 * @return array Associative array of changelog data.
	 */
	protected function _get_changelog() {
		$post_id = $this->_post->ID;

		// Since terms assigned to the post.
		$since_terms = wp_get_post_terms( $post_id, 'wp-parser-since' );

		// Since data stored in meta.
		$since_meta = get_post_meta( $post_id, '_wp-parser_tags', true );

		$data = array();

		// Pair the term data with meta data.
		if ( ! is_wp_error( $since_terms ) ) {
			foreach ( $since_terms as $since_term ) {
				foreach ( $since_meta as $meta ) {
					if ( is_array( $meta ) && $since_term->name == $meta['content'] ) {
						$description = empty( $meta['description'] ) ? '' : $meta['description'];

						$data[ $since_term->name ] = array(
							'version'     => $since_term->name,
							'description' => $description,
							'since_url'   => get_term_link( $since_term )
						);
					}
				}
			}
		}
		return $data;
	}

	/**
	 * Retrieve deprecated flag
	 *
	 * @return string The deprecation method.
	 */
	protected function _get_deprecated() {
		$post_id        = $this->_post->ID;
		$tags           = get_post_meta( $post_id, '_wp-parser_tags', true );
		$all_deprecated = wp_filter_object_list( $tags, array( 'name' => 'deprecated' ) );

		if ( empty( $all_deprecated ) ) {
			return '';
		}

		$deprecated  = array_shift( $all_deprecated );
		// Multi-@deprecated may have been defined, with the second actually having the deprecation text.
		if ( empty( $deprecated['content'] ) ) {
			$deprecated  = array_shift( $all_deprecated );
		}
		return empty( $deprecated['content'] ) ? '' : $deprecated['content'];
	}

	/**
	 * Retrieve name of source file for this reference object.
	 *
	 * @return string The name of this reference object's source file.
	 */
	protected function _get_source_file() {
		$sf_object = $this->_cached_call( '_get_source_file_object' );
		return ( $sf_object ) ? $sf_object->name : '';
	}

	/**
	 * Gets the term object for this reference item's source file.
	 *
	 * @return stdClass The WP Term object for this item's source file.
	 */
	protected function _get_source_file_object() {
		$sf_terms = wp_get_post_terms( $this->_post->ID, 'wp-parser-source-file' );
		return empty( $sf_terms ) ? false : $sf_terms[0];
	}

	/**
	 * Retrieve URL to the source file archive for this reference object.
	 *
	 * @return string The URL to the source file archive page for this object.
	 */
	public function source_file_archive_link() {
		$sf_object = $this->_cached_call( '_get_source_file_object' );
		return ( $sf_object ) ? get_term_link( $sf_object ) : '';
	}

	/**
	 * Retrieve the root directory of the parsed WP code.
	 *
	 * If the option 'wp_parser_root_import_dir' (as set by the parser) is not
	 * set, then assume ABSPATH.
	 *
	 * @return string
	 */
	protected function _source_code_root_dir() {
		$root_dir = get_option( 'wp_parser_root_import_dir' );

		return $root_dir ? trailingslashit( $root_dir ) : ABSPATH;
	}

	/**
	 * Whether this reference object should have source code or not.
	 *
	 * @return bool
	 */
	public function has_source_code() {
		/**
		 * Allows customization of which types actually have source code. 
		 *
		 * @param array $types_with_source An array of post type slugs.
		 */
		$types_with_source_code = apply_filters( 'wpd_types_with_souce_code',
			array(
				'wp-parser-class',
				'wp-parser-method',
				'wp-parser-function'
			)
		);

		return in_array( get_post_type( $this->_post ), $types_with_source_code );
	}

	/**
	 * Retrieve source code for a function or method.
	 *
	 * @param  bool   $force_parse Optional. Force reparsing the source file for source code?
	 * @return string              The source code.
	 */
	public function source_code( $force_parse = false ) {
		// Get the source code stored in post meta.
		$meta_key = '_wp-parser_source_code';
		if ( ! $force_parse && $source_code = get_post_meta( $this->_post->ID, $meta_key, true ) ) {
			return $source_code;
		}

		/* Source code hasn't been stored in post meta, so parse source file to get it. */

		// Get the name of the source file.
		$source_file = $this->_get_source_file();

		// Get the start and end lines.
		$start_line = $this->start_line - 1;
		$end_line   = $this->end_line;

		// Sanity check to ensure proper conditions exist for parsing
		if ( ! $source_file || ! $start_line || ! $end_line || ( $start_line > $end_line ) ) {
			return '';
		}

		// Find just the relevant source code
		$source_code = '';
		$handle = @fopen( $this->_source_code_root_dir() . $source_file, 'r' );
		if ( $handle ) {
			$line = -1;
			$replacements = 1;
			$whitespace = '';
			while ( ! feof( $handle ) ) {
				$line++;
				$source_line = fgets( $handle );

				// Stop reading file once end_line is reached.
				if ( $line > $end_line ) {
					break;
				}

				// Skip lines until start_line is reached.
				if ( $line < $start_line ) {
					continue;
				}

				// Skip the last line if it is "endif;"; the parser includes the
				// endif of a if/endif wrapping typical of pluggable functions.
				if ( $line === $end_line && 'endif;' === trim( $source_line ) ) {
					continue;
				}

				// Capture the indent level of this block.
				if ( $line === $start_line ) {
					preg_match( '/^\s*/', $source_line, $matches );
					$whitespace = $matches[0];
					$whitespace_length = strlen( $whitespace );
				}

				// Strip whitespace from the line, only if it's the first thing we find.
				if ( $whitespace_length && 0 === strpos( $source_line,  $whitespace ) ) {
					$source_code .= substr( $source_line, $whitespace_length );
				} else {
					$source_code .= $source_line;
				}
			}
			fclose( $handle );
		}

		update_post_meta( $this->_post->ID, $meta_key, addslashes( $source_code ) );

		return $source_code;
	}


	/**
	 * Retrieve an explanation for the given post.
	 *
	 * @param int|WP_Post $post      Post ID or WP_Post object.
	 * @param bool        $published Optional. Whether to only retrieve the explanation if it's published.
	 *                               Default false.
	 * @return WP_Post|null WP_Post object for the Explanation, null otherwise.
	 */
	function get_explanation( $post, $published = false ) {
		if ( ! $post = get_post( $post ) ) {
			return null;
		}

		$args = array(
			'post_type'      => 'wporg_explanations',
			'post_parent'    => $post->ID,
			'no_found_rows'  => true,
			'posts_per_page' => 1,
		);

		if ( true === $published ) {
			$args['post_status'] = 'publish';
		}

		$explanation = get_children( $args, OBJECT );

		if ( empty( $explanation ) ) {
			return null;
		}

		$explanation = reset( $explanation );

		if ( ! $explanation ) {
			return null;
		}
		return $explanation;
	}

	/**
	 * Retrieve data from an explanation post field.
	 *
	 * Works only for published explanations.
	 *
	 * @see get_post_field()
	 *
	 * @param string      $field   Post field name.
	 * @param int|WP_Post $post    Post ID or object for the function, hook, class, or method post
	 *                             to retrieve an explanation field for.
	 * @param string      $context Optional. How to filter the field. Accepts 'raw', 'edit', 'db',
	 *                             or 'display'. Default 'display'.
	 * @return string The value of the post field on success, empty string on failure.
	 */
	function get_explanation_field( $field, $post, $context = 'display' ) {
		if ( ! $explanation = get_explanation( $post, $published = true ) ) {
			return '';
		}
		return get_post_field( $field, $explanation, $context );
	}
}
