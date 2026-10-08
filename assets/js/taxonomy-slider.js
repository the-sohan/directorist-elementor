/**
 * Directorist Elementor — Taxonomy Slider (Swiper)
 *
 * Initializes Swiper sliders for All Categories / All Locations widgets
 * when display_mode is set to "slider". Mirrors the Gutenberg integration's
 * taxonomy-slider behavior for feature parity.
 *
 * @package DirectoristElementor
 */
(function () {
  "use strict";

  var TAXONOMY_SLIDER_SELECTOR = ".directorist-gbi-taxonomy-slider";
  var IMAGE_SLIDER_SELECTOR = ".directorist-elementor-listing-card-images-slider__swiper";
  var SLIDER_SELECTOR = TAXONOMY_SLIDER_SELECTOR + "," + IMAGE_SLIDER_SELECTOR;
  var PAGINATION_SELECTOR = ".directorist-gbi-taxonomy-slider__pagination,.directorist-elementor-listing-card-images-slider__pagination,.directorist-swiper__pagination--listing";
  var IMAGE_SLIDER_ROOT_SELECTOR = ".directorist-elementor-listing-card-images-slider";
  var IMAGE_SELECTOR = ".directorist-elementor-listing-card-images-slider__image";
  var MASONRY_SELECTOR = ".directorist-elementor-listing-card-images-slider__masonry";
  var MASONRY_ITEM_SELECTOR = ".directorist-elementor-listing-card-images-slider__masonry-item";
  var LIGHTBOX_TRIGGER_SELECTOR = "[data-directorist-gbi-lightbox-trigger]";
  var LIGHTBOX_SOURCE_SELECTOR = "[data-directorist-gbi-lightbox-source]";
  var LIGHTBOX_ANIMATION_CLASSES = [
    "directorist-gbi-listing-slider-lightbox--animation-elastic",
    "directorist-gbi-listing-slider-lightbox--animation-zoom",
    "directorist-gbi-listing-slider-lightbox--animation-fade",
    "directorist-gbi-listing-slider-lightbox--animation-none",
  ];
  var DEFAULT_LIGHTBOX_SETTINGS = {
    animation: "elastic",
    closeOnOverlay: true,
    loop: true,
    showCounter: true,
    showNavigation: true,
    showThumbnails: true,
  };
  var RETRY_DELAY = 160;
  var MAX_RETRIES = 40;
  var SLIDER_MUTATION_ATTRIBUTES = [
    "data-sw-items",
    "data-sw-responsive",
    "data-sw-margin",
    "data-sw-loop",
    "data-sw-perslide",
    "data-sw-speed",
    "data-sw-autoplay",
    "data-sw-effect",
    "data-gbi-slides-per-view",
    "data-gbi-breakpoints",
    "data-gbi-space-between",
    "data-gbi-show-arrows",
    "data-gbi-show-dots",
    "data-gbi-effect",
    "data-gbi-autoplay",
    "data-gbi-transition-speed",
    "data-gbi-delay",
    "data-gbi-pause-on-hover",
    "data-gbi-grid-rows",
  ];

  var SUPPORTED_EFFECTS = [
    "slide", "fade", "grid", "thumb", "creative",
    "cards", "cube", "flip", "coverflow",
  ];

  var SINGLE_SLIDE_EFFECTS = {
    fade: true,
    cube: true,
    flip: true,
    cards: true,
    creative: true,
  };

  var pendingScriptLoads = {};
  var pendingStyleLoads = {};
  var bootstrapStates = [];
  var bootstrapStateElements = [];
  var masonryObservers = typeof WeakMap === "function" ? new WeakMap() : null;
  var initializedLightboxTriggers = typeof WeakSet === "function" ? new WeakSet() : null;
  var fallbackLightboxTriggers = [];
  var lightboxState = null;

  function getBootstrapState(element) {
    var idx = bootstrapStateElements.indexOf(element);
    if (idx !== -1) {
      return bootstrapStates[idx];
    }
    var state = { timer: 0, retries: 0 };
    bootstrapStateElements.push(element);
    bootstrapStates.push(state);
    return state;
  }

  function parseIntAttr(value, fallback) {
    var parsed = parseInt(value, 10);
    return isFinite(parsed) ? parsed : fallback;
  }

  function parseBoolAttr(value, fallback) {
    if (typeof value !== "string") return !!fallback;
    if (value === "true" || value === "1") return true;
    if (value === "false" || value === "0") return false;
    return !!fallback;
  }

  function parseBreakpoints(value) {
    if (typeof value !== "string" || value === "") return null;
    try {
      var parsed = JSON.parse(value);
      return parsed && typeof parsed === "object" ? parsed : null;
    } catch (e) {
      return null;
    }
  }

  function normalizeEffect(value) {
    var name = String(value || "slide").trim().toLowerCase();
    return SUPPORTED_EFFECTS.indexOf(name) !== -1 ? name : "slide";
  }

  function resolveDirectoristAssetsBase() {
    var candidates = [window, window.parent, window.top];
    for (var i = 0; i < candidates.length; i++) {
      try {
        var url = String((candidates[i] && candidates[i].directorist && candidates[i].directorist.assets_url) || "").trim();
        if (url) return url.replace(/\/+$/, "");
      } catch (e) {
        // cross-origin
      }
    }
    return "";
  }

  function resolveSwiperConstructor() {
    var candidates = [window, window.parent, window.top];
    for (var i = 0; i < candidates.length; i++) {
      try {
        if (candidates[i] && typeof candidates[i].Swiper === "function") {
          if (!window.Swiper) window.Swiper = candidates[i].Swiper;
          return candidates[i].Swiper;
        }
      } catch (e) {
        // cross-origin
      }
    }
    return null;
  }

  function ensureSwiperStyle() {
    var assetsBase = resolveDirectoristAssetsBase();
    if (!assetsBase) return;
    var src = assetsBase + "/vendor-css/swiper.min.css";
    if (pendingStyleLoads[src]) return;

    var existing = document.querySelectorAll('link[rel="stylesheet"]');
    for (var i = 0; i < existing.length; i++) {
      if (existing[i].href === src) return;
    }

    pendingStyleLoads[src] = true;
    var link = document.createElement("link");
    link.rel = "stylesheet";
    link.href = src;
    (document.head || document.body).appendChild(link);
  }

  function ensureSwiperScript() {
    if (resolveSwiperConstructor()) return;
    var assetsBase = resolveDirectoristAssetsBase();
    if (!assetsBase) return;
    var src = assetsBase + "/vendor-js/swiper.min.js";
    if (pendingScriptLoads[src]) return;

    var scripts = document.getElementsByTagName("script");
    for (var i = 0; i < scripts.length; i++) {
      if (scripts[i].src === src) {
        pendingScriptLoads[src] = true;
        return;
      }
    }

    pendingScriptLoads[src] = true;
    var script = document.createElement("script");
    script.src = src;
    script.async = true;
    script.defer = true;
    (document.head || document.body).appendChild(script);
  }

  function resolveCurrentSlidesPerView(base, breakpoints) {
    if (!breakpoints || typeof breakpoints !== "object") return base;
    var vw = window.innerWidth || 0;
    var keys = Object.keys(breakpoints)
      .map(function (k) { return parseInt(k, 10); })
      .filter(function (v) { return isFinite(v); })
      .sort(function (a, b) { return a - b; });

    var result = base;
    for (var i = 0; i < keys.length; i++) {
      if (vw >= keys[i]) {
        var bp = breakpoints[String(keys[i])] || breakpoints[keys[i]];
        var spv = parseInt(bp && bp.slidesPerView, 10);
        if (isFinite(spv) && spv > 0) result = spv;
      }
    }
    return result;
  }

  function destroyInstance(el) {
    var instance = el.__directoristTaxonomySwiper || el.swiper;
    if (instance && typeof instance.destroy === "function") {
      instance.destroy(true, true);
    }
    delete el.__directoristTaxonomySwiper;
    delete el.__directoristTaxonomySwiperConfig;
  }

  function createFallbackConfig(config) {
    var next = {};
    for (var key in config) {
      if (config.hasOwnProperty(key)) next[key] = config[key];
    }
    next.effect = "slide";
    var remove = ["fadeEffect", "cubeEffect", "flipEffect", "coverflowEffect", "creativeEffect", "cardsEffect", "grid"];
    for (var i = 0; i < remove.length; i++) {
      delete next[remove[i]];
    }
    return next;
  }

  function initInstance(el) {
    var SwiperCtor = resolveSwiperConstructor();
    if (typeof SwiperCtor !== "function") return;

    // Destroy non-managed existing instance.
    var existing = el.swiper;
    if (existing && !existing.destroyed) {
      if (el.__directoristTaxonomySwiper !== existing && typeof existing.destroy === "function") {
        existing.destroy(true, true);
      }
    }

    var slides = el.querySelectorAll(".swiper-slide");
    if (slides.length === 0) {
      destroyInstance(el);
      return;
    }
    el.classList.toggle("slider-has-one-item", slides.length === 1);

    var ds = el.dataset || {};
    var baseSPV = parseIntAttr(ds.swItems || ds.gbiSlidesPerView, 3);
    var breakpoints = parseBreakpoints(ds.swResponsive || ds.gbiBreakpoints) || {};

    var effect = normalizeEffect(ds.swEffect || ds.gbiEffect);
    var isGrid = effect === "grid";
    var isThumb = effect === "thumb";
    var swiperEffect = (isGrid || isThumb) ? "slide" : effect;
    var forceSingle = !!SINGLE_SLIDE_EFFECTS[swiperEffect];
    var gridRows = isGrid ? Math.max(1, parseIntAttr(ds.swGridRows || ds.gbiGridRows, 2)) : 1;

    var effectiveBP = forceSingle ? {} : breakpoints;
    var effectiveSPV = forceSingle ? 1 : baseSPV;
    var effectiveSPG = forceSingle ? 1 : Math.max(1, parseIntAttr(ds.swPerslide, 1));

    var pauseOnHover = parseBoolAttr(ds.swPauseOnHover || ds.gbiPauseOnHover, true);
    var currentSPV = resolveCurrentSlidesPerView(effectiveSPV, effectiveBP);
    var visibleCount = currentSPV * Math.max(1, gridRows);

    var showArrows = parseBoolAttr(ds.gbiShowArrows, true);
    var showDots = parseBoolAttr(ds.gbiShowDots, true);

    var loopByAttr = parseBoolAttr(ds.swLoop, true);
    var loopEnabled = loopByAttr && slides.length > visibleCount && !isGrid;

    var speed = Math.max(100, parseIntAttr(ds.swSpeed || ds.gbiTransitionSpeed, 500));
    var delay = Math.max(100, parseIntAttr(ds.swDelay || ds.gbiDelay, speed));
    var autoplay = parseBoolAttr(ds.swAutoplay || ds.gbiAutoplay, false);

    var nextEl = el.querySelector(".directorist-swiper__nav--next-listing");
    var prevEl = el.querySelector(".directorist-swiper__nav--prev-listing");
    var paginationEl = el.querySelector(".directorist-gbi-taxonomy-slider__pagination") ||
      el.querySelector(".directorist-elementor-listing-card-images-slider__pagination") ||
      el.querySelector(".directorist-swiper__pagination--listing");

    var spaceBetween = parseIntAttr(ds.swMargin || ds.gbiSpaceBetween, 16);

    var configSig = JSON.stringify({
      slideCount: slides.length,
      slidesPerView: effectiveSPV,
      spaceBetween: spaceBetween,
      slidesPerGroup: effectiveSPG,
      gridRows: gridRows,
      requestedEffect: effect,
      resolvedEffect: swiperEffect,
      loop: loopEnabled,
      transitionSpeed: speed,
      autoplayDelay: delay,
      breakpoints: effectiveBP,
      nextEl: !!nextEl,
      prevEl: !!prevEl,
      paginationEl: !!paginationEl && showDots,
      shouldAutoplay: autoplay,
      pauseOnHover: pauseOnHover,
    });

    var prev = el.__directoristTaxonomySwiper;
    if (prev && !prev.destroyed) {
      if (el.__directoristTaxonomySwiperConfig === configSig) {
        if (typeof prev.update === "function") prev.update();
        return;
      }
      prev.destroy(true, true);
    }

    if (paginationEl) {
      paginationEl.hidden = !showDots;
      paginationEl.innerHTML = "";
      paginationEl.classList.remove(
        "swiper-pagination-clickable",
        "swiper-pagination-bullets",
        "swiper-pagination-horizontal",
        "swiper-pagination-lock"
      );
    }

    var config = {
      slidesPerView: effectiveSPV,
      spaceBetween: spaceBetween,
      loop: loopEnabled,
      slidesPerGroup: effectiveSPG,
      speed: speed,
      breakpoints: effectiveBP,
      effect: swiperEffect,
      watchOverflow: true,
      observer: true,
      observeParents: true,
      nested: !!el.closest(".directorist-gbi-related-slider, .directorist-elementor-listings-loop-slider"),
      allowTouchMove: true,
      simulateTouch: true,
      preventClicks: false,
      preventClicksPropagation: false,
    };

    if (isGrid) {
      config.grid = { rows: gridRows, fill: "row" };
    }

    if (isThumb) {
      config.watchSlidesProgress = true;
      config.slideToClickedSlide = true;
      config.freeMode = true;
      config.spaceBetween = Math.min(12, config.spaceBetween);
    }

    if (showArrows && (nextEl || prevEl)) {
      config.navigation = { nextEl: nextEl, prevEl: prevEl };
    }

    if (showDots && paginationEl) {
      config.pagination = { el: paginationEl, type: "bullets", clickable: true };
    }

    if (autoplay) {
      config.autoplay = {
        delay: delay,
        disableOnInteraction: false,
        pauseOnMouseEnter: pauseOnHover,
      };
    }

    if (swiperEffect === "fade") {
      config.fadeEffect = { crossFade: true };
    }
    if (swiperEffect === "cube") {
      config.cubeEffect = { shadow: true, slideShadows: true, shadowOffset: 20, shadowScale: 0.94 };
    }
    if (swiperEffect === "flip") {
      config.flipEffect = { slideShadows: true, limitRotation: true };
    }
    if (swiperEffect === "coverflow") {
      config.coverflowEffect = { rotate: 35, stretch: 0, depth: 120, modifier: 1, slideShadows: true };
      config.centeredSlides = true;
    }
    if (swiperEffect === "creative") {
      config.creativeEffect = {
        prev: { shadow: true, translate: [0, 0, -400] },
        next: { translate: ["100%", 0, 0] },
      };
    }
    if (swiperEffect === "cards") {
      config.cardsEffect = { slideShadows: true, rotate: true, perSlideOffset: 8, perSlideRotate: 2 };
    }

    var instance = null;
    try {
      instance = new SwiperCtor(el, config);
    } catch (e) {
      try {
        instance = new SwiperCtor(el, createFallbackConfig(config));
      } catch (e2) {
        return;
      }
    }

    el.__directoristTaxonomySwiper = instance;
    el.__directoristTaxonomySwiperConfig = configSig;

    if (showDots && paginationEl && instance && instance.pagination) {
      if (typeof instance.pagination.render === "function") instance.pagination.render();
      if (typeof instance.pagination.update === "function") instance.pagination.update();
    }
  }

  function collectSliders(root) {
    var ctx = root || document;
    if (typeof ctx.querySelectorAll !== "function") return [];
    var nodes = ctx.querySelectorAll(SLIDER_SELECTOR);
    var result = [];
    for (var i = 0; i < nodes.length; i++) {
      if (nodes[i].nodeType === 1) result.push(nodes[i]);
    }
    return result;
  }

  function tryInit(root, elements) {
    var sliders = elements || collectSliders(root);
    if (sliders.length === 0) return false;
    var Ctor = resolveSwiperConstructor();
    if (typeof Ctor !== "function") return false;
    for (var i = 0; i < sliders.length; i++) {
      initInstance(sliders[i]);
    }
    return true;
  }

  function scheduleBootstrap(root) {
    var stateEl = root || document;
    var state = getBootstrapState(stateEl);
    if (state.timer) {
      clearTimeout(state.timer);
      state.timer = 0;
    }
    if (state.retries >= MAX_RETRIES) {
      state.retries = 0;
      return;
    }
    state.timer = setTimeout(function () {
      state.timer = 0;
      state.retries++;
      var sliders = collectSliders(root);
      if (sliders.length === 0) {
        state.retries = 0;
        return;
      }
      ensureSwiperStyle();
      ensureSwiperScript();
      if (tryInit(root, sliders)) {
        state.retries = 0;
        return;
      }
      scheduleBootstrap(root);
    }, RETRY_DELAY);
  }

  function initAll(root) {
    var sliders = collectSliders(root);
    if (sliders.length === 0) return;
    ensureSwiperStyle();
    ensureSwiperScript();
    if (tryInit(root, sliders)) return;
    scheduleBootstrap(root);
  }

  function destroyAll(root) {
    var stateEl = root || document;
    var state = getBootstrapState(stateEl);
    if (state.timer) {
      clearTimeout(state.timer);
      state.timer = 0;
    }
    state.retries = 0;
    var sliders = collectSliders(root);
    for (var i = 0; i < sliders.length; i++) {
      destroyInstance(sliders[i]);
    }
  }

  function parsePixelValue(value, fallback) {
    var parsed = parseFloat(value);
    return isFinite(parsed) ? parsed : (fallback || 0);
  }

  function getVisibleMasonryItems(masonryEl) {
    var nodes = masonryEl ? masonryEl.querySelectorAll(MASONRY_ITEM_SELECTOR) : [];
    var result = [];
    for (var i = 0; i < nodes.length; i++) {
      if (window.getComputedStyle(nodes[i]).display !== "none") {
        result.push(nodes[i]);
      }
    }
    return result;
  }

  function layoutMasonry(masonryEl) {
    if (!masonryEl || !masonryEl.isConnected) return;

    var computed = window.getComputedStyle(masonryEl);
    var rowHeight = Math.max(1, parsePixelValue(computed.gridAutoRows, 8));
    var rowGap = Math.max(0, parsePixelValue(computed.rowGap, 0));
    var spanUnit = rowHeight + rowGap;
    var items = getVisibleMasonryItems(masonryEl);

    for (var i = 0; i < items.length; i++) {
      items[i].style.gridRowEnd = "auto";
      var itemHeight = items[i].getBoundingClientRect().height;
      var itemSpan = Math.max(1, Math.ceil((itemHeight + rowGap) / spanUnit));
      items[i].style.gridRowEnd = "span " + itemSpan;
    }
  }

  function scheduleMasonryLayout(masonryEl) {
    if (!masonryEl || !masonryEl.isConnected) return;
    var masonryWindow = (masonryEl.ownerDocument && masonryEl.ownerDocument.defaultView) || window;

    if (masonryEl.__directoristElementorMasonryFrame) {
      masonryWindow.cancelAnimationFrame(masonryEl.__directoristElementorMasonryFrame);
    }

    masonryEl.__directoristElementorMasonryFrame = masonryWindow.requestAnimationFrame(function () {
      masonryEl.__directoristElementorMasonryFrame = 0;
      layoutMasonry(masonryEl);
    });
  }

  function initializeMasonry(masonryEl) {
    if (!masonryEl) return;

    scheduleMasonryLayout(masonryEl);

    if (masonryObservers) {
      var existingObserver = masonryObservers.get(masonryEl);
      if (!existingObserver && typeof ResizeObserver === "function") {
        existingObserver = new ResizeObserver(function () {
          scheduleMasonryLayout(masonryEl);
        });
        existingObserver.observe(masonryEl);
        masonryObservers.set(masonryEl, existingObserver);
      }

      if (existingObserver) {
        var items = getVisibleMasonryItems(masonryEl);
        for (var i = 0; i < items.length; i++) {
          existingObserver.observe(items[i]);
        }
      }
    }

    var images = masonryEl.querySelectorAll("img");
    for (var j = 0; j < images.length; j++) {
      if (images[j].complete) continue;
      images[j].addEventListener("load", function () { scheduleMasonryLayout(masonryEl); }, { once: true });
      images[j].addEventListener("error", function () { scheduleMasonryLayout(masonryEl); }, { once: true });
    }
  }

  function initializeMasonryLayouts(root) {
    var ctx = root || document;
    if (!ctx || typeof ctx.querySelectorAll !== "function") return;

    var masonryElements = [];
    if (ctx.matches && ctx.matches(MASONRY_SELECTOR)) masonryElements.push(ctx);

    var found = ctx.querySelectorAll(MASONRY_SELECTOR);
    for (var i = 0; i < found.length; i++) {
      masonryElements.push(found[i]);
    }

    for (var j = 0; j < masonryElements.length; j++) {
      initializeMasonry(masonryElements[j]);
    }
  }

  function hasInitializedLightboxTrigger(triggerEl) {
    if (initializedLightboxTriggers) {
      return initializedLightboxTriggers.has(triggerEl);
    }
    return fallbackLightboxTriggers.indexOf(triggerEl) !== -1;
  }

  function markInitializedLightboxTrigger(triggerEl) {
    if (initializedLightboxTriggers) {
      initializedLightboxTriggers.add(triggerEl);
      return;
    }
    fallbackLightboxTriggers.push(triggerEl);
  }

  function getLightboxItems(triggerEl) {
    var root = triggerEl && triggerEl.closest ? triggerEl.closest(IMAGE_SLIDER_ROOT_SELECTOR) : null;
    if (!root) return [];

    var result = [];
    var sources = root.querySelectorAll(LIGHTBOX_SOURCE_SELECTOR);
    for (var i = 0; i < sources.length; i++) {
      var src = (sources[i].dataset && (sources[i].dataset.fullSrc || sources[i].dataset.src)) || "";
      if (src) {
        result.push({
          src: src,
          alt: (sources[i].dataset && sources[i].dataset.alt) || "",
        });
      }
    }

    if (result.length > 0) return result;

    var images = root.querySelectorAll(IMAGE_SELECTOR);
    for (var j = 0; j < images.length; j++) {
      var imageSrc = images[j].currentSrc || images[j].src || "";
      if (imageSrc) {
        result.push({ src: imageSrc, alt: images[j].alt || "" });
      }
    }

    return result;
  }

  function normalizeLightboxAnimation(value) {
    var animation = String(value || DEFAULT_LIGHTBOX_SETTINGS.animation).trim().toLowerCase();
    return ["elastic", "zoom", "fade", "none"].indexOf(animation) !== -1
      ? animation
      : DEFAULT_LIGHTBOX_SETTINGS.animation;
  }

  function getLightboxSettings(triggerEl) {
    var ds = (triggerEl && triggerEl.dataset) || {};
    return {
      animation: normalizeLightboxAnimation(ds.directoristGbiLightboxAnimation),
      closeOnOverlay: parseBoolAttr(ds.directoristGbiLightboxCloseOnOverlay, DEFAULT_LIGHTBOX_SETTINGS.closeOnOverlay),
      loop: parseBoolAttr(ds.directoristGbiLightboxLoop, DEFAULT_LIGHTBOX_SETTINGS.loop),
      showCounter: parseBoolAttr(ds.directoristGbiLightboxShowCounter, DEFAULT_LIGHTBOX_SETTINGS.showCounter),
      showNavigation: parseBoolAttr(ds.directoristGbiLightboxShowNavigation, DEFAULT_LIGHTBOX_SETTINGS.showNavigation),
      showThumbnails: parseBoolAttr(ds.directoristGbiLightboxShowThumbnails, DEFAULT_LIGHTBOX_SETTINGS.showThumbnails),
    };
  }

  function renderLightboxThumbnails() {
    if (!lightboxState || !lightboxState.thumbnails) return;

    var itemCount = lightboxState.items.length;
    var showThumbnails = lightboxState.settings.showThumbnails !== false && itemCount > 1;
    lightboxState.thumbnails.hidden = !showThumbnails;
    lightboxState.thumbnails.innerHTML = "";
    if (!showThumbnails) return;

    for (var i = 0; i < lightboxState.items.length; i++) {
      (function (item, index) {
        var button = document.createElement("button");
        button.type = "button";
        button.className = "directorist-gbi-listing-slider-lightbox__thumbnail";
        button.setAttribute("aria-label", "View photo " + (index + 1));
        button.classList.toggle("is-active", index === lightboxState.activeIndex);

        var image = document.createElement("img");
        image.src = item.src;
        image.alt = item.alt || "";
        button.appendChild(image);
        button.addEventListener("click", function () { updateLightbox(index); });

        lightboxState.thumbnails.appendChild(button);
      })(lightboxState.items[i], i);
    }
  }

  function restartLightboxImageAnimation() {
    if (!lightboxState || !lightboxState.image) return;

    lightboxState.image.classList.remove("directorist-gbi-listing-slider-lightbox__image--animating");
    if (lightboxState.settings.animation === "none") return;

    // Force a reflow so the same keyframe restarts on every image swap.
    void lightboxState.image.offsetWidth;
    lightboxState.image.classList.add("directorist-gbi-listing-slider-lightbox__image--animating");
  }

  function updateLightbox(nextIndex) {
    if (!lightboxState || lightboxState.items.length <= 0) return;

    var itemCount = lightboxState.items.length;
    var loopEnabled = lightboxState.settings.loop !== false;
    if (loopEnabled) {
      lightboxState.activeIndex = ((nextIndex % itemCount) + itemCount) % itemCount;
    } else {
      lightboxState.activeIndex = Math.max(0, Math.min(nextIndex, itemCount - 1));
    }

    var activeItem = lightboxState.items[lightboxState.activeIndex];
    lightboxState.image.src = activeItem.src;
    lightboxState.image.alt = activeItem.alt || "";
    lightboxState.counter.textContent = (lightboxState.activeIndex + 1) + " / " + itemCount;
    lightboxState.counter.hidden = lightboxState.settings.showCounter === false;

    var showNavigation = lightboxState.settings.showNavigation !== false && itemCount > 1;
    lightboxState.previousButton.hidden = !showNavigation;
    lightboxState.nextButton.hidden = !showNavigation;
    lightboxState.previousButton.disabled = showNavigation && !loopEnabled && lightboxState.activeIndex <= 0;
    lightboxState.nextButton.disabled = showNavigation && !loopEnabled && lightboxState.activeIndex >= itemCount - 1;

    renderLightboxThumbnails();
    restartLightboxImageAnimation();
  }

  function closeLightbox() {
    if (!lightboxState) return;

    lightboxState.overlay.classList.remove("is-open");
    lightboxState.overlay.setAttribute("aria-hidden", "true");
    if (document.body) document.body.classList.remove("directorist-gbi-listing-slider-lightbox-open");
    document.removeEventListener("keydown", handleLightboxKeydown);
  }

  function handleLightboxKeydown(event) {
    if (!lightboxState || !lightboxState.overlay.classList.contains("is-open")) return;

    if (event.key === "Escape") closeLightbox();
    if (event.key === "ArrowLeft") updateLightbox(lightboxState.activeIndex - 1);
    if (event.key === "ArrowRight") updateLightbox(lightboxState.activeIndex + 1);
  }

  function ensureLightbox() {
    if (lightboxState) return lightboxState;

    var overlay = document.createElement("div");
    overlay.className = "directorist-gbi-listing-slider-lightbox";
    overlay.setAttribute("aria-hidden", "true");
    overlay.setAttribute("aria-modal", "true");
    overlay.setAttribute("role", "dialog");

    var dialog = document.createElement("div");
    dialog.className = "directorist-gbi-listing-slider-lightbox__dialog";

    var image = document.createElement("img");
    image.className = "directorist-gbi-listing-slider-lightbox__image";
    image.alt = "";
    image.addEventListener("animationend", function () {
      image.classList.remove("directorist-gbi-listing-slider-lightbox__image--animating");
    });

    var closeButton = document.createElement("button");
    closeButton.type = "button";
    closeButton.className = "directorist-gbi-listing-slider-lightbox__close";
    closeButton.setAttribute("aria-label", "Close gallery");
    closeButton.innerHTML = "&times;";

    var previousButton = document.createElement("button");
    previousButton.type = "button";
    previousButton.className = "directorist-gbi-listing-slider-lightbox__nav directorist-gbi-listing-slider-lightbox__nav--prev";
    previousButton.setAttribute("aria-label", "Previous photo");
    previousButton.innerHTML = "&lsaquo;";

    var nextButton = document.createElement("button");
    nextButton.type = "button";
    nextButton.className = "directorist-gbi-listing-slider-lightbox__nav directorist-gbi-listing-slider-lightbox__nav--next";
    nextButton.setAttribute("aria-label", "Next photo");
    nextButton.innerHTML = "&rsaquo;";

    var counter = document.createElement("div");
    counter.className = "directorist-gbi-listing-slider-lightbox__counter";

    var thumbnails = document.createElement("div");
    thumbnails.className = "directorist-gbi-listing-slider-lightbox__thumbnails";
    thumbnails.hidden = true;

    dialog.appendChild(image);
    overlay.appendChild(dialog);
    overlay.appendChild(closeButton);
    overlay.appendChild(previousButton);
    overlay.appendChild(nextButton);
    overlay.appendChild(counter);
    overlay.appendChild(thumbnails);
    document.body.appendChild(overlay);

    lightboxState = {
      activeIndex: 0,
      counter: counter,
      image: image,
      items: [],
      nextButton: nextButton,
      overlay: overlay,
      previousButton: previousButton,
      settings: DEFAULT_LIGHTBOX_SETTINGS,
      thumbnails: thumbnails,
    };

    closeButton.addEventListener("click", closeLightbox);
    previousButton.addEventListener("click", function () { updateLightbox(lightboxState.activeIndex - 1); });
    nextButton.addEventListener("click", function () { updateLightbox(lightboxState.activeIndex + 1); });
    overlay.addEventListener("click", function (event) {
      if (event.target === overlay && lightboxState.settings.closeOnOverlay !== false) {
        closeLightbox();
      }
    });

    return lightboxState;
  }

  function openLightbox(items, startIndex, settings) {
    if (!items || items.length <= 0) return;

    settings = settings || {};
    var state = ensureLightbox();
    var animation = normalizeLightboxAnimation(settings && settings.animation);
    var nextSettings = {};
    for (var key in DEFAULT_LIGHTBOX_SETTINGS) {
      if (DEFAULT_LIGHTBOX_SETTINGS.hasOwnProperty(key)) nextSettings[key] = DEFAULT_LIGHTBOX_SETTINGS[key];
    }
    for (var settingKey in settings) {
      if (settings.hasOwnProperty(settingKey)) nextSettings[settingKey] = settings[settingKey];
    }
    nextSettings.animation = animation;

    state.settings = nextSettings;
    state.items = items;
    for (var i = 0; i < LIGHTBOX_ANIMATION_CLASSES.length; i++) {
      state.overlay.classList.remove(LIGHTBOX_ANIMATION_CLASSES[i]);
    }
    state.overlay.classList.add("directorist-gbi-listing-slider-lightbox--animation-" + animation);
    state.overlay.classList.add("is-open");
    state.overlay.setAttribute("aria-hidden", "false");
    if (document.body) document.body.classList.add("directorist-gbi-listing-slider-lightbox-open");
    updateLightbox(startIndex || 0);
    document.addEventListener("keydown", handleLightboxKeydown);
    var close = state.overlay.querySelector(".directorist-gbi-listing-slider-lightbox__close");
    if (close && typeof close.focus === "function") close.focus();
  }

  function initializeLightboxTriggers(root) {
    var ctx = root || document;
    if (!ctx || typeof ctx.querySelectorAll !== "function") return;

    var triggers = [];
    if (ctx.matches && ctx.matches(LIGHTBOX_TRIGGER_SELECTOR)) triggers.push(ctx);

    var found = ctx.querySelectorAll(LIGHTBOX_TRIGGER_SELECTOR);
    for (var i = 0; i < found.length; i++) {
      triggers.push(found[i]);
    }

    for (var j = 0; j < triggers.length; j++) {
      if (hasInitializedLightboxTrigger(triggers[j])) continue;

      markInitializedLightboxTrigger(triggers[j]);
      triggers[j].addEventListener("click", function (event) {
        event.preventDefault();
        event.stopPropagation();
        openLightbox(getLightboxItems(this), 0, getLightboxSettings(this));
      });
    }
  }

  // ---------------------------------------------------------------------------
  // AJAX directory-type tab switching
  // ---------------------------------------------------------------------------

  var WIDGET_SELECTOR = ".directorist-elementor-taxonomy-widget";
  var TAB_LINK_SELECTOR = ".directorist-type-nav__link";
  var ACTIVE_TAB_CLASS = "directorist-type-nav__list__current";

  function getWidgetContainer(el) {
    while (el) {
      if (el.matches && el.matches(WIDGET_SELECTOR)) return el;
      el = el.parentElement;
    }
    return null;
  }

  function getFrontendConfig() {
    return window.directoristElementorV4Frontend || {};
  }

  var PAGINATION_LINK_SELECTOR = "a.page-numbers[data-page]";

  function getWidgetPayload(widget) {
    var shortcode = widget.getAttribute("data-shortcode") || "";
    if (!shortcode) return null;

    var atts = {};
    try { atts = JSON.parse(widget.getAttribute("data-atts") || "{}"); } catch (err) { atts = {}; }

    var slider = null;
    try { slider = JSON.parse(widget.getAttribute("data-slider") || "null"); } catch (err) { slider = null; }

    var allTabIcon = null;
    try { allTabIcon = JSON.parse(widget.getAttribute("data-all-tab-icon") || "null"); } catch (err) { allTabIcon = null; }

    var template = null;
    try { template = JSON.parse(widget.getAttribute("data-template") || "null"); } catch (err) { template = null; }

    return {
      shortcode: shortcode,
      atts: atts,
      showAllTab: widget.getAttribute("data-show-all-tab") === "true",
      allTabIcon: allTabIcon,
      slider: slider,
      compositionScope: widget.getAttribute("data-composition-scope") || "",
      template: template,
    };
  }

  function fetchTaxonomyContent(widget, payloadOverrides) {
    var config = getFrontendConfig();
    var ajaxUrl = config.ajaxUrl;
    var nonce = config.nonce;
    if (!ajaxUrl || !nonce) return;

    var base = getWidgetPayload(widget);
    if (!base) return;

    var payload = {};
    for (var k in base) { if (base.hasOwnProperty(k)) payload[k] = base[k]; }
    for (var k2 in payloadOverrides) { if (payloadOverrides.hasOwnProperty(k2)) payload[k2] = payloadOverrides[k2]; }

    widget.style.opacity = "0.6";
    widget.style.pointerEvents = "none";

    var formData = new FormData();
    formData.append("action", "directorist_elementor_v4_taxonomy_switch");
    formData.append("nonce", nonce);
    formData.append("payload", JSON.stringify(payload));

    fetch(ajaxUrl, { method: "POST", body: formData, credentials: "same-origin" })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (data && data.success && data.data && data.data.html) {
          widget.innerHTML = data.data.html;
          initAll(widget);
          initializeMasonryLayouts(widget);
          initializeLightboxTriggers(widget);
        }
      })
      .catch(function () {})
      .finally(function () {
        widget.style.opacity = "";
        widget.style.pointerEvents = "";
      });
  }

  function handleWidgetClick(e) {
    // Handle tab clicks.
    var tabLink = e.target.closest ? e.target.closest(TAB_LINK_SELECTOR) : null;
    if (tabLink) {
      var widget = getWidgetContainer(tabLink);
      if (!widget) return;

      e.preventDefault();
      e.stopPropagation();

      var directoryType = tabLink.getAttribute("data-listing_type") || "";
      if (!directoryType) return;

      // Mark active tab immediately.
      var tabItems = widget.querySelectorAll(".directorist-type-nav__list > li");
      for (var i = 0; i < tabItems.length; i++) {
        tabItems[i].classList.remove(ACTIVE_TAB_CLASS);
      }
      var clickedLi = tabLink.parentElement;
      if (clickedLi && clickedLi.tagName === "LI") {
        clickedLi.classList.add(ACTIVE_TAB_CLASS);
      }

      // Store active directory type on widget for pagination context.
      widget.setAttribute("data-active-directory-type", directoryType);

      fetchTaxonomyContent(widget, { activeDirectoryType: directoryType });
      return;
    }

    // Handle pagination clicks.
    var pageLink = e.target.closest ? e.target.closest(PAGINATION_LINK_SELECTOR) : null;
    if (pageLink) {
      var pWidget = getWidgetContainer(pageLink);
      if (!pWidget) return;

      e.preventDefault();
      e.stopPropagation();

      var page = parseInt(pageLink.getAttribute("data-page"), 10);
      if (!page || page < 1) return;

      var activeDir = pWidget.getAttribute("data-active-directory-type") || "";

      // Merge page into shortcode atts.
      var base = getWidgetPayload(pWidget);
      if (!base) return;

      var attsWithPage = {};
      for (var ak in base.atts) { if (base.atts.hasOwnProperty(ak)) attsWithPage[ak] = base.atts[ak]; }
      attsWithPage.paged = String(page);

      fetchTaxonomyContent(pWidget, {
        atts: attsWithPage,
        activeDirectoryType: activeDir,
      });
      return;
    }
  }

  function bindWidgetListeners(root) {
    var ctx = root || document;
    var widgets = ctx.matches && ctx.matches(WIDGET_SELECTOR) ? [ctx] : [];
    if (widgets.length === 0 && typeof ctx.querySelectorAll === "function") {
      var found = ctx.querySelectorAll(WIDGET_SELECTOR);
      for (var i = 0; i < found.length; i++) widgets.push(found[i]);
    }

    for (var j = 0; j < widgets.length; j++) {
      var w = widgets[j];
      if (w.__directoristWidgetBound) continue;
      w.__directoristWidgetBound = true;
      w.addEventListener("click", handleWidgetClick);
    }
  }

  function nodeHasInitializableSliderUi(node) {
    if (!node || node.nodeType !== 1) return false;
    if (
      node.matches &&
      (
        node.matches(SLIDER_SELECTOR) ||
        node.matches(MASONRY_SELECTOR) ||
        node.matches(LIGHTBOX_TRIGGER_SELECTOR) ||
        node.matches(PAGINATION_SELECTOR) ||
        node.matches(".directorist-elementor-pricing-plans")
      )
    ) {
      return true;
    }

    return !!(
      node.querySelector &&
      (
        node.querySelector(SLIDER_SELECTOR) ||
        node.querySelector(MASONRY_SELECTOR) ||
        node.querySelector(LIGHTBOX_TRIGGER_SELECTOR) ||
        node.querySelector(PAGINATION_SELECTOR) ||
        node.querySelector(".directorist-elementor-pricing-plans")
      )
    );
  }

  function getPricingTabKey(node) {
    if (!node || typeof node.getAttribute !== "function") return "";
    return node.getAttribute("data-tab-key") || node.getAttribute("data-duration-key") || "";
  }

  function activatePricingTab(root, tabKey) {
    if (!root || !tabKey) return;

    var tabs = root.querySelectorAll(".directorist-elementor-pricing-plans__duration-tab");
    for (var i = 0; i < tabs.length; i++) {
      var active = getPricingTabKey(tabs[i]) === tabKey;
      tabs[i].classList.toggle("is-active", active);
      tabs[i].setAttribute("aria-selected", active ? "true" : "false");
      tabs[i].setAttribute("tabindex", active ? "0" : "-1");
    }

    var cards = root.querySelectorAll(".directorist-elementor-pricing-plans__card");
    for (var j = 0; j < cards.length; j++) {
      var cardActive = getPricingTabKey(cards[j]) === tabKey;
      cards[j].hidden = !cardActive;
      cards[j].classList.toggle("is-active", cardActive);
    }
  }

  function initPricingPlans(root) {
    var ctx = root || document;
    var plans = [];

    if (ctx.matches && ctx.matches(".directorist-elementor-pricing-plans")) {
      plans.push(ctx);
    }

    if (typeof ctx.querySelectorAll === "function") {
      var found = ctx.querySelectorAll(".directorist-elementor-pricing-plans");
      for (var i = 0; i < found.length; i++) plans.push(found[i]);
    }

    for (var j = 0; j < plans.length; j++) {
      var planRoot = plans[j];
      if (planRoot.__directoristPricingPlansBound) continue;
      planRoot.__directoristPricingPlansBound = true;

      if (planRoot.getAttribute("data-plan-tabs") === "enabled" || planRoot.getAttribute("data-duration-tabs") === "enabled") {
        var requested = planRoot.getAttribute("data-default-tab-key") || planRoot.getAttribute("data-default-duration-key") || "";
        var escapedRequested = window.CSS && typeof window.CSS.escape === "function"
          ? window.CSS.escape(requested)
          : requested.replace(/"/g, '\\"');
        var activeTab = requested
          ? planRoot.querySelector('.directorist-elementor-pricing-plans__duration-tab[data-tab-key="' + escapedRequested + '"]') || planRoot.querySelector('.directorist-elementor-pricing-plans__duration-tab[data-duration-key="' + escapedRequested + '"]')
          : null;

        activeTab = activeTab || planRoot.querySelector(".directorist-elementor-pricing-plans__duration-tab.is-active") || planRoot.querySelector(".directorist-elementor-pricing-plans__duration-tab");

        if (activeTab) {
          activatePricingTab(planRoot, getPricingTabKey(activeTab));
        }
      }

      planRoot.addEventListener("click", function (event) {
        var tab = event.target.closest(".directorist-elementor-pricing-plans__duration-tab");
        if (!tab || !this.contains(tab)) return;

        event.preventDefault();
        activatePricingTab(this, getPricingTabKey(tab));
      });
    }
  }

  // ---------------------------------------------------------------------------
  // Auto-initialize
  // ---------------------------------------------------------------------------

  function bootAll() {
    initAll();
    initializeMasonryLayouts();
    initializeLightboxTriggers();
    bindWidgetListeners();
    initPricingPlans();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", bootAll);
  } else {
    bootAll();
  }

  // Re-initialize after Elementor frontend renders widgets.
  if (typeof jQuery !== "undefined") {
    jQuery(window).on("elementor/frontend/init", function () {
      if (window.elementorFrontend && typeof window.elementorFrontend.hooks !== "undefined") {
        window.elementorFrontend.hooks.addAction("frontend/element_ready/directorist_all_categories.default", function ($el) {
          initAll($el[0]);
          initializeMasonryLayouts($el[0]);
          initializeLightboxTriggers($el[0]);
          bindWidgetListeners($el[0]);
        });
        window.elementorFrontend.hooks.addAction("frontend/element_ready/directorist_all_locations.default", function ($el) {
          initAll($el[0]);
          initializeMasonryLayouts($el[0]);
          initializeLightboxTriggers($el[0]);
          bindWidgetListeners($el[0]);
        });
        window.elementorFrontend.hooks.addAction("frontend/element_ready/directorist_listing_card_images_slider.default", function ($el) {
          initAll($el[0]);
          initializeMasonryLayouts($el[0]);
          initializeLightboxTriggers($el[0]);
        });
        window.elementorFrontend.hooks.addAction("frontend/element_ready/directorist_pricing_plans.default", function ($el) {
          initPricingPlans($el[0]);
        });
      }
    });
  }

  window.addEventListener("load", bootAll);
  window.addEventListener("resize", function () {
    initializeMasonryLayouts();
  });

  if (typeof MutationObserver === "function" && document.documentElement) {
    new MutationObserver(function (mutations) {
      var shouldBoot = false;
      for (var i = 0; i < mutations.length; i++) {
        if (mutations[i].type === "attributes" && nodeHasInitializableSliderUi(mutations[i].target)) {
          shouldBoot = true;
          break;
        }

        var addedNodes = mutations[i].addedNodes || [];
        for (var j = 0; j < addedNodes.length; j++) {
          if (nodeHasInitializableSliderUi(addedNodes[j])) {
            shouldBoot = true;
            break;
          }
        }
        if (shouldBoot) break;

        var removedNodes = mutations[i].removedNodes || [];
        for (var k = 0; k < removedNodes.length; k++) {
          if (nodeHasInitializableSliderUi(removedNodes[k])) {
            shouldBoot = true;
            break;
          }
        }
        if (shouldBoot) break;
      }
      if (shouldBoot) bootAll();
    }).observe(document.documentElement, {
      attributes: true,
      attributeFilter: SLIDER_MUTATION_ATTRIBUTES,
      childList: true,
      subtree: true,
    });
  }

  // Expose for external use.
  window.directoristElementorTaxonomySlider = {
    init: initAll,
    destroy: destroyAll,
    bindWidgets: bindWidgetListeners,
    initializeMasonry: initializeMasonryLayouts,
    initializeLightbox: initializeLightboxTriggers,
    openLightbox: openLightbox,
  };
})();
