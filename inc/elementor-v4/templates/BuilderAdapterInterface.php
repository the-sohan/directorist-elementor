<?php
/**
 * Builder adapter contract for Directorist template imports.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Templates;

interface BuilderAdapterInterface {

	/**
	 * Get the builder key this adapter handles.
	 *
	 * @return string
	 */
	public function get_builder_key(): string;

	/**
	 * Check whether this adapter can import the package.
	 *
	 * @param array<string,mixed> $manifest Package manifest.
	 * @return bool
	 */
	public function can_import_package( array $manifest ): bool;

	/**
	 * Run adapter-level preflight checks.
	 *
	 * @param array<string,mixed> $manifest Package manifest.
	 * @return array<string,mixed>
	 */
	public function preflight( array $manifest ): array;

	/**
	 * Import builder-specific items.
	 *
	 * @param ImportSession       $session Import session.
	 * @param string              $package_dir Extracted package directory.
	 * @param array<string,mixed> $manifest Package manifest.
	 * @param array<string,mixed> $args Import args.
	 * @return array<string,mixed>
	 */
	public function import_builder_items( ImportSession $session, string $package_dir, array $manifest, array $args = [] ): array;

	/**
	 * Clear builder caches for imported items.
	 *
	 * @param ImportSession $session Import session.
	 * @return void
	 */
	public function clear_caches( ImportSession $session ): void;
}
