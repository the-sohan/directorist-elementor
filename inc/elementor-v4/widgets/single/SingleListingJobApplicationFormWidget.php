<?php
/**
 * Single listing job application form widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Single;

use DirectoristElementor\ElementorV4\Widgets\Base\AbstractSingleExtensionFieldTemplateWidget;

class SingleListingJobApplicationFormWidget extends AbstractSingleExtensionFieldTemplateWidget {

	public function get_name(): string {
		return 'directorist_single_listing_job_application_form';
	}

	public function get_title(): string {
		return __( 'Job Application Form', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-form-horizontal';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'single', 'job', 'application', 'form' ] );
	}

	protected function get_extension_slug(): string {
		return 'job_manager';
	}

	protected function get_field_widget_name(): string {
		return 'dirjob_apply_form';
	}

	protected function get_field_template_key(): string {
		return 'single/fields/dirjob_apply_form';
	}

	protected function get_field_wrapper_selector(): string {
		return '.directorist-info-item-dirjob_apply_form';
	}

	protected function get_field_body_selector(): string {
		return '.directorist-info-item-dirjob_apply_form';
	}

	protected function get_field_icon_selector(): string {
		return '';
	}

	protected function register_additional_extension_field_style_controls(): void {
		$this->register_extension_button_style_section(
			'section_job_application_button_style',
			__( 'Application Button', 'directorist-elementor' ),
			'.directorist-info-item-dirjob_apply_form .directorist-btn, .directorist-info-item-dirjob_apply_form a',
			'job_application_button'
		);
	}
}
