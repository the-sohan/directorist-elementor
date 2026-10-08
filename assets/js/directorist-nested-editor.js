( function( $, window ) {
	const LOOP_WIDGET = 'directorist_listings_loop';
	const HOME_SEARCH_LOOP_WIDGET = 'directorist_homepage_search_loop';
	const HOME_SEARCH_WIDGET = 'directorist_homepage_search';
	const HOME_SEARCH_STYLE_TAB = 'directorist_homepage_search_style';
	const HOME_SEARCH_DIRECTORY_TYPES_STYLE_TAB = 'directorist_homepage_directory_types_style';
	const HOME_SEARCH_INHERITED_SETTINGS_KEY = 'directorist_home_search_inherited_settings';
	const SEARCH_FIELD_PANEL_BODY_CLASS = 'directorist-elementor-search-field-panel';
	const SEARCH_DIRECTORY_TYPES_WIDGET = 'directorist_search_directory_types';
	const SEARCH_MORE_FILTERS_BUTTON_WIDGET = 'directorist_search_more_filters_button';
	const LISTINGS_PAGINATION_WIDGET = 'directorist_listings_pagination';
	const LOOP_WIDGETS = [ LOOP_WIDGET, HOME_SEARCH_LOOP_WIDGET ];
	const CARD_TEMPLATE_WIDGET = 'directorist_listing_card_template';
	const PRICING_PLANS_WIDGET = 'directorist_pricing_plans';
	const AUTHOR_PROFILE_WIDGET = 'directorist_single_listing_author_profile';
	const RELATED_LISTINGS_WIDGET = 'directorist_single_listing_related_listings';
	const SINGLE_MAP_WIDGET = 'directorist_single_listing_map';
	const ALL_CATEGORIES_WIDGET = 'directorist_all_categories';
	const ALL_LOCATIONS_WIDGET = 'directorist_all_locations';
	const TAXONOMY_COMPOSITION_WIDGETS = [ ALL_CATEGORIES_WIDGET, ALL_LOCATIONS_WIDGET ];
	const SEARCH_COMPOSITION_WIDGETS = [ 'directorist_listings_search', HOME_SEARCH_WIDGET ];
	const LOOP_UTILITY_WIDGETS = [
		SEARCH_DIRECTORY_TYPES_WIDGET,
		'directorist_listings_header',
		'directorist_listings_filters',
		LISTINGS_PAGINATION_WIDGET,
	];
	const DIRECTORIST_NESTED_WIDGETS = [ RELATED_LISTINGS_WIDGET, SINGLE_MAP_WIDGET, ALL_CATEGORIES_WIDGET, ALL_LOCATIONS_WIDGET ];

	let elementTypesRegistered = false;
	let panelPatchApplied = false;
	let retryTimer = 0;
	let navigatorObserver = null;
	let navigatorSyncQueued = false;
	let searchCompositionSelectionBridgeBound = false;
	let listingRatingPreviewObserver = null;
	let listingRatingPreviewDocument = null;
	const cardScopePersistTimers = new Map();
	const searchScopePersistTimers = new Map();
	const cardPanelRestoreTimers = new Map();
	const branchSyncLocks = new Set();
	const cardTemplateSettingsLocks = new Set();
	const cardTemplateSettingsLockTimers = new Map();
	const cardTemplateSettingsLockFlushTimers = new Map();
	const cardTemplateLockedWrapperChanges = new Set();
	const cardTemplateProjectionStates = new Map();
	const trackedCardTemplateContainers = new Map();
	const trackedRelatedListingsContainers = new Map();
	const trackedSingleMapContainers = new Map();
	const trackedTaxonomyCompositionContainers = new Map();
	const previewTimers = new Map();
	const previewRequests = new Map();
	const previewRequestTokens = new Map();
	const loopScopeNormalizationLocks = new Set();
	const editorDeviceClasses = [
		'directorist-elementor--device-desktop',
		'directorist-elementor--device-tablet',
		'directorist-elementor--device-mobile',
	];
	const loopViewClasses = [
		'directorist-elementor-loop--grid',
		'directorist-elementor-loop--list',
		'directorist-elementor-loop--map',
	];
	const loopDisplayModeClasses = [
		'directorist-elementor-loop--display-default',
		'directorist-elementor-loop--display-slider',
		'directorist-elementor-loop--display-map_list',
	];
	const cardTemplateViewClasses = [
		'directorist-elementor-card-template--grid',
		'directorist-elementor-card-template--list',
		'directorist-elementor-card-template--map',
	];
	const cardWrapperProxyPrefix = 'template_container_';
	const cardTemplateScopedSettingDenylist = {
		active_template_key: true,
		scoped_templates: true,
		template_status: true,
		_title: true,
		__globals__: true,
	};
	const homeSearchProtectedChildSettings = {
		_title: true,
		directorist_search_context: true,
		directorist_search_field_key: true,
		directorist_search_field_label: true,
		directorist_search_widget_name: true,
		directorist_search_design_type: true,
		directory_type_id: true,
		custom_field: true,
		button_text: true,
		show_icon: true,
		icon_only: true,
	};

	const registeredElementTypes = {};
	const viewCache = {};
	const modelCache = {};
	const elementTypeConfigCache = {};
	let responsiveDeviceBridgeBound = false;
	let previewLoadedBridgeBound = false;

	function isPromiseLike( value ) {
		return !! value && ( 'object' === typeof value || 'function' === typeof value ) && 'function' === typeof value.then;
	}

	function isConstructorLike( value ) {
		return 'function' === typeof value && value.prototype;
	}

	function getEditorConfig() {
		const config = window.directoristElementorV4Editor || {};

		return {
			directoryOptions: config.directoryOptions || {},
			viewLabels: config.viewLabels || {
				grid: 'Grid',
				list: 'List',
				map: 'Map',
			},
			strings: config.strings || {
				directoryFallback: 'Directory',
				firstSelectedDirectory: 'Use First Selected Directory',
				cardTemplateTitle: 'Listing Card Template',
				searchTemplateTitle: 'Search Fields',
			},
			searchFormFieldsByDirectory: config.searchFormFieldsByDirectory || {},
			homeSearchContractDirectoryTypeIds: config.homeSearchContractDirectoryTypeIds || [],
			homeSearchContractDefaultDirectoryTypeId: config.homeSearchContractDefaultDirectoryTypeId || '0',
			homeSearchContractSearchTemplates: config.homeSearchContractSearchTemplates || {},
			preview: config.preview || {},
		};
	}

	function getPreviewConfig() {
		const preview = getEditorConfig().preview || {};

		return {
			ajaxUrl: preview.ajaxUrl || '',
			nonce: preview.nonce || '',
			actions: preview.actions || {},
		};
	}

	function getElementTypeConfig( elementType ) {
		if ( ! elementType || ! window.elementor ) {
			return {};
		}

		if ( elementTypeConfigCache[ elementType ] ) {
			return elementTypeConfigCache[ elementType ];
		}

		const widgetConfig =
			window.elementor.widgetsCache && window.elementor.widgetsCache[ elementType ]
				? window.elementor.widgetsCache[ elementType ]
				: null;
		const elementConfig =
			window.elementor.config &&
			window.elementor.config.elements &&
			window.elementor.config.elements[ elementType ]
				? window.elementor.config.elements[ elementType ]
				: null;

		if ( widgetConfig || elementConfig ) {
			const config = Object.assign( {}, elementConfig || {}, widgetConfig || {} );
			const defaultKeys = [
				'default_children',
				'directorist_default_template_elements',
				'directorist_default_plan_elements',
				'directorist_default_author_elements',
				'directorist_default_search_elements',
			];

			defaultKeys.forEach( function( key ) {
				if (
					elementConfig &&
					Array.isArray( elementConfig[ key ] ) &&
					elementConfig[ key ].length &&
					( ! Array.isArray( config[ key ] ) || 0 === config[ key ].length )
				) {
					config[ key ] = elementConfig[ key ];
				}
			} );

			if ( elementConfig && elementConfig.controls && widgetConfig && widgetConfig.controls ) {
				config.controls = Object.assign( {}, elementConfig.controls, widgetConfig.controls );
			}

			if (
				elementConfig &&
				elementConfig.defaults &&
				Array.isArray( elementConfig.defaults.elements ) &&
				elementConfig.defaults.elements.length &&
				(
					! config.defaults ||
					! Array.isArray( config.defaults.elements ) ||
					0 === config.defaults.elements.length
				)
			) {
				config.defaults = Object.assign( {}, config.defaults || {}, {
					elements: elementConfig.defaults.elements,
				} );
			}

			elementTypeConfigCache[ elementType ] = config;
			return config;
		}

		return {};
	}

	function isDefaultChildAvailable( element ) {
		if ( ! element || 'object' !== typeof element ) {
			return false;
		}

		if ( 'widget' !== element.elType ) {
			return true;
		}

		return !! ( element.widgetType && Object.keys( getElementTypeConfig( element.widgetType ) ).length );
	}

	function cloneDefaultChildren( defaultChildren ) {
		if ( ! Array.isArray( defaultChildren ) ) {
			return [];
		}

		return $.extend( true, [], defaultChildren ).filter( isDefaultChildAvailable );
	}

	function getCardTemplateDefaultElements() {
		const config = getElementTypeConfig( CARD_TEMPLATE_WIDGET );

		return cloneDefaultChildren(
			config.directorist_default_template_elements ||
			config.default_template_elements ||
			[]
		);
	}

	function getPricingPlanDefaultElements() {
		const config = getElementTypeConfig( PRICING_PLANS_WIDGET );

		return cloneDefaultChildren(
			config.directorist_default_plan_elements ||
			config.default_plan_elements ||
			[]
		);
	}

	function getAuthorProfileDefaultElements() {
		const config = getElementTypeConfig( AUTHOR_PROFILE_WIDGET );

		return cloneDefaultChildren(
			config.directorist_default_author_elements ||
			config.default_author_elements ||
			[]
		);
	}

	function isDirectoristNestedWidget( widgetType ) {
		return -1 !== DIRECTORIST_NESTED_WIDGETS.indexOf( widgetType );
	}

	function isRelatedListingsWidget( widgetType ) {
		return RELATED_LISTINGS_WIDGET === widgetType;
	}

	function isTaxonomyCompositionWidget( widgetType ) {
		return -1 !== TAXONOMY_COMPOSITION_WIDGETS.indexOf( widgetType );
	}

	function isSearchCompositionWidget( widgetType ) {
		return -1 !== SEARCH_COMPOSITION_WIDGETS.indexOf( widgetType );
	}

	function isLoopUtilityWidget( widgetType ) {
		return -1 !== LOOP_UTILITY_WIDGETS.indexOf( widgetType );
	}

	function isLoopWidget( widgetType ) {
		return -1 !== LOOP_WIDGETS.indexOf( widgetType );
	}

	function isElementCreateTrace() {
		return !! (
			window.$e &&
			window.$e.commands &&
			Array.isArray( window.$e.commands.currentTrace ) &&
			window.$e.commands.currentTrace.includes( 'document/elements/create' )
		);
	}

	function getSafeNestedModelClass( nestedRepeater, widgetType ) {
		if ( modelCache[ widgetType ] ) {
			return modelCache[ widgetType ];
		}

		const BaseModel = nestedRepeater.NestedModelBase;
		const ElementModel =
			window.elementor &&
			window.elementor.modules &&
			window.elementor.modules.elements &&
			window.elementor.modules.elements.models
				? window.elementor.modules.elements.models.Element
				: null;
		const parentDefaults =
			'function' === typeof BaseModel.prototype.defaults
				? BaseModel.prototype.defaults()
				: ( BaseModel.prototype.defaults || {} );

		modelCache[ widgetType ] = BaseModel.extend( {
			defaults: function() {
				return $.extend( true, {}, parentDefaults, {
					elements: [],
				} );
			},

			initialize: function( options ) {
				const normalizedOptions = $.extend( {}, options, {
					widgetType:
						( options && options.widgetType ) ||
						this.get( 'widgetType' ) ||
						widgetType,
				} );
				const elements = this.get( 'elements' ) || [];

				this.config = window.elementor.widgetsCache[ normalizedOptions.widgetType ] || {};
				this.set( 'supportRepeaterChildren', true );

				if (
					0 === elements.length &&
					isElementCreateTrace() &&
					this.config &&
					this.config.defaults
				) {
					this.onElementCreate();
				}

				if ( ElementModel && ElementModel.prototype && 'function' === typeof ElementModel.prototype.initialize ) {
					ElementModel.prototype.initialize.call( this, normalizedOptions );
				}
			},
		} );

		return modelCache[ widgetType ];
	}

	function getNestedRepeaterExports() {
		if (
			! window.$e ||
			! window.$e.components ||
			typeof window.$e.components.get !== 'function'
		) {
			return null;
		}

		try {
			const component = window.$e.components.get( 'nested-elements/nested-repeater' );
			const exports = component && component.exports ? component.exports : null;

			if (
				! exports ||
				isPromiseLike( exports ) ||
				isPromiseLike( exports.NestedModelBase ) ||
				isPromiseLike( exports.NestedViewBase ) ||
				! isConstructorLike( exports.NestedModelBase ) ||
				! exports.NestedViewBase
			) {
				return null;
			}

			return exports;
		} catch ( error ) {
			return null;
		}
	}

	function getContainerType( container ) {
		if ( ! container || ! container.model || typeof container.model.get !== 'function' ) {
			return '';
		}

		return container.model.get( 'widgetType' ) || container.model.get( 'elType' ) || '';
	}

	function normalizeContainer( container ) {
		if ( ! container ) {
			return null;
		}

		if ( 'function' === typeof container.lookup ) {
			try {
				const lookedUp = container.lookup();

				if ( lookedUp ) {
					return lookedUp;
				}
			} catch ( error ) {
				return container;
			}
		}

		return container;
	}

	function getClosestAncestorWidget( container, widgetType ) {
		if ( Array.isArray( widgetType ) ) {
			let closest = null;

			widgetType.forEach( function( currentWidgetType ) {
				const found = getClosestAncestorWidget( container, currentWidgetType );

				if ( found && ( ! closest || getAncestorDepth( found ) > getAncestorDepth( closest ) ) ) {
					closest = found;
				}
			} );

			return closest;
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

	function getAncestorDepth( container ) {
		let depth = 0;
		let current = container;

		while ( current ) {
			depth++;
			current = current.parent || null;
		}

		return depth;
	}

	function getContainerView( container ) {
		container = normalizeContainer( container );

		if ( ! container ) {
			return null;
		}

		if ( container.view ) {
			return container.view;
		}

		if ( container.renderer && container.renderer.view ) {
			return container.renderer.view;
		}

		return null;
	}

	function getChildContainerAtIndex( container, index ) {
		container = normalizeContainer( container );

		if ( container && Array.isArray( container.children ) && container.children[ index ] ) {
			return normalizeContainer( container.children[ index ] );
		}

		if (
			container &&
			container.model &&
			typeof container.model.get === 'function' &&
			window.elementor &&
			typeof window.elementor.getContainer === 'function'
		) {
			const elements = container.model.get( 'elements' );

			if ( elements && typeof elements.at === 'function' ) {
				const childModel = elements.at( index );

				if ( childModel ) {
					const childContainer = window.elementor.getContainer( childModel.get( 'id' ) );

					if ( childContainer ) {
						return normalizeContainer( childContainer );
					}
				}
			}
		}

		const view = getContainerView( container );

		if ( ! view || ! view.children ) {
			return null;
		}

		if ( typeof view.children.findByIndex === 'function' ) {
			const childView = view.children.findByIndex( index );

			return childView && typeof childView.getContainer === 'function'
				? normalizeContainer( childView.getContainer() )
				: null;
		}

		const childViews = Object.values( view.children._views || {} );
		const childView = childViews[ index ] || null;

		return childView && typeof childView.getContainer === 'function'
			? normalizeContainer( childView.getContainer() )
			: null;
	}

	function getChildContainers( container ) {
		container = normalizeContainer( container );

		if (
			! container ||
			! container.model ||
			'function' !== typeof container.model.get
		) {
			return [];
		}

		const elements = container.model.get( 'elements' );
		const childContainers = [];

		if ( ! elements || 'function' !== typeof elements.forEach ) {
			return childContainers;
		}

		elements.forEach( function( childModel ) {
			if (
				! childModel ||
				! window.elementor ||
				'function' !== typeof window.elementor.getContainer
			) {
				return;
			}

			const childContainer = window.elementor.getContainer( childModel.get( 'id' ) );

			if ( childContainer ) {
				childContainers.push( normalizeContainer( childContainer ) );
			}
		} );

		return childContainers;
	}

	function getContainerSettings( container ) {
		if ( ! container ) {
			return null;
		}

		if ( container.settings ) {
			return container.settings;
		}

		if (
			container.model &&
			typeof container.model.get === 'function' &&
			container.model.get( 'settings' )
		) {
			return container.model.get( 'settings' );
		}

		return null;
	}

	function getSettingValue( settings, key, fallback ) {
		if ( ! settings ) {
			return fallback;
		}

		const value = typeof settings.get === 'function'
			? settings.get( key )
			: settings[ key ];

		return undefined === value || null === value ? fallback : value;
	}

	function normalizeDirectoryIds( value ) {
		if ( 'string' === typeof value ) {
			value = value.split( ',' );
		}

		if ( ! Array.isArray( value ) ) {
			return [];
		}

		const seen = new Set();

		return value
			.map( toInt )
			.filter( function( directoryId ) {
				if ( directoryId <= 0 || seen.has( directoryId ) ) {
					return false;
				}

				seen.add( directoryId );
				return true;
			} );
	}

	function normalizeLoopColumns( value ) {
		const columns = parseInt( value, 10 ) || 0;

		return Math.max( 1, Math.min( 6, columns || 3 ) );
	}

	function resolveResponsiveLoopColumns( settings, key, fallback ) {
		const value = getSettingValue( settings, key, '' );

		if ( '' === String( value || '' ).trim() ) {
			return fallback;
		}

		return normalizeLoopColumns( value );
	}

	function normalizeLoopGapCssValue( value, fallback ) {
		if ( value && 'object' === typeof value && ! Array.isArray( value ) ) {
			const size = parseFloat( value.size );
			const unit = String( value.unit || 'px' );

			if ( isFinite( size ) && size >= 0 ) {
				return `${ size }${ -1 !== [ 'px', 'em', 'rem', '%' ].indexOf( unit ) ? unit : 'px' }`;
			}
		}

		if ( isFinite( parseFloat( value ) ) && isNumericLike( value ) && parseFloat( value ) >= 0 ) {
			return `${ parseFloat( value ) }px`;
		}

		if ( 'string' === typeof value && /^\d+(?:\.\d+)?(?:px|em|rem|%)$/.test( value.trim() ) ) {
			return value.trim();
		}

		return fallback || '20px';
	}

	function isNumericLike( value ) {
		return '' !== String( value ?? '' ).trim() && ! isNaN( Number( value ) );
	}

	function resolveResponsiveLoopGap( settings, key, fallbackDesktop, fallbackTablet, fallbackMobile ) {
		const desktop = normalizeLoopGapCssValue(
			getSettingValue( settings, key, null ),
			fallbackDesktop
		);
		const tablet = normalizeLoopGapCssValue(
			getSettingValue( settings, `${ key }_tablet`, null ),
			fallbackTablet || desktop
		);
		const mobile = normalizeLoopGapCssValue(
			getSettingValue( settings, `${ key }_mobile`, null ),
			fallbackMobile || tablet
		);

		return { desktop, tablet, mobile };
	}

	function resolveLoopGapValues( settings ) {
		const legacyGap = normalizeLoopGapCssValue( getSettingValue( settings, 'card_gap', null ), '20px' );

		return {
			gridCardGap: resolveResponsiveLoopGap( settings, 'grid_card_gap', legacyGap ),
			listCardGap: resolveResponsiveLoopGap( settings, 'list_card_gap', legacyGap ),
			sliderCardGap: resolveResponsiveLoopGap( settings, 'slider_card_gap', '30px', '20px', '10px' ),
			mapListCardGap: resolveResponsiveLoopGap( settings, 'map_list_card_gap', legacyGap ),
			mapListPaneGap: resolveResponsiveLoopGap( settings, 'map_list_pane_gap', '24px' ),
		};
	}

	function resolveActiveLoopGap( gaps, activeView, displayMode ) {
		if ( 'slider' === displayMode ) {
			return gaps.sliderCardGap;
		}

		if ( 'map_list' === displayMode ) {
			return gaps.mapListCardGap;
		}

		if ( 'list' === activeView || 'map' === activeView ) {
			return gaps.listCardGap;
		}

		return gaps.gridCardGap;
	}

	function setLoopGapVariables( style, gaps, activeGap ) {
		const variableMap = {
			'--direl-loop-grid-card-gap': gaps.gridCardGap,
			'--direl-loop-list-card-gap': gaps.listCardGap,
			'--direl-loop-slider-card-gap': gaps.sliderCardGap,
			'--direl-map-list-card-gap': gaps.mapListCardGap,
			'--direl-map-list-pane-gap': gaps.mapListPaneGap,
		};

		style.setProperty( '--direl-loop-gap', activeGap.desktop );

		Object.keys( variableMap ).forEach( function( variableName ) {
			const values = variableMap[ variableName ] || {};

			style.setProperty( variableName, values.desktop || '' );
			style.setProperty( `${ variableName }-tablet`, values.tablet || values.desktop || '' );
			style.setProperty( `${ variableName }-mobile`, values.mobile || values.tablet || values.desktop || '' );
		} );
	}

	function loopGapCssValueToSwiperPx( value, fallback ) {
		const match = String( value || '' ).trim().match( /^(\d+(?:\.\d+)?)px?$/ );

		if ( match ) {
			return Math.max( 0, Math.round( parseFloat( match[ 1 ] ) ) );
		}

		return Math.max( 0, parseInt( fallback, 10 ) || 0 );
	}

	function buildLoopSliderBreakpointConfig( settings, gaps ) {
		const desktop = normalizeLoopColumns( getSettingValue( settings, 'slides_per_view', 3 ) );
		const tablet = resolveResponsiveLoopColumns( settings, 'slides_per_view_tablet', 2 );
		const mobile = resolveResponsiveLoopColumns( settings, 'slides_per_view_mobile', 1 );
		const sliderGaps = gaps.sliderCardGap || {};

		return {
			0: {
				slidesPerView: mobile,
				spaceBetween: loopGapCssValueToSwiperPx( sliderGaps.mobile || '10px', 10 ),
			},
			768: {
				slidesPerView: tablet,
				spaceBetween: loopGapCssValueToSwiperPx( sliderGaps.tablet || '20px', 20 ),
			},
			1200: {
				slidesPerView: desktop,
				spaceBetween: loopGapCssValueToSwiperPx( sliderGaps.desktop || '30px', 30 ),
			},
		};
	}

	function syncLoopSliderGapState( container, gaps ) {
		const settings = getContainerSettings( container );
		const view = getContainerView( container );

		if ( ! settings || ! view || ! view.$el ) {
			return;
		}

		const sliderGaps = gaps && gaps.sliderCardGap ? gaps.sliderCardGap : resolveLoopGapValues( settings ).sliderCardGap;
		const breakpointConfig = buildLoopSliderBreakpointConfig( settings, { sliderCardGap: sliderGaps } );
		const desktopGap = loopGapCssValueToSwiperPx( sliderGaps.desktop || '30px', 30 );
		const responsive = JSON.stringify( breakpointConfig );

		view.$el.find( '.directorist-elementor-listings-loop-slider' ).each( function() {
			this.setAttribute( 'data-sw-margin', String( desktopGap ) );
			this.setAttribute( 'data-sw-responsive', responsive );
		} );

		triggerLoopSliderEditorInit( view.$el );
	}

	function applyLoopEditorRootState( container ) {
		const containerType = getContainerType( container );

		if ( ! isLoopWidget( containerType ) ) {
			return;
		}

		const view = getContainerView( container );
		const settings = getContainerSettings( container );

		if ( ! view || ! view.$el || ! settings ) {
			return;
		}

		const activeView = resolveLoopPreviewView( settings );
		const displayMode = resolveLoopDisplayMode( settings, activeView );
		const activeDirectoryId = resolveLoopActiveDirectoryId( settings, container );
		const modelId = container && container.model && 'function' === typeof container.model.get
			? String( container.model.get( 'id' ) || '' )
			: '';
		const editorPostId = getEditorPostId();
		const instanceId = modelId ? `direl-loop-${ modelId }` : '';
		let columnsDesktop = normalizeLoopColumns( getSettingValue( settings, 'columns', 3 ) );
		let columnsTablet = resolveResponsiveLoopColumns( settings, 'columns_tablet', columnsDesktop );
		let columnsMobile = resolveResponsiveLoopColumns( settings, 'columns_mobile', columnsTablet );
		const gaps = resolveLoopGapValues( settings );
		const activeGap = resolveActiveLoopGap( gaps, activeView, displayMode );

		if ( 'grid' !== activeView ) {
			columnsDesktop = 1;
			columnsTablet = 1;
			columnsMobile = 1;
		}

		view.$el
			.addClass(
				'directorist-elementor-listings-loop directorist-elementor-loop directorist-archive-contents directorist-contents-wrap directorist-w-100'
			)
			.toggleClass( 'directorist-elementor-homepage-search-loop', HOME_SEARCH_LOOP_WIDGET === containerType )
			.removeClass( loopViewClasses.join( ' ' ) )
			.removeClass( loopDisplayModeClasses.join( ' ' ) )
			.addClass( `directorist-elementor-loop--${ activeView }` )
			.addClass( `directorist-elementor-loop--display-${ displayMode }` )
			.attr( {
				'data-direl-view': activeView,
				'data-direl-directory': activeDirectoryId > 0 ? String( activeDirectoryId ) : '',
				'data-display-mode': displayMode,
				'data-direl-loop-id': modelId,
				'data-direl-post-id': editorPostId > 0 ? String( editorPostId ) : '',
				'data-direl-request-post-id': editorPostId > 0 ? String( editorPostId ) : '',
				'data-current-page': '1',
				'data-max-pages': '1',
				'data-direl-instance': instanceId,
			} );

		const rootElement = view.el || view.$el.get( 0 );

		if ( rootElement && rootElement.style ) {
			rootElement.style.setProperty( '--direl-loop-columns', String( columnsDesktop ) );
			rootElement.style.setProperty( '--direl-loop-columns-tablet', String( columnsTablet ) );
			rootElement.style.setProperty( '--direl-loop-columns-mobile', String( columnsMobile ) );
			setLoopGapVariables( rootElement.style, gaps, activeGap );
		}
	}

	function normalizeEditorDeviceMode( deviceMode ) {
		const normalized = String( deviceMode || 'desktop' );

		if ( 0 === normalized.indexOf( 'mobile' ) ) {
			return 'mobile';
		}

		if ( 0 === normalized.indexOf( 'tablet' ) ) {
			return 'tablet';
		}

		return 'desktop';
	}

	function getCurrentEditorDeviceMode() {
		if (
			window.elementor &&
			window.elementor.channels &&
			window.elementor.channels.deviceMode &&
			'function' === typeof window.elementor.channels.deviceMode.request
		) {
			return normalizeEditorDeviceMode(
				window.elementor.channels.deviceMode.request( 'currentMode' )
			);
		}

		return 'desktop';
	}

	function applyEditorDeviceModeClasses( $elements ) {
		if ( ! $elements || ! $elements.length ) {
			return;
		}

		const deviceMode = getCurrentEditorDeviceMode();

		$elements
			.removeClass( editorDeviceClasses.join( ' ' ) )
			.addClass( `directorist-elementor--device-${ deviceMode }` )
			.attr( 'data-direl-editor-device', deviceMode );
	}

	function syncEditorDeviceModeForContainer( container ) {
		const view = getContainerView( container );

		if ( ! view || ! view.$el ) {
			return;
		}

		let $targets = view.$el.find( '.directorist-elementor-loop, .directorist-elementor-card-template, .directorist-elementor-taxonomy-widget, .directorist-elementor-pricing-plans, .directorist-elementor-author-profile' );

		if ( view.$el.is( '.directorist-elementor-loop, .directorist-elementor-card-template, .directorist-elementor-taxonomy-widget, .directorist-elementor-pricing-plans, .directorist-elementor-author-profile' ) ) {
			$targets = $targets.add( view.$el );
		}

		applyEditorDeviceModeClasses( $targets );
	}

	function syncAllEditorDeviceModeClasses() {
		if ( ! window.elementor || ! window.elementor.$previewContents ) {
			return;
		}

		applyEditorDeviceModeClasses(
			window.elementor.$previewContents.find( '.directorist-elementor-loop, .directorist-elementor-card-template, .directorist-elementor-taxonomy-widget, .directorist-elementor-pricing-plans, .directorist-elementor-author-profile' )
		);
	}

	function bindResponsiveDeviceBridge() {
		if ( responsiveDeviceBridgeBound ) {
			return;
		}

		responsiveDeviceBridgeBound = true;

		const syncDeviceMode = function() {
			window.requestAnimationFrame( function() {
				syncAllEditorDeviceModeClasses();
				triggerAllLoopSliderEditorInit();
			} );
		};

		if (
			window.elementor &&
			window.elementor.channels &&
			window.elementor.channels.deviceMode &&
			'function' === typeof window.elementor.listenTo
		) {
			window.elementor.listenTo(
				window.elementor.channels.deviceMode,
				'change',
				syncDeviceMode
			);
		}

		window.addEventListener( 'elementor/device-mode/change', syncDeviceMode );
	}

	function normalizeLoopView( view ) {
		const normalizedView = String( view || '' );

		return -1 !== [ 'grid', 'list', 'map' ].indexOf( normalizedView )
			? normalizedView
			: '';
	}

	function resolveLoopFrontendView( settings ) {
		if ( 'map_list' === String( getSettingValue( settings, 'display_mode', 'default' ) ) ) {
			return normalizeLoopView( getSettingValue( settings, 'map_list_view_type', '' ) ) ||
				normalizeLoopView( getSettingValue( settings, 'view_type', '' ) ) ||
				'grid';
		}

		return normalizeLoopView( getSettingValue( settings, 'view_type', '' ) ) || 'grid';
	}

	function resolveLoopPreviewView( settings ) {
		return normalizeLoopView( getSettingValue( settings, 'active_view_type', '' ) ) || resolveLoopFrontendView( settings );
	}

	function resolveLoopDisplayMode( settings, activeView ) {
		const resolvedView = normalizeLoopView( activeView ) || resolveLoopPreviewView( settings );
		const displayMode = String( getSettingValue( settings, 'display_mode', 'default' ) );

		if ( 'map_list' === displayMode ) {
			return 'map_list';
		}

		return 'slider' === displayMode && 'map' !== resolvedView
			? 'slider'
			: 'default';
	}

	function getLoopAvailableViews() {
		return [ 'grid', 'list', 'map' ];
	}

	function setSettingValue( container, key, value ) {
		const settings = getContainerSettings( container );

		if ( ! settings || typeof settings.setExternalChange !== 'function' ) {
			return;
		}

		settings.setExternalChange( key, value );
	}

	function getLoopEditorStateKey( container ) {
		return container && (
			container.id ||
			(
				container.model &&
				(
					'function' === typeof container.model.get
						? container.model.get( 'id' )
						: ''
				)
			) ||
			( container.model ? container.model.cid : '' )
		) || '';
	}

	function getContainerLockKey( container, prefix ) {
		return `${ prefix }:${ container && ( container.id || ( container.model ? container.model.cid : '' ) ) || '' }`;
	}

	function parseCardTemplateScopeSettings( value ) {
		if ( ! value ) {
			return {};
		}

		if ( 'object' === typeof value ) {
			return value;
		}

		if ( 'string' !== typeof value ) {
			return {};
		}

		try {
			const parsed = JSON.parse( value );

			return parsed && 'object' === typeof parsed ? parsed : {};
		} catch ( error ) {
			return {};
		}
	}

	function cloneEditorValue( value ) {
		if ( Array.isArray( value ) ) {
			return $.extend( true, [], value );
		}

		if ( value && 'object' === typeof value ) {
			return $.extend( true, {}, value );
		}

		return value;
	}

	function getElementControlDefinition( elementType, key ) {
		const controls = getElementTypeConfig( elementType ).controls || {};

		if ( controls[ key ] ) {
			return controls[ key ];
		}

		if ( Array.isArray( controls ) ) {
			return controls.find( function( control ) {
				return control && control.name === key;
			} ) || null;
		}

		return Object.keys( controls ).reduce( function( found, controlKey ) {
			const control = controls[ controlKey ];

			if ( found || ! control || control.name !== key ) {
				return found;
			}

			return control;
		}, null );
	}

	function getCardTemplateScopedSettingDefaultValue( key ) {
		const control = getElementControlDefinition( CARD_TEMPLATE_WIDGET, key ) ||
			( '_' === String( key || '' ).charAt( 0 )
				? getElementControlDefinition( CARD_TEMPLATE_WIDGET, String( key ).slice( 1 ) )
				: null );

		if ( control && Object.prototype.hasOwnProperty.call( control, 'default' ) ) {
			return cloneEditorValue( control.default );
		}

		if ( control && Object.prototype.hasOwnProperty.call( control, 'default_value' ) ) {
			return cloneEditorValue( control.default_value );
		}

		return '';
	}

	function normalizeCardTemplateScopedSettingValue( key, value ) {
		const defaultValue = getCardTemplateScopedSettingDefaultValue( key );

		if ( undefined === value || null === value ) {
			return cloneEditorValue( defaultValue );
		}

		if ( Array.isArray( defaultValue ) ) {
			return Array.isArray( value )
				? cloneEditorValue( value )
				: cloneEditorValue( defaultValue );
		}

		if ( defaultValue && 'object' === typeof defaultValue ) {
			return value && 'object' === typeof value && ! Array.isArray( value )
				? cloneEditorValue( value )
				: cloneEditorValue( defaultValue );
		}

		if ( value && 'object' === typeof value ) {
			return cloneEditorValue( defaultValue );
		}

		return value;
	}

	function isEmptyCardTemplateScopedValue( value ) {
		if ( undefined === value || null === value || '' === value ) {
			return true;
		}

		if ( false === value || 0 === value ) {
			return true;
		}

		if ( Array.isArray( value ) ) {
			return ! value.length || value.every( isEmptyCardTemplateScopedValue );
		}

		if ( value && 'object' === typeof value ) {
			const meaningfulKeys = Object.keys( value ).filter( function( key ) {
				return -1 === [ 'unit', 'isLinked', 'sizes' ].indexOf( key );
			} );

			if ( ! meaningfulKeys.length ) {
				return true;
			}

			return meaningfulKeys.every( function( key ) {
				return isEmptyCardTemplateScopedValue( value[ key ] );
			} );
		}

		return false;
	}

	function isCardTemplateScopedSettingDefaultValue( key, value ) {
		if ( isEmptyCardTemplateScopedValue( value ) ) {
			return true;
		}

		return JSON.stringify( value ) === JSON.stringify( getCardTemplateScopedSettingDefaultValue( key ) );
	}

	function areCardWrapperValuesEqual( currentValue, nextValue ) {
		return JSON.stringify( currentValue ?? '' ) === JSON.stringify( nextValue ?? '' );
	}

	function isCardWrapperProxyKey( key ) {
		return 'string' === typeof key && 0 === key.indexOf( cardWrapperProxyPrefix );
	}

	function isCardTemplateScopedSettingKey( key ) {
		if ( 'string' !== typeof key || ! key || cardTemplateScopedSettingDenylist[ key ] ) {
			return false;
		}

		if ( isCardWrapperProxyKey( key ) || '__dynamic__' === key ) {
			return true;
		}

		return false;
	}

	function normalizeCardTemplateGlobalBindings( value ) {
		const bindings = {};

		if ( ! value || 'object' !== typeof value || Array.isArray( value ) ) {
			return bindings;
		}

		Object.keys( value ).forEach( function( key ) {
			if ( value[ key ] ) {
				bindings[ key ] = value[ key ];
			}
		} );

		return bindings;
	}

	function getCardTemplateGlobalBindings( settings ) {
		const source = settings && 'function' === typeof settings.toJSON
			? settings.toJSON()
			: ( settings && settings.attributes ? settings.attributes : {} );
		const currentGlobals = getSettingValue( settings, '__globals__', null );

		if ( currentGlobals && 'object' === typeof currentGlobals && ! Array.isArray( currentGlobals ) ) {
			return normalizeCardTemplateGlobalBindings( currentGlobals );
		}

		return normalizeCardTemplateGlobalBindings( source && source.__globals__ );
	}

	function getCardTemplateChangedGlobalBindings( settings ) {
		return normalizeCardTemplateGlobalBindings(
			settings &&
			settings.changed &&
			settings.changed.__globals__
		);
	}

	function getCardTemplatePreviousGlobalBindings( settings ) {
		return normalizeCardTemplateGlobalBindings(
			settings &&
			'function' === typeof settings.previous
				? settings.previous( '__globals__' )
				: null
		);
	}

	function getCardTemplateScopeGlobalBindings( templateSettings ) {
		const source = parseCardTemplateScopeSettings( templateSettings );

		return normalizeCardTemplateGlobalBindings( source.__globals__ );
	}

	function getCardTemplateScopedSettingKeys( settings, templateSettings ) {
		const keys = {};
		const source = settings && 'function' === typeof settings.toJSON
			? settings.toJSON()
			: ( settings && settings.attributes ? settings.attributes : {} );
		const nextSettings = parseCardTemplateScopeSettings( templateSettings );
		const currentGlobals = getCardTemplateGlobalBindings( settings );
		const templateGlobals = getCardTemplateScopeGlobalBindings( nextSettings );

		Object.keys( source || {} ).forEach( function( key ) {
			if (
				isCardTemplateScopedSettingKey( key ) &&
				! isCardTemplateScopedSettingDefaultValue( key, source[ key ] )
			) {
				keys[ key ] = true;
			}
		} );

		Object.keys( nextSettings || {} ).forEach( function( key ) {
			if ( isCardTemplateScopedSettingKey( key ) ) {
				keys[ key ] = true;
			}
		} );

		Object.keys( currentGlobals || {} ).forEach( function( key ) {
			if ( isCardTemplateScopedSettingKey( key ) ) {
				keys[ key ] = true;
			}
		} );

		Object.keys( templateGlobals || {} ).forEach( function( key ) {
			if ( isCardTemplateScopedSettingKey( key ) ) {
				keys[ key ] = true;
			}
		} );

		return Object.keys( keys );
	}

	function hasCardWrapperGlobalChange( settings, changedKeys ) {
		if ( -1 === changedKeys.indexOf( '__globals__' ) ) {
			return false;
		}

		const currentGlobals = getCardTemplateGlobalBindings( settings );
		const changedGlobals = getCardTemplateChangedGlobalBindings( settings );
		const previousGlobals = getCardTemplatePreviousGlobalBindings( settings );
		const globalKeys = {};

		[ currentGlobals, changedGlobals, previousGlobals ].forEach( function( globals ) {
			Object.keys( globals || {} ).forEach( function( key ) {
				globalKeys[ key ] = true;
			} );
		} );

		return Object.keys( globalKeys ).some( isCardTemplateScopedSettingKey );
	}

	function extractCardTemplateScopeSettings( settings ) {
		if ( ! settings ) {
			return {};
		}

		const templateSettings = {};
		const source = 'function' === typeof settings.toJSON
			? settings.toJSON()
			: ( settings.attributes || {} );
		const globals = getCardTemplateGlobalBindings( settings );

		Object.keys( source || {} ).forEach( function( key ) {
			const value = source[ key ];

			if (
				isCardTemplateScopedSettingKey( key ) &&
				! isCardTemplateScopedSettingDefaultValue( key, value )
			) {
				templateSettings[ key ] = cloneEditorValue( value );
			}
		} );

		Object.keys( globals ).forEach( function( key ) {
			const value = globals[ key ];

			if ( isCardTemplateScopedSettingKey( key ) && value ) {
				if ( ! templateSettings.__globals__ ) {
					templateSettings.__globals__ = {};
				}

				templateSettings.__globals__[ key ] = value;
			}
		} );

		return templateSettings;
	}

	function getEditorPostId() {
		if (
			window.elementor &&
			window.elementor.config &&
			window.elementor.config.document &&
			window.elementor.config.document.id
		) {
			return toInt( window.elementor.config.document.id );
		}

		return 0;
	}

	function getSelectedEditorContainer() {
		const selection =
			window.elementor &&
			window.elementor.selection &&
			'function' === typeof window.elementor.selection.getElements
				? window.elementor.selection.getElements()
				: [];
		const selected = Array.isArray( selection ) ? selection[ 0 ] : null;

		return selected && selected.view && 'function' === typeof selected.view.getContainer
			? normalizeContainer( selected.view.getContainer() )
			: null;
	}

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

	function getPanelEditorActiveTab( pageView ) {
		if ( pageView && pageView.activeTab ) {
			return pageView.activeTab;
		}

		const editSettings =
			pageView &&
			pageView.model &&
			'function' === typeof pageView.model.get
				? pageView.model.get( 'editSettings' )
				: null;
		const panelSettings = editSettings && 'function' === typeof editSettings.get
			? editSettings.get( 'panel' )
			: null;

		return panelSettings && panelSettings.activeTab ? panelSettings.activeTab : 'content';
	}

	function syncPanelEditorActiveTabClass( tab ) {
		const page = document.querySelector( '#elementor-panel-page-editor' );

		if ( ! page || ! tab ) {
			return;
		}

		page
			.querySelectorAll( '.elementor-panel-navigation-tab' )
			.forEach( function( item ) {
				item.classList.toggle( 'elementor-active', item.getAttribute( 'data-tab' ) === tab );
			} );
	}

	function refreshCardTemplateControlPanel( container ) {
		const selectedContainer = getSelectedEditorContainer();

		if (
			! container ||
			! selectedContainer ||
			selectedContainer.id !== container.id
		) {
			return;
		}

		const pageView = getCurrentPanelPageView();
		const containerView = getContainerView( container );

		if (
			! pageView ||
			! containerView ||
			'function' !== typeof pageView.getOption ||
			pageView.getOption( 'editedElementView' ) !== containerView
		) {
			return;
		}

		window.requestAnimationFrame( function() {
			const currentSelectedContainer = getSelectedEditorContainer();

			if ( ! currentSelectedContainer || currentSelectedContainer.id !== container.id ) {
				return;
			}

			syncPanelEditorActiveTabClass( getPanelEditorActiveTab( pageView ) );
		} );
	}

	function captureCardTemplatePanelState( container ) {
		const selectedContainer = getSelectedEditorContainer();

		if (
			CARD_TEMPLATE_WIDGET !== getContainerType( container ) ||
			! selectedContainer ||
			selectedContainer.id !== container.id
		) {
			return null;
		}

		const pageView = getCurrentPanelPageView();
		const containerView = getContainerView( container );

		if (
			! pageView ||
			! containerView ||
			'function' !== typeof pageView.getOption ||
			pageView.getOption( 'editedElementView' ) !== containerView
		) {
			return null;
		}

		const activeTab = getPanelEditorActiveTab( pageView );

		return {
			activeTab: -1 !== [ 'content', 'style', 'layout', 'advanced' ].indexOf( activeTab )
				? activeTab
				: '',
		};
	}

	function restoreCardTemplatePanelState( container, state ) {
		if ( ! state || ! state.activeTab ) {
			refreshCardTemplateControlPanel( container );
			return false;
		}

		const selectedContainer = getSelectedEditorContainer();
		const pageView = getCurrentPanelPageView();
		const containerView = getContainerView( container );

		if (
			! selectedContainer ||
			selectedContainer.id !== container.id ||
			! pageView ||
			! containerView ||
			'function' !== typeof pageView.getOption ||
			pageView.getOption( 'editedElementView' ) !== containerView
		) {
			return false;
		}

		if ( 'function' === typeof pageView.activateTab ) {
			try {
				stabilizePanelControlDestroyables( pageView );
				pageView.activateTab( state.activeTab );
			} catch ( error ) {
				return false;
			}
		}

		syncPanelEditorActiveTabClass( state.activeTab );

		return true;
	}

	function stabilizePanelControlDestroyables( pageView ) {
		if (
			! pageView ||
			! pageView.children ||
			'function' !== typeof pageView.children.each
		) {
			return;
		}

		pageView.children.each( function( childView ) {
			if (
				childView &&
				'function' === typeof childView.onBeforeDestroy &&
				-1 !== String( childView.onBeforeDestroy ).indexOf( 'colorPicker.destroy' ) &&
				(
					! childView.colorPicker ||
					'function' !== typeof childView.colorPicker.destroy
				)
			) {
				childView.colorPicker = {
					destroy: function() {},
				};
			}
		} );
	}

	function prepareCardTemplatePanelForStructureSwap( container, state ) {
		if ( ! state || ! state.activeTab || 'layout' === state.activeTab ) {
			return;
		}

		const selectedContainer = getSelectedEditorContainer();
		const pageView = getCurrentPanelPageView();
		const containerView = getContainerView( container );

		if (
			! selectedContainer ||
			selectedContainer.id !== container.id ||
			! pageView ||
			! containerView ||
			'function' !== typeof pageView.getOption ||
			'function' !== typeof pageView.activateTab ||
			pageView.getOption( 'editedElementView' ) !== containerView
		) {
			return;
		}

		try {
			stabilizePanelControlDestroyables( pageView );
			pageView.activateTab( 'layout' );
		} catch ( error ) {}
	}

	function scheduleCardTemplatePanelStateRestore( container, state ) {
		const lockKey = getContainerLockKey( container, 'card-template-panel' );
		const delays = [ 120, 280, 520 ];

		if ( cardPanelRestoreTimers.has( lockKey ) ) {
			cardPanelRestoreTimers.get( lockKey ).forEach( function( timer ) {
				window.clearTimeout( timer );
			} );
		}

		cardPanelRestoreTimers.set(
			lockKey,
			delays.map( function( delay, index ) {
				return window.setTimeout( function() {
					window.requestAnimationFrame( function() {
						const restored = restoreCardTemplatePanelState( container, state );

						if ( restored || index === delays.length - 1 ) {
							cardPanelRestoreTimers.delete( lockKey );
						}
					} );
				}, delay );
			} )
		);
	}

	function ensureLoopContentTabActive( view ) {
		if ( ! view || ! view.model ) {
			return;
		}

		const pageView = getCurrentPanelPageView();

		if (
			! pageView ||
			'function' !== typeof pageView.getOption ||
			'function' !== typeof pageView.activateTab
		) {
			return;
		}

		const editedElementView = pageView.getOption( 'editedElementView' );

		if ( editedElementView !== view ) {
			return;
		}

		if ( 'content' !== pageView.activeTab ) {
			pageView.activateTab( 'content' );
		}
	}

	function toInt( value ) {
		const parsed = parseInt( value, 10 );

		return Number.isNaN( parsed ) ? 0 : parsed;
	}

	function getDirectoryLabel( directoryTypeId ) {
		const config = getEditorConfig();
		const key = String( toInt( directoryTypeId ) );
		const label = config.directoryOptions[ key ];

		return label || config.strings.directoryFallback || 'Directory';
	}

	function getViewLabel( viewType ) {
		const config = getEditorConfig();
		const key = String( viewType || 'grid' );

		return config.viewLabels[ key ] || config.viewLabels.grid || 'Grid';
	}

	function getCardActiveViewLabel( viewType ) {
		const strings = getEditorConfig().strings || {};

		switch ( String( viewType || 'grid' ) ) {
			case 'list':
				return strings.editingListCard || 'Editing List Card';
			case 'map':
				return strings.editingMapCard || 'Editing Map Card';
			case 'grid':
			default:
				return strings.editingGridCard || 'Editing Grid Card';
		}
	}

	function getLoopDirectoryIds( settings, container ) {
		return normalizeDirectoryIds( getSettingValue( settings, 'directory_type_ids', [] ) );
	}

	function getLoopDefaultDirectoryId( settings, container ) {
		return toInt( getSettingValue( settings, 'default_directory_type_id', 0 ) );
	}

	function getLoopDirectoryTabs( settings, container ) {
		const config = getEditorConfig();
		const directoryOptions = config.directoryOptions || {};
		const selectedDirectoryIds = getLoopDirectoryIds( settings, container );
		const seenDirectories = new Set();
		const tabs = [];
		const pushDirectoryTab = ( directoryId ) => {
			const normalizedId = toInt( directoryId );

			if ( normalizedId <= 0 || seenDirectories.has( normalizedId ) ) {
				return;
			}

			const key = String( normalizedId );

			if ( undefined === directoryOptions[ key ] ) {
				return;
			}

			seenDirectories.add( normalizedId );
			tabs.push( {
				id: normalizedId,
				label: directoryOptions[ key ],
			} );
		};

		if ( selectedDirectoryIds.length ) {
			selectedDirectoryIds.forEach( pushDirectoryTab );
		} else {
			Object.keys( directoryOptions ).forEach( pushDirectoryTab );
		}

		return tabs;
	}

	function getLoopDefaultDirectoryOptions( settings, container ) {
		const strings = getEditorConfig().strings || {};
		const options = {
			0: strings.firstSelectedDirectory || 'Use First Selected Directory',
		};

		getLoopDirectoryTabs( settings, container ).forEach( function( tab ) {
			options[ String( tab.id ) ] = tab.label;
		} );

		return options;
	}

	function resolveLoopActiveDirectoryId( settings, container ) {
		const tabs = getLoopDirectoryTabs( settings, container );
		const activeDirectoryId = toInt( getSettingValue( settings, 'active_directory_type_id', 0 ) );

		if ( tabs.some( ( tab ) => tab.id === activeDirectoryId ) ) {
			return activeDirectoryId;
		}

		return tabs.length ? tabs[ 0 ].id : 0;
	}

	function normalizeRepeaterItems( items ) {
		if ( ! items ) {
			return [];
		}

		if ( Array.isArray( items ) ) {
			return items;
		}

		if ( items.models && Array.isArray( items.models ) ) {
			return items.models.map( ( item ) => {
				if ( item && typeof item.toJSON === 'function' ) {
					return item.toJSON();
				}

				return item && item.attributes ? item.attributes : item;
			} );
		}

		return [];
	}

	function serializeEditorValue( value ) {
		if ( null === value || undefined === value ) {
			return value;
		}

		if ( Array.isArray( value ) ) {
			return value.map( serializeEditorValue );
		}

		if ( value && value.models && Array.isArray( value.models ) ) {
			return value.models.map( serializeEditorValue );
		}

		if ( value && value.attributes ) {
			const serialized = {};

			Object.keys( value.attributes ).forEach( function( key ) {
				serialized[ key ] = serializeEditorValue( value.attributes[ key ] );
			} );

			return serialized;
		}

		if ( value && 'object' === typeof value ) {
			const serialized = {};

			Object.keys( value ).forEach( function( key ) {
				serialized[ key ] = serializeEditorValue( value[ key ] );
			} );

			return serialized;
		}

		return value;
	}

	function serializeElementModel( model ) {
		if ( ! model || 'function' !== typeof model.get ) {
			return null;
		}

		const childElements = model.get( 'elements' );
		const elements = [];

		if ( childElements && 'function' === typeof childElements.forEach ) {
			childElements.forEach( function( childModel ) {
				const serializedChild = serializeElementModel( childModel );

				if ( serializedChild ) {
					elements.push( serializedChild );
				}
			} );
		}

		const elementData = {
			id: model.get( 'id' ),
			elType: model.get( 'elType' ),
			settings: serializeEditorValue( model.get( 'settings' ) ),
			elements: elements,
			isInner: !! model.get( 'isInner' ),
		};

		if ( true === model.get( 'hidden' ) ) {
			elementData.hidden = true;
		}

		if ( model.get( 'widgetType' ) ) {
			elementData.widgetType = model.get( 'widgetType' );
		}

		return elementData;
	}

	function serializeLoopUtilityElements( model ) {
		if ( ! model || 'function' !== typeof model.get ) {
			return [];
		}

		const childElements = model.get( 'elements' );
		const elements = [];

		if ( ! childElements || 'function' !== typeof childElements.forEach ) {
			return elements;
		}

		childElements.forEach( function( childModel ) {
			const widgetType = childModel && 'function' === typeof childModel.get
				? childModel.get( 'widgetType' )
				: '';

			if ( isLoopUtilityWidget( widgetType ) ) {
				elements.push( {
					id: childModel.get( 'id' ),
					elType: childModel.get( 'elType' ),
					widgetType: widgetType,
					settings: serializeEditorValue( childModel.get( 'settings' ) ),
					elements: [],
				} );
				return;
			}

			serializeLoopUtilityElements( childModel ).forEach( function( element ) {
				elements.push( element );
			} );
		} );

		return elements;
	}

	function buildLoopPayload( container ) {
		const settings = getContainerSettings( container );
		const elementType = getContainerType( container );
		const elementData = HOME_SEARCH_LOOP_WIDGET === elementType && container && container.model
			? serializeElementModel( container.model )
			: null;
		const utilityElements = LOOP_WIDGET === elementType && container && container.model
			? serializeLoopUtilityElements( container.model )
			: [];

		if ( ! settings ) {
			return null;
		}

		return {
			id: container && container.model ? container.model.get( 'id' ) : '',
			elType: elementType,
			settings: serializeEditorValue( settings ),
			elements: HOME_SEARCH_LOOP_WIDGET === elementType && elementData && Array.isArray( elementData.elements )
				? elementData.elements
				: utilityElements,
		};
	}

	function abortPreviewRequest( key ) {
		const request = previewRequests.get( key );

		if ( request && 'function' === typeof request.abort ) {
			request.abort();
		}

		previewRequests.delete( key );
	}

	function sendPreviewRequest( key, action, payload ) {
		const previewConfig = getPreviewConfig();

		if ( ! previewConfig.ajaxUrl || ! previewConfig.nonce || ! action ) {
			return $.Deferred().reject().promise();
		}

		abortPreviewRequest( key );

		const request = $.ajax( {
			url: previewConfig.ajaxUrl,
			method: 'POST',
			dataType: 'json',
			data: {
				action: action,
				nonce: previewConfig.nonce,
				payload: JSON.stringify( $.extend( {
					editor_post_id: getEditorPostId(),
				}, payload || {} ) ),
			},
		} );

		previewRequests.set( key, request );

		request.always( function() {
			if ( previewRequests.get( key ) === request ) {
				previewRequests.delete( key );
			}
		} );

		return request;
	}

	function createPreviewRequestToken( key ) {
		const token = ( previewRequestTokens.get( key ) || 0 ) + 1;

		previewRequestTokens.set( key, token );

		return token;
	}

	function isPreviewRequestTokenCurrent( key, token ) {
		return previewRequestTokens.get( key ) === token;
	}

	function queuePreviewRequest( key, callback, delay ) {
		if ( previewTimers.has( key ) ) {
			window.clearTimeout( previewTimers.get( key ) );
		}

		previewTimers.set(
			key,
			window.setTimeout( function() {
				previewTimers.delete( key );
				callback();
			}, delay || 120 )
		);
	}

	function buildCardBranchKey( directoryTypeId, viewType ) {
		return `dir-${ toInt( directoryTypeId ) }-${ String( viewType || 'grid' ) }`;
	}

	function parseCardBranchKey( key ) {
		const match = String( key || '' ).match( /^dir-(\d+)-([a-z0-9_-]+)$/ );

		if ( ! match ) {
			return null;
		}

		const directoryTypeId = String( toInt( match[ 1 ] ) );
		const viewType = normalizeLoopView( match[ 2 ] || 'grid' ) || 'grid';

		return {
			key: buildCardBranchKey( directoryTypeId, viewType ),
			directoryTypeId,
			viewType,
			label: getScopeDisplayTitle( directoryTypeId, viewType ),
		};
	}

	function getScopeDisplayTitle( directoryTypeId, viewType ) {
		return `${ getDirectoryLabel( directoryTypeId ) } / ${ getViewLabel( viewType ) }`;
	}

	function getCardTemplateScopeState( container ) {
		const loopEditingState = getLoopEditingState( container );

		if ( loopEditingState ) {
			return {
				key: buildCardBranchKey( loopEditingState.activeDirectoryId, loopEditingState.activeView ),
				directoryTypeId: String( toInt( loopEditingState.activeDirectoryId ) ),
				viewType: String( loopEditingState.activeView || 'grid' ),
				label: getScopeDisplayTitle( loopEditingState.activeDirectoryId, loopEditingState.activeView ),
			};
		}

		const settings = getContainerSettings( container );
		const activeKey = String( getSettingValue( settings, 'active_template_key', '' ) || '' );
		const activeDirectoryId = toInt( getSettingValue( settings, 'active_directory_type_id', 0 ) );
		const activeView = resolveLoopPreviewView( settings );
		const key = activeKey || buildCardBranchKey( activeDirectoryId, activeView );

		return {
			key,
			directoryTypeId: String( activeDirectoryId ),
			viewType: String( activeView || 'grid' ),
			label: getScopeDisplayTitle( activeDirectoryId, activeView ),
		};
	}

	function getCardTemplateCurrentPersistState( container ) {
		const settings = getContainerSettings( container );
		const activeKey = String( getSettingValue( settings, 'active_template_key', '' ) || '' );
		const parsedState = parseCardBranchKey( activeKey );

		return parsedState || getCardTemplateScopeState( container );
	}

	function parseCardTemplateScopes( value ) {
		if ( value && 'object' === typeof value && ! Array.isArray( value ) ) {
			return $.extend( true, {}, value );
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

	function getCardTemplateScopes( container ) {
		const settings = getContainerSettings( container );

		return parseCardTemplateScopes( getSettingValue( settings, 'scoped_templates', '{}' ) );
	}

	function setCardTemplateScopes( container, scopes ) {
		setSettingValue( container, 'scoped_templates', JSON.stringify( scopes || {} ) );
	}

	function normalizeCardTemplateScope( scope, fallbackState ) {
		const source = scope && 'object' === typeof scope && ! Array.isArray( scope ) ? scope : {};
		const fallback = fallbackState || {};
		const directoryTypeId = String( toInt( source.directory_type_id ?? fallback.directoryTypeId ?? 0 ) );
		const viewType = normalizeLoopView( source.view_type || fallback.viewType || 'grid' ) || 'grid';

		return {
			directory_type_id: directoryTypeId,
			view_type: viewType,
			label: String( source.label || fallback.label || getScopeDisplayTitle( directoryTypeId, viewType ) ),
			settings: parseCardTemplateScopeSettings( source.settings ),
			elements: Array.isArray( source.elements ) ? $.extend( true, [], source.elements ) : [],
		};
	}

	function normalizeCardTemplateScopeForState( scope, state ) {
		const normalized = normalizeCardTemplateScope( scope, state );
		const directoryTypeId = String( toInt( state && state.directoryTypeId ) );
		const viewType = normalizeLoopView( state && state.viewType || 'grid' ) || 'grid';

		normalized.directory_type_id = directoryTypeId;
		normalized.view_type = viewType;
		normalized.label = String(
			state && state.label
				? state.label
				: getScopeDisplayTitle( directoryTypeId, viewType )
		);

		return normalized;
	}

	function getCardTemplateProjectionState( container ) {
		const lockKey = getContainerLockKey( container, 'card-template-scope' );

		if ( ! cardTemplateProjectionStates.has( lockKey ) ) {
			cardTemplateProjectionStates.set( lockKey, {
				initialized: false,
				key: '',
				transition: 0,
			} );
		}

		return cardTemplateProjectionStates.get( lockKey );
	}

	function getCardTemplateRawChildModels( container ) {
		const elements =
			container &&
			container.model &&
			'function' === typeof container.model.get
				? container.model.get( 'elements' )
				: null;
		const children = [];

		if ( elements && 'function' === typeof elements.forEach ) {
			elements.forEach( function( childModel ) {
				if ( childModel ) {
					children.push( childModel );
				}
			} );
		}

		return children;
	}

	function getCardTemplateElementIds( elements ) {
		return ( Array.isArray( elements ) ? elements : [] )
			.map( function( element ) {
				return String( element && element.id || '' );
			} )
			.filter( Boolean );
	}

	function getCardTemplateProjectionIds( container ) {
		return getCardTemplateRawChildModels( container )
			.map( function( childModel ) {
				return childModel && 'function' === typeof childModel.get
					? String( childModel.get( 'id' ) || '' )
					: '';
			} )
			.filter( Boolean );
	}

	function cardTemplateProjectionMatches( container, elements ) {
		return JSON.stringify( getCardTemplateProjectionIds( container ) ) === JSON.stringify( getCardTemplateElementIds( elements ) );
	}

	function getCardTemplateElementOwners( scopes ) {
		const owners = {};

		Object.keys( scopes || {} ).forEach( function( scopeKey ) {
			const scope = normalizeCardTemplateScope( scopes[ scopeKey ] );

			getCardTemplateElementIds( scope.elements ).forEach( function( elementId ) {
				if ( ! owners[ elementId ] ) {
					owners[ elementId ] = [];
				}

				if ( -1 === owners[ elementId ].indexOf( scopeKey ) ) {
					owners[ elementId ].push( scopeKey );
				}
			} );
		} );

		return owners;
	}

	function serializeCardTemplateChildren( container, state, scopes ) {
		const owners = getCardTemplateElementOwners( scopes );
		const children = [];
		let valid = true;

		getCardTemplateRawChildModels( container ).forEach( function( childModel ) {
			const elementId = childModel && 'function' === typeof childModel.get
				? String( childModel.get( 'id' ) || '' )
				: '';
			const foreignOwners = ( owners[ elementId ] || [] ).filter( function( ownerKey ) {
				return ! state || ownerKey !== state.key;
			} );

			if ( foreignOwners.length ) {
				valid = false;
				return;
			}

			const child = serializeElementModel( childModel );

			if ( child ) {
				children.push( child );
			}
		} );

		return { elements: children, valid };
	}

	function persistCardTemplateActiveScope( container, scopeState, options ) {
		if ( CARD_TEMPLATE_WIDGET !== getContainerType( container ) ) {
			return false;
		}

		options = options || {};

		const lockKey = getContainerLockKey( container, 'card-template-scope' );
		const settingsLockKey = getContainerLockKey( container, 'card-template-settings' );

		if ( branchSyncLocks.has( lockKey ) && ! scopeState ) {
			return false;
		}

		if ( cardTemplateSettingsLocks.has( settingsLockKey ) && ! scopeState ) {
			scheduleCardTemplateSettingsLockFlush( container, 80 );
			return false;
		}

		const state = scopeState || getCardTemplateScopeState( container );

		if ( ! state || ! state.key ) {
			return false;
		}

		const scopes = getCardTemplateScopes( container );
		const settings = getContainerSettings( container );
		const activeKey = String( getSettingValue( settings, 'active_template_key', '' ) || '' );

		if ( activeKey && activeKey !== state.key ) {
			return false;
		}

		const existingScope = normalizeCardTemplateScopeForState( scopes[ state.key ], {
			directoryTypeId: state.directoryTypeId,
			viewType: state.viewType,
			label: state.label,
		} );

		if (
			! options.force &&
			! scopeState &&
			isCardTemplateScopeMissingLiveSettings( settings, existingScope.settings || {} )
		) {
			hydrateCardTemplateScopeSettings( container, existingScope.settings || {} );
			applyCardTemplateSettingsToEditor( container );
			return false;
		}

		const projection = serializeCardTemplateChildren( container, state, scopes );

		if ( ! projection.valid ) {
			return false;
		}

		const nextScope = normalizeCardTemplateScope( {
			directory_type_id: state.directoryTypeId,
			view_type: state.viewType,
			label: state.label,
			settings: extractCardTemplateScopeSettings( settings ),
			elements: projection.elements,
		}, state );
		const currentSerialized = JSON.stringify( scopes[ state.key ] || {} );
		const nextSerialized = JSON.stringify( nextScope );

		if ( currentSerialized === nextSerialized ) {
			return false;
		}

		scopes[ state.key ] = nextScope;
		setCardTemplateScopes( container, scopes );

		return true;
	}

	function scheduleCardTemplateActiveScopePersist( container, delay, options ) {
		if ( CARD_TEMPLATE_WIDGET !== getContainerType( container ) ) {
			return;
		}

		options = options || {};

		const lockKey = getContainerLockKey( container, 'card-template-scope' );

		if ( cardScopePersistTimers.has( lockKey ) ) {
			window.clearTimeout( cardScopePersistTimers.get( lockKey ) );
		}

		cardScopePersistTimers.set(
			lockKey,
			window.setTimeout( function() {
				cardScopePersistTimers.delete( lockKey );

				if ( branchSyncLocks.has( lockKey ) ) {
					scheduleCardTemplateActiveScopePersist( container, delay || 120, options );
					return;
				}

				persistCardTemplateActiveScope( container, null, options );
			}, delay || 120 )
		);
	}

	function buildSearchBranchKey( directoryTypeId ) {
		return `dir-${ toInt( directoryTypeId ) }`;
	}

	function getSearchScopeDisplayTitle( directoryTypeId ) {
		return getDirectoryLabel( directoryTypeId );
	}

	function getSearchCompositionScopeState( container ) {
		const loopEditingState = getLoopEditingState( container );

		if ( loopEditingState ) {
			return {
				key: buildSearchBranchKey( loopEditingState.activeDirectoryId ),
				directoryTypeId: String( toInt( loopEditingState.activeDirectoryId ) ),
				label: getSearchScopeDisplayTitle( loopEditingState.activeDirectoryId ),
			};
		}

		const settings = getContainerSettings( container );
		if ( HOME_SEARCH_WIDGET === getContainerType( container ) ) {
			const config = getEditorConfig();
			const directoryIds = normalizeDirectoryIds( config.homeSearchContractDirectoryTypeIds );
			let activeDirectoryId = toInt(
				getSettingValue( settings, 'standalone_design_directory_type_id', 0 )
			);

			if ( activeDirectoryId <= 0 || ( directoryIds.length && -1 === directoryIds.indexOf( activeDirectoryId ) ) ) {
				activeDirectoryId = toInt( config.homeSearchContractDefaultDirectoryTypeId );
			}

			if ( activeDirectoryId <= 0 && directoryIds.length ) {
				activeDirectoryId = directoryIds[ 0 ];
			}

			return {
				key: buildSearchBranchKey( activeDirectoryId ),
				directoryTypeId: String( activeDirectoryId ),
				label: getSearchScopeDisplayTitle( activeDirectoryId ),
			};
		}

		const activeKey = String( getSettingValue( settings, 'active_search_template_key', '' ) || '' );
		const activeDirectoryId = toInt(
			getSettingValue(
				settings,
				'default_directory_type_id',
				Object.keys( getEditorConfig().directoryOptions || {} )[ 0 ] || 0
			)
		);
		const key = activeKey || buildSearchBranchKey( activeDirectoryId );

		return {
			key,
			directoryTypeId: String( activeDirectoryId ),
			label: getSearchScopeDisplayTitle( activeDirectoryId ),
		};
	}

	function parseSearchTemplateScopes( value ) {
		if ( value && 'object' === typeof value && ! Array.isArray( value ) ) {
			return $.extend( true, {}, value );
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

	function getSearchTemplateScopes( container ) {
		const settings = getContainerSettings( container );

		return parseSearchTemplateScopes( getSettingValue( settings, 'scoped_search_templates', '{}' ) );
	}

	function setSearchTemplateScopes( container, scopes ) {
		setSettingValue( container, 'scoped_search_templates', JSON.stringify( scopes || {} ) );
	}

	function normalizeSearchTemplateScope( scope, fallbackState ) {
		const source = scope && 'object' === typeof scope && ! Array.isArray( scope ) ? scope : {};
		const fallback = fallbackState || {};
		const directoryTypeId = String( toInt( source.directory_type_id ?? fallback.directoryTypeId ?? 0 ) );

		return {
			directory_type_id: directoryTypeId,
			label: String( source.label || fallback.label || getSearchScopeDisplayTitle( directoryTypeId ) ),
			elements: normalizeSearchCompositionElements( source.elements ),
		};
	}

	function normalizeSearchTemplateScopeForState( scope, state ) {
		const normalized = normalizeSearchTemplateScope( scope, state );
		const directoryTypeId = String( toInt( state && state.directoryTypeId ) );

		normalized.directory_type_id = directoryTypeId;
		normalized.label = String(
			state && state.label
				? state.label
				: getSearchScopeDisplayTitle( directoryTypeId )
		);

		return normalized;
	}

	function normalizeSearchCompositionElements( elements ) {
		if ( ! Array.isArray( elements ) ) {
			return [];
		}

		return $.extend( true, [], elements ).filter( function( element ) {
			return (
				element &&
				'object' === typeof element &&
				! element.hidden &&
				SEARCH_DIRECTORY_TYPES_WIDGET !== String( element.widgetType || '' )
			);
		} );
	}

	function getStandaloneHomepageSearchCanonicalScope( state ) {
		const scopes = getEditorConfig().homeSearchContractSearchTemplates || {};
		const scope = state && state.key ? scopes[ state.key ] : null;

		return normalizeSearchTemplateScopeForState( scope, state || {} );
	}

	function getSearchChildIdentity( element ) {
		const settings = element && element.settings && 'object' === typeof element.settings
			? element.settings
			: {};
		const widgetType = String( element && ( element.widgetType || element.elType ) || '' );
		const fieldKey = String( settings.directorist_search_field_key || '' );
		const customField = String( settings.custom_field || '' );

		if ( fieldKey ) {
			return `${ widgetType }|field:${ fieldKey }`;
		}

		if ( customField ) {
			return `${ widgetType }|custom:${ customField }`;
		}

		return `${ widgetType }|widget`;
	}

	function parseInheritedHomepageSearchSettings( value ) {
		if ( value && 'object' === typeof value && ! Array.isArray( value ) ) {
			return $.extend( true, {}, value );
		}

		if ( 'string' !== typeof value || ! value.trim() ) {
			return {};
		}

		try {
			const parsed = JSON.parse( value );

			return parsed && 'object' === typeof parsed && ! Array.isArray( parsed )
				? parsed
				: {};
		} catch ( error ) {
			return {};
		}
	}

	function areSearchSettingValuesEqual( first, second ) {
		return JSON.stringify( serializeEditorValue( first ) ) === JSON.stringify( serializeEditorValue( second ) );
	}

	function getStandaloneHomepageSearchInheritedSettings( canonicalElement ) {
		const canonicalSettings = canonicalElement && canonicalElement.settings && 'object' === typeof canonicalElement.settings
			? $.extend( true, {}, canonicalElement.settings )
			: {};

		delete canonicalSettings[ HOME_SEARCH_INHERITED_SETTINGS_KEY ];

		return canonicalSettings;
	}

	function getStandaloneHomepageSearchControlDefinition( elementType, key ) {
		let control = getElementControlDefinition( elementType, key );

		if ( control ) {
			return control;
		}

		const baseKey = String( key || '' ).replace( /_(tablet|mobile)$/i, '' );
		if ( baseKey !== key ) {
			control = getElementControlDefinition( elementType, baseKey );
		}

		return control;
	}

	function isStandaloneHomepageSearchDesignSetting( elementType, key ) {
		if ( '__globals__' === key ) {
			return true;
		}

		if ( 0 === String( key || '' ).indexOf( 'search_field_' ) ) {
			return true;
		}

		if ( 'directorist_search_submit_button' === elementType ) {
			return 0 === String( key || '' ).indexOf( 'button_' );
		}

		if ( SEARCH_MORE_FILTERS_BUTTON_WIDGET === elementType ) {
			return /^(container|button|icon|modal|field|action|apply|reset)_/.test( String( key || '' ) );
		}

		const control = getStandaloneHomepageSearchControlDefinition( elementType, key );

		return !! control && 'style' === String( control.tab || '' );
	}

	function getStandaloneHomepageSearchSettingDefault( elementType, key ) {
		if ( '__globals__' === key ) {
			return {};
		}

		const control = getStandaloneHomepageSearchControlDefinition( elementType, key );
		if ( control && Object.prototype.hasOwnProperty.call( control, 'default' ) ) {
			return serializeEditorValue( control.default );
		}

		if ( control && Object.prototype.hasOwnProperty.call( control, 'default_value' ) ) {
			return serializeEditorValue( control.default_value );
		}

		return '';
	}

	function mergeStandaloneHomepageSearchElement( canonicalElement, localElement ) {
		let canonical = $.extend( true, {}, canonicalElement || {} );
		const local = localElement && 'object' === typeof localElement
			? localElement
			: {};
		const canonicalSettings = canonical.settings && 'object' === typeof canonical.settings
			? $.extend( true, {}, canonical.settings )
			: {};
		const nextInheritedSettings = getStandaloneHomepageSearchInheritedSettings( canonical );
		const localSettings = local.settings && 'object' === typeof local.settings
			? local.settings
			: {};
		const inheritedSettings = parseInheritedHomepageSearchSettings(
			localSettings[ HOME_SEARCH_INHERITED_SETTINGS_KEY ]
		);
		const elementType = String( canonical.widgetType || canonical.elType || '' );
		const overrides = {};

		if ( Object.keys( inheritedSettings ).length ) {
			Object.keys( localSettings ).forEach( function( key ) {
				if (
					HOME_SEARCH_INHERITED_SETTINGS_KEY === key ||
					homeSearchProtectedChildSettings[ key ] ||
					! isStandaloneHomepageSearchDesignSetting( elementType, key )
				) {
					return;
				}

				const inheritedValue = Object.prototype.hasOwnProperty.call( inheritedSettings, key )
					? inheritedSettings[ key ]
					: getStandaloneHomepageSearchSettingDefault( elementType, key );

				if ( ! areSearchSettingValuesEqual( localSettings[ key ], inheritedValue ) ) {
					overrides[ key ] = serializeEditorValue( localSettings[ key ] );
				}
			} );
		}

		canonical.settings = $.extend( true, {}, canonicalSettings, overrides, {
			[ HOME_SEARCH_INHERITED_SETTINGS_KEY ]: JSON.stringify( nextInheritedSettings ),
		} );
		canonical.isInner = !! ( local.isInner || canonical.isInner );

		if ( local.id ) {
			canonical.id = local.id;
		} else {
			delete canonical.id;
			canonical = buildElementModelWithIds( canonical );
		}

		return canonical;
	}

	function reconcileStandaloneHomepageSearchElements( canonicalElements, localElements ) {
		const localQueues = {};

		normalizeSearchCompositionElements( localElements ).forEach( function( element ) {
			const identity = getSearchChildIdentity( element );
			localQueues[ identity ] = localQueues[ identity ] || [];
			localQueues[ identity ].push( element );
		} );

		return normalizeSearchCompositionElements( canonicalElements ).map( function( canonicalElement ) {
			const identity = getSearchChildIdentity( canonicalElement );
			const localElement = localQueues[ identity ] && localQueues[ identity ].length
				? localQueues[ identity ].shift()
				: null;

			return mergeStandaloneHomepageSearchElement( canonicalElement, localElement );
		} );
	}

	function syncStandaloneHomepageSearchScope( container ) {
		const desired = getSearchCompositionScopeState( container );
		const settings = getContainerSettings( container );
		const currentKey = String( getSettingValue( settings, 'active_search_template_key', '' ) || '' );
		const lockKey = getContainerLockKey( container, 'search-composition-scope' );

		if ( ! desired.key || branchSyncLocks.has( lockKey ) ) {
			return;
		}

		branchSyncLocks.add( lockKey );

		try {
			let scopes = getSearchTemplateScopes( container );

			if ( currentKey && currentKey !== desired.key ) {
				const previousScope = normalizeSearchTemplateScope( scopes[ currentKey ], {} );
				persistSearchCompositionActiveScope(
					container,
					{
						key: currentKey,
						directoryTypeId: previousScope.directory_type_id || '0',
						label: previousScope.label || '',
					},
					{ force: true }
				);
				scopes = getSearchTemplateScopes( container );
			}

			const canonicalScope = getStandaloneHomepageSearchCanonicalScope( desired );
			const localScope = normalizeSearchTemplateScopeForState( scopes[ desired.key ], desired );
			const localElements = currentKey === desired.key
				? serializeSearchCompositionChildren( container )
				: localScope.elements;
			const targetScope = normalizeSearchTemplateScopeForState(
				{
					directory_type_id: desired.directoryTypeId,
					label: canonicalScope.label || desired.label,
					elements: reconcileStandaloneHomepageSearchElements(
						canonicalScope.elements,
						localElements
					),
				},
				desired
			);
			const selectedContainer = getSelectedEditorContainer();
			const shouldSelectSearchContainer = isContainerDescendantOf( selectedContainer, container );

			scopes[ desired.key ] = targetScope;
			setSearchTemplateScopes( container, scopes );
			setSettingValue( container, 'active_search_template_key', desired.key );

			const currentElements = reconcileStandaloneHomepageSearchElements(
				canonicalScope.elements,
				serializeSearchCompositionChildren( container )
			);
			if ( JSON.stringify( currentElements ) !== JSON.stringify( targetScope.elements ) ) {
				replaceSearchCompositionChildren( container, targetScope.elements );
			}

			if ( shouldSelectSearchContainer && currentKey && currentKey !== desired.key ) {
				selectContainer( container );
			}
		} finally {
			window.setTimeout( function() {
				branchSyncLocks.delete( lockKey );
			}, 0 );
		}
	}

	function getSearchFieldDefaultElementsForDirectory( directoryTypeId ) {
		const config = getEditorConfig();
		const fieldsByDirectory = config.searchFormFieldsByDirectory || {};
		const fields = fieldsByDirectory[ String( toInt( directoryTypeId ) ) ] || {};
		const elements = [];

		Object.keys( fields ).forEach( function( fieldKey ) {
			const field = fields[ fieldKey ] || {};
			const widgetType = String( field.widget_type || '' );

			if ( ! widgetType || ! Object.keys( getElementTypeConfig( widgetType ) ).length ) {
				return;
			}

			const label = String( field.label || field.title || field.search_field_key || fieldKey );
			const settings = {
				_title: label,
				directorist_search_context: 'yes',
				directorist_search_field_key: String( field.search_field_key || fieldKey ),
				directorist_search_field_label: label,
				directorist_search_widget_name: String( field.widget_name || '' ),
				directorist_search_design_type: String( field.design_type || '' ),
				directory_type_id: toInt( directoryTypeId ),
			};

			if ( 'custom' === String( field.widget_group || '' ) ) {
				settings.custom_field = `${ toInt( directoryTypeId ) }|${ String( field.field_key || fieldKey ) }`;
			}

			elements.push( {
				elType: 'widget',
				widgetType,
				settings,
				elements: [],
				editor_settings: {
					title: label,
				},
			} );
		} );

		if ( Object.keys( getElementTypeConfig( SEARCH_MORE_FILTERS_BUTTON_WIDGET ) ).length ) {
			elements.push( {
				elType: 'widget',
				widgetType: SEARCH_MORE_FILTERS_BUTTON_WIDGET,
				settings: {
					_title: 'More Filters Button',
					directory_type_id: toInt( directoryTypeId ),
				},
				elements: [],
				editor_settings: {
					title: 'More Filters Button',
				},
			} );
		}

		if ( Object.keys( getElementTypeConfig( 'directorist_search_submit_button' ) ).length ) {
			elements.push( {
				elType: 'widget',
				widgetType: 'directorist_search_submit_button',
				settings: {
					_title: 'Search Button',
					directory_type_id: toInt( directoryTypeId ),
				},
				elements: [],
				editor_settings: {
					title: 'Search Button',
				},
			} );
		}

		return elements;
	}

	function getSearchCompositionDefaultElements( container, state ) {
		const generated = getSearchFieldDefaultElementsForDirectory( state && state.directoryTypeId );

		if ( generated.length ) {
			return generated;
		}

		const config = getElementTypeConfig( getContainerType( container ) );

		return cloneDefaultChildren(
			config.directorist_default_search_elements ||
			config.default_children ||
			( config.defaults && config.defaults.elements ) ||
			[]
		);
	}

	function serializeSearchCompositionChildren( container ) {
		const elements =
			container &&
			container.model &&
			'function' === typeof container.model.get
				? container.model.get( 'elements' )
				: null;
		const children = [];

		if ( elements && 'function' === typeof elements.forEach ) {
			elements.forEach( function( childModel ) {
				const child = serializeElementModel( childModel );

				if ( child && SEARCH_DIRECTORY_TYPES_WIDGET !== String( child.widgetType || '' ) ) {
					children.push( child );
				}
			} );
		}

		return children;
	}

	function getSearchElementDirectoryId( element ) {
		const settings = element && element.settings && 'object' === typeof element.settings
			? element.settings
			: {};

		return toInt( settings.directory_type_id );
	}

	function doSearchElementsBelongToDirectory( elements, directoryTypeId ) {
		const normalized = normalizeSearchCompositionElements( elements );
		const targetId = toInt( directoryTypeId );

		if ( ! normalized.length || targetId <= 0 ) {
			return false;
		}

		return normalized.every( function( element ) {
			const elementDirectoryId = getSearchElementDirectoryId( element );

			return elementDirectoryId <= 0 || elementDirectoryId === targetId;
		} );
	}

	function persistSearchCompositionActiveScope( container, scopeState, options ) {
		if ( ! isSearchCompositionWidget( getContainerType( container ) ) ) {
			return false;
		}

		options = options || {};

		const lockKey = getContainerLockKey( container, 'search-composition-scope' );

		if ( branchSyncLocks.has( lockKey ) && ! scopeState ) {
			return false;
		}

		const state = scopeState || getSearchCompositionScopeState( container );

		if ( ! state || ! state.key ) {
			return false;
		}

		const scopes = getSearchTemplateScopes( container );
		let elements = serializeSearchCompositionChildren( container );
		if ( HOME_SEARCH_WIDGET === getContainerType( container ) && ! getLoopEditingState( container ) ) {
			const canonicalScope = getStandaloneHomepageSearchCanonicalScope( state );
			elements = reconcileStandaloneHomepageSearchElements(
				canonicalScope.elements,
				elements
			);
		}

		const nextScope = normalizeSearchTemplateScope( {
			directory_type_id: state.directoryTypeId,
			label: state.label,
			elements,
		}, state );
		const currentSerialized = JSON.stringify( scopes[ state.key ] || {} );
		const nextSerialized = JSON.stringify( nextScope );

		if ( ! options.force && currentSerialized === nextSerialized ) {
			return false;
		}

		scopes[ state.key ] = nextScope;
		setSearchTemplateScopes( container, scopes );

		return true;
	}

	function scheduleSearchCompositionActiveScopePersist( container, delay, options ) {
		if ( ! isSearchCompositionWidget( getContainerType( container ) ) ) {
			return;
		}

		options = options || {};

		const lockKey = getContainerLockKey( container, 'search-composition-scope' );

		if ( searchScopePersistTimers.has( lockKey ) ) {
			window.clearTimeout( searchScopePersistTimers.get( lockKey ) );
		}

		searchScopePersistTimers.set(
			lockKey,
			window.setTimeout( function() {
				searchScopePersistTimers.delete( lockKey );

				if ( branchSyncLocks.has( lockKey ) ) {
					scheduleSearchCompositionActiveScopePersist( container, delay || 120, options );
					return;
				}

				persistSearchCompositionActiveScope( container, null, options );
			}, delay || 120 )
		);
	}

	function isOnlySearchTemplateStorageChange( changedKeys ) {
		return (
			Array.isArray( changedKeys ) &&
			changedKeys.length > 0 &&
			changedKeys.every( function( key ) {
				return -1 !== [ 'active_search_template_key', 'scoped_search_templates' ].indexOf( key );
			} )
		);
	}

	function replaceSearchCompositionChildren( container, elements ) {
		if (
			! container ||
			! window.$e ||
			'function' !== typeof window.$e.run
		) {
			return;
		}

		getChildContainers( container ).slice().reverse().forEach( function( childContainer ) {
			window.$e.run( 'document/elements/delete', {
				container: childContainer,
				force: true,
			} );
		} );

		normalizeSearchCompositionElements( elements ).forEach( function( element, index ) {
			window.$e.run( 'document/elements/create', {
				container,
				model: buildElementModelWithIds( element ),
				options: {
					edit: false,
					at: index,
				},
			} );
		} );
	}

	function isContainerDescendantOf( container, ancestor ) {
		let current = normalizeContainer( container );
		const target = normalizeContainer( ancestor );

		while ( current ) {
			if ( current === target || ( target && current.id && target.id && current.id === target.id ) ) {
				return true;
			}

			current = current.parent || null;
		}

		return false;
	}

	function syncSearchCompositionScope( container ) {
		if (
			! isSearchCompositionWidget( getContainerType( container ) ) ||
			! window.$e ||
			'function' !== typeof window.$e.run
		) {
			return;
		}

		if ( HOME_SEARCH_WIDGET === getContainerType( container ) && ! getLoopEditingState( container ) ) {
			syncStandaloneHomepageSearchScope( container );
			return;
		}

		const settings = getContainerSettings( container );
		const desired = getSearchCompositionScopeState( container );
		const currentKey = String( getSettingValue( settings, 'active_search_template_key', '' ) || '' );
		const lockKey = getContainerLockKey( container, 'search-composition-scope' );

		if ( ! desired.key || branchSyncLocks.has( lockKey ) ) {
			return;
		}

		branchSyncLocks.add( lockKey );

		try {
			if ( ! currentKey ) {
				const scopes = getSearchTemplateScopes( container );
				let targetScope = normalizeSearchTemplateScopeForState( scopes[ desired.key ], desired );

				if ( ! targetScope.elements.length ) {
					const currentChildren = serializeSearchCompositionChildren( container );
					targetScope = normalizeSearchTemplateScope( {
						directory_type_id: desired.directoryTypeId,
						label: desired.label,
						elements: doSearchElementsBelongToDirectory( currentChildren, desired.directoryTypeId )
							? currentChildren
							: getSearchCompositionDefaultElements( container, desired ),
					}, desired );
				}

				scopes[ desired.key ] = targetScope;
				setSearchTemplateScopes( container, scopes );
				setSettingValue( container, 'active_search_template_key', desired.key );

				if ( ! doSearchElementsBelongToDirectory( serializeSearchCompositionChildren( container ), desired.directoryTypeId ) ) {
					replaceSearchCompositionChildren( container, targetScope.elements || [] );
				}

				return;
			}

			if ( currentKey === desired.key ) {
				const scopes = getSearchTemplateScopes( container );
				const currentScope = normalizeSearchTemplateScopeForState( scopes[ desired.key ], desired );

				if ( ! currentScope.elements.length ) {
					scopes[ desired.key ] = normalizeSearchTemplateScope( {
						directory_type_id: desired.directoryTypeId,
						label: desired.label,
						elements: getSearchCompositionDefaultElements( container, desired ),
					}, desired );
					setSearchTemplateScopes( container, scopes );
					replaceSearchCompositionChildren( container, scopes[ desired.key ].elements || [] );
					return;
				}

				persistSearchCompositionActiveScope( container, desired );
				return;
			}

			const scopes = getSearchTemplateScopes( container );
			const previousScope = normalizeSearchTemplateScope( scopes[ currentKey ], desired );
			const previousState = {
				key: currentKey,
				directoryTypeId: previousScope.directory_type_id || desired.directoryTypeId,
				label: previousScope.label || desired.label,
			};
			const selectedContainer = getSelectedEditorContainer();
			const shouldSelectSearchContainer = isContainerDescendantOf( selectedContainer, container );

			persistSearchCompositionActiveScope( container, previousState );

			const latestScopes = getSearchTemplateScopes( container );
			const targetScope = normalizeSearchTemplateScopeForState( latestScopes[ desired.key ], desired );

			if ( ! targetScope.elements.length ) {
				targetScope.elements = getSearchCompositionDefaultElements( container, desired );
			}

			if ( JSON.stringify( latestScopes[ desired.key ] || {} ) !== JSON.stringify( targetScope ) ) {
				latestScopes[ desired.key ] = targetScope;
				setSearchTemplateScopes( container, latestScopes );
			}

			setSettingValue( container, 'active_search_template_key', desired.key );
			replaceSearchCompositionChildren( container, targetScope.elements || [] );

			if ( shouldSelectSearchContainer ) {
				selectContainer( container );
			}
		} finally {
			window.setTimeout( function() {
				branchSyncLocks.delete( lockKey );
			}, 0 );
		}
	}

	function isOnlyCardTemplateStorageChange( changedKeys ) {
		return (
			Array.isArray( changedKeys ) &&
			changedKeys.length > 0 &&
			changedKeys.every( function( key ) {
				return -1 !== [ 'active_template_key', 'scoped_templates' ].indexOf( key );
			} )
		);
	}

	function releaseCardTemplateSettingsLock( lockKey ) {
		if ( cardTemplateSettingsLockTimers.has( lockKey ) ) {
			window.clearTimeout( cardTemplateSettingsLockTimers.get( lockKey ) );
		}

		cardTemplateSettingsLockTimers.set(
			lockKey,
			window.setTimeout( function() {
				cardTemplateSettingsLockTimers.delete( lockKey );
				cardTemplateSettingsLocks.delete( lockKey );
			}, 900 )
		);
	}

	function scheduleCardTemplateSettingsLockFlush( container, delay ) {
		if ( CARD_TEMPLATE_WIDGET !== getContainerType( container ) ) {
			return;
		}

		const lockKey = getContainerLockKey( container, 'card-template-settings' );

		if ( cardTemplateSettingsLockFlushTimers.has( lockKey ) ) {
			window.clearTimeout( cardTemplateSettingsLockFlushTimers.get( lockKey ) );
		}

		cardTemplateSettingsLockFlushTimers.set(
			lockKey,
			window.setTimeout( function() {
				cardTemplateSettingsLockFlushTimers.delete( lockKey );

				if ( cardTemplateSettingsLocks.has( lockKey ) ) {
					scheduleCardTemplateSettingsLockFlush( container, 80 );
					return;
				}

				if ( cardTemplateLockedWrapperChanges.has( lockKey ) ) {
					cardTemplateLockedWrapperChanges.delete( lockKey );
					persistCardTemplateActiveScope( container, null, { force: true } );
					applyCardTemplateSettingsToEditor( container );
				}

				syncCardTemplateScope( container );

				if ( cardTemplateSettingsLocks.has( lockKey ) ) {
					scheduleCardTemplateSettingsLockFlush( container, 80 );
					return;
				}

				persistCardTemplateActiveScope( container, null, { force: true } );
				applyCardTemplateSettingsToEditor( container );
				queuePreviewRequest(
					`card:${ container.id || '' }`,
					() => requestCardTemplatePreview( container ),
					0
				);
			}, delay || 80 )
		);
	}

	function hydrateCardTemplateScopeSettings( container, templateSettings ) {
		const settings = getContainerSettings( container );

		if ( ! settings || 'function' !== typeof settings.setExternalChange ) {
			return false;
		}

		const lockKey = getContainerLockKey( container, 'card-template-settings' );
		const nextSettings = parseCardTemplateScopeSettings( templateSettings );
		const scopedKeys = getCardTemplateScopedSettingKeys( settings, nextSettings );
		let changed = false;

		cardTemplateSettingsLocks.add( lockKey );

		try {
			scopedKeys.forEach( function( key ) {
				const nextValue = Object.prototype.hasOwnProperty.call( nextSettings, key )
					? normalizeCardTemplateScopedSettingValue( key, nextSettings[ key ] )
					: getCardTemplateScopedSettingDefaultValue( key );

				if ( ! areCardWrapperValuesEqual( getSettingValue( settings, key, '' ), nextValue ) ) {
					settings.setExternalChange( key, nextValue );
					changed = true;
				}
			} );

			const currentGlobals = getCardTemplateGlobalBindings( settings );
			const nextGlobals = $.extend( {}, currentGlobals && 'object' === typeof currentGlobals ? currentGlobals : {} );
			const templateGlobals = getCardTemplateScopeGlobalBindings( nextSettings );

			scopedKeys.forEach( function( key ) {
				if ( Object.prototype.hasOwnProperty.call( templateGlobals, key ) && templateGlobals[ key ] ) {
					nextGlobals[ key ] = templateGlobals[ key ];
				} else if ( Object.prototype.hasOwnProperty.call( nextGlobals, key ) ) {
					delete nextGlobals[ key ];
				}
			} );

			if ( JSON.stringify( currentGlobals ) !== JSON.stringify( nextGlobals ) ) {
				settings.setExternalChange( '__globals__', nextGlobals );
				changed = true;
			}
		} finally {
			releaseCardTemplateSettingsLock( lockKey );
		}

		return changed;
	}

	function isCardTemplateScopeMissingLiveSettings( settings, templateSettings ) {
		const nextSettings = parseCardTemplateScopeSettings( templateSettings );
		const templateGlobals = getCardTemplateScopeGlobalBindings( nextSettings );
		const currentGlobals = getCardTemplateGlobalBindings( settings );
		const globalKeys = {};

		[ currentGlobals, templateGlobals ].forEach( function( globals ) {
			Object.keys( globals || {} ).forEach( function( key ) {
				if ( isCardTemplateScopedSettingKey( key ) ) {
					globalKeys[ key ] = true;
				}
			} );
		} );

		const hasGlobalMismatch = Object.keys( globalKeys ).some( function( key ) {
			return String( currentGlobals[ key ] || '' ) !== String( templateGlobals[ key ] || '' );
		} );

		if ( hasGlobalMismatch ) {
			return true;
		}

		return getCardTemplateScopedSettingKeys( settings, nextSettings ).some( function( key ) {
			if ( '__globals__' === key || ! isCardTemplateScopedSettingKey( key ) ) {
				return false;
			}

			const targetValue = Object.prototype.hasOwnProperty.call( nextSettings, key )
				? normalizeCardTemplateScopedSettingValue( key, nextSettings[ key ] )
				: getCardTemplateScopedSettingDefaultValue( key );
			const currentValue = getSettingValue( settings, key, getCardTemplateScopedSettingDefaultValue( key ) );

			return ! areCardWrapperValuesEqual( currentValue, targetValue );
		} );
	}

	function sanitizeCardDesignNumber( value ) {
		const number = parseFloat( value );

		return Number.isNaN( number ) ? '0' : String( number );
	}

	function getCardDesignUnit( value, allowedUnits ) {
		const unit = value && 'object' === typeof value
			? String( value.unit || 'px' )
			: 'px';

		return -1 !== allowedUnits.indexOf( unit ) ? unit : '';
	}

	function buildCardDesignDimensions( value ) {
		if ( ! value || 'object' !== typeof value ) {
			return '';
		}

		const unit = getCardDesignUnit( value, [ 'px', 'em', 'rem', '%' ] );

		if ( ! unit ) {
			return '';
		}

		const parts = [];

		for ( const side of [ 'top', 'right', 'bottom', 'left' ] ) {
			const raw = value[ side ];

			if ( '' === raw || undefined === raw || null === raw ) {
				return '';
			}

			parts.push( `${ sanitizeCardDesignNumber( raw ) }${ unit }` );
		}

		return parts.join( ' ' );
	}

	function buildCardDesignSize( value ) {
		if ( ! value || 'object' !== typeof value || '' === String( value.size ?? '' ) ) {
			return '';
		}

		const unit = getCardDesignUnit( value, [ 'px', 'em', 'rem', '%', 'vh' ] );

		return unit ? `${ sanitizeCardDesignNumber( value.size ) }${ unit }` : '';
	}

	function getResponsiveCardDesignValue( design, key, suffix ) {
		const responsiveKey = `${ key }${ suffix || '' }`;

		if (
			suffix &&
			Object.prototype.hasOwnProperty.call( design, responsiveKey ) &&
			undefined !== design[ responsiveKey ] &&
			null !== design[ responsiveKey ] &&
			'' !== design[ responsiveKey ]
		) {
			return design[ responsiveKey ];
		}

		return design[ key ] ?? '';
	}

	function getCardDesignValueFromKeys( design, keys ) {
		for ( const key of keys ) {
			if (
				Object.prototype.hasOwnProperty.call( design, key ) &&
				! isEmptyCardTemplateScopedValue( design[ key ] )
			) {
				return design[ key ];
			}
		}

		return '';
	}

	function getResponsiveCardDesignValueFromKeys( design, keys, suffix ) {
		for ( const key of keys ) {
			const value = getResponsiveCardDesignValue( design, key, suffix );

			if ( ! isEmptyCardTemplateScopedValue( value ) ) {
				return value;
			}
		}

		return '';
	}

	function getCardDesignKeywordForValue( value, allowedValues ) {
		value = String( value || '' ).toLowerCase().replace( /[^a-z0-9_-]/g, '' );

		return -1 !== allowedValues.indexOf( value ) ? value : '';
	}

	function getCardDesignGlobalColor( design, key ) {
		const globals = design && design.__globals__ && 'object' === typeof design.__globals__
			? design.__globals__
			: {};
		const value = String( globals[ key ] || '' );

		if ( 0 !== value.indexOf( 'globals/colors?id=' ) ) {
			return '';
		}

		const id = decodeURIComponent( value.slice( 'globals/colors?id='.length ) ).replace( /[^a-zA-Z0-9_-]/g, '' );

		return id ? `var(--e-global-color-${ id })` : '';
	}

	function getCardDesignColor( design, key ) {
		const globalColor = getCardDesignGlobalColor( design, key );

		if ( globalColor ) {
			return globalColor;
		}

		const value = String( design[ key ] || '' );

		return /^(#[0-9a-fA-F]{3,8}|rgba?\([^)]+\)|hsla?\([^)]+\)|var\([^)]+\))$/.test( value )
			? value
			: '';
	}

	function getCardDesignColorFromKeys( design, keys ) {
		for ( const key of keys ) {
			const value = getCardDesignColor( design, key );

			if ( value ) {
				return value;
			}
		}

		return '';
	}

	function getCardDesignKeyword( design, key, allowedValues ) {
		const value = String( design[ key ] || '' ).toLowerCase().replace( /[^a-z0-9_-]/g, '' );

		return -1 !== allowedValues.indexOf( value ) ? value : '';
	}

	function getCardDesignKeywordFromKeys( design, keys, allowedValues ) {
		return getCardDesignKeywordForValue( getCardDesignValueFromKeys( design, keys ), allowedValues );
	}

	function getCardBackgroundDesignKeys( property ) {
		return [
			`template_container_background_${ property }`,
		];
	}

	function getCardBorderDesignKeys( property ) {
		return [
			`template_container_border_${ property }`,
		];
	}

	function getCardBoxShadowDesignKeys( property ) {
		return [
			`template_container_box_shadow_box_shadow_${ property }`,
		];
	}

	function normalizeCardDesignPosition( value ) {
		const normalized = String( value || '' ).toLowerCase().replace( /[_-]/g, ' ' ).trim();
		const allowed = [
			'center center',
			'center left',
			'center right',
			'top center',
			'top left',
			'top right',
			'bottom center',
			'bottom left',
			'bottom right',
		];

		return -1 !== allowed.indexOf( normalized ) ? normalized : '';
	}

	function buildCardDesignGradientBackground( design, suffix ) {
		const colorA = getCardDesignColorFromKeys( design, getCardBackgroundDesignKeys( 'color' ) );
		const colorB = getCardDesignColorFromKeys( design, getCardBackgroundDesignKeys( 'color_b' ) );

		if ( ! colorA || ! colorB ) {
			return colorA || colorB;
		}

		const stopA = '0';
		const stopB = '100';

		if ( 'radial' === getCardDesignKeywordFromKeys( design, getCardBackgroundDesignKeys( 'gradient_type' ), [ 'linear', 'radial' ] ) ) {
			const position = normalizeCardDesignPosition(
				getCardDesignValueFromKeys( design, getCardBackgroundDesignKeys( 'gradient_position' ) )
			) || 'center center';

			return `radial-gradient(at ${ position }, ${ colorA } ${ stopA }%, ${ colorB } ${ stopB }%)`;
		}

		const angleValue = getCardDesignValueFromKeys( design, getCardBackgroundDesignKeys( 'gradient_angle' ) );
		const angle = sanitizeCardDesignNumber(
			angleValue && 'object' === typeof angleValue
				? angleValue.size
				: ( angleValue || 180 )
		);

		return `linear-gradient(${ angle }deg, ${ colorA } ${ stopA }%, ${ colorB } ${ stopB }%)`;
	}

	function buildCardDesignBackground( design, suffix ) {
		const type = getCardDesignKeywordFromKeys( design, getCardBackgroundDesignKeys( 'background' ), [ 'classic', 'gradient' ] );

		if ( 'gradient' === type ) {
			return buildCardDesignGradientBackground( design, suffix );
		}

		return 'classic' === type ? getCardDesignColorFromKeys( design, getCardBackgroundDesignKeys( 'color' ) ) : '';
	}

	function buildCardDesignBoxShadow( design ) {
		if ( 'yes' !== String( getCardDesignValueFromKeys( design, getCardBoxShadowDesignKeys( 'type' ) ) || '' ) ) {
			return '';
		}

		const color = getCardDesignColorFromKeys( design, getCardBoxShadowDesignKeys( 'color' ) ) || 'rgba(0,0,0,.15)';
		const horizontal = `${ sanitizeCardDesignNumber( getCardDesignValueFromKeys( design, getCardBoxShadowDesignKeys( 'horizontal' ) ) || 0 ) }px`;
		const vertical = `${ sanitizeCardDesignNumber( getCardDesignValueFromKeys( design, getCardBoxShadowDesignKeys( 'vertical' ) ) || 0 ) }px`;
		const blur = `${ sanitizeCardDesignNumber( getCardDesignValueFromKeys( design, getCardBoxShadowDesignKeys( 'blur' ) ) || 0 ) }px`;
		const spread = `${ sanitizeCardDesignNumber( getCardDesignValueFromKeys( design, getCardBoxShadowDesignKeys( 'spread' ) ) || 0 ) }px`;
		const position = 'inset' === String( getCardDesignValueFromKeys( design, getCardBoxShadowDesignKeys( 'position' ) ) || '' ) ? ' inset' : '';

		return `${ horizontal } ${ vertical } ${ blur } ${ spread } ${ color }${ position }`;
	}

	function buildCardTemplateCssVariables( templateSettings ) {
		const design = parseCardTemplateScopeSettings( templateSettings );

		if ( ! Object.keys( design ).length ) {
			return {};
		}

		const variables = {
			'--direl-card-template-border-color': getCardDesignColorFromKeys( design, getCardBorderDesignKeys( 'color' ) ),
			'--direl-card-template-border-style': getCardDesignKeywordFromKeys( design, getCardBorderDesignKeys( 'border' ), [ 'solid', 'double', 'dotted', 'dashed', 'groove' ] ),
			'--direl-card-template-overflow': getCardDesignKeywordFromKeys( design, [ 'template_container_overflow' ], [ 'visible', 'hidden', 'clip' ] ),
			'--direl-card-template-box-shadow': buildCardDesignBoxShadow( design ),
		};

		[
			[ '', '' ],
			[ '-tablet', '_tablet' ],
			[ '-mobile', '_mobile' ],
		].forEach( function( pair ) {
			const variableSuffix = pair[ 0 ];
			const settingSuffix = pair[ 1 ];

			variables[ `--direl-card-template-bg${ variableSuffix }` ] = buildCardDesignBackground( design, settingSuffix );
			variables[ `--direl-card-template-padding${ variableSuffix }` ] = buildCardDesignDimensions(
				getResponsiveCardDesignValueFromKeys( design, [ 'template_container_padding' ], settingSuffix )
			);
			variables[ `--direl-card-template-radius${ variableSuffix }` ] = buildCardDesignDimensions(
				getResponsiveCardDesignValueFromKeys( design, [ 'template_container_border_radius' ], settingSuffix )
			);
			variables[ `--direl-card-template-min-height${ variableSuffix }` ] = buildCardDesignSize(
				getResponsiveCardDesignValueFromKeys( design, [ 'template_container_min_height' ], settingSuffix )
			);
			variables[ `--direl-card-template-border-width${ variableSuffix }` ] = buildCardDesignDimensions(
				getResponsiveCardDesignValueFromKeys( design, getCardBorderDesignKeys( 'width' ), settingSuffix )
			);
		} );

		return variables;
	}

	function applyCardTemplateSettingsToEditor( container ) {
		if ( CARD_TEMPLATE_WIDGET !== getContainerType( container ) ) {
			return;
		}

		const view = getContainerView( container );
		const $surfaces = view && view.$el
			? view.$el
				.find( '.directorist-elementor-card-template__preview-surface .directorist-elementor-loop__item, .directorist-elementor-card-template__preview-surface .directorist-elementor-map-card, .directorist-elementor-card-template__content--editor' )
				.not( '.directorist-elementor-card-template__storage' )
			: $();
		const elements = $surfaces.length ? $surfaces.get() : [];

		if ( ! elements.length ) {
			return;
		}

		const variables = buildCardTemplateCssVariables( extractCardTemplateScopeSettings( getContainerSettings( container ) ) );
		const variableNames = [
			'--direl-card-template-bg',
			'--direl-card-template-bg-tablet',
			'--direl-card-template-bg-mobile',
			'--direl-card-template-border-color',
			'--direl-card-template-border-style',
			'--direl-card-template-overflow',
			'--direl-card-template-box-shadow',
			'--direl-card-template-padding',
			'--direl-card-template-padding-tablet',
			'--direl-card-template-padding-mobile',
			'--direl-card-template-radius',
			'--direl-card-template-radius-tablet',
			'--direl-card-template-radius-mobile',
			'--direl-card-template-min-height',
			'--direl-card-template-min-height-tablet',
			'--direl-card-template-min-height-mobile',
			'--direl-card-template-border-width',
			'--direl-card-template-border-width-tablet',
			'--direl-card-template-border-width-mobile',
		];

		variableNames.forEach( function( name ) {
			const value = variables[ name ] || '';

			elements.forEach( function( element ) {
				if ( value ) {
					element.style.setProperty( name, value );
				} else {
					element.style.removeProperty( name );
				}
			} );
		} );

		const hasVariables = Object.keys( variables ).some( function( name ) {
			return !! variables[ name ];
		} );

		elements.forEach( function( element ) {
			if ( element.classList.contains( 'directorist-elementor-loop__item' ) ) {
				element.classList.toggle( 'directorist-elementor-loop__item--card-styled', hasVariables );
			}

			if ( element.classList.contains( 'directorist-elementor-map-card' ) ) {
				element.classList.toggle( 'directorist-elementor-map-card--card-styled', hasVariables );
			}

			if ( element.classList.contains( 'directorist-elementor-card-template__content' ) ) {
				element.classList.toggle( 'directorist-elementor-card-template__content--card-styled', hasVariables );
			}
		} );
	}

	async function runCardTemplateChildDelete( container, collection, childModel ) {
		const childId = childModel && 'function' === typeof childModel.get
			? childModel.get( 'id' )
			: '';
		const childContainer =
			childId &&
			window.elementor &&
			'function' === typeof window.elementor.getContainer
				? window.elementor.getContainer( childId )
				: null;

		if ( childContainer ) {
			try {
				await Promise.resolve(
					window.$e.run( 'document/elements/delete', {
						container: normalizeContainer( childContainer ),
						force: true,
					} )
				);
				return;
			} catch ( error ) {
				void error;
			}
		}

		if ( collection && 'function' === typeof collection.remove ) {
			collection.remove( childModel );
		}
	}

	async function createCardTemplateChildren( container, elements ) {
		for ( let index = 0; index < elements.length; index++ ) {
			await Promise.resolve(
				window.$e.run( 'document/elements/create', {
					container,
					model: buildElementModelWithIds( elements[ index ] ),
					options: {
						edit: false,
						at: index,
					},
				} )
			);
		}
	}

	function waitForCardTemplateProjection( container, elements, attemptsLeft ) {
		if ( cardTemplateProjectionMatches( container, elements ) || attemptsLeft <= 0 ) {
			return Promise.resolve( cardTemplateProjectionMatches( container, elements ) );
		}

		return new Promise( function( resolve ) {
			window.setTimeout( function() {
				resolve( waitForCardTemplateProjection( container, elements, attemptsLeft - 1 ) );
			}, 40 );
		} );
	}

	async function replaceCardTemplateChildren( container, elements ) {
		if (
			! container ||
			! window.$e ||
			'function' !== typeof window.$e.run
		) {
			return false;
		}

		const collection = container.model && 'function' === typeof container.model.get
			? container.model.get( 'elements' )
			: null;
		const childModels = getCardTemplateRawChildModels( container ).slice().reverse();
		const nextElements = Array.isArray( elements ) ? elements : [];

		for ( const childModel of childModels ) {
			await runCardTemplateChildDelete( container, collection, childModel );
		}

		await createCardTemplateChildren( container, nextElements );

		if ( await waitForCardTemplateProjection( container, nextElements, 20 ) ) {
			return true;
		}

		// A model may not have a hydrated Elementor container during the first
		// editor render. Reset only as a last resort, then rebuild the projection.
		if ( collection && 'function' === typeof collection.reset ) {
			collection.reset( [] );
			await createCardTemplateChildren( container, nextElements );
		}

		return waitForCardTemplateProjection( container, nextElements, 20 );
	}

	async function syncCardTemplateScope( container ) {
		if (
			CARD_TEMPLATE_WIDGET !== getContainerType( container ) ||
			! window.$e ||
			'function' !== typeof window.$e.run
		) {
			return false;
		}

		const settings = getContainerSettings( container );
		const desired = getCardTemplateScopeState( container );
		const currentKey = String( getSettingValue( settings, 'active_template_key', '' ) || '' );
		const lockKey = getContainerLockKey( container, 'card-template-scope' );
		const settingsLockKey = getContainerLockKey( container, 'card-template-settings' );

		if ( ! desired.key || branchSyncLocks.has( lockKey ) ) {
			return false;
		}

		if ( cardTemplateSettingsLocks.has( settingsLockKey ) ) {
			scheduleCardTemplateSettingsLockFlush( container, 80 );
			return false;
		}

		branchSyncLocks.add( lockKey );
		const projectionState = getCardTemplateProjectionState( container );
		const transition = projectionState.transition + 1;
		projectionState.transition = transition;

		try {
			const scopes = getCardTemplateScopes( container );
			const hasDesiredScope = Object.prototype.hasOwnProperty.call( scopes, desired.key );

			if ( ! currentKey || currentKey === desired.key ) {
				if ( ! hasDesiredScope ) {
					const projection = serializeCardTemplateChildren( container, desired, scopes );
					const initialScope = normalizeCardTemplateScopeForState( {
						settings: extractCardTemplateScopeSettings( settings ),
						elements: projection.valid ? projection.elements : [],
					}, desired );

					scopes[ desired.key ] = initialScope;
					setCardTemplateScopes( container, scopes );
				}

				const initialScope = normalizeCardTemplateScopeForState( scopes[ desired.key ], {
					directoryTypeId: desired.directoryTypeId,
					viewType: desired.viewType,
					label: desired.label,
				} );
				const currentProjection = serializeCardTemplateChildren( container, desired, scopes );
				const projectionInitializedForScope =
					projectionState.initialized &&
					projectionState.key === desired.key;
				const mustRestoreProjection =
					! currentProjection.valid ||
					(
						! projectionInitializedForScope &&
						! cardTemplateProjectionMatches( container, initialScope.elements || [] )
					);

				setSettingValue( container, 'active_template_key', desired.key );

				if (
					mustRestoreProjection ||
					isCardTemplateScopeMissingLiveSettings( settings, initialScope.settings || {} )
				) {
					hydrateCardTemplateScopeSettings( container, initialScope.settings || {} );
					applyCardTemplateSettingsToEditor( container );
				}

				if ( mustRestoreProjection ) {
					projectionState.initialized = false;

					if ( ! await replaceCardTemplateChildren( container, initialScope.elements || [] ) ) {
						return false;
					}
				}

				projectionState.initialized = true;
				projectionState.key = desired.key;
				return true;
			}

			const panelState = captureCardTemplatePanelState( container );
			const previousScope = normalizeCardTemplateScope( scopes[ currentKey ], desired );
			const previousState = {
				key: currentKey,
				directoryTypeId: previousScope.directory_type_id || desired.directoryTypeId,
				viewType: previousScope.view_type || desired.viewType,
				label: previousScope.label || desired.label,
			};

			persistCardTemplateActiveScope( container, previousState );

			const targetScope = normalizeCardTemplateScopeForState( getCardTemplateScopes( container )[ desired.key ], {
				directoryTypeId: desired.directoryTypeId,
				viewType: desired.viewType,
				label: desired.label,
			} );
			const latestScopes = getCardTemplateScopes( container );

			if ( JSON.stringify( latestScopes[ desired.key ] || {} ) !== JSON.stringify( targetScope ) ) {
				latestScopes[ desired.key ] = targetScope;
				setCardTemplateScopes( container, latestScopes );
			}

			setSettingValue( container, 'active_template_key', desired.key );
			hydrateCardTemplateScopeSettings( container, targetScope.settings || {} );
			prepareCardTemplatePanelForStructureSwap( container, panelState );
			projectionState.initialized = false;

			if ( ! await replaceCardTemplateChildren( container, targetScope.elements || [] ) ) {
				return false;
			}

			projectionState.initialized = true;
			projectionState.key = desired.key;
			scheduleCardTemplatePanelStateRestore( container, panelState );
			return true;
		} catch ( error ) {
			void error;
			return false;
		} finally {
			if ( projectionState.transition === transition ) {
				branchSyncLocks.delete( lockKey );
			}

			const latestDesired = getCardTemplateScopeState( container );
			const latestActiveKey = String( getSettingValue( getContainerSettings( container ), 'active_template_key', '' ) || '' );

			if (
				latestDesired.key &&
				(
					latestDesired.key !== latestActiveKey ||
					! projectionState.initialized ||
					projectionState.key !== latestDesired.key
				)
			) {
				window.setTimeout( function() {
					syncCardTemplateScope( container );
				}, 80 );
			}
		}
	}

	function getCardTemplateNavigatorTitle( title ) {
		const config = getEditorConfig();

		if ( ! title ) {
			return config.strings.cardTemplateTitle || 'Listing Card Template';
		}

		return `${ config.strings.cardTemplateTitle || 'Listing Card Template' } · ${ title }`;
	}

	function getLoopEditingState( container ) {
		const loopContainer = isLoopWidget( getContainerType( container ) )
			? container
			: getClosestAncestorWidget( container ? container.parent : null, LOOP_WIDGETS );
		const loopSettings = getContainerSettings( loopContainer );

		if ( ! loopContainer || ! loopSettings ) {
			return null;
		}

		return {
			container: loopContainer,
			activeView: resolveLoopPreviewView( loopSettings ),
			activeDirectoryId: resolveLoopActiveDirectoryId( loopSettings, loopContainer ),
		};
	}

	function normalizeLoopScopeSettings( container ) {
		if ( ! isLoopWidget( getContainerType( container ) ) ) {
			return;
		}

		const settings = getContainerSettings( container );
		const loopTabs = getLoopDirectoryTabs( settings, container );
		const activeDirectoryId = resolveLoopActiveDirectoryId( settings, container );
		const defaultDirectoryId = getLoopDefaultDirectoryId( settings, container );
		const storedActiveDirectoryId = toInt( getSettingValue( settings, 'active_directory_type_id', 0 ) );
		const defaultDirectoryVisible = loopTabs.some( ( tab ) => tab.id === defaultDirectoryId );
		const activeView = normalizeLoopView( getSettingValue( settings, 'active_view_type', '' ) );
		const frontendView = resolveLoopFrontendView( settings );
		const previewMode = String( getSettingValue( settings, 'editor_preview_mode', '' ) || '' );
		const lockKey = getLoopEditorStateKey( container );

		loopScopeNormalizationLocks.add( lockKey );

		try {
				if ( ! loopTabs.length ) {
					if ( 0 !== storedActiveDirectoryId ) {
						setSettingValue( container, 'active_directory_type_id', '0' );
					}

					if ( 0 !== defaultDirectoryId ) {
						setSettingValue( container, 'default_directory_type_id', '0' );
					}
				} else if ( activeDirectoryId > 0 && activeDirectoryId !== storedActiveDirectoryId ) {
					setSettingValue( container, 'active_directory_type_id', String( activeDirectoryId ) );
				}

				if ( loopTabs.length && defaultDirectoryId > 0 && ! defaultDirectoryVisible ) {
					setSettingValue( container, 'default_directory_type_id', '0' );
				}

			if ( ! activeView ) {
				setSettingValue( container, 'active_view_type', frontendView );
			}

			if ( 'results' !== previewMode ) {
				setSettingValue( container, 'editor_preview_mode', 'results' );
			}
		} finally {
			loopScopeNormalizationLocks.delete( lockKey );
		}
	}

	function syncLoopDefaultDirectoryControl( container ) {
		if ( ! isLoopWidget( getContainerType( container ) ) || ! container || ! container.model ) {
			return;
		}

		const selectedContainer = getSelectedEditorContainer();

		if (
			! selectedContainer ||
			! selectedContainer.model ||
			selectedContainer.model.get( 'id' ) !== container.model.get( 'id' )
		) {
			return;
		}

		const pageView = getCurrentPanelPageView();

		if (
			! pageView ||
			! pageView.collection ||
			'function' !== typeof pageView.collection.findWhere
		) {
			return;
		}

		const controlModel = pageView.collection.findWhere( {
			name: 'default_directory_type_id',
		} );

		if ( ! controlModel ) {
			return;
		}

		const options = getLoopDefaultDirectoryOptions( getContainerSettings( container ), container );

		let didUpdateOptions = false;

		if ( JSON.stringify( controlModel.get( 'options' ) || {} ) !== JSON.stringify( options ) ) {
			controlModel.set( 'options', options );
			didUpdateOptions = true;
		}

		const controlView =
			pageView.children && 'function' === typeof pageView.children.findByModelCid
				? pageView.children.findByModelCid( controlModel.cid )
				: null;

		if ( didUpdateOptions && controlView && 'function' === typeof controlView.render ) {
			controlView.render();
		}
	}

	function updateModelEditorSettings( model, patch ) {
		if (
			! model ||
			typeof model.get !== 'function' ||
			typeof model.set !== 'function'
		) {
			return;
		}

		const currentSettings = model.get( 'editor_settings' ) || {};
		const nextSettings = $.extend( {}, currentSettings, patch );
		const hasChanges = Object.keys( patch ).some(
			( key ) => currentSettings[ key ] !== nextSettings[ key ]
		);

		if ( ! hasChanges ) {
			return;
		}

		model.set( 'editor_settings', nextSettings );
	}

	function trackCardTemplateContainer( container ) {
		if ( CARD_TEMPLATE_WIDGET !== getContainerType( container ) || ! container ) {
			return;
		}

		const containerId = container.id || ( container.model ? container.model.get( 'id' ) : '' );

		if ( ! containerId ) {
			return;
		}

		trackedCardTemplateContainers.set( containerId, container );
	}

	function trackRelatedListingsContainer( container ) {
		if ( RELATED_LISTINGS_WIDGET !== getContainerType( container ) || ! container ) {
			return;
		}

		const containerId = container.id || ( container.model ? container.model.get( 'id' ) : '' );

		if ( ! containerId ) {
			return;
		}

		trackedRelatedListingsContainers.set( containerId, container );
	}

	function trackSingleMapContainer( container ) {
		if ( SINGLE_MAP_WIDGET !== getContainerType( container ) || ! container ) {
			return;
		}

		const containerId = container.id || ( container.model ? container.model.get( 'id' ) : '' );

		if ( ! containerId ) {
			return;
		}

		trackedSingleMapContainers.set( containerId, container );
	}

	function trackTaxonomyCompositionContainer( container ) {
		if ( ! isTaxonomyCompositionWidget( getContainerType( container ) ) || ! container ) {
			return;
		}

		const containerId = container.id || ( container.model ? container.model.get( 'id' ) : '' );

		if ( ! containerId ) {
			return;
		}

		trackedTaxonomyCompositionContainers.set( containerId, container );
	}

	function getNavigatorElement( modelId ) {
		if ( ! modelId ) {
			return $();
		}

		return $( `#elementor-navigator .elementor-navigator__element[data-id="${ modelId }"]` ).first();
	}

	function updateNavigatorElementTitle( $element, title ) {
		if ( ! $element || ! $element.length || ! title ) {
			return;
		}

		const $title = $element
			.find(
				[
					'.elementor-navigator__element__title__text',
					'.elementor-navigator__element__title span',
					'.elementor-navigator__item__title',
				].join( ',' )
			)
			.first();

		if ( $title.length ) {
			$title.text( title );
		}

		$element
			.attr( 'aria-label', title )
			.attr( 'title', title );
	}

	function syncCardTemplateNavigatorProjection( container, attempt ) {
		if ( CARD_TEMPLATE_WIDGET !== getContainerType( container ) ) {
			return;
		}

		const scopeState = getCardTemplateScopeState( container );
		const containerModelId =
			container && container.model && 'function' === typeof container.model.get
				? container.model.get( 'id' )
				: '';
		const $cardNavigatorElement = getNavigatorElement( containerModelId );
		const title = getCardTemplateNavigatorTitle( scopeState.label );

		if ( $cardNavigatorElement.length ) {
			$cardNavigatorElement
				.addClass( 'directorist-elementor-navigator-card-template' )
				.attr( 'data-direl-template', scopeState.key );
			updateNavigatorElementTitle( $cardNavigatorElement, title );
		}
	}

	function queueTrackedCardTemplateNavigatorSync() {
		if ( navigatorSyncQueued ) {
			return;
		}

		navigatorSyncQueued = true;

		window.requestAnimationFrame( function() {
			navigatorSyncQueued = false;

			trackedCardTemplateContainers.forEach( function( container, key ) {
				if ( ! container || ! container.model ) {
					trackedCardTemplateContainers.delete( key );
					return;
				}

				syncCardTemplateNavigatorProjection( container, 0 );
			} );
		} );
	}

	function syncRelatedListingsNavigatorProjection( container, attempt ) {
		if ( RELATED_LISTINGS_WIDGET !== getContainerType( container ) ) {
			return;
		}

		const containerModelId =
			container && container.model && 'function' === typeof container.model.get
				? container.model.get( 'id' )
				: '';
		const $widgetNavigatorElement = getNavigatorElement( containerModelId );
		const templateContainer = getRelatedListingsTemplateContainer( container );
		const templateModelId =
			templateContainer && templateContainer.model && 'function' === typeof templateContainer.model.get
				? templateContainer.model.get( 'id' )
				: '';
		const $templateNavigatorElement = getNavigatorElement( templateModelId );

		if ( $widgetNavigatorElement.length ) {
			$widgetNavigatorElement.addClass( 'directorist-elementor-navigator-related-listings' );
		}

		if ( $templateNavigatorElement.length ) {
			$templateNavigatorElement
				.removeClass( 'directorist-elementor-panel-hidden' )
				.addClass( 'directorist-elementor-navigator-bridge' )
				.attr( 'aria-hidden', 'false' );
			return;
		}

		if ( ( attempt || 0 ) < 4 ) {
			window.requestAnimationFrame( function() {
				syncRelatedListingsNavigatorProjection( container, ( attempt || 0 ) + 1 );
			} );
		}
	}

	function syncTaxonomyCompositionNavigatorProjection( container, attempt ) {
		if ( ! isTaxonomyCompositionWidget( getContainerType( container ) ) ) {
			return;
		}

		const containerModelId =
			container && container.model && 'function' === typeof container.model.get
				? container.model.get( 'id' )
				: '';
		const $widgetNavigatorElement = getNavigatorElement( containerModelId );
		const templateContainer = getTaxonomyCompositionTemplateContainer( container );
		const templateModelId =
			templateContainer && templateContainer.model && 'function' === typeof templateContainer.model.get
				? templateContainer.model.get( 'id' )
				: '';
		const $templateNavigatorElement = getNavigatorElement( templateModelId );

		if ( $widgetNavigatorElement.length ) {
			$widgetNavigatorElement.addClass( 'directorist-elementor-navigator-taxonomy-composition' );
		}

		if ( $templateNavigatorElement.length ) {
			$templateNavigatorElement
				.removeClass( 'directorist-elementor-panel-hidden' )
				.addClass( 'directorist-elementor-navigator-bridge' )
				.attr( 'aria-hidden', 'false' );
			return;
		}

		if ( ( attempt || 0 ) < 4 ) {
			window.requestAnimationFrame( function() {
				syncTaxonomyCompositionNavigatorProjection( container, ( attempt || 0 ) + 1 );
			} );
		}
	}

	function syncSingleMapNavigatorProjection( container, attempt ) {
		if ( SINGLE_MAP_WIDGET !== getContainerType( container ) ) {
			return;
		}

		const containerModelId =
			container && container.model && 'function' === typeof container.model.get
				? container.model.get( 'id' )
				: '';
		const $widgetNavigatorElement = getNavigatorElement( containerModelId );
		const templateContainer = getSingleMapTemplateContainer( container );
		const templateModelId =
			templateContainer && templateContainer.model && 'function' === typeof templateContainer.model.get
				? templateContainer.model.get( 'id' )
				: '';
		const $templateNavigatorElement = getNavigatorElement( templateModelId );

		if ( $widgetNavigatorElement.length ) {
			$widgetNavigatorElement.addClass( 'directorist-elementor-navigator-single-map' );
		}

		if ( $templateNavigatorElement.length ) {
			$templateNavigatorElement
				.removeClass( 'directorist-elementor-panel-hidden' )
				.addClass( 'directorist-elementor-navigator-bridge' )
				.attr( 'aria-hidden', 'false' );
			return;
		}

		if ( ( attempt || 0 ) < 4 ) {
			window.requestAnimationFrame( function() {
				syncSingleMapNavigatorProjection( container, ( attempt || 0 ) + 1 );
			} );
		}
	}

	function queueTrackedRelatedListingsNavigatorSync() {
		if ( navigatorSyncQueued ) {
			return;
		}

		navigatorSyncQueued = true;

		window.requestAnimationFrame( function() {
			navigatorSyncQueued = false;

			trackedCardTemplateContainers.forEach( function( container, key ) {
				if ( ! container || ! container.model ) {
					trackedCardTemplateContainers.delete( key );
					return;
				}

				syncCardTemplateNavigatorProjection( container, 0 );
			} );

			trackedRelatedListingsContainers.forEach( function( container, key ) {
				if ( ! container || ! container.model ) {
					trackedRelatedListingsContainers.delete( key );
					return;
				}

				syncRelatedListingsNavigatorProjection( container, 0 );
			} );

			trackedSingleMapContainers.forEach( function( container, key ) {
				if ( ! container || ! container.model ) {
					trackedSingleMapContainers.delete( key );
					return;
				}

				syncSingleMapNavigatorProjection( container, 0 );
			} );

			trackedTaxonomyCompositionContainers.forEach( function( container, key ) {
				if ( ! container || ! container.model ) {
					trackedTaxonomyCompositionContainers.delete( key );
					return;
				}

				syncTaxonomyCompositionNavigatorProjection( container, 0 );
			} );
		} );
	}

	function initNavigatorObserver() {
		const navigatorRoot = document.getElementById( 'elementor-navigator' );

		if ( navigatorObserver || ! navigatorRoot || 'undefined' === typeof MutationObserver ) {
			return !! navigatorRoot;
		}

		navigatorObserver = new MutationObserver( function() {
			queueTrackedRelatedListingsNavigatorSync();
		} );
		navigatorObserver.observe( navigatorRoot, {
			childList: true,
			subtree: true,
		} );

		return true;
	}

	function hasChildElements( container ) {
		if ( ! container || ! container.model || 'function' !== typeof container.model.get ) {
			return false;
		}

		const childElements = container.model.get( 'elements' );

		return !! ( childElements && childElements.length );
	}

	function isLegacyAuthorProfileFieldsWrapper( container ) {
		if ( 'container' !== getContainerType( container ) ) {
			return false;
		}

		const settings = getContainerSettings( container );

		return 'Author Profile Fields' === String( getSettingValue( settings, '_title', '' ) || '' );
	}

	function unwrapLegacyAuthorProfileFieldsWrapper( container ) {
		if (
			AUTHOR_PROFILE_WIDGET !== getContainerType( container ) ||
			! window.$e ||
			'function' !== typeof window.$e.run
		) {
			return false;
		}

		const lockKey = `author-profile-unwrap:${ container.id || ( container.model ? container.model.cid : '' ) }`;

		if ( branchSyncLocks.has( lockKey ) ) {
			return false;
		}

		const children = getChildContainers( container );

		if ( 1 !== children.length || ! isLegacyAuthorProfileFieldsWrapper( children[ 0 ] ) ) {
			return false;
		}

		branchSyncLocks.add( lockKey );

		const wrapper = children[ 0 ];
		const wrapperChildren = getChildContainers( wrapper );

		try {
			if ( wrapperChildren.length ) {
				wrapperChildren.forEach( function( childContainer, index ) {
					window.$e.run( 'document/elements/move', {
						container: childContainer,
						target: container,
						options: {
							at: index,
							edit: false,
						},
					} );
				} );
			}

			window.setTimeout( function() {
				window.$e.run( 'document/elements/delete', {
					container: wrapper,
					force: true,
				} );

				branchSyncLocks.delete( lockKey );
				refreshAuthorProfileEditorState( container );
			}, 0 );
		} catch ( error ) {
			branchSyncLocks.delete( lockKey );
			return false;
		}

		return true;
	}

	function seedPricingPlanDefaultsWhenReady( container, planIndex, attemptsLeft ) {
		if (
			PRICING_PLANS_WIDGET !== getContainerType( container ) ||
			! window.$e ||
			'function' !== typeof window.$e.run
		) {
			return;
		}

		const remainingAttempts = Number.isInteger( attemptsLeft ) ? attemptsLeft : 8;
		const planContainer = getChildContainerAtIndex( container, planIndex );

		if ( ! planContainer || ! planContainer.model ) {
			if ( remainingAttempts > 0 ) {
				window.setTimeout( function() {
					seedPricingPlanDefaultsWhenReady( container, planIndex, remainingAttempts - 1 );
				}, 40 );
			}

			return;
		}

		if ( hasChildElements( planContainer ) ) {
			return;
		}

		const defaultElements = getPricingPlanDefaultElements();

		if ( ! defaultElements.length ) {
			return;
		}

		defaultElements.forEach( function( element, index ) {
			window.$e.run( 'document/elements/create', {
				container: planContainer,
				model: element,
				options: {
					edit: false,
					at: index,
				},
			} );
		} );

		syncPricingPlanChildPreviewSettingsForIndex( container, planIndex );
	}

	function getPricingPlanContainerPlanId( planContainer ) {
		let planId = '0';

		if ( ! planContainer ) {
			return planId;
		}

		walkDescendantContainers( planContainer, function( childContainer ) {
			if ( '0' !== planId || ! isPricingPlanFieldWidget( getContainerType( childContainer ) ) ) {
				return;
			}

			const childSettings = getContainerSettings( childContainer );
			planId = String( getSettingValue( childSettings, 'preview_plan_id', '0' ) || '0' );
		} );

		return planId;
	}

	function buildElementModelWithIds( element ) {
		const nextElement = $.extend( true, {}, element || {} );

		if ( ! nextElement.id && window.elementorCommon && window.elementorCommon.helpers ) {
			nextElement.id = window.elementorCommon.helpers.getUniqueId();
		}

		nextElement.elements = Array.isArray( nextElement.elements )
			? nextElement.elements.map( buildElementModelWithIds )
			: [];

		return nextElement;
	}

	function buildPricingPlanContainerModel( plan, index ) {
		const planId = String( plan && plan.plan_id ? plan.plan_id : '0' );
		const title = getPricingPlanLabel( plan, Number.isInteger( index ) ? index : 0 );
		const elements = getPricingPlanDefaultElements().map( function( element ) {
			const nextElement = buildElementModelWithIds( element );

			nextElement.settings = $.extend( true, {}, nextElement.settings || {}, {
				preview_plan_id: planId,
			} );

			return nextElement;
		} );

		return {
			elType: 'container',
			isInner: true,
			settings: {
				_title: title,
			},
			elements,
		};
	}

	function getDesiredPricingPlans( plans ) {
		const seen = {};

		return plans.filter( function( plan ) {
			const planId = String( plan && plan.plan_id ? plan.plan_id : '0' );

			if ( '0' === planId || seen[ planId ] ) {
				return false;
			}

			seen[ planId ] = true;

			return true;
		} );
	}

	function getPricingPlanLabel( plan, index ) {
		const fallback = `Pricing Plan ${ index + 1 }`;
		const label = String( plan && plan.label ? plan.label : '' ).trim();

		return label || fallback;
	}

	function updatePricingPlanContainerForPlan( planContainer, plan, index ) {
		if ( ! planContainer ) {
			return;
		}

		const title = getPricingPlanLabel( plan, index );
		const settings = getContainerSettings( planContainer );

		if ( settings && String( getSettingValue( settings, '_title', '' ) || '' ) === title ) {
			return;
		}

		if (
			window.$e &&
			'function' === typeof window.$e.run
		) {
			window.$e.run( 'document/elements/settings', {
				container: planContainer,
				settings: {
					_title: title,
				},
			} );
		}
	}

	function movePricingPlanContainer( parentContainer, childContainer, targetIndex ) {
		if (
			! parentContainer ||
			! childContainer ||
			! window.$e ||
			'function' !== typeof window.$e.run
		) {
			return null;
		}

		return window.$e.run( 'document/elements/move', {
			container: childContainer,
			target: parentContainer,
			options: {
				at: targetIndex,
				edit: false,
			},
		} );
	}

	function deletePricingPlanContainer( childContainer ) {
		if (
			! childContainer ||
			! window.$e ||
			'function' !== typeof window.$e.run
		) {
			return;
		}

		window.$e.run( 'document/elements/delete', {
			container: childContainer,
			force: true,
		} );
	}

	function syncPricingPlanContainers( container ) {
		if (
			PRICING_PLANS_WIDGET !== getContainerType( container ) ||
			! window.$e ||
			'function' !== typeof window.$e.run
		) {
			return;
		}

		const settings = getContainerSettings( container );
		const plans = getDesiredPricingPlans( normalizeRepeaterItems( getSettingValue( settings, 'plans', [] ) ) );
		const lockKey = `pricing-plans:${ container.id || ( container.model ? container.model.cid : '' ) }`;

		if ( ! lockKey || branchSyncLocks.has( lockKey ) ) {
			return;
		}

		branchSyncLocks.add( lockKey );

		try {
			plans.forEach( function( plan, index ) {
				const planId = String( plan && plan.plan_id ? plan.plan_id : '0' );
				const childContainers = getChildContainers( container );
				let planContainer = childContainers[ index ] || null;

				if ( planContainer && planId === getPricingPlanContainerPlanId( planContainer ) ) {
					updatePricingPlanContainerForPlan( planContainer, plan, index );
					syncPricingPlanChildPreviewSettingsForIndex( container, index );
					return;
				}

				const reusableIndex = childContainers.findIndex( function( childContainer, childIndex ) {
					return childIndex > index && planId === getPricingPlanContainerPlanId( childContainer );
				} );

				if ( -1 !== reusableIndex ) {
					planContainer = movePricingPlanContainer( container, childContainers[ reusableIndex ], index ) || getChildContainerAtIndex( container, index );
					updatePricingPlanContainerForPlan( planContainer, plan, index );
					syncPricingPlanChildPreviewSettingsForIndex( container, index );
					return;
				}

				const remainingPlanIds = plans.slice( index + 1 ).map( function( remainingPlan ) {
					return String( remainingPlan && remainingPlan.plan_id ? remainingPlan.plan_id : '0' );
				} );
				const currentPlanId = planContainer ? getPricingPlanContainerPlanId( planContainer ) : '0';

				if ( planContainer && -1 === remainingPlanIds.indexOf( currentPlanId ) ) {
					updatePricingPlanContainerForPlan( planContainer, plan, index );
					syncPricingPlanChildPreviewSettingsForIndex( container, index );
					return;
				}

				window.$e.run( 'document/elements/create', {
					container: container,
					model: buildPricingPlanContainerModel( plan, index ),
					options: {
						edit: false,
						at: index,
					},
				} );

				seedPricingPlanDefaultsWhenReady( container, index );
			} );

			const currentChildren = getChildContainers( container );

			for ( let index = currentChildren.length - 1; index >= plans.length; index-- ) {
				deletePricingPlanContainer( currentChildren[ index ] );
			}
		} finally {
			branchSyncLocks.delete( lockKey );
		}
	}

	function isPricingPlanFieldWidget( widgetType ) {
		return 'string' === typeof widgetType && 0 === widgetType.indexOf( 'directorist_pricing_plan_' );
	}

	function syncPricingPlanChildPreviewSettingsForIndex( container, planIndex, attemptsLeft ) {
		if ( PRICING_PLANS_WIDGET !== getContainerType( container ) ) {
			return;
		}

		const settings = getContainerSettings( container );
		const plans = getDesiredPricingPlans( normalizeRepeaterItems( getSettingValue( settings, 'plans', [] ) ) );
		const plan = plans[ planIndex ] || null;
		const planId = plan ? String( plan.plan_id || '0' ) : '0';

		if ( '0' === planId ) {
			return;
		}

		const remainingAttempts = Number.isInteger( attemptsLeft ) ? attemptsLeft : 8;
		const planContainer = getChildContainerAtIndex( container, planIndex );

		if ( ! planContainer ) {
			if ( remainingAttempts > 0 ) {
				window.setTimeout( function() {
					syncPricingPlanChildPreviewSettingsForIndex( container, planIndex, remainingAttempts - 1 );
				}, 40 );
			}

			return;
		}

		let matchedField = false;

		walkDescendantContainers( planContainer, function( childContainer ) {
			const widgetType = getContainerType( childContainer );

			if ( ! isPricingPlanFieldWidget( widgetType ) ) {
				return;
			}

			const childSettings = getContainerSettings( childContainer );

			if ( ! childSettings ) {
				return;
			}

			matchedField = true;

			if ( String( getSettingValue( childSettings, 'preview_plan_id', '' ) || '' ) !== planId ) {
				if (
					window.$e &&
					'function' === typeof window.$e.run
				) {
					window.$e.run( 'document/elements/settings', {
						container: childContainer,
						settings: {
							preview_plan_id: planId,
						},
					} );
					return;
				}

				if ( 'function' !== typeof childSettings.setExternalChange ) {
					return;
				}

				childSettings.setExternalChange( 'preview_plan_id', planId );

				const childView = getContainerView( childContainer );

				if ( childView && 'function' === typeof childView.render ) {
					window.requestAnimationFrame( function() {
						childView.render();
					} );
				}
			}
		} );

		if ( ! matchedField && remainingAttempts > 0 ) {
			window.setTimeout( function() {
				syncPricingPlanChildPreviewSettingsForIndex( container, planIndex, remainingAttempts - 1 );
			}, 40 );
		}
	}

	function syncPricingPlanChildPreviewSettings( container ) {
		if ( PRICING_PLANS_WIDGET !== getContainerType( container ) ) {
			return;
		}

		const settings = getContainerSettings( container );
		const plans = getDesiredPricingPlans( normalizeRepeaterItems( getSettingValue( settings, 'plans', [] ) ) );

		plans.forEach( function( _plan, index ) {
			syncPricingPlanChildPreviewSettingsForIndex( container, index );
		} );
	}

	function seedPricingPlanDefaultsForContainer( container ) {
		if ( PRICING_PLANS_WIDGET !== getContainerType( container ) ) {
			return;
		}

		syncPricingPlanContainers( container );

		const settings = getContainerSettings( container );
		const plans = getDesiredPricingPlans( normalizeRepeaterItems( getSettingValue( settings, 'plans', [] ) ) );

		plans.forEach( function( _plan, index ) {
			seedPricingPlanDefaultsWhenReady( container, index );
		} );

		syncPricingPlanChildPreviewSettings( container );
	}

	function seedAuthorProfileDefaultsWhenReady( container, attemptsLeft ) {
		if (
			AUTHOR_PROFILE_WIDGET !== getContainerType( container ) ||
			! window.$e ||
			'function' !== typeof window.$e.run
		) {
			return;
		}

		const remainingAttempts = Number.isInteger( attemptsLeft ) ? attemptsLeft : 8;

		if ( ! container.model ) {
			if ( remainingAttempts > 0 ) {
				window.setTimeout( function() {
					seedAuthorProfileDefaultsWhenReady( container, remainingAttempts - 1 );
				}, 40 );
			}

			return;
		}

		if ( unwrapLegacyAuthorProfileFieldsWrapper( container ) ) {
			return;
		}

		if ( hasChildElements( container ) ) {
			return;
		}

		const defaultElements = getAuthorProfileDefaultElements();

		if ( ! defaultElements.length ) {
			return;
		}

		defaultElements.forEach( function( element, index ) {
			window.$e.run( 'document/elements/create', {
				container: container,
				model: element,
				options: {
					edit: false,
					at: index,
				},
			} );
		} );
	}

	function seedAuthorProfileDefaultsForContainer( container ) {
		if (
			AUTHOR_PROFILE_WIDGET !== getContainerType( container ) ||
			! window.$e ||
			'function' !== typeof window.$e.run
		) {
			return;
		}

		if ( unwrapLegacyAuthorProfileFieldsWrapper( container ) ) {
			return;
		}

		seedAuthorProfileDefaultsWhenReady( container );
	}

	function refreshAuthorProfileEditorState( container ) {
		if ( AUTHOR_PROFILE_WIDGET !== getContainerType( container ) ) {
			return;
		}

		seedAuthorProfileDefaultsForContainer( container );

		queuePreviewRequest(
			`author-profile:${ container.id || '' }`,
			() => requestAuthorProfilePreview( container ),
			120
		);
	}

	function syncPricingPlanEditorLayout( container ) {
		if ( PRICING_PLANS_WIDGET !== getContainerType( container ) ) {
			return;
		}

		const view = getContainerView( container );
		const root = view && view.$el
			? view.$el.find( '.directorist-elementor-pricing-plans' ).get( 0 )
			: null;
		const grid = root ? root.querySelector( '.directorist-elementor-pricing-plans__storage' ) : null;

		if ( ! grid ) {
			return;
		}

		const directChildren = Array.prototype.slice.call( grid.children || [] );
		const cards = directChildren.filter( function( element ) {
			return element.classList && element.classList.contains( 'directorist-elementor-pricing-plans__card' );
		} );
		const canvases = directChildren.filter( function( element ) {
			return element.classList && element.classList.contains( 'directorist-elementor-pricing-plans__card-canvas' );
		} );

		canvases.forEach( function( canvas, index ) {
			const card = cards[ index ] || null;
			const target = card ? card.querySelector( '.directorist-elementor-pricing-plans__card-inner' ) : null;

			if ( target && canvas.parentNode !== target ) {
				target.appendChild( canvas );
			}
		} );
	}

	function refreshPricingPlanEditorState( container ) {
		seedPricingPlanDefaultsForContainer( container );
		syncPricingPlanChildPreviewSettings( container );

		window.requestAnimationFrame( function() {
			syncPricingPlanEditorLayout( container );
		} );

		queuePreviewRequest(
			`pricing:${ container.id || '' }`,
			() => requestPricingPlansPreview( container ),
			120
		);
	}

	function getFirstChildContainer( container ) {
		if (
			! container ||
			! container.model ||
			typeof container.model.get !== 'function'
		) {
			return null;
		}

		const childElements = container.model.get( 'elements' );

		if ( ! childElements || ! childElements.length ) {
			return null;
		}

		return getChildContainerAtIndex( container, 0 );
	}

	function getRelatedListingsTemplateContainer( container ) {
		if ( RELATED_LISTINGS_WIDGET !== getContainerType( container ) ) {
			return null;
		}

		return getChildContainerAtIndex( container, 0 );
	}

	function getTaxonomyCompositionTemplateContainer( container ) {
		if ( ! isTaxonomyCompositionWidget( getContainerType( container ) ) ) {
			return null;
		}

		return getChildContainerAtIndex( container, 0 );
	}

	function getSingleMapTemplateContainer( container ) {
		if ( SINGLE_MAP_WIDGET !== getContainerType( container ) ) {
			return null;
		}

		return getChildContainerAtIndex( container, 0 );
	}

	function getAuthorProfileTemplateContainer( container ) {
		if ( AUTHOR_PROFILE_WIDGET !== getContainerType( container ) ) {
			return null;
		}

		return container;
	}

	function resolveCompositionTarget( container ) {
		if ( ! container ) {
			return null;
		}

		const cardTemplateAncestor = getClosestAncestorWidget( container, CARD_TEMPLATE_WIDGET );
		if ( cardTemplateAncestor ) {
			syncCardTemplateScope( cardTemplateAncestor );

			return cardTemplateAncestor;
		}

		const relatedListingsAncestor = getClosestAncestorWidget( container, RELATED_LISTINGS_WIDGET );
		if ( relatedListingsAncestor ) {
			return (
				getRelatedListingsTemplateContainer( relatedListingsAncestor ) ||
				getFirstChildContainer( relatedListingsAncestor ) ||
				null
			);
		}

		const pricingPlansAncestor = getClosestAncestorWidget( container, PRICING_PLANS_WIDGET );
		if ( pricingPlansAncestor ) {
			seedPricingPlanDefaultsForContainer( pricingPlansAncestor );

			return getFirstChildContainer( pricingPlansAncestor ) || null;
		}

		const authorProfileAncestor = getClosestAncestorWidget( container, AUTHOR_PROFILE_WIDGET );
		if ( authorProfileAncestor ) {
			seedAuthorProfileDefaultsForContainer( authorProfileAncestor );

			return (
				getAuthorProfileTemplateContainer( authorProfileAncestor ) ||
				getFirstChildContainer( authorProfileAncestor ) ||
				null
			);
		}

		for ( let i = 0; i < TAXONOMY_COMPOSITION_WIDGETS.length; i++ ) {
			const taxonomyAncestor = getClosestAncestorWidget( container, TAXONOMY_COMPOSITION_WIDGETS[ i ] );
			if ( taxonomyAncestor ) {
				return (
					getTaxonomyCompositionTemplateContainer( taxonomyAncestor ) ||
					getFirstChildContainer( taxonomyAncestor ) ||
					null
				);
			}
		}

		for ( let i = 0; i < SEARCH_COMPOSITION_WIDGETS.length; i++ ) {
			const searchAncestor = getClosestAncestorWidget( container, SEARCH_COMPOSITION_WIDGETS[ i ] );
			if ( searchAncestor ) {
				return searchAncestor;
			}
		}

		const singleMapAncestor = getClosestAncestorWidget( container, SINGLE_MAP_WIDGET );
		if ( singleMapAncestor ) {
			return (
				getSingleMapTemplateContainer( singleMapAncestor ) ||
				getFirstChildContainer( singleMapAncestor ) ||
				null
			);
		}

		if ( CARD_TEMPLATE_WIDGET === getContainerType( container ) ) {
			syncCardTemplateScope( container );

			return container;
		}

		if ( RELATED_LISTINGS_WIDGET === getContainerType( container ) ) {
			return getRelatedListingsTemplateContainer( container ) || getFirstChildContainer( container );
		}

		if ( PRICING_PLANS_WIDGET === getContainerType( container ) ) {
			seedPricingPlanDefaultsForContainer( container );

			return getFirstChildContainer( container );
		}

		if ( AUTHOR_PROFILE_WIDGET === getContainerType( container ) ) {
			seedAuthorProfileDefaultsForContainer( container );

			return getAuthorProfileTemplateContainer( container ) || getFirstChildContainer( container );
		}

		if ( isTaxonomyCompositionWidget( getContainerType( container ) ) ) {
			return getTaxonomyCompositionTemplateContainer( container ) || getFirstChildContainer( container );
		}

		if ( isSearchCompositionWidget( getContainerType( container ) ) ) {
			return container;
		}

		if ( SINGLE_MAP_WIDGET === getContainerType( container ) ) {
			return getSingleMapTemplateContainer( container ) || getFirstChildContainer( container );
		}

		return null;
	}

	function walkDescendantContainers( container, callback ) {
		if ( ! container || 'function' !== typeof callback ) {
			return;
		}

		const childModels =
			container.model && typeof container.model.get === 'function'
				? container.model.get( 'elements' )
				: null;

		if ( ! childModels || 'function' !== typeof childModels.forEach ) {
			return;
		}

		childModels.forEach( function( childModel ) {
			if (
				! childModel ||
				! window.elementor ||
				'function' !== typeof window.elementor.getContainer
			) {
				return;
			}

			const childContainer = window.elementor.getContainer( childModel.get( 'id' ) );

			if ( ! childContainer ) {
				return;
			}

			callback( childContainer );
			walkDescendantContainers( childContainer, callback );
		} );
	}

	function walkElementModels( model, callback, depth ) {
		if ( ! model || 'function' !== typeof callback ) {
			return;
		}

		const currentDepth = Number.isInteger( depth ) ? depth : 0;

		callback( model, currentDepth );

		if ( 'function' !== typeof model.get ) {
			return;
		}

		const childModels = model.get( 'elements' );

		if ( ! childModels || 'function' !== typeof childModels.forEach ) {
			return;
		}

		childModels.forEach( function( childModel ) {
			walkElementModels( childModel, callback, currentDepth + 1 );
		} );
	}

	function clearLoopDescendantBindings( view ) {
		if ( ! view || 'function' !== typeof view.stopListening ) {
			return;
		}

		const bindings = Array.isArray( view.__directoristLoopDescendantBindings )
			? view.__directoristLoopDescendantBindings
			: [];

		bindings.forEach( function( binding ) {
			if ( binding && binding.target && binding.event && binding.handler ) {
				view.stopListening( binding.target, binding.event, binding.handler );
			}
		} );

		view.__directoristLoopDescendantBindings = [];
	}

	function bindLoopDescendantBindings( view ) {
		if ( ! view || ! view.model || 'function' !== typeof view.listenTo ) {
			return;
		}

		clearLoopDescendantBindings( view );

		if ( HOME_SEARCH_LOOP_WIDGET !== ( view.model.get( 'widgetType' ) || view.model.get( 'elType' ) || '' ) ) {
			return;
		}

		const bindings = [];
		const registerBinding = function( target, eventName, handler ) {
			if ( ! target || ! eventName || 'function' !== typeof handler ) {
				return;
			}

			view.listenTo( target, eventName, handler );
			bindings.push( {
				target: target,
				event: eventName,
				handler: handler,
			} );
		};

		walkElementModels( view.model, function( elementModel, depth ) {
			if ( depth <= 0 || ! elementModel || 'function' !== typeof elementModel.get ) {
				return;
			}

			const settings = elementModel.get( 'settings' );
			const elements = elementModel.get( 'elements' );
			const elementType = elementModel.get( 'widgetType' ) || elementModel.get( 'elType' ) || '';

			if ( elements ) {
				registerBinding(
					elements,
					'add remove sort reset update',
					view.onDirectoristLoopDescendantStructureChange
				);
			}
		} );

		view.__directoristLoopDescendantBindings = bindings;
	}

	function clearCardTemplateDescendantBindings( view ) {
		const bindings = Array.isArray( view.__directoristDescendantBindings )
			? view.__directoristDescendantBindings
			: [];

		bindings.forEach( function( binding ) {
			if ( binding && binding.target && binding.event && binding.handler ) {
				view.stopListening( binding.target, binding.event, binding.handler );
			}
		} );

		view.__directoristDescendantBindings = [];
	}

	function bindCardTemplateDescendantBindings( view ) {
		if ( ! view || ! view.model || 'function' !== typeof view.listenTo ) {
			return;
		}

		clearCardTemplateDescendantBindings( view );

		const bindings = [];
		const registerBinding = function( target, eventName, handler ) {
			if ( ! target || ! eventName || 'function' !== typeof handler ) {
				return;
			}

			view.listenTo( target, eventName, handler );
			bindings.push( {
				target: target,
				event: eventName,
				handler: handler,
			} );
		};

		walkElementModels( view.model, function( elementModel, depth ) {
			if ( depth <= 0 || ! elementModel || 'function' !== typeof elementModel.get ) {
				return;
			}

			const settings = elementModel.get( 'settings' );
			const elements = elementModel.get( 'elements' );

			if ( settings ) {
				registerBinding(
					settings,
					'change',
					view.onDirectoristCardTemplateDescendantSettingsChange
				);
			}

			if ( elements ) {
				registerBinding(
					elements,
					'add remove sort reset update',
					view.onDirectoristCardTemplateDescendantStructureChange
				);
			}

			registerBinding(
				elementModel,
				'change:hidden',
				view.onDirectoristCardTemplateDescendantStructureChange
			);
		} );

		view.__directoristDescendantBindings = bindings;
	}

	function bindPricingPlanDescendantBindings( view ) {
		if ( ! view || ! view.model || 'function' !== typeof view.listenTo ) {
			return;
		}

		clearCardTemplateDescendantBindings( view );

		const bindings = [];
		const registerBinding = function( target, eventName, handler ) {
			if ( ! target || ! eventName || 'function' !== typeof handler ) {
				return;
			}

			view.listenTo( target, eventName, handler );
			bindings.push( {
				target: target,
				event: eventName,
				handler: handler,
			} );
		};

		walkElementModels( view.model, function( elementModel, depth ) {
			if ( depth <= 0 || ! elementModel || 'function' !== typeof elementModel.get ) {
				return;
			}

			const settings = elementModel.get( 'settings' );
			const elements = elementModel.get( 'elements' );

			if ( settings ) {
				registerBinding(
					settings,
					'change',
					view.onDirectoristPricingPlansDescendantSettingsChange
				);
			}

			if ( elements ) {
				registerBinding(
					elements,
					'add remove sort reset update',
					view.onDirectoristPricingPlansDescendantStructureChange
				);
			}
		} );

		view.__directoristDescendantBindings = bindings;
	}

	function bindPricingPlansRepeaterBindings( view ) {
		if (
			! view ||
			! view.model ||
			'function' !== typeof view.listenTo ||
			'function' !== typeof view.stopListening
		) {
			return;
		}

		const settings = view.model.get( 'settings' );
		const plans = settings && 'function' === typeof settings.get
			? settings.get( 'plans' )
			: null;

		if ( view.__directoristPricingPlansRepeater === plans ) {
			return;
		}

		if ( view.__directoristPricingPlansRepeater ) {
			view.stopListening(
				view.__directoristPricingPlansRepeater,
				'add remove sort reset update change',
				view.onDirectoristPricingPlansStateChange
			);
		}

		view.__directoristPricingPlansRepeater = null;

		if ( plans && 'function' === typeof plans.on ) {
			view.listenTo(
				plans,
				'add remove sort reset update change',
				view.onDirectoristPricingPlansStateChange
			);
			view.__directoristPricingPlansRepeater = plans;
		}
	}

	function syncCardTemplateNavigatorState( container ) {
		if ( CARD_TEMPLATE_WIDGET !== getContainerType( container ) || ! container.model ) {
			return;
		}

		trackCardTemplateContainer( container );

		const scopeState = getCardTemplateScopeState( container );

		updateModelEditorSettings(
			container.model,
			{
				title: getCardTemplateNavigatorTitle( scopeState.label ),
			}
		);

		window.requestAnimationFrame( function() {
			syncCardTemplateNavigatorProjection( container, 0 );
		} );
	}

	function syncCardTemplateEditorDomState( container ) {
		if ( CARD_TEMPLATE_WIDGET !== getContainerType( container ) ) {
			return;
		}

		const view = getContainerView( container );

		if ( ! view || ! view.$el ) {
			return;
		}

		const scopeState = getCardTemplateScopeState( container );
		const activeView = scopeState.viewType || 'grid';
		const $root = view.$el;

		$root
			.removeClass( cardTemplateViewClasses.join( ' ' ) )
			.addClass( `directorist-elementor-card-template--${ activeView }` )
			.attr( 'data-direl-view', activeView )
			.attr( 'data-direl-template', scopeState.key )
			.attr( 'data-direl-directory', scopeState.directoryTypeId );

		$root
			.find( '.directorist-elementor-card-template' )
			.first()
			.removeClass( cardTemplateViewClasses.join( ' ' ) )
			.addClass( `directorist-elementor-card-template--${ activeView }` )
			.attr( 'data-direl-view', activeView )
			.attr( 'data-direl-template', scopeState.key )
			.attr( 'data-direl-directory', scopeState.directoryTypeId );

		$root
			.find( '.directorist-elementor-card-template__content--editor' )
			.first()
			.attr( 'data-direl-view', activeView )
			.attr( 'data-direl-template', scopeState.key )
			.attr( 'data-direl-directory', scopeState.directoryTypeId );

		$root
			.find( '.directorist-elementor-card-template__active-view' )
			.text( scopeState.label );

		applyCardTemplateSettingsToEditor( container );
	}

	function syncCardTemplateEditorState( container, options ) {
		if ( CARD_TEMPLATE_WIDGET !== getContainerType( container ) ) {
			return;
		}

		options = options || {};
		trackCardTemplateContainer( container );

		window.requestAnimationFrame( function() {
			if ( ! options.skipScope ) {
				syncCardTemplateScope( container );
			}

			syncEditorDeviceModeForContainer( container );
			syncCardTemplateNavigatorState( container );
			syncCardTemplateEditorDomState( container );
			syncCardTemplateNavigatorProjection( container, 0 );
			queuePreviewRequest(
				`card:${ container.id || '' }`,
				function() {
					requestCardTemplatePreview( container );
				},
				80
			);
		} );
	}

	function requestLoopPreview( container ) {
		if ( ! isLoopWidget( getContainerType( container ) ) ) {
			return;
		}

		const view = getContainerView( container );
		const loopPayload = buildLoopPayload( container );

		if ( ! view || ! view.$el || ! loopPayload ) {
			return;
		}

		sendPreviewRequest(
			`loop:${ container.id || '' }`,
			getPreviewConfig().actions.loop,
			{
				loop: loopPayload,
			}
		).done( function( response ) {
			if ( ! response || ! response.success || ! response.data || ! view.$el ) {
				return;
			}

			const html = response.data.html || '';

			if ( html ) {
				view.$el.find( '.directorist-elementor-loop__state' ).replaceWith( html );
			}

			syncLoopCanvasState( container );
			syncLoopDescendantCardTemplates( container );
		} );
	}

	function requestCardTemplatePreview( container ) {
		if ( CARD_TEMPLATE_WIDGET !== getContainerType( container ) ) {
			return;
		}

		const view = getContainerView( container );
		const loopEditingState = getLoopEditingState( container );
		const $previewSurface = view && view.$el
			? view.$el.find( '.directorist-elementor-card-template__preview-surface' ).first()
			: $();

		if ( ! view || ! view.$el || ! $previewSurface.length || ! container || ! container.model ) {
			return;
		}

		if ( cardTemplateSettingsLocks.has( getContainerLockKey( container, 'card-template-settings' ) ) ) {
			scheduleCardTemplateSettingsLockFlush( container, 80 );
			return;
		}

		bindCardTemplatePreviewSurfaceClick( container );
		syncCardTemplateScope( container );
		persistCardTemplateActiveScope( container );

		$previewSurface.attr( 'data-direl-preview-state', 'loading' );

		const requestKey = `card:${ container.id || '' }`;
		const requestToken = createPreviewRequestToken( requestKey );

		sendPreviewRequest(
			requestKey,
			getPreviewConfig().actions.cardTemplate,
			{
				loop: loopEditingState ? buildLoopPayload( loopEditingState.container ) : null,
				card: serializeElementModel( container.model ),
			}
		).done( function( response ) {
			if ( ! isPreviewRequestTokenCurrent( requestKey, requestToken ) ) {
				return;
			}

			if ( ! response || ! response.success || ! response.data || ! view.$el ) {
				return;
			}

			$previewSurface
				.attr( 'data-direl-preview-state', 'ready' )
				.html( response.data.html || '' );
			bindCardTemplatePreviewSurfaceClick( container );
			syncEditorDeviceModeForContainer( container );
			triggerLoopSliderEditorInit( $previewSurface );
		} ).fail( function( jqXHR, textStatus ) {
			if ( ! isPreviewRequestTokenCurrent( requestKey, requestToken ) || 'abort' === textStatus ) {
				return;
			}

			$previewSurface
				.attr( 'data-direl-preview-state', 'error' )
				.html(
					'<div class="directorist-elementor-placeholder"><p class="directorist-elementor-placeholder__title">Preview Unavailable</p><p>The active card preview could not be rendered for the current loop scope.</p></div>'
				);
		} );
	}

	function requestPricingPlansPreview( container ) {
		if ( PRICING_PLANS_WIDGET !== getContainerType( container ) ) {
			return;
		}

		const view = getContainerView( container );
		const $previewSurface = view && view.$el
			? view.$el.find( '.directorist-elementor-pricing-plans__preview-surface' ).first()
			: $();

		if ( ! view || ! view.$el || ! $previewSurface.length || ! container || ! container.model ) {
			return;
		}

		$previewSurface.attr( 'data-direl-preview-state', 'loading' );

		sendPreviewRequest(
			`pricing:${ container.id || '' }`,
			getPreviewConfig().actions.pricingPlans,
			{
				widget: serializeElementModel( container.model ),
			}
		).done( function( response ) {
			if ( ! response || ! response.success || ! response.data || ! view.$el ) {
				return;
			}

			$previewSurface
				.attr( 'data-direl-preview-state', 'ready' )
				.html( response.data.html || '' );
			syncEditorDeviceModeForContainer( container );
		} ).fail( function() {
			$previewSurface
				.attr( 'data-direl-preview-state', 'error' )
				.html(
					'<div class="directorist-elementor-placeholder"><p class="directorist-elementor-placeholder__title">Preview Unavailable</p><p>The pricing plans preview could not be rendered from the current widget settings.</p></div>'
				);
		} );
	}

	function requestAuthorProfilePreview( container ) {
		if ( AUTHOR_PROFILE_WIDGET !== getContainerType( container ) ) {
			return;
		}

		const view = getContainerView( container );
		const $previewSurface = view && view.$el
			? view.$el.find( '.directorist-elementor-author-profile__preview-surface' ).first()
			: $();

		if ( ! view || ! view.$el || ! $previewSurface.length || ! container || ! container.model ) {
			return;
		}

		$previewSurface.attr( 'data-direl-preview-state', 'loading' );

		sendPreviewRequest(
			`author-profile:${ container.id || '' }`,
			getPreviewConfig().actions.authorProfile,
			{
				widget: serializeElementModel( container.model ),
			}
		).done( function( response ) {
			if ( ! response || ! response.success || ! response.data || ! view.$el ) {
				return;
			}

			$previewSurface
				.attr( 'data-direl-preview-state', 'ready' )
				.html( response.data.html || '' );
			syncEditorDeviceModeForContainer( container );
		} ).fail( function() {
			$previewSurface
				.attr( 'data-direl-preview-state', 'error' )
				.html(
					'<div class="directorist-elementor-placeholder"><p class="directorist-elementor-placeholder__title">Preview Unavailable</p><p>The author profile preview could not be rendered from the current element settings.</p></div>'
				);
		} );
	}

	function syncRelatedListingsEditorState( container ) {
		if ( RELATED_LISTINGS_WIDGET !== getContainerType( container ) ) {
			return;
		}

		trackRelatedListingsContainer( container );

		window.requestAnimationFrame( function() {
			syncRelatedListingsNavigatorProjection( container, 0 );
		} );
	}

	function requestRelatedListingsPreview( container ) {
		if ( RELATED_LISTINGS_WIDGET !== getContainerType( container ) ) {
			return;
		}

		const view = getContainerView( container );
		const $previewSurface = view && view.$el
			? view.$el.find( '.directorist-elementor-related-listings__preview-surface' ).first()
			: $();

		if ( ! view || ! view.$el || ! $previewSurface.length || ! container || ! container.model ) {
			return;
		}

		$previewSurface.attr( 'data-direl-preview-state', 'loading' );

		sendPreviewRequest(
			`related:${ container.id || '' }`,
			getPreviewConfig().actions.relatedListings,
			{
				widget: serializeElementModel( container.model ),
			}
		).done( function( response ) {
			if ( ! response || ! response.success || ! response.data || ! view.$el ) {
				return;
			}

			$previewSurface
				.attr( 'data-direl-preview-state', 'ready' )
				.html( response.data.html || '' );
			triggerLoopSliderEditorInit( $previewSurface );
		} ).fail( function() {
			$previewSurface
				.attr( 'data-direl-preview-state', 'error' )
				.html(
					'<div class="directorist-elementor-placeholder"><p class="directorist-elementor-placeholder__title">Preview Unavailable</p><p>The related listings preview could not be rendered for the current single listing context.</p></div>'
				);
		} );
	}

	function triggerLoopSliderEditorInit( $previewSurface ) {
		const root = $previewSurface && $previewSurface.length
			? $previewSurface.get( 0 )
			: null;

		if ( ! root ) {
			return;
		}

		const rootWindow = root.ownerDocument && root.ownerDocument.defaultView
			? root.ownerDocument.defaultView
			: window;
		const init = rootWindow.directoristElementorLoopSlider && 'function' === typeof rootWindow.directoristElementorLoopSlider.init
			? rootWindow.directoristElementorLoopSlider.init
			: (
				rootWindow.directoristElementorRelatedSlider && 'function' === typeof rootWindow.directoristElementorRelatedSlider.init
					? rootWindow.directoristElementorRelatedSlider.init
					: null
			);

		const run = function() {
			if ( 'function' === typeof init ) {
				init( root );
			}

			if ( 'function' === typeof rootWindow.CustomEvent && 'function' === typeof rootWindow.dispatchEvent ) {
				rootWindow.dispatchEvent( new rootWindow.CustomEvent( 'directorist-instant-search-reloaded' ) );
				rootWindow.dispatchEvent( new rootWindow.CustomEvent( 'directorist-reload-listings-map-archive' ) );
			}
		};

		if ( 'function' === typeof rootWindow.requestAnimationFrame ) {
			rootWindow.requestAnimationFrame( run );
			return;
		}

		rootWindow.setTimeout( run, 0 );
	}

	function triggerAllLoopSliderEditorInit() {
		if ( ! window.elementor || ! window.elementor.$previewContents || ! window.elementor.$previewContents.length ) {
			return;
		}

		const previewRoot = window.elementor.$previewContents.get( 0 );
		const previewDocument = previewRoot && 9 === previewRoot.nodeType
			? previewRoot
			: ( previewRoot && previewRoot.ownerDocument ? previewRoot.ownerDocument : null );
		const previewWindow = previewDocument && previewDocument.defaultView
			? previewDocument.defaultView
			: null;
		const init = previewWindow && previewWindow.directoristElementorLoopSlider && 'function' === typeof previewWindow.directoristElementorLoopSlider.init
			? previewWindow.directoristElementorLoopSlider.init
			: null;

		if ( ! init ) {
			return;
		}

		const run = function() {
			init( previewDocument );
		};

		if ( 'function' === typeof previewWindow.requestAnimationFrame ) {
			previewWindow.requestAnimationFrame( run );
			return;
		}

		previewWindow.setTimeout( run, 0 );
	}

	function applyListingRatingEditorPreview( root ) {
		if ( ! root || 'function' !== typeof root.querySelectorAll ) {
			return;
		}

		root.querySelectorAll( '.directorist-elementor-listing-card-rating' ).forEach( function( rating ) {
			const value = rating.querySelector( '.directorist-elementor-listing-card-rating__value' );

			if ( ! value || parseFloat( value.textContent || '0' ) > 0 ) {
				return;
			}

			value.textContent = '4.5';

			const count = rating.querySelector( '.directorist-elementor-listing-card-rating__count' );
			if ( count ) {
				count.textContent = '(2)';
			}

			rating.querySelectorAll( '.directorist-elementor-listing-card-rating__star' ).forEach( function( star, index ) {
				star.style.setProperty( '--direl-rating-star-fill', index < 4 ? '100%' : ( 4 === index ? '50%' : '0%' ) );
			} );
		} );
	}

	function bindListingRatingEditorPreview() {
		if ( ! window.elementor || ! window.elementor.$previewContents || ! window.elementor.$previewContents.length ) {
			return;
		}

		const previewRoot = window.elementor.$previewContents.get( 0 );
		const previewDocument = previewRoot && 9 === previewRoot.nodeType
			? previewRoot
			: ( previewRoot && previewRoot.ownerDocument ? previewRoot.ownerDocument : null );

		if ( ! previewDocument ) {
			return;
		}

		applyListingRatingEditorPreview( previewDocument );

		if ( listingRatingPreviewDocument === previewDocument && listingRatingPreviewObserver ) {
			return;
		}

		if ( listingRatingPreviewObserver ) {
			listingRatingPreviewObserver.disconnect();
		}

		listingRatingPreviewDocument = previewDocument;
		listingRatingPreviewObserver = new MutationObserver( function( mutations ) {
			mutations.forEach( function( mutation ) {
				mutation.addedNodes.forEach( function( node ) {
					if ( 1 !== node.nodeType ) {
						return;
					}

					if ( node.matches && node.matches( '.directorist-elementor-listing-card-rating' ) ) {
						applyListingRatingEditorPreview( node.parentNode || node );
						return;
					}

					applyListingRatingEditorPreview( node );
				} );
			} );
		} );
		listingRatingPreviewObserver.observe( previewDocument.body, {
			childList: true,
			subtree: true,
		} );
	}

	function syncTaxonomyCompositionEditorState( container ) {
		if ( ! isTaxonomyCompositionWidget( getContainerType( container ) ) ) {
			return;
		}

		trackTaxonomyCompositionContainer( container );

		window.requestAnimationFrame( function() {
			syncTaxonomyCompositionNavigatorProjection( container, 0 );
		} );
	}

	function requestTaxonomyCompositionPreview( container ) {
		if ( ! isTaxonomyCompositionWidget( getContainerType( container ) ) ) {
			return;
		}

		const view = getContainerView( container );
		const $previewSurface = view && view.$el
			? view.$el.find( '.directorist-elementor-taxonomy-composition__preview-surface' ).first()
			: $();

		if ( ! view || ! view.$el || ! $previewSurface.length || ! container || ! container.model ) {
			return;
		}

		$previewSurface.attr( 'data-direl-preview-state', 'loading' );

		sendPreviewRequest(
			`taxonomy:${ container.id || '' }`,
			getPreviewConfig().actions.taxonomyArchive,
			{
				widget: serializeElementModel( container.model ),
			}
		).done( function( response ) {
			if ( ! response || ! response.success || ! response.data || ! view.$el ) {
				return;
			}

			$previewSurface
				.attr( 'data-direl-preview-state', 'ready' )
				.html( response.data.html || '' );

			if ( window.directoristElementorTaxonomySlider && 'function' === typeof window.directoristElementorTaxonomySlider.init ) {
				window.directoristElementorTaxonomySlider.init( $previewSurface.get( 0 ) );
			}
			if ( window.directoristElementorTaxonomySlider && 'function' === typeof window.directoristElementorTaxonomySlider.bindWidgets ) {
				window.directoristElementorTaxonomySlider.bindWidgets( $previewSurface.get( 0 ) );
			}
		} ).fail( function() {
			$previewSurface
				.attr( 'data-direl-preview-state', 'error' )
				.html(
					'<div class="directorist-elementor-placeholder"><p class="directorist-elementor-placeholder__title">Preview Unavailable</p><p>The category/location preview could not be rendered for the current widget settings.</p></div>'
				);
		} );
	}

	function syncSearchCompositionEditorState( container, options ) {
		if ( ! isSearchCompositionWidget( getContainerType( container ) ) ) {
			return;
		}

		options = options || {};

		window.requestAnimationFrame( function() {
			if ( ! options.skipScope ) {
				syncSearchCompositionScope( container );
			}

			syncEditorDeviceModeForContainer( container );
		} );
	}

	function requestSearchCompositionPreview( container ) {
		if ( ! isSearchCompositionWidget( getContainerType( container ) ) ) {
			return;
		}

		const view = getContainerView( container );
		const loopEditingState = getLoopEditingState( container );
		const $previewSurface = view && view.$el
			? view.$el.find( '.directorist-elementor-search-composition__preview-surface' ).first()
			: $();
		const searchPreviewKey = `search:${ getLoopEditorStateKey( container ) }`;

		if ( ! view || ! view.$el || ! $previewSurface.length || ! container || ! container.model ) {
			return;
		}

		syncSearchCompositionScope( container );
		persistSearchCompositionActiveScope( container );
		bindSearchCompositionPreviewSurfaceClick( container );
		$previewSurface.attr( 'data-direl-preview-state', 'loading' );

		sendPreviewRequest(
			searchPreviewKey,
			getPreviewConfig().actions.searchComposition,
			{
				loop: loopEditingState ? buildLoopPayload( loopEditingState.container ) : null,
				widget: serializeElementModel( container.model ),
			}
		).done( function( response ) {
			if ( ! response || ! response.success || ! response.data || ! view.$el ) {
				return;
			}

			$previewSurface
				.attr( 'data-direl-preview-state', 'ready' )
				.html( response.data.html || '' );
			bindSearchCompositionPreviewSurfaceClick( container );
			syncEditorDeviceModeForContainer( container );
		} ).fail( function() {
			$previewSurface
				.attr( 'data-direl-preview-state', 'error' )
				.html(
					'<div class="directorist-elementor-placeholder"><p class="directorist-elementor-placeholder__title">Preview Unavailable</p><p>The search composition preview could not be rendered for the current directory context.</p></div>'
				);
		} );
	}

	function syncSingleMapEditorState( container ) {
		if ( SINGLE_MAP_WIDGET !== getContainerType( container ) ) {
			return;
		}

		trackSingleMapContainer( container );

		window.requestAnimationFrame( function() {
			syncSingleMapNavigatorProjection( container, 0 );
		} );
	}

	function requestSingleMapPreview( container ) {
		if ( SINGLE_MAP_WIDGET !== getContainerType( container ) ) {
			return;
		}

		const view = getContainerView( container );
		const $previewSurface = view && view.$el
			? view.$el.find( '.directorist-elementor-single-map__preview-surface' ).first()
			: $();

		if ( ! view || ! view.$el || ! $previewSurface.length || ! container || ! container.model ) {
			return;
		}

		$previewSurface.attr( 'data-direl-preview-state', 'loading' );

		sendPreviewRequest(
			`single-map:${ container.id || '' }`,
			getPreviewConfig().actions.singleMap,
			{
				widget: serializeElementModel( container.model ),
			}
		).done( function( response ) {
			if ( ! response || ! response.success || ! response.data || ! view.$el ) {
				return;
			}

			$previewSurface
				.attr( 'data-direl-preview-state', 'ready' )
				.html( response.data.html || '' );

			triggerSingleMapEditorInit( $previewSurface );
		} ).fail( function() {
			$previewSurface
				.attr( 'data-direl-preview-state', 'error' )
				.html(
					'<div class="directorist-elementor-placeholder"><p class="directorist-elementor-placeholder__title">Preview Unavailable</p><p>The single listing map preview could not be rendered for the current listing context.</p></div>'
				);
		} );
	}

	function triggerSingleMapEditorInit( $previewSurface ) {
		const $ = window.jQuery;

		if ( ! $ || 'function' !== typeof $.fn.triggerHandler ) {
			return;
		}

		function parseSingleMapData( mapElement ) {
			if ( ! mapElement || 'function' !== typeof mapElement.getAttribute ) {
				return null;
			}

			try {
				return JSON.parse( mapElement.getAttribute( 'data-map' ) || '{}' );
			} catch ( error ) {
				return null;
			}
		}

		function resolveSingleMapPosition( mapData ) {
			if ( ! mapData ) {
				return null;
			}

			const defaultLat = parseFloat( mapData.default_latitude );
			const defaultLng = parseFloat( mapData.default_longitude );
			const manualLat = parseFloat( mapData.manual_lat );
			const manualLng = parseFloat( mapData.manual_lng );
			const lat = Number.isFinite( manualLat ) ? manualLat : defaultLat;
			const lng = Number.isFinite( manualLng ) ? manualLng : defaultLng;

			if ( ! Number.isFinite( lat ) || ! Number.isFinite( lng ) ) {
				return null;
			}

			return {
				lat,
				lng,
				zoom: parseInt( mapData.map_zoom_level, 10 ) || 13,
				displayInfo: !! mapData.display_map_info,
				popupHtml: mapData.info_content || '',
				iconHtml: mapData.cat_icon || '<i class="directorist-icon-mask"></i>',
			};
		}

		function initLeafletSingleMap( mapElement ) {
			if ( ! window.L || ! mapElement || mapElement.__direlSingleMapInitialized ) {
				return false;
			}

			if ( mapElement.classList.contains( 'leaflet-container' ) ) {
				mapElement.__direlSingleMapInitialized = true;
				return true;
			}

			const mapData = parseSingleMapData( mapElement );
			const position = resolveSingleMapPosition( mapData );

			if ( ! mapData || ! position ) {
				return false;
			}

			const markerIcon = window.L.divIcon( {
				html: `<div class="atbd_map_shape">${ position.iconHtml }</div>`,
				iconSize: [ 20, 20 ],
				className: 'myDivIcon',
			} );
			const mapInstance = window.L.map( mapElement, {
				scrollWheelZoom: false,
			} ).setView( [ position.lat, position.lng ], position.zoom );
			const marker = window.L.marker( [ position.lat, position.lng ], {
				icon: markerIcon,
			} ).addTo( mapInstance );

			if ( position.displayInfo && position.popupHtml ) {
				marker.bindPopup( position.popupHtml, {
					maxWidth: 400,
				} );

				if ( 'yes' === mapElement.getAttribute( 'data-direl-editor-open-popup' ) ) {
					marker.openPopup();
				}
			}

			window.L.tileLayer( 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
				attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
			} ).addTo( mapInstance );

			mapElement.__direlSingleMapInitialized = {
				type: 'leaflet',
				map: mapInstance,
				marker,
			};

			[ 40, 180, 420 ].forEach( function( delay ) {
				window.setTimeout( function() {
					if ( mapInstance && 'function' === typeof mapInstance.invalidateSize ) {
						mapInstance.invalidateSize();
					}

					if ( marker && 'function' === typeof marker.openPopup && 'yes' === mapElement.getAttribute( 'data-direl-editor-open-popup' ) ) {
						marker.openPopup();
					}
				}, delay );
			} );

			return true;
		}

		function initGoogleSingleMap( mapElement ) {
			if (
				! window.google ||
				! window.google.maps ||
				! window.google.maps.Map ||
				! window.google.maps.InfoWindow ||
				! window.google.maps.marker ||
				! window.google.maps.marker.AdvancedMarkerElement ||
				! mapElement ||
				mapElement.__direlSingleMapInitialized
			) {
				return false;
			}

			if ( mapElement.querySelector( '.gm-style' ) ) {
				mapElement.__direlSingleMapInitialized = true;
				return true;
			}

			const mapData = parseSingleMapData( mapElement );
			const position = resolveSingleMapPosition( mapData );

			if ( ! mapData || ! position ) {
				return false;
			}

			const markerShape = document.createElement( 'div' );
			markerShape.className = 'atbd_map_shape';
			markerShape.innerHTML = position.iconHtml;

			const mapInstance = new window.google.maps.Map( mapElement, {
				zoom: position.zoom,
				center: {
					lat: position.lat,
					lng: position.lng,
				},
				mapId: 'single_listing_map',
			} );
			const marker = new window.google.maps.marker.AdvancedMarkerElement( {
				map: mapInstance,
				position: {
					lat: position.lat,
					lng: position.lng,
				},
				content: markerShape,
			} );
			let infoWindow = null;
			let isOpen = false;

			if ( position.displayInfo && position.popupHtml ) {
				infoWindow = new window.google.maps.InfoWindow( {
					content: position.popupHtml,
					maxWidth: 400,
				} );

				marker.addListener( 'click', function() {
					if ( isOpen ) {
						infoWindow.close();
						isOpen = false;
						return;
					}

					try {
						infoWindow.open( {
							anchor: marker,
							map: mapInstance,
						} );
					} catch ( error ) {
						infoWindow.open( mapInstance, marker );
					}

					isOpen = true;
				} );

				if ( 'yes' === mapElement.getAttribute( 'data-direl-editor-open-popup' ) ) {
					window.setTimeout( function() {
						try {
							infoWindow.open( {
								anchor: marker,
								map: mapInstance,
							} );
						} catch ( error ) {
							infoWindow.open( mapInstance, marker );
						}

						isOpen = true;
					}, 160 );
				}
			}

			mapElement.__direlSingleMapInitialized = {
				type: 'google',
				map: mapInstance,
				marker,
				infoWindow,
			};

			return true;
		}

		function ensureSingleMapPreviewInstances( $root ) {
			if ( ! $root || ! $root.length ) {
				return false;
			}

			let initialized = false;

			$root.find( '.directorist-single-map' ).each( function() {
				if ( this.classList.contains( 'leaflet-container' ) || this.querySelector( '.gm-style' ) ) {
					this.__direlSingleMapInitialized = true;
					initialized = true;
					return;
				}

				if ( initGoogleSingleMap( this ) || initLeafletSingleMap( this ) ) {
					initialized = true;
				}
			} );

			return initialized;
		}

		function activateSingleMapPopup( $root ) {
			if ( ! $root || ! $root.length ) {
				return false;
			}

			if ( $root.find( '.leaflet-popup, .gm-style .gm-style-iw-c' ).length ) {
				return true;
			}

			const markerElement =
				$root.find( '.leaflet-marker-icon' ).get( 0 ) ||
				$root.find( '.gm-style .atbd_map_shape' ).get( 0 ) ||
				$root.find( '.leaflet-marker-pane .atbd_map_shape' ).get( 0 ) ||
				null;

			if ( ! markerElement || 'function' !== typeof markerElement.dispatchEvent ) {
				return false;
			}

			markerElement.dispatchEvent(
				new window.MouseEvent( 'click', {
					bubbles: false,
					cancelable: true,
					view: window,
				} )
			);

			return true;
		}

		[ 40, 220, 620, 980, 1380 ].forEach( function( delay ) {
			window.setTimeout( function() {
				const $root = $previewSurface && $previewSurface.length
					? $previewSurface
					: $( '.directorist-elementor-single-map__preview-surface' );
				let needsInit = false;

				$root.find( '.directorist-single-map' ).each( function() {
					const hasLeafletMount = this.classList.contains( 'leaflet-container' );
					const hasGoogleMount = !! this.querySelector( '.gm-style' );

					if ( ! hasLeafletMount && ! hasGoogleMount ) {
						needsInit = true;
					}
				} );

				if ( ! needsInit ) {
					activateSingleMapPopup( $root );
					return;
				}

				if ( ensureSingleMapPreviewInstances( $root ) ) {
					window.setTimeout( function() {
						activateSingleMapPopup( $root );
					}, 120 );
					return;
				}

				if ( 'function' === typeof window.directoristLoadGoogleMap ) {
					window.directoristLoadGoogleMap();
				}

				$( 'body' ).triggerHandler( 'click' );
				window.setTimeout( function() {
					ensureSingleMapPreviewInstances( $root );
				}, 80 );
				window.setTimeout( function() {
					activateSingleMapPopup( $root );
				}, 160 );
			}, delay );
		} );
	}

	function updateLoopUtilityPreviewHtml( view, html ) {
		if ( ! view || ! view.$el ) {
			return false;
		}

		const $container = view.$el.find( '.elementor-widget-container' ).first();

		if ( ! $container.length ) {
			return false;
		}

		$container.html( html || '' );
		return true;
	}

	function bindEditorSearchDirectoryTypeNavGuard( view ) {
		const root = view && view.$el && view.$el.get( 0 );
		const ownerDocument = root && root.ownerDocument ? root.ownerDocument : document;

		if ( ! ownerDocument || ownerDocument.__directoristSearchDirectoryTypeNavGuardBound ) {
			return;
		}

		ownerDocument.__directoristSearchDirectoryTypeNavGuardBound = true;
		ownerDocument.addEventListener(
			'click',
			function( event ) {
				const target = event.target && 'function' === typeof event.target.closest
					? event.target.closest(
						'.directorist-elementor-search-directory-types[data-direl-editor-inert="1"] .directorist-type-nav__link, ' +
						'.directorist-elementor-search-directory-types[data-direl-editor-inert="1"] .search_listing_types'
					)
					: null;

				if ( ! target ) {
					return;
				}

				event.preventDefault();
				event.stopPropagation();

				if ( 'function' === typeof event.stopImmediatePropagation ) {
					event.stopImmediatePropagation();
				}
			},
			true
		);
	}

	function markEditorSearchDirectoryTypesPreview( container, view ) {
		if ( SEARCH_DIRECTORY_TYPES_WIDGET !== getContainerType( container ) || ! view || ! view.$el ) {
			return;
		}

		const $root = view.$el.find( '.directorist-elementor-search-directory-types' ).first();

		if ( ! $root.length ) {
			return;
		}

		$root.attr( 'data-direl-editor-inert', '1' );
		$root.find( '.directorist-type-nav__link, .search_listing_types' ).each( function() {
			const $item = $( this );

			$item.attr( {
				'aria-disabled': 'true',
				'data-direl-editor-inert': '1',
			} );

			if ( this.tagName && 'a' === this.tagName.toLowerCase() ) {
				if ( ! $item.attr( 'data-direl-editor-href' ) ) {
					$item.attr( 'data-direl-editor-href', $item.attr( 'href' ) || '' );
				}

				$item.attr( 'href', '#' );
			}
		} );

		bindEditorSearchDirectoryTypeNavGuard( view );
	}

	function requestLoopUtilityPreview( container, loopContainer ) {
		const widgetType = getContainerType( container );

		if ( ! isLoopUtilityWidget( widgetType ) ) {
			return;
		}

		const view = getContainerView( container );
		const loopPayload = buildLoopPayload( loopContainer );
		const loopSettings = getContainerSettings( loopContainer );
		const loopActiveView = resolveLoopPreviewView( loopSettings );
		const loopDisplayMode = resolveLoopDisplayMode( loopSettings, loopActiveView );

		if ( ! view || ! view.$el || ! container || ! container.model || ! loopPayload ) {
			return;
		}

		if ( LISTINGS_PAGINATION_WIDGET === widgetType && 'slider' === loopDisplayMode ) {
			view.$el.removeClass( 'elementor-loading' ).hide();
			if ( container.model && 'function' === typeof container.model.setHtmlCache ) {
				container.model.setHtmlCache( '' );
			}
			updateLoopUtilityPreviewHtml( view, '' );
			return;
		}

		view.$el.show();
		view.$el.addClass( 'elementor-loading' );

		sendPreviewRequest(
			`utility:${ container.id || container.model.get( 'id' ) || '' }`,
			getPreviewConfig().actions.loopUtility,
			{
				loop: loopPayload,
				widget: serializeElementModel( container.model ),
			}
		).done( function( response ) {
			if ( ! response || ! response.success || ! response.data || ! view.$el ) {
				return;
			}

			view.$el.removeClass( 'elementor-loading' );

			if (
				LISTINGS_PAGINATION_WIDGET === widgetType &&
				'slider' === resolveLoopDisplayMode( getContainerSettings( loopContainer ), resolveLoopPreviewView( getContainerSettings( loopContainer ) ) )
			) {
				view.$el.hide();
				if ( container.model && 'function' === typeof container.model.setHtmlCache ) {
					container.model.setHtmlCache( '' );
				}
				updateLoopUtilityPreviewHtml( view, '' );
				return;
			}

			if ( SEARCH_DIRECTORY_TYPES_WIDGET === widgetType ) {
				updateLoopUtilityPreviewHtml( view, response.data.html || '' );
				markEditorSearchDirectoryTypesPreview( container, view );
				syncEditorDeviceModeForContainer( container );
				return;
			}

			if ( container.model && 'function' === typeof container.model.setHtmlCache ) {
				container.model.setHtmlCache( response.data.html || '' );
			}

			if ( 'function' === typeof view.render ) {
				view.render();
				return;
			}

			updateLoopUtilityPreviewHtml( view, response.data.html || '' );
		} ).fail( function() {
			view.$el.removeClass( 'elementor-loading' );
		} );
	}

	function syncLoopCanvasState( container ) {
		if ( ! isLoopWidget( getContainerType( container ) ) ) {
			return;
		}

		const view = getContainerView( container );

		if ( ! view || ! view.$el ) {
			return;
		}

		const settings = getContainerSettings( container );
		const activeDirectoryId = String( resolveLoopActiveDirectoryId( settings, container ) || '0' );
		const activeView = resolveLoopPreviewView( settings );
		const displayMode = resolveLoopDisplayMode( settings, activeView );
		const $root = view.$el;
		const directoryTabs = getLoopDirectoryTabs( settings, container );
		const $directoryTabs = $root.find( '.directorist-elementor-loop__directory-tabs' ).first();
		const availableViews = getLoopAvailableViews();
		const $viewTabs = $root.find( '.directorist-elementor-loop__view-tabs' ).first();

		if ( $directoryTabs.length ) {
			const currentDirectoryIds = $directoryTabs
				.find( '[data-direl-loop-directory]' )
				.map( function() {
					return String( $( this ).attr( 'data-direl-loop-directory' ) || '' );
				} )
				.get();
			const nextDirectoryIds = directoryTabs.map( function( tab ) {
				return String( tab.id );
			} );

			if ( currentDirectoryIds.join( ',' ) !== nextDirectoryIds.join( ',' ) ) {
				$directoryTabs.empty();

				directoryTabs.forEach( function( tab ) {
					$( '<button />', {
						type: 'button',
						class: 'directorist-elementor-loop__scope-button directorist-elementor-loop__scope-button--directory',
						'data-direl-loop-directory': String( tab.id ),
						text: tab.label,
					} ).appendTo( $directoryTabs );
				} );
			}
		}

		if ( $viewTabs.length ) {
			const currentViews = $viewTabs
				.find( '[data-direl-loop-view]' )
				.map( function() {
					return String( $( this ).attr( 'data-direl-loop-view' ) || '' );
				} )
				.get();

			if ( currentViews.join( ',' ) !== availableViews.join( ',' ) ) {
				const viewIcons = {
					grid: 'eicon-gallery-grid',
					list: 'eicon-post-list',
					map: 'eicon-google-maps',
				};

				$viewTabs.empty();

				availableViews.forEach( function( viewType ) {
					$( '<button />', {
						type: 'button',
						class: 'directorist-elementor-loop__scope-button directorist-elementor-loop__scope-button--view',
						'data-direl-loop-view': viewType,
						'aria-label': getViewLabel( viewType ),
						title: getViewLabel( viewType ),
					} )
						.append( $( '<i />', {
							class: viewIcons[ viewType ] || '',
							'aria-hidden': 'true',
						} ) )
						.appendTo( $viewTabs );
				} );
			}
		}

		$root
			.attr( 'data-direl-active-directory', activeDirectoryId )
			.attr( 'data-direl-active-view', activeView )
			.attr( 'data-direl-directory', activeDirectoryId )
			.attr( 'data-direl-view', activeView )
			.attr( 'data-display-mode', displayMode )
			.removeClass( loopDisplayModeClasses.join( ' ' ) )
			.addClass( `directorist-elementor-loop--display-${ displayMode }` );

		$root
			.find( '[data-direl-loop-directory]' )
			.each( function() {
				const isActive = String( $( this ).attr( 'data-direl-loop-directory' ) || '' ) === activeDirectoryId;

				$( this ).toggleClass( 'is-active', isActive );
			} );

		$root
			.find( '[data-direl-loop-view]' )
			.each( function() {
				const isActive = String( $( this ).attr( 'data-direl-loop-view' ) || '' ) === activeView;

				$( this ).toggleClass( 'is-active', isActive );
			} );

		$root
			.find( '.directorist-elementor-listings-pagination' )
			.closest( '.elementor-element' )
			.toggle( -1 === [ 'slider', 'map_list' ].indexOf( displayMode ) );
	}

	function syncLoopDescendantCardTemplates( container ) {
		if ( ! isLoopWidget( getContainerType( container ) ) ) {
			return;
		}

		walkDescendantContainers( container, function( descendantContainer ) {
			if ( CARD_TEMPLATE_WIDGET === getContainerType( descendantContainer ) ) {
				syncCardTemplateEditorState( descendantContainer );
				queuePreviewRequest(
					`card:${ descendantContainer.id || '' }`,
					function() {
						requestCardTemplatePreview( descendantContainer );
					}
				);
			}
		} );
	}

	function persistLoopDescendantCardTemplatesBeforeScopeChange( container ) {
		if ( ! isLoopWidget( getContainerType( container ) ) ) {
			return;
		}

		walkDescendantContainers( container, function( descendantContainer ) {
			if ( CARD_TEMPLATE_WIDGET !== getContainerType( descendantContainer ) ) {
				return;
			}

			const currentState = getCardTemplateCurrentPersistState( descendantContainer );

			if ( currentState && currentState.key ) {
				persistCardTemplateActiveScope( descendantContainer, currentState, { force: true } );
			}
		} );
	}

	function syncLoopDescendantSearchCompositions( container ) {
		if ( ! isLoopWidget( getContainerType( container ) ) ) {
			return;
		}

		walkDescendantContainers( container, function( descendantContainer ) {
			if ( isSearchCompositionWidget( getContainerType( descendantContainer ) ) ) {
				syncSearchCompositionEditorState( descendantContainer );
				queuePreviewRequest(
					`search:${ descendantContainer.id || '' }`,
					function() {
						requestSearchCompositionPreview( descendantContainer );
					}
				);
			}
		} );
	}

	function syncLoopDescendantUtilityWidgets( container ) {
		if ( ! isLoopWidget( getContainerType( container ) ) ) {
			return;
		}

		walkDescendantContainers( container, function( descendantContainer ) {
			if ( isLoopUtilityWidget( getContainerType( descendantContainer ) ) ) {
				queuePreviewRequest(
					`utility:${ descendantContainer.id || ( descendantContainer.model ? descendantContainer.model.get( 'id' ) : '' ) || '' }`,
					function() {
						requestLoopUtilityPreview( descendantContainer, container );
					}
				);
			}
		} );
	}

	function syncLoopEditorState( container ) {
		if ( ! isLoopWidget( getContainerType( container ) ) ) {
			return;
		}

		window.requestAnimationFrame( function() {
			normalizeLoopScopeSettings( container );
			applyLoopEditorRootState( container );
			syncEditorDeviceModeForContainer( container );
			syncLoopCanvasState( container );
			syncLoopDefaultDirectoryControl( container );
			queuePreviewRequest(
				`loop:${ container.id || '' }`,
				function() {
					requestLoopPreview( container );
				}
				);
				syncLoopDescendantUtilityWidgets( container );
				syncLoopDescendantCardTemplates( container );
				syncLoopDescendantSearchCompositions( container );
			} );
	}
	function getDefaultChildrenModels( container ) {
		if ( ! container || ! container.model ) {
			return [];
		}

		if ( typeof container.model.getDefaultChildren === 'function' ) {
			return container.model.getDefaultChildren();
		}

		return [];
	}

	function ensureCompositionTarget( container ) {
		const existingTarget = resolveCompositionTarget( container );

		if ( existingTarget ) {
			return existingTarget;
		}

		if (
			! container ||
			! container.model ||
			typeof container.model.get !== 'function' ||
			! window.$e ||
			typeof window.$e.run !== 'function'
		) {
			return null;
		}

		const widgetType = container.model.get( 'widgetType' ) || '';
		const childElements = container.model.get( 'elements' );

		if ( ! isDirectoristNestedWidget( widgetType ) || ( childElements && childElements.length ) ) {
			return null;
		}

		getDefaultChildrenModels( container ).forEach( ( childModel, index ) => {
			window.$e.run( 'document/elements/create', {
				container: container,
				model: childModel,
				options: {
					edit: false,
					at: index,
				},
			} );
		} );

		return resolveCompositionTarget( container );
	}

	function selectContainer( container ) {
		container = normalizeContainer( container );

		if (
			! container ||
			! window.$e ||
			typeof window.$e.run !== 'function'
		) {
			return;
		}

		window.$e.run( 'document/elements/select', {
			container: container,
		} );

		scheduleSearchCompositionPreviewScroll( container );
	}

	function getContainerModelId( container ) {
		container = normalizeContainer( container );

		if ( ! container ) {
			return '';
		}

		if ( container.model && 'function' === typeof container.model.get ) {
			return String( container.model.get( 'id' ) || container.id || '' );
		}

		return String( container.id || '' );
	}

	function escapeSelectorValue( value ) {
		value = String( value || '' );

		if ( $.escapeSelector ) {
			return $.escapeSelector( value );
		}

		if ( window.CSS && 'function' === typeof window.CSS.escape ) {
			return window.CSS.escape( value );
		}

		return value.replace( /([!"#$%&'()*+,./:;<=>?@[\\\]^`{|}~])/g, '\\$1' );
	}

	function scrollSearchCompositionPreviewCloneIntoView( container ) {
		container = normalizeContainer( container );

		if ( ! container ) {
			return false;
		}

		const searchContainer = isSearchCompositionWidget( getContainerType( container ) )
			? container
			: getClosestAncestorWidget( container, SEARCH_COMPOSITION_WIDGETS );
		const selectedModelId = getContainerModelId( container );
		const searchModelId = getContainerModelId( searchContainer );

		if ( ! searchContainer || ! selectedModelId || selectedModelId === searchModelId ) {
			return false;
		}

		const view = getContainerView( searchContainer );
		const previewSurface = view && view.$el
			? view.$el.find( '.directorist-elementor-search-composition__preview-surface' ).get( 0 )
			: null;

		if ( ! previewSurface ) {
			return false;
		}

		const previewElement = previewSurface.querySelector(
			`.elementor-element[data-id="${ escapeSelectorValue( selectedModelId ) }"]`
		);

		if ( ! previewElement || 'function' !== typeof previewElement.scrollIntoView ) {
			return false;
		}

		previewElement.scrollIntoView( {
			block: 'center',
			inline: 'nearest',
			behavior: 'smooth',
		} );

		return true;
	}

	function scheduleSearchCompositionPreviewScroll( container, attempt ) {
		container = normalizeContainer( container );
		attempt = attempt || 0;

		if ( ! container || ! getClosestAncestorWidget( container, SEARCH_COMPOSITION_WIDGETS ) ) {
			return;
		}

		window.requestAnimationFrame( function() {
			if ( scrollSearchCompositionPreviewCloneIntoView( container ) || attempt >= 4 ) {
				return;
			}

			window.setTimeout( function() {
				scheduleSearchCompositionPreviewScroll( container, attempt + 1 );
			}, 120 );
		} );
	}

	function bindSearchCompositionSelectionBridge() {
		if (
			searchCompositionSelectionBridgeBound ||
			! window.elementor ||
			! window.elementor.hooks ||
			'function' !== typeof window.elementor.hooks.addAction
		) {
			return searchCompositionSelectionBridgeBound;
		}

		const onPanelOpen = function( manager, model, view ) {
			let container = view && 'function' === typeof view.getContainer
				? view.getContainer()
				: null;

			if (
				! container &&
				model &&
				'function' === typeof model.get
			) {
				container = getContainerByModelId( model.get( 'id' ) );
			}

			scheduleSearchCompositionPreviewScroll( container );
			syncSearchFieldPanelTabs( container );
		};

		window.elementor.hooks.addAction( 'panel/open_editor/widget', onPanelOpen );
		window.elementor.hooks.addAction( 'panel/open_editor/container', onPanelOpen );

		searchCompositionSelectionBridgeBound = true;
		return true;
	}

	function isProjectedSearchFieldContainer( container ) {
		container = normalizeContainer( container );

		if ( ! container ) {
			return false;
		}

		const settings = getContainerSettings( container );
		const isSearchField = 'yes' === String(
			getSettingValue( settings, 'directorist_search_context', '' )
		);

		return isSearchField && !! getClosestAncestorWidget( container, SEARCH_COMPOSITION_WIDGETS );
	}

	function syncSearchFieldPanelTabs( container ) {
		container = normalizeContainer( container );
		const isSearchField = isProjectedSearchFieldContainer( container );

		if ( document.body ) {
			document.body.classList.toggle( SEARCH_FIELD_PANEL_BODY_CLASS, isSearchField );
		}

		if ( ! isSearchField ) {
			return;
		}

		window.requestAnimationFrame( function() {
			const selectedContainer = getSelectedEditorContainer();

			if (
				! selectedContainer ||
				getContainerModelId( selectedContainer ) !== getContainerModelId( container ) ||
				! isProjectedSearchFieldContainer( selectedContainer )
			) {
				if ( document.body ) {
					document.body.classList.remove( SEARCH_FIELD_PANEL_BODY_CLASS );
				}
				return;
			}

			const pageView = getCurrentPanelPageView();
			const contentTab = document.querySelector(
				'#elementor-panel-page-editor .elementor-tab-control-content'
			);
			const contentRouteActive = document.body && document.body.classList.contains(
				'e-route-panel-editor-content'
			);

			if (
				pageView &&
				(
					'content' === getPanelEditorActiveTab( pageView ) ||
					contentRouteActive ||
					( contentTab && contentTab.classList.contains( 'elementor-active' ) )
				)
			) {
				const selectedView = getContainerView( selectedContainer );

				if (
					window.$e &&
					'function' === typeof window.$e.route &&
					selectedContainer.model &&
					selectedView
				) {
					window.$e.route( 'panel/editor/style', {
						model: selectedContainer.model,
						view: selectedView,
					} );
				} else if ( 'function' === typeof pageView.activateTab ) {
					pageView.activateTab( 'style' );
					syncPanelEditorActiveTabClass( 'style' );
				}
			}
		} );
	}

	function getChildElementIds( container ) {
		const childElements =
			container &&
			container.model &&
			'function' === typeof container.model.get
				? container.model.get( 'elements' )
				: null;

		if ( ! childElements || 'function' !== typeof childElements.map ) {
			return [];
		}

		return childElements
			.map( ( childModel ) => String( childModel && 'function' === typeof childModel.get ? childModel.get( 'id' ) || '' : '' ) )
			.filter( Boolean );
	}

	function resolveInsertedChildContainer( targetContainer, existingChildIds ) {
		const target = normalizeContainer( targetContainer );
		const childElements =
			target &&
			target.model &&
			'function' === typeof target.model.get
				? target.model.get( 'elements' )
				: null;

		if ( ! childElements || ! childElements.length ) {
			return null;
		}

		const existingIds = Array.isArray( existingChildIds ) ? existingChildIds : [];
		let insertedId = '';

		if ( 'function' === typeof childElements.forEach ) {
			childElements.forEach( ( childModel ) => {
				if ( insertedId || ! childModel || 'function' !== typeof childModel.get ) {
					return;
				}

				const childId = String( childModel.get( 'id' ) || '' );

				if ( childId && -1 === existingIds.indexOf( childId ) ) {
					insertedId = childId;
				}
			} );
		}

		if ( ! insertedId && 'function' === typeof childElements.at ) {
			const lastChildModel = childElements.at( childElements.length - 1 );

			if ( lastChildModel && 'function' === typeof lastChildModel.get ) {
				insertedId = String( lastChildModel.get( 'id' ) || '' );
			}
		}

		return insertedId ? getContainerByModelId( insertedId ) : null;
	}

	function selectInsertedChildWhenReady( targetContainer, existingChildIds, attemptsLeft ) {
		const remainingAttempts = Number.isInteger( attemptsLeft ) ? attemptsLeft : 12;
		const insertedContainer = resolveInsertedChildContainer( targetContainer, existingChildIds );

		if ( insertedContainer ) {
			selectContainer( insertedContainer );
			return;
		}

		if ( remainingAttempts <= 0 ) {
			selectContainer( targetContainer );
			return;
		}

		window.requestAnimationFrame( function() {
			selectInsertedChildWhenReady( targetContainer, existingChildIds, remainingAttempts - 1 );
		} );
	}

	function dispatchElementAddedEvent( modelData ) {
		const eventsManager = window.elementorCommon && window.elementorCommon.eventsManager;

		if ( ! eventsManager || typeof eventsManager.dispatchEvent !== 'function' || ! modelData ) {
			return;
		}

		const elType = modelData.elType || '';
		const widgetType = modelData.widgetType || '';
		const elementName = 'widget' === elType ? widgetType : elType;

		eventsManager.dispatchEvent( 'add_element', {
			location: 'editor_panel',
			element_name: elementName,
			element_type: elType,
			widget_type: widgetType,
		} );
	}

	function dropPanelElementIntoContainer( panelElementView, targetContainer ) {
		targetContainer = normalizeContainer( targetContainer );

		if (
			! panelElementView ||
			! panelElementView.model ||
			! targetContainer ||
			! window.$e ||
			typeof window.$e.run !== 'function'
		) {
			return false;
		}

		const widgetType =
			typeof panelElementView.model.get === 'function'
				? panelElementView.model.get( 'widgetType' )
				: '';

		if (
			window.elementor &&
			window.elementor.helpers &&
			typeof window.elementor.helpers.maybeDisableWidget === 'function' &&
			window.elementor.helpers.maybeDisableWidget( widgetType )
		) {
			return true;
		}

		const custom =
			typeof panelElementView.model.get === 'function'
				? panelElementView.model.get( 'custom' )
				: null;

		if (
			custom &&
			custom.isPreset &&
			custom.preset_settings &&
			typeof panelElementView.model.set === 'function'
		) {
			panelElementView.model.set( 'settings', custom.preset_settings );
		}

		const modelData = buildElementModelWithIds(
			typeof panelElementView.model.toJSON === 'function'
				? panelElementView.model.toJSON()
				: panelElementView.model.attributes || {}
		);
		const existingChildIds = getChildElementIds( targetContainer );

		window.$e.run( 'document/elements/create', {
			container: targetContainer,
			options: {
				scrollIntoView: true,
				edit: true,
				at: existingChildIds.length,
			},
			model: modelData,
		} );

		dispatchElementAddedEvent( modelData );
		selectInsertedChildWhenReady( targetContainer, existingChildIds );

		return true;
	}

	function patchPanelElementAddToPage() {
		if ( panelPatchApplied ) {
			return true;
		}

		const ElementsView =
			window.elementor &&
			window.elementor.modules &&
			window.elementor.modules.layouts &&
			window.elementor.modules.layouts.panel &&
			window.elementor.modules.layouts.panel.pages &&
			window.elementor.modules.layouts.panel.pages.elements &&
			window.elementor.modules.layouts.panel.pages.elements.views
				? window.elementor.modules.layouts.panel.pages.elements.views.Elements
				: null;
		const ElementView =
			ElementsView &&
			ElementsView.prototype &&
			ElementsView.prototype.childView
				? ElementsView.prototype.childView
				: null;

		if (
			! ElementView ||
			! ElementView.prototype ||
			typeof ElementView.prototype.addToPage !== 'function'
		) {
			return false;
		}

		if ( ElementView.prototype.__directoristNestedPatched ) {
			panelPatchApplied = true;

			return true;
		}

		const originalAddToPage = ElementView.prototype.addToPage;

		ElementView.prototype.addToPage = function() {
			const selectedElements =
				typeof this.getSelectedElements === 'function' ? this.getSelectedElements() : [];

			if ( 1 === selectedElements.length ) {
				const selected = selectedElements[ 0 ];
				const selectedContainer =
					selected &&
					selected.view &&
					typeof selected.view.getContainer === 'function'
						? selected.view.getContainer()
						: null;
				const targetContainer = ensureCompositionTarget( selectedContainer );

				if ( targetContainer ) {
					return dropPanelElementIntoContainer( this, targetContainer );
				}
			}

			return originalAddToPage.apply( this, arguments );
		};

		ElementView.prototype.__directoristNestedPatched = true;
		panelPatchApplied = true;

		return true;
	}

	function getProxySelector( widgetType ) {
		if ( PRICING_PLANS_WIDGET === widgetType ) {
			return '.directorist-elementor-pricing-plans, .directorist-elementor-pricing-plans__preview-surface, .directorist-elementor-pricing-plans__tabs-wrap, .directorist-elementor-pricing-plans__grid, .directorist-elementor-pricing-plans__card, .directorist-elementor-pricing-plans__card-inner';
		}

		if ( AUTHOR_PROFILE_WIDGET === widgetType ) {
			return '.directorist-elementor-author-profile, .directorist-elementor-author-profile__preview-surface, .directorist-elementor-author-profile__card, .directorist-elementor-author-profile__header, .directorist-elementor-author-profile__body, .directorist-elementor-author-profile__inner';
		}

		if ( RELATED_LISTINGS_WIDGET === widgetType ) {
			return '.directorist-elementor-related-listings, .directorist-elementor-related-listings__state, .directorist-elementor-related-listings__preview-surface, .directorist-elementor-related-listings__preview-items, .directorist-elementor-related-listings__preview-item';
		}

		if ( isTaxonomyCompositionWidget( widgetType ) ) {
			return '.directorist-elementor-taxonomy-composition, .directorist-elementor-taxonomy-composition__preview-surface, .directorist-elementor-taxonomy-widget, .directorist-elementor-taxonomy-card';
		}

		if ( SEARCH_DIRECTORY_TYPES_WIDGET === widgetType ) {
			return '.directorist-elementor-search-directory-types, .directorist-elementor-listings-search-nav, .directorist-type-nav, .directorist-type-nav__link';
		}

		if ( isSearchCompositionWidget( widgetType ) ) {
			return '.directorist-elementor-search-composition, .directorist-elementor-search-composition__preview-surface, .directorist-elementor-listings-search, .directorist-elementor-search-field, .directorist-elementor-search-submit';
		}

		if ( SINGLE_MAP_WIDGET === widgetType ) {
			return '.directorist-elementor-single-map, .directorist-elementor-single-map__state, .directorist-elementor-single-map__preview-surface, .directorist-elementor-single-map__surface, .directorist-elementor-single-map__popup-card';
		}

		return '.directorist-elementor-card-template, .directorist-elementor-card-template__state, .directorist-elementor-card-template__preview-surface, .directorist-elementor-loop__cards, .directorist-elementor-loop__item, .directorist-elementor-card-template__content';
	}

	function getContainerByModelId( modelId ) {
		if (
			! modelId ||
			! window.elementor ||
			'function' !== typeof window.elementor.getContainer
		) {
			return null;
		}

		return normalizeContainer( window.elementor.getContainer( modelId ) || null );
	}

	function resolvePreviewClickContainer( container, eventTarget ) {
		const widgetType = getContainerType( container );
		const view = getContainerView( container );
		let previewSelector = '.directorist-elementor-card-template__preview-surface';
		const targetElement = eventTarget && 1 === eventTarget.nodeType
			? eventTarget
			: ( eventTarget && eventTarget.parentElement ? eventTarget.parentElement : null );

		if ( RELATED_LISTINGS_WIDGET === widgetType ) {
			previewSelector = '.directorist-elementor-related-listings__preview-surface';
		} else if ( AUTHOR_PROFILE_WIDGET === widgetType ) {
			previewSelector = '.directorist-elementor-author-profile__preview-surface';
		} else if ( isTaxonomyCompositionWidget( widgetType ) ) {
			previewSelector = '.directorist-elementor-taxonomy-composition__preview-surface';
		} else if ( isSearchCompositionWidget( widgetType ) ) {
			previewSelector = '.directorist-elementor-search-composition__preview-surface';
		} else if ( SINGLE_MAP_WIDGET === widgetType ) {
			previewSelector = '.directorist-elementor-single-map__preview-surface';
		}
		const previewSurface =
			view && view.$el
				? view.$el.find( previewSelector ).get( 0 )
				: null;

		if ( ! previewSurface || ! targetElement || ! previewSurface.contains( targetElement ) ) {
			return null;
		}

		let activeCompositionContainer = CARD_TEMPLATE_WIDGET === widgetType
			? container
			: null;

		if ( RELATED_LISTINGS_WIDGET === widgetType ) {
			activeCompositionContainer = getRelatedListingsTemplateContainer( container );
		} else if ( AUTHOR_PROFILE_WIDGET === widgetType ) {
			activeCompositionContainer = getAuthorProfileTemplateContainer( container );
		} else if ( isTaxonomyCompositionWidget( widgetType ) ) {
			activeCompositionContainer = getTaxonomyCompositionTemplateContainer( container );
		} else if ( isSearchCompositionWidget( widgetType ) ) {
			activeCompositionContainer = container;
		} else if ( SINGLE_MAP_WIDGET === widgetType ) {
			activeCompositionContainer = getSingleMapTemplateContainer( container );
		}
		const activeCompositionModelId =
			activeCompositionContainer &&
			activeCompositionContainer.model &&
			'function' === typeof activeCompositionContainer.model.get
				? String( activeCompositionContainer.model.get( 'id' ) || '' )
				: '';
		const previewElement = targetElement.closest( '.elementor-element[data-id]' );

		if ( previewElement && previewSurface.contains( previewElement ) ) {
			const previewModelId = String( previewElement.getAttribute( 'data-id' ) || '' );

			if ( activeCompositionModelId && previewModelId === activeCompositionModelId ) {
				return container;
			}

			return getContainerByModelId( previewModelId );
		}

		return container;
	}

	function bindCardTemplatePreviewSurfaceClick( container ) {
		if ( CARD_TEMPLATE_WIDGET !== getContainerType( container ) ) {
			return;
		}

		const view = getContainerView( container );
		const surface = view && view.$el
			? view.$el.find( '.directorist-elementor-card-template__preview-surface' ).get( 0 )
			: null;

		if ( ! surface || surface.__directoristCardTemplatePreviewCaptureBound ) {
			return;
		}

		surface.__directoristCardTemplatePreviewCaptureBound = true;
		surface.addEventListener(
			'click',
			function( event ) {
				const previewClickContainer = resolvePreviewClickContainer( container, event.target );

				if ( ! previewClickContainer ) {
					return;
				}

				event.preventDefault();
				event.stopPropagation();

				if ( 'function' === typeof event.stopImmediatePropagation ) {
					event.stopImmediatePropagation();
				}

					selectContainer( previewClickContainer );
				},
				true
			);
	}

	function bindSearchCompositionPreviewSurfaceClick( container ) {
		if ( ! isSearchCompositionWidget( getContainerType( container ) ) ) {
			return;
		}

		const view = getContainerView( container );
		const surface = view && view.$el
			? view.$el.find( '.directorist-elementor-search-composition__preview-surface' ).get( 0 )
			: null;

		if ( ! surface || surface.__directoristSearchCompositionPreviewCaptureBound ) {
			return;
		}

		surface.__directoristSearchCompositionPreviewCaptureBound = true;
		surface.addEventListener(
			'click',
			function( event ) {
				const directoryLink = event.target && event.target.closest
					? event.target.closest( '.directorist-type-nav__link, .search_listing_types' )
					: null;

				if (
					directoryLink &&
					HOME_SEARCH_WIDGET === getContainerType( container ) &&
					! getLoopEditingState( container )
				) {
					const directoryTypeId = toInt(
						directoryLink.getAttribute( 'data-listing_type_id' ) ||
						directoryLink.getAttribute( 'data-directory-type-id' ) ||
						directoryLink.getAttribute( 'data-directory_type' ) ||
						0
					);

					if ( directoryTypeId > 0 ) {
						event.preventDefault();
						event.stopPropagation();

						if ( 'function' === typeof event.stopImmediatePropagation ) {
							event.stopImmediatePropagation();
						}

						setSettingValue(
							container,
							'standalone_design_directory_type_id',
							String( directoryTypeId )
						);
						syncSearchCompositionEditorState( container );
						queuePreviewRequest(
							`search:${ container.id || '' }`,
							function() {
								requestSearchCompositionPreview( container );
							},
							20
						);
						return;
					}
				}

				const previewClickContainer = resolvePreviewClickContainer( container, event.target );

				if ( ! previewClickContainer ) {
					return;
				}

				event.preventDefault();
				event.stopPropagation();

				if ( 'function' === typeof event.stopImmediatePropagation ) {
					event.stopImmediatePropagation();
				}

				selectContainer( previewClickContainer );
			},
			true
		);
	}

	function createLoopElementViewClass( BaseView, loopWidgetType ) {
		loopWidgetType = loopWidgetType || LOOP_WIDGET;

		if ( viewCache[ `loop:${ loopWidgetType }` ] ) {
			return viewCache[ `loop:${ loopWidgetType }` ];
		}

		viewCache[ `loop:${ loopWidgetType }` ] = BaseView.extend( {
			getTemplate: function() {
				return Marionette.TemplateCache.get( `#tmpl-elementor-${ loopWidgetType }-content` );
			},

			initialize: function() {
				if ( 'function' === typeof BaseView.prototype.initialize ) {
					BaseView.prototype.initialize.apply( this, arguments );
				}

				if (
					this.model &&
					'function' === typeof this.model.get &&
					this.model.get( 'editSettings' ) &&
					'function' === typeof this.model.get( 'editSettings' ).set
				) {
					this.model.get( 'editSettings' ).set( 'defaultEditRoute', 'content' );
				}

				const settings = this.model.get( 'settings' );
				const elements = this.model.get( 'elements' );

				if ( settings ) {
					this.listenTo(
						settings,
						'change:active_directory_type_id change:active_view_type change:directory_type_ids change:default_directory_type_id change:view_type change:map_list_view_type change:columns change:columns_tablet change:columns_mobile change:listings_per_page change:pagination_type change:display_mode change:slides_per_view change:slides_per_view_tablet change:slides_per_view_mobile change:show_arrow_navigation change:show_dot_navigation change:slider_autoplay change:slider_effect change:slider_grid_rows change:pause_on_hover change:autoplay_delay change:transition_speed change:map_list_map_position change:map_list_map_height change:map_list_map_width change:map_list_sticky_map change:map_list_fit_on_load change:map_list_hover_focus change:map_list_hover_zoom change:map_list_show_map_card',
						this.onDirectoristLoopStateChange
					);
					this.listenTo(
						settings,
						'change:card_gap change:grid_card_gap change:grid_card_gap_tablet change:grid_card_gap_mobile change:list_card_gap change:list_card_gap_tablet change:list_card_gap_mobile change:slider_card_gap change:slider_card_gap_tablet change:slider_card_gap_mobile change:map_list_card_gap change:map_list_card_gap_tablet change:map_list_card_gap_mobile change:map_list_pane_gap change:map_list_pane_gap_tablet change:map_list_pane_gap_mobile',
						this.onDirectoristLoopSpacingChange
					);
				}

				if ( elements ) {
					this.listenTo(
						elements,
						'add remove sort reset update',
						this.onDirectoristLoopStateChange
					);
				}

				bindLoopDescendantBindings( this );
			},

			events: function() {
				const parentEvents =
					'function' === typeof BaseView.prototype.events
						? BaseView.prototype.events.apply( this, arguments )
						: ( BaseView.prototype.events || {} );

				return $.extend( {}, parentEvents, {
					'click .directorist-elementor-loop__scope-button--directory': 'onDirectoristLoopDirectoryClick',
					'click .directorist-elementor-loop__scope-button--view': 'onDirectoristLoopViewClick',
				} );
			},

			getChildViewContainer: function() {
				const selector = this.isBoxedWidth()
					? '> .e-con-inner > .directorist-elementor-loop__inner'
					: '> .directorist-elementor-loop__inner';

				return this.$el.find( selector );
			},

			getCorrectContainerElement: function() {
				return this.getChildViewContainer();
			},

			getDroppableOptions: function() {
				const options =
					'function' === typeof BaseView.prototype.getDroppableOptions
						? BaseView.prototype.getDroppableOptions.apply( this, arguments )
						: {};

				options.items = '> .elementor-element, > .elementor-empty-view .elementor-first-add';

				return options;
			},

			droppableDestroy: function() {
				const $container = this.getCorrectContainerElement();

				if ( $container && $container.length ) {
					$container.html5Droppable( 'destroy' );
				}
			},

			droppableInitialize: function() {
				const $container = this.getCorrectContainerElement();

				if ( $container && $container.length ) {
					$container.html5Droppable( this.getDroppableOptions() );
				}
			},

			onDirectoristLoopStateChange: function() {
				const container = this.getContainer();

				if ( loopScopeNormalizationLocks.has( getLoopEditorStateKey( container ) ) ) {
					return;
				}

				bindLoopDescendantBindings( this );
				syncLoopEditorState( container );
			},

			onDirectoristLoopDescendantSettingsChange: function() {
				syncLoopEditorState( this.getContainer() );
			},

			onDirectoristLoopSpacingChange: function() {
				const container = this.getContainer();
				const settings = getContainerSettings( container );
				const gaps = resolveLoopGapValues( settings );

				applyLoopEditorRootState( container );
				syncEditorDeviceModeForContainer( container );

				if ( 'slider' === resolveLoopDisplayMode( settings, resolveLoopPreviewView( settings ) ) ) {
					syncLoopSliderGapState( container, gaps );
				}
			},

			onDirectoristLoopDescendantStructureChange: function() {
				bindLoopDescendantBindings( this );
				syncLoopEditorState( this.getContainer() );
			},

			onDirectoristLoopDirectoryClick: function( event ) {
				event.preventDefault();
				event.stopPropagation();

				persistLoopDescendantCardTemplatesBeforeScopeChange( this.getContainer() );
				setSettingValue(
					this.getContainer(),
					'active_directory_type_id',
					String( $( event.currentTarget ).attr( 'data-direl-loop-directory' ) || '0' )
				);
			},

			onDirectoristLoopViewClick: function( event ) {
				event.preventDefault();
				event.stopPropagation();

				const nextView = String( $( event.currentTarget ).attr( 'data-direl-loop-view' ) || 'grid' );

				persistLoopDescendantCardTemplatesBeforeScopeChange( this.getContainer() );
				setSettingValue(
					this.getContainer(),
					'active_view_type',
					nextView
				);
			},

			onRender: function() {
				if ( 'function' === typeof BaseView.prototype.onRender ) {
					BaseView.prototype.onRender.apply( this, arguments );
				}

				applyLoopEditorRootState( this.getContainer() );
				bindLoopDescendantBindings( this );
				ensureLoopContentTabActive( this );
				syncLoopEditorState( this.getContainer() );
			},

			renderOnChange: function() {
				if ( 'function' === typeof BaseView.prototype.renderOnChange ) {
					BaseView.prototype.renderOnChange.apply( this, arguments );
				}

				applyLoopEditorRootState( this.getContainer() );
				bindLoopDescendantBindings( this );
				ensureLoopContentTabActive( this );
				syncLoopEditorState( this.getContainer() );
			},

			onBeforeDestroy: function() {
				clearLoopDescendantBindings( this );

				if ( 'function' === typeof BaseView.prototype.onBeforeDestroy ) {
					BaseView.prototype.onBeforeDestroy.apply( this, arguments );
				}
			},
		} );

		return viewCache[ `loop:${ loopWidgetType }` ];
	}

	function createNestedViewClass( BaseView ) {
		const selector = getProxySelector( CARD_TEMPLATE_WIDGET );

		return BaseView.extend( {
			getTemplate: function() {
				return Marionette.TemplateCache.get( `#tmpl-elementor-${ CARD_TEMPLATE_WIDGET }-content` );
			},

			getChildViewContainer: function() {
				const selector = this.isBoxedWidth && this.isBoxedWidth()
					? '> .e-con-inner > .directorist-elementor-card-template > .directorist-elementor-card-template__content--editor'
					: '> .directorist-elementor-card-template > .directorist-elementor-card-template__content--editor';

				const $container = this.$el.find( selector );

				return $container.length
					? $container
					: this.$el.find( '.directorist-elementor-card-template__content--editor' ).first();
			},

			getCorrectContainerElement: function() {
				return this.getChildViewContainer();
			},

			getDroppableOptions: function() {
				const options =
					'function' === typeof BaseView.prototype.getDroppableOptions
						? BaseView.prototype.getDroppableOptions.apply( this, arguments )
						: {};

				options.items = '> .elementor-element, > .elementor-empty-view .elementor-first-add';

				return options;
			},

			droppableDestroy: function() {
				const $container = this.getCorrectContainerElement();

				if ( $container && $container.length ) {
					$container.html5Droppable( 'destroy' );
				}
			},

			droppableInitialize: function() {
				const $container = this.getCorrectContainerElement();

				if ( $container && $container.length ) {
					$container.html5Droppable( this.getDroppableOptions() );
				}
			},

			initialize: function() {
				if ( 'function' === typeof BaseView.prototype.initialize ) {
					BaseView.prototype.initialize.apply( this, arguments );
				}

				const settings = this.model.get( 'settings' );
				const elements = this.model.get( 'elements' );

				if ( settings ) {
					this.listenTo(
						settings,
						'change',
						this.onDirectoristCardTemplateStateChange
					);
				}

				if ( elements ) {
					this.listenTo(
						elements,
						'add remove sort reset update',
						this.onDirectoristCardTemplateStructureChange
					);
				}

				bindCardTemplateDescendantBindings( this );
			},

			getTemplateType: function() {
				return 'js';
			},

			events: function() {
				const parentEvents =
					'function' === typeof BaseView.prototype.events
						? BaseView.prototype.events.apply( this, arguments )
						: ( BaseView.prototype.events || {} );

				return $.extend( {}, parentEvents, {
					[ `click ${ selector }` ]: 'onDirectoristCompositionClick',
				} );
			},

			onDirectoristCompositionClick: function( event ) {
				const previewClickContainer = resolvePreviewClickContainer( this.getContainer(), event.target );

				if ( previewClickContainer ) {
					event.preventDefault();
					event.stopPropagation();
					selectContainer( previewClickContainer );
					return;
				}

				const targetContainer = ensureCompositionTarget( this.getContainer() );

				if ( ! targetContainer ) {
					return;
				}

				event.preventDefault();
				event.stopPropagation();

				selectContainer( targetContainer );
			},

			onDirectoristCardTemplateStateChange: function() {
				const settings = getContainerSettings( this.getContainer() );
				const changedKeys = settings && settings.changed
					? Object.keys( settings.changed )
					: [];
				const hasWrapperChange = changedKeys.some( isCardTemplateScopedSettingKey ) || hasCardWrapperGlobalChange( settings, changedKeys );
				const settingsLockKey = getContainerLockKey( this.getContainer(), 'card-template-settings' );

				if ( isOnlyCardTemplateStorageChange( changedKeys ) ) {
					if ( -1 !== changedKeys.indexOf( 'active_template_key' ) ) {
						syncCardTemplateEditorState( this.getContainer(), { skipScope: true } );
					}

					return;
				}

				if ( hasWrapperChange ) {
					if ( cardTemplateSettingsLocks.has( settingsLockKey ) ) {
						cardTemplateLockedWrapperChanges.add( settingsLockKey );
						applyCardTemplateSettingsToEditor( this.getContainer() );
						scheduleCardTemplateSettingsLockFlush( this.getContainer(), 80 );
						return;
					}

					persistCardTemplateActiveScope( this.getContainer(), null, { force: true } );
					applyCardTemplateSettingsToEditor( this.getContainer() );
					queuePreviewRequest(
						`card:${ this.getContainer().id || '' }`,
						() => requestCardTemplatePreview( this.getContainer() ),
						80
					);
					return;
				}

				bindCardTemplateDescendantBindings( this );
				syncCardTemplateEditorState( this.getContainer() );
			},

			onDirectoristCardTemplateStructureChange: function() {
				bindCardTemplateDescendantBindings( this );
				scheduleCardTemplateActiveScopePersist( this.getContainer(), 0, { force: true } );
				syncCardTemplateEditorState( this.getContainer(), { skipScope: true } );
			},

			onDirectoristCardTemplateDescendantSettingsChange: function() {
				scheduleCardTemplateActiveScopePersist( this.getContainer(), 120, { force: true } );
				queuePreviewRequest(
					`card:${ this.getContainer().id || '' }`,
					() => requestCardTemplatePreview( this.getContainer() ),
					80
				);
			},

			onDirectoristCardTemplateDescendantStructureChange: function() {
				bindCardTemplateDescendantBindings( this );
				scheduleCardTemplateActiveScopePersist( this.getContainer(), 0, { force: true } );
				queuePreviewRequest(
					`card:${ this.getContainer().id || '' }`,
					() => requestCardTemplatePreview( this.getContainer() ),
					80
				);
			},

			renderHTML: function() {
				const templateType = this.getTemplateType();
				const editModel = this.getEditModel();

				if ( 'js' === templateType ) {
					editModel.setHtmlCache();
					this.render();
					bindCardTemplateDescendantBindings( this );
					syncCardTemplateEditorState( this.getContainer() );
					queuePreviewRequest(
						`card:${ this.getContainer().id || '' }`,
						() => requestCardTemplatePreview( this.getContainer() )
					);
					return;
				}
			},

			onRender: function() {
				if ( 'function' === typeof BaseView.prototype.onRender ) {
					BaseView.prototype.onRender.apply( this, arguments );
				}

				bindCardTemplateDescendantBindings( this );
				syncCardTemplateEditorState( this.getContainer() );
				queuePreviewRequest(
					`card:${ this.getContainer().id || '' }`,
					() => requestCardTemplatePreview( this.getContainer() )
				);
			},

			onBeforeDestroy: function() {
				clearCardTemplateDescendantBindings( this );
				cardTemplateProjectionStates.delete(
					getContainerLockKey( this.getContainer(), 'card-template-scope' )
				);

				if ( 'function' === typeof BaseView.prototype.onBeforeDestroy ) {
					BaseView.prototype.onBeforeDestroy.apply( this, arguments );
				}
			},
		} );
	}

	function createPricingPlansViewClass( BaseView ) {
		if ( viewCache[ `pricing:${ PRICING_PLANS_WIDGET }` ] ) {
			return viewCache[ `pricing:${ PRICING_PLANS_WIDGET }` ];
		}

		const selector = getProxySelector( PRICING_PLANS_WIDGET );

		viewCache[ `pricing:${ PRICING_PLANS_WIDGET }` ] = BaseView.extend( {
			getTemplate: function() {
				return Marionette.TemplateCache.get( `#tmpl-elementor-${ PRICING_PLANS_WIDGET }-content` );
			},

			initialize: function() {
				if ( 'function' === typeof BaseView.prototype.initialize ) {
					BaseView.prototype.initialize.apply( this, arguments );
				}

				if (
					this.model &&
					'function' === typeof this.model.get &&
					this.model.get( 'editSettings' ) &&
					'function' === typeof this.model.get( 'editSettings' ).set
				) {
					this.model.get( 'editSettings' ).set( 'defaultEditRoute', 'content' );
				}

				const settings = this.model.get( 'settings' );
				const elements = this.model.get( 'elements' );

				if ( settings ) {
					this.listenTo(
						settings,
						'change:plans change:columns change:columns_tablet change:columns_mobile change:gap change:gap_tablet change:gap_mobile change:enable_duration_tabs change:tab_type change:default_duration_key change:default_package_type_key change:default_tab_key',
						this.onDirectoristPricingPlansStateChange
					);
				}

				if ( elements ) {
					this.listenTo(
						elements,
						'add remove sort reset update',
						this.onDirectoristPricingPlansStateChange
					);
				}

				bindPricingPlansRepeaterBindings( this );
				bindPricingPlanDescendantBindings( this );
			},

			getChildViewContainer: function() {
				const selector = this.isBoxedWidth()
					? '> .e-con-inner > .directorist-elementor-pricing-plans > .directorist-elementor-pricing-plans__storage'
					: '> .directorist-elementor-pricing-plans > .directorist-elementor-pricing-plans__storage';

				const $container = this.$el.find( selector );

				return $container.length
					? $container
					: this.$el.find( '.directorist-elementor-pricing-plans__storage' ).first();
			},

			getCorrectContainerElement: function() {
				return this.getChildViewContainer();
			},

			getDroppableOptions: function() {
				const options =
					'function' === typeof BaseView.prototype.getDroppableOptions
						? BaseView.prototype.getDroppableOptions.apply( this, arguments )
						: {};

				options.items = '> .elementor-element, > .elementor-empty-view .elementor-first-add';

				return options;
			},

			droppableDestroy: function() {
				const $container = this.getCorrectContainerElement();

				if ( $container && $container.length ) {
					$container.html5Droppable( 'destroy' );
				}
			},

			droppableInitialize: function() {
				const $container = this.getCorrectContainerElement();

				if ( $container && $container.length ) {
					$container.html5Droppable( this.getDroppableOptions() );
				}
			},

			events: function() {
				const parentEvents =
					'function' === typeof BaseView.prototype.events
						? BaseView.prototype.events.apply( this, arguments )
						: ( BaseView.prototype.events || {} );

				return $.extend( {}, parentEvents, {
					[ `click ${ selector }` ]: 'onDirectoristPricingPlansClick',
				} );
			},

			onDirectoristPricingPlansClick: function( event ) {
				const container = this.getContainer();
				const view = getContainerView( container );
				const root = view && view.$el
					? view.$el.find( '.directorist-elementor-pricing-plans' ).get( 0 )
					: null;
				const clickedElement = event.target && 'function' === typeof event.target.closest
					? event.target
					: null;
				const tabButton = clickedElement
					? clickedElement.closest( '.directorist-elementor-pricing-plans__duration-tab' )
					: null;
				const elementTarget = clickedElement
					? clickedElement.closest( '.elementor-element[data-id]' )
					: null;
				const card = clickedElement
					? clickedElement.closest( '.directorist-elementor-pricing-plans__card' )
					: null;
				const previewSurface = root
					? root.querySelector( '.directorist-elementor-pricing-plans__preview-surface' )
					: null;

				if ( tabButton ) {
					selectContainer( container );
					return;
				}

				if ( previewSurface && clickedElement && previewSurface.contains( clickedElement ) && elementTarget && previewSurface.contains( elementTarget ) ) {
					const targetContainer = getContainerByModelId( elementTarget.getAttribute( 'data-id' ) || '' );

					if ( targetContainer && targetContainer !== container ) {
						event.preventDefault();
						event.stopPropagation();
						selectContainer( targetContainer );
						return;
					}
				}

				if ( previewSurface && clickedElement && previewSurface.contains( clickedElement ) && card && previewSurface.contains( card ) ) {
					const rawIndex = Number.parseInt( card.getAttribute( 'data-direl-plan-index' ) || '-1', 10 );
					const childContainer = rawIndex >= 0 ? getChildContainerAtIndex( container, rawIndex ) : null;

					if ( childContainer ) {
						event.preventDefault();
						event.stopPropagation();
						selectContainer( childContainer );
						return;
					}
				}

				event.preventDefault();
				event.stopPropagation();
				selectContainer( container );
			},

			scheduleDirectoristPricingPlansRefresh: function() {
				if ( this.__directoristPricingPlansRefreshFrame ) {
					return;
				}

				this.__directoristPricingPlansRefreshFrame = window.requestAnimationFrame( () => {
					this.__directoristPricingPlansRefreshFrame = null;
					refreshPricingPlanEditorState( this.getContainer() );
				} );
			},

			onDirectoristPricingPlansStateChange: function() {
				bindPricingPlansRepeaterBindings( this );
				bindPricingPlanDescendantBindings( this );
				this.scheduleDirectoristPricingPlansRefresh();
			},

			onDirectoristPricingPlansDescendantSettingsChange: function() {
				queuePreviewRequest(
					`pricing:${ this.getContainer().id || '' }`,
					() => requestPricingPlansPreview( this.getContainer() ),
					60
				);
			},

			onDirectoristPricingPlansDescendantStructureChange: function() {
				bindPricingPlanDescendantBindings( this );
				this.scheduleDirectoristPricingPlansRefresh();
			},

			onRender: function() {
				if ( 'function' === typeof BaseView.prototype.onRender ) {
					BaseView.prototype.onRender.apply( this, arguments );
				}

				bindPricingPlanDescendantBindings( this );
				this.scheduleDirectoristPricingPlansRefresh();
			},

			renderOnChange: function() {
				if ( 'function' === typeof BaseView.prototype.renderOnChange ) {
					BaseView.prototype.renderOnChange.apply( this, arguments );
				}

				bindPricingPlanDescendantBindings( this );
				this.scheduleDirectoristPricingPlansRefresh();
			},

			onBeforeDestroy: function() {
				if ( this.__directoristPricingPlansRefreshFrame ) {
					window.cancelAnimationFrame( this.__directoristPricingPlansRefreshFrame );
					this.__directoristPricingPlansRefreshFrame = null;
				}

				clearCardTemplateDescendantBindings( this );
				this.__directoristPricingPlansRepeater = null;

				if ( 'function' === typeof BaseView.prototype.onBeforeDestroy ) {
					BaseView.prototype.onBeforeDestroy.apply( this, arguments );
				}
			},
		} );

		return viewCache[ `pricing:${ PRICING_PLANS_WIDGET }` ];
	}

	function createAuthorProfileViewClass( BaseView ) {
		if ( viewCache[ `author:${ AUTHOR_PROFILE_WIDGET }` ] ) {
			return viewCache[ `author:${ AUTHOR_PROFILE_WIDGET }` ];
		}

		const selector = getProxySelector( AUTHOR_PROFILE_WIDGET );

		viewCache[ `author:${ AUTHOR_PROFILE_WIDGET }` ] = BaseView.extend( {
			getTemplate: function() {
				return Marionette.TemplateCache.get( `#tmpl-elementor-${ AUTHOR_PROFILE_WIDGET }-content` );
			},

			initialize: function() {
				if ( 'function' === typeof BaseView.prototype.initialize ) {
					BaseView.prototype.initialize.apply( this, arguments );
				}

				if (
					this.model &&
					'function' === typeof this.model.get &&
					this.model.get( 'editSettings' ) &&
					'function' === typeof this.model.get( 'editSettings' ).set
				) {
					this.model.get( 'editSettings' ).set( 'defaultEditRoute', 'content' );
				}

				const settings = this.model.get( 'settings' );
				const elements = this.model.get( 'elements' );

				if ( settings ) {
					this.listenTo(
						settings,
						'change:preview_listing_id change:preview_author_id change:show_header change:section_title change:display_email',
						this.onDirectoristAuthorProfileStateChange
					);
				}

				if ( elements ) {
					this.listenTo(
						elements,
						'add remove sort reset update',
						this.onDirectoristAuthorProfileStateChange
					);
				}

				bindCardTemplateDescendantBindings( this );
			},

			getChildViewContainer: function() {
				const selector = this.isBoxedWidth()
					? '> .e-con-inner > .directorist-elementor-author-profile > .directorist-elementor-author-profile__storage'
					: '> .directorist-elementor-author-profile > .directorist-elementor-author-profile__storage';

				const $container = this.$el.find( selector );

				return $container.length
					? $container
					: this.$el.find( '.directorist-elementor-author-profile__storage' ).first();
			},

			getCorrectContainerElement: function() {
				return this.getChildViewContainer();
			},

			getDroppableOptions: function() {
				const options =
					'function' === typeof BaseView.prototype.getDroppableOptions
						? BaseView.prototype.getDroppableOptions.apply( this, arguments )
						: {};

				options.items = '> .elementor-element, > .elementor-empty-view .elementor-first-add';

				return options;
			},

			droppableDestroy: function() {
				const $container = this.getCorrectContainerElement();

				if ( $container && $container.length ) {
					$container.html5Droppable( 'destroy' );
				}
			},

			droppableInitialize: function() {
				const $container = this.getCorrectContainerElement();

				if ( $container && $container.length ) {
					$container.html5Droppable( this.getDroppableOptions() );
				}
			},

			events: function() {
				const parentEvents =
					'function' === typeof BaseView.prototype.events
						? BaseView.prototype.events.apply( this, arguments )
						: ( BaseView.prototype.events || {} );

				return $.extend( {}, parentEvents, {
					[ `click ${ selector }` ]: 'onDirectoristAuthorProfileClick',
				} );
			},

			onDirectoristAuthorProfileClick: function( event ) {
				const previewClickContainer = resolvePreviewClickContainer( this.getContainer(), event.target );

				if ( previewClickContainer ) {
					event.preventDefault();
					event.stopPropagation();
					selectContainer( previewClickContainer );
					return;
				}

				const targetContainer = ensureCompositionTarget( this.getContainer() );

				if ( targetContainer ) {
					event.preventDefault();
					event.stopPropagation();
					selectContainer( targetContainer );
					return;
				}

				event.preventDefault();
				event.stopPropagation();
				selectContainer( this.getContainer() );
			},

			scheduleDirectoristAuthorProfileRefresh: function() {
				if ( this.__directoristAuthorProfileRefreshFrame ) {
					return;
				}

				this.__directoristAuthorProfileRefreshFrame = window.requestAnimationFrame( () => {
					this.__directoristAuthorProfileRefreshFrame = null;
					refreshAuthorProfileEditorState( this.getContainer() );
				} );
			},

			onDirectoristAuthorProfileStateChange: function() {
				bindCardTemplateDescendantBindings( this );
				this.scheduleDirectoristAuthorProfileRefresh();
			},

			onDirectoristCardTemplateDescendantSettingsChange: function() {
				queuePreviewRequest(
					`author-profile:${ this.getContainer().id || '' }`,
					() => requestAuthorProfilePreview( this.getContainer() ),
					60
				);
			},

			onDirectoristCardTemplateDescendantStructureChange: function() {
				bindCardTemplateDescendantBindings( this );
				this.scheduleDirectoristAuthorProfileRefresh();
			},

			onRender: function() {
				if ( 'function' === typeof BaseView.prototype.onRender ) {
					BaseView.prototype.onRender.apply( this, arguments );
				}

				bindCardTemplateDescendantBindings( this );
				this.scheduleDirectoristAuthorProfileRefresh();
			},

			renderOnChange: function() {
				if ( 'function' === typeof BaseView.prototype.renderOnChange ) {
					BaseView.prototype.renderOnChange.apply( this, arguments );
				}

				bindCardTemplateDescendantBindings( this );
				this.scheduleDirectoristAuthorProfileRefresh();
			},

			onBeforeDestroy: function() {
				if ( this.__directoristAuthorProfileRefreshFrame ) {
					window.cancelAnimationFrame( this.__directoristAuthorProfileRefreshFrame );
					this.__directoristAuthorProfileRefreshFrame = null;
				}

				clearCardTemplateDescendantBindings( this );

				if ( 'function' === typeof BaseView.prototype.onBeforeDestroy ) {
					BaseView.prototype.onBeforeDestroy.apply( this, arguments );
				}
			},
		} );

		return viewCache[ `author:${ AUTHOR_PROFILE_WIDGET }` ];
	}

	function createRelatedListingsViewClass( BaseView ) {
		const selector = getProxySelector( RELATED_LISTINGS_WIDGET );

		return BaseView.extend( {
			initialize: function() {
				if ( 'function' === typeof BaseView.prototype.initialize ) {
					BaseView.prototype.initialize.apply( this, arguments );
				}

				const settings = this.model.get( 'settings' );
				const elements = this.model.get( 'elements' );

				if ( settings ) {
					this.listenTo(
						settings,
						'change',
						this.onDirectoristRelatedListingsStateChange
					);
				}

				if ( elements ) {
					this.listenTo(
						elements,
						'add remove sort reset update',
						this.onDirectoristRelatedListingsStateChange
					);
				}

				bindCardTemplateDescendantBindings( this );
			},

			getTemplateType: function() {
				return 'js';
			},

			events: function() {
				const parentEvents =
					'function' === typeof BaseView.prototype.events
						? BaseView.prototype.events.apply( this, arguments )
						: ( BaseView.prototype.events || {} );

				return $.extend( {}, parentEvents, {
					[ `click ${ selector }` ]: 'onDirectoristCompositionClick',
				} );
			},

			onDirectoristCompositionClick: function( event ) {
				const previewClickContainer = resolvePreviewClickContainer( this.getContainer(), event.target );

				if ( previewClickContainer ) {
					event.preventDefault();
					event.stopPropagation();
					selectContainer( previewClickContainer );
					return;
				}

				const targetContainer = ensureCompositionTarget( this.getContainer() );

				if ( ! targetContainer ) {
					return;
				}

				event.preventDefault();
				event.stopPropagation();

				selectContainer( targetContainer );
			},

			onDirectoristRelatedListingsStateChange: function() {
				bindCardTemplateDescendantBindings( this );
				syncRelatedListingsEditorState( this.getContainer() );
				queuePreviewRequest(
					`related:${ this.getContainer().id || '' }`,
					() => requestRelatedListingsPreview( this.getContainer() )
				);
			},

			onDirectoristCardTemplateDescendantSettingsChange: function() {
				queuePreviewRequest(
					`related:${ this.getContainer().id || '' }`,
					() => requestRelatedListingsPreview( this.getContainer() ),
					60
				);
			},

			onDirectoristCardTemplateDescendantStructureChange: function() {
				bindCardTemplateDescendantBindings( this );
				queuePreviewRequest(
					`related:${ this.getContainer().id || '' }`,
					() => requestRelatedListingsPreview( this.getContainer() ),
					40
				);
			},

			renderHTML: function() {
				const templateType = this.getTemplateType();
				const editModel = this.getEditModel();

				if ( 'js' === templateType ) {
					editModel.setHtmlCache();
					this.render();
					bindCardTemplateDescendantBindings( this );
					syncRelatedListingsEditorState( this.getContainer() );
					queuePreviewRequest(
						`related:${ this.getContainer().id || '' }`,
						() => requestRelatedListingsPreview( this.getContainer() )
					);
					return;
				}
			},

			onRender: function() {
				if ( 'function' === typeof BaseView.prototype.onRender ) {
					BaseView.prototype.onRender.apply( this, arguments );
				}

				bindCardTemplateDescendantBindings( this );
				syncRelatedListingsEditorState( this.getContainer() );
				queuePreviewRequest(
					`related:${ this.getContainer().id || '' }`,
					() => requestRelatedListingsPreview( this.getContainer() )
				);
			},

			onBeforeDestroy: function() {
				clearCardTemplateDescendantBindings( this );

				if ( 'function' === typeof BaseView.prototype.onBeforeDestroy ) {
					BaseView.prototype.onBeforeDestroy.apply( this, arguments );
				}
			},
		} );
	}

	function createTaxonomyCompositionViewClass( BaseView, widgetType ) {
		const selector = getProxySelector( widgetType );

		return BaseView.extend( {
			initialize: function() {
				if ( 'function' === typeof BaseView.prototype.initialize ) {
					BaseView.prototype.initialize.apply( this, arguments );
				}

				const settings = this.model.get( 'settings' );
				const elements = this.model.get( 'elements' );

				if ( settings ) {
					this.listenTo(
						settings,
						'change',
						this.onDirectoristTaxonomyCompositionStateChange
					);
				}

				if ( elements ) {
					this.listenTo(
						elements,
						'add remove sort reset update',
						this.onDirectoristTaxonomyCompositionStateChange
					);
				}

				bindCardTemplateDescendantBindings( this );
			},

			getTemplateType: function() {
				return 'js';
			},

			events: function() {
				const parentEvents =
					'function' === typeof BaseView.prototype.events
						? BaseView.prototype.events.apply( this, arguments )
						: ( BaseView.prototype.events || {} );

				return $.extend( {}, parentEvents, {
					[ `click ${ selector }` ]: 'onDirectoristCompositionClick',
				} );
			},

			onDirectoristCompositionClick: function( event ) {
				const previewClickContainer = resolvePreviewClickContainer( this.getContainer(), event.target );

				if ( previewClickContainer ) {
					event.preventDefault();
					event.stopPropagation();
					selectContainer( previewClickContainer );
					return;
				}

				const targetContainer = ensureCompositionTarget( this.getContainer() );

				if ( ! targetContainer ) {
					return;
				}

				event.preventDefault();
				event.stopPropagation();

				selectContainer( targetContainer );
			},

			onDirectoristTaxonomyCompositionStateChange: function() {
				bindCardTemplateDescendantBindings( this );
				syncTaxonomyCompositionEditorState( this.getContainer() );
				queuePreviewRequest(
					`taxonomy:${ this.getContainer().id || '' }`,
					() => requestTaxonomyCompositionPreview( this.getContainer() )
				);
			},

			onDirectoristCardTemplateDescendantSettingsChange: function() {
				queuePreviewRequest(
					`taxonomy:${ this.getContainer().id || '' }`,
					() => requestTaxonomyCompositionPreview( this.getContainer() ),
					60
				);
			},

			onDirectoristCardTemplateDescendantStructureChange: function() {
				bindCardTemplateDescendantBindings( this );
				queuePreviewRequest(
					`taxonomy:${ this.getContainer().id || '' }`,
					() => requestTaxonomyCompositionPreview( this.getContainer() ),
					40
				);
			},

			renderHTML: function() {
				const templateType = this.getTemplateType();
				const editModel = this.getEditModel();

				if ( 'js' === templateType ) {
					editModel.setHtmlCache();
					this.render();
					bindCardTemplateDescendantBindings( this );
					syncTaxonomyCompositionEditorState( this.getContainer() );
					queuePreviewRequest(
						`taxonomy:${ this.getContainer().id || '' }`,
						() => requestTaxonomyCompositionPreview( this.getContainer() )
					);
					return;
				}
			},

			onRender: function() {
				if ( 'function' === typeof BaseView.prototype.onRender ) {
					BaseView.prototype.onRender.apply( this, arguments );
				}

				bindCardTemplateDescendantBindings( this );
				syncTaxonomyCompositionEditorState( this.getContainer() );
				queuePreviewRequest(
					`taxonomy:${ this.getContainer().id || '' }`,
					() => requestTaxonomyCompositionPreview( this.getContainer() )
				);
			},

			onBeforeDestroy: function() {
				clearCardTemplateDescendantBindings( this );

				if ( 'function' === typeof BaseView.prototype.onBeforeDestroy ) {
					BaseView.prototype.onBeforeDestroy.apply( this, arguments );
				}
				},
			} );
		}

	function createSearchCompositionViewClass( BaseView, widgetType ) {
		if ( viewCache[ `search:${ widgetType }` ] ) {
			return viewCache[ `search:${ widgetType }` ];
		}

		const selector = getProxySelector( widgetType );

		viewCache[ `search:${ widgetType }` ] = BaseView.extend( {
				getTemplate: function() {
					return Marionette.TemplateCache.get( `#tmpl-elementor-${ widgetType }-content` );
				},

				initialize: function() {
					if ( 'function' === typeof BaseView.prototype.initialize ) {
						BaseView.prototype.initialize.apply( this, arguments );
					}

					if (
						this.model &&
						'function' === typeof this.model.get &&
						this.model.get( 'editSettings' ) &&
						'function' === typeof this.model.get( 'editSettings' ).set
					) {
						this.model.get( 'editSettings' ).set( 'defaultEditRoute', 'content' );
					}

					const settings = this.model.get( 'settings' );
					const elements = this.model.get( 'elements' );

					if ( settings ) {
						this.listenTo(
							settings,
							'change',
							this.onDirectoristSearchCompositionStateChange
						);
					}

					if ( elements ) {
						this.listenTo(
							elements,
							'add remove sort reset update',
							this.onDirectoristSearchCompositionStateChange
						);
					}

					bindCardTemplateDescendantBindings( this );
				},

				getChildViewContainer: function() {
					const selector = this.isBoxedWidth && this.isBoxedWidth()
						? '> .e-con-inner > .directorist-elementor-search-composition > .directorist-elementor-search-composition__storage'
						: '> .directorist-elementor-search-composition > .directorist-elementor-search-composition__storage';
					const $container = this.$el.find( selector );

					return $container.length
						? $container
						: this.$el.find( '.directorist-elementor-search-composition__storage' ).first();
				},

				getCorrectContainerElement: function() {
					return this.getChildViewContainer();
				},

				getDroppableOptions: function() {
					const options =
						'function' === typeof BaseView.prototype.getDroppableOptions
							? BaseView.prototype.getDroppableOptions.apply( this, arguments )
							: {};

					options.items = '> .elementor-element, > .elementor-empty-view .elementor-first-add';

					return options;
				},

				droppableDestroy: function() {
					const $container = this.getCorrectContainerElement();

					if ( $container && $container.length ) {
						$container.html5Droppable( 'destroy' );
					}
				},

				droppableInitialize: function() {
					const $container = this.getCorrectContainerElement();

					if ( $container && $container.length ) {
						$container.html5Droppable( this.getDroppableOptions() );
					}
				},

				getTemplateType: function() {
					return 'js';
				},

				events: function() {
					const parentEvents =
						'function' === typeof BaseView.prototype.events
							? BaseView.prototype.events.apply( this, arguments )
							: ( BaseView.prototype.events || {} );

					return $.extend( {}, parentEvents, {
						[ `click ${ selector }` ]: 'onDirectoristCompositionClick',
					} );
				},

				onDirectoristCompositionClick: function( event ) {
					if ( CARD_TEMPLATE_WIDGET === getContainerType( this.getContainer() ) ) {
						syncCardTemplateEditorState( this.getContainer() );
					}

					const previewClickContainer = resolvePreviewClickContainer( this.getContainer(), event.target );

					if ( previewClickContainer ) {
						event.preventDefault();
						event.stopPropagation();
						selectContainer( previewClickContainer );
						return;
					}

					const targetContainer = ensureCompositionTarget( this.getContainer() );

					if ( ! targetContainer ) {
						return;
					}

					event.preventDefault();
					event.stopPropagation();

					selectContainer( targetContainer );
				},

				onDirectoristSearchCompositionStateChange: function( changedModel ) {
					const changedKeys = changedModel && changedModel.changed
						? Object.keys( changedModel.changed )
						: [];

					bindCardTemplateDescendantBindings( this );
					if ( isOnlySearchTemplateStorageChange( changedKeys ) ) {
						syncSearchCompositionEditorState( this.getContainer(), { skipScope: true } );
						return;
					}

					syncSearchCompositionEditorState( this.getContainer() );
					queuePreviewRequest(
						`search:${ this.getContainer().id || '' }`,
						() => requestSearchCompositionPreview( this.getContainer() )
					);
				},

				onDirectoristCardTemplateDescendantSettingsChange: function() {
					scheduleSearchCompositionActiveScopePersist( this.getContainer(), 40, { force: true } );
					queuePreviewRequest(
						`search:${ this.getContainer().id || '' }`,
						() => requestSearchCompositionPreview( this.getContainer() ),
						60
					);
				},

				onDirectoristCardTemplateDescendantStructureChange: function() {
					bindCardTemplateDescendantBindings( this );
					scheduleSearchCompositionActiveScopePersist( this.getContainer(), 20, { force: true } );
					queuePreviewRequest(
						`search:${ this.getContainer().id || '' }`,
						() => requestSearchCompositionPreview( this.getContainer() ),
						40
					);
				},

				renderHTML: function() {
					const templateType = this.getTemplateType();
					const editModel = this.getEditModel();

					if ( 'js' === templateType ) {
						editModel.setHtmlCache();
						this.render();
						bindCardTemplateDescendantBindings( this );
						syncSearchCompositionEditorState( this.getContainer() );
						queuePreviewRequest(
							`search:${ this.getContainer().id || '' }`,
							() => requestSearchCompositionPreview( this.getContainer() )
						);
						return;
					}
				},

				onRender: function() {
					if ( 'function' === typeof BaseView.prototype.onRender ) {
						BaseView.prototype.onRender.apply( this, arguments );
					}

					bindCardTemplateDescendantBindings( this );
					syncSearchCompositionEditorState( this.getContainer() );
					ensureLoopContentTabActive( this );
					queuePreviewRequest(
						`search:${ this.getContainer().id || '' }`,
						() => requestSearchCompositionPreview( this.getContainer() )
					);
				},

				onBeforeDestroy: function() {
					clearCardTemplateDescendantBindings( this );

					if ( 'function' === typeof BaseView.prototype.onBeforeDestroy ) {
						BaseView.prototype.onBeforeDestroy.apply( this, arguments );
					}
				},
			} );

			return viewCache[ `search:${ widgetType }` ];
		}

	function createSingleMapViewClass( BaseView ) {
		const selector = getProxySelector( SINGLE_MAP_WIDGET );

		return BaseView.extend( {
			initialize: function() {
				if ( 'function' === typeof BaseView.prototype.initialize ) {
					BaseView.prototype.initialize.apply( this, arguments );
				}

				const settings = this.model.get( 'settings' );
				const elements = this.model.get( 'elements' );

				if ( settings ) {
					this.listenTo(
						settings,
						'change',
						this.onDirectoristSingleMapStateChange
					);
				}

				if ( elements ) {
					this.listenTo(
						elements,
						'add remove sort reset update',
						this.onDirectoristSingleMapStateChange
					);
				}

				bindCardTemplateDescendantBindings( this );
			},

			getTemplateType: function() {
				return 'js';
			},

			events: function() {
				const parentEvents =
					'function' === typeof BaseView.prototype.events
						? BaseView.prototype.events.apply( this, arguments )
						: ( BaseView.prototype.events || {} );

				return $.extend( {}, parentEvents, {
					[ `click ${ selector }` ]: 'onDirectoristCompositionClick',
				} );
			},

			onDirectoristCompositionClick: function( event ) {
				const previewClickContainer = resolvePreviewClickContainer( this.getContainer(), event.target );

				if ( previewClickContainer ) {
					event.preventDefault();
					event.stopPropagation();
					selectContainer( previewClickContainer );
					return;
				}

				const targetContainer = ensureCompositionTarget( this.getContainer() );

				if ( ! targetContainer ) {
					return;
				}

				event.preventDefault();
				event.stopPropagation();

				selectContainer( targetContainer );
			},

			onDirectoristSingleMapStateChange: function() {
				bindCardTemplateDescendantBindings( this );
				syncSingleMapEditorState( this.getContainer() );
				queuePreviewRequest(
					`single-map:${ this.getContainer().id || '' }`,
					() => requestSingleMapPreview( this.getContainer() )
				);
			},

			onDirectoristCardTemplateDescendantSettingsChange: function() {
				queuePreviewRequest(
					`single-map:${ this.getContainer().id || '' }`,
					() => requestSingleMapPreview( this.getContainer() ),
					60
				);
			},

			onDirectoristCardTemplateDescendantStructureChange: function() {
				bindCardTemplateDescendantBindings( this );
				queuePreviewRequest(
					`single-map:${ this.getContainer().id || '' }`,
					() => requestSingleMapPreview( this.getContainer() ),
					40
				);
			},

			renderHTML: function() {
				const templateType = this.getTemplateType();
				const editModel = this.getEditModel();

				if ( 'js' === templateType ) {
					editModel.setHtmlCache();
					this.render();
					bindCardTemplateDescendantBindings( this );
					syncSingleMapEditorState( this.getContainer() );
					queuePreviewRequest(
						`single-map:${ this.getContainer().id || '' }`,
						() => requestSingleMapPreview( this.getContainer() )
					);
					return;
				}
			},

			onRender: function() {
				if ( 'function' === typeof BaseView.prototype.onRender ) {
					BaseView.prototype.onRender.apply( this, arguments );
				}

				bindCardTemplateDescendantBindings( this );
				syncSingleMapEditorState( this.getContainer() );
				queuePreviewRequest(
					`single-map:${ this.getContainer().id || '' }`,
					() => requestSingleMapPreview( this.getContainer() )
				);
			},

			onBeforeDestroy: function() {
				clearCardTemplateDescendantBindings( this );

				if ( 'function' === typeof BaseView.prototype.onBeforeDestroy ) {
					BaseView.prototype.onBeforeDestroy.apply( this, arguments );
				}
			},
		} );
	}

	function getContainerElementTypeBase() {
		if (
			! window.elementor ||
			! window.elementor.elementsManager ||
			'function' !== typeof window.elementor.elementsManager.getElementTypeClass
		) {
			return null;
		}

		const type = window.elementor.elementsManager.getElementTypeClass( 'container' ) || null;

		return isPromiseLike( type ) ? null : type;
	}

	function createLoopElementTypeClass( BaseTypeInstance, loopWidgetType ) {
		loopWidgetType = loopWidgetType || LOOP_WIDGET;

		if ( viewCache[ `type:${ loopWidgetType }` ] ) {
			return viewCache[ `type:${ loopWidgetType }` ];
		}

		const BaseConstructor = BaseTypeInstance.constructor;
		const BaseView = BaseTypeInstance.getView();
		const BaseModel = BaseTypeInstance.getModel();
		const LoopView = createLoopElementViewClass( BaseView, loopWidgetType );
		const LoopModel = BaseModel.extend( {
			defaults: function() {
				const parentDefaults =
					'function' === typeof BaseModel.prototype.defaults
						? BaseModel.prototype.defaults()
						: ( BaseModel.prototype.defaults || {} );

				return $.extend( true, {}, parentDefaults, {
					defaultEditSettings: $.extend(
						true,
						{},
						parentDefaults.defaultEditSettings || {},
						{
							defaultEditRoute: 'content',
						}
					),
				} );
			},

			initialize: function() {
				const elements = this.get( 'elements' ) || [];

				this.config = getElementTypeConfig( loopWidgetType );

				if ( 0 === elements.length && isElementCreateTrace() ) {
					this.onElementCreate();
				}

				if ( 'function' === typeof BaseModel.prototype.initialize ) {
					BaseModel.prototype.initialize.apply( this, arguments );
				}
			},

			getDefaultChildren: function() {
				const config = this.config || getElementTypeConfig( loopWidgetType );
				const defaultChildren =
					config.default_children ||
					( config.defaults && config.defaults.elements ) ||
					[];

				return cloneDefaultChildren( defaultChildren );
			},

			onElementCreate: function() {
				this.set(
					'elements',
					this.getDefaultChildren().map( ( element ) => this.buildElement( element ) )
				);
			},

			buildElement: function( element ) {
				const childElements = Array.isArray( element.elements )
					? element.elements.map( ( child ) => this.buildElement( child ) )
					: [];

				return {
					elType: element.elType,
					widgetType: element.widgetType,
					id: elementorCommon.helpers.getUniqueId(),
					settings: $.extend( true, {}, element.settings || {} ),
					elements: childElements,
					isInner: !! element.isInner,
					isLocked: !! element.isLocked,
					editor_settings: $.extend( true, {}, element.editor_settings || {} ),
				};
			},
		} );

		viewCache[ loopWidgetType ] = LoopView;
		viewCache[ `type:${ loopWidgetType }` ] = class DirectoristLoopElementType extends BaseConstructor {
			getType() {
				return loopWidgetType;
			}

			getView() {
				return LoopView;
			}

			getEmptyView() {
				return 'function' === typeof BaseTypeInstance.getEmptyView
					? BaseTypeInstance.getEmptyView()
					: LoopView;
			}

			getModel() {
				return LoopModel;
			}
		};

		return viewCache[ `type:${ loopWidgetType }` ];
	}

	function createCardTemplateElementTypeClass( BaseTypeInstance ) {
		if ( viewCache[ `type:${ CARD_TEMPLATE_WIDGET }` ] ) {
			return viewCache[ `type:${ CARD_TEMPLATE_WIDGET }` ];
		}

		const BaseConstructor = BaseTypeInstance.constructor;
		const BaseView = BaseTypeInstance.getView();
		const BaseModel = BaseTypeInstance.getModel();
		const CardTemplateView = createNestedViewClass( BaseView );
		const CardTemplateModel = BaseModel.extend( {
			defaults: function() {
				const parentDefaults =
					'function' === typeof BaseModel.prototype.defaults
						? BaseModel.prototype.defaults()
						: ( BaseModel.prototype.defaults || {} );

				return $.extend( true, {}, parentDefaults, {
					defaultEditSettings: $.extend(
						true,
						{},
						parentDefaults.defaultEditSettings || {},
						{
							defaultEditRoute: 'content',
						}
					),
				} );
			},

			initialize: function() {
				const elements = this.get( 'elements' ) || [];

				this.config = getElementTypeConfig( CARD_TEMPLATE_WIDGET );
				this.set( 'supportRepeaterChildren', true );

				if ( 0 === elements.length && isElementCreateTrace() ) {
					this.onElementCreate();
				}

				if ( 'function' === typeof BaseModel.prototype.initialize ) {
					BaseModel.prototype.initialize.apply( this, arguments );
				}
			},

			getDefaultChildren: function() {
				const config = this.config || getElementTypeConfig( CARD_TEMPLATE_WIDGET );
				const defaultChildren =
					config.default_children ||
					( config.defaults && config.defaults.elements ) ||
					[];

				return cloneDefaultChildren( defaultChildren );
			},

			onElementCreate: function() {
				this.set(
					'elements',
					this.getDefaultChildren().map( ( element ) => this.buildElement( element ) )
				);
			},

			buildElement: function( element ) {
				const childElements = Array.isArray( element.elements )
					? element.elements.map( ( child ) => this.buildElement( child ) )
					: [];

				return {
					elType: element.elType,
					widgetType: element.widgetType,
					id: elementorCommon.helpers.getUniqueId(),
					settings: $.extend( true, {}, element.settings || {} ),
					elements: childElements,
					isInner: !! element.isInner,
					isLocked: !! element.isLocked,
					editor_settings: $.extend( true, {}, element.editor_settings || {} ),
				};
			},
		} );

		viewCache[ CARD_TEMPLATE_WIDGET ] = CardTemplateView;
		viewCache[ `type:${ CARD_TEMPLATE_WIDGET }` ] = class DirectoristCardTemplateElementType extends BaseConstructor {
			getType() {
				return CARD_TEMPLATE_WIDGET;
			}

			getView() {
				return CardTemplateView;
			}

			getEmptyView() {
				return 'function' === typeof BaseTypeInstance.getEmptyView
					? BaseTypeInstance.getEmptyView()
					: CardTemplateView;
			}

			getModel() {
				return CardTemplateModel;
			}
		};

		return viewCache[ `type:${ CARD_TEMPLATE_WIDGET }` ];
	}

	function createPricingPlansElementTypeClass( BaseTypeInstance ) {
		if ( viewCache[ `type:${ PRICING_PLANS_WIDGET }` ] ) {
			return viewCache[ `type:${ PRICING_PLANS_WIDGET }` ];
		}

		const BaseConstructor = BaseTypeInstance.constructor;
		const BaseView = BaseTypeInstance.getView();
		const BaseModel = BaseTypeInstance.getModel();
		const PricingPlansView = createPricingPlansViewClass( BaseView );
		const PricingPlansModel = BaseModel.extend( {
			defaults: function() {
				const parentDefaults =
					'function' === typeof BaseModel.prototype.defaults
						? BaseModel.prototype.defaults()
						: ( BaseModel.prototype.defaults || {} );

				return $.extend( true, {}, parentDefaults, {
					defaultEditSettings: $.extend(
						true,
						{},
						parentDefaults.defaultEditSettings || {},
						{
							defaultEditRoute: 'content',
						}
					),
				} );
			},

			initialize: function() {
				const elements = this.get( 'elements' ) || [];

				this.config = getElementTypeConfig( PRICING_PLANS_WIDGET );
				this.set( 'supportRepeaterChildren', true );

				if ( 0 === elements.length && isElementCreateTrace() ) {
					this.onElementCreate();
				}

				if ( 'function' === typeof BaseModel.prototype.initialize ) {
					BaseModel.prototype.initialize.apply( this, arguments );
				}
			},

			getDefaultChildren: function() {
				const config = this.config || getElementTypeConfig( PRICING_PLANS_WIDGET );
				const defaultChildren =
					config.default_children ||
					( config.defaults && config.defaults.elements ) ||
					[];

				return cloneDefaultChildren( defaultChildren );
			},

			onElementCreate: function() {
				this.set(
					'elements',
					this.getDefaultChildren().map( ( element ) => this.buildElement( element ) )
				);
			},

			buildElement: function( element ) {
				const childElements = Array.isArray( element.elements )
					? element.elements.map( ( child ) => this.buildElement( child ) )
					: [];

				return {
					elType: element.elType,
					widgetType: element.widgetType,
					id: elementorCommon.helpers.getUniqueId(),
					settings: $.extend( true, {}, element.settings || {} ),
					elements: childElements,
					isInner: !! element.isInner,
					isLocked: !! element.isLocked,
					editor_settings: $.extend( true, {}, element.editor_settings || {} ),
				};
			},
		} );

		viewCache[ PRICING_PLANS_WIDGET ] = PricingPlansView;
		viewCache[ `type:${ PRICING_PLANS_WIDGET }` ] = class DirectoristPricingPlansElementType extends BaseConstructor {
			getType() {
				return PRICING_PLANS_WIDGET;
			}

			getView() {
				return PricingPlansView;
			}

			getEmptyView() {
				return 'function' === typeof BaseTypeInstance.getEmptyView
					? BaseTypeInstance.getEmptyView()
					: PricingPlansView;
			}

			getModel() {
				return PricingPlansModel;
			}
		};

		return viewCache[ `type:${ PRICING_PLANS_WIDGET }` ];
	}

	function createAuthorProfileElementTypeClass( BaseTypeInstance ) {
		if ( viewCache[ `type:${ AUTHOR_PROFILE_WIDGET }` ] ) {
			return viewCache[ `type:${ AUTHOR_PROFILE_WIDGET }` ];
		}

		const BaseConstructor = BaseTypeInstance.constructor;
		const BaseView = BaseTypeInstance.getView();
		const BaseModel = BaseTypeInstance.getModel();
		const AuthorProfileView = createAuthorProfileViewClass( BaseView );
		const AuthorProfileModel = BaseModel.extend( {
			defaults: function() {
				const parentDefaults =
					'function' === typeof BaseModel.prototype.defaults
						? BaseModel.prototype.defaults()
						: ( BaseModel.prototype.defaults || {} );

				return $.extend( true, {}, parentDefaults, {
					defaultEditSettings: $.extend(
						true,
						{},
						parentDefaults.defaultEditSettings || {},
						{
							defaultEditRoute: 'content',
						}
					),
				} );
			},

			initialize: function() {
				this.normalizeLegacyAuthorProfileElements();
				this.config = getElementTypeConfig( AUTHOR_PROFILE_WIDGET );
				this.set( 'supportRepeaterChildren', true );

				if ( 0 === this.getAuthorProfileElementsLength() && isElementCreateTrace() ) {
					this.onElementCreate();
				}

				if ( 'function' === typeof BaseModel.prototype.initialize ) {
					BaseModel.prototype.initialize.apply( this, arguments );
				}
			},

			getAuthorProfileElementsLength: function() {
				const elements = this.get( 'elements' );

				if ( Array.isArray( elements ) ) {
					return elements.length;
				}

				return elements && Number.isInteger( elements.length ) ? elements.length : 0;
			},

			normalizeLegacyAuthorProfileElements: function() {
				const elements = this.get( 'elements' );
				let first = null;

				if ( Array.isArray( elements ) ) {
					if ( 1 !== elements.length ) {
						return;
					}

					first = elements[ 0 ];
				} else if ( elements && 'function' === typeof elements.at ) {
					if ( 1 !== elements.length ) {
						return;
					}

					first = elements.at( 0 );
				}

				const getValue = function( source, key ) {
					if ( ! source ) {
						return undefined;
					}

					if ( 'function' === typeof source.get ) {
						return source.get( key );
					}

					return source[ key ];
				};
				const firstSettings = getValue( first, 'settings' ) || {};
				const firstTitle = 'function' === typeof firstSettings.get
					? firstSettings.get( '_title' )
					: firstSettings._title;

				if (
					'container' !== getValue( first, 'elType' ) ||
					'Author Profile Fields' !== String( firstTitle || '' )
				) {
					return;
				}

				const childElements = getValue( first, 'elements' );
				const normalizedChildren = Array.isArray( childElements )
					? childElements
					: ( childElements && 'function' === typeof childElements.toJSON ? childElements.toJSON() : [] );

				this.set( 'elements', normalizedChildren );
			},

			getDefaultChildren: function() {
				const config = this.config || getElementTypeConfig( AUTHOR_PROFILE_WIDGET );
				const defaultChildren =
					config.default_children ||
					( config.defaults && config.defaults.elements ) ||
					[];

				return cloneDefaultChildren( defaultChildren );
			},

			onElementCreate: function() {
				this.set(
					'elements',
					this.getDefaultChildren().map( ( element ) => this.buildElement( element ) )
				);
			},

			buildElement: function( element ) {
				const childElements = Array.isArray( element.elements )
					? element.elements.map( ( child ) => this.buildElement( child ) )
					: [];

				return {
					elType: element.elType,
					widgetType: element.widgetType,
					id: elementorCommon.helpers.getUniqueId(),
					settings: $.extend( true, {}, element.settings || {} ),
					elements: childElements,
					isInner: !! element.isInner,
					isLocked: !! element.isLocked,
					editor_settings: $.extend( true, {}, element.editor_settings || {} ),
				};
			},
		} );

		viewCache[ AUTHOR_PROFILE_WIDGET ] = AuthorProfileView;
		viewCache[ `type:${ AUTHOR_PROFILE_WIDGET }` ] = class DirectoristAuthorProfileElementType extends BaseConstructor {
			getType() {
				return AUTHOR_PROFILE_WIDGET;
			}

			getView() {
				return AuthorProfileView;
			}

			getEmptyView() {
				return 'function' === typeof BaseTypeInstance.getEmptyView
					? BaseTypeInstance.getEmptyView()
					: AuthorProfileView;
			}

			getModel() {
				return AuthorProfileModel;
			}
		};

		return viewCache[ `type:${ AUTHOR_PROFILE_WIDGET }` ];
	}

	function createSearchCompositionElementTypeClass( BaseTypeInstance, widgetType ) {
		if ( viewCache[ `type:${ widgetType }` ] ) {
			return viewCache[ `type:${ widgetType }` ];
		}

		const BaseConstructor = BaseTypeInstance.constructor;
		const BaseView = BaseTypeInstance.getView();
		const BaseModel = BaseTypeInstance.getModel();
		const SearchCompositionView = createSearchCompositionViewClass( BaseView, widgetType );
		const defaultEditRoute = HOME_SEARCH_WIDGET === widgetType ? HOME_SEARCH_STYLE_TAB : 'content';
		const SearchCompositionModel = BaseModel.extend( {
			defaults: function() {
				const parentDefaults =
					'function' === typeof BaseModel.prototype.defaults
						? BaseModel.prototype.defaults()
						: ( BaseModel.prototype.defaults || {} );

				return $.extend( true, {}, parentDefaults, {
					defaultEditSettings: $.extend(
						true,
						{},
						parentDefaults.defaultEditSettings || {},
						{
							defaultEditRoute,
						}
					),
				} );
			},

			initialize: function() {
				const elements = this.get( 'elements' ) || [];

				this.config = getElementTypeConfig( widgetType );
				this.set( 'supportRepeaterChildren', true );

				if ( 0 === elements.length && isElementCreateTrace() ) {
					this.onElementCreate();
				}

				if ( 'function' === typeof BaseModel.prototype.initialize ) {
					BaseModel.prototype.initialize.apply( this, arguments );
				}
			},

			getDefaultChildren: function() {
				const config = this.config || getElementTypeConfig( widgetType );
				const defaultChildren =
					config.default_children ||
					( config.defaults && config.defaults.elements ) ||
					[];

				return cloneDefaultChildren( defaultChildren );
			},

			onElementCreate: function() {
				this.set(
					'elements',
					this.getDefaultChildren().map( ( element ) => this.buildElement( element ) )
				);
			},

			buildElement: function( element ) {
				const childElements = Array.isArray( element.elements )
					? element.elements.map( ( child ) => this.buildElement( child ) )
					: [];

				return {
					elType: element.elType,
					widgetType: element.widgetType,
					id: elementorCommon.helpers.getUniqueId(),
					settings: $.extend( true, {}, element.settings || {} ),
					elements: childElements,
					isInner: !! element.isInner,
					isLocked: !! element.isLocked,
					editor_settings: $.extend( true, {}, element.editor_settings || {} ),
				};
			},
		} );

		viewCache[ widgetType ] = SearchCompositionView;
		viewCache[ `type:${ widgetType }` ] = class DirectoristSearchCompositionElementType extends BaseConstructor {
			getType() {
				return widgetType;
			}

			getView() {
				return SearchCompositionView;
			}

			getEmptyView() {
				return 'function' === typeof BaseTypeInstance.getEmptyView
					? BaseTypeInstance.getEmptyView()
					: SearchCompositionView;
			}

			getModel() {
				return SearchCompositionModel;
			}
		};

		return viewCache[ `type:${ widgetType }` ];
	}

	function getNestedElementTypeBase() {
		if (
			! window.elementor ||
			! window.elementor.modules ||
			! window.elementor.modules.elements ||
			! window.elementor.modules.elements.types
		) {
			return null;
		}

		const baseType =
			window.elementor.modules.elements.types.NestedElementBase ||
			window.elementor.modules.elements.types.NestedElementTypesBase ||
			null;

		if ( isPromiseLike( baseType ) ) {
			return null;
		}

		if ( baseType && isConstructorLike( baseType.default ) ) {
			return baseType.default;
		}

		return isConstructorLike( baseType ) ? baseType : null;
	}

	function createNestedElementTypeClass( BaseType, nestedRepeater, widgetType ) {
		if ( viewCache[ `type:${ widgetType }` ] ) {
			return viewCache[ `type:${ widgetType }` ];
		}

		if (
			! isConstructorLike( BaseType ) ||
			! nestedRepeater ||
			! isConstructorLike( nestedRepeater.NestedModelBase ) ||
			! nestedRepeater.NestedViewBase
		) {
			return null;
		}

		const SafeModel = getSafeNestedModelClass( nestedRepeater, widgetType );
		let NestedView = createNestedViewClass( nestedRepeater.NestedViewBase );

			if ( RELATED_LISTINGS_WIDGET === widgetType ) {
				NestedView = createRelatedListingsViewClass( nestedRepeater.NestedViewBase );
			} else if ( isTaxonomyCompositionWidget( widgetType ) ) {
				NestedView = createTaxonomyCompositionViewClass( nestedRepeater.NestedViewBase, widgetType );
			} else if ( isSearchCompositionWidget( widgetType ) ) {
				NestedView = createSearchCompositionViewClass( nestedRepeater.NestedViewBase, widgetType );
			} else if ( SINGLE_MAP_WIDGET === widgetType ) {
				NestedView = createSingleMapViewClass( nestedRepeater.NestedViewBase );
			}

		viewCache[ widgetType ] = NestedView;
		viewCache[ `type:${ widgetType }` ] = class DirectoristNestedElementType extends BaseType {
			getType() {
				return widgetType;
			}

			getView() {
				return NestedView;
			}

			getEmptyView() {
				if ( 'function' === typeof BaseType.prototype.getEmptyView ) {
					return BaseType.prototype.getEmptyView.call( this );
				}

				return this.getView();
			}

			getModel() {
				return SafeModel;
			}
		};

		return viewCache[ `type:${ widgetType }` ];
	}

	function registerLoopElementType( loopWidgetType ) {
		loopWidgetType = loopWidgetType || LOOP_WIDGET;

		if ( registeredElementTypes[ loopWidgetType ] ) {
			return true;
		}

		const BaseTypeInstance = getContainerElementTypeBase();

		if (
			! BaseTypeInstance ||
			! window.elementor ||
			! window.elementor.elementsManager ||
			typeof window.elementor.elementsManager.registerElementType !== 'function'
		) {
			return false;
		}

		const ElementTypeClass = createLoopElementTypeClass( BaseTypeInstance, loopWidgetType );

		window.elementor.elementsManager.registerElementType(
			new ElementTypeClass()
		);
		registeredElementTypes[ loopWidgetType ] = true;

		return true;
	}

	function registerPricingPlansElementType() {
		if ( registeredElementTypes[ PRICING_PLANS_WIDGET ] ) {
			return true;
		}

		const BaseTypeInstance = getContainerElementTypeBase();

		if (
			! BaseTypeInstance ||
			! window.elementor ||
			! window.elementor.elementsManager ||
			typeof window.elementor.elementsManager.registerElementType !== 'function'
		) {
			return false;
		}

		const ElementTypeClass = createPricingPlansElementTypeClass( BaseTypeInstance );

		window.elementor.elementsManager.registerElementType(
			new ElementTypeClass()
		);
		registeredElementTypes[ PRICING_PLANS_WIDGET ] = true;

		return true;
	}

	function registerCardTemplateElementType() {
		if ( registeredElementTypes[ CARD_TEMPLATE_WIDGET ] ) {
			return true;
		}

		const BaseTypeInstance = getContainerElementTypeBase();

		if (
			! BaseTypeInstance ||
			! window.elementor ||
			! window.elementor.elementsManager ||
			typeof window.elementor.elementsManager.registerElementType !== 'function'
		) {
			return false;
		}

		const ElementTypeClass = createCardTemplateElementTypeClass( BaseTypeInstance );

		window.elementor.elementsManager.registerElementType(
			new ElementTypeClass()
		);
		registeredElementTypes[ CARD_TEMPLATE_WIDGET ] = true;

		return true;
	}

	function registerAuthorProfileElementType() {
		if ( registeredElementTypes[ AUTHOR_PROFILE_WIDGET ] ) {
			return true;
		}

		const BaseTypeInstance = getContainerElementTypeBase();

		if (
			! BaseTypeInstance ||
			! window.elementor ||
			! window.elementor.elementsManager ||
			typeof window.elementor.elementsManager.registerElementType !== 'function'
		) {
			return false;
		}

		const ElementTypeClass = createAuthorProfileElementTypeClass( BaseTypeInstance );

		window.elementor.elementsManager.registerElementType(
			new ElementTypeClass()
		);
		registeredElementTypes[ AUTHOR_PROFILE_WIDGET ] = true;

		return true;
	}

	function registerSearchCompositionElementType( widgetType ) {
		if ( registeredElementTypes[ widgetType ] ) {
			return true;
		}

		const BaseTypeInstance = getContainerElementTypeBase();

		if (
			! BaseTypeInstance ||
			! window.elementor ||
			! window.elementor.elementsManager ||
			typeof window.elementor.elementsManager.registerElementType !== 'function'
		) {
			return false;
		}

		const ElementTypeClass = createSearchCompositionElementTypeClass( BaseTypeInstance, widgetType );

		window.elementor.elementsManager.registerElementType(
			new ElementTypeClass()
		);
		registeredElementTypes[ widgetType ] = true;

		return true;
	}

	function registerHomepageSearchPanelTabs() {
		if (
			! window.$e ||
			! window.$e.components ||
			'function' !== typeof window.$e.components.get
		) {
			return false;
		}

		const panelEditor = window.$e.components.get( 'panel/editor' );

		if (
			! panelEditor ||
			'function' !== typeof panelEditor.addTab ||
			'function' !== typeof panelEditor.hasTab
		) {
			return false;
		}

		if ( ! panelEditor.hasTab( HOME_SEARCH_STYLE_TAB ) ) {
			panelEditor.addTab( HOME_SEARCH_STYLE_TAB, { title: 'Search' }, 1 );
		}

		if (
			shouldShowHomepageDirectoryTypesStyleTab() &&
			! panelEditor.hasTab( HOME_SEARCH_DIRECTORY_TYPES_STYLE_TAB )
		) {
			panelEditor.addTab( HOME_SEARCH_DIRECTORY_TYPES_STYLE_TAB, { title: 'Directory Types' }, 2 );
		}

		return true;
	}

	function shouldShowHomepageDirectoryTypesStyleTab() {
		const config = getEditorConfig();
		let directoryIds = normalizeDirectoryIds( config.homeSearchContractDirectoryTypeIds || [] );

		if ( ! directoryIds.length ) {
			directoryIds = normalizeDirectoryIds( Object.keys( config.directoryOptions || {} ) );
		}

		return directoryIds.length > 1;
	}

	function registerNestedElementTypes() {
		const nestedRepeater = getNestedRepeaterExports();
		const BaseType = getNestedElementTypeBase();

		if (
			! nestedRepeater ||
			! BaseType ||
			! window.elementor ||
			! window.elementor.elementsManager ||
			typeof window.elementor.elementsManager.registerElementType !== 'function'
		) {
			return false;
		}

		DIRECTORIST_NESTED_WIDGETS.forEach( ( widgetType ) => {
			if ( registeredElementTypes[ widgetType ] ) {
				return;
			}

			const ElementTypeClass = createNestedElementTypeClass( BaseType, nestedRepeater, widgetType );

			if ( ! ElementTypeClass ) {
				return;
			}

			window.elementor.elementsManager.registerElementType(
				new ElementTypeClass()
			);
			registeredElementTypes[ widgetType ] = true;
		} );

		return DIRECTORIST_NESTED_WIDGETS.every(
			( widgetType ) => !! registeredElementTypes[ widgetType ]
		);
	}

	function ensureNestedCompositionSupport() {
		const loopTypeReady = LOOP_WIDGETS.every( registerLoopElementType );
		const cardTemplateTypeReady = registerCardTemplateElementType();
		const pricingPlansTypeReady = registerPricingPlansElementType();
		const authorProfileTypeReady = registerAuthorProfileElementType();
		const searchCompositionTypeReady = SEARCH_COMPOSITION_WIDGETS.every( registerSearchCompositionElementType );
		const nestedTypesReady = registerNestedElementTypes();
		const panelReady = patchPanelElementAddToPage();
		const homepageSearchPanelTabsReady = registerHomepageSearchPanelTabs();
		const navigatorReady = initNavigatorObserver();
		const searchSelectionBridgeReady = bindSearchCompositionSelectionBridge();

		elementTypesRegistered = loopTypeReady && cardTemplateTypeReady && pricingPlansTypeReady && authorProfileTypeReady && searchCompositionTypeReady && nestedTypesReady;

		if ( elementTypesRegistered && panelReady && homepageSearchPanelTabsReady && navigatorReady && searchSelectionBridgeReady ) {
			return;
		}

		if ( retryTimer ) {
			return;
		}

		retryTimer = window.setTimeout( function() {
			retryTimer = 0;
			ensureNestedCompositionSupport();
		}, 250 );
	}

	function init() {
		bindResponsiveDeviceBridge();
		ensureNestedCompositionSupport();
		window.requestAnimationFrame( function() {
			syncAllEditorDeviceModeClasses();
			bindListingRatingEditorPreview();
		} );

		if (
			! previewLoadedBridgeBound &&
			window.elementor &&
			typeof window.elementor.on === 'function'
		) {
			previewLoadedBridgeBound = true;
			window.elementor.on( 'preview:loaded', function() {
				ensureNestedCompositionSupport();
				window.requestAnimationFrame( function() {
					syncAllEditorDeviceModeClasses();
					bindListingRatingEditorPreview();
				} );
			} );
		}
	}

	$( window ).on( 'elementor:init', init );

	if ( window.elementor ) {
		init();
	}
} )( jQuery, window );
