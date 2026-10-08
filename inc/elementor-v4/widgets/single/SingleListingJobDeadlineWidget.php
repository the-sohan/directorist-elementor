<?php
/**
 * Single listing job deadline widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Single;

use DirectoristElementor\ElementorV4\Widgets\Base\AbstractSingleExtensionFieldTemplateWidget;

class SingleListingJobDeadlineWidget extends AbstractSingleExtensionFieldTemplateWidget {

	public function get_name(): string {
		return 'directorist_single_listing_job_deadline';
	}

	public function get_title(): string {
		return __( 'Job Deadline', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-clock-o';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'single', 'job', 'deadline' ] );
	}

	protected function get_extension_slug(): string {
		return 'job_manager';
	}

	protected function get_field_widget_name(): string {
		return 'dirjob_deadline';
	}

	protected function get_field_template_key(): string {
		return 'single/fields/dirjob_deadline';
	}

	protected function get_field_wrapper_selector(): string {
		return '.directorist-info-item-dirjob_deadline';
	}
}
