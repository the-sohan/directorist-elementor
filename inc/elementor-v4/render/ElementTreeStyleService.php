<?php
/**
 * Elementor style compiler for raw nested element trees.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Render;

use DirectoristElementor\Traits\Singleton;
use Elementor\Core\Files\CSS\Post;

/**
 * Compile a supplied element tree through Elementor's native CSS parser.
 */
class ElementTreeStyleService {
	use Singleton;

	/**
	 * Render scoped CSS for raw Elementor children.
	 *
	 * @param array<int,array<string,mixed>> $elements Raw elements.
	 * @param int                            $post_id Owning Elementor document.
	 * @param string                         $scope Style-fragment scope identifier.
	 * @return string
	 */
	public function render_style_tag( array $elements, int $post_id = 0, string $scope = 'element-tree' ): string {
		if ( empty( $elements ) || ! class_exists( Post::class ) ) {
			return '';
		}

		$post_id = $post_id > 0 ? $post_id : $this->resolve_current_document_id();
		if ( $post_id <= 0 ) {
			return '';
		}

		$normalized = [];
		foreach ( $elements as $element ) {
			if ( is_array( $element ) ) {
				$normalized[] = ElementTreeRenderService::get_instance()->normalize_element_data( $element );
			}
		}

		if ( empty( $normalized ) ) {
			return '';
		}

		$css_file = new RawElementTreeCssFile( $post_id, $normalized );
		$css      = trim( (string) $css_file->get_content() );

		if ( '' === $css ) {
			return '';
		}

		return sprintf(
			'<style class="directorist-elementor-branch-styles" data-direl-style-scope="%1$s" data-elementor-post-id="%2$d">%3$s</style>',
			esc_attr( sanitize_key( $scope ) ),
			$post_id,
			$css
		);
	}

	/**
	 * Resolve the Elementor document currently being rendered.
	 *
	 * @return int
	 */
	protected function resolve_current_document_id(): int {
		if ( class_exists( '\\Elementor\\Plugin' ) ) {
			$document = \Elementor\Plugin::$instance->documents->get_current();
			if ( $document ) {
				if ( method_exists( $document, 'get_main_id' ) ) {
					$post_id = absint( $document->get_main_id() );
					if ( $post_id > 0 ) {
						return $post_id;
					}
				}

				if ( method_exists( $document, 'get_id' ) ) {
					$post_id = absint( $document->get_id() );
					if ( $post_id > 0 ) {
						return $post_id;
					}
				}
			}
		}

		foreach ( [ 'editor_post_id', 'post_id', 'post' ] as $request_key ) {
			if ( ! empty( $_REQUEST[ $request_key ] ) ) {
				$post_id = absint( wp_unslash( $_REQUEST[ $request_key ] ) );
				if ( $post_id > 0 ) {
					return $post_id;
				}
			}
		}

		return absint( get_the_ID() );
	}
}

/**
 * Elementor Post CSS adapter backed by an in-memory element tree.
 */
class RawElementTreeCssFile extends Post {

	/**
	 * Raw elements supplied by the Directorist renderer.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	protected array $raw_elements;

	/**
	 * Constructor.
	 *
	 * @param int                            $post_id Owning document id.
	 * @param array<int,array<string,mixed>> $raw_elements Raw elements.
	 */
	public function __construct( int $post_id, array $raw_elements ) {
		$this->raw_elements = $raw_elements;
		parent::__construct( $post_id );
	}

	/**
	 * Return the supplied tree instead of reading document metadata.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	protected function get_data() {
		return $this->raw_elements;
	}

	/**
	 * Use a private parse hook namespace for transient branch CSS.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'directorist-element-tree';
	}
}
