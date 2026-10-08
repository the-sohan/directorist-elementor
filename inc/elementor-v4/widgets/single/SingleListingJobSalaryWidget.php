<?php
/**
 * Single listing job salary widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Single;

use DirectoristElementor\ElementorV4\Widgets\Base\AbstractSingleExtensionFieldTemplateWidget;

class SingleListingJobSalaryWidget extends AbstractSingleExtensionFieldTemplateWidget {

	public function get_name(): string {
		return 'directorist_single_listing_job_salary';
	}

	public function get_title(): string {
		return __( 'Job Salary', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-price-table';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'single', 'job', 'salary' ] );
	}

	protected function get_extension_slug(): string {
		return 'job_manager';
	}

	protected function get_field_widget_name(): string {
		return 'dirjob_salary';
	}

	protected function get_field_template_key(): string {
		return 'single/fields/dirjob_salary';
	}

	protected function get_field_wrapper_selector(): string {
		return '.directorist-info-item-dirjob_salary';
	}
}
