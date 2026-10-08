( function( $, window, document ) {
	const HIDDEN_CLASS = 'directorist-elementor-panel-hidden';
	const LOOP_WIDGET = 'directorist_listings_loop';
	const HOME_SEARCH_LOOP_WIDGET = 'directorist_homepage_search_loop';
	const HOME_SEARCH_WIDGET = 'directorist_homepage_search';
	const LISTINGS_SEARCH_WIDGET = 'directorist_listings_search';
	const SEARCH_WIDGETS = [ LISTINGS_SEARCH_WIDGET, HOME_SEARCH_WIDGET ];
	const SEARCH_DIRECTORY_TYPES_WIDGET = 'directorist_search_directory_types';
	const SEARCH_MORE_FILTERS_BUTTON_WIDGET = 'directorist_search_more_filters_button';
	const SEARCH_SUBMIT_BUTTON_WIDGET = 'directorist_search_submit_button';
	const SEARCH_ACTION_WIDGETS = [
		SEARCH_MORE_FILTERS_BUTTON_WIDGET,
		SEARCH_SUBMIT_BUTTON_WIDGET,
	];
	const LOOP_WIDGETS = [ LOOP_WIDGET, HOME_SEARCH_LOOP_WIDGET ];
	const CARD_TEMPLATE_WIDGET = 'directorist_listing_card_template';
	const PRICING_PLANS_WIDGET = 'directorist_pricing_plans';
	const AUTHOR_PROFILE_WIDGET = 'directorist_single_listing_author_profile';
	const AUTHOR_PROFILE_DOCUMENT = 'directorist-listing-author-archive';
	const HOME_SEARCH_DOCUMENT = 'directorist-home-search-result';
	const RELATED_LISTINGS_WIDGET = 'directorist_single_listing_related_listings';
	const SINGLE_MAP_WIDGET = 'directorist_single_listing_map';
	const ALL_CATEGORIES_WIDGET = 'directorist_all_categories';
	const ALL_LOCATIONS_WIDGET = 'directorist_all_locations';
	const LISTING_IMAGE_SLIDER_WIDGET = 'directorist_listing_card_images_slider';
	const PAGINATION_WIDGET = 'directorist_listings_pagination';
	const CATEGORY_OTHERS = 'directorist-others-fields';
	const CATEGORY_PRESET = 'directorist-listing-card-preset-fields';
	const CATEGORY_CUSTOM = 'directorist-listing-card-custom-fields';
	const CATEGORY_SEARCH_FIELDS = 'directorist-search-form-fields';
	const CATEGORY_PRICING_PLAN = 'directorist-pricing-plan-fields';
	const CATEGORY_AUTHOR = 'directorist-author-fields';
	const DIRECTORIST_CATEGORIES = [
		'directorist-listings-archive',
		CATEGORY_OTHERS,
		CATEGORY_PRESET,
		CATEGORY_CUSTOM,
		CATEGORY_SEARCH_FIELDS,
		CATEGORY_PRICING_PLAN,
		CATEGORY_AUTHOR,
	];
	const CUSTOM_FIELD_WIDGET_NAMES = {
		directorist_listing_card_custom_text: [ 'text' ],
		directorist_listing_card_custom_textarea: [ 'textarea' ],
		directorist_listing_card_custom_url: [ 'url' ],
		directorist_listing_card_custom_number: [ 'number' ],
		directorist_listing_card_custom_date: [ 'date' ],
		directorist_listing_card_custom_time: [ 'time' ],
		directorist_listing_card_custom_select: [ 'select' ],
		directorist_listing_card_custom_radio: [ 'radio' ],
		directorist_listing_card_custom_checkbox: [ 'checkbox' ],
		directorist_listing_card_custom_color: [ 'color', 'color_picker' ],
		directorist_listing_card_custom_file: [ 'file', 'file_upload' ],
		directorist_listing_card_custom_html: [ 'html', 'wp_editor' ],
		directorist_listing_card_custom_button: [ 'button' ],
	};
	const CUSTOM_FIELD_UNAVAILABLE_NOTICE =
		'This custom field widget is not available for the current directory type because no matching field widget is configured in the submission form fields.';
	const CUSTOM_FIELD_SELECTION_UNAVAILABLE_NOTICE =
		'The selected custom field is not available for the current directory type.';
	const RULES = {
		[ LOOP_WIDGET ]: {
			disallowHomeSearchDocument: true,
		},
		[ HOME_SEARCH_LOOP_WIDGET ]: {
			homeSearchDocumentOnly: true,
			disallowAncestor: [ LOOP_WIDGET, HOME_SEARCH_LOOP_WIDGET, CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET ],
		},
		[ HOME_SEARCH_WIDGET ]: {
			disallowAncestor: CARD_TEMPLATE_WIDGET,
		},
		directorist_listing_card_template: {
			ancestor: LOOP_WIDGETS,
			disallowAncestor: CARD_TEMPLATE_WIDGET,
		},
		[ LISTINGS_SEARCH_WIDGET ]: {
			ancestor: LOOP_WIDGET,
			disallowAncestor: CARD_TEMPLATE_WIDGET,
		},
		[ SEARCH_DIRECTORY_TYPES_WIDGET ]: {
			ancestor: LOOP_WIDGETS,
			disallowAncestor: [ CARD_TEMPLATE_WIDGET, LISTINGS_SEARCH_WIDGET, HOME_SEARCH_WIDGET ],
		},
		[ SEARCH_SUBMIT_BUTTON_WIDGET ]: {
			ancestor: SEARCH_WIDGETS,
			disallowAncestor: CARD_TEMPLATE_WIDGET,
		},
		[ SEARCH_MORE_FILTERS_BUTTON_WIDGET ]: {
			ancestor: SEARCH_WIDGETS,
			disallowAncestor: CARD_TEMPLATE_WIDGET,
		},
		directorist_listings_header: {
			ancestor: LOOP_WIDGETS,
			disallowAncestor: CARD_TEMPLATE_WIDGET,
		},
		directorist_listings_filters: {
			ancestor: LOOP_WIDGETS,
			disallowAncestor: CARD_TEMPLATE_WIDGET,
		},
		[ PAGINATION_WIDGET ]: {
			ancestor: LOOP_WIDGETS,
			disallowAncestor: CARD_TEMPLATE_WIDGET,
		},
		directorist_all_listings: {
			disallowAncestor: [ LOOP_WIDGET, HOME_SEARCH_LOOP_WIDGET, CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET ],
		},
		[ PRICING_PLANS_WIDGET ]: {
			disallowAncestor: [ LOOP_WIDGET, HOME_SEARCH_LOOP_WIDGET, CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET, AUTHOR_PROFILE_WIDGET ],
		},
		[ AUTHOR_PROFILE_WIDGET ]: {
			singleDocumentOnly: true,
			allowAuthorDocument: true,
			disallowAncestor: [ LOOP_WIDGET, HOME_SEARCH_LOOP_WIDGET, CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET, AUTHOR_PROFILE_WIDGET ],
		},
		directorist_pricing_plan_title: {
			ancestor: PRICING_PLANS_WIDGET,
			disallowAncestor: [ CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET ],
		},
		directorist_pricing_plan_description: {
			ancestor: PRICING_PLANS_WIDGET,
			disallowAncestor: [ CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET ],
		},
		directorist_pricing_plan_price: {
			ancestor: PRICING_PLANS_WIDGET,
			disallowAncestor: [ CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET ],
		},
		directorist_pricing_plan_duration: {
			ancestor: PRICING_PLANS_WIDGET,
			disallowAncestor: [ CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET ],
		},
		directorist_pricing_plan_trial_note: {
			ancestor: PRICING_PLANS_WIDGET,
			disallowAncestor: [ CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET ],
		},
		directorist_pricing_plan_type_badge: {
			ancestor: PRICING_PLANS_WIDGET,
			disallowAncestor: [ CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET ],
		},
		directorist_pricing_plan_recommended_badge: {
			ancestor: PRICING_PLANS_WIDGET,
			disallowAncestor: [ CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET ],
		},
		directorist_pricing_plan_active_badge: {
			ancestor: PRICING_PLANS_WIDGET,
			disallowAncestor: [ CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET ],
		},
		directorist_pricing_plan_features: {
			ancestor: PRICING_PLANS_WIDGET,
			disallowAncestor: [ CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET ],
		},
		directorist_pricing_plan_action_button: {
			ancestor: PRICING_PLANS_WIDGET,
			disallowAncestor: [ CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET ],
		},
		directorist_author_profile_avatar: {
			ancestor: AUTHOR_PROFILE_WIDGET,
			disallowAncestor: [ CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET, PRICING_PLANS_WIDGET ],
		},
		directorist_author_profile_name: {
			ancestor: AUTHOR_PROFILE_WIDGET,
			disallowAncestor: [ CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET, PRICING_PLANS_WIDGET ],
		},
		directorist_author_profile_membership: {
			ancestor: AUTHOR_PROFILE_WIDGET,
			disallowAncestor: [ CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET, PRICING_PLANS_WIDGET ],
		},
		directorist_author_profile_rating: {
			ancestor: AUTHOR_PROFILE_WIDGET,
			disallowAncestor: [ CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET, PRICING_PLANS_WIDGET ],
		},
		directorist_author_profile_listing_count: {
			ancestor: AUTHOR_PROFILE_WIDGET,
			disallowAncestor: [ CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET, PRICING_PLANS_WIDGET ],
		},
		directorist_author_profile_bio: {
			ancestor: AUTHOR_PROFILE_WIDGET,
			disallowAncestor: [ CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET, PRICING_PLANS_WIDGET ],
		},
		directorist_author_profile_contact_address: {
			ancestor: AUTHOR_PROFILE_WIDGET,
			disallowAncestor: [ CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET, PRICING_PLANS_WIDGET ],
		},
		directorist_author_profile_contact_phone: {
			ancestor: AUTHOR_PROFILE_WIDGET,
			disallowAncestor: [ CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET, PRICING_PLANS_WIDGET ],
		},
		directorist_author_profile_contact_email: {
			ancestor: AUTHOR_PROFILE_WIDGET,
			disallowAncestor: [ CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET, PRICING_PLANS_WIDGET ],
		},
		directorist_author_profile_contact_website: {
			ancestor: AUTHOR_PROFILE_WIDGET,
			disallowAncestor: [ CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET, PRICING_PLANS_WIDGET ],
		},
		directorist_author_profile_social_links: {
			ancestor: AUTHOR_PROFILE_WIDGET,
			disallowAncestor: [ CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET, PRICING_PLANS_WIDGET ],
		},
		directorist_author_profile_button: {
			ancestor: AUTHOR_PROFILE_WIDGET,
			disallowAncestor: [ CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET, PRICING_PLANS_WIDGET ],
		},
		directorist_author_profile_message_button: {
			ancestor: AUTHOR_PROFILE_WIDGET,
			disallowAncestor: [ CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET, PRICING_PLANS_WIDGET ],
		},
		[ ALL_CATEGORIES_WIDGET ]: {
			disallowAncestor: [ CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET, SINGLE_MAP_WIDGET, ALL_CATEGORIES_WIDGET, ALL_LOCATIONS_WIDGET ],
		},
		[ ALL_LOCATIONS_WIDGET ]: {
			disallowAncestor: [ CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET, SINGLE_MAP_WIDGET, ALL_CATEGORIES_WIDGET, ALL_LOCATIONS_WIDGET ],
		},
		directorist_category_card_image: {
			ancestor: ALL_CATEGORIES_WIDGET,
			disallowAncestor: [ CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET, SINGLE_MAP_WIDGET, ALL_LOCATIONS_WIDGET ],
		},
		directorist_category_card_icon: {
			ancestor: ALL_CATEGORIES_WIDGET,
			disallowAncestor: [ CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET, SINGLE_MAP_WIDGET, ALL_LOCATIONS_WIDGET ],
		},
		directorist_category_card_title: {
			ancestor: ALL_CATEGORIES_WIDGET,
			disallowAncestor: [ CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET, SINGLE_MAP_WIDGET, ALL_LOCATIONS_WIDGET ],
		},
		directorist_category_card_count: {
			ancestor: ALL_CATEGORIES_WIDGET,
			disallowAncestor: [ CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET, SINGLE_MAP_WIDGET, ALL_LOCATIONS_WIDGET ],
		},
		directorist_category_card_description: {
			ancestor: ALL_CATEGORIES_WIDGET,
			disallowAncestor: [ CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET, SINGLE_MAP_WIDGET, ALL_LOCATIONS_WIDGET ],
		},
		directorist_category_card_button: {
			ancestor: ALL_CATEGORIES_WIDGET,
			disallowAncestor: [ CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET, SINGLE_MAP_WIDGET, ALL_LOCATIONS_WIDGET ],
		},
		directorist_location_card_image: {
			ancestor: ALL_LOCATIONS_WIDGET,
			disallowAncestor: [ CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET, SINGLE_MAP_WIDGET, ALL_CATEGORIES_WIDGET ],
		},
		directorist_location_card_icon: {
			ancestor: ALL_LOCATIONS_WIDGET,
			disallowAncestor: [ CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET, SINGLE_MAP_WIDGET, ALL_CATEGORIES_WIDGET ],
		},
		directorist_location_card_title: {
			ancestor: ALL_LOCATIONS_WIDGET,
			disallowAncestor: [ CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET, SINGLE_MAP_WIDGET, ALL_CATEGORIES_WIDGET ],
		},
		directorist_location_card_count: {
			ancestor: ALL_LOCATIONS_WIDGET,
			disallowAncestor: [ CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET, SINGLE_MAP_WIDGET, ALL_CATEGORIES_WIDGET ],
		},
		directorist_location_card_description: {
			ancestor: ALL_LOCATIONS_WIDGET,
			disallowAncestor: [ CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET, SINGLE_MAP_WIDGET, ALL_CATEGORIES_WIDGET ],
		},
		directorist_location_card_button: {
			ancestor: ALL_LOCATIONS_WIDGET,
			disallowAncestor: [ CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET, SINGLE_MAP_WIDGET, ALL_CATEGORIES_WIDGET ],
		},
		directorist_listing_card_thumbnail: {
			ancestor: CARD_TEMPLATE_WIDGET,
			allowSingleDocument: true,
		},
		directorist_listing_card_images_slider: {
			ancestor: CARD_TEMPLATE_WIDGET,
			allowSingleDocument: true,
		},
		directorist_listing_card_address: {
			ancestor: CARD_TEMPLATE_WIDGET,
			allowSingleDocument: true,
		},
		directorist_listing_card_category: {
			ancestor: CARD_TEMPLATE_WIDGET,
			allowSingleDocument: true,
		},
		directorist_listing_card_pricing: {
			ancestor: CARD_TEMPLATE_WIDGET,
			allowSingleDocument: true,
		},
		directorist_listing_card_phone: {
			ancestor: CARD_TEMPLATE_WIDGET,
			allowSingleDocument: true,
		},
		directorist_listing_card_phone_two: {
			ancestor: CARD_TEMPLATE_WIDGET,
			allowSingleDocument: true,
		},
		directorist_listing_card_email: {
			ancestor: CARD_TEMPLATE_WIDGET,
			allowSingleDocument: true,
		},
		directorist_listing_card_fax: {
			ancestor: CARD_TEMPLATE_WIDGET,
			allowSingleDocument: true,
		},
		directorist_listing_card_website: {
			ancestor: CARD_TEMPLATE_WIDGET,
			allowSingleDocument: true,
		},
		directorist_listing_card_video: {
			ancestor: CARD_TEMPLATE_WIDGET,
			allowSingleDocument: true,
		},
		directorist_listing_card_business_hours: {
			ancestor: CARD_TEMPLATE_WIDGET,
			allowSingleDocument: true,
		},
		directorist_listing_card_zip_code: {
			ancestor: CARD_TEMPLATE_WIDGET,
			allowSingleDocument: true,
		},
		directorist_listing_card_posted_date: {
			ancestor: CARD_TEMPLATE_WIDGET,
			allowSingleDocument: true,
		},
		directorist_listing_card_view_count: {
			ancestor: CARD_TEMPLATE_WIDGET,
			allowSingleDocument: true,
		},
		directorist_listing_card_rating: {
			ancestor: CARD_TEMPLATE_WIDGET,
			allowSingleDocument: true,
		},
		directorist_listing_card_social_info: {
			ancestor: CARD_TEMPLATE_WIDGET,
			allowSingleDocument: true,
		},
		directorist_listing_card_user_avatar: {
			ancestor: CARD_TEMPLATE_WIDGET,
			allowSingleDocument: true,
		},
		directorist_listing_card_badge_featured: {
			ancestor: CARD_TEMPLATE_WIDGET,
			allowSingleDocument: true,
		},
		directorist_listing_card_badge_favorite: {
			ancestor: CARD_TEMPLATE_WIDGET,
			allowSingleDocument: true,
		},
		directorist_listing_card_badge_new: {
			ancestor: CARD_TEMPLATE_WIDGET,
			allowSingleDocument: true,
		},
		directorist_listing_card_badge_popular: {
			ancestor: CARD_TEMPLATE_WIDGET,
			allowSingleDocument: true,
		},
		directorist_listing_card_title: {
			ancestor: CARD_TEMPLATE_WIDGET,
			allowSingleDocument: true,
		},
		directorist_listing_card_excerpt: {
			ancestor: CARD_TEMPLATE_WIDGET,
			allowSingleDocument: true,
		},
		directorist_listing_card_location: {
			ancestor: CARD_TEMPLATE_WIDGET,
			allowSingleDocument: true,
		},
		directorist_social_fields: {
			singleDocumentOnly: true,
			disallowAncestor: [ CARD_TEMPLATE_WIDGET, RELATED_LISTINGS_WIDGET ],
		},
		directorist_listing_card_custom_text: {
			ancestor: CARD_TEMPLATE_WIDGET,
			allowSingleDocument: true,
		},
		directorist_listing_card_custom_textarea: {
			ancestor: CARD_TEMPLATE_WIDGET,
			allowSingleDocument: true,
		},
		directorist_listing_card_custom_url: {
			ancestor: CARD_TEMPLATE_WIDGET,
			allowSingleDocument: true,
		},
		directorist_listing_card_custom_number: {
			ancestor: CARD_TEMPLATE_WIDGET,
			allowSingleDocument: true,
		},
		directorist_listing_card_custom_date: {
			ancestor: CARD_TEMPLATE_WIDGET,
			allowSingleDocument: true,
		},
		directorist_listing_card_custom_time: {
			ancestor: CARD_TEMPLATE_WIDGET,
			allowSingleDocument: true,
		},
		directorist_listing_card_custom_select: {
			ancestor: CARD_TEMPLATE_WIDGET,
			allowSingleDocument: true,
		},
		directorist_listing_card_custom_radio: {
			ancestor: CARD_TEMPLATE_WIDGET,
			allowSingleDocument: true,
		},
		directorist_listing_card_custom_checkbox: {
			ancestor: CARD_TEMPLATE_WIDGET,
			allowSingleDocument: true,
		},
		directorist_listing_card_custom_color: {
			ancestor: CARD_TEMPLATE_WIDGET,
			allowSingleDocument: true,
		},
		directorist_listing_card_custom_file: {
			ancestor: CARD_TEMPLATE_WIDGET,
			allowSingleDocument: true,
		},
		directorist_listing_card_custom_html: {
			ancestor: CARD_TEMPLATE_WIDGET,
			allowSingleDocument: true,
		},
		directorist_listing_card_custom_button: {
			ancestor: CARD_TEMPLATE_WIDGET,
			allowSingleDocument: true,
		},
		directorist_single_listing_back: {
			singleDocumentOnly: true,
			disallowAncestor: RELATED_LISTINGS_WIDGET,
		},
		directorist_single_listing_bookmark: {
			singleDocumentOnly: true,
			disallowAncestor: RELATED_LISTINGS_WIDGET,
		},
		directorist_single_listing_share: {
			singleDocumentOnly: true,
			disallowAncestor: RELATED_LISTINGS_WIDGET,
		},
		directorist_single_listing_report: {
			singleDocumentOnly: true,
			disallowAncestor: RELATED_LISTINGS_WIDGET,
		},
		directorist_single_listing_author_info: {
			singleDocumentOnly: true,
			disallowAncestor: RELATED_LISTINGS_WIDGET,
		},
		directorist_single_listing_review: {
			singleDocumentOnly: true,
			disallowAncestor: RELATED_LISTINGS_WIDGET,
		},
		directorist_single_listing_booking: {
			singleDocumentOnly: true,
			disallowAncestor: RELATED_LISTINGS_WIDGET,
		},
		directorist_single_listing_compare: {
			singleDocumentOnly: true,
			disallowAncestor: RELATED_LISTINGS_WIDGET,
		},
		directorist_single_listing_claim_listing: {
			singleDocumentOnly: true,
			disallowAncestor: RELATED_LISTINGS_WIDGET,
		},
		directorist_single_listing_digital_downloads: {
			singleDocumentOnly: true,
			disallowAncestor: RELATED_LISTINGS_WIDGET,
		},
		directorist_single_listing_gallery: {
			singleDocumentOnly: true,
			disallowAncestor: RELATED_LISTINGS_WIDGET,
		},
		directorist_single_listing_tag: {
			ancestor: CARD_TEMPLATE_WIDGET,
			allowSingleDocument: true,
		},
		directorist_single_listing_faq: {
			singleDocumentOnly: true,
			disallowAncestor: RELATED_LISTINGS_WIDGET,
		},
		directorist_single_listing_live_chat: {
			singleDocumentOnly: true,
			disallowAncestor: RELATED_LISTINGS_WIDGET,
		},
		directorist_single_listing_job_details: {
			singleDocumentOnly: true,
			disallowAncestor: RELATED_LISTINGS_WIDGET,
		},
		directorist_single_listing_job_type: {
			singleDocumentOnly: true,
			disallowAncestor: RELATED_LISTINGS_WIDGET,
		},
		directorist_single_listing_job_salary: {
			singleDocumentOnly: true,
			disallowAncestor: RELATED_LISTINGS_WIDGET,
		},
		directorist_single_listing_job_open_position: {
			singleDocumentOnly: true,
			disallowAncestor: RELATED_LISTINGS_WIDGET,
		},
		directorist_single_listing_job_deadline: {
			singleDocumentOnly: true,
			disallowAncestor: RELATED_LISTINGS_WIDGET,
		},
		directorist_single_listing_job_application_form: {
			singleDocumentOnly: true,
			disallowAncestor: RELATED_LISTINGS_WIDGET,
		},
		directorist_single_listing_directory_linking: {
			singleDocumentOnly: true,
			disallowAncestor: RELATED_LISTINGS_WIDGET,
		},
		directorist_single_listing_contact_owner_form: {
			singleDocumentOnly: true,
			disallowAncestor: RELATED_LISTINGS_WIDGET,
		},
		directorist_single_listing_custom_content: {
			singleDocumentOnly: true,
			disallowAncestor: RELATED_LISTINGS_WIDGET,
		},
		directorist_single_listing_description: {
			singleDocumentOnly: true,
			disallowAncestor: RELATED_LISTINGS_WIDGET,
		},
		directorist_single_listing_formgent_form: {
			singleDocumentOnly: true,
			disallowAncestor: RELATED_LISTINGS_WIDGET,
		},
		directorist_single_listing_map: {
			singleDocumentOnly: true,
			disallowAncestor: [ RELATED_LISTINGS_WIDGET, SINGLE_MAP_WIDGET ],
		},
		directorist_single_listing_related_listings: {
			singleDocumentOnly: true,
			disallowAncestor: RELATED_LISTINGS_WIDGET,
		},
	};
	const PAGINATION_PANEL_SECTIONS = {
		numbered: [
			'section_pagination_previous_icon',
			'section_pagination_next_icon',
			'section_pagination_style_numbered',
		],
		infinite_scroll: [
			'section_pagination_infinite',
			'section_pagination_style_infinite',
			'section_pagination_style_loading',
		],
	};
	const LISTING_IMAGE_SINGLE_TEMPLATE_CONTROLS = [
		'preview_listing_id',
		'view_mode',
		'items_to_show',
		'section_images_slider_grid',
		'section_images_slider_flex',
		'section_images_slider_masonry',
	];
	let rafId = null;
	let didInit = false;
	let initRetryTimer = 0;
	let panelMutationObserver = null;
	let lastSelectedContainer = null;
	let directoristRefreshEventsBound = false;

	function getSafePanelView() {
		if (
			! window.elementor ||
			'function' !== typeof window.elementor.getPanelView
		) {
			return null;
		}

		try {
			return window.elementor.getPanelView() || null;
		} catch ( error ) {
			void error;
			return null;
		}
	}

	function releasePanelLoadingState() {
		if (
			! document.body ||
			! document.body.classList.contains( 'elementor-panel-loading' ) ||
			! window.$e ||
			'function' !== typeof window.$e.internal
		) {
			return;
		}

		const panelView = getSafePanelView();

		if ( ! panelView || ! panelView.$el || ! panelView.$el.length ) {
			return;
		}

		try {
			window.$e.internal( 'panel/state-ready' );
		} catch ( error ) {
			if ( window.console && 'function' === typeof window.console.warn ) {
				window.console.warn( 'Directorist panel/state-ready release failed', error );
			}
		}
	}

	function getSelectedContainer() {
		const elementor = window.elementor;
		const selection =
			elementor &&
			elementor.selection &&
			typeof elementor.selection.getElements === 'function'
				? elementor.selection.getElements()
				: [];
		const selected = Array.isArray( selection ) ? selection : [];
		const selectedItem = selected[ 0 ] || null;
		const selectedContainer = selectedItem && selectedItem.view && 'function' === typeof selectedItem.view.getContainer
			? selectedItem.view.getContainer()
			: null;

		if ( selectedContainer ) {
			lastSelectedContainer = selectedContainer;
			return selectedContainer;
		}

		if (
			lastSelectedContainer &&
			lastSelectedContainer.model &&
			'function' === typeof lastSelectedContainer.model.get &&
			elementor &&
			'function' === typeof elementor.getContainer
		) {
			const currentContainer = elementor.getContainer( lastSelectedContainer.model.get( 'id' ) );

			if ( currentContainer ) {
				lastSelectedContainer = currentContainer;
				return currentContainer;
			}
		}

		lastSelectedContainer = null;

		return null;
	}

	function getCurrentPanelPageView() {
		const panelView = getSafePanelView();

		try {
			return panelView && 'function' === typeof panelView.getCurrentPageView
				? panelView.getCurrentPageView()
				: null;
		} catch ( error ) {
			void error;
			return null;
		}
	}

	function getCurrentDocumentType() {
		if (
			! window.elementor ||
			! window.elementor.documents ||
			typeof window.elementor.documents.getCurrent !== 'function'
		) {
			return '';
		}

		const currentDocument = window.elementor.documents.getCurrent();

		return currentDocument && currentDocument.config ? currentDocument.config.type || '' : '';
	}

	function isDirectoristDirectorySingleListingDocument() {
		return 0 === getCurrentDocumentType().indexOf( 'directorist-single-listing-directory-' );
	}

	function isDirectoristAuthorProfileDocument() {
		return AUTHOR_PROFILE_DOCUMENT === getCurrentDocumentType();
	}

	function isDirectoristHomeSearchResultDocument() {
		return HOME_SEARCH_DOCUMENT === getCurrentDocumentType();
	}

	function isDirectoristAnySingleListingDocument() {
		return isDirectoristDirectorySingleListingDocument();
	}

	function getContainerType( container ) {
		if ( ! container || ! container.model || typeof container.model.get !== 'function' ) {
			return '';
		}

		return container.model.get( 'widgetType' ) || container.model.get( 'elType' ) || '';
	}

	function hasAncestorWidget( container, widgetType ) {
		if ( Array.isArray( widgetType ) ) {
			return widgetType.some( ( currentWidgetType ) =>
				hasAncestorWidget( container, currentWidgetType )
			);
		}

		let current = container;

		while ( current ) {
			if ( widgetType === getContainerType( current ) ) {
				return true;
			}

			current = current.parent || null;
		}

		return false;
	}

	function findAncestorWidgetContainer( container, widgetType ) {
		if ( Array.isArray( widgetType ) ) {
			for ( let index = 0; index < widgetType.length; index++ ) {
				const found = findAncestorWidgetContainer( container, widgetType[ index ] );

				if ( found ) {
					return found;
				}
			}

			return null;
		}

		let current = container;

		while ( current ) {
			if ( widgetType === getContainerType( current ) ) {
				return current;
			}

			current = current.parent || null;
		}

		return null;
	}

	function getContainerSettingsModel( container ) {
		if ( ! container || ! container.model || 'function' !== typeof container.model.get ) {
			return null;
		}

		const settings = container.model.get( 'settings' );

		return settings && 'function' === typeof settings.get ? settings : null;
	}

	function toPositiveInt( value ) {
		const parsed = parseInt( value, 10 );

		return Number.isNaN( parsed ) || parsed <= 0 ? 0 : parsed;
	}

	function getDirectoryIdFromDocumentType() {
		const prefix = 'directorist-single-listing-directory-';
		const documentType = String( getCurrentDocumentType() || '' );

		if ( 0 !== documentType.indexOf( prefix ) ) {
			return 0;
		}

		return toPositiveInt( documentType.slice( prefix.length ) );
	}

	function getActiveCardTemplateDirectoryId( selectedContainer ) {
		const cardContainer = selectedContainer
			? findAncestorWidgetContainer( selectedContainer, CARD_TEMPLATE_WIDGET )
			: null;
		const settings = getContainerSettingsModel( cardContainer );

		if ( ! settings ) {
			return 0;
		}

		const loopDirectoryId = getActiveLoopDirectoryId( cardContainer );

		if ( loopDirectoryId > 0 ) {
			return loopDirectoryId;
		}

		const activeDirectoryId = toPositiveInt( settings.get( 'active_directory_type_id' ) );

		if ( activeDirectoryId > 0 ) {
			return activeDirectoryId;
		}

		const defaultDirectoryId = toPositiveInt( settings.get( 'default_directory_type_id' ) );

		if ( defaultDirectoryId > 0 ) {
			return defaultDirectoryId;
		}

		const directoryTypeIds = settings.get( 'directory_type_ids' );

		if ( Array.isArray( directoryTypeIds ) && directoryTypeIds.length ) {
			return toPositiveInt( directoryTypeIds[ 0 ] );
		}

		return 0;
	}

	function getActiveLoopDirectoryId( selectedContainer ) {
		const loopContainer = selectedContainer
			? findAncestorWidgetContainer( selectedContainer, LOOP_WIDGETS )
			: null;
		const settings = getContainerSettingsModel( loopContainer );

		if ( ! settings ) {
			return 0;
		}

		const activeDirectoryId = toPositiveInt( settings.get( 'active_directory_type_id' ) );

		if ( activeDirectoryId > 0 ) {
			return activeDirectoryId;
		}

		const defaultDirectoryId = toPositiveInt( settings.get( 'default_directory_type_id' ) );

		if ( defaultDirectoryId > 0 ) {
			return defaultDirectoryId;
		}

		const directoryTypeIds = settings.get( 'directory_type_ids' );

		if ( Array.isArray( directoryTypeIds ) && directoryTypeIds.length ) {
			return toPositiveInt( directoryTypeIds[ 0 ] );
		}

		return 0;
	}

	function getSearchCompositionContainer( selectedContainer ) {
		if ( ! selectedContainer ) {
			return null;
		}

		return findAncestorWidgetContainer( selectedContainer, SEARCH_WIDGETS );
	}

	function isSearchCompositionContext( selectedContainer ) {
		return !! getSearchCompositionContainer( selectedContainer );
	}

	function getActiveSearchDirectoryId( selectedContainer ) {
		const searchContainer = getSearchCompositionContainer( selectedContainer );
		const settings = getContainerSettingsModel( searchContainer );
		const loopDirectoryId = getActiveLoopDirectoryId( selectedContainer );

		if ( loopDirectoryId > 0 ) {
			return loopDirectoryId;
		}

		if ( settings ) {
			const activeDirectoryId = toPositiveInt( settings.get( 'active_directory_type_id' ) );

			if ( activeDirectoryId > 0 ) {
				return activeDirectoryId;
			}

			const defaultDirectoryId = toPositiveInt( settings.get( 'default_directory_type_id' ) );

			if ( defaultDirectoryId > 0 ) {
				return defaultDirectoryId;
			}

			const directoryTypeIds = settings.get( 'directory_type_ids' );

			if ( Array.isArray( directoryTypeIds ) && directoryTypeIds.length ) {
				return toPositiveInt( directoryTypeIds[ 0 ] );
			}

			const scopedDirectoryId = getActiveSearchScopedDirectoryId( settings );

			if ( scopedDirectoryId > 0 ) {
				return scopedDirectoryId;
			}
		}
		return 0;
	}

	function parseSearchTemplateScopes( value ) {
		if ( value && 'object' === typeof value && ! Array.isArray( value ) ) {
			return value;
		}

		if ( 'string' !== typeof value || ! value.trim() ) {
			return {};
		}

		try {
			const parsed = JSON.parse( value );

			return parsed && 'object' === typeof parsed && ! Array.isArray( parsed ) ? parsed : {};
		} catch ( error ) {
			return {};
		}
	}

	function getDirectoryIdFromSearchTemplateKey( key ) {
		const match = String( key || '' ).match( /^dir-(\d+)$/ );

		return match ? toPositiveInt( match[ 1 ] ) : 0;
	}

	function getActiveSearchScopedDirectoryId( settings ) {
		if ( ! settings || 'function' !== typeof settings.get ) {
			return 0;
		}

		const activeKey = String( settings.get( 'active_search_template_key' ) || '' );
		const scopes = parseSearchTemplateScopes( settings.get( 'scoped_search_templates' ) || '{}' );
		const activeScope = activeKey && scopes[ activeKey ] && 'object' === typeof scopes[ activeKey ]
			? scopes[ activeKey ]
			: null;
		const activeScopeDirectoryId = activeScope
			? toPositiveInt( activeScope.directory_type_id )
			: 0;

		if ( activeScopeDirectoryId > 0 ) {
			return activeScopeDirectoryId;
		}

		const keyDirectoryId = getDirectoryIdFromSearchTemplateKey( activeKey );

		if ( keyDirectoryId > 0 ) {
			return keyDirectoryId;
		}

		const firstScopeKey = Object.keys( scopes ).find( ( scopeKey ) => {
			const scope = scopes[ scopeKey ];
			const directoryId = scope && 'object' === typeof scope
				? toPositiveInt( scope.directory_type_id )
				: getDirectoryIdFromSearchTemplateKey( scopeKey );

			return directoryId > 0;
		} );

		if ( firstScopeKey ) {
			const scope = scopes[ firstScopeKey ];
			const directoryId = scope && 'object' === typeof scope
				? toPositiveInt( scope.directory_type_id )
				: 0;

			return directoryId > 0 ? directoryId : getDirectoryIdFromSearchTemplateKey( firstScopeKey );
		}

		return 0;
	}

	function getSearchFieldWidgetTypesForDirectory( directoryTypeId ) {
		const config = window.directoristElementorV4Editor || {};
		const fieldsByDirectory = config.searchFormFieldsByDirectory || {};
		const fields = fieldsByDirectory[ String( directoryTypeId ) ] || {};

		return Object.keys( fields ).reduce( ( widgetTypes, fieldKey ) => {
			const field = fields[ fieldKey ] || {};
			const widgetType = String( field.widget_type || '' ).trim();

			if ( widgetType && -1 === widgetTypes.indexOf( widgetType ) ) {
				widgetTypes.push( widgetType );
			}

			return widgetTypes;
		}, [] );
	}

	function isSearchFieldWidget( widgetType ) {
		const config = window.directoristElementorV4Editor || {};
		const widgetTypes = Array.isArray( config.searchFieldWidgetTypes )
			? config.searchFieldWidgetTypes
			: [];

		return -1 !== widgetTypes.indexOf( widgetType );
	}

	function isSearchFieldWidgetAvailableForDirectory( widgetType, directoryTypeId ) {
		if ( ! isSearchFieldWidget( widgetType ) ) {
			return true;
		}

		if ( directoryTypeId <= 0 ) {
			return false;
		}

		return -1 !== getSearchFieldWidgetTypesForDirectory( directoryTypeId ).indexOf( widgetType );
	}

	function getActiveCustomFieldDirectoryId( selectedContainer ) {
		const cardDirectoryId = getActiveCardTemplateDirectoryId( selectedContainer );

		if ( cardDirectoryId > 0 ) {
			return cardDirectoryId;
		}

		if ( isDirectoristDirectorySingleListingDocument() ) {
			return getDirectoryIdFromDocumentType();
		}

		return 0;
	}

	function isCustomFieldWidgetAvailableForDirectory( widgetType, directoryTypeId ) {
		const requiredWidgetNames = CUSTOM_FIELD_WIDGET_NAMES[ widgetType ];

		if ( ! requiredWidgetNames || directoryTypeId <= 0 ) {
			return true;
		}

		const config = window.directoristElementorV4Editor || {};
		const namesByDirectory = config.customFieldWidgetNamesByDirectory || {};
		const availableNames = Array.isArray( namesByDirectory[ String( directoryTypeId ) ] )
			? namesByDirectory[ String( directoryTypeId ) ]
			: [];
		const availableNameMap = availableNames.reduce( ( map, widgetName ) => {
			const normalized = String( widgetName || '' ).trim();

			if ( normalized ) {
				map[ normalized ] = true;
			}

			return map;
		}, {} );

		return requiredWidgetNames.some( ( widgetName ) => !! availableNameMap[ widgetName ] );
	}

	function hasBlockedAncestor( container, widgetType ) {
		if ( ! widgetType ) {
			return false;
		}

		if ( Array.isArray( widgetType ) ) {
			return widgetType.some( ( currentWidgetType ) =>
				hasAncestorWidget( container, currentWidgetType )
			);
		}

		return hasAncestorWidget( container, widgetType );
	}

	function matchesInsertionContext( container, ancestorWidgetType, disallowAncestor ) {
		if ( ! container ) {
			return false;
		}

		if ( hasBlockedAncestor( container, disallowAncestor ) ) {
			return false;
		}

		return hasAncestorWidget( container, ancestorWidgetType );
	}

	function canUseWidget( widgetType, selectedContainer ) {
		const rule = RULES[ widgetType ];

		if ( isSearchFieldWidget( widgetType ) && isSearchCompositionContext( selectedContainer ) ) {
			return isSearchFieldWidgetAvailableForDirectory(
				widgetType,
				getActiveSearchDirectoryId( selectedContainer )
			);
		}

		if ( ! rule ) {
			return true;
		}

		if (
			isSearchCompositionContext( selectedContainer ) &&
			! SEARCH_ACTION_WIDGETS.includes( widgetType )
		) {
			return false;
		}

		if (
			! isCustomFieldWidgetAvailableForDirectory(
				widgetType,
				getActiveCustomFieldDirectoryId( selectedContainer )
			)
		) {
			return false;
		}

		if ( rule.homeSearchDocumentOnly ) {
			if ( ! isDirectoristHomeSearchResultDocument() ) {
				return false;
			}

			if ( selectedContainer && hasBlockedAncestor( selectedContainer, rule.disallowAncestor ) ) {
				return false;
			}

			return true;
		}

		if ( rule.disallowHomeSearchDocument && isDirectoristHomeSearchResultDocument() ) {
			return false;
		}

		if ( rule.singleDocumentOnly ) {
			if (
				! isDirectoristAnySingleListingDocument() &&
				! ( rule.allowAuthorDocument && isDirectoristAuthorProfileDocument() )
			) {
				return false;
			}

			if ( selectedContainer && hasBlockedAncestor( selectedContainer, rule.disallowAncestor ) ) {
				return false;
			}

			return true;
		}

		if ( rule.allowSingleDocument && isDirectoristAnySingleListingDocument() ) {
			return true;
		}

		if ( ! rule.ancestor ) {
			if ( selectedContainer && hasBlockedAncestor( selectedContainer, rule.disallowAncestor ) ) {
				return false;
			}

			return true;
		}

		if ( ! selectedContainer ) {
			return false;
		}

		if ( hasBlockedAncestor( selectedContainer, rule.disallowAncestor ) ) {
			return false;
		}

		if (
			(
				rule.ancestor === CARD_TEMPLATE_WIDGET ||
				( Array.isArray( rule.ancestor ) && rule.ancestor.includes( CARD_TEMPLATE_WIDGET ) )
			) &&
			CARD_TEMPLATE_WIDGET === getContainerType( selectedContainer )
		) {
			return true;
		}

		return hasAncestorWidget( selectedContainer, rule.ancestor );
	}

	function isDirectoristCategoryAllowed( categorySlug, selectedContainer ) {
		if ( CATEGORY_OTHERS === categorySlug ) {
			return isDirectoristDirectorySingleListingDocument();
		}

		if ( CATEGORY_PRESET === categorySlug ) {
			if ( isSearchCompositionContext( selectedContainer ) ) {
				return false;
			}

			return isDirectoristAnySingleListingDocument() ||
				matchesInsertionContext( selectedContainer, CARD_TEMPLATE_WIDGET ) ||
				matchesInsertionContext( selectedContainer, ALL_CATEGORIES_WIDGET ) ||
				matchesInsertionContext( selectedContainer, ALL_LOCATIONS_WIDGET );
		}

		if ( CATEGORY_CUSTOM === categorySlug && isSearchCompositionContext( selectedContainer ) ) {
			return false;
		}

		if ( CATEGORY_SEARCH_FIELDS === categorySlug ) {
			return isSearchCompositionContext( selectedContainer );
		}

		if ( CATEGORY_PRICING_PLAN === categorySlug ) {
			return matchesInsertionContext( selectedContainer, PRICING_PLANS_WIDGET );
		}

		if ( CATEGORY_AUTHOR === categorySlug ) {
			return matchesInsertionContext( selectedContainer, AUTHOR_PROFILE_WIDGET );
		}

		return true;
	}

	function getPanelControlView( controlName ) {
		const pageView = getCurrentPanelPageView();

		if (
			! pageView ||
			! pageView.collection ||
			'function' !== typeof pageView.collection.findWhere ||
			! pageView.children ||
			'function' !== typeof pageView.children.findByModelCid
		) {
			return null;
		}

		try {
			const controlModel = pageView.collection.findWhere( { name: controlName } );

			return controlModel ? pageView.children.findByModelCid( controlModel.cid ) : null;
		} catch ( error ) {
			void error;
			return null;
		}
	}

	function getPanelControlModel( controlName ) {
		const pageView = getCurrentPanelPageView();

		if (
			! pageView ||
			! pageView.collection ||
			'function' !== typeof pageView.collection.findWhere
		) {
			return null;
		}

		try {
			return pageView.collection.findWhere( { name: controlName } ) || null;
		} catch ( error ) {
			void error;
			return null;
		}
	}

	function setPanelControlVisibility( controlName, isVisible ) {
		let controlView = null;

		try {
			controlView = getPanelControlView( controlName );
		} catch ( error ) {
			void error;
			return;
		}

		try {
			if ( ! controlView || ! controlView.$el || ! controlView.$el.length ) {
				return;
			}

			const element = controlView.$el.get( 0 );

			if ( element ) {
				element.hidden = ! isVisible;
				element.setAttribute( 'aria-hidden', isVisible ? 'false' : 'true' );
			}

			controlView.$el.toggleClass( HIDDEN_CLASS, ! isVisible );
		} catch ( error ) {
			void error;
		}
	}

	function getSelectedPaginationMode( selectedContainer ) {
		if ( ! selectedContainer || PAGINATION_WIDGET !== getContainerType( selectedContainer ) ) {
			return '';
		}

		const loopContainer = findAncestorWidgetContainer( selectedContainer, LOOP_WIDGETS );
		const loopSettings = getContainerSettingsModel( loopContainer );
		const paginationType = loopSettings ? String( loopSettings.get( 'pagination_type' ) || 'numbered' ) : 'numbered';

		return 'infinite_scroll' === paginationType ? 'infinite_scroll' : 'numbered';
	}

	function applyPaginationPanelVisibility( selectedContainer ) {
		const isPaginationWidget = selectedContainer && PAGINATION_WIDGET === getContainerType( selectedContainer );
		const activeMode = getSelectedPaginationMode( selectedContainer );

		Object.keys( PAGINATION_PANEL_SECTIONS ).forEach( ( mode ) => {
			PAGINATION_PANEL_SECTIONS[ mode ].forEach( ( sectionName ) => {
				setPanelControlVisibility( sectionName, ! isPaginationWidget || mode === activeMode );
			} );
		} );
	}

	function applyListingImagePanelVisibility( selectedContainer ) {
		const isListingImageWidget =
			selectedContainer && LISTING_IMAGE_SLIDER_WIDGET === getContainerType( selectedContainer );
		const isSingleTemplateContext =
			isListingImageWidget &&
			isDirectoristAnySingleListingDocument() &&
			! hasAncestorWidget( selectedContainer, CARD_TEMPLATE_WIDGET );

		LISTING_IMAGE_SINGLE_TEMPLATE_CONTROLS.forEach( ( controlName ) => {
			setPanelControlVisibility( controlName, ! isListingImageWidget || isSingleTemplateContext );
		} );
	}

	function getControlOptionsObject( controlModel ) {
		if ( ! controlModel || 'function' !== typeof controlModel.get ) {
			return {};
		}

		const options = controlModel.get( 'options' );

		return options && 'object' === typeof options ? options : {};
	}

	function getOriginalControlOptions( controlModel ) {
		if ( ! controlModel ) {
			return {};
		}

		if ( ! controlModel._directoristOriginalOptions ) {
			controlModel._directoristOriginalOptions = { ...getControlOptionsObject( controlModel ) };
		}

		return controlModel._directoristOriginalOptions;
	}

	function setControlOptions( controlModel, nextOptions ) {
		if ( ! controlModel || 'function' !== typeof controlModel.set ) {
			return;
		}

		const currentOptions = getControlOptionsObject( controlModel );

		if ( JSON.stringify( currentOptions ) === JSON.stringify( nextOptions ) ) {
			return;
		}

		controlModel.set( 'options', nextOptions );

		const controlView = getPanelControlView( 'custom_field' );
		if ( controlView && 'function' === typeof controlView.render ) {
			controlView.render();
		}
	}

	function syncCustomFieldNotice( showNotice, message = CUSTOM_FIELD_UNAVAILABLE_NOTICE ) {
		const controlView = getPanelControlView( 'custom_field' );

		if ( ! controlView || ! controlView.$el || ! controlView.$el.length ) {
			return;
		}

		let notice = controlView.$el.prev( '.directorist-elementor-custom-field-directory-notice' );

		if ( showNotice ) {
			if ( ! notice.length ) {
				notice = $( '<div />', {
					class: 'directorist-elementor-custom-field-directory-notice elementor-panel-alert elementor-panel-alert-warning',
				} );
				controlView.$el.before( notice );
			}
			notice.text( message );
			return;
		}

		if ( notice.length ) {
			notice.remove();
		}
	}

	function applyCustomFieldPanelAvailability( selectedContainer ) {
		const widgetType = selectedContainer ? getContainerType( selectedContainer ) : '';

		if ( ! CUSTOM_FIELD_WIDGET_NAMES[ widgetType ] ) {
			syncCustomFieldNotice( false );
			return;
		}

		const directoryTypeId = getActiveCustomFieldDirectoryId( selectedContainer );
		const controlModel = getPanelControlModel( 'custom_field' );

		if ( ! controlModel || directoryTypeId <= 0 ) {
			if ( controlModel ) {
				setControlOptions( controlModel, getOriginalControlOptions( controlModel ) );
			}
			syncCustomFieldNotice( false );
			return;
		}

		const originalOptions = getOriginalControlOptions( controlModel );
		const prefix = `${ directoryTypeId }|`;
		const filteredOptions = Object.keys( originalOptions ).reduce( ( options, optionKey ) => {
			if ( 0 === String( optionKey ).indexOf( prefix ) ) {
				options[ optionKey ] = originalOptions[ optionKey ];
			}

			return options;
		}, {} );
		const hasMatchingOptions = Object.keys( filteredOptions ).length > 0;
		const settings = getContainerSettingsModel( selectedContainer );
		const selectedValue =
			settings && 'function' === typeof settings.get
				? String( settings.get( 'custom_field' ) || '' )
				: '';
		const hasSelectedOption =
			! selectedValue ||
			Object.prototype.hasOwnProperty.call( filteredOptions, selectedValue );

		setControlOptions( controlModel, hasMatchingOptions ? filteredOptions : {} );
		setPanelControlVisibility( 'custom_field', hasMatchingOptions );
		syncCustomFieldNotice(
			! hasMatchingOptions || ! hasSelectedOption,
			hasMatchingOptions && ! hasSelectedOption
				? CUSTOM_FIELD_SELECTION_UNAVAILABLE_NOTICE
				: CUSTOM_FIELD_UNAVAILABLE_NOTICE
		);
	}

	function applyContextRules() {
		try {
			const selectedContainer = getSelectedContainer();

			Object.keys( RULES ).forEach( ( widgetType ) => {
				const visible = canUseWidget( widgetType, selectedContainer );

				document
					.querySelectorAll( `[data-library-element-type="${ widgetType }"]` )
					.forEach( ( element ) => {
						if ( element.hidden !== ! visible ) {
							element.hidden = ! visible;
						}

						if ( element.classList.contains( HIDDEN_CLASS ) !== ! visible ) {
							element.classList.toggle( HIDDEN_CLASS, ! visible );
						}

						if ( element.getAttribute( 'aria-hidden' ) !== ( visible ? 'false' : 'true' ) ) {
							element.setAttribute( 'aria-hidden', visible ? 'false' : 'true' );
						}
					} );
			} );
			syncDirectoristCategoryVisibility( selectedContainer );
			applyPaginationPanelVisibility( selectedContainer );
			applyListingImagePanelVisibility( selectedContainer );
			applyCustomFieldPanelAvailability( selectedContainer );
			suppressDirectorySingleConditionsUi();
		} catch ( error ) {
			if ( window.console && 'function' === typeof window.console.warn ) {
				window.console.warn( 'Directorist editor-context apply failed', error );
			}
		}
	}

	function syncDirectoristCategoryVisibility( selectedContainer ) {
		DIRECTORIST_CATEGORIES.forEach( ( categorySlug ) => {
			const categoryElement = document.getElementById( `elementor-panel-category-${ categorySlug }` );

			if ( ! categoryElement ) {
				return;
			}

			const visibleItems = categoryElement.querySelectorAll(
				'[data-library-element-type]:not([hidden]):not(.directorist-elementor-panel-hidden)'
			);
			const isVisible =
				isDirectoristCategoryAllowed( categorySlug, selectedContainer ) &&
				visibleItems.length > 0;

			if ( categoryElement.hidden !== ! isVisible ) {
				categoryElement.hidden = ! isVisible;
			}

			if ( categoryElement.classList.contains( HIDDEN_CLASS ) !== ! isVisible ) {
				categoryElement.classList.toggle( HIDDEN_CLASS, ! isVisible );
			}

			if ( categoryElement.getAttribute( 'aria-hidden' ) !== ( isVisible ? 'false' : 'true' ) ) {
				categoryElement.setAttribute( 'aria-hidden', isVisible ? 'false' : 'true' );
			}
		} );
	}

	function suppressDirectorySingleConditionsUi() {
		if ( ! isDirectoristDirectorySingleListingDocument() ) {
			return;
		}

		if (
			window.elementor &&
			window.elementor.config &&
			window.elementor.config.document &&
			window.elementor.config.document.theme_builder &&
			window.elementor.config.document.theme_builder.settings
		) {
			window.elementor.config.document.theme_builder.settings.location = '';
		}

		try {
			const panelView = getSafePanelView();
			const footerView = panelView && panelView.footer && panelView.footer.currentView
				? panelView.footer.currentView
				: null;

			if ( footerView && typeof footerView.removeSubMenuItem === 'function' ) {
				footerView.removeSubMenuItem( 'saver-options', { name: 'conditions' } );
			}
		} catch ( error ) {
			void error;
		}

		try {
			const component =
				window.$e &&
				window.$e.components &&
				typeof window.$e.components.get === 'function'
					? window.$e.components.get( 'theme-builder-publish' )
					: null;

			if ( component && typeof component.removeTab === 'function' ) {
				component.removeTab( 'conditions' );
			}
		} catch ( error ) {
			void error;
		}
	}

	function scheduleApply() {
		if ( rafId ) {
			return;
		}

		rafId = window.requestAnimationFrame( () => {
			rafId = null;
			applyContextRules();
			releasePanelLoadingState();
		} );
	}

	function observePanelMutations() {
		if ( panelMutationObserver ) {
			return true;
		}

		const targets = [
			document.getElementById( 'elementor-panel-categories' ),
			document.getElementById( 'elementor-panel-elements-wrapper' ),
			document.getElementById( 'elementor-panel' ),
		].filter( Boolean );

		if ( ! targets.length ) {
			return false;
		}

		panelMutationObserver = new MutationObserver( () => {
			scheduleApply();
		} );

		targets.forEach( ( target ) => {
			panelMutationObserver.observe( target, {
				childList: true,
				subtree: true,
			} );
		} );

		return true;
	}

	function bindDirectoristRefreshEvents() {
		if ( directoristRefreshEventsBound || ! window.addEventListener ) {
			return;
		}

		directoristRefreshEventsBound = true;
		window.addEventListener( 'directorist-elementor-context-refresh', scheduleApply );
		window.addEventListener( 'directorist-search-form-nav-tab-reloaded', scheduleApply );
	}

	const TAXONOMY_WIDGETS = [
		'directorist_all_categories',
		'directorist_all_locations',
	];

	function isTaxonomyWidget( widgetType ) {
		return TAXONOMY_WIDGETS.indexOf( widgetType ) !== -1;
	}

	function buildDirectoryOptions( settings ) {
		const allDirectoryOptions =
			window.directoristElementorV4Editor &&
			window.directoristElementorV4Editor.directorySlugOptions
				? window.directoristElementorV4Editor.directorySlugOptions
				: {};

		const selectedSlugs = settings.get( 'directory_type' );
		const selectedArray = Array.isArray( selectedSlugs ) ? selectedSlugs : [];
		const showAllTab = settings.get( 'show_all_directory_tab' );

		const newOptions = {};

		if ( showAllTab !== '' && showAllTab !== 'no' && showAllTab !== false ) {
			newOptions[''] = 'All';
		} else {
			newOptions[''] = '\u2014 Select \u2014';
		}

		if ( selectedArray.length > 0 ) {
			selectedArray.forEach( ( slug ) => {
				if ( allDirectoryOptions[ slug ] ) {
					newOptions[ slug ] = allDirectoryOptions[ slug ];
				} else {
					newOptions[ slug ] = String( slug ).replace( /[-_]/g, ' ' ).replace( /\b\w/g, ( c ) => c.toUpperCase() );
				}
			} );
		} else {
			Object.keys( allDirectoryOptions ).forEach( ( key ) => {
				newOptions[ key ] = allDirectoryOptions[ key ];
			} );
		}

		return newOptions;
	}

	function applyOptionsToSelect( $select, newOptions, settings ) {
		const currentValue = settings.get( 'default_directory_type' ) || '';

		$select.empty();
		Object.keys( newOptions ).forEach( ( key ) => {
			$select.append( $( '<option>' ).val( key ).text( newOptions[ key ] ) );
		} );

		if ( newOptions.hasOwnProperty( currentValue ) ) {
			$select.val( currentValue );
		} else {
			$select.val( '' );
			settings.set( 'default_directory_type', '' );
		}
	}

	function syncDefaultDirectorySelect( settings ) {
		const $select = $( 'select[data-setting="default_directory_type"]' );
		if ( ! $select.length ) {
			return false;
		}
		applyOptionsToSelect( $select, buildDirectoryOptions( settings ), settings );
		return true;
	}

	function normalizeTaxonomyViewSettings( settings ) {
		if ( ! settings || 'function' !== typeof settings.get || 'function' !== typeof settings.set ) {
			return;
		}

		const displayMode = String( settings.get( 'display_mode' ) || 'pagination' );
		const currentView = String( settings.get( 'view' ) || 'grid' );

		if ( 'slider' === displayMode && 'grid' !== currentView ) {
			settings.set( 'view', 'grid' );
		}
	}

	function bindTaxonomySettingsSync() {
		if ( ! window.elementor || typeof window.elementor.on !== 'function' ) {
			return;
		}

		// Use a MutationObserver on the panel to detect when the select control appears.
		let panelObserver = null;

		function observePanelForSelect( settings ) {
			if ( panelObserver ) {
				panelObserver.disconnect();
			}

			const panelEl = document.getElementById( 'elementor-panel' );
			if ( ! panelEl ) {
				return;
			}

			let attempts = 0;
			const maxAttempts = 20;

			panelObserver = new MutationObserver( () => {
				attempts++;
				if ( syncDefaultDirectorySelect( settings ) || attempts >= maxAttempts ) {
					panelObserver.disconnect();
					panelObserver = null;
				}
			} );

			panelObserver.observe( panelEl, { childList: true, subtree: true } );
		}

		let boundSettingsCid = '';

		window.elementor.on( 'panel:open_editor', () => {
			try {
				const selectedContainer = getSelectedContainer();
				if ( ! selectedContainer || ! selectedContainer.model ) {
					return;
				}

				const widgetType = getContainerType( selectedContainer );
				if ( ! isTaxonomyWidget( widgetType ) ) {
					return;
				}

				const model = selectedContainer.model;
				const settings = model.get( 'settings' );
				if ( ! settings || typeof settings.on !== 'function' ) {
					return;
				}

				const doSync = () => {
					normalizeTaxonomyViewSettings( settings );

					if ( ! syncDefaultDirectorySelect( settings ) ) {
						observePanelForSelect( settings );
					}
				};

				// Bind change listeners (avoid duplicates per model).
				const cid = model.cid || '';
				if ( cid !== boundSettingsCid ) {
					boundSettingsCid = cid;
					settings.on( 'change:directory_type', doSync );
					settings.on( 'change:show_all_directory_tab', doSync );
					settings.on( 'change:display_mode', doSync );
				}

				// Initial sync — try immediately, then observe if not ready.
				doSync();
			} catch ( error ) {
				void error;
			}
		} );
	}

	function init() {
		if ( didInit ) {
			scheduleApply();
			observePanelMutations();
			bindDirectoristRefreshEvents();
			return;
		}

		didInit = true;
		scheduleApply();
		observePanelMutations();
		bindDirectoristRefreshEvents();

		if ( window.elementor && typeof window.elementor.on === 'function' ) {
			window.elementor.on( 'preview:loaded', scheduleApply );
			window.elementor.on( 'panel:open_editor widget:editor:open', scheduleApply );
		}

		if (
			window.elementor &&
			window.elementor.channels &&
			window.elementor.channels.editor &&
			'function' === typeof window.elementor.channels.editor.on
		) {
			window.elementor.channels.editor.on( 'change', scheduleApply );
			window.elementor.channels.editor.on( 'section:activated', scheduleApply );
		}

		bindTaxonomySettingsSync();

		window.setTimeout( scheduleApply, 250 );
		window.setTimeout( scheduleApply, 1000 );
		window.setTimeout( observePanelMutations, 250 );
		window.setTimeout( observePanelMutations, 1000 );
	}

	function ensureInit() {
		if ( window.elementor ) {
			init();
			return;
		}

		if ( initRetryTimer ) {
			return;
		}

		initRetryTimer = window.setTimeout( () => {
			initRetryTimer = 0;
			ensureInit();
		}, 250 );
	}

	$( window ).on( 'elementor:init', init );

	ensureInit();
} )( jQuery, window, document );
