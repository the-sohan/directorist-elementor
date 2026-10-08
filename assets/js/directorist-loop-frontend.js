( function( $, window, document ) {
	const config = window.directoristElementorV4Frontend || {};
		const LOOP_SELECTOR = '.directorist-elementor-loop[data-direl-loop-id]';
		const HOME_SEARCH_SELECTOR = '.directorist-elementor-homepage-search';
		const HOME_SEARCH_LOOP_SELECTOR = '.directorist-elementor-homepage-search-loop';
		const HOME_SEARCH_FORM_SELECTOR = `${ HOME_SEARCH_SELECTOR } form.directorist-search-form, ${ HOME_SEARCH_SELECTOR } form.directorist-basic-search, ${ HOME_SEARCH_SELECTOR } form.directorist-advanced-search, ${ HOME_SEARCH_SELECTOR } form.directorist-advanced-filter__form`;
		const FAVORITE_FALLBACK_EVENT = 'click.directoristElementorFavoriteFallback';
		const FAVORITE_SELECTOR = '.directorist-elementor-listing-card-badge-favorite__button.directorist-mark-as-favorite__btn';
		const MAP_LIST_SELECTOR = '.directorist-elementor-map-list';
		const LOOP_MAP_SELECTOR = `${ LOOP_SELECTOR } .directorist-elementor-loop__map`;
		const MAP_PREVIEW_SELECTOR = '.directorist-elementor-card-template__map-preview';
	const MAP_CONTEXT_SELECTOR = `${ MAP_LIST_SELECTOR }, ${ LOOP_MAP_SELECTOR }, ${ MAP_PREVIEW_SELECTOR }`;
	const MAP_POPUP_CONTEXT_SELECTOR = `${ LOOP_MAP_SELECTOR }, ${ MAP_PREVIEW_SELECTOR }`;
	const LISTING_ID_SELECTOR = '[data-directorist-listing-id]';
	const MOBILE_MEDIA_QUERY = '(max-width: 991px)';
	const MAP_POPUP_HORIZONTAL_GUTTER = 24;
	const MAP_POPUP_VERTICAL_RESERVE = 72;
	const pendingRequests = new Map();
	const standaloneHomepageSearchRequests = new WeakMap();
	const LOOP_FORM_SELECTOR = `${ LOOP_SELECTOR } form.directorist-search-form, ${ LOOP_SELECTOR } form.directorist-basic-search, ${ LOOP_SELECTOR } form.directorist-advanced-search, ${ LOOP_SELECTOR } form.directorist-advanced-filter__form`;
	const SEARCH_MODAL_SCOPE_SELECTOR = `${ LOOP_SELECTOR }, ${ HOME_SEARCH_SELECTOR }`;
	let infiniteScrollCheckScheduled = false;
	let leafletPatchTimer = null;
	let googlePatchTimer = null;
	let leafletPatchAttempts = 0;
	let googlePatchAttempts = 0;
	let leafletGlobalInterceptorInstalled = false;

	function debounce( callback, delay ) {
		let timeoutId = 0;

		return function( ...args ) {
			const context = this;

			window.clearTimeout( timeoutId );
			timeoutId = window.setTimeout( function() {
				callback.apply( context, args );
			}, delay );
		};
	}

	function toInt( value, fallback = 0 ) {
		const parsed = parseInt( value, 10 );

		return Number.isFinite( parsed ) ? parsed : fallback;
	}

		function getLoopRoot( element ) {
			const $element = $( element );

			return $element.closest( LOOP_SELECTOR );
		}

		function getFavoriteConfig() {
			const directoristConfig = window.directorist || {};
			const directoristI18n = directoristConfig.i18n_text || {};
			const elementorI18n = config.i18n || {};

			return {
				ajaxUrl: directoristConfig.ajax_url || directoristConfig.ajaxurl || config.ajaxUrl || '',
				nonce: directoristConfig.directorist_nonce || config.directoristNonce || '',
				addedMessage: directoristI18n.added_favourite || elementorI18n.addedFavourite || 'Added to favorites',
				loginMessage: directoristI18n.please_login || elementorI18n.pleaseLogin || 'Please login first',
			};
		}

		function removeFavoriteTooltips() {
			$( '.directorist-favorite-tooltip' ).hide();
			$( '.directorist-favorite-tooltip span' ).remove();
		}

		function showFavoriteTooltip( $buttons, message ) {
			$buttons
				.children( '.directorist-favorite-tooltip' )
				.empty()
				.append( $( '<span />' ).text( message ) )
				.fadeIn();

			window.setTimeout( function() {
				$buttons.children( '.directorist-favorite-tooltip' ).children( 'span' ).remove();
			}, 3000 );
		}

		function initializeFavoriteFallback() {
			if (
				window.directorist_favorite_executed ||
				window.directoristElementorFavoriteFallbackExecuted
			) {
				return;
			}

			const favoriteConfig = getFavoriteConfig();

			if ( ! favoriteConfig.ajaxUrl || ! favoriteConfig.nonce ) {
				return;
			}

			window.directoristElementorFavoriteFallbackExecuted = true;

			$( document.body )
				.off( FAVORITE_FALLBACK_EVENT, FAVORITE_SELECTOR )
				.on( FAVORITE_FALLBACK_EVENT, FAVORITE_SELECTOR, function( event ) {
					event.preventDefault();

					const $button = $( this );
					const listingId = $button.data( 'listing_id' );

					if ( ! listingId ) {
						return;
					}

					removeFavoriteTooltips();

					$.post(
						favoriteConfig.ajaxUrl,
						{
							action: 'atbdp-favourites-all-listing',
							directorist_nonce: favoriteConfig.nonce,
							post_id: listingId,
						},
						function( response ) {
							const normalizedResponse = String( response );
							const normalizedListingId = String( listingId );
							const $listingButtons = $( `.directorist-fav_${ normalizedListingId }` );

							if ( 'login_required' === normalizedResponse ) {
								showFavoriteTooltip( $listingButtons, favoriteConfig.loginMessage );
								return;
							}

							if ( 'false' === normalizedResponse ) {
								$listingButtons.removeClass( 'directorist-added-to-favorite' );
								removeFavoriteTooltips();
								return;
							}

							$listingButtons.addClass( 'directorist-added-to-favorite' );
							showFavoriteTooltip( $listingButtons, favoriteConfig.addedMessage );
						}
					);
				} );
		}

	function isMobileViewport() {
		return window.matchMedia( MOBILE_MEDIA_QUERY ).matches;
	}

	function normalizeKey( key ) {
		return String( key || '' ).replace( /\[\]$/, '' );
	}

	function normalizeRequestScalarValue( value ) {
		if ( Array.isArray( value ) ) {
			value = value[ 0 ];
		}

		return String( value || '' ).trim();
	}

	function appendFieldValue( target, key, value ) {
		const normalizedKey = normalizeKey( key );

		if ( '' === normalizedKey ) {
			return;
		}

		if ( undefined === target[ normalizedKey ] ) {
			target[ normalizedKey ] = value;
			return;
		}

		if ( target[ normalizedKey ] === value ) {
			return;
		}

		if ( Array.isArray( target[ normalizedKey ] ) ) {
			if ( ! target[ normalizedKey ].includes( value ) ) {
				target[ normalizedKey ].push( value );
			}
			return;
		}

		target[ normalizedKey ] = [ target[ normalizedKey ], value ];
	}

	function collectLoopRequestVars( $loop ) {
		const requestVars = {};

		$loop.find( 'form.directorist-search-form, form.directorist-advanced-filter__form' ).each( function() {
			const formData = new window.FormData( this );

			formData.forEach( function( value, key ) {
				appendFieldValue( requestVars, key, value );
			} );
		} );

		return requestVars;
	}

	function collectFormRequestVars( form ) {
		const requestVars = {};

		if ( ! form ) {
			return requestVars;
		}

		const formData = new window.FormData( form );

		formData.forEach( function( value, key ) {
			appendFieldValue( requestVars, key, value );
		} );

		return requestVars;
	}

	function collectHomepageSearchRequestVars( $search, form ) {
		const requestVars = {};
		const $forms = $search && $search.length
			? $search.find( 'form.directorist-search-form, form.directorist-basic-search, form.directorist-advanced-search, form.directorist-advanced-filter__form' )
			: $();

		if ( $forms.length ) {
			$forms.each( function() {
				const formData = new window.FormData( this );

				formData.forEach( function( value, key ) {
					appendFieldValue( requestVars, key, value );
				} );
			} );

			return requestVars;
		}

		return collectFormRequestVars( form );
	}

	function getHomepageSearchRoot( element ) {
		return $( element ).closest( HOME_SEARCH_SELECTOR );
	}

	function parseCsvIds( value ) {
		return String( value || '' )
			.split( ',' )
			.map( function( item ) {
				return toInt( item, 0 );
			} )
			.filter( function( item, index, values ) {
				return item > 0 && values.indexOf( item ) === index;
			} );
	}

	function parseJsonAttribute( element, attributeName, fallback = null ) {
		if ( ! element ) {
			return fallback;
		}

		try {
			const rawValue = element.getAttribute( attributeName );

			return rawValue ? JSON.parse( rawValue ) : fallback;
		} catch ( error ) {
			return fallback;
		}
	}

	function getListingIdFromMarkup( markup ) {
		if ( 'string' !== typeof markup || '' === markup ) {
			return 0;
		}

		const match = markup.match( /data-directorist-listing-id=["']?(\d+)/i );

		return match && match[ 1 ] ? toInt( match[ 1 ], 0 ) : 0;
	}

	function getCurrentMapListListingIds( mapList ) {
		const listPane = mapList.querySelector( '.directorist-elementor-map-list__list-pane' );

		if ( ! listPane ) {
			return [];
		}

		return Array.from( listPane.querySelectorAll( LISTING_ID_SELECTOR ) )
			.map( function( element ) {
				return toInt( element.getAttribute( 'data-directorist-listing-id' ), 0 );
			} )
			.filter( function( listingId, index, allIds ) {
				return listingId > 0 && allIds.indexOf( listingId ) === index;
			} );
	}

	function getMapContextRoot( element ) {
		return element && element.closest
			? element.closest( MAP_CONTEXT_SELECTOR )
			: null;
	}

	function getMapViewportElement( mapRoot ) {
		return mapRoot && mapRoot.querySelector
			? mapRoot.querySelector( '.leaflet-container, .atbdp-map, #map, .directorist-map, .directorist-map-wrapper' )
			: null;
	}

	function registerLeafletMapContext( target, map ) {
		const element = 'string' === typeof target
			? document.getElementById( target )
			: target;
		const contextRoot = getMapContextRoot( element );

		if ( contextRoot ) {
			element.__directoristDirelLeafletMap = map;
			element.__directoristDirelLeafletMarkers = [];
			window.__directoristDirelActiveLeafletMapElement = element;
			window.setTimeout( function() {
				if ( window.__directoristDirelActiveLeafletMapElement === element ) {
					window.__directoristDirelActiveLeafletMapElement = null;
				}
			}, 5000 );
		}
	}

	function registerLeafletMarkerContext( marker ) {
		if ( ! marker || marker.__directoristDirelMapListRegistered ) {
			return;
		}

		marker.__directoristDirelMapListRegistered = true;

		const activeElement = window.__directoristDirelActiveLeafletMapElement;
		if ( activeElement && activeElement.__directoristDirelLeafletMarkers ) {
			activeElement.__directoristDirelLeafletMarkers.push( marker );
		}
	}

	function watchLeafletApiProperty( leafletApi, propertyName ) {
		if ( ! leafletApi || 'function' === typeof leafletApi[ propertyName ] ) {
			return;
		}

		const watchFlag = `__directoristDirelMapList${ propertyName }WatchInstalled`;
		if ( leafletApi[ watchFlag ] ) {
			return;
		}

		try {
			let currentValue = leafletApi[ propertyName ];
			Object.defineProperty( leafletApi, propertyName, {
				configurable: true,
				get() {
					return currentValue;
				},
				set( value ) {
					currentValue = value;
					if ( 'function' === typeof currentValue ) {
						patchLeafletApi( leafletApi );
					}
				},
			} );
			leafletApi[ watchFlag ] = true;
		} catch ( error ) {}
	}

	function patchLeafletApi( leafletApi ) {
		if ( ! leafletApi ) {
			return false;
		}

		const hasMapFactory = 'function' === typeof leafletApi.map;
		const hasMarkerFactory = 'function' === typeof leafletApi.marker;
		const hasMapClass = !! (
			leafletApi.Map &&
			leafletApi.Map.prototype &&
			leafletApi.Map.prototype.initialize
		);
		const hasMarkerClass = !! (
			leafletApi.Marker &&
			leafletApi.Marker.prototype &&
			leafletApi.Marker.prototype.initialize
		);

		if ( ! hasMapFactory ) {
			watchLeafletApiProperty( leafletApi, 'map' );
		}

		if ( ! hasMarkerFactory ) {
			watchLeafletApiProperty( leafletApi, 'marker' );
		}

		if ( ! hasMapFactory && ! hasMarkerFactory && ! hasMapClass && ! hasMarkerClass ) {
			return false;
		}

		if ( hasMapFactory && ! leafletApi.map.__directoristDirelMapListPatched ) {
			const originalMap = leafletApi.map;

			const patchedMap = function patchedDirectoristElementorLeafletMap( target, options ) {
				const map = originalMap.apply( this, arguments );
				registerLeafletMapContext( target, map );
				return map;
			};

			patchedMap.prototype = originalMap.prototype;
			Object.setPrototypeOf( patchedMap, originalMap );
			patchedMap.__directoristDirelMapListPatched = true;
			Object.defineProperty( leafletApi, 'map', {
				configurable: true,
				writable: true,
				value: patchedMap,
			} );
		}

		if ( hasMarkerFactory && ! leafletApi.marker.__directoristDirelMapListPatched ) {
			const originalMarker = leafletApi.marker;

			const patchedMarker = function patchedDirectoristElementorLeafletMarker() {
				const marker = originalMarker.apply( this, arguments );
				registerLeafletMarkerContext( marker );
				return marker;
			};

			patchedMarker.prototype = originalMarker.prototype;
			Object.setPrototypeOf( patchedMarker, originalMarker );
			patchedMarker.__directoristDirelMapListPatched = true;
			Object.defineProperty( leafletApi, 'marker', {
				configurable: true,
				writable: true,
				value: patchedMarker,
			} );
		}

		if ( hasMapClass && ! leafletApi.Map.prototype.__directoristDirelMapListPatched ) {
			const originalMapInitialize = leafletApi.Map.prototype.initialize;
			leafletApi.Map.prototype.initialize = function patchedDirectoristElementorLeafletMapInitialize( target, options ) {
				const result = originalMapInitialize.apply( this, arguments );
				registerLeafletMapContext( target, this );
				return result;
			};
			leafletApi.Map.prototype.__directoristDirelMapListPatched = true;
		}

		if ( hasMarkerClass && ! leafletApi.Marker.prototype.__directoristDirelMapListPatched ) {
			const originalMarkerInitialize = leafletApi.Marker.prototype.initialize;
			leafletApi.Marker.prototype.initialize = function patchedDirectoristElementorLeafletMarkerInitialize() {
				const result = originalMarkerInitialize.apply( this, arguments );
				registerLeafletMarkerContext( this );
				return result;
			};
			leafletApi.Marker.prototype.__directoristDirelMapListPatched = true;
		}

		leafletApi.__directoristDirelMapListPatched = true;
		return true;
	}

	function installLeafletGlobalInterceptor() {
		if ( leafletGlobalInterceptorInstalled ) {
			return;
		}

		leafletGlobalInterceptorInstalled = true;

		try {
			let currentLeafletApi = window.L;
			Object.defineProperty( window, 'L', {
				configurable: true,
				get() {
					return currentLeafletApi;
				},
				set( value ) {
					currentLeafletApi = value;
					patchLeafletApi( currentLeafletApi );
				},
			} );

			patchLeafletApi( currentLeafletApi );
		} catch ( error ) {
			patchLeafletApi( window.L );
		}
	}

	function installLeafletPatch() {
		return patchLeafletApi( window.L );
	}

	function installGooglePatch() {
		if (
			! window.google ||
			! window.google.maps ||
			! window.google.maps.Map ||
			window.google.maps.Map.__directoristDirelMapListPatched
		) {
			return !! (
				window.google &&
				window.google.maps &&
				window.google.maps.Map &&
				window.google.maps.Map.__directoristDirelMapListPatched
			);
		}

		const OriginalMap = window.google.maps.Map;

		function PatchedGoogleMap( element, options ) {
			const map = new OriginalMap( element, options );
			if ( getMapContextRoot( element ) ) {
				element.__directoristDirelGoogleMap = map;
			}
			return map;
		}

		PatchedGoogleMap.prototype = OriginalMap.prototype;
		Object.setPrototypeOf( PatchedGoogleMap, OriginalMap );
		PatchedGoogleMap.__directoristDirelMapListPatched = true;
		window.google.maps.Map = PatchedGoogleMap;

		return true;
	}

	function scheduleMapProviderPatchInstall() {
		installLeafletGlobalInterceptor();

		if ( ! leafletPatchTimer ) {
			leafletPatchAttempts = 0;
			leafletPatchTimer = window.setInterval( function() {
				leafletPatchAttempts += 1;
				installLeafletPatch();
				if ( leafletPatchAttempts >= 200 ) {
					window.clearInterval( leafletPatchTimer );
					leafletPatchTimer = null;
				}
			}, 50 );
		}

		if ( ! googlePatchTimer ) {
			googlePatchAttempts = 0;
			googlePatchTimer = window.setInterval( function() {
				googlePatchAttempts += 1;
				installGooglePatch();
				if ( googlePatchAttempts >= 200 ) {
					window.clearInterval( googlePatchTimer );
					googlePatchTimer = null;
				}
			}, 50 );
		}

		installLeafletPatch();
		installGooglePatch();
	}

	function resolveLeafletContext( mapList ) {
		const mapElement = mapList.querySelector( '#map, .directorist-map.leaflet-container, .leaflet-container' );
		if ( ! mapElement || ! mapElement.__directoristDirelLeafletMap ) {
			return null;
		}

		const markers = Array.isArray( mapElement.__directoristDirelLeafletMarkers )
			? mapElement.__directoristDirelLeafletMarkers
			: [];
		const markersByListingId = new Map();

		markers.forEach( function( marker ) {
			const popup = marker && marker.getPopup ? marker.getPopup() : null;
			const content = popup && popup.getContent ? popup.getContent() : '';
			const listingId = getListingIdFromMarkup( content );
			const latLng = marker && marker.getLatLng ? marker.getLatLng() : null;

			if ( listingId > 0 && marker && latLng && ! markersByListingId.has( listingId ) ) {
				markersByListingId.set( listingId, {
					marker,
					lat: parseFloat( latLng.lat ),
					lng: parseFloat( latLng.lng ),
				} );
			}
		} );

		const cards = parseJsonAttribute( mapElement, 'data-card', [] );
		cards.forEach( function( card, index ) {
			const listingId = getListingIdFromMarkup( card && card.content ? card.content : '' );
			if ( listingId <= 0 ) {
				return;
			}

			const existingMarkerData = markersByListingId.get( listingId );
			const marker = existingMarkerData && existingMarkerData.marker ? existingMarkerData.marker : ( markers[ index ] || null );
			const cardLat = parseFloat( card.latitude );
			const cardLng = parseFloat( card.longitude );
			markersByListingId.set( listingId, {
				marker,
				lat: Number.isFinite( cardLat ) ? cardLat : existingMarkerData && existingMarkerData.lat,
				lng: Number.isFinite( cardLng ) ? cardLng : existingMarkerData && existingMarkerData.lng,
			} );
		} );

		return {
			type: 'leaflet',
			map: mapElement.__directoristDirelLeafletMap,
			markersByListingId,
		};
	}

	function resolveGoogleContext( mapList ) {
		const mapElement = mapList.querySelector( '.atbdp-map' );
		if ( ! mapElement || ! mapElement.__directoristDirelGoogleMap ) {
			return null;
		}

		const markerElements = Array.from( mapElement.querySelectorAll( '.marker' ) );
		const markers = Array.isArray( mapElement.__directoristDirelGoogleMap.markers )
			? mapElement.__directoristDirelGoogleMap.markers
			: [];
		const markersByListingId = new Map();

		markerElements.forEach( function( markerElement, index ) {
			const listingId = toInt(
				markerElement.getAttribute( 'data-directorist-listing-id' ) ||
					markerElement.getAttribute( 'data-listing-id' ),
				0
			);
			const marker = markers[ index ];
			if ( listingId > 0 && marker ) {
				markersByListingId.set( listingId, {
					marker,
					lat: parseFloat( markerElement.getAttribute( 'data-latitude' ) ),
					lng: parseFloat( markerElement.getAttribute( 'data-longitude' ) ),
				} );
			}
		} );

		return {
			type: 'google',
			map: mapElement.__directoristDirelGoogleMap,
			markersByListingId,
		};
	}

	function resolveMapListContext( mapList ) {
		return resolveLeafletContext( mapList ) || resolveGoogleContext( mapList );
	}

	function getMapPopupElement( mapRoot ) {
		return mapRoot && mapRoot.querySelector
			? mapRoot.querySelector( '.leaflet-popup, .gm-style-iw-c' )
			: null;
	}

	function getMapPopupContentElement( mapRoot ) {
		return mapRoot && mapRoot.querySelector
			? mapRoot.querySelector( '.leaflet-popup-content, .gm-style-iw-d' )
			: null;
	}

	function applyMapPopupGeometry( mapRoot ) {
		const mapElement = getMapViewportElement( mapRoot );
		if ( ! mapElement || ! mapRoot || ! mapRoot.style ) {
			return null;
		}

		const mapRect = mapElement.getBoundingClientRect();
		if ( mapRect.width <= 0 || mapRect.height <= 0 ) {
			return null;
		}

		const defaultPreferredWidth = window.matchMedia( '(max-width: 767px)' ).matches ? 320 : 360;
		const availableWidth = Math.max( 1, Math.floor( mapRect.width - ( MAP_POPUP_HORIZONTAL_GUTTER * 2 ) ) );
		const availableHeight = Math.max( 1, Math.floor( mapRect.height - MAP_POPUP_VERTICAL_RESERVE ) );
		const popupContent = getMapPopupContentElement( mapRoot );
		const intrinsicWidth = popupContent ? Math.ceil( popupContent.scrollWidth ) : 0;
		const preferredWidth = Math.max( defaultPreferredWidth, intrinsicWidth );

		mapRoot.style.setProperty( '--direl-map-popup-width', `${ Math.min( preferredWidth, availableWidth ) }px` );
		mapRoot.style.setProperty( '--direl-map-popup-max-width', `${ availableWidth }px` );
		mapRoot.style.setProperty( '--direl-map-popup-max-height', `${ availableHeight }px` );

		return mapElement;
	}

	function panMapPopupIntoView( context, mapRoot ) {
		if ( ! context || ! context.map || ! mapRoot ) {
			return;
		}

		const mapElement = getMapViewportElement( mapRoot );
		const popupElement = getMapPopupElement( mapRoot );
		if ( ! mapElement || ! popupElement ) {
			return;
		}

		const mapRect = mapElement.getBoundingClientRect();
		const popupRect = popupElement.getBoundingClientRect();
		let offsetX = 0;
		let offsetY = 0;

		if ( popupRect.left < mapRect.left + MAP_POPUP_HORIZONTAL_GUTTER ) {
			offsetX = popupRect.left - mapRect.left - MAP_POPUP_HORIZONTAL_GUTTER;
		} else if ( popupRect.right > mapRect.right - MAP_POPUP_HORIZONTAL_GUTTER ) {
			offsetX = popupRect.right - mapRect.right + MAP_POPUP_HORIZONTAL_GUTTER;
		}

		if ( popupRect.top < mapRect.top + MAP_POPUP_HORIZONTAL_GUTTER ) {
			offsetY = popupRect.top - mapRect.top - MAP_POPUP_HORIZONTAL_GUTTER;
		} else if ( popupRect.bottom > mapRect.bottom - MAP_POPUP_HORIZONTAL_GUTTER ) {
			offsetY = popupRect.bottom - mapRect.bottom + MAP_POPUP_HORIZONTAL_GUTTER;
		}

		if ( 0 === offsetX && 0 === offsetY ) {
			return;
		}

		if ( 'leaflet' === context.type && context.map.panBy ) {
			context.map.panBy( [ offsetX, offsetY ], { animate: false } );
		} else if ( 'google' === context.type && context.map.panBy ) {
			context.map.panBy( offsetX, offsetY );
		}
	}

	function adjustMapPopupGeometry( mapRoot ) {
		if ( ! applyMapPopupGeometry( mapRoot ) || ! getMapPopupElement( mapRoot ) ) {
			return;
		}

		const context = resolveMapListContext( mapRoot );
		if ( ! context ) {
			return;
		}

		window.requestAnimationFrame( function() {
			panMapPopupIntoView( context, mapRoot );
		} );
	}

	function scheduleMapPopupAdjustment( mapRoot ) {
		if ( ! mapRoot ) {
			return;
		}

		window.clearTimeout( mapRoot.__directoristDirelMapPopupTimer );
		window.cancelAnimationFrame( mapRoot.__directoristDirelMapPopupFrame || 0 );
		mapRoot.__directoristDirelMapPopupFrame = window.requestAnimationFrame( function() {
			adjustMapPopupGeometry( mapRoot );
		} );
		mapRoot.__directoristDirelMapPopupTimer = window.setTimeout( function() {
			adjustMapPopupGeometry( mapRoot );
		}, 180 );
	}

	function bindMapViewportEvents( mapRoot, context ) {
		if ( ! mapRoot || ! context || ! context.map ) {
			return;
		}

		const previousBinding = mapRoot.__directoristDirelMapViewportBinding;
		if ( previousBinding && previousBinding.map === context.map ) {
			return;
		}

		if ( previousBinding && 'leaflet' === previousBinding.type && previousBinding.map && previousBinding.map.off ) {
			previousBinding.map.off( 'zoomend', previousBinding.handler );
			previousBinding.map.off( 'moveend', previousBinding.handler );
		} else if ( previousBinding && 'google' === previousBinding.type && previousBinding.listeners ) {
			previousBinding.listeners.forEach( function( listener ) {
				if ( listener && listener.remove ) {
					listener.remove();
				}
			} );
		}

		const handler = function() {
			scheduleMapPopupAdjustment( mapRoot );
		};
		if ( 'leaflet' === context.type && context.map.on ) {
			context.map.on( 'zoomend', handler );
			context.map.on( 'moveend', handler );
			mapRoot.__directoristDirelMapViewportBinding = {
				type: 'leaflet',
				map: context.map,
				handler,
			};
		} else if (
			'google' === context.type &&
			window.google &&
			window.google.maps &&
			window.google.maps.event &&
			window.google.maps.event.addListener
		) {
			mapRoot.__directoristDirelMapViewportBinding = {
				type: 'google',
				map: context.map,
				handler,
				listeners: [
					window.google.maps.event.addListener( context.map, 'zoom_changed', handler ),
					window.google.maps.event.addListener( context.map, 'idle', handler ),
				],
			};
		}
	}

	function captureMapPopupForViewportChange( mapRoot ) {
		const context = resolveMapListContext( mapRoot );
		const source = context && 'leaflet' === context.type && context.map && context.map._popup
			? context.map._popup._source
			: null;
		if ( ! source || ! source.openPopup ) {
			return;
		}

		mapRoot.__directoristDirelPendingPopupSource = source;
		window.clearTimeout( mapRoot.__directoristDirelPendingPopupCleanupTimer );
		mapRoot.__directoristDirelPendingPopupCleanupTimer = window.setTimeout( function() {
			mapRoot.__directoristDirelPendingPopupSource = null;
		}, 1200 );
	}

	function restoreMapPopupAfterViewportChange( mapRoot, context ) {
		const source = mapRoot ? mapRoot.__directoristDirelPendingPopupSource : null;
		if ( ! source || ! source.openPopup || ! context || 'leaflet' !== context.type ) {
			return;
		}

		if ( context.map && context.map._popup && context.map._popup._source === source ) {
			return;
		}

		const openPopup = function() {
			source.openPopup();
			scheduleMapPopupAdjustment( mapRoot );
		};
		const clusterGroup = source.__parent && source.__parent._group ? source.__parent._group : null;

		if ( source._map ) {
			openPopup();
		} else if ( clusterGroup && clusterGroup.zoomToShowLayer ) {
			clusterGroup.zoomToShowLayer( source, openPopup );
		}
	}

	function synchronizeMapViewport( mapRoot ) {
		if ( ! mapRoot || ! mapRoot.isConnected ) {
			return;
		}

		const context = resolveMapListContext( mapRoot );
		if ( ! context || ! context.map ) {
			applyMapPopupGeometry( mapRoot );
			mapRoot.__directoristDirelMapProviderReadyAttempts =
				( mapRoot.__directoristDirelMapProviderReadyAttempts || 0 ) + 1;
			if ( mapRoot.__directoristDirelMapProviderReadyAttempts <= 50 ) {
				window.clearTimeout( mapRoot.__directoristDirelMapProviderReadyTimer );
				mapRoot.__directoristDirelMapProviderReadyTimer = window.setTimeout( function() {
					synchronizeMapViewport( mapRoot );
				}, 100 );
			}
			return;
		}

		mapRoot.__directoristDirelMapProviderReadyAttempts = 0;
		window.clearTimeout( mapRoot.__directoristDirelMapProviderReadyTimer );
		bindMapViewportEvents( mapRoot, context );
		if ( context && 'leaflet' === context.type && context.map && context.map.invalidateSize ) {
			context.map.invalidateSize( { animate: false, pan: false } );
		} else if (
			context &&
			'google' === context.type &&
			window.google &&
			window.google.maps &&
			window.google.maps.event &&
			window.google.maps.event.trigger
		) {
			const center = context.map && context.map.getCenter ? context.map.getCenter() : null;
			window.google.maps.event.trigger( context.map, 'resize' );
			if ( center && context.map && context.map.setCenter ) {
				context.map.setCenter( center );
			}
		}

		restoreMapPopupAfterViewportChange( mapRoot, context );
		applyMapPopupGeometry( mapRoot );
		scheduleMapPopupAdjustment( mapRoot );
	}

	function scheduleMapViewportSynchronization( mapRoot ) {
		if ( ! mapRoot ) {
			return;
		}

		window.clearTimeout( mapRoot.__directoristDirelMapViewportTimer );
		window.cancelAnimationFrame( mapRoot.__directoristDirelMapViewportFrame || 0 );
		mapRoot.__directoristDirelMapViewportFrame = window.requestAnimationFrame( function() {
			synchronizeMapViewport( mapRoot );
		} );
		mapRoot.__directoristDirelMapViewportTimer = window.setTimeout( function() {
			synchronizeMapViewport( mapRoot );
		}, 180 );
	}

	function initializeMapPopupContainment( mapRoot ) {
		if ( ! mapRoot || mapRoot.__directoristDirelMapPopupBound ) {
			return;
		}

		mapRoot.__directoristDirelMapPopupBound = true;

		const observer = new window.MutationObserver( function( mutations ) {
			const popupChanged = mutations.some( function( mutation ) {
				const target = 1 === mutation.target.nodeType ? mutation.target : mutation.target.parentElement;
				if ( target && target.closest && target.closest( '.leaflet-popup, .gm-style-iw-c' ) ) {
					return true;
				}

				return Array.from( mutation.addedNodes || [] ).some( function( node ) {
					return 1 === node.nodeType && (
						node.matches( '.leaflet-popup, .gm-style-iw-c' ) ||
						( node.querySelector && node.querySelector( '.leaflet-popup, .gm-style-iw-c' ) )
					);
				} );
			} );

			if ( popupChanged ) {
				scheduleMapPopupAdjustment( mapRoot );
			}
		} );
		observer.observe( mapRoot, { childList: true, subtree: true } );
		mapRoot.__directoristDirelMapPopupObserver = observer;

		mapRoot.addEventListener( 'load', function( event ) {
			if ( event.target && event.target.closest && event.target.closest( '.leaflet-popup, .gm-style-iw-c' ) ) {
				scheduleMapPopupAdjustment( mapRoot );
			}
		}, true );

		mapRoot.addEventListener( 'click', function( event ) {
			if ( event.target && event.target.closest && event.target.closest( '#gmap_full_screen_button' ) ) {
				captureMapPopupForViewportChange( mapRoot );
				scheduleMapViewportSynchronization( mapRoot );
			}
		}, true );

		if ( 'function' === typeof window.ResizeObserver ) {
			const mapElement = getMapViewportElement( mapRoot );
			if ( mapElement ) {
				const resizeObserver = new window.ResizeObserver( function() {
					scheduleMapViewportSynchronization( mapRoot );
				} );
				resizeObserver.observe( mapElement );
				mapRoot.__directoristDirelMapPopupResizeObserver = resizeObserver;
				mapElement.addEventListener( 'fullscreenchange', function() {
					scheduleMapViewportSynchronization( mapRoot );
				} );
				mapElement.addEventListener( 'webkitfullscreenchange', function() {
					scheduleMapViewportSynchronization( mapRoot );
				} );
			}
		}

		applyMapPopupGeometry( mapRoot );
		scheduleMapViewportSynchronization( mapRoot );
	}

	function initializeMapPopupContainments( root = document ) {
		const scope = root && root.querySelectorAll ? root : document;
		const mapRoots = [];

		if ( scope.matches && scope.matches( MAP_POPUP_CONTEXT_SELECTOR ) ) {
			mapRoots.push( scope );
		}

		scope.querySelectorAll( MAP_POPUP_CONTEXT_SELECTOR ).forEach( function( mapRoot ) {
			if ( ! mapRoots.includes( mapRoot ) ) {
				mapRoots.push( mapRoot );
			}
		} );

		mapRoots.forEach( initializeMapPopupContainment );
	}

	function focusMapListMarker( context, markerData, zoomLevel, openPopup = true ) {
		if ( ! context || ! markerData ) {
			return;
		}

		if ( 'leaflet' === context.type ) {
			const marker = markerData.marker || null;
			const latLng = markerData.marker && markerData.marker.getLatLng
				? markerData.marker.getLatLng()
				: [ markerData.lat, markerData.lng ];
			const lat = Array.isArray( latLng ) ? parseFloat( latLng[ 0 ] ) : parseFloat( latLng.lat );
			const lng = Array.isArray( latLng ) ? parseFloat( latLng[ 1 ] ) : parseFloat( latLng.lng );

			if ( context.map.setView && Number.isFinite( lat ) && Number.isFinite( lng ) ) {
				context.map.setView( latLng, zoomLevel, { animate: true } );
			}

			if ( openPopup && marker && marker.openPopup ) {
				const openMarkerPopup = function() {
					marker.openPopup();
				};
				const clusterGroup = marker.__parent && marker.__parent._group
					? marker.__parent._group
					: null;

				if ( marker._map ) {
					openMarkerPopup();
				} else if ( clusterGroup && clusterGroup.zoomToShowLayer ) {
					clusterGroup.zoomToShowLayer( marker, openMarkerPopup );
				} else {
					window.setTimeout( openMarkerPopup, 350 );
				}
			}
			return;
		}

		if ( 'google' === context.type ) {
			const position = markerData.marker && markerData.marker.getPosition
				? markerData.marker.getPosition()
				: {
					lat: markerData.lat,
					lng: markerData.lng,
				};

			if ( position && context.map.panTo ) {
				context.map.panTo( position );
			}

			if ( context.map.setZoom ) {
				context.map.setZoom( zoomLevel );
			}

			if (
				openPopup &&
				markerData.marker &&
				window.google &&
				window.google.maps &&
				window.google.maps.event
			) {
				window.google.maps.event.trigger( markerData.marker, 'click' );
			}
		}
	}

	function adjustMapPreviewPopup( context, markerData, mapRoot ) {
		if ( ! context ) {
			return;
		}

		if ( 'leaflet' === context.type ) {
			if ( context.map && context.map.invalidateSize ) {
				context.map.invalidateSize();
			}

			const popup = markerData && markerData.marker && markerData.marker.getPopup
				? markerData.marker.getPopup()
				: null;

			if ( popup && popup.update ) {
				popup.update();
			}

			if ( popup && 'function' === typeof popup._adjustPan ) {
				popup._adjustPan();
			}

			if ( context.map && context.map.panBy && mapRoot ) {
				const mapElement = getMapViewportElement( mapRoot );
				const popupElement = mapRoot.querySelector( '.leaflet-popup' );

				if ( mapElement && popupElement ) {
					const mapRect = mapElement.getBoundingClientRect();
					const popupRect = popupElement.getBoundingClientRect();
					const padding = 24;
					let offsetX = 0;
					let offsetY = 0;

					if ( popupRect.left < mapRect.left + padding ) {
						offsetX = popupRect.left - mapRect.left - padding;
					} else if ( popupRect.right > mapRect.right - padding ) {
						offsetX = popupRect.right - mapRect.right + padding;
					}

					if ( popupRect.top < mapRect.top + padding ) {
						offsetY = popupRect.top - mapRect.top - padding;
					} else if ( popupRect.bottom > mapRect.bottom - padding ) {
						offsetY = popupRect.bottom - mapRect.bottom + padding;
					}

					if ( 0 !== offsetX || 0 !== offsetY ) {
						context.map.panBy( [ offsetX, offsetY ], { animate: false } );
					}
				}
			}

			return;
		}

		if (
			'google' === context.type &&
			window.google &&
			window.google.maps &&
			window.google.maps.event
		) {
			window.google.maps.event.trigger( context.map, 'resize' );

			if ( markerData && markerData.marker && markerData.marker.getPosition && context.map.panTo ) {
				context.map.panTo( markerData.marker.getPosition() );
			}

			if ( context.map.panBy && mapRoot ) {
				const mapElement = getMapViewportElement( mapRoot );
				const popupElement = mapRoot.querySelector( '.gm-style-iw-c' );

				if ( mapElement && popupElement ) {
					const mapRect = mapElement.getBoundingClientRect();
					const popupRect = popupElement.getBoundingClientRect();
					const padding = 24;
					let offsetX = 0;
					let offsetY = 0;

					if ( popupRect.left < mapRect.left + padding ) {
						offsetX = popupRect.left - mapRect.left - padding;
					} else if ( popupRect.right > mapRect.right - padding ) {
						offsetX = popupRect.right - mapRect.right + padding;
					}

					if ( popupRect.top < mapRect.top + padding ) {
						offsetY = popupRect.top - mapRect.top - padding;
					} else if ( popupRect.bottom > mapRect.bottom - padding ) {
						offsetY = popupRect.bottom - mapRect.bottom + padding;
					}

					if ( 0 !== offsetX || 0 !== offsetY ) {
						context.map.panBy( offsetX, offsetY );
					}
				}
			}
		}
	}

	function fitMapToCurrentListings( mapList, context ) {
		if ( ! context || '0' === mapList.getAttribute( 'data-fit-on-load' ) ) {
			return;
		}

		const listingIds = getCurrentMapListListingIds( mapList );
		const markerData = listingIds
			.map( function( listingId ) {
				return context.markersByListingId.get( listingId );
			} )
			.filter( Boolean )
			.filter( function( item ) {
				return Number.isFinite( item.lat ) && Number.isFinite( item.lng );
			} );

		if ( markerData.length < 1 ) {
			return;
		}

		if ( 'leaflet' === context.type && window.L && context.map.fitBounds ) {
			const bounds = window.L.latLngBounds(
				markerData.map( function( item ) {
					return [ item.lat, item.lng ];
				} )
			);
			context.map.fitBounds( bounds, {
				padding: [ 32, 32 ],
				maxZoom: toInt( mapList.getAttribute( 'data-hover-zoom' ), 14 ),
			} );
			return;
		}

		if (
			'google' === context.type &&
			window.google &&
			window.google.maps &&
			window.google.maps.LatLngBounds &&
			context.map.fitBounds
		) {
			const bounds = new window.google.maps.LatLngBounds();
			markerData.forEach( function( item ) {
				bounds.extend( {
					lat: item.lat,
					lng: item.lng,
				} );
			} );
			context.map.fitBounds( bounds );
		}
	}

	function bindMapListInteractions( mapList ) {
		if ( mapList.__directoristDirelMapListBound ) {
			return;
		}

		mapList.__directoristDirelMapListBound = true;
		let hoverTimer = null;

		const handleFocus = function( event ) {
			if ( '0' === mapList.getAttribute( 'data-hover-focus' ) ) {
				return;
			}

			const card = event.target.closest( LISTING_ID_SELECTOR );
			if ( ! card || ! mapList.contains( card ) ) {
				return;
			}

			const listPane = mapList.querySelector( '.directorist-elementor-map-list__list-pane' );
			if ( ! listPane || ! listPane.contains( card ) ) {
				return;
			}

			const listingId = toInt( card.getAttribute( 'data-directorist-listing-id' ), 0 );
			if ( listingId <= 0 ) {
				return;
			}

			window.clearTimeout( hoverTimer );
			hoverTimer = window.setTimeout( function() {
				const context = resolveMapListContext( mapList );
				if ( ! context ) {
					return;
				}

				const markerData = context.markersByListingId.get( listingId );
				if ( ! markerData ) {
					return;
				}

				mapList.querySelectorAll( '.directorist-elementor-map-list__is-active' ).forEach( function( element ) {
					element.classList.remove( 'directorist-elementor-map-list__is-active' );
				} );
				card.classList.add( 'directorist-elementor-map-list__is-active' );
				focusMapListMarker(
					context,
					markerData,
					toInt( mapList.getAttribute( 'data-hover-zoom' ), 14 ),
					'0' !== mapList.getAttribute( 'data-show-map-card' )
				);
			}, 80 );
		};

		mapList.addEventListener( 'mouseenter', handleFocus, true );
		mapList.addEventListener( 'focusin', handleFocus );
	}

	function initializeMapList( mapList ) {
		bindMapListInteractions( mapList );

		let attempts = 0;
		const fitWhenReady = function() {
			attempts += 1;
			const context = resolveMapListContext( mapList );
			if ( context ) {
				fitMapToCurrentListings( mapList, context );
				return;
			}

			if ( attempts < 40 ) {
				window.setTimeout( fitWhenReady, 150 );
			}
		};

		fitWhenReady();
	}

	function initializeMapLists( root = document ) {
		scheduleMapProviderPatchInstall();
		const scope = root && root.querySelectorAll ? root : document;
		scope.querySelectorAll( MAP_LIST_SELECTOR ).forEach( initializeMapList );
		initializeMapPopupContainments( scope );
	}

	function getMapPreviewMarkerData( context ) {
		if ( ! context || ! context.markersByListingId || ! context.markersByListingId.size ) {
			return null;
		}

		const markerItems = Array.from( context.markersByListingId.values() );

		return markerItems.find( function( item ) {
			return item && item.marker && Number.isFinite( item.lat ) && Number.isFinite( item.lng );
		} ) || markerItems.find( function( item ) {
			return item && Number.isFinite( item.lat ) && Number.isFinite( item.lng );
		} ) || null;
	}

	function initializeMapPreview( mapPreview ) {
		if ( ! mapPreview ) {
			return;
		}

		scheduleMapProviderPatchInstall();

		let attempts = 0;
		const openWhenReady = function() {
			attempts += 1;
			const context = resolveMapListContext( mapPreview );
			const markerData = getMapPreviewMarkerData( context );

			if ( context && markerData && markerData.marker ) {
				focusMapListMarker(
					context,
					markerData,
					toInt( mapPreview.getAttribute( 'data-preview-zoom' ), 14 ),
					true
				);
				adjustMapPreviewPopup( context, markerData, mapPreview );
				window.setTimeout( function() {
					adjustMapPreviewPopup( context, markerData, mapPreview );
				}, 120 );
				window.setTimeout( function() {
					adjustMapPreviewPopup( context, markerData, mapPreview );
				}, 360 );
				window.setTimeout( function() {
					adjustMapPreviewPopup( context, markerData, mapPreview );
				}, 900 );
				window.setTimeout( function() {
					adjustMapPreviewPopup( context, markerData, mapPreview );
				}, 1600 );
				return;
			}

			if ( attempts < 40 ) {
				window.setTimeout( openWhenReady, 150 );
			}
		};

		openWhenReady();
	}

	function initializeMapPreviews( root = document ) {
		scheduleMapProviderPatchInstall();
		const scope = root && root.querySelectorAll ? root : document;
		scope.querySelectorAll( MAP_PREVIEW_SELECTOR ).forEach( initializeMapPreview );
	}

	function scheduleMapPreviewRefresh( root = document ) {
		[ 80, 240, 600, 1200 ].forEach( function( delay ) {
			window.setTimeout( function() {
				initializeMapPreviews( root );
			}, delay );
		} );
	}

	function resolveDirectoryIdFromRequest( requestVars, $search ) {
		const requestedId = toInt( requestVars.directory_type_id || requestVars.directory_type, 0 );

		if ( requestedId > 0 ) {
			return requestedId;
		}

		const activeTabId = toInt(
			$search.find( '.directorist-type-nav__list__current .directorist-type-nav__link, .directorist-type-nav__link.active' ).first().attr( 'data-listing_type_id' ),
			0
		);

		if ( activeTabId > 0 ) {
			return activeTabId;
		}

		return toInt( $search.attr( 'data-default-directory-type-id' ), 0 );
	}

	function appendHomepageSearchContext( requestVars, $search ) {
		const directoryIds = parseCsvIds( $search.attr( 'data-directory-type-ids' ) );
		const defaultDirectoryId = toInt( $search.attr( 'data-default-directory-type-id' ), 0 );

		requestVars.directorist_home_search = '1';

		if ( directoryIds.length ) {
			requestVars.directory_type_ids = directoryIds.join( ',' );
		}

		if ( defaultDirectoryId > 0 ) {
			requestVars.default_directory_type_id = String( defaultDirectoryId );
		}

		if ( ! requestVars.directory_type && ! requestVars.directory_type_id ) {
			const activeTabId = toInt(
				$search.find( '.directorist-type-nav__list__current .directorist-type-nav__link, .directorist-type-nav__link.active' ).first().attr( 'data-listing_type_id' ),
				0
			);

			if ( activeTabId > 0 ) {
				requestVars.directory_type_id = String( activeTabId );
			}
		}

		return requestVars;
	}

	function stopNativeEvent( event ) {
		event.preventDefault();
		event.stopPropagation();
		if ( 'function' === typeof event.stopImmediatePropagation ) {
			event.stopImmediatePropagation();
		}
	}

	function getSearchModalScope( element ) {
		if ( ! element || ! element.closest ) {
			return null;
		}

		return element.closest( SEARCH_MODAL_SCOPE_SELECTOR );
	}

	function getSearchModalRoot( element ) {
		const contentsWrap = element && element.closest
			? element.closest( '.directorist-contents-wrap' )
			: null;

		if ( contentsWrap ) {
			return contentsWrap;
		}

		return getSearchModalScope( element );
	}

	function getModalForTrigger( trigger ) {
		const root = getSearchModalRoot( trigger );

		if ( ! root ) {
			return null;
		}

		if ( trigger.classList.contains( 'directorist-modal-btn--basic' ) ) {
			return root.querySelector( '.directorist-search-modal--basic' );
		}

		if ( trigger.classList.contains( 'directorist-modal-btn--advanced' ) ) {
			return root.querySelector( '.directorist-search-modal--advanced' );
		}

		if ( trigger.classList.contains( 'directorist-modal-btn--full' ) ) {
			return root.querySelector( '.directorist-search-modal--full' );
		}

		return null;
	}

	function openSearchModal( modal ) {
		if ( ! modal ) {
			return;
		}

		const modalOverlay = modal.querySelector( '.directorist-search-modal__overlay' );
		const modalContent = modal.querySelector( '.directorist-search-modal__contents' );
		const activeContent = document.querySelector( '.directorist-content-active' );
		const getCssVariable = ( element, variableName, fallback ) => {
			if ( ! element || typeof window === 'undefined' ) {
				return fallback;
			}

			const value = window
				.getComputedStyle( element )
				.getPropertyValue( variableName )
				.trim();

			return value || fallback;
		};

		if ( activeContent ) {
			activeContent.classList.add( 'directorist-overlay-active' );
		}

		modal.style.visibility = 'visible';

		if ( modalOverlay ) {
			const overlayOpacity = getCssVariable(
				modalOverlay,
				'--directorist-gbi-search-more-filters-modal-overlay-opacity',
				'1'
			);
			modalOverlay.style.cssText =
				`opacity: ${ overlayOpacity }; visibility: visible; transition: 0.3s ease;`;
		}

		if ( ! modalContent ) {
			return;
		}

		const modalOpacity = getCssVariable(
			modalContent,
			'--directorist-gbi-search-more-filters-modal-opacity',
			'1'
		);
		modalContent.style.cssText =
			`opacity: ${ modalOpacity }; visibility: visible; bottom: 50%; transform: translate(-50%, 50%)`;

		if ( document.body.offsetWidth < 576 ) {
			const bodyStyles = getComputedStyle( document.body );
			const bodyBackdropStyle = bodyStyles && bodyStyles.backdropFilter
				? bodyStyles.backdropFilter
				: '';

			if ( 'none' === bodyBackdropStyle || '' === bodyBackdropStyle ) {
				modalContent.style.cssText += 'bottom: 0; transform: translate(-50%, 0)';
			} else {
				modalContent.style.cssText += 'bottom: 50%; transform: translate(-50%, 50%)';
			}
		}
	}

	function closeSearchModal( modal ) {
		if ( ! modal ) {
			return;
		}

		const modalOverlay = modal.querySelector( '.directorist-search-modal__overlay' );
		const modalContent = modal.querySelector( '.directorist-search-modal__contents' );
		const activeContent = document.querySelector( '.directorist-content-active' );

		if ( activeContent ) {
			activeContent.classList.remove( 'directorist-overlay-active' );
		}

		modal.style.visibility = 'hidden';

		if ( modalOverlay ) {
			modalOverlay.style.cssText = 'opacity: 0; visibility: hidden';
		}

		if ( modalContent ) {
			modalContent.style.cssText = 'opacity: 0; visibility: hidden; bottom: -200px;';
		}
	}

	function minimizeSearchModal( modal ) {
		if ( ! modal ) {
			return;
		}

		const modalContent = modal.querySelector( '.directorist-search-modal__contents' );
		const modalMinimizer = modal.querySelector( '.directorist-search-modal__minimizer' );

		if ( ! modalContent || ! modalMinimizer ) {
			return;
		}

		if ( modalMinimizer.classList.contains( 'minimized' ) ) {
			modalMinimizer.classList.remove( 'minimized' );
			modalContent.style.bottom = '0';
			return;
		}

		modalMinimizer.classList.add( 'minimized' );
		modalContent.style.bottom = '-50%';
	}

	function installSearchModalController() {
		if ( window.directoristElementorSearchModalControllerInstalled ) {
			return;
		}

		window.directoristElementorSearchModalControllerInstalled = true;

		document.addEventListener( 'click', function( event ) {
			const target = event.target && event.target.closest
				? event.target
				: null;

			if ( ! target ) {
				return;
			}

			const modalTrigger = target.closest( '.directorist-modal-btn' );
			if ( modalTrigger && getSearchModalScope( modalTrigger ) ) {
				const modal = getModalForTrigger( modalTrigger );

				if ( ! modal ) {
					return;
				}

				stopNativeEvent( event );
				openSearchModal( modal );
				return;
			}

			const closeTrigger = target.closest( '.directorist-search-modal__contents__btn--close, .directorist-search-modal__overlay' );
			if ( closeTrigger && getSearchModalScope( closeTrigger ) ) {
				stopNativeEvent( event );
				closeSearchModal( closeTrigger.closest( '.directorist-search-modal' ) );
				return;
			}

			const minimizeTrigger = target.closest( '.directorist-search-modal__minimizer' );
			if ( minimizeTrigger && getSearchModalScope( minimizeTrigger ) ) {
				stopNativeEvent( event );
				minimizeSearchModal( minimizeTrigger.closest( '.directorist-search-modal' ) );
			}
		}, true );
	}

	function isHomepageSearchResultContext( $search ) {
		if ( '1' === String( $search.attr( 'data-home-search-result-context' ) || '' ) ) {
			return true;
		}

		try {
			return '0' !== String( new window.URL( window.location.href ).searchParams.get( 'directorist_home_search' ) || '0' );
		} catch ( error ) {
			return false;
		}
	}

	function findHomepageSearchLoop( element ) {
		const $currentLoop = $( element ).closest( `${ LOOP_SELECTOR }${ HOME_SEARCH_LOOP_SELECTOR }` );

		if ( $currentLoop.length ) {
			return $currentLoop;
		}

		return $( `${ LOOP_SELECTOR }${ HOME_SEARCH_LOOP_SELECTOR }` ).first();
	}

	function redirectHomepageSearch( form, $search ) {
		const searchElement = $search.get( 0 );
		if ( searchElement && '1' === searchElement.dataset.direlHomeSearchRouting ) {
			return;
		}

		if ( searchElement ) {
			searchElement.dataset.direlHomeSearchRouting = '1';
			window.setTimeout( function() {
				delete searchElement.dataset.direlHomeSearchRouting;
			}, 750 );
		}

		const targetUrl = String( $search.attr( 'data-home-search-result-url' ) || config.homeSearchResultUrl || '' );
		const requestVars = appendHomepageSearchContext( collectHomepageSearchRequestVars( $search, form ), $search );

		delete requestVars.paged;

		let url;

		try {
			url = new window.URL( targetUrl || window.location.href, window.location.origin );
		} catch ( error ) {
			url = new window.URL( window.location.href );
		}

		Object.keys( requestVars ).forEach( function( key ) {
			const value = requestVars[ key ];

			url.searchParams.delete( key );

			if ( Array.isArray( value ) ) {
				value.forEach( function( item ) {
					url.searchParams.append( key, item );
				} );
				return;
			}

			if ( undefined !== value && null !== value && '' !== String( value ) ) {
				url.searchParams.set( key, value );
			}
		} );

		openHomepageSearchResult( url.toString() );
	}

	function openHomepageSearchResult( url ) {
		if ( ! url ) {
			return;
		}

		if ( document.body ) {
			const link = document.createElement( 'a' );
			link.href = url;
			link.target = '_blank';
			link.rel = 'noopener noreferrer';
			link.style.display = 'none';
			document.body.appendChild( link );
			link.click();
			window.setTimeout( function() {
				link.remove();
			}, 0 );
			return;
		}

		const openedWindow = window.open( url, '_blank', 'noopener' );
		if ( openedWindow ) {
			try {
				openedWindow.opener = null;
			} catch ( error ) {}
		}
	}

	function replaceHomepageSearchMarkup( $target, markup ) {
		if ( ! $target.length || 'string' !== typeof markup || '' === markup.trim() ) {
			return $();
		}

		const $replacement = $( markup.trim() ).first();
		if ( ! $replacement.length ) {
			return $();
		}

		$target.replaceWith( $replacement );

		return $replacement;
	}

	function refreshStandaloneHomepageSearchFields( $search, trigger ) {
		if ( ! $search.length || ! config.ajaxUrl || ! config.nonce ) {
			return;
		}

		const $link = $( trigger );
		const activeDirectoryId = toInt( $link.attr( 'data-listing_type_id' ), 0 );
		const directorySlug = String( $link.attr( 'data-listing_type' ) || '' );
		const directoryType = directorySlug || extractUrlQueryValue( $link.attr( 'href' ), 'directory_type' ) || 'all';
		const data = {
			action: config.homeSearchFormAction || 'directorist_elementor_v4_render_homepage_search_form',
			nonce: config.nonce,
			directory_type: directoryType,
			directory_type_ids: String( $search.attr( 'data-directory-type-ids' ) || '' ),
			default_directory_type_id: String( $search.attr( 'data-default-directory-type-id' ) || '' ),
			post_id: String( $search.attr( 'data-home-search-post-id' ) || '' ),
			widget_id: String( $search.attr( 'data-home-search-widget-id' ) || '' ),
		};

		if ( activeDirectoryId > 0 ) {
			data.directory_type_id = activeDirectoryId;
		}

		const searchElement = $search.get( 0 );
		const previousRequest = searchElement
			? standaloneHomepageSearchRequests.get( searchElement )
			: null;
		if ( previousRequest && 4 !== previousRequest.readyState ) {
			previousRequest.abort();
		}

		$search.addClass( 'atbdp-form-fade' );

		const request = $.ajax( {
			url: config.ajaxUrl,
			type: 'POST',
			dataType: 'json',
			data,
		} );

		if ( searchElement ) {
			standaloneHomepageSearchRequests.set( searchElement, request );
		}

		request.done( function( response ) {
			if ( ! response || ! response.success || ! response.data ) {
				return;
			}

			replaceHomepageSearchMarkup(
				$search.find( '.directorist-elementor-listings-search__form, .directorist-elementor-listings-archive-search-form' ).first(),
				response.data.form
			);

			$search.find( "input[name='directory_type']" ).val( directoryType );
			window.dispatchEvent( new window.CustomEvent( 'directorist-search-form-nav-tab-reloaded' ) );
			window.dispatchEvent( new window.CustomEvent( 'directorist-instant-search-reloaded' ) );
			document.body.dispatchEvent( new window.CustomEvent( 'directorist-reload-select2-fields' ) );
		} ).always( function() {
			if ( ! searchElement || standaloneHomepageSearchRequests.get( searchElement ) === request ) {
				if ( searchElement ) {
					standaloneHomepageSearchRequests.delete( searchElement );
				}
				$search.removeClass( 'atbdp-form-fade' );
			}
		} );
	}

	function activateHomepageSearchDirectory( trigger ) {
		const $link = $( trigger );
		const $search = getHomepageSearchRoot( trigger );

		if ( ! $search.length ) {
			return;
		}

		const activeDirectoryId = toInt( $link.attr( 'data-listing_type_id' ), 0 );
			const directorySlug = String( $link.attr( 'data-listing_type' ) || '' );
			const directoryType = directorySlug || extractUrlQueryValue( $link.attr( 'href' ), 'directory_type' ) || 'all';

			const $nav = $link.closest( '.directorist-type-nav' );
			$nav
				.find( '.directorist-type-nav__list__current, li.current' )
				.removeClass( 'directorist-type-nav__list__current current' );
			$link
				.closest( 'li' )
				.addClass( 'directorist-type-nav__list__current current' );
			$search.find( "input[name='directory_type']" ).val( directoryType );

		if ( ! isHomepageSearchResultContext( $search ) ) {
			refreshStandaloneHomepageSearchFields( $search, trigger );
			return;
		}

		const form = $search.find( 'form.directorist-search-form, form.directorist-basic-search, form.directorist-advanced-search' ).get( 0 );
		const $loop = findHomepageSearchLoop( trigger );

		if ( ! form || ! $loop.length ) {
			return;
		}

		resetHomepageSearchFormForDirectoryChange( $search, directoryType );

		const requestVars = appendHomepageSearchContext( collectHomepageSearchRequestVars( $search, form ), $search );
		requestVars.directory_type = directoryType;
		requestVars.paged = 1;

		requestLoopRender( $loop, {
			activeDirectoryId,
			requestVars,
		} );
	}

	function extractUrlQueryValue( url, key ) {
		if ( ! url ) {
			return '';
		}

		try {
			const parsedUrl = new window.URL( url, window.location.origin );

			return String( parsedUrl.searchParams.get( key ) || '' );
		} catch ( error ) {
			return '';
		}
	}

	function resolveCurrentSortValue( $loop ) {
		if ( ! $loop.length ) {
			return '';
		}

		const $activeSortLink = $loop
			.find( '.directorist-sortby-dropdown .directorist-dropdown__links__single-js.active' )
			.first();
		const activeSortValue = normalizeRequestScalarValue(
			extractUrlQueryValue( $activeSortLink.attr( 'data-link' ), 'sort' ) ||
			extractUrlQueryValue( $activeSortLink.attr( 'href' ), 'sort' )
		);

		if ( '' !== activeSortValue ) {
			return activeSortValue;
		}

		return normalizeRequestScalarValue(
			$loop.find( "input[name='sort']" ).first().val()
		);
	}

	function closeSortDropdowns( root = document, except = null ) {
		const scope = root && root.querySelectorAll ? root : document;

		scope.querySelectorAll( `${ LOOP_SELECTOR } .directorist-sortby-dropdown .directorist-dropdown__links-js` ).forEach( function( links ) {
			if ( links !== except ) {
				links.style.display = 'none';
			}
		} );
	}

	function toggleSortDropdown( toggle ) {
		const dropdown = toggle && toggle.closest
			? toggle.closest( '.directorist-sortby-dropdown' )
			: null;
		const loop = dropdown && dropdown.closest
			? dropdown.closest( LOOP_SELECTOR )
			: null;
		const links = dropdown ? dropdown.querySelector( '.directorist-dropdown__links-js' ) : null;

		if ( ! loop || ! links ) {
			return;
		}

		const isHidden = 'none' === window.getComputedStyle( links ).display;

		closeSortDropdowns( loop, links );
		links.style.display = isHidden ? 'block' : 'none';
	}

	function syncDirectoryTypeInputs( $loop, directoryType ) {
		if ( ! $loop.length ) {
			return;
		}

		const normalizedDirectoryType = String( directoryType || 'all' );

		$loop.find( "input[name='directory_type']" ).val( normalizedDirectoryType );
	}

	function resetLoopFormsForDirectoryChange( $loop, directoryType ) {
		if ( ! $loop.length ) {
			return;
		}

		markLoopResetting( $loop );

		$loop.find( 'form.directorist-search-form, form.directorist-advanced-filter__form' ).each( function() {
			resetLoopForm( $( this ) );
		} );

		syncDirectoryTypeInputs( $loop, directoryType );
	}

	function resetHomepageSearchFormForDirectoryChange( $search, directoryType ) {
		if ( ! $search.length ) {
			return;
		}

		$search.find( 'form.directorist-search-form, form.directorist-basic-search, form.directorist-advanced-search' ).each( function() {
			resetLoopForm( $( this ) );
		} );

		$search.find( "input[name='directory_type']" ).val( String( directoryType || 'all' ) );
	}

	function markLoopResetting( $loop ) {
		const loopElement = $loop && $loop.get ? $loop.get( 0 ) : null;

		if ( ! loopElement ) {
			return;
		}

		loopElement.dataset.direlResetting = 'true';

		window.clearTimeout( loopElement.direlResettingTimer );
		loopElement.direlResettingTimer = window.setTimeout( function() {
			delete loopElement.dataset.direlResetting;
			delete loopElement.direlResettingTimer;
		}, 500 );
	}

	function resolveFloatingFilterRoot( element ) {
		const $loop = getLoopRoot( element );

		if ( ! $loop.length ) {
			return null;
		}

		return $loop
			.find( '.directorist-elementor-listings-filters.directorist-gbi-filters-mobile-floating' )
			.get( 0 ) || null;
	}

	function openFloatingFilter( floatingRoot ) {
		if ( ! floatingRoot ) {
			return;
		}

		floatingRoot.classList.add( 'is-mobile-floating-open' );
	}

	function closeFloatingFilter( floatingRoot ) {
		if ( ! floatingRoot ) {
			return;
		}

		floatingRoot.classList.remove( 'is-mobile-floating-open' );
	}

	function closeAllFloatingFilters() {
		document.querySelectorAll(
			`${ LOOP_SELECTOR } .directorist-elementor-listings-filters.directorist-gbi-filters-mobile-floating.is-mobile-floating-open`
		).forEach( closeFloatingFilter );
	}

	function isFormResetting( element ) {
		const formElement = element instanceof window.Element ? element.closest( 'form' ) : null;
		const loopElement = element instanceof window.Element ? element.closest( LOOP_SELECTOR ) : null;

		return !! (
			( formElement && 'true' === formElement.dataset.direlResetting ) ||
			( loopElement && 'true' === loopElement.dataset.direlResetting )
		);
	}

	function requestLoopFirstPage( element ) {
		const $loop = getLoopRoot( element );

		if ( ! $loop.length ) {
			return;
		}

		requestLoopRender( $loop, {
			requestVars: {
				paged: 1,
			},
		} );
	}

	function setInfinitePaginationLoadingState( $loop, isLoading ) {
		const $pagination = $loop
			.find( '.directorist-elementor-listings-pagination[data-pagination-mode="infinite_scroll"]' )
			.first();

		if ( ! $pagination.length ) {
			return;
		}

		$pagination.toggleClass( 'is-loading', !! isLoading );

		const $loadingTemplate = $pagination
			.find( '.directorist-elementor-listings-pagination__loading-template' )
			.first();

		if ( ! $loadingTemplate.length ) {
			return;
		}

		if ( isLoading ) {
			$loadingTemplate.removeAttr( 'hidden' );
			return;
		}

		$loadingTemplate.attr( 'hidden', 'hidden' );
	}

	function getInfiniteAppendTarget( $loop ) {
		return $loop.find( '.directorist-elementor-loop__cards' ).first();
	}

	function shouldAutoLoadInfinitePage( $loop ) {
		if ( ! $loop.length || $loop.hasClass( 'directorist-elementor-loop--loading' ) ) {
			return false;
		}

		const $pagination = $loop
			.find( '.directorist-elementor-listings-pagination[data-pagination-mode="infinite_scroll"]' )
			.first();

		if ( ! $pagination.length || $pagination.hasClass( 'is-loading' ) ) {
			return false;
		}

		if ( ! getInfiniteAppendTarget( $loop ).length ) {
			return false;
		}

		return toInt( $loop.attr( 'data-current-page' ), 1 ) < toInt( $loop.attr( 'data-max-pages' ), 1 );
	}

	function handleInfiniteScroll() {
		const scrollBottom = window.scrollY + window.innerHeight;

		$( LOOP_SELECTOR ).each( function() {
			const $loop = $( this );

			if ( ! shouldAutoLoadInfinitePage( $loop ) ) {
				return;
			}

			const $appendTarget = getInfiniteAppendTarget( $loop );

			if ( ! $appendTarget.length ) {
				return;
			}

			const targetBottom = $appendTarget.offset().top + $appendTarget.outerHeight();

			if ( scrollBottom < targetBottom ) {
				return;
			}

			requestLoopRender( $loop, {
				appendMode: true,
				requestVars: {
					paged: Math.max( 1, toInt( $loop.attr( 'data-current-page' ), 1 ) + 1 ),
				},
			} );
		} );
	}

	function scheduleInfiniteScrollCheck() {
		if ( infiniteScrollCheckScheduled ) {
			return;
		}

		infiniteScrollCheckScheduled = true;
		( window.requestAnimationFrame || window.setTimeout )( function() {
			infiniteScrollCheckScheduled = false;
			handleInfiniteScroll();
		}, 16 );
	}

	function buildState( $loop, overrides = {} ) {
		const requestVars = Object.assign( {}, collectLoopRequestVars( $loop ), overrides.requestVars || {} );
		const activeView = String(
			overrides.activeView || $loop.attr( 'data-direl-view' ) || 'grid'
		);
		const currentSort = normalizeRequestScalarValue(
			overrides.sort || requestVars.sort || resolveCurrentSortValue( $loop )
		);

		if ( '' !== activeView ) {
			requestVars.view = activeView;
		}

		if ( '' !== currentSort ) {
			requestVars.sort = currentSort;
		} else {
			delete requestVars.sort;
		}

		const authorId = toInt( $loop.attr( 'data-direl-author' ), 0 );
		if ( authorId > 0 && ! requestVars.author_id ) {
			requestVars.author_id = authorId;
		}

		return {
			requestVars,
			activeDirectoryId: toInt(
				overrides.activeDirectoryId !== undefined ? overrides.activeDirectoryId : $loop.attr( 'data-direl-directory' ),
				0
			),
			activeView,
			appendMode: !! overrides.appendMode,
		};
	}

	function applyLoopAttributes( $currentLoop, $newLoop ) {
		[ 'class', 'style', 'data-atts', 'data-direl-view', 'data-direl-directory', 'data-direl-author', 'data-display-mode', 'data-pagination-type', 'data-listings-count', 'data-current-page', 'data-max-pages', 'data-direl-instance', 'data-direl-post-id', 'data-direl-request-post-id', 'data-map-list-position', 'data-map-list-map-height', 'data-map-list-map-width', 'data-map-list-sticky-map', 'data-map-list-fit-on-load', 'data-map-list-hover-focus', 'data-map-list-hover-zoom', 'data-map-list-show-map-card' ].forEach( function( attributeName ) {
			const attributeValue = $newLoop.attr( attributeName );

			if ( undefined === attributeValue ) {
				$currentLoop.removeAttr( attributeName );
				return;
			}

			$currentLoop.attr( attributeName, attributeValue );
		} );
	}

	function parseLoopMarkup( html ) {
		const $markup = $( $.parseHTML( html, document, true ) );

		return $markup.filter( LOOP_SELECTOR ).add( $markup.find( LOOP_SELECTOR ) ).first();
	}

	function applyRenderedLoop( $loop, html, appendMode ) {
		const $newLoop = parseLoopMarkup( html );

		if ( ! $newLoop.length ) {
			return;
		}

		if ( appendMode ) {
			const $currentCards = $loop.find( '.directorist-elementor-loop__cards' ).first();
			const $newCards = $newLoop.find( '.directorist-elementor-loop__cards' ).first();

			if ( $currentCards.length && $newCards.length ) {
				$currentCards.append( $newCards.children( '.directorist-elementor-loop__item' ) );
				applyLoopAttributes( $loop, $newLoop );

				const $currentPagination = $loop.find( '.directorist-elementor-listings-pagination' ).first();
				const $newPagination = $newLoop.find( '.directorist-elementor-listings-pagination' ).first();

				if ( $currentPagination.length && $newPagination.length ) {
					$currentPagination.replaceWith( $newPagination );
				} else if ( $currentPagination.length && ! $newPagination.length ) {
					$currentPagination.remove();
				}

				window.dispatchEvent( new window.CustomEvent( 'directorist-instant-search-reloaded' ) );
				window.dispatchEvent( new window.CustomEvent( 'directorist-reload-listings-map-archive' ) );
				document.body.dispatchEvent( new window.CustomEvent( 'directorist-reload-select2-fields' ) );
				initializeMapLists( $loop.get( 0 ) || document );
				scheduleInfiniteScrollCheck();

				return;
			}
		}

		$loop.replaceWith( $newLoop );
		window.dispatchEvent( new window.CustomEvent( 'directorist-instant-search-reloaded' ) );
		window.dispatchEvent( new window.CustomEvent( 'directorist-reload-listings-map-archive' ) );
		document.body.dispatchEvent( new window.CustomEvent( 'directorist-reload-select2-fields' ) );
		initializeMapLists( $newLoop.get( 0 ) || document );
		scheduleInfiniteScrollCheck();
	}

	function setLoopLoadingState( $loop, isLoading ) {
		$loop.toggleClass( 'directorist-elementor-loop--loading', !! isLoading );
	}

	function requestLoopRender( $loop, overrides = {} ) {
		if ( ! $loop.length || ! config.ajaxUrl || ! config.action || ! config.nonce ) {
			return;
		}

		const loopId = String( $loop.attr( 'data-direl-loop-id' ) || '' );
		const state = buildState( $loop, overrides );
		const payload = {
			postId: toInt( $loop.attr( 'data-direl-post-id' ) || config.currentDocumentId || config.currentPostId, 0 ),
			requestPostId: toInt( $loop.attr( 'data-direl-request-post-id' ) || config.currentRequestPostId || config.currentPostId, 0 ),
			loopId,
			state,
		};

		if ( pendingRequests.has( loopId ) ) {
			pendingRequests.get( loopId ).abort();
		}

		setLoopLoadingState( $loop, true );
		setInfinitePaginationLoadingState( $loop, !! state.appendMode );

		const xhr = $.ajax( {
			url: config.ajaxUrl,
			type: 'POST',
			dataType: 'json',
			data: {
				action: config.action,
				nonce: config.nonce,
				payload: JSON.stringify( payload ),
			},
		} ).done( function( response ) {
			if ( ! response || ! response.success || ! response.data || ! response.data.html ) {
				return;
			}

			applyRenderedLoop( $loop, response.data.html, !! state.appendMode );
		} ).always( function() {
			pendingRequests.delete( loopId );
			setLoopLoadingState( $loop, false );
			setInfinitePaginationLoadingState( $loop, false );
		} );

		pendingRequests.set( loopId, xhr );
	}

	function resetLoopForm( $form ) {
		const formElement = $form.get( 0 );

		if ( ! formElement ) {
			return;
		}

		formElement.dataset.direlResetting = 'true';
		formElement.reset();

		$form.find( 'select' ).each( function() {
			if ( $( this ).data( 'select2' ) ) {
				$( this ).val( null ).trigger( 'change' );
				return;
			}

			this.selectedIndex = 0;
		} );

		$form
			.find( "input[type='text'], input[type='search'], input[type='number'], input[type='url'], input[type='date'], input[type='time'], textarea" )
			.val( '' );

		$form
			.find( "input[type='hidden']" )
			.not( "[name='directory_type'], [name='radius-search-based-on']" )
			.val( '' );

		$form
			.find( "input[type='checkbox'], input[type='radio']" )
			.prop( 'checked', false );

		window.setTimeout( function() {
			delete formElement.dataset.direlResetting;
		}, 0 );
	}

	installSearchModalController();

	if ( 'loading' === document.readyState ) {
		initializeMapLists();
		document.addEventListener( 'DOMContentLoaded', function() {
			initializeMapLists();
		} );
	} else {
		initializeMapLists();
	}

	document.addEventListener( 'submit', function( event ) {
		const form = event.target instanceof window.Element
			? event.target.closest( HOME_SEARCH_FORM_SELECTOR )
			: null;

		if ( ! form ) {
			return;
		}

		const $search = getHomepageSearchRoot( form );

		if ( ! $search.length ) {
			return;
		}

		event.preventDefault();
		event.stopPropagation();

		if ( 'function' === typeof event.stopImmediatePropagation ) {
			event.stopImmediatePropagation();
		}

		if ( ! isHomepageSearchResultContext( $search ) ) {
			redirectHomepageSearch( form, $search );
			return;
		}

		const $loop = findHomepageSearchLoop( form );

		if ( ! $loop.length ) {
			return;
		}

		const requestVars = appendHomepageSearchContext( collectHomepageSearchRequestVars( $search, form ), $search );
		requestVars.paged = 1;

		requestLoopRender( $loop, {
			activeDirectoryId: resolveDirectoryIdFromRequest( requestVars, $search ),
			requestVars,
		} );
	}, true );

	document.addEventListener( 'click', function( event ) {
		const submitTrigger = event.target instanceof window.Element
			? event.target.closest(
				`${ HOME_SEARCH_SELECTOR } button[type="submit"], ${ HOME_SEARCH_SELECTOR } input[type="submit"], ${ HOME_SEARCH_SELECTOR } .directorist-search-form-action__submit, ${ HOME_SEARCH_SELECTOR } .directorist-btn-search, ${ HOME_SEARCH_SELECTOR } .directorist-advanced-filter__action .directorist-btn-submit`
			)
			: null;

		if ( ! submitTrigger ) {
			return;
		}

		const form = submitTrigger.closest( HOME_SEARCH_FORM_SELECTOR );
		const $search = getHomepageSearchRoot( submitTrigger );

		if ( ! form || ! $search.length || isHomepageSearchResultContext( $search ) ) {
			return;
		}

		stopNativeEvent( event );
		redirectHomepageSearch( form, $search );
	}, true );

	$( document ).on( 'submit', LOOP_FORM_SELECTOR, function( event ) {
		event.preventDefault();
		requestLoopRender( getLoopRoot( this ), {
			requestVars: {
				paged: 1,
			},
		} );
	} );

	$( document ).on(
		'keyup',
		`${ LOOP_SELECTOR } .directorist-search-form input, ${ LOOP_SELECTOR } .directorist-basic-search input, ${ LOOP_SELECTOR } .directorist-advanced-search input, ${ LOOP_SELECTOR } .directorist-advanced-filter__form input`,
		debounce( function( event ) {
			if (
				isFormResetting( this ) ||
				$( event.target ).closest( '.directorist-custom-range-slider__value' ).length > 0 ||
				( 'Enter' === event.key && '' === String( event.target.value || '' ).trim() )
			) {
				return;
			}

			event.preventDefault();
			requestLoopFirstPage( this );
		}, 250 )
	);

	$( document ).on(
		'change',
		`${ LOOP_SELECTOR } .directorist-search-form input[type='checkbox'], ${ LOOP_SELECTOR } .directorist-search-form input[type='radio'], ${ LOOP_SELECTOR } .directorist-search-form input[type='time'], ${ LOOP_SELECTOR } .directorist-search-form input[type='date'], ${ LOOP_SELECTOR } .directorist-search-form .directorist-custom-range-slider__wrap .directorist-custom-range-slider__range, ${ LOOP_SELECTOR } .directorist-search-form .directorist-search-location .location-name, ${ LOOP_SELECTOR } .directorist-basic-search input[type='checkbox'], ${ LOOP_SELECTOR } .directorist-basic-search input[type='radio'], ${ LOOP_SELECTOR } .directorist-basic-search input[type='time'], ${ LOOP_SELECTOR } .directorist-basic-search input[type='date'], ${ LOOP_SELECTOR } .directorist-basic-search .directorist-custom-range-slider__wrap .directorist-custom-range-slider__range, ${ LOOP_SELECTOR } .directorist-basic-search .directorist-search-location .location-name, ${ LOOP_SELECTOR } .directorist-advanced-search input[type='checkbox'], ${ LOOP_SELECTOR } .directorist-advanced-search input[type='radio'], ${ LOOP_SELECTOR } .directorist-advanced-search input[type='time'], ${ LOOP_SELECTOR } .directorist-advanced-search input[type='date'], ${ LOOP_SELECTOR } .directorist-advanced-search .directorist-custom-range-slider__wrap .directorist-custom-range-slider__range, ${ LOOP_SELECTOR } .directorist-advanced-search .directorist-search-location .location-name, ${ LOOP_SELECTOR } .directorist-advanced-filter__form input[type='checkbox'], ${ LOOP_SELECTOR } .directorist-advanced-filter__form input[type='radio'], ${ LOOP_SELECTOR } .directorist-advanced-filter__form input[type='time'], ${ LOOP_SELECTOR } .directorist-advanced-filter__form input[type='date'], ${ LOOP_SELECTOR } .directorist-advanced-filter__form .directorist-custom-range-slider__wrap .directorist-custom-range-slider__range, ${ LOOP_SELECTOR } .directorist-advanced-filter__form .directorist-search-location .location-name`,
		debounce( function( event ) {
			if ( isFormResetting( this ) ) {
				return;
			}

			event.preventDefault();
			requestLoopFirstPage( this );
		}, 250 )
	);

	$( document ).on(
		'change',
		`${ LOOP_SELECTOR } .directorist-search-form .directorist-search-location, ${ LOOP_SELECTOR } .directorist-search-form .directorist-zipcode-search, ${ LOOP_SELECTOR } .directorist-basic-search .directorist-search-location, ${ LOOP_SELECTOR } .directorist-basic-search .directorist-zipcode-search, ${ LOOP_SELECTOR } .directorist-advanced-search .directorist-search-location, ${ LOOP_SELECTOR } .directorist-advanced-search .directorist-zipcode-search, ${ LOOP_SELECTOR } .directorist-advanced-filter__form .directorist-search-location, ${ LOOP_SELECTOR } .directorist-advanced-filter__form .directorist-zipcode-search`,
		debounce( function( event ) {
			if ( isFormResetting( this ) ) {
				return;
			}

			if ( $( this ).hasClass( 'directorist-search-location' ) ) {
				const $locationField = $( this ).find( "input[name='address']" );

				if ( ! String( $locationField.val() || '' ).trim() ) {
					return;
				}
			}

			event.preventDefault();
			requestLoopFirstPage( this );
		}, 250 )
	);

	$( document ).on(
		'change',
		`${ LOOP_SELECTOR } .directorist-search-form select, ${ LOOP_SELECTOR } .directorist-basic-search select, ${ LOOP_SELECTOR } .directorist-advanced-search select, ${ LOOP_SELECTOR } .directorist-advanced-filter__form select`,
		debounce( function( event ) {
			if ( isFormResetting( this ) ) {
				return;
			}

			event.preventDefault();
			requestLoopFirstPage( this );
		}, 250 )
	);

	window.addEventListener(
		'directorist-color-changed',
		debounce( function( event ) {
			const input = event && event.detail ? event.detail.input : null;

			if ( ! input || ! getLoopRoot( input ).length || isFormResetting( input ) ) {
				return;
			}

			requestLoopFirstPage( input );
		}, 250 )
	);

	$( document ).on(
		'click',
		`${ LOOP_SELECTOR } .directorist-search-form .directorist-filter-location-icon, ${ LOOP_SELECTOR } .directorist-basic-search .directorist-filter-location-icon, ${ LOOP_SELECTOR } .directorist-advanced-search .directorist-filter-location-icon, ${ LOOP_SELECTOR } .directorist-advanced-filter__form .directorist-filter-location-icon`,
		debounce( function( event ) {
			if ( isFormResetting( this ) ) {
				return;
			}

			event.preventDefault();
			requestLoopFirstPage( this );
		}, 1000 )
	);

	$( document ).on(
		'click',
		`${ LOOP_SELECTOR } .directorist-search-form .directorist-search-field__btn--clear, ${ LOOP_SELECTOR } .directorist-basic-search .directorist-search-field__btn--clear, ${ LOOP_SELECTOR } .directorist-advanced-search .directorist-search-field__btn--clear, ${ LOOP_SELECTOR } .directorist-advanced-filter__form .directorist-search-field__btn--clear`,
		function() {
			const trigger = this;

			if ( isFormResetting( trigger ) ) {
				return;
			}

			window.setTimeout( function() {
				requestLoopFirstPage( trigger );
			}, 100 );
		}
	);

	$( document ).on( 'click', `${ HOME_SEARCH_SELECTOR } .directorist-type-nav__link, ${ HOME_SEARCH_SELECTOR } .search_listing_types`, function( event ) {
		event.preventDefault();
		event.stopPropagation();

		if ( 'function' === typeof event.stopImmediatePropagation ) {
			event.stopImmediatePropagation();
		}

		activateHomepageSearchDirectory( this );
	} );

	$( document ).on( 'click', `${ LOOP_SELECTOR } .directorist-viewas__item`, function( event ) {
		event.preventDefault();

		const view = extractUrlQueryValue( $( this ).attr( 'href' ), 'view' ) || 'grid';

		requestLoopRender( getLoopRoot( this ), {
			activeView: view,
			requestVars: {
				view,
				paged: 1,
			},
		} );
	} );

	document.addEventListener( 'click', function( event ) {
		const toggle = event.target && event.target.closest
			? event.target.closest( `${ LOOP_SELECTOR } .directorist-sortby-dropdown .directorist-dropdown__toggle-js` )
			: null;

		if ( ! toggle ) {
			return;
		}

		event.preventDefault();
		event.stopPropagation();
		event.stopImmediatePropagation();
		toggleSortDropdown( toggle );
	}, true );

	document.addEventListener( 'click', function( event ) {
		if (
			event.target &&
			event.target.closest &&
			event.target.closest( `${ LOOP_SELECTOR } .directorist-sortby-dropdown` )
		) {
			return;
		}

		closeSortDropdowns();
	} );

	$( document ).on( 'click', `${ LOOP_SELECTOR } .directorist-sortby-dropdown .directorist-dropdown__links__single-js`, function( event ) {
		event.preventDefault();

		const sort = (
			extractUrlQueryValue( $( this ).attr( 'data-link' ), 'sort' ) ||
			extractUrlQueryValue( $( this ).attr( 'href' ), 'sort' )
		);

		$( this ).addClass( 'active' ).siblings( '.directorist-dropdown__links__single-js' ).removeClass( 'active' );
		closeSortDropdowns( getLoopRoot( this ).get( 0 ) || document );
		requestLoopRender( getLoopRoot( this ), {
			requestVars: {
				sort,
				paged: 1,
			},
		} );
	} );

	$( document ).on( 'click', `${ LOOP_SELECTOR } .directorist-pagination .page-numbers`, function( event ) {
		event.preventDefault();

		const $link = $( this );
		const currentPage = toInt( getLoopRoot( this ).attr( 'data-current-page' ), 1 );
		let paged = toInt( extractUrlQueryValue( $link.attr( 'href' ), 'paged' ), 0 );

		if ( paged <= 0 ) {
			const pageText = String( $link.text() || '' ).trim();
			if ( /^\d+$/.test( pageText ) ) {
				paged = toInt( pageText, 1 );
			} else if ( $link.hasClass( 'next' ) ) {
				paged = currentPage + 1;
			} else if ( $link.hasClass( 'prev' ) || $link.hasClass( 'previous' ) ) {
				paged = Math.max( 1, currentPage - 1 );
			}
		}

		requestLoopRender( getLoopRoot( this ), {
			requestVars: {
				paged: Math.max( 1, paged ),
			},
		} );
	} );

	$( document ).on( 'click', `${ LOOP_SELECTOR } .directorist-btn-reset-js`, function( event ) {
		event.preventDefault();

		const $form = $( this ).closest( 'form' );
		const $loop = getLoopRoot( this );

		if ( $form.length ) {
			resetLoopForm( $form );
		}

		requestLoopRender( $loop, {
			requestVars: {
				paged: 1,
			},
		} );
	} );

	$( document ).on( 'click', `${ LOOP_SELECTOR } .directorist-elementor-pagination__infinite-button`, function( event ) {
		event.preventDefault();

		const $button = $( this );
		const nextPage = Math.max( 1, toInt( $button.attr( 'data-next-page' ), 1 ) );

		requestLoopRender( getLoopRoot( this ), {
			appendMode: true,
			requestVars: {
				paged: nextPage,
			},
		} );
	} );

	document.addEventListener( 'click', function( event ) {
		const directoryTrigger = event.target.closest(
			`${ LOOP_SELECTOR } .directorist-type-nav__link, ${ LOOP_SELECTOR } .search_listing_types`
		);

		if ( directoryTrigger ) {
			if ( directoryTrigger.closest( HOME_SEARCH_SELECTOR ) ) {
				return;
			}

			const $link = $( directoryTrigger );
			const $loop = getLoopRoot( directoryTrigger );
			const activeDirectoryId = toInt( $link.attr( 'data-listing_type_id' ), 0 );
			const directorySlug = String( $link.attr( 'data-listing_type' ) || '' );
			const directoryType = directorySlug || extractUrlQueryValue( $link.attr( 'href' ), 'directory_type' );

			event.preventDefault();
			event.stopPropagation();

			if ( 'function' === typeof event.stopImmediatePropagation ) {
				event.stopImmediatePropagation();
			}

			resetLoopFormsForDirectoryChange( $loop, directoryType || 'all' );

			requestLoopRender( $loop, {
				activeDirectoryId,
				requestVars: {
					directory_type: directoryType || 'all',
					paged: 1,
				},
			} );

			return;
		}

		const openTrigger = event.target.closest(
			`${ LOOP_SELECTOR } .directorist-filter-btn, ${ LOOP_SELECTOR } .directorist-archive-sidebar-toggle`
		);

		if ( openTrigger ) {
			const floatingRoot = resolveFloatingFilterRoot( openTrigger );

			if ( floatingRoot && isMobileViewport() ) {
				event.preventDefault();
				event.stopPropagation();

				if ( 'function' === typeof event.stopImmediatePropagation ) {
					event.stopImmediatePropagation();
				}

				openFloatingFilter( floatingRoot );
			}

			return;
		}

		const closeTrigger = event.target.closest(
			`${ LOOP_SELECTOR } .directorist-advanced-filter__close, ${ LOOP_SELECTOR } .directorist-gbi-filters-mobile-overlay`
		);

		if ( ! closeTrigger ) {
			return;
		}

		const floatingRoot =
			closeTrigger.closest(
				'.directorist-elementor-listings-filters.directorist-gbi-filters-mobile-floating'
			) || resolveFloatingFilterRoot( closeTrigger );

		if ( ! floatingRoot ) {
			return;
		}

		event.preventDefault();
		event.stopPropagation();

		if ( 'function' === typeof event.stopImmediatePropagation ) {
			event.stopImmediatePropagation();
		}

		closeFloatingFilter( floatingRoot );
	}, true );

	window.addEventListener( 'keydown', function( event ) {
		if ( 'Escape' !== event.key ) {
			return;
		}

		closeAllFloatingFilters();
	} );

	window.addEventListener( 'resize', function() {
		if ( isMobileViewport() ) {
			scheduleInfiniteScrollCheck();
			return;
		}

		closeAllFloatingFilters();
		scheduleInfiniteScrollCheck();
	} );

	window.addEventListener( 'scroll', scheduleInfiniteScrollCheck, { passive: true } );

	document.addEventListener( 'click', function( event ) {
		if (
			event.target.closest(
				'.directorist-elementor-loop__scope-button--directory, .directorist-elementor-loop__scope-button--view'
			)
		) {
			scheduleMapPreviewRefresh();
		}
	}, true );

		window.addEventListener( 'directorist-instant-search-reloaded', function() {
			closeAllFloatingFilters();
			initializeMapLists();
			initializeMapPreviews();
			initializeFavoriteFallback();
			scheduleInfiniteScrollCheck();
		} );

	window.addEventListener( 'directorist-reload-listings-map-archive', function() {
		initializeMapLists();
		initializeMapPreviews();
	} );

		initializeMapPreviews();
		initializeFavoriteFallback();
		window.setTimeout( scheduleInfiniteScrollCheck, 0 );
} )( jQuery, window, document );
