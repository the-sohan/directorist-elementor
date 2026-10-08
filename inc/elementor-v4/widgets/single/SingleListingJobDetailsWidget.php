<?php
/**
 * Single listing job details widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Single;

use DirectoristElementor\ElementorV4\Widgets\Base\AbstractSingleExtensionSectionTemplateWidget;

class SingleListingJobDetailsWidget extends AbstractSingleExtensionSectionTemplateWidget {

	public function get_name(): string {
		return 'directorist_single_listing_job_details';
	}

	public function get_title(): string {
		return __( 'Job Details', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-post-info';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'single', 'job', 'details' ] );
	}

	protected function get_extension_slug(): string {
		return 'job_manager';
	}

	protected function get_section_widget_name(): string {
		return 'dirjob_job_details';
	}

	protected function get_section_template_key(): string {
		return 'single/section-dirjob_job_details';
	}

	protected function get_default_section_icon(): string {
		return 'las la-briefcase';
	}

	protected function get_section_wrapper_selector(): string {
		return '.directorist-card-listing-description';
	}

	protected function get_section_title_selector(): string {
		return '.directorist-card-listing-description .directorist-card__header--title';
	}

	protected function get_section_body_selector(): string {
		return '.directorist-card-listing-description .directorist-card__body';
	}

	protected function register_additional_extension_section_style_controls( string $section_id ): void {
		$this->register_extension_box_style_section(
			$section_id . '_job_item',
			__( 'Job Detail Item', 'directorist-elementor' ),
			'.directorist-card-listing-description .directorist-info-item, .directorist-card-listing-description li',
			'job_detail_item'
		);

		$this->register_extension_text_style_section(
			$section_id . '_job_label',
			__( 'Job Detail Label', 'directorist-elementor' ),
			'.directorist-card-listing-description .directorist-info-item__label, .directorist-card-listing-description strong',
			'job_detail_label'
		);
	}
}
