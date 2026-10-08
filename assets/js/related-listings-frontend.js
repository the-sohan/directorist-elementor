/**
 * Directorist Elementor — Related Listings Frontend
 *
 * Handles Swiper slider initialization and client-side pagination
 * for the Related Listings widget.
 *
 * @package DirectoristElementor
 */
(function () {
  "use strict";

  var SLIDER_SELECTOR = ".directorist-gbi-related-slider, .directorist-elementor-listings-loop-slider";
  var PAGINATION_BTN = ".directorist-gbi-related-pagination__button[data-gbi-related-page]";
  var RETRY_DELAY = 160;
  var MAX_RETRIES = 40;

  var SUPPORTED_EFFECTS = [
    "slide", "fade", "grid", "thumb", "creative",
    "cards", "cube", "flip", "coverflow",
  ];

  var SINGLE_SLIDE_EFFECTS = {
    fade: true, cube: true, flip: true, cards: true, creative: true,
  };

  var retryTimer = 0;
  var retryCount = 0;

  // ---------------------------------------------------------------------------
  // Swiper helpers (same pattern as taxonomy-slider.js)
  // ---------------------------------------------------------------------------

  function parseIntAttr(v, fb) { var n = parseInt(v, 10); return isFinite(n) ? n : fb; }
  function parseBoolAttr(v, fb) {
    if (typeof v !== "string") return !!fb;
    if (v === "true" || v === "1") return true;
    if (v === "false" || v === "0") return false;
    return !!fb;
  }
  function parseBreakpoints(v) {
    if (typeof v !== "string" || !v) return null;
    try { var p = JSON.parse(v); return p && typeof p === "object" ? p : null; } catch (e) { return null; }
  }
  function normalizeEffect(v) {
    var n = String(v || "slide").trim().toLowerCase();
    return SUPPORTED_EFFECTS.indexOf(n) !== -1 ? n : "slide";
  }

  function resolveEditorDeviceMode(el) {
    var doc = el && el.ownerDocument ? el.ownerDocument : document;
    var body = doc && doc.body ? doc.body : null;
    var scoped = el && el.closest ? el.closest(
      ".directorist-elementor--device-desktop, .directorist-elementor--device-tablet, .directorist-elementor--device-mobile"
    ) : null;

    if (!body || !body.classList || !body.classList.contains("elementor-editor-active") || !scoped) {
      return "";
    }

    if (scoped.classList.contains("directorist-elementor--device-mobile")) return "mobile";
    if (scoped.classList.contains("directorist-elementor--device-tablet")) return "tablet";
    if (scoped.classList.contains("directorist-elementor--device-desktop")) return "desktop";

    return "";
  }

  function resolveEditorBreakpointConfig(baseSPV, bp, deviceMode, fallbackGap) {
    if (!deviceMode) return null;

    var key = deviceMode === "mobile" ? "0" : (deviceMode === "tablet" ? "768" : "1200");
    var config = bp && (bp[key] || bp[parseInt(key, 10)]) || {};
    var slidesPerView = parseInt(config.slidesPerView, 10);
    var spaceBetween = parseInt(config.spaceBetween, 10);

    return {
      slidesPerView: isFinite(slidesPerView) && slidesPerView > 0 ? slidesPerView : baseSPV,
      spaceBetween: isFinite(spaceBetween) && spaceBetween >= 0 ? spaceBetween : fallbackGap,
    };
  }

  function buildSliderSignature(el) {
    var ds = el.dataset || {};
    return [
      resolveEditorDeviceMode(el),
      ds.swItems || "",
      ds.swMargin || "",
      ds.swLoop || "",
      ds.swPerslide || "",
      ds.swSpeed || "",
      ds.swDelay || "",
      ds.swAutoplay || "",
      ds.swEffect || "",
      ds.swGridRows || "",
      ds.swPauseOnHover || "",
      ds.swResponsive || "",
      el.querySelectorAll(".swiper-slide").length,
    ].join("|");
  }

  function resolveScopedElement(el, datasetKey, fallbackSelectors) {
    var selectors = [];
    var ds = el.dataset || {};
    if (typeof ds[datasetKey] === "string" && ds[datasetKey]) {
      selectors.push(ds[datasetKey]);
    }
    selectors = selectors.concat(fallbackSelectors || []);
    for (var i = 0; i < selectors.length; i++) {
      try {
        var found = el.querySelector(selectors[i]);
        if (found) return found;
      } catch (e) {}
    }
    return null;
  }

  function resolveAssetsBase() {
    var candidates = [window, window.parent, window.top];
    for (var i = 0; i < candidates.length; i++) {
      try {
        var u = String((candidates[i] && candidates[i].directorist && candidates[i].directorist.assets_url) || "").trim();
        if (u) return u.replace(/\/+$/, "");
      } catch (e) {}
    }
    return "";
  }

  function resolveSwiperCtor() {
    var candidates = [window, window.parent, window.top];
    for (var i = 0; i < candidates.length; i++) {
      try {
        if (candidates[i] && typeof candidates[i].Swiper === "function") {
          if (!window.Swiper) window.Swiper = candidates[i].Swiper;
          return candidates[i].Swiper;
        }
      } catch (e) {}
    }
    return null;
  }

  function ensureSwiperAssets() {
    var base = resolveAssetsBase();
    if (!base) return;
    // CSS
    var cssSrc = base + "/vendor-css/swiper.min.css";
    var links = document.querySelectorAll('link[rel="stylesheet"]');
    var cssLoaded = false;
    for (var i = 0; i < links.length; i++) { if (links[i].href === cssSrc) { cssLoaded = true; break; } }
    if (!cssLoaded) {
      var link = document.createElement("link");
      link.rel = "stylesheet"; link.href = cssSrc;
      (document.head || document.body).appendChild(link);
    }
    // JS
    if (resolveSwiperCtor()) return;
    var jsSrc = base + "/vendor-js/swiper.min.js";
    var scripts = document.getElementsByTagName("script");
    for (var j = 0; j < scripts.length; j++) { if (scripts[j].src === jsSrc) return; }
    var script = document.createElement("script");
    script.src = jsSrc; script.async = true; script.defer = true;
    (document.head || document.body).appendChild(script);
  }

  function resolveCurrentSPV(base, bp) {
    if (!bp || typeof bp !== "object") return base;
    var vw = window.innerWidth || 0;
    var keys = Object.keys(bp).map(function(k) { return parseInt(k, 10); }).filter(isFinite).sort(function(a,b) { return a - b; });
    var r = base;
    for (var i = 0; i < keys.length; i++) {
      if (vw >= keys[i]) {
        var s = parseInt((bp[String(keys[i])] || bp[keys[i]] || {}).slidesPerView, 10);
        if (isFinite(s) && s > 0) r = s;
      }
    }
    return r;
  }

  function createFallbackConfig(cfg) {
    var n = {}; for (var k in cfg) { if (cfg.hasOwnProperty(k)) n[k] = cfg[k]; }
    n.effect = "slide";
    ["fadeEffect","cubeEffect","flipEffect","coverflowEffect","creativeEffect","cardsEffect","grid"].forEach(function(ek) { delete n[ek]; });
    return n;
  }

  function initSlider(el) {
    var Ctor = resolveSwiperCtor();
    if (typeof Ctor !== "function") return;

    var signature = buildSliderSignature(el);
    var existing = el.swiper;
    if (
      existing &&
      !existing.destroyed &&
      (el.__directoristElementorSwiper === existing || el.__directoristRelatedSwiper === existing) &&
      el.__directoristElementorSwiperSignature === signature
    ) {
      return;
    }

    if (existing && !existing.destroyed) {
      if (typeof existing.destroy === "function") existing.destroy(true, true);
    }

    var slides = el.querySelectorAll(".swiper-slide");
    if (slides.length === 0) return;

    var ds = el.dataset || {};
    var baseSPV = parseIntAttr(ds.swItems, 3);
    var bp = parseBreakpoints(ds.swResponsive) || {};
    var effect = normalizeEffect(ds.swEffect);
    var isGrid = effect === "grid";
    var isThumb = effect === "thumb";
    var swiperEffect = (isGrid || isThumb) ? "slide" : effect;
    var forceSingle = !!SINGLE_SLIDE_EFFECTS[swiperEffect];
    var gridRows = isGrid ? Math.max(1, parseIntAttr(ds.swGridRows, 2)) : 1;
    var baseGap = parseIntAttr(ds.swMargin, 30);
    var editorDeviceConfig = forceSingle ? null : resolveEditorBreakpointConfig(baseSPV, bp, resolveEditorDeviceMode(el), baseGap);

    var effBP = forceSingle || editorDeviceConfig ? {} : bp;
    var effSPV = forceSingle ? 1 : (editorDeviceConfig ? editorDeviceConfig.slidesPerView : baseSPV);
    var effSPG = forceSingle ? 1 : Math.max(1, parseIntAttr(ds.swPerslide, 1));
    var pauseOnHover = parseBoolAttr(ds.swPauseOnHover, true);
    var curSPV = resolveCurrentSPV(effSPV, effBP);
    var visCount = curSPV * Math.max(1, gridRows);
    var loopEnabled = parseBoolAttr(ds.swLoop, true) && slides.length > visCount && !isGrid;
    var speed = Math.max(100, parseIntAttr(ds.swSpeed, 500));
    var delay = Math.max(100, parseIntAttr(ds.swDelay, speed));
    var autoplay = parseBoolAttr(ds.swAutoplay, false);

    var nextEl = resolveScopedElement(el, "swNextSelector", [
      ".directorist-swiper__nav--next-related",
      ".directorist-swiper__nav--next-loop",
    ]);
    var prevEl = resolveScopedElement(el, "swPrevSelector", [
      ".directorist-swiper__nav--prev-related",
      ".directorist-swiper__nav--prev-loop",
    ]);
    var pagEl = resolveScopedElement(el, "swPaginationSelector", [
      ".directorist-swiper__pagination--related",
      ".directorist-swiper__pagination--loop",
    ]);

    var config = {
      slidesPerView: effSPV,
      spaceBetween: editorDeviceConfig ? editorDeviceConfig.spaceBetween : baseGap,
      loop: loopEnabled,
      slidesPerGroup: effSPG,
      speed: speed,
      breakpoints: effBP,
      effect: swiperEffect,
      watchOverflow: true,
      observer: true,
      observeParents: true,
      allowTouchMove: true,
      simulateTouch: true,
    };

    if (isGrid) config.grid = { rows: gridRows, fill: "row" };
    if (isThumb) { config.watchSlidesProgress = true; config.slideToClickedSlide = true; config.freeMode = true; config.spaceBetween = Math.min(12, config.spaceBetween); }
    if (nextEl || prevEl) config.navigation = { nextEl: nextEl, prevEl: prevEl };
    if (pagEl) config.pagination = { el: pagEl, type: "bullets", clickable: true };
    if (autoplay) config.autoplay = { delay: delay, disableOnInteraction: false, pauseOnMouseEnter: pauseOnHover };

    if (swiperEffect === "fade") config.fadeEffect = { crossFade: true };
    if (swiperEffect === "cube") config.cubeEffect = { shadow: true, slideShadows: true, shadowOffset: 20, shadowScale: 0.94 };
    if (swiperEffect === "flip") config.flipEffect = { slideShadows: true, limitRotation: true };
    if (swiperEffect === "coverflow") { config.coverflowEffect = { rotate: 35, stretch: 0, depth: 120, modifier: 1, slideShadows: true }; config.centeredSlides = true; }
    if (swiperEffect === "creative") config.creativeEffect = { prev: { shadow: true, translate: [0, 0, -400] }, next: { translate: ["100%", 0, 0] } };
    if (swiperEffect === "cards") config.cardsEffect = { slideShadows: true, rotate: true, perSlideOffset: 8, perSlideRotate: 2 };

    var instance;
    try { instance = new Ctor(el, config); } catch (e) {
      try { instance = new Ctor(el, createFallbackConfig(config)); } catch (e2) { return; }
    }

    el.__directoristElementorSwiper = instance;
    el.__directoristRelatedSwiper = instance;
    el.__directoristElementorSwiperSignature = signature;

    if (pagEl && instance && instance.pagination) {
      if (typeof instance.pagination.render === "function") instance.pagination.render();
      if (typeof instance.pagination.update === "function") instance.pagination.update();
    }
  }

  function collectSliders(root) {
    var ctx = root || document;
    if (typeof ctx.querySelectorAll !== "function") return [];
    var nodes = ctx.querySelectorAll(SLIDER_SELECTOR);
    var out = [];
    for (var i = 0; i < nodes.length; i++) { if (nodes[i].nodeType === 1) out.push(nodes[i]); }
    return out;
  }

  function tryInitSliders(root) {
    var sliders = collectSliders(root);
    if (sliders.length === 0) return false;
    var Ctor = resolveSwiperCtor();
    if (typeof Ctor !== "function") return false;
    for (var i = 0; i < sliders.length; i++) initSlider(sliders[i]);
    return true;
  }

  function scheduleBootstrap(root) {
    if (retryTimer) clearTimeout(retryTimer);
    if (retryCount >= MAX_RETRIES) { retryCount = 0; return; }
    retryTimer = setTimeout(function () {
      retryTimer = 0; retryCount++;
      var sliders = collectSliders(root);
      if (sliders.length === 0) { retryCount = 0; return; }
      ensureSwiperAssets();
      if (tryInitSliders(root)) { retryCount = 0; return; }
      scheduleBootstrap(root);
    }, RETRY_DELAY);
  }

  function initAllSliders(root) {
    var sliders = collectSliders(root);
    if (sliders.length === 0) return;
    ensureSwiperAssets();
    if (tryInitSliders(root)) return;
    scheduleBootstrap(root);
  }

  // ---------------------------------------------------------------------------
  // Client-side pagination
  // ---------------------------------------------------------------------------

  function handlePaginationClick(e) {
    var btn = e.target.closest ? e.target.closest(PAGINATION_BTN) : null;
    if (!btn) return;

    e.preventDefault();

    var page = parseInt(btn.getAttribute("data-gbi-related-page"), 10);
    if (!page || page < 1) return;

    var section = btn.closest(".directorist-related-listing");
    if (!section) return;

    // Update active button.
    var allBtns = section.querySelectorAll(PAGINATION_BTN);
    for (var i = 0; i < allBtns.length; i++) {
      allBtns[i].classList.remove("is-active");
      allBtns[i].removeAttribute("aria-current");
    }
    btn.classList.add("is-active");
    btn.setAttribute("aria-current", "page");

    // Show/hide card groups.
    var items = section.querySelectorAll("[data-direl-related-page]");
    for (var j = 0; j < items.length; j++) {
      var itemPage = parseInt(items[j].getAttribute("data-direl-related-page"), 10);
      items[j].style.display = itemPage === page ? "" : "none";
    }
  }

  // ---------------------------------------------------------------------------
  // Boot
  // ---------------------------------------------------------------------------

  function boot() {
    initAllSliders();
    document.addEventListener("click", handlePaginationClick);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }

  if (typeof jQuery !== "undefined") {
    jQuery(window).on("elementor/frontend/init", function () {
      if (window.elementorFrontend && typeof window.elementorFrontend.hooks !== "undefined") {
        window.elementorFrontend.hooks.addAction(
          "frontend/element_ready/directorist_single_listing_related_listings.default",
          function ($el) { initAllSliders($el[0]); }
        );
        window.elementorFrontend.hooks.addAction(
          "frontend/element_ready/directorist_listings_loop.default",
          function ($el) { initAllSliders($el[0]); }
        );
      }
    });
  }

  window.addEventListener("directorist-instant-search-reloaded", function () {
    initAllSliders(document);
  });

  window.directoristElementorRelatedSlider = {
    init: initAllSliders,
  };
  window.directoristElementorLoopSlider = {
    init: initAllSliders,
  };
})();
