( function ( window, document ) {
	'use strict';

	const config = window.DirectoristElementorTemplateImport || {};
	const labels = config.labels || {};
	const apiFetch = window.wp && window.wp.apiFetch;
	const root = document.getElementById( 'directorist-template-import-app' );
	const previewRoot = document.getElementById( 'directorist-template-preview-app' );
	const appShell = document.querySelector( '.directorist-template-import-app' );
	const categoryRoot = document.querySelector( '[data-directorist-template-categories]' );
	const favoriteStorageKey = 'directoristElementorTemplateFavorites';

	if ( ! root || ! apiFetch ) {
		return;
	}

	if ( config.nonce && apiFetch.createNonceMiddleware ) {
		apiFetch.use( apiFetch.createNonceMiddleware( config.nonce ) );
	}

	let catalogItems = [];
	let activeCategories = new Set();
	let activeView = 'all';
	let activeSearch = '';
	let activeSort = 'featured';
	let previewItem = null;
	let previewDevice = 'desktop';
	let favoriteIds = loadFavorites();
	const siteChangeOptionKeys = [ 'apply_site_settings', 'apply_template_conditions', 'set_homepage' ];
	const defaultImportOptions = {
		conflict_behavior: 'update',
		include_templates: true,
		include_content: true,
		apply_site_settings: true,
		apply_template_conditions: true,
		set_homepage: true,
	};

	const state = {
		loading: false,
		error: '',
		importing: '',
		notice: '',
		progress: null,
	};

	function endpoint( path ) {
		const base = String( config.restBase || '' ).replace( /\/$/, '' );
		const suffix = String( path || '' );

		if ( ! base ) {
			return suffix;
		}

		try {
			const url = new URL( base, window.location.origin );
			const restRoute = url.searchParams.get( 'rest_route' );

			if ( null !== restRoute ) {
				const parts = suffix.split( '?' );
				const routeSuffix = parts.shift() || '';
				const query = parts.join( '?' );

				url.searchParams.set( 'rest_route', restRoute.replace( /\/$/, '' ) + routeSuffix );

				if ( query ) {
					new URLSearchParams( query ).forEach( function ( value, key ) {
						url.searchParams.set( key, value );
					} );
				}

				return url.toString();
			}
		} catch ( error ) {
			// Fall back to the previous concatenation behavior for unusual admin URLs.
		}

		return base + suffix;
	}

	function request( path, options ) {
		const target = endpoint( path );
		const args = Object.assign( {}, options || {} );

		if ( /^https?:\/\//.test( target ) ) {
			args.url = target;
		} else {
			args.path = target;
		}

		return apiFetch( args );
	}

	function escapeHTML( value ) {
		return String( value || '' )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' )
			.replace( /'/g, '&#039;' );
	}

	function normalizeItems( response ) {
		if ( Array.isArray( response ) ) {
			return response;
		}

		if ( response && Array.isArray( response.items ) ) {
			return response.items;
		}

		return [];
	}

	function getPreviewUrl( item ) {
		return item.demo_url || item.preview_url || item.dashboard_preview_url || '';
	}

	function getThumbnailUrl( item ) {
		if ( item && ( item.thumbnail || item.thumbnail_url ) ) {
			return item.thumbnail || item.thumbnail_url;
		}

		return config.placeholderThumbnail || '';
	}

	function loadFavorites() {
		try {
			const stored = JSON.parse( window.localStorage.getItem( favoriteStorageKey ) || '[]' );
			return new Set( Array.isArray( stored ) ? stored.filter( Boolean ) : [] );
		} catch ( error ) {
			return new Set();
		}
	}

	function saveFavorites() {
		try {
			window.localStorage.setItem( favoriteStorageKey, JSON.stringify( Array.from( favoriteIds ) ) );
		} catch ( error ) {
			// Browser storage can be disabled. The visible state still updates for this session.
		}
	}

	function findItem( itemId ) {
		return catalogItems.find( function ( item ) {
			return item && item.id === itemId;
		} ) || null;
	}

	function getPackageTypeLabel( type ) {
		const labelsByType = {
			site: 'Full Site',
			page: 'Page',
			template: 'Template',
			section: 'Section',
			fse_theme: 'FSE Theme',
		};

		return labelsByType[ type ] || type || 'Template';
	}

	function getImportOptionDefaults( item ) {
		const defaults = Object.assign( {}, defaultImportOptions );
		const manifestDefaults = item && item.directorist && item.directorist.import_defaults && typeof item.directorist.import_defaults === 'object' ? item.directorist.import_defaults : {};

		Object.keys( defaults ).forEach( function ( key ) {
			if ( Object.prototype.hasOwnProperty.call( manifestDefaults, key ) ) {
				if ( siteChangeOptionKeys.indexOf( key ) !== -1 ) {
					return;
				}

				defaults[ key ] = 'conflict_behavior' === key ? String( manifestDefaults[ key ] || defaults[ key ] ) : !! manifestDefaults[ key ];
			}
		} );

		return defaults;
	}

	function normalizeImportOptions( options ) {
		const normalized = Object.assign( {}, defaultImportOptions, options || {} );
		const allowedConflicts = [ 'update', 'skip', 'duplicate', 'replace' ];

		normalized.conflict_behavior = allowedConflicts.indexOf( normalized.conflict_behavior ) !== -1 ? normalized.conflict_behavior : 'update';
		normalized.include_templates = !! normalized.include_templates;
		normalized.include_content = !! normalized.include_content;
		normalized.apply_site_settings = !! normalized.apply_site_settings;
		normalized.apply_template_conditions = !! normalized.apply_template_conditions;
		normalized.set_homepage = !! normalized.set_homepage;
		normalized.activate_templates = normalized.apply_template_conditions;

		return normalized;
	}

	function getImportOptionsFromForm( form ) {
		return normalizeImportOptions( {
			conflict_behavior: form.querySelector( '[name="conflict_behavior"]' ).value,
			include_templates: form.querySelector( '[name="include_templates"]' ).checked,
			include_content: form.querySelector( '[name="include_content"]' ).checked,
			apply_site_settings: form.querySelector( '[name="apply_site_settings"]' ).checked,
			apply_template_conditions: form.querySelector( '[name="apply_template_conditions"]' ).checked,
			set_homepage: form.querySelector( '[name="set_homepage"]' ).checked,
		} );
	}

	function renderCheckboxField( name, label, description, checked, warning ) {
		return [
			'<label class="directorist-template-import-options__check">',
			'<input type="checkbox" name="' + escapeHTML( name ) + '"' + ( checked ? ' checked' : '' ) + ' />',
			'<span class="directorist-template-import-options__check-content">',
			'<strong class="directorist-template-import-options__check-title">' + escapeHTML( label ) + '</strong>',
			description ? '<small>' + escapeHTML( description ) + '</small>' : '',
			warning ? '<em>' + escapeHTML( warning ) + '</em>' : '',
			'</span>',
			'</label>',
		].join( '' );
	}

	function getExtensionRequirementItems( item ) {
		const requirements = item && item.extension_requirements && Array.isArray( item.extension_requirements.items ) ? item.extension_requirements.items : [];

		return requirements.filter( Boolean );
	}

	function areExtensionRequirementsSatisfied( item ) {
		if ( ! item || ! item.extension_requirements ) {
			return true;
		}

		return !! item.extension_requirements.satisfied;
	}

	function renderExtensionRequirements( item ) {
		const requirements = getExtensionRequirementItems( item );

		if ( ! requirements.length ) {
			return '';
		}

		const rows = requirements.map( function ( requirement ) {
			const status = requirement.status || 'missing';
			const name = requirement.name || requirement.slug || '';
			const provenance = getRequirementProvenance( requirement );
			const canActivate = 'inactive' === status && requirement.can_activate && requirement.plugin;
			let action = '';

			if ( canActivate ) {
				action = '<button type="button" class="directorist-template-import-requirement__action" data-directorist-requirement-activate data-plugin="' + escapeHTML( requirement.plugin ) + '" data-slug="' + escapeHTML( requirement.slug || '' ) + '">' + escapeHTML( labels.activateRequirement || 'Activate' ) + '</button>';
			} else if ( 'missing' === status && requirement.action_url ) {
				action = '<a href="' + escapeHTML( requirement.action_url ) + '" target="_blank" rel="noopener noreferrer">' + escapeHTML( labels.installRequirement || 'Install' ) + '</a>';
			}

			return [
				'<li class="directorist-template-import-requirement directorist-template-import-requirement--' + escapeHTML( status ) + '">',
				'<span class="directorist-template-import-requirement__name"><span>' + escapeHTML( name ) + '</span>',
				provenance.label ? '<small title="' + escapeHTML( provenance.evidence ) + '">' + escapeHTML( provenance.label ) + '</small>' : '',
				'</span>',
				'<span class="directorist-template-import-requirement__status">' + escapeHTML( getRequirementStatusLabel( status ) ) + '</span>',
				action,
				'</li>',
			].join( '' );
		} ).join( '' );
		const satisfied = areExtensionRequirementsSatisfied( item );

		return [
			'<section class="directorist-template-import-requirements' + ( satisfied ? ' directorist-template-import-requirements--satisfied' : ' directorist-template-import-requirements--blocked' ) + '">',
			'<div class="directorist-template-import-requirements__header">',
			'<h3>' + escapeHTML( labels.requiredExtensions || 'Required plugins and extensions' ) + '</h3>',
			'<span>' + escapeHTML( satisfied ? labels.requirementsReady || 'Ready' : labels.requirementsBlocked || 'Extensions unavailable' ) + '</span>',
			'</div>',
			'<ul>' + rows + '</ul>',
			satisfied ? '' : '<p>' + escapeHTML( labels.requirementsHelp || 'You can import this template now, but sections or widgets that depend on missing or inactive extensions may not render or work until those extensions are installed and activated.' ) + '</p>',
			'</section>',
		].join( '' );
	}

	function getRequirementStatusLabel( status ) {
		if ( 'active' === status ) {
			return labels.requirementActive || 'Active';
		}

		if ( 'inactive' === status ) {
			return labels.requirementInactive || 'Inactive';
		}

		return labels.requirementMissing || 'Missing';
	}

	function getRequirementProvenance( requirement ) {
		const sourceLabels = {
			base: labels.requirementSourceBase || 'Core requirement',
			manual: labels.requirementSourceManual || 'Selected by template author',
			component: labels.requirementSourceComponent || 'Detected from exported content',
			builder_content: labels.requirementSourceBuilderContent || 'Detected from builder content',
		};
		const sources = Array.isArray( requirement.sources ) ? requirement.sources : [];
		const evidence = Array.isArray( requirement.evidence ) ? requirement.evidence : [];

		return {
			label: sources.map( function ( source ) {
				return sourceLabels[ source ] || source;
			} ).filter( Boolean ).join( ', ' ),
			evidence: evidence.join( ', ' ),
		};
	}

	function requirementMatches( requirement, plugin, slug ) {
		if ( ! requirement ) {
			return false;
		}

		if ( plugin && requirement.plugin === plugin ) {
			return true;
		}

		return !! slug && requirement.slug === slug;
	}

	function markRequirementActive( item, plugin, slug ) {
		const requirements = getExtensionRequirementItems( item );
		let matched = false;

		requirements.forEach( function ( requirement ) {
			if ( ! requirementMatches( requirement, plugin, slug ) ) {
				return;
			}

			requirement.plugin = plugin || requirement.plugin;
			requirement.status = 'active';
			requirement.action_url = '';
			requirement.can_activate = false;
			matched = true;
		} );

		if ( ! matched || ! item.extension_requirements ) {
			return;
		}

		item.extension_requirements.missing = requirements.filter( function ( requirement ) {
			return 'missing' === requirement.status;
		} );
		item.extension_requirements.inactive = requirements.filter( function ( requirement ) {
			return 'inactive' === requirement.status;
		} );
		item.extension_requirements.satisfied = ! item.extension_requirements.missing.length && ! item.extension_requirements.inactive.length;
	}

	function syncActivatedRequirement( plugin, slug ) {
		catalogItems.forEach( function ( item ) {
			markRequirementActive( item, plugin, slug );
		} );

		if ( previewItem ) {
			markRequirementActive( previewItem, plugin, slug );
		}
	}

	function updateActivatedRequirementRow( button ) {
		const row = button.closest( '.directorist-template-import-requirement' );

		if ( ! row ) {
			return;
		}

		row.classList.remove( 'directorist-template-import-requirement--inactive' );
		row.classList.add( 'directorist-template-import-requirement--active' );

		const status = row.querySelector( '.directorist-template-import-requirement__status' );

		if ( status ) {
			status.textContent = labels.requirementActive || 'Active';
		}

		button.remove();

		const section = row.closest( '.directorist-template-import-requirements' );

		if ( ! section || section.querySelector( '.directorist-template-import-requirement--inactive, .directorist-template-import-requirement--missing' ) ) {
			return;
		}

		section.classList.remove( 'directorist-template-import-requirements--blocked' );
		section.classList.add( 'directorist-template-import-requirements--satisfied' );

		const summary = section.querySelector( '.directorist-template-import-requirements__header span' );
		const help = section.querySelector( ':scope > p' );

		if ( summary ) {
			summary.textContent = labels.requirementsReady || 'Ready';
		}

		if ( help ) {
			help.remove();
		}
	}

	function activateRequirement( button ) {
		if ( ! button || button.disabled ) {
			return;
		}

		const plugin = button.getAttribute( 'data-plugin' ) || '';
		const slug = button.getAttribute( 'data-slug' ) || '';
		const row = button.closest( '.directorist-template-import-requirement' );
		let error = row ? row.querySelector( '.directorist-template-import-requirement__error' ) : null;

		if ( error ) {
			error.remove();
		}

		button.disabled = true;
		button.textContent = labels.activatingRequirement || 'Activating...';

		request( '/requirements/activate', {
			method: 'POST',
			data: {
				plugin: plugin,
				slug: slug,
			},
		} )
			.then( function ( response ) {
				const requirement = response && response.requirement ? response.requirement : {};
				const activePlugin = requirement.plugin || plugin;
				const activeSlug = requirement.slug || slug;

				syncActivatedRequirement( activePlugin, activeSlug );
				updateActivatedRequirementRow( button );
			} )
			.catch( function ( requestError ) {
				button.disabled = false;
				button.textContent = labels.activateRequirement || 'Activate';

				if ( row ) {
					error = document.createElement( 'span' );
					error.className = 'directorist-template-import-requirement__error';
					error.textContent = requestError && requestError.message ? requestError.message : labels.activateRequirementFailed || 'The plugin could not be activated.';
					row.appendChild( error );
				}
			} );
	}

	function buildImportResultMessage( response, result ) {
		const parts = [];
		const report = response && response.report && typeof response.report === 'object' ? response.report : {};
		const reportedTotal = sumReportGroups( report.created ) + sumReportGroups( report.updated );
		const importedTotal = Array.isArray( report.created ) || Array.isArray( report.updated )
			? reportedTotal
			: result.imported;

		if ( response && response.message ) {
			parts.push( response.message );
		} else {
			parts.push( labels.imported || 'Import completed.' );
		}

		[
			[ labels.importedCount || 'Imported', importedTotal ],
			[ labels.skippedCount || 'Skipped', result.skipped ],
			[ labels.replacedCount || 'Replaced', result.replaced ],
		].forEach( function ( pair ) {
			if ( Number( pair[ 1 ] || 0 ) > 0 ) {
				parts.push( pair[ 0 ] + ': ' + Number( pair[ 1 ] ) );
			}
		} );

		return parts.join( ' ' );
	}

	function getItemCategories( item ) {
		const values = [];
		const addValue = function ( value ) {
			if ( ! value ) {
				return;
			}

			if ( typeof value === 'string' ) {
				values.push( value );
				return;
			}

			if ( typeof value === 'object' ) {
				values.push( value.name || value.label || value.slug || value.title || '' );
			}
		};

		if ( Array.isArray( item.categories ) ) {
			item.categories.forEach( addValue );
		}

		if ( Array.isArray( item.category ) ) {
			item.category.forEach( addValue );
		} else {
			addValue( item.category );
		}

		addValue( item.category_name );
		addValue( item.category_label );

		return Array.from( new Set( values.map( function ( value ) {
			return String( value || '' ).trim();
		} ).filter( Boolean ) ) );
	}

	function getItemKeywords( item ) {
		const keywords = [];

		[ item.keywords, item.tags ].forEach( function ( values ) {
			if ( Array.isArray( values ) ) {
				values.forEach( function ( value ) {
					if ( value ) {
						keywords.push( typeof value === 'string' ? value : value.name || value.label || value.title || '' );
					}
				} );
			}
		} );

		return Array.from( new Set( keywords.map( function ( value ) {
			return String( value || '' ).trim();
		} ).filter( Boolean ) ) );
	}

	function renderCardChips( item ) {
		const categories = getItemCategories( item ).slice( 0, 2 );
		const keywords = getItemKeywords( item ).filter( function ( keyword ) {
			return categories.indexOf( keyword ) === -1;
		} ).slice( 0, 2 );
		const chips = categories.map( function (category) {
			return '<span class="directorist-template-import-card__chip directorist-template-import-card__chip--category">' + escapeHTML( category ) + '</span>';
		} ).concat( keywords.map( function (keyword) {
			return '<span class="directorist-template-import-card__chip">' + escapeHTML( keyword ) + '</span>';
		} ) );

		return chips.length ? '<div class="directorist-template-import-card__chips">' + chips.join( '' ) + '</div>' : '';
	}

	function normalizeCategoryValue( value ) {
		return String( value || '' ).trim().toLowerCase();
	}

	function getCatalogCategories() {
		const map = new Map();

		catalogItems.forEach( function ( item ) {
			getItemCategories( item ).forEach( function ( category ) {
				const key = normalizeCategoryValue( category );

				if ( key && ! map.has( key ) ) {
					map.set( key, category );
				}
			} );
		} );

		return Array.from( map.entries() )
			.map( function ( entry ) {
				return {
					value: entry[ 0 ],
					label: entry[ 1 ],
				};
			} )
			.sort( function ( a, b ) {
				return a.label.localeCompare( b.label );
			} );
	}

	function renderCategories() {
		if ( ! categoryRoot ) {
			return;
		}

		const categories = getCatalogCategories();
		const allCategoriesChecked = activeCategories.size === 0;

		categoryRoot.innerHTML = [
			'<label><input class="directorist-template-import-app__filter-checkbox" type="checkbox" value="" data-directorist-template-filter-category' + ( allCategoriesChecked ? ' checked' : '' ) + ' /> ' + escapeHTML( labels.allCategories || 'All Categories' ) + '</label>',
		].concat( categories.map( function ( category ) {
			return '<label><input class="directorist-template-import-app__filter-checkbox" type="checkbox" value="' + escapeHTML( category.value ) + '" data-directorist-template-filter-category' + ( activeCategories.has( category.value ) ? ' checked' : '' ) + ' /> ' + escapeHTML( category.label ) + '</label>';
		} ) ).join( '' );
	}

	function renderViewNav() {
		document.querySelectorAll( '[data-directorist-template-view]' ).forEach( function ( button ) {
			const isActive = button.getAttribute( 'data-directorist-template-view' ) === activeView;

			button.classList.toggle( 'directorist-template-import-app__nav-item--active', isActive );
		} );
	}

	function filterItems() {
		const search = activeSearch.trim().toLowerCase();

		return catalogItems.filter( function ( item ) {
			if ( 'favorites' === activeView && ! favoriteIds.has( item.id ) ) {
				return false;
			}

			if ( activeCategories.size ) {
				const categories = getItemCategories( item ).map( normalizeCategoryValue );
				const hasCategoryMatch = categories.some( function ( category ) {
					return activeCategories.has( category );
				} );

				if ( ! hasCategoryMatch ) {
					return false;
				}
			}

			if ( ! search ) {
				return true;
			}

			return [ item.title, item.description, item.package_type, item.builder ].concat( getItemCategories( item ), getItemKeywords( item ) )
				.filter( Boolean )
				.join( ' ' )
				.toLowerCase()
				.indexOf( search ) !== -1;
		} );
	}

	function getVisibleItems() {
		const items = filterItems().slice();

		if ( 'title' === activeSort ) {
			items.sort( function ( a, b ) {
				return String( a.title || a.id || '' ).localeCompare( String( b.title || b.id || '' ) );
			} );
		}

		if ( 'type' === activeSort ) {
			items.sort( function ( a, b ) {
				return String( a.package_type || '' ).localeCompare( String( b.package_type || '' ) );
			} );
		}

		return items;
	}

	function render() {
		renderCategories();
		renderViewNav();
		renderProgressOverlay();
		syncPageBusyState();

		if ( state.loading ) {
			root.innerHTML = '<div class="directorist-template-import__state">' + escapeHTML( labels.loading || 'Loading...' ) + '</div>';
			return;
		}

		if ( state.error ) {
			root.innerHTML = '<div class="notice notice-error directorist-template-import__notice"><p>' + escapeHTML( state.error ) + '</p></div>';
			return;
		}

		const items = getVisibleItems();

		if ( ! items.length ) {
			root.innerHTML = '<div class="directorist-template-import__state">' + escapeHTML( labels.empty || 'No templates found.' ) + '</div>';
			return;
		}

		root.innerHTML =
			( state.notice ? '<div class="notice notice-success directorist-template-import__notice"><p>' + escapeHTML( state.notice ) + '</p></div>' : '' ) +
			'<div class="directorist-template-import__grid">' +
			items.map( renderCard ).join( '' ) +
			'</div>';
	}

	function syncPageBusyState() {
		const busy = !! state.progress && state.progress.active;

		if ( appShell ) {
			appShell.classList.toggle( 'directorist-template-import-app--busy', busy );
			appShell.setAttribute( 'aria-busy', busy ? 'true' : 'false' );
		}

		document.body.classList.toggle( 'directorist-template-import-is-busy', busy );
	}

	function pendingImport( value ) {
		try {
			const key = 'directorist-import:' + endpoint( '' );
			if ( undefined === value ) {
				return JSON.parse( window.sessionStorage.getItem( key ) || 'null' );
			}
			if ( null === value ) {
				window.sessionStorage.removeItem( key );
			} else {
				window.sessionStorage.setItem( key, JSON.stringify( value ) );
			}
		} catch ( error ) {
			return null;
		}
		return value;
	}

	function completeImport( response, dialog ) {
		const result = response && response.result ? response.result : {};
		const errors = Array.isArray( result.errors ) ? result.errors.filter( Boolean ) : [];
		state.importing = '';
		state.notice = '';
		state.error = '';
		if ( ( response && ( 'partial' === response.status || 'failed' === response.status ) ) || errors.length ) {
			state.error = [ response && response.message ? response.message : labels.failed || 'Import failed.' ].concat( errors ).join( ' ' );
		} else if ( ! [ result.imported, result.updated, result.skipped, result.replaced ].some( function ( count ) { return Number( count || 0 ) > 0; } ) ) {
			state.error = labels.failed || 'Import failed.';
		} else {
			state.notice = buildImportResultMessage( response, result );
		}
		if ( dialog ) {
			setUploadDialogBusy( dialog, false );
		}
		if ( state.error ) {
			failProgress( state.error );
		} else {
			finishProgress( response );
		}
		render();
		syncPreviewStatus();
	}

	function resumeImport() {
		const saved = pendingImport();
		if ( ! saved || ! saved.id ) {
			return;
		}
		state.importing = '__resume__';
		startProgress( saved.title || labels.progressProcessing, saved.mode || 'catalog', saved.options || {} );
		render();
		request( '/jobs/' + encodeURIComponent( saved.id ) ).then( continueImport ).then( function ( response ) {
			completeImport( response );
		} ).catch( function ( error ) {
			if ( error && [ 'import_job_missing', 'import_job_expired' ].includes( error.code ) ) {
				pendingImport( null );
			}
			state.importing = '';
			state.error = error && error.message ? error.message : labels.failed;
			failProgress( state.error );
			render();
		} );
	}

	function continueImport( response ) {
		if ( ! response || 'running' !== response.status || ! response.job || ! response.job.id ) {
			pendingImport( null );
			return response;
		}
		pendingImport( { id: response.job.id, title: state.progress && state.progress.title, mode: state.progress && state.progress.mode, options: state.progress && state.progress.options } );
		if ( state.progress ) {
			state.progress.message = response.message || labels.progressProcessing || 'Processing the website package';
			render();
		}
		if ( response.job.busy ) {
			return new Promise( function ( resolve ) { window.setTimeout( resolve, 500 ); } ).then( function () {
				return request( '/jobs/' + encodeURIComponent( response.job.id ) );
			} ).then( continueImport );
		}
		return request( '/jobs/' + encodeURIComponent( response.job.id ) + '/step', { method: 'POST' } ).then( continueImport );
	}

	function startProgress( title, mode, options ) {
		state.progress = {
			active: true,
			done: false,
			error: false,
			mode: mode || 'catalog',
			options: normalizeImportOptions( options ),
			title: title || labels.importing || 'Importing...',
			message: 'upload' === mode
				? labels.progressUploadHelp || 'Uploading and importing the package. The status will update when the server finishes.'
				: labels.progressProcessingHelp || 'Importing content, templates, media, and selected site settings. The status will update when the server finishes.',
			result: '',
			report: null,
		};

		render();
	}

	function finishProgress( response ) {
		if ( ! state.progress ) {
			return;
		}

		const result = response && response.result ? response.result : {};

		state.progress.active = false;
		state.progress.done = true;
		state.progress.error = false;
		if ( 'upload' === state.progress.mode ) {
			state.progress.title = labels.importComplete || 'Website import complete';
		}
		state.progress.message = labels.progressCompleteHelp || 'The imported items are ready on this site.';
		state.progress.result = buildImportResultMessage( response, result );
		state.progress.report = normalizeImportReport( response, result, state.progress.options );
		render();
	}

	function failProgress( message ) {
		if ( ! state.progress ) {
			return;
		}

		state.progress.active = false;
		state.progress.done = true;
		state.progress.error = true;
		if ( 'upload' === state.progress.mode ) {
			state.progress.title = labels.importFailed || 'Website import failed';
		}
		state.progress.message = labels.progressFailedHelp || 'The import could not be completed.';
		state.progress.result = message || labels.failed || 'Import failed.';
		render();
	}

	function closeProgress() {
		state.progress = null;
		render();
	}

	function sumReportGroups( groups ) {
		return ( Array.isArray( groups ) ? groups : [] ).reduce( function ( total, group ) {
			return total + Number( group && group.count ? group.count : 0 );
		}, 0 );
	}

	function normalizeImportReport( response, result, options ) {
		const report = response && response.report && typeof response.report === 'object' ? response.report : {};
		const session = response && response.session && typeof response.session === 'object' ? response.session : {};
		const created = Array.isArray( report.created ) ? report.created : [];
		const updated = Array.isArray( report.updated ) ? report.updated : [];
		const totals = Object.assign( {
			created: sumReportGroups( created ),
			updated: sumReportGroups( updated ),
			skipped: Number( result.skipped || 0 ),
			replaced: Number( result.replaced || 0 ),
		}, report.totals || {} );

		return {
			packageId: report.package_id || session.package_id || '',
			packageVersion: report.package_version || session.package_version || '',
			siteUrl: report.site_url || window.location.origin,
			created: created,
			updated: updated,
			warnings: Array.isArray( report.warnings ) ? report.warnings.filter( Boolean ) : [],
			totals: totals,
			siteChanges: Array.isArray( report.site_changes ) ? report.site_changes : buildFallbackSiteChanges( options ),
		};
	}

	function buildFallbackSiteChanges( options ) {
		const settings = normalizeImportOptions( options );

		return [
			{
				label: labels.applySiteSettings || 'Elementor site settings',
				status: settings.apply_site_settings ? 'applied' : 'preserved',
				detail: settings.apply_site_settings ? 'Selected site settings were applied.' : 'Existing site settings were preserved.',
			},
			{
				label: labels.applyTemplateConditions || 'Template conditions',
				status: settings.apply_template_conditions ? 'applied' : 'preserved',
				detail: settings.apply_template_conditions ? 'Imported template conditions were activated.' : 'Existing template conditions were preserved.',
			},
			{
				label: labels.setHomepage || 'Homepage',
				status: settings.set_homepage ? 'applied' : 'preserved',
				detail: settings.set_homepage ? 'The imported homepage was selected.' : 'The existing homepage was preserved.',
			},
		];
	}

	function renderReportGroup( group ) {
		const items = Array.isArray( group && group.items ) ? group.items : [];
		const count = Number( group && group.count ? group.count : items.length );

		return [
			'<details class="directorist-template-import-report__group">',
			'<summary><span>' + escapeHTML( group && group.label ? group.label : group && group.type ? group.type : 'Items' ) + '</span><strong>' + count + '</strong></summary>',
			items.length ? '<ul>' + items.map( function ( item ) {
				const title = escapeHTML( item && item.title ? item.title : labels.untitled || '(Untitled)' );
				return '<li>' + ( item && item.edit_url ? '<a href="' + escapeHTML( item.edit_url ) + '">' + title + '</a>' : '<span>' + title + '</span>' ) + '</li>';
			} ).join( '' ) + '</ul>' : '',
			'</details>',
		].join( '' );
	}

	function renderImportReport( report ) {
		if ( ! report ) {
			return '';
		}

		const totals = report.totals || {};
		const metrics = [
			[ labels.createdCount || 'Created', Number( totals.created || 0 ) ],
			[ labels.updatedCount || 'Updated', Number( totals.updated || 0 ) ],
			[ labels.skippedCount || 'Skipped', Number( totals.skipped || 0 ) ],
			[ labels.replacedCount || 'Replaced', Number( totals.replaced || 0 ) ],
		];
		const objectSections = [
			[ labels.createdItems || 'Created items', report.created ],
			[ labels.updatedItems || 'Updated items', report.updated ],
		].filter( function ( section ) {
			return Array.isArray( section[ 1 ] ) && section[ 1 ].length;
		} );
		const packageMeta = [ report.packageId, report.packageVersion ? 'v' + report.packageVersion : '' ].filter( Boolean ).join( ' · ' );

		return [
			'<div class="directorist-template-import-report">',
			packageMeta ? '<p class="directorist-template-import-report__package">' + escapeHTML( packageMeta ) + '</p>' : '',
			report.warnings && report.warnings.length ? '<div class="directorist-template-import-report__warnings" role="status"><strong>' + escapeHTML( labels.needsAttention || 'Needs attention' ) + '</strong><ul>' + report.warnings.map( function ( warning ) {
				return '<li>' + escapeHTML( warning ) + '</li>';
			} ).join( '' ) + '</ul></div>' : '',
			'<div class="directorist-template-import-report__metrics">',
			metrics.map( function ( metric ) {
				return '<div><strong>' + metric[ 1 ] + '</strong><span>' + escapeHTML( metric[ 0 ] ) + '</span></div>';
			} ).join( '' ),
			'</div>',
			'<div class="directorist-template-import-report__body">',
			'<section class="directorist-template-import-report__changes">',
			'<h3>' + escapeHTML( labels.siteChanges || 'Site changes' ) + '</h3>',
			'<ul>',
			( report.siteChanges || [] ).map( function ( change ) {
				return '<li class="is-' + escapeHTML( change.status || 'preserved' ) + '"><span class="directorist-template-import-report__status"></span><div><strong>' + escapeHTML( change.label || '' ) + '</strong><small>' + escapeHTML( change.detail || '' ) + '</small></div></li>';
			} ).join( '' ),
			'</ul>',
			'</section>',
			objectSections.length ? '<section class="directorist-template-import-report__objects"><h3>' + escapeHTML( labels.importedItems || 'Imported items' ) + '</h3>' + objectSections.map( function ( section ) {
				return '<div class="directorist-template-import-report__object-section"><h4>' + escapeHTML( section[ 0 ] ) + '</h4>' + section[ 1 ].map( renderReportGroup ).join( '' ) + '</div>';
			} ).join( '' ) + '</section>' : '',
			'</div>',
			'</div>',
		].join( '' );
	}

	function renderProgressOverlay() {
		let overlay = document.querySelector( '[data-directorist-template-import-progress]' );

		if ( ! state.progress ) {
			if ( overlay ) {
				overlay.remove();
			}
			return;
		}

		const progress = state.progress;
		const canClose = progress.done;
		const statusClass = progress.error ? ' directorist-template-import-progress--error' : ( progress.done ? ' directorist-template-import-progress--done' : '' );

		if ( ! overlay ) {
			overlay = document.createElement( 'div' );
			overlay.setAttribute( 'data-directorist-template-import-progress', '' );
			document.body.appendChild( overlay );
		}

		overlay.className = 'directorist-template-import-progress' + statusClass;
		overlay.innerHTML = [
			'<div class="directorist-template-import-progress__panel" role="dialog" aria-modal="true" aria-live="assertive" aria-labelledby="directorist-template-import-progress-title">',
			'<div class="directorist-template-import-progress__header">',
			'<span class="directorist-template-import-progress__eyebrow">' + escapeHTML( progress.done ? ( labels.progressFinished || 'Import finished' ) : ( labels.progressRunning || 'Import in progress' ) ) + '</span>',
			'<h2 id="directorist-template-import-progress-title">' + escapeHTML( progress.title ) + '</h2>',
			'</div>',
			progress.active ? '<div class="directorist-template-import-progress__activity" aria-hidden="true"><span></span></div>' : '',
			progress.active ? '<div class="directorist-template-import-progress__operation"><span class="directorist-template-import-progress__spinner" aria-hidden="true"></span><div><strong>' + escapeHTML( labels.progressProcessing || 'Processing the website package' ) + '</strong><p>' + escapeHTML( progress.message ) + '</p></div></div>' : '<p class="directorist-template-import-progress__message">' + escapeHTML( progress.result || progress.message ) + '</p>',
			progress.done && ! progress.error ? renderImportReport( progress.report ) : '',
			canClose ? '<div class="directorist-template-import-progress__actions">' + ( ! progress.error && progress.report && progress.report.siteUrl ? '<a class="directorist-template-import-progress__button directorist-template-import-progress__button--secondary" href="' + escapeHTML( progress.report.siteUrl ) + '" target="_blank" rel="noopener">' + escapeHTML( labels.viewSite || 'View site' ) + '</a>' : '' ) + '<button type="button" class="directorist-template-import-progress__button directorist-template-import-progress__button--primary" data-directorist-template-progress-close>' + escapeHTML( labels.done || 'Done' ) + '</button></div>' : '<span class="directorist-template-import-progress__lock">' + escapeHTML( labels.progressLock || 'Please keep this page open while the import finishes.' ) + '</span>',
			'</div>',
		].join( '' );
	}

	function renderCard( item ) {
		const previewUrl = getPreviewUrl( item );
		const thumbnailUrl = getThumbnailUrl( item );
		const thumbnailClass = 'directorist-template-import-card__thumb' + ( item.thumbnail || item.thumbnail_url ? '' : ' directorist-template-import-card__thumb--placeholder' );
		const isFavorite = favoriteIds.has( item.id );
		const favoriteLabel = isFavorite ? labels.removeFavorite || 'Remove from Favorites' : labels.addFavorite || 'Add to Favorites';
		const importLabel = labels.importThisTemplate || labels.import || 'Import This Template';

		return [
			'<article class="directorist-template-import-card" data-item-id="' + escapeHTML( item.id ) + '">',
			'<div class="directorist-template-import-card__header">',
			'<div class="directorist-template-import-card__heading">',
			'<h3 class="directorist-template-import-card__title">' + escapeHTML( item.title || item.id ) + '</h3>',
			'</div>',
			'<button type="button" class="directorist-template-import-card__favorite' + ( isFavorite ? ' is-favorite' : '' ) + '" data-directorist-template-favorite="' + escapeHTML( item.id ) + '" aria-pressed="' + ( isFavorite ? 'true' : 'false' ) + '" aria-label="' + escapeHTML( favoriteLabel ) + '"></button>',
			'</div>',
			'<div class="directorist-template-import-card__thumb-wrap">',
			thumbnailUrl ? '<img class="' + escapeHTML( thumbnailClass ) + '" src="' + escapeHTML( thumbnailUrl ) + '" alt="" loading="lazy" />' : '<div class="directorist-template-import-card__thumb directorist-template-import-card__thumb--empty"></div>',
			renderCardChips( item ),
			'</div>',
			'<div class="directorist-template-import-card__footer">',
			previewUrl ? '<a class="directorist-template-import-card__action directorist-template-import-card__action--demo" href="' + escapeHTML( previewUrl ) + '" data-directorist-template-preview="' + escapeHTML( item.id ) + '" title="' + escapeHTML( labels.showDemo || 'View Demo' ) + '"><span>' + escapeHTML( labels.showDemo || 'View Demo' ) + '</span></a>' : '<button type="button" class="directorist-template-import-card__action directorist-template-import-card__action--demo" data-directorist-template-details="' + escapeHTML( item.id ) + '" title="' + escapeHTML( labels.details || 'Details' ) + '"><span>' + escapeHTML( labels.details || 'Details' ) + '</span></button>',
			'<button type="button" class="directorist-template-import-card__action directorist-template-import-card__action--import" data-directorist-template-import="' + escapeHTML( item.id ) + '" title="' + escapeHTML( importLabel ) + '">' + escapeHTML( labels.import || 'Import' ) + '</button>',
			'</div>',
			'</article>',
		].join( '' );
	}

	function setPreviewMode( active ) {
		if ( ! previewRoot || ! appShell ) {
			return;
		}

		appShell.classList.toggle( 'directorist-template-import-app--previewing', !! active );
		previewRoot.hidden = ! active;
	}

	function renderDeviceButton( device, label ) {
		const active = previewDevice === device ? ' directorist-template-preview__device--active' : '';

		return '<button type="button" class="directorist-template-preview__device directorist-template-preview__device--' + escapeHTML( device ) + active + '" data-directorist-preview-device="' + escapeHTML( device ) + '" aria-label="' + escapeHTML( label ) + '"><span class="directorist-template-import__sr">' + escapeHTML( label ) + '</span></button>';
	}

	function renderPreview() {
		if ( ! previewRoot || ! previewItem ) {
			return;
		}

		const previewUrl = getPreviewUrl( previewItem );

		previewRoot.innerHTML = [
			'<div class="directorist-template-preview__bar">',
			'<button type="button" class="directorist-template-preview__back" data-directorist-preview-back>' + escapeHTML( labels.backToLibrary || 'Back to Library' ) + '</button>',
			'<div class="directorist-template-preview__title">' + escapeHTML( previewItem.title || previewItem.id ) + '</div>',
			'<div class="directorist-template-preview__devices" role="group" aria-label="Preview device">',
			renderDeviceButton( 'desktop', labels.desktop || 'Desktop' ),
			renderDeviceButton( 'tablet', labels.tablet || 'Tablet' ),
			renderDeviceButton( 'mobile', labels.mobile || 'Mobile' ),
			'</div>',
			'<div class="directorist-template-preview__actions">',
			'<button type="button" class="directorist-template-preview__button directorist-template-preview__button--secondary" data-directorist-preview-overview>' + escapeHTML( labels.overview || 'Overview' ) + '</button>',
			'<button type="button" class="directorist-template-preview__button directorist-template-preview__button--primary" data-directorist-preview-import="' + escapeHTML( previewItem.id ) + '">' + escapeHTML( state.importing === previewItem.id ? labels.importing || 'Importing...' : labels.import || 'Import' ) + '</button>',
			'<span class="directorist-template-preview__divider" aria-hidden="true"></span>',
			'<a class="directorist-template-preview__icon-button directorist-template-preview__icon-button--close" href="' + escapeHTML( config.returnUrl || '#' ) + '" aria-label="Close"><span class="directorist-template-import__sr">Close</span></a>',
			'</div>',
			'</div>',
			'<div class="directorist-template-preview__status" data-directorist-preview-status hidden></div>',
			'<div class="directorist-template-preview__stage directorist-template-preview__stage--' + escapeHTML( previewDevice ) + ' is-loading">',
			'<div class="directorist-template-preview__loader">' + escapeHTML( labels.loadingPreview || 'Loading preview...' ) + '</div>',
			previewUrl ? '<iframe class="directorist-template-preview__frame" title="' + escapeHTML( previewItem.title || previewItem.id ) + '" src="' + escapeHTML( previewUrl ) + '"></iframe>' : '<div class="directorist-template-preview__empty">' + escapeHTML( labels.empty || 'No preview available.' ) + '</div>',
			'</div>',
		].join( '' );

		syncPreviewStatus();

		const stage = previewRoot.querySelector( '.directorist-template-preview__stage' );
		const iframe = previewRoot.querySelector( '.directorist-template-preview__frame' );

		if ( iframe && stage ) {
			iframe.addEventListener( 'load', function () {
				stage.classList.remove( 'is-loading' );
			}, { once: true } );
		} else if ( stage ) {
			stage.classList.remove( 'is-loading' );
		}
	}

	function syncPreviewStatus() {
		if ( ! previewRoot ) {
			return;
		}

		const status = previewRoot.querySelector( '[data-directorist-preview-status]' );
		const message = state.error || state.notice || '';

		if ( ! status ) {
			return;
		}

		status.hidden = ! message;
		status.className = 'directorist-template-preview__status' + ( state.error ? ' directorist-template-preview__status--error' : '' );
		status.textContent = message;
	}

	function openPreview( itemId ) {
		const item = findItem( itemId );

		if ( ! item || ! getPreviewUrl( item ) ) {
			showDetails( itemId );
			return;
		}

		state.notice = '';
		state.error = '';
		previewItem = item;
		setPreviewMode( true );
		renderPreview();
	}

	function closePreview() {
		previewItem = null;
		setPreviewMode( false );

		if ( previewRoot ) {
			previewRoot.innerHTML = '';
		}
	}

	function loadCatalog( refresh ) {
		state.loading = true;
		state.error = '';
		state.notice = '';
		render();

		request( '/catalog' + ( refresh ? '?refresh=1' : '' ), {
			method: 'GET',
		} )
			.then( function ( response ) {
				catalogItems = normalizeItems( response );
				state.loading = false;
				renderCategories();
				render();
			} )
			.catch( function ( error ) {
				state.loading = false;
				state.error = error && error.message ? error.message : 'Could not load the Directorist template catalog.';
				render();
			} );
	}

	function openImportDialog( itemId ) {
		const item = findItem( itemId );

		if ( ! item ) {
			state.error = labels.noTemplateSelected || 'No Directorist template is available to import in the current view.';
			state.notice = '';
			render();
			syncPreviewStatus();
			return;
		}

		const defaults = getImportOptionDefaults( item );
		const categories = getItemCategories( item );
		const dialog = document.createElement( 'div' );
		dialog.className = 'directorist-template-import-dialog directorist-template-import-dialog--options';
		dialog.innerHTML = [
			'<div class="directorist-template-import-dialog__panel directorist-template-import-dialog__panel--options" role="dialog" aria-modal="true" aria-labelledby="directorist-template-import-options-title">',
			'<button type="button" class="directorist-template-import-dialog__close" aria-label="' + escapeHTML( labels.close || 'Close' ) + '">&times;</button>',
			'<div class="directorist-template-import-dialog__header">',
			'<span class="directorist-template-import-dialog__eyebrow">' + escapeHTML( labels.importOptions || 'Import Options' ) + '</span>',
			'<h2 id="directorist-template-import-options-title">' + escapeHTML( item.title || item.id ) + '</h2>',
			categories.length ? '<p class="directorist-template-import-options__intro">' + categories.map( escapeHTML ).join( ' / ' ) + '</p>' : '',
			'</div>',
			'<form class="directorist-template-import-options__form" data-directorist-template-import-options="' + escapeHTML( item.id ) + '">',
			'<div class="directorist-template-import-options__body">',
			'<label class="directorist-template-import-options__field">',
			'<span>' + escapeHTML( labels.conflictBehavior || 'Existing Items' ) + '</span>',
			'<select name="conflict_behavior">',
			'<option value="update"' + ( 'update' === defaults.conflict_behavior ? ' selected' : '' ) + '>' + escapeHTML( labels.conflictUpdate || 'Update matching imported items' ) + ' ' + escapeHTML( labels.recommended || '(Recommended)' ) + '</option>',
			'<option value="skip"' + ( 'skip' === defaults.conflict_behavior ? ' selected' : '' ) + '>' + escapeHTML( labels.conflictSkip || 'Skip existing imported items' ) + '</option>',
			'<option value="duplicate"' + ( 'duplicate' === defaults.conflict_behavior ? ' selected' : '' ) + '>' + escapeHTML( labels.conflictDuplicate || 'Import as duplicates' ) + '</option>',
			'<option value="replace"' + ( 'replace' === defaults.conflict_behavior ? ' selected' : '' ) + '>' + escapeHTML( labels.conflictReplace || 'Replace existing imported items' ) + '</option>',
			'</select>',
			'</label>',
			renderExtensionRequirements( item ),
			'<div class="directorist-template-import-options__layout">',
			'<div class="directorist-template-import-options__group">',
			'<h3>' + escapeHTML( labels.importScope || 'Import scope' ) + '</h3>',
			renderCheckboxField( 'include_templates', labels.includeTemplates || 'Elementor templates', labels.includeTemplatesHelp || 'Import Elementor theme-builder templates, sections, loop items, and saved templates included in this kit.', defaults.include_templates ),
			renderCheckboxField( 'include_content', labels.includeContent || 'Pages, listings, menus, and taxonomies', labels.includeContentHelp || 'Import WordPress content, Directorist listings, taxonomy data, and menu items included in this kit.', defaults.include_content ),
			'</div>',
			'<div class="directorist-template-import-options__group directorist-template-import-options__group--advanced">',
			'<h3>' + escapeHTML( labels.siteBehavior || 'Site Changes' ) + '</h3>',
			renderCheckboxField( 'apply_site_settings', labels.applySiteSettings || 'Apply site settings', '', defaults.apply_site_settings ),
			renderCheckboxField( 'set_homepage', labels.setHomepage || 'Set imported homepage', '', defaults.set_homepage ),
			renderCheckboxField( 'apply_template_conditions', labels.applyTemplateConditions || 'Activate template conditions', '', defaults.apply_template_conditions ),
			'</div>',
			'</div>',
			'</div>',
			'<div class="directorist-template-import-options__actions">',
			'<button type="button" class="button button-secondary" data-directorist-import-options-cancel>' + escapeHTML( labels.cancel || 'Cancel' ) + '</button>',
			'<button type="submit" class="button button-primary">' + escapeHTML( labels.import || 'Import' ) + '</button>',
			'</div>',
			'</form>',
			'</div>',
		].join( '' );

		document.body.appendChild( dialog );

		dialog.addEventListener( 'click', function ( event ) {
			if ( event.target === dialog || event.target.classList.contains( 'directorist-template-import-dialog__close' ) || event.target.matches( '[data-directorist-import-options-cancel]' ) ) {
				dialog.remove();
			}
		} );

		dialog.addEventListener( 'submit', function ( event ) {
			event.preventDefault();

			const form = event.target;
			const id = form.getAttribute( 'data-directorist-template-import-options' );
			const options = getImportOptionsFromForm( form );

			dialog.remove();
			importItem( id, options );
		} );
	}

	function openZipUploadDialog() {
		const defaults = Object.assign( {}, defaultImportOptions );
		const dialog = document.createElement( 'div' );
		dialog.className = 'directorist-template-import-dialog directorist-template-import-dialog--upload';
		dialog.innerHTML = [
			'<div class="directorist-template-import-dialog__panel directorist-template-import-dialog__panel--options directorist-template-import-dialog__panel--upload" role="dialog" aria-modal="true" aria-labelledby="directorist-template-import-upload-title">',
			'<button type="button" class="directorist-template-import-dialog__close" aria-label="' + escapeHTML( labels.close || 'Close' ) + '">&times;</button>',
			'<div class="directorist-template-import-dialog__header">',
			'<span class="directorist-template-import-dialog__eyebrow">' + escapeHTML( labels.upload || 'Upload' ) + '</span>',
			'<h2 id="directorist-template-import-upload-title">' + escapeHTML( labels.uploadTemplateZip || 'Import From ZIP' ) + '</h2>',
			'<p>' + escapeHTML( labels.uploadTemplateZipDescription || 'Import a Directorist Elementor website package ZIP exported from the template source.' ) + '</p>',
			'</div>',
			'<form class="directorist-template-import-options__form directorist-template-import-upload__form" enctype="multipart/form-data" data-directorist-template-upload-form>',
			'<div class="directorist-template-import-options__body">',
			'<label class="directorist-template-import-upload__drop">',
			'<input type="file" name="template_zip" accept=".zip,application/zip,application/x-zip-compressed" data-directorist-template-zip />',
			'<span class="directorist-template-import-upload__icon" aria-hidden="true"></span>',
			'<span class="directorist-template-import-upload__copy">',
			'<strong>' + escapeHTML( labels.chooseTemplateZip || 'Choose Package ZIP' ) + '</strong>',
			'<small>' + escapeHTML( labels.uploadTemplateZipDescription || 'Import a Directorist Elementor website package ZIP exported from the template source.' ) + '</small>',
			'<em data-directorist-template-zip-name hidden></em>',
			'</span>',
			'</label>',
			'<div class="directorist-template-import-upload__error" data-directorist-template-upload-error hidden></div>',
			'<label class="directorist-template-import-options__field">',
			'<span>' + escapeHTML( labels.conflictBehavior || 'Existing Items' ) + '</span>',
			'<select name="conflict_behavior">',
			'<option value="update"' + ( 'update' === defaults.conflict_behavior ? ' selected' : '' ) + '>' + escapeHTML( labels.conflictUpdate || 'Update matching imported items' ) + ' ' + escapeHTML( labels.recommended || '(Recommended)' ) + '</option>',
			'<option value="skip"' + ( 'skip' === defaults.conflict_behavior ? ' selected' : '' ) + '>' + escapeHTML( labels.conflictSkip || 'Skip existing imported items' ) + '</option>',
			'<option value="duplicate"' + ( 'duplicate' === defaults.conflict_behavior ? ' selected' : '' ) + '>' + escapeHTML( labels.conflictDuplicate || 'Import as duplicates' ) + '</option>',
			'<option value="replace"' + ( 'replace' === defaults.conflict_behavior ? ' selected' : '' ) + '>' + escapeHTML( labels.conflictReplace || 'Replace existing imported items' ) + '</option>',
			'</select>',
			'</label>',
			'<div class="directorist-template-import-options__layout">',
			'<div class="directorist-template-import-options__group">',
			'<h3>' + escapeHTML( labels.importScope || 'Import scope' ) + '</h3>',
			renderCheckboxField( 'include_templates', labels.includeTemplates || 'Elementor templates', labels.includeTemplatesHelp || 'Import Elementor theme-builder templates, sections, loop items, and saved templates included in this kit.', defaults.include_templates ),
			renderCheckboxField( 'include_content', labels.includeContent || 'Pages, listings, menus, and taxonomies', labels.includeContentHelp || 'Import WordPress content, Directorist listings, taxonomy data, and menu items included in this kit.', defaults.include_content ),
			'</div>',
			'<div class="directorist-template-import-options__group directorist-template-import-options__group--advanced">',
			'<h3>' + escapeHTML( labels.siteBehavior || 'Site Changes' ) + '</h3>',
			renderCheckboxField( 'apply_site_settings', labels.applySiteSettings || 'Apply site settings', '', defaults.apply_site_settings ),
			renderCheckboxField( 'set_homepage', labels.setHomepage || 'Set imported homepage', '', defaults.set_homepage ),
			renderCheckboxField( 'apply_template_conditions', labels.applyTemplateConditions || 'Activate template conditions', '', defaults.apply_template_conditions ),
			'</div>',
			'</div>',
			'</div>',
			'<div class="directorist-template-import-options__actions">',
			'<button type="button" class="button button-secondary" data-directorist-import-options-cancel>' + escapeHTML( labels.cancel || 'Cancel' ) + '</button>',
			'<button type="submit" class="button button-primary">' + escapeHTML( labels.uploadImport || 'Upload and Import' ) + '</button>',
			'</div>',
			'</form>',
			'</div>',
		].join( '' );

		document.body.appendChild( dialog );

		dialog.addEventListener( 'click', function ( event ) {
			if ( state.importing === '__upload__' ) {
				return;
			}

			if ( event.target === dialog || event.target.classList.contains( 'directorist-template-import-dialog__close' ) || event.target.matches( '[data-directorist-import-options-cancel]' ) ) {
				dialog.remove();
			}
		} );

		dialog.addEventListener( 'change', function ( event ) {
			if ( ! event.target.matches( '[data-directorist-template-zip]' ) ) {
				return;
			}

			const file = event.target.files && event.target.files[ 0 ];
			const fileName = dialog.querySelector( '[data-directorist-template-zip-name]' );

			if ( fileName ) {
				fileName.hidden = ! file;
				fileName.textContent = file ? ( labels.selectedTemplateZip || 'Selected package' ) + ': ' + file.name : '';
			}

			setUploadDialogError( dialog, '' );
		} );

		dialog.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			importUploadedPackage( event.target, dialog );
		} );
	}

	function setUploadDialogError( dialog, message ) {
		const error = dialog.querySelector( '[data-directorist-template-upload-error]' );

		if ( ! error ) {
			return;
		}

		error.hidden = ! message;
		error.textContent = message || '';
	}

	function setUploadDialogBusy( dialog, busy ) {
		dialog.querySelectorAll( 'input, select, button' ).forEach( function ( control ) {
			if ( control.matches( '[data-directorist-import-options-cancel], .directorist-template-import-dialog__close' ) ) {
				control.disabled = !! busy;
				return;
			}

			control.disabled = !! busy;
		} );

		const submit = dialog.querySelector( 'button[type="submit"]' );

		if ( submit ) {
			submit.textContent = busy ? labels.uploading || 'Uploading...' : labels.uploadImport || 'Upload and Import';
		}
	}

	function importUploadedPackage( form, dialog ) {
		if ( state.importing ) {
			return;
		}

		const fileInput = form.querySelector( '[name="template_zip"]' );
		const file = fileInput && fileInput.files ? fileInput.files[ 0 ] : null;

		if ( ! file ) {
			setUploadDialogError( dialog, labels.noTemplateZipSelected || 'Choose a Directorist Elementor website package ZIP before importing.' );
			return;
		}

		const importOptions = getImportOptionsFromForm( form );
		const body = new FormData();

		body.append( 'template_zip', file, file.name );
		Object.keys( importOptions ).forEach( function ( key ) {
			body.append( key, typeof importOptions[ key ] === 'boolean' ? ( importOptions[ key ] ? '1' : '0' ) : String( importOptions[ key ] ) );
		} );

		state.importing = '__upload__';
		state.error = '';
		state.notice = '';
		startProgress( labels.progressProcessing || 'Processing the website package', 'upload', importOptions );
		setUploadDialogBusy( dialog, true );
		setUploadDialogError( dialog, '' );
		dialog.remove();
		render();
		syncPreviewStatus();

		window.fetch( endpoint( '/upload' ), {
			body: body,
			credentials: 'same-origin',
			headers: config.nonce ? { 'X-WP-Nonce': config.nonce } : {},
			method: 'POST',
		} )
			.then( function ( response ) {
				return response.json().catch( function () {
					return {};
				} ).then( function ( data ) {
					if ( ! response.ok ) {
						throw new Error( data && data.message ? data.message : labels.failed || 'Import failed.' );
					}

					return data;
				} );
			} )
			.then( continueImport )
			.then( function ( response ) {
				completeImport( response, dialog );
			} )
			.catch( function ( error ) {
				state.importing = '';
				state.error = error && error.message ? error.message : labels.failed || 'Import failed.';
				setUploadDialogBusy( dialog, false );
				failProgress( state.error );
				render();
				syncPreviewStatus();
			} );
	}

	function importItem( itemId, options ) {
		if ( ! itemId || state.importing ) {
			return;
		}

		const importOptions = normalizeImportOptions( options );
		state.importing = itemId;
		state.error = '';
		state.notice = '';
		const item = findItem( itemId );
		startProgress( item && item.title ? item.title : labels.importing || 'Importing...', 'catalog', importOptions );
		render();
		syncPreviewStatus();

		request( '/import', {
			method: 'POST',
			data: Object.assign( { item_id: itemId }, importOptions ),
		} )
			.then( continueImport )
			.then( function ( response ) {
				completeImport( response );
			} )
			.catch( function ( error ) {
				state.importing = '';
				state.error = error && error.message ? error.message : labels.failed || 'Import failed.';
				failProgress( state.error );
				render();
				syncPreviewStatus();
			} );
	}

	function showDetails( itemId ) {
		if ( ! itemId ) {
			return;
		}

		state.error = '';

		request( '/items/' + encodeURIComponent( itemId ), {
			method: 'GET',
		} )
			.then( function ( item ) {
				openDetailsDialog( item );
			} )
			.catch( function ( error ) {
				state.error = error && error.message ? error.message : 'Could not load item details.';
				render();
				syncPreviewStatus();
			} );
	}

	function openDetailsDialog( item ) {
		const previewUrl = getPreviewUrl( item );
		const thumbnailUrl = getThumbnailUrl( item );
		const requirements = item.requirements && typeof item.requirements === 'object' ? Object.keys( item.requirements ).map( function ( key ) {
			return key + ': ' + item.requirements[ key ];
		} ) : [];

		const dialog = document.createElement( 'div' );
		dialog.className = 'directorist-template-import-dialog directorist-template-import-dialog--overview';
		dialog.innerHTML = [
			'<div class="directorist-template-import-dialog__panel directorist-template-import-dialog__panel--overview" role="dialog" aria-modal="true" aria-labelledby="directorist-template-import-overview-title">',
			'<button type="button" class="directorist-template-import-dialog__close" aria-label="' + escapeHTML( labels.close || 'Close' ) + '">&times;</button>',
			'<div class="directorist-template-import-dialog__header">',
			'<span class="directorist-template-import-dialog__eyebrow">' + escapeHTML( labels.overview || 'Overview' ) + '</span>',
			'<h2 id="directorist-template-import-overview-title">' + escapeHTML( item.title || item.id ) + '</h2>',
			item.description ? '<p>' + escapeHTML( item.description ) + '</p>' : '',
			'</div>',
			'<div class="directorist-template-import-overview__body">',
			thumbnailUrl ? '<img class="directorist-template-import-dialog__thumb" src="' + escapeHTML( thumbnailUrl ) + '" alt="" />' : '',
			'<dl class="directorist-template-import-overview__meta">',
			'<div><dt>Type</dt><dd>' + escapeHTML( getPackageTypeLabel( item.package_type ) ) + '</dd></div>',
			'<div><dt>Builder</dt><dd>' + escapeHTML( item.builder || 'elementor' ) + '</dd></div>',
			requirements.length ? '<div><dt>Requirements</dt><dd>' + escapeHTML( requirements.join( ', ' ) ) + '</dd></div>' : '',
			getExtensionRequirementItems( item ).length ? '<div><dt>' + escapeHTML( labels.requiredExtensions || 'Required plugins and extensions' ) + '</dt><dd>' + escapeHTML( getExtensionRequirementItems( item ).map( function ( requirement ) {
				const provenance = getRequirementProvenance( requirement );
				return ( requirement.name || requirement.slug || '' ) + ' (' + [ getRequirementStatusLabel( requirement.status || 'missing' ), provenance.label ].filter( Boolean ).join( '; ' ) + ')';
			} ).join( ', ' ) ) + '</dd></div>' : '',
			'</dl>',
			'</div>',
			'<div class="directorist-template-import-dialog__actions">',
			previewUrl ? '<button type="button" class="button button-secondary" data-directorist-dialog-preview="' + escapeHTML( item.id ) + '">' + escapeHTML( labels.showDemo || 'View Demo' ) + '</button>' : '',
			'<button type="button" class="button button-primary" data-directorist-dialog-import="' + escapeHTML( item.id ) + '">' + escapeHTML( labels.import || 'Import' ) + '</button>',
			'</div>',
			'</div>',
		].join( '' );

		document.body.appendChild( dialog );

		dialog.addEventListener( 'click', function ( event ) {
			if ( event.target === dialog || event.target.classList.contains( 'directorist-template-import-dialog__close' ) ) {
				dialog.remove();
			}

			const previewButton = event.target.closest( '[data-directorist-dialog-preview]' );
			const importButton = event.target.closest( '[data-directorist-dialog-import]' );

			if ( previewButton ) {
				const id = previewButton.getAttribute( 'data-directorist-dialog-preview' );
				dialog.remove();
				openPreview( id );
			}

			if ( importButton ) {
				const id = importButton.getAttribute( 'data-directorist-dialog-import' );
				dialog.remove();
				openImportDialog( id );
			}
		} );
	}

	document.addEventListener( 'click', function ( event ) {
		const refreshButton = event.target.closest( '[data-directorist-template-refresh]' );
		const primaryImportButton = event.target.closest( '[data-directorist-template-primary-import]' );
		const infoButton = event.target.closest( '[data-directorist-template-info]' );
		const viewButton = event.target.closest( '[data-directorist-template-view]' );
		const favoriteButton = event.target.closest( '[data-directorist-template-favorite]' );
		const previewButton = event.target.closest( '[data-directorist-template-preview]' );
		const importButton = event.target.closest( '[data-directorist-template-import]' );
		const detailsButton = event.target.closest( '[data-directorist-template-details]' );
		const previewBackButton = event.target.closest( '[data-directorist-preview-back]' );
		const previewDeviceButton = event.target.closest( '[data-directorist-preview-device]' );
		const previewOverviewButton = event.target.closest( '[data-directorist-preview-overview]' );
		const previewImportButton = event.target.closest( '[data-directorist-preview-import]' );
		const progressCloseButton = event.target.closest( '[data-directorist-template-progress-close]' );
		const activateRequirementButton = event.target.closest( '[data-directorist-requirement-activate]' );

		if ( activateRequirementButton ) {
			event.preventDefault();
			activateRequirement( activateRequirementButton );
			return;
		}

		if ( progressCloseButton ) {
			closeProgress();
			return;
		}

		if ( refreshButton ) {
			loadCatalog( true );
			return;
		}

		if ( viewButton ) {
			activeView = viewButton.getAttribute( 'data-directorist-template-view' ) || 'all';
			render();
			return;
		}

		if ( favoriteButton ) {
			const itemId = favoriteButton.getAttribute( 'data-directorist-template-favorite' );

			if ( itemId ) {
				if ( favoriteIds.has( itemId ) ) {
					favoriteIds.delete( itemId );
				} else {
					favoriteIds.add( itemId );
				}

				saveFavorites();
				render();
			}

			return;
		}

		if ( previewButton ) {
			event.preventDefault();
			openPreview( previewButton.getAttribute( 'data-directorist-template-preview' ) );
			return;
		}

		if ( previewBackButton ) {
			closePreview();
			return;
		}

		if ( previewDeviceButton ) {
			previewDevice = previewDeviceButton.getAttribute( 'data-directorist-preview-device' ) || 'desktop';
			renderPreview();
			return;
		}

		if ( previewOverviewButton && previewItem ) {
			showDetails( previewItem.id );
			return;
		}

		if ( previewImportButton ) {
			openImportDialog( previewImportButton.getAttribute( 'data-directorist-preview-import' ) );
			return;
		}

		if ( primaryImportButton ) {
			openZipUploadDialog();
			return;
		}

		if ( infoButton ) {
			state.notice = labels.infoDescription || 'Select a Directorist Elementor website template, preview the demo, then import it into your Elementor template library.';
			render();
			return;
		}

		if ( importButton ) {
			openImportDialog( importButton.getAttribute( 'data-directorist-template-import' ) );
			return;
		}

		if ( detailsButton ) {
			showDetails( detailsButton.getAttribute( 'data-directorist-template-details' ) );
		}
	} );

	document.addEventListener( 'input', function ( event ) {
		if ( event.target.matches( '[data-directorist-template-search]' ) ) {
			activeSearch = event.target.value || '';
			render();
		}
	} );

	document.addEventListener( 'change', function ( event ) {
		if ( event.target.matches( '[data-directorist-template-filter-category]' ) ) {
			const category = event.target.value || '';

			if ( ! category ) {
				activeCategories.clear();
			} else if ( event.target.checked ) {
				activeCategories.add( category );
			} else {
				activeCategories.delete( category );
			}

			render();
		}

		if ( event.target.matches( '[data-directorist-template-sort]' ) ) {
			activeSort = event.target.value || 'featured';
			render();
		}
	} );

	loadCatalog( true );
	resumeImport();
} )( window, document );
