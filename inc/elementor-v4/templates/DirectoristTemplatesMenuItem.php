<?php
/**
 * Elementor Editor One menu item for Directorist templates.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Templates;

use Elementor\Core\Admin\EditorOneMenu\Interfaces\Menu_Item_Interface;
use Elementor\Modules\EditorOne\Classes\Menu_Config;

class DirectoristTemplatesMenuItem implements Menu_Item_Interface {

	/**
	 * Get required capability.
	 *
	 * @return string
	 */
	public function get_capability(): string {
		return 'manage_options';
	}

	/**
	 * Get menu label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Directorist Templates', 'directorist-elementor' );
	}

	/**
	 * Get parent slug for Elementor hidden submenu registration.
	 *
	 * @return string
	 */
	public function get_parent_slug(): string {
		return Menu_Config::ELEMENTOR_MENU_SLUG;
	}

	/**
	 * Check item visibility.
	 *
	 * @return bool
	 */
	public function is_visible(): bool {
		return current_user_can( $this->get_capability() );
	}

	/**
	 * Get item position inside the Templates group.
	 *
	 * @return int
	 */
	public function get_position(): int {
		return 35;
	}

	/**
	 * Get menu slug.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return TemplateImportBootstrap::get_app_url();
	}

	/**
	 * Get Elementor Editor One group id.
	 *
	 * @return string
	 */
	public function get_group_id(): string {
		return Menu_Config::TEMPLATES_GROUP_ID;
	}

}
