<?php
/**
 * Elementor widget registrar.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4;

use DirectoristElementor\ElementorV4\Widgets\Extensions\SocialFieldsWidget;
use DirectoristElementor\ElementorV4\Widgets\Archive\AllCategoriesWidget;
use DirectoristElementor\ElementorV4\Widgets\Archive\AllListingsWidget;
use DirectoristElementor\ElementorV4\Widgets\Archive\AllLocationsWidget;
use DirectoristElementor\ElementorV4\Widgets\Author\AuthorProfileAvatarWidget;
use DirectoristElementor\ElementorV4\Widgets\Author\AuthorProfileBioWidget;
use DirectoristElementor\ElementorV4\Widgets\Author\AuthorProfileButtonWidget;
use DirectoristElementor\ElementorV4\Widgets\Author\AuthorProfileContactAddressWidget;
use DirectoristElementor\ElementorV4\Widgets\Author\AuthorProfileContactEmailWidget;
use DirectoristElementor\ElementorV4\Widgets\Author\AuthorProfileContactPhoneWidget;
use DirectoristElementor\ElementorV4\Widgets\Author\AuthorProfileContactWebsiteWidget;
use DirectoristElementor\ElementorV4\Widgets\Author\AuthorProfileListingCountWidget;
use DirectoristElementor\ElementorV4\Widgets\Author\AuthorProfileMembershipWidget;
use DirectoristElementor\ElementorV4\Widgets\Author\AuthorProfileMessageButtonWidget;
use DirectoristElementor\ElementorV4\Widgets\Author\AuthorProfileNameWidget;
use DirectoristElementor\ElementorV4\Widgets\Author\AuthorProfileRatingWidget;
use DirectoristElementor\ElementorV4\Widgets\Author\AuthorProfileSocialLinksWidget;
use DirectoristElementor\ElementorV4\Widgets\FieldsCustom\ListingCardCustomCheckboxWidget;
use DirectoristElementor\ElementorV4\Widgets\FieldsCustom\ListingCardCustomButtonWidget;
use DirectoristElementor\ElementorV4\Widgets\FieldsCustom\ListingCardCustomColorWidget;
use DirectoristElementor\ElementorV4\Widgets\FieldsCustom\ListingCardCustomDateWidget;
use DirectoristElementor\ElementorV4\Widgets\FieldsCustom\ListingCardCustomFileWidget;
use DirectoristElementor\ElementorV4\Widgets\FieldsCustom\ListingCardCustomHtmlWidget;
use DirectoristElementor\ElementorV4\Widgets\FieldsCustom\ListingCardCustomNumberWidget;
use DirectoristElementor\ElementorV4\Widgets\FieldsCustom\ListingCardCustomRadioWidget;
use DirectoristElementor\ElementorV4\Widgets\FieldsCustom\ListingCardCustomSelectWidget;
use DirectoristElementor\ElementorV4\Widgets\FieldsCustom\ListingCardCustomTextWidget;
use DirectoristElementor\ElementorV4\Widgets\FieldsCustom\ListingCardCustomTextareaWidget;
use DirectoristElementor\ElementorV4\Widgets\FieldsCustom\ListingCardCustomTimeWidget;
use DirectoristElementor\ElementorV4\Widgets\FieldsCustom\ListingCardCustomUrlWidget;
use DirectoristElementor\ElementorV4\Widgets\FieldsPreset\ListingCardAddressWidget;
use DirectoristElementor\ElementorV4\Widgets\FieldsPreset\ListingCardBadgeFavoriteWidget;
use DirectoristElementor\ElementorV4\Widgets\FieldsPreset\ListingCardBadgeCustomWidget;
use DirectoristElementor\ElementorV4\Widgets\FieldsPreset\ListingCardBadgeFeaturedWidget;
use DirectoristElementor\ElementorV4\Widgets\FieldsPreset\ListingCardBadgeNewWidget;
use DirectoristElementor\ElementorV4\Widgets\FieldsPreset\ListingCardBadgePopularWidget;
use DirectoristElementor\ElementorV4\Widgets\FieldsPreset\ListingCardBusinessHoursWidget;
use DirectoristElementor\ElementorV4\Widgets\FieldsPreset\ListingCardCategoryWidget;
use DirectoristElementor\ElementorV4\Widgets\FieldsPreset\ListingDescriptionWidget;
use DirectoristElementor\ElementorV4\Widgets\FieldsPreset\ListingCardExcerptWidget;
use DirectoristElementor\ElementorV4\Widgets\FieldsPreset\ListingCardEmailWidget;
use DirectoristElementor\ElementorV4\Widgets\FieldsPreset\ListingCardFaxWidget;
use DirectoristElementor\ElementorV4\Widgets\FieldsPreset\ListingCardImagesSliderWidget;
use DirectoristElementor\ElementorV4\Widgets\FieldsPreset\ListingCardLocationWidget;
use DirectoristElementor\ElementorV4\Widgets\FieldsPreset\ListingCardPhoneWidget;
use DirectoristElementor\ElementorV4\Widgets\FieldsPreset\ListingCardPhoneTwoWidget;
use DirectoristElementor\ElementorV4\Widgets\FieldsPreset\ListingCardPostedDateWidget;
use DirectoristElementor\ElementorV4\Widgets\FieldsPreset\ListingCardPricingWidget;
use DirectoristElementor\ElementorV4\Widgets\FieldsPreset\ListingCardRatingWidget;
use DirectoristElementor\ElementorV4\Widgets\FieldsPreset\ListingCardSocialInfoWidget;
use DirectoristElementor\ElementorV4\Widgets\FieldsPreset\ListingCardThumbnailWidget;
use DirectoristElementor\ElementorV4\Widgets\FieldsPreset\ListingCardTitleWidget;
use DirectoristElementor\ElementorV4\Widgets\FieldsPreset\ListingCardUserAvatarWidget;
use DirectoristElementor\ElementorV4\Widgets\FieldsPreset\ListingCardVideoWidget;
use DirectoristElementor\ElementorV4\Widgets\FieldsPreset\ListingCardViewCountWidget;
use DirectoristElementor\ElementorV4\Widgets\FieldsPreset\ListingCardWebsiteWidget;
use DirectoristElementor\ElementorV4\Widgets\FieldsPreset\ListingCardZipCodeWidget;
use DirectoristElementor\ElementorV4\Widgets\Loop\ListingsFiltersWidget;
use DirectoristElementor\ElementorV4\Widgets\Loop\ListingsHeaderWidget;
use DirectoristElementor\ElementorV4\Widgets\Loop\ListingsPaginationWidget;
use DirectoristElementor\ElementorV4\Widgets\Pricing\PricingPlanActionButtonWidget;
use DirectoristElementor\ElementorV4\Widgets\Pricing\PricingPlanActiveBadgeWidget;
use DirectoristElementor\ElementorV4\Widgets\Pricing\PricingPlanDescriptionWidget;
use DirectoristElementor\ElementorV4\Widgets\Pricing\PricingPlanDurationWidget;
use DirectoristElementor\ElementorV4\Widgets\Pricing\PricingPlanFeaturesWidget;
use DirectoristElementor\ElementorV4\Widgets\Pricing\PricingPlanPriceWidget;
use DirectoristElementor\ElementorV4\Widgets\Pricing\PricingPlanRecommendedBadgeWidget;
use DirectoristElementor\ElementorV4\Widgets\Pricing\PricingPlanTitleWidget;
use DirectoristElementor\ElementorV4\Widgets\Pricing\PricingPlanTrialNoteWidget;
use DirectoristElementor\ElementorV4\Widgets\Pricing\PricingPlanTypeBadgeWidget;
use DirectoristElementor\ElementorV4\Widgets\Search\SearchDirectoryTypesWidget;
use DirectoristElementor\ElementorV4\Widgets\Search\SearchMoreFiltersButtonWidget;
use DirectoristElementor\ElementorV4\Widgets\Search\SearchSubmitButtonWidget;
use DirectoristElementor\ElementorV4\Widgets\Single\SingleListingAuthorInfoWidget;
use DirectoristElementor\ElementorV4\Widgets\Single\SingleListingBackWidget;
use DirectoristElementor\ElementorV4\Widgets\Single\SingleListingBookmarkWidget;
use DirectoristElementor\ElementorV4\Widgets\Single\SingleListingBookingWidget;
use DirectoristElementor\ElementorV4\Widgets\Single\SingleListingClaimListingWidget;
use DirectoristElementor\ElementorV4\Widgets\Single\SingleListingCompareWidget;
use DirectoristElementor\ElementorV4\Widgets\Single\SingleListingContactOwnerFormWidget;
use DirectoristElementor\ElementorV4\Widgets\Single\SingleListingCustomContentWidget;
use DirectoristElementor\ElementorV4\Widgets\Single\SingleListingDigitalDownloadsWidget;
use DirectoristElementor\ElementorV4\Widgets\Single\SingleListingDirectoryLinkingWidget;
use DirectoristElementor\ElementorV4\Widgets\Single\SingleListingFaqWidget;
use DirectoristElementor\ElementorV4\Widgets\Single\SingleListingFormgentFormWidget;
use DirectoristElementor\ElementorV4\Widgets\Single\SingleListingGalleryWidget;
use DirectoristElementor\ElementorV4\Widgets\Single\SingleListingJobApplicationFormWidget;
use DirectoristElementor\ElementorV4\Widgets\Single\SingleListingJobDeadlineWidget;
use DirectoristElementor\ElementorV4\Widgets\Single\SingleListingJobDetailsWidget;
use DirectoristElementor\ElementorV4\Widgets\Single\SingleListingJobOpenPositionWidget;
use DirectoristElementor\ElementorV4\Widgets\Single\SingleListingJobSalaryWidget;
use DirectoristElementor\ElementorV4\Widgets\Single\SingleListingJobTypeWidget;
use DirectoristElementor\ElementorV4\Widgets\Single\SingleListingLiveChatWidget;
use DirectoristElementor\ElementorV4\Widgets\Single\SingleListingMapWidget;
use DirectoristElementor\ElementorV4\Widgets\Single\SingleListingRelatedListingsWidget;
use DirectoristElementor\ElementorV4\Widgets\Single\SingleListingReportWidget;
use DirectoristElementor\ElementorV4\Widgets\Single\SingleListingReviewWidget;
use DirectoristElementor\ElementorV4\Widgets\Single\SingleListingShareWidget;
use DirectoristElementor\ElementorV4\Widgets\Single\SingleListingTagWidget;
use DirectoristElementor\ElementorV4\Widgets\Taxonomy\CategoryCardButtonWidget;
use DirectoristElementor\ElementorV4\Widgets\Taxonomy\CategoryCardCountWidget;
use DirectoristElementor\ElementorV4\Widgets\Taxonomy\CategoryCardDescriptionWidget;
use DirectoristElementor\ElementorV4\Widgets\Taxonomy\CategoryCardIconWidget;
use DirectoristElementor\ElementorV4\Widgets\Taxonomy\CategoryCardImageWidget;
use DirectoristElementor\ElementorV4\Widgets\Taxonomy\CategoryCardTitleWidget;
use DirectoristElementor\ElementorV4\Widgets\Taxonomy\LocationCardButtonWidget;
use DirectoristElementor\ElementorV4\Widgets\Taxonomy\LocationCardCountWidget;
use DirectoristElementor\ElementorV4\Widgets\Taxonomy\LocationCardDescriptionWidget;
use DirectoristElementor\ElementorV4\Widgets\Taxonomy\LocationCardIconWidget;
use DirectoristElementor\ElementorV4\Widgets\Taxonomy\LocationCardImageWidget;
use DirectoristElementor\ElementorV4\Widgets\Taxonomy\LocationCardTitleWidget;
use DirectoristElementor\Traits\Singleton;

class WidgetRegistrar {
	use Singleton;

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	protected function __construct() {
		add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );

		if ( class_exists( '\\Elementor\\Plugin' ) ) {
			$plugin = \Elementor\Plugin::instance();

			if ( isset( $plugin->widgets_manager ) ) {
				$this->register_widgets( $plugin->widgets_manager );
			}
		}
	}

	/**
	 * Register initial widget surface.
	 *
	 * @param object $widgets_manager Elementor widgets manager.
	 * @return void
	 */
	public function register_widgets( $widgets_manager ): void {
		if ( ! Compatibility::get_instance()->is_supported() || ! FeatureDependencyManager::get_instance()->are_active() ) {
			return;
		}

		$widgets = [
			new AllListingsWidget(),
			new AllCategoriesWidget(),
			new AllLocationsWidget(),
			new SearchDirectoryTypesWidget(),
			new SearchMoreFiltersButtonWidget(),
			new SearchSubmitButtonWidget(),
			new ListingsHeaderWidget(),
			new ListingsFiltersWidget(),
			new ListingsPaginationWidget(),
			new PricingPlanTitleWidget(),
			new PricingPlanDescriptionWidget(),
			new PricingPlanPriceWidget(),
			new PricingPlanDurationWidget(),
			new PricingPlanTrialNoteWidget(),
			new PricingPlanTypeBadgeWidget(),
			new PricingPlanRecommendedBadgeWidget(),
			new PricingPlanActiveBadgeWidget(),
			new PricingPlanFeaturesWidget(),
			new PricingPlanActionButtonWidget(),
			new AuthorProfileAvatarWidget(),
			new AuthorProfileNameWidget(),
			new AuthorProfileMembershipWidget(),
			new AuthorProfileRatingWidget(),
			new AuthorProfileListingCountWidget(),
			new AuthorProfileBioWidget(),
			new AuthorProfileContactAddressWidget(),
			new AuthorProfileContactPhoneWidget(),
			new AuthorProfileContactEmailWidget(),
			new AuthorProfileContactWebsiteWidget(),
			new AuthorProfileSocialLinksWidget(),
			new AuthorProfileButtonWidget(),
			new AuthorProfileMessageButtonWidget(),
			new CategoryCardImageWidget(),
			new CategoryCardIconWidget(),
			new CategoryCardTitleWidget(),
			new CategoryCardCountWidget(),
			new CategoryCardDescriptionWidget(),
			new CategoryCardButtonWidget(),
			new LocationCardImageWidget(),
			new LocationCardIconWidget(),
			new LocationCardTitleWidget(),
			new LocationCardCountWidget(),
			new LocationCardDescriptionWidget(),
			new LocationCardButtonWidget(),
			new ListingCardAddressWidget(),
			new ListingCardCategoryWidget(),
			new ListingCardPricingWidget(),
			new ListingCardPhoneWidget(),
			new ListingCardPhoneTwoWidget(),
			new ListingCardEmailWidget(),
			new ListingCardFaxWidget(),
			new ListingCardWebsiteWidget(),
			new ListingCardZipCodeWidget(),
			new ListingCardPostedDateWidget(),
			new ListingCardViewCountWidget(),
			new ListingCardRatingWidget(),
			new ListingCardSocialInfoWidget(),
			new ListingCardUserAvatarWidget(),
			new ListingCardBadgeCustomWidget(),
			new ListingCardBadgeFeaturedWidget(),
			new ListingCardBadgeFavoriteWidget(),
			new ListingCardBadgeNewWidget(),
			new ListingCardBadgePopularWidget(),
			new ListingCardImagesSliderWidget(),
			new ListingCardVideoWidget(),
			new ListingCardBusinessHoursWidget(),
			new ListingCardThumbnailWidget(),
			new ListingCardTitleWidget(),
			new ListingCardExcerptWidget(),
			new ListingDescriptionWidget(),
			new ListingCardLocationWidget(),
			new SocialFieldsWidget(),
			new ListingCardCustomTextWidget(),
			new ListingCardCustomTextareaWidget(),
			new ListingCardCustomUrlWidget(),
			new ListingCardCustomNumberWidget(),
			new ListingCardCustomDateWidget(),
			new ListingCardCustomTimeWidget(),
			new ListingCardCustomSelectWidget(),
			new ListingCardCustomRadioWidget(),
			new ListingCardCustomCheckboxWidget(),
			new ListingCardCustomColorWidget(),
			new ListingCardCustomFileWidget(),
			new ListingCardCustomHtmlWidget(),
			new ListingCardCustomButtonWidget(),
			new SingleListingBackWidget(),
			new SingleListingBookmarkWidget(),
			new SingleListingShareWidget(),
			new SingleListingReportWidget(),
			new SingleListingAuthorInfoWidget(),
			new SingleListingReviewWidget(),
			new SingleListingBookingWidget(),
			new SingleListingCompareWidget(),
			new SingleListingClaimListingWidget(),
			new SingleListingDigitalDownloadsWidget(),
			new SingleListingGalleryWidget(),
			new SingleListingMapWidget(),
			new SingleListingTagWidget(),
			new SingleListingFaqWidget(),
			new SingleListingLiveChatWidget(),
			new SingleListingJobDetailsWidget(),
			new SingleListingJobTypeWidget(),
			new SingleListingJobSalaryWidget(),
			new SingleListingJobOpenPositionWidget(),
			new SingleListingJobDeadlineWidget(),
			new SingleListingJobApplicationFormWidget(),
			new SingleListingDirectoryLinkingWidget(),
			new SingleListingContactOwnerFormWidget(),
			new SingleListingCustomContentWidget(),
			new SingleListingFormgentFormWidget(),
			new SingleListingRelatedListingsWidget(),
		];

		foreach ( $widgets as $widget ) {
			if ( method_exists( $widgets_manager, 'register' ) ) {
				$widgets_manager->register( $widget );
				continue;
			}

			if ( method_exists( $widgets_manager, 'register_widget_type' ) ) {
				$widgets_manager->register_widget_type( $widget );
			}
		}
	}
}
