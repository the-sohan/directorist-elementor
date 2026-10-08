<?php
/**
 * Runtime render context stack.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Context;

use DirectoristElementor\Traits\Singleton;

class RenderContext {
	use Singleton;

	/**
	 * Loop context stack.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	protected array $loop_context_stack = [];

	/**
	 * Listing context stack.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	protected array $listing_context_stack = [];

	/**
	 * Taxonomy card context stack.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	protected array $taxonomy_context_stack = [];

	/**
	 * Pricing plan context stack.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	protected array $pricing_plan_context_stack = [];

	/**
	 * Author profile context stack.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	protected array $author_profile_context_stack = [];

	/**
	 * Search form context stack.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	protected array $search_form_context_stack = [];

	/**
	 * Push loop context.
	 *
	 * @param string $instance_id Instance id.
	 * @param array  $query_args Query args.
	 * @param string $active_view Active view.
	 * @param int    $active_directory Active directory type id.
	 * @param array  $extra_context Additional loop runtime state.
	 * @return array<string,mixed>
	 */
	public function push_loop_context(
		string $instance_id,
		array $query_args = [],
		string $active_view = 'grid',
		int $active_directory = 0,
		array $extra_context = []
	): array {
		$context = array_merge(
			[
			'instance_id'       => sanitize_key( $instance_id ),
			'query_args'        => $query_args,
			'active_view'       => in_array( $active_view, [ 'grid', 'list', 'map' ], true ) ? $active_view : 'grid',
			'active_directory'  => max( 0, $active_directory ),
			],
			$extra_context
		);

		$this->loop_context_stack[] = $context;

		return $context;
	}

	/**
	 * Push listing context.
	 *
	 * @param int $listing_id Listing id.
	 * @param int $index Loop index.
	 * @param int $directory_type_id Directory type id.
	 * @return array<string,int>
	 */
	public function push_listing_context( int $listing_id, int $index = 0, int $directory_type_id = 0 ): array {
		$context = [
			'listing_id'         => max( 0, $listing_id ),
			'index'              => max( 0, $index ),
			'directory_type_id'  => max( 0, $directory_type_id ),
		];

		$this->listing_context_stack[] = $context;

		return $context;
	}

	/**
	 * Push taxonomy card context.
	 *
	 * @param string $scope Taxonomy card scope: category or location.
	 * @param array  $item Taxonomy item payload from Directorist core.
	 * @param int    $index Item index.
	 * @param array  $extra_context Additional context.
	 * @return array<string,mixed>
	 */
	public function push_taxonomy_context( string $scope, array $item = [], int $index = 0, array $extra_context = [] ): array {
		$scope = sanitize_key( $scope );

		if ( ! in_array( $scope, [ 'category', 'location' ], true ) ) {
			$scope = 'category';
		}

		$context = array_merge(
			[
				'scope' => $scope,
				'item'  => $item,
				'index' => max( 0, $index ),
			],
			$extra_context
		);

		$this->taxonomy_context_stack[] = $context;

		return $context;
	}

	/**
	 * Push pricing plan context.
	 *
	 * @param int   $plan_id Plan id.
	 * @param int   $index Plan index.
	 * @param array $extra_context Additional context.
	 * @return array<string,mixed>
	 */
	public function push_pricing_plan_context( int $plan_id, int $index = 0, array $extra_context = [] ): array {
		$context = array_merge(
			[
				'plan_id' => max( 0, $plan_id ),
				'index'   => max( 0, $index ),
			],
			$extra_context
		);

		$this->pricing_plan_context_stack[] = $context;

		return $context;
	}

	/**
	 * Push author profile context.
	 *
	 * @param array $author Author display data.
	 * @param int   $listing_id Listing id.
	 * @param array $extra_context Additional context.
	 * @return array<string,mixed>
	 */
	public function push_author_profile_context( array $author, int $listing_id = 0, array $extra_context = [] ): array {
		$context = array_merge(
			[
				'author'     => $author,
				'listing_id' => max( 0, $listing_id ),
			],
			$extra_context
		);

		$this->author_profile_context_stack[] = $context;

		return $context;
	}

	/**
	 * Push search form context.
	 *
	 * @param array $context Search form context.
	 * @return array<string,mixed>
	 */
	public function push_search_form_context( array $context ): array {
		$this->search_form_context_stack[] = $context;

		return $context;
	}

	/**
	 * Get current loop context.
	 *
	 * @return array<string,mixed>
	 */
	public function current_loop_context(): array {
		$context = end( $this->loop_context_stack );

		return is_array( $context ) ? $context : [];
	}

	/**
	 * Get current listing context.
	 *
	 * @return array<string,mixed>
	 */
	public function current_listing_context(): array {
		$context = end( $this->listing_context_stack );

		return is_array( $context ) ? $context : [];
	}

	/**
	 * Get current taxonomy card context.
	 *
	 * @return array<string,mixed>
	 */
	public function current_taxonomy_context(): array {
		$context = end( $this->taxonomy_context_stack );

		return is_array( $context ) ? $context : [];
	}

	/**
	 * Get current pricing plan context.
	 *
	 * @return array<string,mixed>
	 */
	public function current_pricing_plan_context(): array {
		$context = end( $this->pricing_plan_context_stack );

		return is_array( $context ) ? $context : [];
	}

	/**
	 * Get current author profile context.
	 *
	 * @return array<string,mixed>
	 */
	public function current_author_profile_context(): array {
		$context = end( $this->author_profile_context_stack );

		return is_array( $context ) ? $context : [];
	}

	/**
	 * Get current search form context.
	 *
	 * @return array<string,mixed>
	 */
	public function current_search_form_context(): array {
		$context = end( $this->search_form_context_stack );

		return is_array( $context ) ? $context : [];
	}

	/**
	 * Pop listing context.
	 *
	 * @return array<string,mixed>
	 */
	public function pop_listing_context(): array {
		$context = array_pop( $this->listing_context_stack );

		return is_array( $context ) ? $context : [];
	}

	/**
	 * Pop taxonomy card context.
	 *
	 * @return array<string,mixed>
	 */
	public function pop_taxonomy_context(): array {
		$context = array_pop( $this->taxonomy_context_stack );

		return is_array( $context ) ? $context : [];
	}

	/**
	 * Pop pricing plan context.
	 *
	 * @return array<string,mixed>
	 */
	public function pop_pricing_plan_context(): array {
		$context = array_pop( $this->pricing_plan_context_stack );

		return is_array( $context ) ? $context : [];
	}

	/**
	 * Pop search form context.
	 *
	 * @return array<string,mixed>
	 */
	public function pop_search_form_context(): array {
		$context = array_pop( $this->search_form_context_stack );

		return is_array( $context ) ? $context : [];
	}

	/**
	 * Pop author profile context.
	 *
	 * @return array<string,mixed>
	 */
	public function pop_author_profile_context(): array {
		$context = array_pop( $this->author_profile_context_stack );

		return is_array( $context ) ? $context : [];
	}

	/**
	 * Pop loop context.
	 *
	 * @return array<string,mixed>
	 */
	public function pop_loop_context(): array {
		$context = array_pop( $this->loop_context_stack );

		return is_array( $context ) ? $context : [];
	}
}
