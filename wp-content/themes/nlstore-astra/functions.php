<?php
/**
 * NLStore Astra — Child Theme
 */

define( 'CHILD_THEME_NLSTORE_ASTRA_VERSION', '3.0.0' );

/* ----------------------------------------------------------
   STYLES & FONTS
---------------------------------------------------------- */
/**
 * Version d'un asset du thème basée sur sa date de modification (filemtime).
 * Garantit que chaque déploiement « casse » le cache navigateur/CDN : l'URL
 * ?ver= change dès que le fichier change, donc les utilisateurs voient les
 * mises à jour sans avoir à vider leur cache.
 */
function nl_asset_ver( $relative_path ) {
    $file = get_stylesheet_directory() . '/' . ltrim( $relative_path, '/' );
    $mtime = file_exists( $file ) ? filemtime( $file ) : false;
    return $mtime ? (string) $mtime : CHILD_THEME_NLSTORE_ASTRA_VERSION;
}

function nl_enqueue_assets() {
    // Google Fonts
    wp_enqueue_style(
        'nl-google-fonts',
        'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Montserrat:wght@300;400;500;600;700&display=swap',
        [],
        null
    );

    // Theme stylesheet (after parent Astra)
    wp_enqueue_style(
        'nlstore-astra-theme-css',
        get_stylesheet_directory_uri() . '/style.css',
        [ 'astra-theme-css' ],
        nl_asset_ver( 'style.css' ),
        'all'
    );

    // WooCommerce dedicated stylesheet
    if ( class_exists( 'WooCommerce' ) ) {
        wp_enqueue_style(
            'nlstore-woocommerce-css',
            get_stylesheet_directory_uri() . '/woocommerce.css',
            [ 'nlstore-astra-theme-css' ],
            nl_asset_ver( 'woocommerce.css' ),
            'all'
        );
    }
}
add_action( 'wp_enqueue_scripts', 'nl_enqueue_assets', 15 );

/* ----------------------------------------------------------
   SCROLL REVEAL (icônes Lucide : voir helper nl_icon() plus bas)
---------------------------------------------------------- */
function nl_enqueue_interactions() {
    // Reveal au scroll — pas de dépendance externe (handle inline sans fichier).
    wp_register_script( 'nl-interactions', false, [], CHILD_THEME_NLSTORE_ASTRA_VERSION, true );
    wp_enqueue_script( 'nl-interactions' );

    // Données pour les icônes d'en-tête (recherche + panier) injectées en JS.
    $cart_count = ( class_exists( 'WooCommerce' ) && WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;
    wp_localize_script( 'nl-interactions', 'NL_DATA', [
        'cartUrl'   => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/panier/' ),
        'cartCount' => (int) $cart_count,
        'searchUrl' => home_url( '/' ),
        'hasWoo'    => class_exists( 'WooCommerce' ) ? 1 : 0,
    ] );

    $js = <<<'JS'
(function () {
  var SELECTOR = '.nl-reveal, .nl-reveal-left, .nl-reveal-right, .nl-reveal-scale, .nl-stagger';

  // Affecte un index aux enfants des conteneurs « stagger » pour la cascade.
  function nlIndexStagger() {
    document.querySelectorAll('.nl-stagger').forEach(function (box) {
      var kids = box.children;
      for (var i = 0; i < kids.length; i++) {
        kids[i].style.setProperty('--nl-i', i);
      }
    });
  }

  function nlReveal() {
    nlIndexStagger();
    var els = document.querySelectorAll(SELECTOR);
    if (!('IntersectionObserver' in window) || !els.length) {
      els.forEach(function (el) { el.classList.add('nl-in'); });
      return;
    }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('nl-in');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });
    els.forEach(function (el) { io.observe(el); });
  }

  // Parallax léger basé sur le scroll (rAF), désactivé si reduced-motion.
  function nlParallax() {
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var nodes = document.querySelectorAll('.nl-parallax');
    if (reduce || !nodes.length || window.innerWidth < 1025) { return; }
    var ticking = false;
    function update() {
      var vh = window.innerHeight;
      nodes.forEach(function (el) {
        var r = el.getBoundingClientRect();
        if (r.bottom < 0 || r.top > vh) { return; }
        var speed = parseFloat(el.getAttribute('data-speed')) || 0.12;
        var offset = (r.top + r.height / 2 - vh / 2) * speed;
        el.style.setProperty('--nl-par', offset.toFixed(1) + 'px');
      });
      ticking = false;
    }
    function onScroll() {
      if (!ticking) { window.requestAnimationFrame(update); ticking = true; }
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll, { passive: true });
    update();
  }

  // Flèches de navigation des sliders produits (scroll horizontal).
  function nlSliders() {
    document.querySelectorAll('.nl-products-slider').forEach(function (slider) {
      var track = slider.querySelector('ul.products');
      var prev = slider.querySelector('.nl-slider-prev');
      var next = slider.querySelector('.nl-slider-next');
      if (!track || !prev || !next) { return; }

      function step() {
        var card = track.querySelector('li.product');
        var w = card ? card.getBoundingClientRect().width : track.clientWidth * 0.8;
        return w + 24; // largeur carte + gap approx.
      }
      function sync() {
        var max = track.scrollWidth - track.clientWidth - 2;
        prev.disabled = track.scrollLeft <= 2;
        next.disabled = track.scrollLeft >= max;
      }
      prev.addEventListener('click', function () { track.scrollBy({ left: -step() * 2, behavior: 'smooth' }); });
      next.addEventListener('click', function () { track.scrollBy({ left: step() * 2, behavior: 'smooth' }); });
      track.addEventListener('scroll', sync, { passive: true });
      window.addEventListener('resize', sync, { passive: true });
      sync();
    });
  }

  // Wishlist (cœur) — bascule visuelle persistée en localStorage, sans backend.
  function nlWishlist() {
    var KEY = 'nl_wishlist';
    var set;
    try { set = new Set(JSON.parse(localStorage.getItem(KEY) || '[]')); } catch (e) { set = new Set(); }
    function save() { try { localStorage.setItem(KEY, JSON.stringify(Array.from(set))); } catch (e) {} }
    document.querySelectorAll('.nl-wishlist').forEach(function (btn) {
      var id = btn.getAttribute('data-id');
      if (id && set.has(id)) { btn.classList.add('is-active'); btn.setAttribute('aria-pressed', 'true'); }
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var active = btn.classList.toggle('is-active');
        btn.setAttribute('aria-pressed', active ? 'true' : 'false');
        if (id) { active ? set.add(id) : set.delete(id); save(); }
      });
    });
  }

  // Carousel auto des catégories sur mobile : avance toutes les 2 s vers la
  // droite, boucle au début, se met en pause au toucher puis reprend.
  function nlCatCarousel() {
    var grid = document.querySelector('.nl-categories-grid');
    if (!grid) { return; }
    var cards = grid.querySelectorAll('.nl-cat-card');
    if (cards.length < 2) { return; }
    var mqMobile = window.matchMedia('(max-width: 768px)');
    var mqReduce = window.matchMedia('(prefers-reduced-motion: reduce)');

    // Puces d'indication
    var dotsWrap = document.createElement('div');
    dotsWrap.className = 'nl-cat-dots';
    var dots = [];
    cards.forEach(function (c, i) {
      var d = document.createElement('button');
      d.type = 'button';
      d.setAttribute('aria-label', 'Aller à la catégorie ' + (i + 1));
      d.addEventListener('click', function () { goTo(i); pauseThenResume(); });
      dots.push(d);
      dotsWrap.appendChild(d);
    });
    grid.parentNode.insertBefore(dotsWrap, grid.nextSibling);

    var timer = null, resumeT = null, current = 0;
    function stepW() { return cards[0].getBoundingClientRect().width + 14; }
    function maxScroll() { return grid.scrollWidth - grid.clientWidth - 4; }
    function goTo(idx) {
      current = idx;
      grid.scrollTo({ left: Math.min(idx * stepW(), maxScroll()), behavior: 'smooth' });
    }
    function next() {
      if (!mqMobile.matches) { return; }
      current = (grid.scrollLeft >= maxScroll() - 2 || current + 1 >= cards.length) ? 0 : current + 1;
      grid.scrollTo({ left: Math.min(current * stepW(), maxScroll()), behavior: 'smooth' });
    }
    function syncDots() {
      var idx = Math.round(grid.scrollLeft / stepW());
      if (idx > cards.length - 1) { idx = cards.length - 1; }
      if (idx < 0) { idx = 0; }
      current = idx;
      dots.forEach(function (d, i) { d.classList.toggle('is-active', i === idx); });
    }
    function start() { stop(); if (mqMobile.matches && !mqReduce.matches) { timer = setInterval(next, 2000); } }
    function stop() { if (timer) { clearInterval(timer); timer = null; } }
    function pauseThenResume() { stop(); clearTimeout(resumeT); resumeT = setTimeout(start, 3500); }

    grid.addEventListener('scroll', function () { window.requestAnimationFrame(syncDots); }, { passive: true });
    grid.addEventListener('touchstart', function () { stop(); clearTimeout(resumeT); }, { passive: true });
    grid.addEventListener('touchend', pauseThenResume, { passive: true });
    if (mqMobile.addEventListener) { mqMobile.addEventListener('change', function () { syncDots(); start(); }); }

    syncDots();
    start();
  }

  // Repli : si le filtre PHP n'a pas pris, on remplace « NL Store » par
  // « Store » dans le titre de l'en-tête uniquement (le logo affiche déjà NL).
  function nlHeaderTitle() {
    document.querySelectorAll('.site-title a').forEach(function (a) {
      if (a.children.length === 0 && a.textContent.trim() === 'NL Store') {
        a.textContent = 'Store';
      }
    });
  }

  // Icônes d'en-tête (recherche + panier) injectées à côté du burger, hors menu.
  var NL_SVG_SEARCH = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>';
  var NL_SVG_BAG = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>';

  function nlBuildSearchOverlay(trigger) {
    if (!window.NL_DATA) { return; }
    var ov = document.createElement('div');
    ov.className = 'nl-search-overlay';
    ov.innerHTML =
      '<form class="nl-search-form" action="' + NL_DATA.searchUrl + '" method="get" role="search">' +
        '<span class="nl-search-form__ico" aria-hidden="true">' + NL_SVG_SEARCH + '</span>' +
        '<input type="search" name="s" placeholder="Rechercher un produit…" aria-label="Rechercher" autocomplete="off">' +
        (NL_DATA.hasWoo ? '<input type="hidden" name="post_type" value="product">' : '') +
        '<button type="submit" class="nl-search-go">OK</button>' +
        '<button type="button" class="nl-search-close" aria-label="Fermer">&times;</button>' +
      '</form>';
    document.body.appendChild(ov);
    function open() { ov.classList.add('is-open'); setTimeout(function () { var i = ov.querySelector('input[name=s]'); if (i) { i.focus(); } }, 60); }
    function close() { ov.classList.remove('is-open'); }
    trigger.addEventListener('click', open);
    ov.querySelector('.nl-search-close').addEventListener('click', close);
    ov.addEventListener('click', function (e) { if (e.target === ov) { close(); } });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { close(); } });
  }

  function nlHeaderActions() {
    if (!window.NL_DATA) { return; }
    if (document.querySelector('.nl-header-actions')) { return; }
    var toggle = document.querySelector('.ast-mobile-menu-buttons .menu-toggle, .main-header-bar .menu-toggle, .ast-header-base .menu-toggle, .menu-toggle');
    if (!toggle) { return; }

    var wrap = document.createElement('div');
    wrap.className = 'nl-header-actions';

    var searchBtn = document.createElement('button');
    searchBtn.type = 'button';
    searchBtn.className = 'nl-header-icon nl-header-search';
    searchBtn.setAttribute('aria-label', 'Rechercher');
    searchBtn.innerHTML = NL_SVG_SEARCH;
    wrap.appendChild(searchBtn);

    if (NL_DATA.hasWoo) {
      var cart = document.createElement('a');
      cart.className = 'nl-header-icon nl-header-cart';
      cart.href = NL_DATA.cartUrl;
      cart.setAttribute('aria-label', 'Voir le panier');
      cart.innerHTML = NL_SVG_BAG +
        '<span class="nl-cart-count' + (NL_DATA.cartCount > 0 ? ' has-items' : '') + '">' + NL_DATA.cartCount + '</span>';
      wrap.appendChild(cart);
    }

    toggle.parentNode.insertBefore(wrap, toggle);
    nlBuildSearchOverlay(searchBtn);
  }

  function init() { nlReveal(); nlParallax(); nlSliders(); nlWishlist(); nlCatCarousel(); nlHeaderTitle(); nlHeaderActions(); }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
JS;
    wp_add_inline_script( 'nl-interactions', $js );
}
add_action( 'wp_enqueue_scripts', 'nl_enqueue_interactions', 20 );

/* ----------------------------------------------------------
   ICÔNES LUCIDE — inline SVG (auto-hébergé, aucune dépendance CDN)
   Tracés officiels Lucide (ISC). Usage : <?php echo nl_icon('truck'); ?>
---------------------------------------------------------- */
function nl_icon( $name, $class = 'nl-icon' ) {
    static $paths = null;
    if ( null === $paths ) {
        $paths = [
            'truck'          => '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>',
            'shield-check'   => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>',
            'headphones'     => '<path d="M3 14h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-7a9 9 0 0 1 18 0v7a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3"/>',
            'arrow-right'    => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
            'chevron-left'   => '<path d="m15 18-6-6 6-6"/>',
            'chevron-right'  => '<path d="m9 18 6-6-6-6"/>',
            'flame'          => '<path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/>',
            'quote'          => '<path d="M16 3a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2.5a.5.5 0 0 1 .5.5v.5a2 2 0 0 1-2 2h-.5a1 1 0 0 0 0 2H17a4 4 0 0 0 4-4V5a2 2 0 0 0-2-2zM5 3a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2.5a.5.5 0 0 1 .5.5v.5a2 2 0 0 1-2 2h-.5a1 1 0 0 0 0 2H6a4 4 0 0 0 4-4V5a2 2 0 0 0-2-2z"/>',
            'message-circle' => '<path d="M2.992 16.342a2 2 0 0 1 .094 1.167l-1.065 3.29a1 1 0 0 0 1.236 1.168l3.413-.998a2 2 0 0 1 1.099.092 10 10 0 1 0-4.777-4.719"/>',
            'map-pin'        => '<path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/>',
            'phone'          => '<path d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384"/>',
            'mail'           => '<path d="m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7"/><rect x="2" y="4" width="20" height="16" rx="2"/>',
            'instagram'      => '<rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/>',
            'facebook'       => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
            'heart'          => '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>',
            'shopping-bag'   => '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>',
            'badge-star'     => '<path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"/><path d="M12 8.3 12.94 11.01 15.8 11.06 13.52 12.79 14.35 15.54 12 13.9 9.65 15.54 10.48 12.79 8.2 11.06 11.06 11.01Z"/>',
            'check'          => '<path d="M20 6 9 17l-5-5"/>',
            'x'              => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
        ];
    }
    if ( ! isset( $paths[ $name ] ) ) {
        return '';
    }
    return sprintf(
        '<svg class="%s" xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%s</svg>',
        esc_attr( $class ),
        $paths[ $name ] // tracés statiques de confiance (Lucide ISC)
    );
}

/* ----------------------------------------------------------
   WOOCOMMERCE SUPPORT
---------------------------------------------------------- */
add_action( 'after_setup_theme', function () {
    add_theme_support( 'woocommerce', [
        'thumbnail_image_width' => 600,
        'single_image_width'    => 900,
        'product_grid'          => [
            'default_rows'    => 3,
            'min_rows'        => 1,
            'default_columns' => 4,
            'min_columns'     => 1,
            'max_columns'     => 6,
        ],
    ] );
    add_theme_support( 'wc-product-gallery-zoom' );
    add_theme_support( 'wc-product-gallery-lightbox' );
    add_theme_support( 'wc-product-gallery-slider' );
} );

// Placeholder produit de marque (remplace le placeholder gris WooCommerce par défaut)
add_filter( 'woocommerce_placeholder_img_src', function () {
    return get_stylesheet_directory_uri() . '/assets/img/nl-placeholder.svg';
} );

/* Badge soldes : affiche le pourcentage de remise (-20%) plutôt que « Promo ! ». */
add_filter( 'woocommerce_sale_flash', 'nl_sale_flash_percent', 10, 3 );
function nl_sale_flash_percent( $html, $post, $product ) {
    if ( ! is_a( $product, 'WC_Product' ) ) {
        return $html;
    }
    $regular = (float) $product->get_regular_price();
    $sale    = (float) $product->get_sale_price();
    if ( $regular > 0 && $sale > 0 && $sale < $regular ) {
        $pct = (int) round( ( $regular - $sale ) / $regular * 100 );
        return '<span class="onsale">-' . $pct . '%</span>';
    }
    return '<span class="onsale">Promo</span>';
}

/* Cœur « favoris » sur chaque carte produit (haut-droite, hors du lien produit).
   Priorité 7 : après la fermeture du lien (5), avant le bouton panier (10). */
add_action( 'woocommerce_after_shop_loop_item', 'nl_wishlist_button', 7 );
function nl_wishlist_button() {
    global $product;
    $id = ( $product && is_a( $product, 'WC_Product' ) ) ? $product->get_id() : 0;
    printf(
        '<button type="button" class="nl-wishlist" data-id="%s" aria-label="Ajouter aux favoris" aria-pressed="false">%s</button>',
        esc_attr( $id ),
        nl_icon( 'heart', 'nl-icon' )
    );
}

/* ============================================================
   NL STORE — EN-TÊTE : titre « Store » (le logo affiche déjà « NL »)
   On évite la répétition « NL » + « NL Store » dans l'en-tête UNIQUEMENT.
   Le vrai nom du site (balise <title>, footer…) reste inchangé.
   ============================================================ */
function nl_header_store_title( $html ) {
    return str_replace( 'NL Store', 'Store', $html );
}
// Couvre les différents noms de filtre selon la version d'Astra (sans risque).
add_filter( 'astra_site_title_output', 'nl_header_store_title', 20 );
add_filter( 'astra_site_title', 'nl_header_store_title', 20 );

/* ============================================================
   NL STORE — PANIER : item de menu + compteur live + popup
   ============================================================ */

// Ajoute un lien « Panier » (icône + compteur) au menu principal (et donc au
// menu burger off-canvas qu'Astra clone depuis ce menu).
add_filter( 'wp_nav_menu_items', 'nl_add_cart_menu_item', 10, 2 );
function nl_add_cart_menu_item( $items, $args ) {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return $items;
    }
    if ( empty( $args->theme_location ) || 'primary' !== $args->theme_location ) {
        return $items;
    }
    $count = ( WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;
    $url   = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/panier/' );

    $items .= '<li class="menu-item nl-cart-menu-item"><a href="' . esc_url( $url ) . '" class="nl-cart-link">'
        . nl_icon( 'shopping-bag', 'nl-icon' )
        . '<span class="nl-cart-label">Panier</span>'
        . '<span class="nl-cart-count' . ( $count > 0 ? ' has-items' : '' ) . '">' . (int) $count . '</span>'
        . '</a></li>';

    return $items;
}

// Met à jour le compteur en direct (AJAX) sans recharger la page.
add_filter( 'woocommerce_add_to_cart_fragments', 'nl_cart_count_fragment' );
function nl_cart_count_fragment( $fragments ) {
    $count = ( WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;
    $fragments['.nl-cart-count'] = '<span class="nl-cart-count' . ( $count > 0 ? ' has-items' : '' ) . '">' . (int) $count . '</span>';
    return $fragments;
}

// Popup « Voir le panier » à l'ajout (écoute l'événement WooCommerce added_to_cart).
add_action( 'wp_enqueue_scripts', 'nl_enqueue_cart_toast', 25 );
function nl_enqueue_cart_toast() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }
    $cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/panier/' );
    $label    = esc_js( __( 'Produit ajouté au panier', 'nlstore' ) );
    $btn      = esc_js( __( 'Voir le panier', 'nlstore' ) );
    $url      = esc_url( $cart_url );

    $js = <<<JS
jQuery(function($){
  function nlCartToast(){
    var old = document.querySelector('.nl-cart-toast');
    if (old) { old.remove(); }
    var el = document.createElement('div');
    el.className = 'nl-cart-toast';
    el.setAttribute('role','status');
    el.innerHTML =
      '<span class="nl-cart-toast__ico" aria-hidden="true">&#10003;</span>' +
      '<span class="nl-cart-toast__txt">$label</span>' +
      '<a class="nl-cart-toast__btn" href="$url">$btn</a>' +
      '<button type="button" class="nl-cart-toast__close" aria-label="Fermer">&times;</button>';
    document.body.appendChild(el);
    requestAnimationFrame(function(){ el.classList.add('is-visible'); });
    var timer = setTimeout(close, 5000);
    function close(){ clearTimeout(timer); el.classList.remove('is-visible'); setTimeout(function(){ if(el.parentNode){ el.remove(); } }, 400); }
    el.querySelector('.nl-cart-toast__close').addEventListener('click', close);
  }
  $(document.body).on('added_to_cart', nlCartToast);
});
JS;
    wp_add_inline_script( 'jquery-core', $js );
}

/* ============================================================
   NL STORE — MARQUES : bande défilante + page « Nos marques »
   Réadaptation du composant React/shadcn « Marquee » en PHP/CSS.
   Les marques sont gérées via le filtre 'nl_brands' (nom + logo + url).
   Tant qu'aucun logo n'est fourni, on affiche un wordmark doré.
   ============================================================ */

// Liste des marques (filtrable). 'logo' = nom de fichier dans assets/brands/
// (ou URL absolue) ; vide => wordmark. 'url' = lien externe optionnel.
function nl_brands() {
    $brands = [
        [ 'name' => 'Collection Privée Paris', 'logo' => '', 'url' => '', 'desc' => 'Parfums de niche, éditions dorées.' ],
        [ 'name' => 'Édition La Dorée',        'logo' => '', 'url' => '', 'desc' => 'Fragrances signature, flacons d\'exception.' ],
        [ 'name' => 'Kenzie',                  'logo' => '', 'url' => '', 'desc' => 'Brumes parfumées et senteurs gourmandes.' ],
        [ 'name' => 'Uriage',                  'logo' => '', 'url' => '', 'desc' => 'Soins et hygiène, expertise dermatologique.' ],
    ];
    return apply_filters( 'nl_brands', $brands );
}

// Résout l'URL d'un logo de marque (fichier dans assets/brands/ ou URL absolue).
function nl_brand_logo_url( $logo ) {
    if ( ! $logo ) {
        return '';
    }
    if ( preg_match( '#^https?://#i', $logo ) ) {
        return $logo;
    }
    return get_stylesheet_directory_uri() . '/assets/brands/' . ltrim( $logo, '/' );
}

// Rendu d'une « pastille » de marque (logo si dispo, sinon wordmark).
function nl_brand_chip( $brand ) {
    if ( ! empty( $brand['logo'] ) ) {
        $inner = sprintf(
            '<img src="%s" alt="%s" loading="lazy" decoding="async">',
            esc_url( nl_brand_logo_url( $brand['logo'] ) ),
            esc_attr( $brand['name'] )
        );
    } else {
        $inner = '<span class="nl-brand-wordmark">' . esc_html( $brand['name'] ) . '</span>';
    }
    return '<span class="nl-brand-chip">' . $inner . '</span>';
}

// URL de la page « Nos marques ».
function nl_marques_url() {
    $page = get_page_by_path( 'nos-marques' );
    return $page ? get_permalink( $page ) : home_url( '/nos-marques/' );
}

// Bande défilante des marques — cliquable vers la page « Nos marques ».
add_shortcode( 'nl_brands_marquee', 'nl_render_brands_marquee' );
function nl_render_brands_marquee() {
    $brands = nl_brands();
    if ( empty( $brands ) ) {
        return '';
    }
    $items = '';
    foreach ( $brands as $b ) {
        $items .= nl_brand_chip( $b );
    }
    // On répète le jeu pour remplir l'écran, puis on le double pour la boucle.
    $group = str_repeat( $items, 3 );
    $url   = nl_marques_url();

    ob_start();
    ?>
    <section class="nl-brands">
        <p class="nl-brands__label">Nos marques</p>
        <a class="nl-brands__link" href="<?php echo esc_url( $url ); ?>" aria-label="Découvrir toutes nos marques">
            <div class="nl-brands__marquee">
                <div class="nl-brands__track">
                    <?php echo $group; // pastilles déjà échappées ?>
                    <?php echo $group; ?>
                </div>
            </div>
        </a>
    </section>
    <?php
    return ob_get_clean();
}

// Page « Nos marques » auto-créée (idempotent, verrou d'option).
add_action( 'admin_init', 'nl_seed_marques_page', 26 );
function nl_seed_marques_page() {
    if ( get_option( 'nl_marques_page_seeded' ) === 'v1' ) {
        return;
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $existing = get_page_by_path( 'nos-marques' );
    if ( ! $existing ) {
        $page_id = wp_insert_post( [
            'post_title'   => 'Nos marques',
            'post_name'    => 'nos-marques',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => '',
        ] );
        if ( $page_id && ! is_wp_error( $page_id ) ) {
            update_post_meta( $page_id, '_wp_page_template', 'template-marques.php' );
        }
    } else {
        update_post_meta( $existing->ID, '_wp_page_template', 'template-marques.php' );
    }
    update_option( 'nl_marques_page_seeded', 'v1' );
}

/* ============================================================
   NL STORE — IMPORT PRODUITS (one-shot, auto-désactivé)
   Crée le catalogue de base s'il est absent. S'exécute UNE seule
   fois lorsqu'un administrateur charge l'admin (verrou : option
   nl_products_seeded). Idempotent (saute les SKU déjà présents).
   ➜ Supprimable une fois les produits créés.
   ============================================================ */
add_action( 'admin_init', 'nl_seed_products' );
function nl_seed_products() {
    if ( get_option( 'nl_products_seeded' ) === 'v1' ) {
        return; // déjà fait
    }
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        return; // seul un admin boutique déclenche
    }
    if ( ! function_exists( 'wc_get_product_id_by_sku' ) || ! class_exists( 'WC_Product_Simple' ) ) {
        return; // WooCommerce indisponible
    }

    // Catégories (créées si absentes)
    $cat_ids = [];
    foreach ( [ 'parfums' => 'Parfums', 'brumes-dubai' => 'Brumes Dubaï' ] as $slug => $name ) {
        $term = term_exists( $slug, 'product_cat' );
        if ( ! $term ) {
            $term = wp_insert_term( $name, 'product_cat', [ 'slug' => $slug ] );
        }
        if ( ! is_wp_error( $term ) ) {
            $cat_ids[ $slug ] = (int) ( is_array( $term ) ? $term['term_id'] : $term );
        }
    }

    $cp = 'Collection Privée — Édition La Dorée Paris. Eau de parfum 50 ml, fabriquée pour Maison NL. Disponible exclusivement à Mayotte, livraison rapide.';

    // [ sku, nom, vedette, [slugs catégories], description courte, description longue ]
    $produits = [
        [ 'NL-AISHA-50',        'Aïsha',                     true,  [ 'parfums' ],                 'Sillage oriental floral, chaleureux et enveloppant.', 'Aïsha — ' . $cp . ' Notes ambrées et florales soutenues par un cœur fleuri raffiné.' ],
        [ 'NL-BACCARA-50',      'Baccara',                   true,  [ 'parfums' ],                 'Fruité gourmand intense, signature audacieuse.',      'Baccara — ' . $cp . ' Une composition rouge et envoûtante, fruitée et sucrée, à la tenue exceptionnelle.' ],
        [ 'NL-SCANDALEF-50',    'Scandale Femme',            true,  [ 'parfums' ],                 'Floral séduisant, féminin et lumineux.',              'Scandale Femme — ' . $cp . ' Un bouquet floral moderne et sensuel.' ],
        [ 'NL-YARA-50',         'Yara',                      true,  [ 'parfums', 'brumes-dubai' ], 'Brume parfumée florale et poudrée.',                  'Yara — Brume parfumée florale et poudrée, fraîche et délicate. 50 ml.' ],
        [ 'NL-MOULA-50',        'Moula',                     false, [ 'parfums' ],                 'Ambré boisé, profond et tenace.',                     'Moula — ' . $cp . ' Un accord ambré boisé puissant et raffiné.' ],
        [ 'NL-SCANDALEH-50',    'Scandale Homme',            false, [ 'parfums' ],                 'Boisé frais, élégant et masculin.',                   'Scandale Homme — ' . $cp . ' Une signature boisée fraîche et distinguée.' ],
        [ 'NL-INVICTS-50',      'Invicts',                   false, [ 'parfums' ],                 'Aromatique frais, énergique et moderne.',             'Invicts — ' . $cp . ' Un parfum aromatique vibrant.' ],
        [ 'NL-KIRKE-50',        'Kirké',                     false, [ 'parfums' ],                 'Vert frais et hespéridé.',                            'Kirké — ' . $cp . ' Une fraîcheur verte et hespéridée, vive et raffinée.' ],
        [ 'NL-COCOVAN-50',      'Coco Vanille',              false, [ 'parfums' ],                 'Gourmand vanille & coco, addictif.',                  'Coco Vanille — ' . $cp . ' Un cocon gourmand de vanille et de coco.' ],
        [ 'NL-CREMEBRULEE-50',  'Crème Brûlée',              false, [ 'parfums' ],                 'Gourmand sucré, caramel et vanille.',                 'Crème Brûlée — ' . $cp . ' Un dessert olfactif gourmand, caramélisé et vanillé.' ],
        [ 'NL-YARACANDY-250',   'Yara Candy',                false, [ 'parfums', 'brumes-dubai' ], 'Brume Dubaï fruitée et sucrée.',                      'Yara Candy — Brume parfumée Dubaï, gourmande et fruitée. 250 ml.' ],
        [ 'NL-KENZIE-AMBER-250','Kenzie Amber Lychee',       false, [ 'parfums', 'brumes-dubai' ], 'Brume ambre & litchi, fraîche.',                      'Kenzie Amber Lychee — Brume rafraîchissante ambre et litchi. 250 ml.' ],
        [ 'NL-KENZIE-APPLE-250','Kenzie Exotic Apple Crush', false, [ 'parfums', 'brumes-dubai' ], 'Brume pomme exotique, pétillante.',                   'Kenzie Exotic Apple Crush — Brume rafraîchissante pomme exotique. 250 ml.' ],
    ];

    $created = 0;
    foreach ( $produits as $p ) {
        list( $sku, $name, $featured, $slugs, $short, $desc ) = $p;
        if ( wc_get_product_id_by_sku( $sku ) ) {
            continue; // déjà existant : on saute (idempotent)
        }
        $product = new WC_Product_Simple();
        $product->set_name( $name );
        $product->set_status( 'publish' );
        $product->set_catalog_visibility( 'visible' );
        $product->set_sku( $sku );
        $product->set_regular_price( '15' ); // ⚠️ prix par défaut, à ajuster
        $product->set_featured( (bool) $featured );
        $product->set_short_description( $short );
        $product->set_description( $desc );
        $ids = [];
        foreach ( $slugs as $s ) {
            if ( isset( $cat_ids[ $s ] ) ) {
                $ids[] = $cat_ids[ $s ];
            }
        }
        if ( $ids ) {
            $product->set_category_ids( $ids );
        }
        $product->save();
        $created++;
    }

    update_option( 'nl_products_seeded', 'v1' );
    set_transient( 'nl_products_seeded_notice', $created, 120 );
}

add_action( 'admin_notices', function () {
    $n = get_transient( 'nl_products_seeded_notice' );
    if ( false !== $n ) {
        delete_transient( 'nl_products_seeded_notice' );
        printf(
            '<div class="notice notice-success is-dismissible"><p>✅ <strong>NL Store</strong> : %d produit(s) créé(s) automatiquement. Pensez à ajuster les prix, puis à retirer le bloc d\'import du thème.</p></div>',
            absint( $n )
        );
    }
} );

/* ------------------------------------------------------------
   NL STORE — IMAGES PRODUITS (one-shot, auto-désactivé)
   Verse les photos fournies (assets/products/<SKU>.jpg) dans la
   médiathèque et les définit comme image principale du produit
   correspondant. S'exécute après le seeder produits (priorité 20).
   Idempotent : saute les produits qui ont déjà une image.
   ------------------------------------------------------------ */
add_action( 'admin_init', 'nl_seed_product_images', 20 );
function nl_seed_product_images() {
    if ( get_option( 'nl_product_images_seeded' ) === 'v1' ) {
        return;
    }
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        return;
    }
    if ( ! function_exists( 'wc_get_product_id_by_sku' ) || ! function_exists( 'wc_get_product' ) ) {
        return;
    }

    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $dir  = trailingslashit( get_stylesheet_directory() ) . 'assets/products/';
    $skus = [
        'NL-AISHA-50', 'NL-BACCARA-50', 'NL-SCANDALEF-50', 'NL-YARA-50',
        'NL-MOULA-50', 'NL-SCANDALEH-50', 'NL-INVICTS-50', 'NL-KIRKE-50',
        'NL-COCOVAN-50', 'NL-CREMEBRULEE-50', 'NL-KENZIE-AMBER-250', 'NL-KENZIE-APPLE-250',
    ];

    $done = 0;
    $pending = 0;
    foreach ( $skus as $sku ) {
        $pid = wc_get_product_id_by_sku( $sku );
        if ( ! $pid ) {
            $pending++; // produit pas encore prêt : on réessaiera au prochain chargement
            continue;
        }
        $product = wc_get_product( $pid );
        if ( ! $product || $product->get_image_id() ) {
            continue; // déjà une image (ou produit invalide) : rien à faire
        }
        $file = $dir . $sku . '.jpg';
        if ( ! file_exists( $file ) ) {
            continue; // pas de photo fournie pour ce SKU
        }

        $upload = wp_upload_bits( $sku . '.jpg', null, file_get_contents( $file ) );
        if ( ! empty( $upload['error'] ) ) {
            $pending++;
            continue;
        }
        $filetype  = wp_check_filetype( $upload['file'], null );
        $attach_id = wp_insert_attachment( [
            'post_mime_type' => $filetype['type'],
            'post_title'     => $product->get_name(),
            'post_status'    => 'inherit',
        ], $upload['file'], $pid );
        if ( is_wp_error( $attach_id ) || ! $attach_id ) {
            $pending++;
            continue;
        }
        wp_update_attachment_metadata( $attach_id, wp_generate_attachment_metadata( $attach_id, $upload['file'] ) );
        $product->set_image_id( $attach_id );
        $product->save();
        $done++;
    }

    if ( 0 === $pending ) {
        update_option( 'nl_product_images_seeded', 'v1' ); // tout est traité : on verrouille
    }
    if ( $done ) {
        set_transient( 'nl_product_images_notice', $done, 120 );
    }
}

add_action( 'admin_notices', function () {
    $n = get_transient( 'nl_product_images_notice' );
    if ( false !== $n ) {
        delete_transient( 'nl_product_images_notice' );
        printf(
            '<div class="notice notice-success is-dismissible"><p>🖼️ <strong>NL Store</strong> : %d image(s) produit ajoutée(s) automatiquement.</p></div>',
            absint( $n )
        );
    }
} );

/**
 * ============================================
 * NL STORE TESTIMONIALS CAROUSEL SHORTCODE
 * ============================================
 */

function nl_testimonials_carousel_shortcode($atts) {
    $atts = shortcode_atts(array(
        'title' => 'Avis du Bizkit',
        'max_reviews' => 12,
        'min_rating' => 4,
    ), $atts, 'nl_testimonials_carousel');

    global $wpdb;
    $reviews = $wpdb->get_results($wpdb->prepare(
        "SELECT c.comment_ID, c.comment_author, c.comment_content,
                m.meta_value as rating
         FROM {$wpdb->comments} c
         LEFT JOIN {$wpdb->commentmeta} m ON c.comment_ID = m.comment_id
            AND m.meta_key = 'rating'
         WHERE c.comment_type = 'review'
         AND c.comment_approved = 1
         AND m.meta_value >= %d
         ORDER BY m.meta_value DESC, c.comment_date_gmt DESC
         LIMIT %d",
        intval($atts['min_rating']),
        intval($atts['max_reviews'])
    ));

    ob_start();
    ?>
    <div class="nl-quote-block">
        <p class="nl-col-label"><?php echo esc_html($atts['title']); ?></p>

        <?php if ($reviews && count($reviews) > 0): ?>
        <div class="swiper nl-quote-swiper">
            <div class="swiper-wrapper">
                <?php foreach ($reviews as $review):
                    $rating = intval($review->rating) ?: 5;
                    $author = $review->comment_author ?: 'Client NL Store';
                    $initial = mb_strtoupper(mb_substr($author, 0, 1));
                ?>
                <div class="swiper-slide">
                    <article class="nl-quote-card">
                        <span class="nl-quote-mark"><?php echo nl_icon('quote'); ?></span>
                        <div class="nl-quote-stars" aria-label="<?php echo esc_attr($rating); ?> sur 5">
                            <?php for ($i = 1; $i <= 5; $i++) echo $i <= $rating ? '★' : '<span class="nl-star-empty">★</span>'; ?>
                        </div>
                        <p class="nl-quote-text"><?php echo wp_kses_post($review->comment_content); ?></p>
                        <div class="nl-quote-author">
                            <span class="nl-quote-avatar"><?php echo esc_html($initial); ?></span>
                            <span class="nl-quote-name"><?php echo esc_html($author); ?></span>
                        </div>
                    </article>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="nl-swiper-controls">
                <button type="button" class="nl-swiper-prev" aria-label="Avis précédent"><?php echo nl_icon('chevron-left'); ?></button>
                <div class="swiper-pagination"></div>
                <button type="button" class="nl-swiper-next" aria-label="Avis suivant"><?php echo nl_icon('chevron-right'); ?></button>
            </div>
        </div>
        <?php else: ?>
        <div class="nl-quote-empty">
            <span class="nl-quote-mark"><?php echo nl_icon('quote'); ?></span>
            <p>Soyez le premier à partager votre expérience.<br>Vos avis brillent ici. ✦</p>
        </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

add_shortcode('nl_testimonials_carousel', 'nl_testimonials_carousel_shortcode');

// Enqueue sur wp_enqueue_scripts pour que le CSS Swiper atterrisse bien dans le <head>.
// Swiper (carrousels avis & promos) — enqueue conditionnel + init unifié.
add_action('wp_enqueue_scripts', function() {
    // Ne charge Swiper que sur les pages qui rendent un carrousel (perf).
    $needs = is_front_page() || is_page_template( 'template-accueil.php' );
    if ( ! $needs && is_singular() ) {
        $post = get_post();
        if ( $post && ( has_shortcode( $post->post_content, 'nl_weekly_promos_carousel' )
                     || has_shortcode( $post->post_content, 'nl_testimonials_carousel' ) ) ) {
            $needs = true;
        }
    }
    if ( ! apply_filters( 'nl_enqueue_swiper', $needs ) ) {
        return;
    }

    // Swiper auto-hébergé (assets/swiper) — aucune dépendance CDN.
    $dir = get_stylesheet_directory_uri() . '/assets/swiper/';
    wp_enqueue_script('swiper-js', $dir . 'swiper-bundle.min.js', [], '11.2.10', true);
    wp_enqueue_style('swiper-css', $dir . 'swiper-bundle.min.css', [], '11.2.10');

    $js = <<<'JS'
document.addEventListener('DOMContentLoaded', function () {
  if (typeof Swiper === 'undefined') return;
  function init(sel, opts) {
    document.querySelectorAll(sel).forEach(function (el) {
      var n = el.querySelectorAll('.swiper-slide').length;
      new Swiper(el, Object.assign({
        slidesPerView: 1,
        spaceBetween: 22,
        grabCursor: true,
        loop: n > 1,
        autoplay: n > 1 ? { delay: 5200, disableOnInteraction: false, pauseOnMouseEnter: true } : false,
        pagination: { el: el.querySelector('.swiper-pagination'), clickable: true },
        navigation: { nextEl: el.querySelector('.nl-swiper-next'), prevEl: el.querySelector('.nl-swiper-prev') }
      }, opts || {}));
    });
  }
  init('.nl-quote-swiper');
  init('.nl-wpromo-swiper');
});
JS;
    wp_add_inline_script('swiper-js', $js);
});


/**
 * ============================================
 * NL STORE - PROMOTIONS MANAGER
 * ============================================
 */

add_action('admin_menu', 'nl_add_promotions_menu');

function nl_add_promotions_menu() {
    add_menu_page(
        'NL Store - Promotions',
        'NL Store',
        'manage_options',
        'nl-store-promotions',
        'nl_promotions_page',
        'dashicons-star-filled',
        3
    );
    
    add_submenu_page(
        'nl-store-promotions',
        'Bannière Promo',
        'Bannière Promo',
        'manage_options',
        'nl-promo-banner',
        'nl_promo_banner_page'
    );
    
    add_submenu_page(
        'nl-store-promotions',
        'Promotions de la Semaine',
        'Promotions Semaine',
        'manage_options',
        'nl-weekly-promos',
        'nl_weekly_promos_page'
    );

    add_submenu_page(
        'nl-store-promotions',
        'Barre de réassurance',
        'Réassurance',
        'manage_options',
        'nl-reassurance',
        'nl_reassurance_page'
    );

    add_submenu_page(
        'nl-store-promotions',
        'Carte (localisation)',
        'Carte',
        'manage_options',
        'nl-map-settings',
        'nl_map_settings_page'
    );

    add_submenu_page(
        'nl-store-promotions',
        'SEO & Analytics',
        'SEO & Analytics',
        'manage_options',
        'nl-seo-settings',
        'nl_seo_settings_page'
    );
}

/* ============================================================
   NL STORE — SEO, ANALYTICS (gated RGPD) & BANDEAU COOKIES
   ============================================================ */
function nl_seo_settings() {
    $defaults = [
        'meta_description' => '',
        'ga4_id'           => '',
        'og_image'         => '',
    ];
    $opt = get_option( 'nl_seo_settings', [] );
    return array_merge( $defaults, is_array( $opt ) ? $opt : [] );
}

function nl_seo_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    if ( 'POST' === $_SERVER['REQUEST_METHOD'] && check_admin_referer( 'nl_seo_settings_nonce' ) ) {
        update_option( 'nl_seo_settings', [
            'meta_description' => sanitize_text_field( wp_unslash( $_POST['meta_description'] ?? '' ) ),
            'ga4_id'           => sanitize_text_field( wp_unslash( $_POST['ga4_id'] ?? '' ) ),
            'og_image'         => esc_url_raw( wp_unslash( $_POST['og_image'] ?? '' ) ),
        ] );
        echo '<div class="notice notice-success"><p>✅ Réglages SEO & Analytics enregistrés !</p></div>';
    }
    $s = nl_seo_settings();
    ?>
    <div class="wrap">
        <h1>🔎 SEO & Analytics</h1>
        <p>Description par défaut pour les moteurs de recherche et le partage, et suivi d'audience (chargé uniquement après acceptation des cookies).</p>
        <form method="post" style="background:#fff;padding:20px;border-radius:8px;max-width:720px;">
            <?php wp_nonce_field( 'nl_seo_settings_nonce' ); ?>
            <table class="form-table">
                <tr>
                    <th><label for="meta_description">Description par défaut (meta)</label></th>
                    <td>
                        <textarea id="meta_description" name="meta_description" rows="3" class="large-text" maxlength="320" placeholder="Ex : Parfums, articles bébé et vêtements à Mayotte et alentours. Qualité, prix doux, livraison rapide."><?php echo esc_textarea( $s['meta_description'] ); ?></textarea>
                        <p class="description">~150-160 caractères idéalement. Utilisée si la page n'a pas de description propre.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="og_image">Image de partage (URL)</label></th>
                    <td>
                        <input type="url" id="og_image" name="og_image" value="<?php echo esc_attr( $s['og_image'] ); ?>" class="large-text" placeholder="https://… (1200×630 conseillé)">
                        <p class="description">Image affichée lors d'un partage (Facebook, WhatsApp…). Par défaut : le logo.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="ga4_id">ID Google Analytics (GA4)</label></th>
                    <td>
                        <input type="text" id="ga4_id" name="ga4_id" value="<?php echo esc_attr( $s['ga4_id'] ); ?>" style="width:260px;" placeholder="G-XXXXXXXXXX">
                        <p class="description">Commence par « G- ». Le suivi ne se charge <strong>qu'après acceptation</strong> des cookies (RGPD).</p>
                    </td>
                </tr>
            </table>
            <?php submit_button( 'Enregistrer' ); ?>
        </form>
    </div>
    <?php
}

// Balises meta description + Open Graph / Twitter.
add_action( 'wp_head', 'nl_seo_meta', 1 );
function nl_seo_meta() {
    $s    = nl_seo_settings();
    $info = function_exists( 'nl_company_info' ) ? nl_company_info() : [];

    // Description : excerpt de la page > réglage > baseline.
    $desc = '';
    if ( is_singular() ) {
        $post = get_queried_object();
        if ( $post && ! empty( $post->post_excerpt ) ) {
            $desc = $post->post_excerpt;
        }
    }
    if ( ! $desc ) {
        $desc = $s['meta_description'] ?: ( $info['baseline'] ?? get_bloginfo( 'description' ) );
    }
    $desc = wp_strip_all_tags( $desc );

    $title = wp_get_document_title();
    $url   = home_url( add_query_arg( null, null ) );
    $image = $s['og_image'];
    if ( ! $image ) {
        $logo_id = get_theme_mod( 'custom_logo' );
        $image   = $logo_id ? wp_get_attachment_image_url( $logo_id, 'large' ) : '';
    }

    echo "\n<!-- NL Store SEO -->\n";
    printf( '<meta name="description" content="%s">' . "\n", esc_attr( $desc ) );
    printf( '<meta property="og:type" content="%s">' . "\n", is_singular( 'product' ) ? 'product' : 'website' );
    printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( get_bloginfo( 'name' ) ) );
    printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $title ) );
    printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $desc ) );
    printf( '<meta property="og:url" content="%s">' . "\n", esc_url( $url ) );
    if ( $image ) {
        printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $image ) );
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    } else {
        echo '<meta name="twitter:card" content="summary">' . "\n";
    }
    printf( '<meta name="twitter:title" content="%s">' . "\n", esc_attr( $title ) );
    printf( '<meta name="twitter:description" content="%s">' . "\n", esc_attr( $desc ) );
}

// Google Analytics (GA4) — chargé UNIQUEMENT après acceptation des cookies.
add_action( 'wp_head', 'nl_analytics', 20 );
function nl_analytics() {
    $s     = nl_seo_settings();
    $ga_id = preg_replace( '/[^A-Za-z0-9\-]/', '', $s['ga4_id'] );
    if ( ! $ga_id ) {
        return;
    }
    $ga_js = esc_js( $ga_id );
    ?>
    <script>
    (function () {
      var GA = '<?php echo $ga_js; ?>';
      function consent() { try { return localStorage.getItem('nl_cookie_consent'); } catch (e) { return null; } }
      function loadGA() {
        if (window.__nlGAloaded || !GA) { return; }
        window.__nlGAloaded = true;
        var s = document.createElement('script');
        s.async = true;
        s.src = 'https://www.googletagmanager.com/gtag/js?id=' + GA;
        document.head.appendChild(s);
        window.dataLayer = window.dataLayer || [];
        window.gtag = function () { dataLayer.push(arguments); };
        gtag('js', new Date());
        gtag('config', GA, { anonymize_ip: true });
      }
      if (consent() === 'accept') { loadGA(); }
      document.addEventListener('nl:consent', function (e) { if (e.detail === 'accept') { loadGA(); } });
    })();
    </script>
    <?php
}

// Bandeau cookies (RGPD) — auto-contenu (consentement en localStorage).
add_action( 'wp_footer', 'nl_render_cookie_banner', 20 );
function nl_render_cookie_banner() {
    if ( is_admin() ) {
        return;
    }
    $privacy_url = ( $p = get_page_by_path( 'politique-de-confidentialite' ) ) ? get_permalink( $p ) : home_url( '/politique-de-confidentialite/' );
    ?>
    <div class="nl-cookie" id="nl-cookie" role="dialog" aria-label="Préférences cookies" hidden>
        <p class="nl-cookie__txt">Nous utilisons des cookies pour le bon fonctionnement du site et, avec votre accord, pour mesurer l'audience.
            <a href="<?php echo esc_url( $privacy_url ); ?>">En savoir plus</a>.</p>
        <div class="nl-cookie__actions">
            <button type="button" class="nl-cookie__btn nl-cookie__btn--ghost" data-nl-cookie="refuse">Refuser</button>
            <button type="button" class="nl-cookie__btn" data-nl-cookie="accept">Accepter</button>
        </div>
    </div>
    <script>
    (function () {
      var el = document.getElementById('nl-cookie');
      if (!el) { return; }
      function get() { try { return localStorage.getItem('nl_cookie_consent'); } catch (e) { return 'na'; } }
      function set(v) { try { localStorage.setItem('nl_cookie_consent', v); } catch (e) {} }
      if (!get()) { el.hidden = false; requestAnimationFrame(function () { el.classList.add('is-visible'); }); }
      el.querySelectorAll('[data-nl-cookie]').forEach(function (b) {
        b.addEventListener('click', function () {
          var v = b.getAttribute('data-nl-cookie');
          set(v);
          document.dispatchEvent(new CustomEvent('nl:consent', { detail: v }));
          el.classList.remove('is-visible');
          setTimeout(function () { el.hidden = true; }, 350);
        });
      });
    })();
    </script>
    <?php
}

/* ============================================================
   NL STORE — RÉGLAGES CARTE (Mapbox token + coordonnées)
   ============================================================ */
function nl_map_settings() {
    $info = function_exists( 'nl_company_info' ) ? nl_company_info() : [];
    $defaults = [
        'mapbox_token' => '',
        'style'        => 'mapbox://styles/mapbox/dark-v11',
        'lat'          => isset( $info['lat'] ) ? (float) $info['lat'] : -12.7847,
        'lng'          => isset( $info['lng'] ) ? (float) $info['lng'] : 45.11,
        'zoom'         => 14,
    ];
    $opt = get_option( 'nl_map_settings', [] );
    if ( ! is_array( $opt ) ) {
        $opt = [];
    }
    return array_merge( $defaults, $opt );
}

function nl_map_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    if ( 'POST' === $_SERVER['REQUEST_METHOD'] && check_admin_referer( 'nl_map_settings_nonce' ) ) {
        $lat = (float) str_replace( ',', '.', wp_unslash( $_POST['map_lat'] ?? '' ) );
        $lng = (float) str_replace( ',', '.', wp_unslash( $_POST['map_lng'] ?? '' ) );
        update_option( 'nl_map_settings', [
            'mapbox_token' => sanitize_text_field( wp_unslash( $_POST['mapbox_token'] ?? '' ) ),
            'style'        => sanitize_text_field( wp_unslash( $_POST['map_style'] ?? 'mapbox://styles/mapbox/dark-v11' ) ),
            'lat'          => $lat,
            'lng'          => $lng,
            'zoom'         => max( 3, min( 20, absint( wp_unslash( $_POST['map_zoom'] ?? 14 ) ) ) ),
        ] );
        echo '<div class="notice notice-success"><p>✅ Réglages carte enregistrés !</p></div>';
    }
    $s = nl_map_settings();
    ?>
    <div class="wrap">
        <h1>🗺️ Carte (localisation)</h1>
        <p>Sans token, la carte utilise un fond sombre gratuit (CARTO). Avec un <strong>token Mapbox</strong> (gratuit), elle bascule sur le rendu dark premium Mapbox.</p>
        <p><a href="https://account.mapbox.com/access-tokens/" target="_blank" rel="noopener">→ Créer un token Mapbox gratuit</a> (compte gratuit, 50 000 chargements/mois).</p>
        <form method="post" style="background:#fff;padding:20px;border-radius:8px;max-width:680px;">
            <?php wp_nonce_field( 'nl_map_settings_nonce' ); ?>
            <table class="form-table">
                <tr>
                    <th><label for="mapbox_token">Token Mapbox</label></th>
                    <td>
                        <input type="text" id="mapbox_token" name="mapbox_token" value="<?php echo esc_attr( $s['mapbox_token'] ); ?>" class="large-text" placeholder="pk.eyJ1Ijoi… (laisser vide = carte gratuite)">
                        <p class="description">Token public Mapbox (commence par « pk. »). Vide = carte gratuite CARTO Dark.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="map_style">Style Mapbox</label></th>
                    <td>
                        <input type="text" id="map_style" name="map_style" value="<?php echo esc_attr( $s['style'] ); ?>" class="large-text">
                        <p class="description">Par défaut : <code>mapbox://styles/mapbox/dark-v11</code>. (Ignoré si pas de token.)</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="map_lat">Latitude</label></th>
                    <td><input type="text" id="map_lat" name="map_lat" value="<?php echo esc_attr( $s['lat'] ); ?>" style="width:200px;"></td>
                </tr>
                <tr>
                    <th><label for="map_lng">Longitude</label></th>
                    <td><input type="text" id="map_lng" name="map_lng" value="<?php echo esc_attr( $s['lng'] ); ?>" style="width:200px;"></td>
                </tr>
                <tr>
                    <th><label for="map_zoom">Zoom</label></th>
                    <td><input type="number" id="map_zoom" name="map_zoom" value="<?php echo esc_attr( $s['zoom'] ); ?>" min="3" max="20" style="width:90px;"></td>
                </tr>
            </table>
            <p class="description" style="margin:10px 0;">💡 Pour trouver les coordonnées : sur Google Maps, clic droit sur l'emplacement → cliquez sur les chiffres (lat, lng) pour les copier.</p>
            <?php submit_button( 'Enregistrer' ); ?>
        </form>
    </div>
    <?php
}

/* ============================================================
   NL STORE — RÉASSURANCE (barre 4 items configurable)
   ============================================================ */

// Icônes proposées au choix dans le back-office.
function nl_reassurance_icon_choices() {
    return [
        'truck'          => 'Camion (livraison)',
        'shield-check'   => 'Bouclier (sécurité)',
        'headphones'     => 'Casque (support)',
        'badge-star'     => 'Sceau étoile (qualité)',
        'flame'          => 'Flamme (promo)',
        'heart'          => 'Cœur',
        'shopping-bag'   => 'Sac (panier)',
        'map-pin'        => 'Épingle (localisation)',
        'phone'          => 'Téléphone',
        'mail'           => 'E-mail',
        'message-circle' => 'Message',
    ];
}

// Données de la barre de réassurance (option + valeurs par défaut).
function nl_reassurance_data() {
    $defaults = [
        'is_active' => 1,
        'items'     => [
            [ 'icon' => 'truck',        'title' => 'Livraison rapide',    'subtitle' => 'À Mayotte et alentours' ],
            [ 'icon' => 'shield-check', 'title' => 'Paiement sécurisé',   'subtitle' => '100% sécurisé' ],
            [ 'icon' => 'headphones',   'title' => 'Service client',      'subtitle' => 'À votre écoute' ],
            [ 'icon' => 'badge-star',   'title' => 'Produits de qualité', 'subtitle' => 'Sélection premium' ],
        ],
    ];
    $data = get_option( 'nl_reassurance', [] );
    if ( ! is_array( $data ) || empty( $data['items'] ) ) {
        return $defaults;
    }
    $data['is_active'] = isset( $data['is_active'] ) ? (int) $data['is_active'] : 1;
    return $data;
}

function nl_reassurance_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $choices = nl_reassurance_icon_choices();

    if ( 'POST' === $_SERVER['REQUEST_METHOD'] && check_admin_referer( 'nl_reassurance_nonce' ) ) {
        $titles    = (array) wp_unslash( $_POST['title'] ?? [] );
        $subtitles = (array) wp_unslash( $_POST['subtitle'] ?? [] );
        $icons     = (array) wp_unslash( $_POST['icon'] ?? [] );
        $items     = [];
        for ( $i = 0; $i < 4; $i++ ) {
            $icon  = isset( $icons[ $i ] ) ? sanitize_key( $icons[ $i ] ) : 'truck';
            if ( ! isset( $choices[ $icon ] ) ) {
                $icon = 'truck';
            }
            $items[] = [
                'icon'     => $icon,
                'title'    => sanitize_text_field( $titles[ $i ] ?? '' ),
                'subtitle' => sanitize_text_field( $subtitles[ $i ] ?? '' ),
            ];
        }
        update_option( 'nl_reassurance', [
            'is_active' => isset( $_POST['is_active'] ) ? 1 : 0,
            'items'     => $items,
        ] );
        echo '<div class="notice notice-success"><p>✅ Barre de réassurance mise à jour !</p></div>';
    }

    $data = nl_reassurance_data();
    ?>
    <div class="wrap">
        <h1>🛡️ Barre de réassurance</h1>
        <p>Cette barre s'affiche sur l'accueil, entre « Nos catégories » et « Best-sellers ». Personnalisez les 4 blocs (icône, titre, sous-titre).</p>
        <form method="post" style="background:#fff;padding:20px;border-radius:8px;max-width:760px;">
            <?php wp_nonce_field( 'nl_reassurance_nonce' ); ?>
            <p>
                <label><input type="checkbox" name="is_active" <?php checked( $data['is_active'], 1 ); ?>> Afficher la barre</label>
            </p>
            <table class="form-table">
                <?php for ( $i = 0; $i < 4; $i++ ) :
                    $it = $data['items'][ $i ] ?? [ 'icon' => 'truck', 'title' => '', 'subtitle' => '' ];
                ?>
                <tr>
                    <th>Bloc <?php echo (int) ( $i + 1 ); ?></th>
                    <td>
                        <select name="icon[]" style="min-width:200px;margin-bottom:6px;">
                            <?php foreach ( $choices as $key => $label ) : ?>
                                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $it['icon'], $key ); ?>><?php echo esc_html( $label ); ?></option>
                            <?php endforeach; ?>
                        </select><br>
                        <input type="text" name="title[]" value="<?php echo esc_attr( $it['title'] ); ?>" class="large-text" placeholder="Titre" style="margin-bottom:6px;"><br>
                        <input type="text" name="subtitle[]" value="<?php echo esc_attr( $it['subtitle'] ); ?>" class="large-text" placeholder="Sous-titre">
                    </td>
                </tr>
                <?php endfor; ?>
            </table>
            <?php submit_button( 'Enregistrer' ); ?>
        </form>
    </div>
    <?php
}

// Rendu de la barre de réassurance (utilisé par le template d'accueil).
function nl_render_reassurance() {
    $data = nl_reassurance_data();
    if ( empty( $data['is_active'] ) || empty( $data['items'] ) ) {
        return '';
    }
    ob_start();
    ?>
    <section class="nl-reassurance nl-reveal">
        <div class="nl-reassurance__grid">
            <?php foreach ( $data['items'] as $it ) :
                if ( empty( $it['title'] ) && empty( $it['subtitle'] ) ) {
                    continue;
                }
            ?>
            <div class="nl-reassurance__item">
                <span class="nl-reassurance__ico"><?php echo nl_icon( $it['icon'] ?? 'truck' ); ?></span>
                <div class="nl-reassurance__txt">
                    <strong><?php echo esc_html( $it['title'] ?? '' ); ?></strong>
                    <span><?php echo esc_html( $it['subtitle'] ?? '' ); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
    return ob_get_clean();
}
add_shortcode( 'nl_reassurance', 'nl_render_reassurance' );

function nl_promotions_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1>🎁 NL Store - Gestion des Promotions</h1>
        <p>Gérez vos bannières promo et vos promotions de la semaine.</p>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 30px;">
            <div style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #d4af37;">
                <h2>📢 Bannière Promo</h2>
                <p>Configurez la bannière défilante avec vos offres limitées</p>
                <a href="?page=nl-promo-banner" class="button button-primary">Gérer la bannière</a>
            </div>
            
            <div style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #d4af37;">
                <h2>⭐ Promotions de la Semaine</h2>
                <p>Créez un carousel de promotions à la une</p>
                <a href="?page=nl-weekly-promos" class="button button-primary">Gérer les promos</a>
            </div>
        </div>
    </div>
    <?php
}

function nl_promo_banner_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('nl_promo_banner_nonce')) {
        // Messages multiples (un par ligne) pour la bande défilante.
        $raw_messages = wp_unslash($_POST['banner_messages'] ?? '');
        $lines        = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $raw_messages)));
        $messages     = array_values(array_map('sanitize_text_field', $lines));

        $banner_data = array(
            // 'text' conservé (1er message) pour compatibilité.
            'text' => $messages ? $messages[0] : sanitize_text_field(wp_unslash($_POST['banner_text'] ?? '')),
            'messages' => $messages,
            'speed' => max(8, min(120, absint(wp_unslash($_POST['banner_speed'] ?? 26)))),
            'link_text' => sanitize_text_field(wp_unslash($_POST['banner_link_text'] ?? '')),
            'link_url' => esc_url_raw(wp_unslash($_POST['banner_link_url'] ?? '')),
            'bg_gradient_from' => sanitize_hex_color(wp_unslash($_POST['bg_gradient_from'] ?? '#d4af37')),
            'bg_gradient_to' => sanitize_hex_color(wp_unslash($_POST['bg_gradient_to'] ?? '#c9a22e')),
            'text_color' => sanitize_hex_color(wp_unslash($_POST['text_color'] ?? '#050505')),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        );

        update_option('nl_promo_banner', $banner_data);
        echo '<div class="notice notice-success"><p>✅ Bannière mise à jour!</p></div>';
    }

    $banner = get_option('nl_promo_banner', array(
        'text' => 'Offre limitée - Pack Bébé à 29,90€',
        'messages' => array('Offre limitée - Pack Bébé à 29,90€'),
        'speed' => 26,
        'link_text' => 'Commander maintenant',
        'link_url' => '/produit/pack-bebe-complet/',
        'bg_gradient_from' => '#d4af37',
        'bg_gradient_to' => '#c9a22e',
        'text_color' => '#050505',
        'is_active' => 1,
    ));
    // Compat : si pas encore de 'messages', on dérive depuis 'text'.
    $banner_messages = ! empty( $banner['messages'] ) && is_array( $banner['messages'] )
        ? $banner['messages']
        : array_filter( array( $banner['text'] ?? '' ) );
    $banner_speed = isset( $banner['speed'] ) ? (int) $banner['speed'] : 26;
    ?>
    <div class="wrap">
        <h1>📢 Configuration - Bannière Promo</h1>
        
        <div style="margin: 20px 0; padding: 20px; background: #f0f0f0; border-radius: 8px;">
            <h3>Shortcode :</h3>
            <code style="background: #fff; padding: 10px; border-radius: 4px; display: block;">[nl_promo_banner]</code>
        </div>
        
        <form method="post" style="background: #fff; padding: 20px; border-radius: 8px; max-width: 600px;">
            <?php wp_nonce_field('nl_promo_banner_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th><label for="banner_messages">Messages</label></th>
                    <td>
                        <textarea id="banner_messages" name="banner_messages" rows="4" class="large-text" placeholder="Un message par ligne…"><?php echo esc_textarea( implode( "\n", $banner_messages ) ); ?></textarea>
                        <p class="description">Un message par ligne. Ils défilent à la suite dans la bannière.</p>
                    </td>
                </tr>

                <tr>
                    <th><label for="banner_speed">Vitesse de défilement</label></th>
                    <td>
                        <input type="number" id="banner_speed" name="banner_speed" value="<?php echo esc_attr( $banner_speed ); ?>" min="8" max="120" step="1" style="width:90px;"> secondes par boucle
                        <p class="description">Plus le nombre est grand, plus le défilement est lent (8 à 120).</p>
                    </td>
                </tr>

                <tr>
                    <th><label for="banner_link_text">Texte du lien</label></th>
                    <td>
                        <input type="text" id="banner_link_text" name="banner_link_text" value="<?php echo esc_attr($banner['link_text'] ?? ''); ?>" class="large-text">
                    </td>
                </tr>

                <tr>
                    <th><label for="banner_link_url">URL du lien</label></th>
                    <td>
                        <input type="url" id="banner_link_url" name="banner_link_url" value="<?php echo esc_attr($banner['link_url'] ?? ''); ?>" class="large-text">
                    </td>
                </tr>
                
                <tr>
                    <th><label for="bg_gradient_from">Couleur gradient début</label></th>
                    <td>
                        <input type="color" id="bg_gradient_from" name="bg_gradient_from" value="<?php echo esc_attr($banner['bg_gradient_from'] ?? ''); ?>" style="width: 100px;">
                    </td>
                </tr>
                
                <tr>
                    <th><label for="bg_gradient_to">Couleur gradient fin</label></th>
                    <td>
                        <input type="color" id="bg_gradient_to" name="bg_gradient_to" value="<?php echo esc_attr($banner['bg_gradient_to'] ?? ''); ?>" style="width: 100px;">
                    </td>
                </tr>
                
                <tr>
                    <th><label for="text_color">Couleur du texte</label></th>
                    <td>
                        <input type="color" id="text_color" name="text_color" value="<?php echo esc_attr($banner['text_color'] ?? ''); ?>" style="width: 100px;">
                    </td>
                </tr>
                
                <tr>
                    <th><label for="is_active">Actif</label></th>
                    <td>
                        <input type="checkbox" id="is_active" name="is_active" <?php checked($banner['is_active'], 1); ?>>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('Enregistrer'); ?>
        </form>
    </div>
    <?php
}

function nl_weekly_promos_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'nl_weekly_promos';
    
    // Crée la table seulement si absente (évite dbDelta à chaque chargement de la page).
    if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
        nl_create_promotions_table();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('nl_weekly_promos_nonce')) {
        if (isset($_POST['action']) && $_POST['action'] === 'add_promo') {
            $promo_data = array(
                'title' => sanitize_text_field(wp_unslash($_POST['promo_title'])),
                'description' => sanitize_textarea_field(wp_unslash($_POST['promo_description'])),
                'price' => sanitize_text_field(wp_unslash($_POST['promo_price'])),
                'image_url' => esc_url_raw(wp_unslash($_POST['promo_image'])),
                'link_url' => esc_url_raw(wp_unslash($_POST['promo_link'])),
                'created_at' => current_time('mysql'),
            );
            
            $wpdb->insert($table, $promo_data);
            echo '<div class="notice notice-success"><p>✅ Promotion ajoutée!</p></div>';
        }
        
        if (isset($_POST['action']) && $_POST['action'] === 'delete_promo') {
            $id = intval($_POST['promo_id']);
            $wpdb->delete($table, array('id' => $id));
            echo '<div class="notice notice-success"><p>✅ Promotion supprimée!</p></div>';
        }
    }
    
    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- nom de table statique du thème
    $promos = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC" );
    ?>
    <div class="wrap">
        <h1>⭐ Promotions de la Semaine</h1>
        
        <div style="margin: 20px 0; padding: 20px; background: #f0f0f0; border-radius: 8px;">
            <h3>Shortcode :</h3>
            <code style="background: #fff; padding: 10px; border-radius: 4px; display: block;">[nl_weekly_promos_carousel]</code>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 30px;">
            
            <div style="background: #fff; padding: 20px; border-radius: 8px;">
                <h2>➕ Ajouter une promotion</h2>
                <form method="post">
                    <?php wp_nonce_field('nl_weekly_promos_nonce'); ?>
                    <input type="hidden" name="action" value="add_promo">
                    
                    <div style="margin-bottom: 15px;">
                        <label for="promo_title">Titre *</label><br>
                        <input type="text" id="promo_title" name="promo_title" class="widefat" required>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label for="promo_description">Description *</label><br>
                        <textarea id="promo_description" name="promo_description" class="widefat" rows="3" required></textarea>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label for="promo_price">Prix *</label><br>
                        <input type="text" id="promo_price" name="promo_price" class="widefat" required>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label for="promo_image">URL Image *</label><br>
                        <input type="url" id="promo_image" name="promo_image" class="widefat" required>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label for="promo_link">URL produit *</label><br>
                        <input type="url" id="promo_link" name="promo_link" class="widefat" required>
                    </div>
                    
                    <?php submit_button('Ajouter'); ?>
                </form>
            </div>
            
            <div style="background: #fff; padding: 20px; border-radius: 8px;">
                <h2>📋 Promotions actives (<?php echo absint( count( $promos ) ); ?>)</h2>
                
                <?php if (empty($promos)): ?>
                    <p style="color: #999;">Aucune promotion</p>
                <?php else: ?>
                    <div style="max-height: 500px; overflow-y: auto;">
                        <?php foreach ($promos as $promo): ?>
                            <div style="padding: 12px; border: 1px solid #eee; border-radius: 4px; margin-bottom: 10px;">
                                <strong><?php echo esc_html($promo->title); ?></strong>
                                <div style="font-size: 12px; color: #666; margin: 5px 0;">
                                    <?php echo esc_html(substr($promo->description, 0, 50)); ?>...
                                </div>
                                <div style="font-size: 16px; color: #d4af37; font-weight: bold; margin: 5px 0;">
                                    <?php echo esc_html($promo->price); ?>
                                </div>
                                
                                <form method="post" style="display: inline;">
                                    <?php wp_nonce_field('nl_weekly_promos_nonce'); ?>
                                    <input type="hidden" name="action" value="delete_promo">
                                    <input type="hidden" name="promo_id" value="<?php echo esc_attr( $promo->id ); ?>">
                                    <button type="submit" class="button button-small" onclick="return confirm('Supprimer?')">Supprimer</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}

function nl_create_promotions_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'nl_weekly_promos';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        description longtext NOT NULL,
        price varchar(50) NOT NULL,
        image_url longtext NOT NULL,
        link_url longtext NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Création de la table uniquement à l'activation du thème (évite dbDelta à chaque pageload).
add_action('after_switch_theme', 'nl_create_promotions_table');

// SHORTCODES
add_shortcode('nl_promo_banner', 'nl_render_promo_banner');

function nl_render_promo_banner() {
    static $rendered = false;
    $banner = get_option('nl_promo_banner');

    // Garde anti-double-rendu (rendue via wp_body_open ; évite un doublon si
    // le shortcode [nl_promo_banner] est aussi placé dans le contenu).
    if ( ! $banner || empty( $banner['is_active'] ) || $rendered ) {
        return '';
    }
    $rendered = true;

    // Couleurs déjà validées par sanitize_hex_color à l'enregistrement.
    $gradient = sprintf(
        'linear-gradient(90deg, %s 0%%, %s 100%%)',
        $banner['bg_gradient_from'],
        $banner['bg_gradient_to']
    );
    
    $color = esc_attr( $banner['text_color'] );

    // Liste des messages (compat : dérive depuis 'text' si 'messages' absent).
    $messages = ( ! empty( $banner['messages'] ) && is_array( $banner['messages'] ) )
        ? $banner['messages']
        : array_filter( array( $banner['text'] ?? '' ) );
    if ( empty( $messages ) ) {
        return '';
    }
    $speed = isset( $banner['speed'] ) ? max( 8, min( 120, (int) $banner['speed'] ) ) : 26;

    // Construit un item par message (+ un item « lien » optionnel à la fin).
    $items = '';
    foreach ( $messages as $msg ) {
        $items .= '<span class="nl-promo-marquee__item" style="color:' . $color . ';">'
            . esc_html( $msg ) . '</span>';
    }
    if ( ! empty( $banner['link_text'] ) && ! empty( $banner['link_url'] ) ) {
        $items .= '<span class="nl-promo-marquee__item" style="color:' . $color . ';"><a href="'
            . esc_url( $banner['link_url'] ) . '" style="color:' . $color . ';">'
            . esc_html( $banner['link_text'] ) . '</a></span>';
    }

    // On répète assez de fois pour remplir l'écran, puis on double pour la boucle.
    $group = str_repeat( $items, 3 );

    ob_start();
    ?>
    <div class="nl-promo-banner" style="background: <?php echo esc_attr( $gradient ); ?>;">
        <div class="nl-promo-marquee">
            <div class="nl-promo-marquee__track" style="animation-duration: <?php echo (int) $speed; ?>s;">
                <?php echo $group; // déjà échappé ci-dessus ?>
                <?php echo $group; // 2e copie pour la boucle sans couture ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/* Affiche la bannière promo en barre haute, sur toutes les pages. */
add_action( 'wp_body_open', 'nl_render_promo_banner_top', 5 );
function nl_render_promo_banner_top() {
    echo nl_render_promo_banner(); // sortie déjà échappée dans la fonction
}

/* Bannière promo par défaut (one-shot) pour qu'elle s'affiche d'emblée. */
add_action( 'after_switch_theme', 'nl_seed_promo_banner' );
add_action( 'admin_init', 'nl_seed_promo_banner' );
function nl_seed_promo_banner() {
    if ( false !== get_option( 'nl_promo_banner', false ) ) {
        return; // déjà configurée
    }
    add_option( 'nl_promo_banner', [
        'text'             => 'Livraison rapide partout à Mayotte — Qualité garantie',
        'messages'         => [
            'Livraison rapide partout à Mayotte',
            'Qualité garantie sur toute la sélection',
            'Paiement 100% sécurisé',
        ],
        'speed'            => 26,
        'link_text'        => 'Découvrir la boutique',
        'link_url'         => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/boutique/' ),
        'bg_gradient_from' => '#e4c46a',
        'bg_gradient_to'   => '#9c7723',
        'text_color'       => '#0b0904',
        'is_active'        => 1,
    ] );
}

add_shortcode('nl_weekly_promos_carousel', 'nl_render_weekly_promos_carousel');

function nl_render_weekly_promos_carousel() {
    global $wpdb;
    $table  = $wpdb->prefix . 'nl_weekly_promos';
    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- nom de table statique du thème
    $promos = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC LIMIT 10" );

    ob_start();
    ?>
    <div class="nl-wpromo-block">
        <p class="nl-col-label">Nos coups de cœur</p>

        <?php if ( ! empty( $promos ) ) : ?>
        <div class="swiper nl-wpromo-swiper">
            <div class="swiper-wrapper">
                <?php foreach ( $promos as $promo ) : ?>
                <div class="swiper-slide">
                    <article class="nl-wpromo-card">
                        <div class="nl-wpromo-media">
                            <img src="<?php echo esc_url( $promo->image_url ); ?>" alt="<?php echo esc_attr( $promo->title ); ?>" loading="lazy">
                            <span class="nl-wpromo-flag"><?php echo nl_icon( 'flame' ); ?> Offre</span>
                        </div>
                        <div class="nl-wpromo-body">
                            <h3 class="nl-wpromo-title"><?php echo esc_html( $promo->title ); ?></h3>
                            <p class="nl-wpromo-desc"><?php echo esc_html( $promo->description ); ?></p>
                            <div class="nl-wpromo-foot">
                                <span class="nl-wpromo-price"><?php echo esc_html( $promo->price ); ?></span>
                                <a class="nl-wpromo-cta" href="<?php echo esc_url( $promo->link_url ); ?>">Découvrir <?php echo nl_icon( 'arrow-right' ); ?></a>
                            </div>
                        </div>
                    </article>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="nl-swiper-controls">
                <button type="button" class="nl-swiper-prev" aria-label="Promo précédente"><?php echo nl_icon( 'chevron-left' ); ?></button>
                <div class="swiper-pagination"></div>
                <button type="button" class="nl-swiper-next" aria-label="Promo suivante"><?php echo nl_icon( 'chevron-right' ); ?></button>
            </div>
        </div>
        <?php else : ?>
        <div class="nl-wpromo-empty">
            <span class="nl-wpromo-empty-icon"><?php echo nl_icon( 'flame' ); ?></span>
            <p>Nos offres de la semaine arrivent très bientôt. &#10022;</p>
        </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * ============================================
 * NL STORE — FOOTER LUXE  [nl_footer]
 * À placer dans le pied de page (Astra > Footer Builder
 * widget HTML/shortcode, ou bloc shortcode).
 * ============================================
 */
add_shortcode( 'nl_footer', 'nl_render_footer' );

function nl_render_footer( $content = '' ) {
    $shop_url    = class_exists( 'WooCommerce' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/boutique/' );
    $account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/mon-compte/' );
    $cart_url    = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/panier/' );
    $contact_url = get_permalink( get_page_by_path( 'contact' ) ) ?: home_url( '/contact/' );

    // Coordonnées société (officielles INSEE/SIRENE — voir nl_company_info())
    $info = nl_company_info();

    // Normalisation FR (0X… → indicatif +33) pour les liens tel: et WhatsApp
    $tel_digits = preg_replace( '/\D+/', '', $info['phone'] );
    $tel_intl   = ( 0 === strpos( (string) $tel_digits, '0' ) ) ? '+33' . substr( $tel_digits, 1 ) : ( $tel_digits ? '+' . $tel_digits : '' );
    $wa_digits  = preg_replace( '/\D+/', '', $info['whatsapp'] );
    if ( 0 === strpos( (string) $wa_digits, '0' ) ) {
        $wa_digits = '33' . substr( $wa_digits, 1 );
    }

    $map_src = 'https://maps.google.com/maps?q=' . rawurlencode( $info['map_query'] ) . '&z=15&output=embed';

    ob_start(); ?>
    <div class="nl-footer-luxury">
        <div class="nl-footer-bg"></div>
        <div class="nl-footer-content">
            <div class="nl-footer-grid">

                <div class="nl-footer-brand">
                    <h2><?php echo esc_html( $info['name'] ); ?></h2>
                    <p><?php echo esc_html( $info['baseline'] ); ?></p>
                    <?php if ( nl_map_enabled() ) : ?>
                        <div class="nl-footer-map__frame">
                            <?php echo nl_map_div( 14 ); // carte sombre, au-dessus de l'adresse ?>
                        </div>
                        <a class="nl-footer-map__link" href="<?php echo esc_url( 'https://maps.google.com/maps?q=' . rawurlencode( $info['map_query'] ) ); ?>" target="_blank" rel="noopener"><?php echo nl_icon( 'map-pin' ); ?> Itinéraire</a>
                    <?php endif; ?>
                    <?php if ( $info['address'] ) : ?>
                        <p class="nl-footer-meta"><?php echo nl_icon( 'map-pin' ); ?> <?php echo esc_html( $info['address'] ); ?></p>
                    <?php endif; ?>
                    <?php if ( $info['phone'] ) : ?>
                        <p class="nl-footer-meta"><?php echo nl_icon( 'phone' ); ?> <a href="tel:<?php echo esc_attr( $tel_intl ); ?>"><?php echo esc_html( $info['phone'] ); ?></a></p>
                    <?php endif; ?>
                    <?php if ( $info['email'] ) : ?>
                        <p class="nl-footer-meta"><?php echo nl_icon( 'mail' ); ?> <a href="mailto:<?php echo esc_attr( $info['email'] ); ?>"><?php echo esc_html( $info['email'] ); ?></a></p>
                    <?php endif; ?>
                    <div class="nl-socials">
                        <?php if ( $info['whatsapp'] ) : ?>
                            <a href="https://wa.me/<?php echo esc_attr( $wa_digits ); ?>" aria-label="WhatsApp" target="_blank" rel="noopener"><?php echo nl_icon( 'message-circle' ); ?></a>
                        <?php endif; ?>
                        <?php if ( $info['instagram'] && '#' !== $info['instagram'] ) : ?>
                            <a href="<?php echo esc_url( $info['instagram'] ); ?>" aria-label="Instagram" target="_blank" rel="noopener"><?php echo nl_icon( 'instagram' ); ?></a>
                        <?php endif; ?>
                        <?php if ( $info['facebook'] && '#' !== $info['facebook'] ) : ?>
                            <a href="<?php echo esc_url( $info['facebook'] ); ?>" aria-label="Facebook" target="_blank" rel="noopener"><?php echo nl_icon( 'facebook' ); ?></a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="nl-footer-col">
                    <h3>Boutique</h3>
                    <ul>
                        <li><a href="<?php echo esc_url( $shop_url ); ?>">Tous les produits</a></li>
                        <li><a href="<?php echo esc_url( $shop_url ); ?>?product_cat=parfums">Parfums</a></li>
                        <li><a href="<?php echo esc_url( $shop_url ); ?>?product_cat=bebe">Bébé</a></li>
                        <li><a href="<?php echo esc_url( $shop_url ); ?>?product_cat=hygiene">Hygiène</a></li>
                    </ul>
                </div>

                <div class="nl-footer-col">
                    <h3>Aide &amp; Compte</h3>
                    <ul>
                        <li><a href="<?php echo esc_url( $account_url ); ?>">Mon compte</a></li>
                        <li><a href="<?php echo esc_url( $cart_url ); ?>">Mon panier</a></li>
                        <li><a href="<?php echo esc_url( $contact_url ); ?>">Contact</a></li>
                        <li><a href="<?php echo esc_url( get_permalink( get_page_by_path( 'faq' ) ) ?: home_url( '/faq/' ) ); ?>">FAQ</a></li>
                        <li><a href="<?php echo esc_url( get_permalink( get_page_by_path( 'support' ) ) ?: home_url( '/support/' ) ); ?>">Support &amp; Assistance</a></li>
                        <li><a href="<?php echo esc_url( get_permalink( get_page_by_path( 'cgv' ) ) ?: home_url( '/cgv/' ) ); ?>">CGV</a></li>
                    </ul>
                </div>

            </div>

            <div class="nl-footer-bottom">
                <p>© <?php echo esc_html( date_i18n( 'Y' ) ); ?> <?php echo esc_html( $info['name'] ); ?>. Tous droits réservés. · <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'cgv' ) ) ?: home_url( '/cgv/' ) ); ?>">CGV</a> · <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'politique-de-confidentialite' ) ) ?: home_url( '/politique-de-confidentialite/' ) ); ?>">Confidentialité</a> · <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'faq' ) ) ?: home_url( '/faq/' ) ); ?>">FAQ</a></p>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/* Affiche le footer luxe en pied de page, quelle que soit la config Astra.
   Le footer Astra par défaut est masqué en CSS (.site-footer). On garde aussi
   le filtre copyright comme repli si le Footer Builder n'est pas utilisé. */
add_action( 'wp_footer', 'nl_output_luxury_footer', 5 );
function nl_output_luxury_footer() {
    echo nl_render_footer(); // sortie déjà échappée dans la fonction
}
add_filter( 'astra_footer_copyright', '__return_empty_string', 99 );
/* ============================================================
   NL STORE — Coordonnées société (source unique)
   ============================================================ */
function nl_company_info() {
    return apply_filters( 'nl_company_info', [
        'name'      => 'NL Store',
        'legal'     => 'MADI ALI — Entrepreneur individuel',
        'baseline'  => 'Tout pour bébé, parfums et vêtements — pour Mayotte et ses alentours.',
        'address'   => 'Imp. de la Place Publique, Mroalé — 97680 Tsingoni, Mayotte',
        'phone'     => '07 66 53 38 47',
        'whatsapp'  => '07 66 53 38 47',
        'email'     => 'contact@nl.store.ghost-service.fr',
        'instagram' => '',
        'facebook'  => '',
        'siren'     => '812 234 094',
        'siret'     => '812 234 094 00017',
        'ape'       => '47.11B — Commerce d\'alimentation générale',
        'map_query' => 'Mroalé, 97680 Tsingoni, Mayotte',
        // Coordonnées GPS pour la carte sombre Leaflet (ajustables ici).
        'lat'       => -12.7847,
        'lng'       => 45.1100,
    ] );
}

/* ============================================================
   NL STORE — CARTE SOMBRE (Leaflet + tuiles CARTO Dark)
   Remplace l'iframe Google (et son bandeau imposé) par une carte
   sombre propre, sans clé API. Chargée uniquement là où une carte
   est affichée (accueil + page Contact) pour préserver la perf.
   ============================================================ */
function nl_map_enabled() {
    return ( is_front_page() || is_page_template( 'template-contact.php' ) || is_page( 'contact' ) );
}

// Markup d'une carte (initialisée en JS au scroll).
function nl_map_div( $zoom = null ) {
    $info = nl_company_info();
    $s    = nl_map_settings();
    $lat  = (float) $s['lat'];
    $lng  = (float) $s['lng'];
    $zoom = ( null === $zoom ) ? (int) $s['zoom'] : (int) $zoom;
    return sprintf(
        '<div class="nl-map" data-lat="%s" data-lng="%s" data-zoom="%d" role="img" aria-label="%s"></div>',
        esc_attr( $lat ),
        esc_attr( $lng ),
        (int) $zoom,
        esc_attr( 'Carte — ' . ( $info['address'] ?? '' ) )
    );
}

// Charge la bonne carte : Mapbox premium si un token est renseigné, sinon
// Leaflet + tuiles CARTO Dark (gratuit, sans clé).
add_action( 'wp_enqueue_scripts', 'nl_enqueue_map', 18 );
function nl_enqueue_map() {
    if ( is_admin() || ! nl_map_enabled() ) {
        return;
    }
    $s = nl_map_settings();

    if ( ! empty( $s['mapbox_token'] ) ) {
        // ----- Mapbox GL JS (premium) -----
        wp_enqueue_style( 'mapbox-gl', 'https://api.mapbox.com/mapbox-gl-js/v3.6.0/mapbox-gl.css', [], '3.6.0' );
        wp_enqueue_script( 'mapbox-gl', 'https://api.mapbox.com/mapbox-gl-js/v3.6.0/mapbox-gl.js', [], '3.6.0', true );
        wp_add_inline_script( 'mapbox-gl', 'window.NL_MAP=' . wp_json_encode( [
            'token' => $s['mapbox_token'],
            'style' => $s['style'] ?: 'mapbox://styles/mapbox/dark-v11',
        ] ) . ';', 'before' );

        $js = <<<'JS'
(function () {
  function build(el) {
    if (el.dataset.inited || typeof mapboxgl === 'undefined' || !window.NL_MAP) { return; }
    var lat = parseFloat(el.getAttribute('data-lat'));
    var lng = parseFloat(el.getAttribute('data-lng'));
    var z = parseInt(el.getAttribute('data-zoom') || '14', 10);
    if (isNaN(lat) || isNaN(lng)) { return; }
    el.dataset.inited = '1';
    mapboxgl.accessToken = NL_MAP.token;
    var map = new mapboxgl.Map({ container: el, style: NL_MAP.style, center: [lng, lat], zoom: z, cooperativeGestures: true });
    map.addControl(new mapboxgl.NavigationControl({ showCompass: false }), 'top-right');
    var pin = document.createElement('div'); pin.className = 'nl-map-pin'; pin.innerHTML = '<span></span>';
    new mapboxgl.Marker({ element: pin }).setLngLat([lng, lat]).addTo(map);
    map.on('load', function () {
      map.addSource('nl-zone', { type: 'geojson', data: { type: 'Feature', geometry: { type: 'Point', coordinates: [lng, lat] } } });
      map.addLayer({ id: 'nl-zone', type: 'circle', source: 'nl-zone', paint: {
        'circle-color': '#d4af37', 'circle-opacity': 0.08,
        'circle-stroke-color': '#d4af37', 'circle-stroke-width': 1.5, 'circle-stroke-opacity': 0.6,
        'circle-radius': ['interpolate', ['exponential', 2], ['zoom'], 10, 6, 16, 110]
      } });
    });
    setTimeout(function () { map.resize(); }, 220);
  }
  function init() {
    document.querySelectorAll('.nl-map').forEach(function (el) {
      if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (es) { es.forEach(function (e) { if (e.isIntersecting) { build(el); io.disconnect(); } }); }, { rootMargin: '250px' });
        io.observe(el);
      } else { build(el); }
    });
  }
  if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
})();
JS;
        wp_add_inline_script( 'mapbox-gl', $js );
        return;
    }

    // ----- Leaflet + CARTO Dark (gratuit) -----
    wp_enqueue_style( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4' );
    wp_enqueue_script( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true );

    $js = <<<'JS'
(function () {
  function build(el) {
    if (el.dataset.inited || typeof L === 'undefined') { return; }
    var lat = parseFloat(el.getAttribute('data-lat'));
    var lng = parseFloat(el.getAttribute('data-lng'));
    var z = parseInt(el.getAttribute('data-zoom') || '14', 10);
    if (isNaN(lat) || isNaN(lng)) { return; }
    el.dataset.inited = '1';
    var map = L.map(el, { scrollWheelZoom: false }).setView([lat, lng], z);
    map.attributionControl.setPrefix(''); // retire le « Leaflet » + drapeau (on garde OSM/CARTO, requis)
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
      subdomains: 'abcd', maxZoom: 20,
      attribution: '&copy; OpenStreetMap, &copy; CARTO'
    }).addTo(map);
    L.circle([lat, lng], { radius: 420, color: '#e4c46a', weight: 1.5, dashArray: '2 8', fillColor: '#d4af37', fillOpacity: 0.025 }).addTo(map);
    var icon = L.divIcon({ className: 'nl-map-pin', html: '<span></span>', iconSize: [20, 20], iconAnchor: [10, 10] });
    L.marker([lat, lng], { icon: icon }).addTo(map);
    setTimeout(function () { map.invalidateSize(); }, 220);
  }
  function init() {
    document.querySelectorAll('.nl-map').forEach(function (el) {
      if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (es) { es.forEach(function (e) { if (e.isIntersecting) { build(el); io.disconnect(); } }); }, { rootMargin: '250px' });
        io.observe(el);
      } else { build(el); }
    });
  }
  if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
})();
JS;
    wp_add_inline_script( 'leaflet', $js );
}

/* ============================================================
   NL STORE — PAGES FAQ / CGV / SUPPORT (auto-créées, one-shot)
   Pages publiées avec contenu par défaut + gabarit « NL Store — Page ».
   Idempotent (verrou d'option). Modifiables ensuite dans WP > Pages.
   ============================================================ */
add_action( 'admin_init', 'nl_seed_legal_pages', 27 );
function nl_seed_legal_pages() {
    if ( get_option( 'nl_legal_pages_seeded' ) === 'v2' ) {
        return;
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $info  = function_exists( 'nl_company_info' ) ? nl_company_info() : [];
    $email = $info['email'] ?? '';
    $phone = $info['phone'] ?? '';
    $wa    = preg_replace( '/\D+/', '', $info['whatsapp'] ?? '' );
    if ( 0 === strpos( (string) $wa, '0' ) ) {
        $wa = '33' . substr( $wa, 1 );
    }
    $addr   = $info['address'] ?? '';
    $legal  = $info['legal'] ?? '';
    $siren  = $info['siren'] ?? '';
    $siret  = $info['siret'] ?? '';
    $ape    = $info['ape'] ?? '';
    $name   = $info['name'] ?? 'NL Store';
    $contact_url = ( $p = get_page_by_path( 'contact' ) ) ? get_permalink( $p ) : home_url( '/contact/' );

    // ---- FAQ ----
    $faq = <<<HTML
<div class="nl-faq">
<details open><summary>Livrez-vous partout à Mayotte ?</summary><p>Oui, nous livrons sur l'ensemble de Mayotte, ainsi que dans ses alentours. Les modalités précises sont confirmées au moment de la commande selon votre localisation.</p></details>
<details><summary>Quels sont les délais de livraison ?</summary><p>En général, votre commande est préparée sous 24 à 48h, puis livrée rapidement selon la zone. Vous êtes tenu informé à chaque étape.</p></details>
<details><summary>Quels moyens de paiement acceptez-vous ?</summary><p>Le paiement s'effectue en ligne de manière 100% sécurisée. Les moyens disponibles s'affichent au moment du règlement.</p></details>
<details><summary>Mes paiements sont-ils sécurisés ?</summary><p>Oui. Les transactions sont chiffrées et traitées par des prestataires de paiement certifiés. Nous n'avons jamais accès à vos données bancaires.</p></details>
<details><summary>Puis-je retourner un produit ?</summary><p>Vous disposez d'un droit de rétractation de 14 jours pour les produits éligibles. Certains articles (hygiène, produits descellés) en sont exclus pour des raisons sanitaires. Voir nos <a href="/cgv/">CGV</a>.</p></details>
<details><summary>Comment suivre ma commande ?</summary><p>Connectez-vous à votre espace <a href="/mon-compte/">Mon compte</a> pour suivre l'état de votre commande, ou contactez-nous directement.</p></details>
<details><summary>Comment vous contacter ?</summary><p>Par téléphone au {$phone}, sur WhatsApp, par e-mail à {$email}, ou via notre page <a href="{$contact_url}">Contact</a>.</p></details>
</div>
HTML;

    // ---- Support & Assistance ----
    $support = <<<HTML
<p class="nl-lead">Une question, un souci avec une commande, un conseil produit ? Notre équipe vous répond rapidement.</p>
<h2>Nous contacter</h2>
<ul>
<li><strong>Téléphone :</strong> {$phone}</li>
<li><strong>WhatsApp :</strong> <a href="https://wa.me/{$wa}" target="_blank" rel="noopener">Démarrer une discussion</a></li>
<li><strong>E-mail :</strong> <a href="mailto:{$email}">{$email}</a></li>
<li><strong>Formulaire :</strong> <a href="{$contact_url}">Page Contact</a></li>
</ul>
<h2>Horaires</h2>
<p>Du lundi au samedi, de 8h à 18h (heure de Mayotte). Nous répondons aux messages dans les meilleurs délais.</p>
<h2>Questions fréquentes</h2>
<p>La plupart des réponses se trouvent dans notre <a href="/faq/">FAQ</a> (livraison, paiement, retours, suivi de commande).</p>
<h2>Suivi de commande</h2>
<p>Retrouvez l'état de vos commandes dans votre espace <a href="/mon-compte/">Mon compte</a>.</p>
HTML;

    // ---- CGV ----
    $cgv = <<<HTML
<p class="nl-lead">Les présentes conditions générales de vente (CGV) régissent les ventes réalisées sur le site {$name}.</p>
<h2>Article 1 — Identification du vendeur</h2>
<p>{$name} — {$legal}.<br>Adresse : {$addr}.<br>SIREN : {$siren} · SIRET : {$siret} · Code APE : {$ape}.<br>E-mail : {$email} · Téléphone : {$phone}.</p>
<h2>Article 2 — Objet</h2>
<p>Les présentes CGV définissent les droits et obligations des parties dans le cadre de la vente en ligne des produits proposés par {$name}.</p>
<h2>Article 3 — Produits</h2>
<p>Les produits proposés sont décrits avec la plus grande exactitude. Les photographies sont les plus fidèles possibles mais ne sauraient engager le vendeur en cas de légère différence.</p>
<h2>Article 4 — Prix</h2>
<p>Les prix sont indiqués en euros, toutes taxes comprises, hors frais de livraison éventuels précisés avant validation de la commande. {$name} se réserve le droit de modifier ses prix à tout moment, les produits étant facturés au tarif en vigueur lors de la commande.</p>
<h2>Article 5 — Commande</h2>
<p>La validation de la commande implique l'acceptation pleine et entière des présentes CGV. Un e-mail de confirmation récapitule la commande.</p>
<h2>Article 6 — Paiement</h2>
<p>Le paiement s'effectue en ligne de manière sécurisée. La commande est traitée après confirmation du paiement.</p>
<h2>Article 7 — Livraison</h2>
<p>Les produits sont livrés à Mayotte et dans ses alentours, à l'adresse indiquée lors de la commande. Les délais sont communiqués à titre indicatif.</p>
<h2>Article 8 — Droit de rétractation</h2>
<p>Conformément à la loi, vous disposez d'un délai de 14 jours à compter de la réception pour exercer votre droit de rétractation, sans avoir à motiver votre décision. Sont exclus notamment les produits d'hygiène descellés et les produits personnalisés ou périssables.</p>
<h2>Article 9 — Garanties légales</h2>
<p>Tous les produits bénéficient de la garantie légale de conformité et de la garantie contre les vices cachés, dans les conditions prévues par la loi.</p>
<h2>Article 10 — Données personnelles</h2>
<p>Les données collectées sont nécessaires au traitement des commandes. Conformément au RGPD, vous disposez d'un droit d'accès, de rectification et de suppression en nous écrivant à {$email}.</p>
<h2>Article 11 — Service client</h2>
<p>Pour toute question, contactez-nous à {$email} ou au {$phone}, ou via notre page <a href="{$contact_url}">Contact</a>.</p>
<h2>Article 12 — Droit applicable et litiges</h2>
<p>Les présentes CGV sont soumises au droit français. En cas de litige, une solution amiable sera recherchée avant toute action judiciaire. Le consommateur peut recourir à un médiateur de la consommation.</p>
HTML;

    // ---- Politique de confidentialité (RGPD) ----
    $privacy = <<<HTML
<p class="nl-lead">La présente politique décrit comment {$name} collecte, utilise et protège vos données personnelles.</p>
<h2>Responsable du traitement</h2>
<p>{$name} — {$legal}.<br>Adresse : {$addr}.<br>Contact : {$email} · {$phone}.</p>
<h2>Données collectées</h2>
<p>Nous collectons les données que vous nous fournissez (nom, adresse, e-mail, téléphone, informations de commande) ainsi que des données de navigation (cookies, adresse IP, pages consultées).</p>
<h2>Finalités</h2>
<ul>
<li>Traitement et suivi des commandes</li>
<li>Livraison et service après-vente</li>
<li>Réponses à vos messages</li>
<li>Envoi d'informations commerciales (avec votre accord)</li>
<li>Amélioration du site et mesure d'audience</li>
</ul>
<h2>Base légale</h2>
<p>L'exécution du contrat (commandes), votre consentement (cookies, communications) et notre intérêt légitime (sécurité, amélioration du service).</p>
<h2>Destinataires</h2>
<p>Vos données sont destinées à {$name} et à ses prestataires (paiement, livraison, hébergement), tenus à la confidentialité. Elles ne sont jamais vendues à des tiers.</p>
<h2>Durée de conservation</h2>
<p>Les données sont conservées le temps nécessaire aux finalités, puis archivées ou supprimées conformément aux obligations légales.</p>
<h2>Cookies</h2>
<p>Le site utilise des cookies nécessaires à son fonctionnement et, avec votre consentement, des cookies de mesure d'audience. Vous pouvez accepter ou refuser ces derniers via le bandeau de cookies, et modifier votre choix à tout moment.</p>
<h2>Vos droits</h2>
<p>Conformément au RGPD, vous disposez d'un droit d'accès, de rectification, d'effacement, d'opposition, de limitation et de portabilité de vos données. Pour les exercer, écrivez-nous à {$email}. Vous pouvez également introduire une réclamation auprès de la CNIL (www.cnil.fr).</p>
<h2>Contact</h2>
<p>Pour toute question relative à vos données personnelles : {$email}.</p>
HTML;

    $pages = [
        'faq'                        => [ 'title' => 'FAQ', 'content' => $faq ],
        'support'                    => [ 'title' => 'Support & Assistance', 'content' => $support ],
        'cgv'                        => [ 'title' => 'Conditions Générales de Vente', 'content' => $cgv ],
        'politique-de-confidentialite' => [ 'title' => 'Politique de confidentialité', 'content' => $privacy ],
    ];

    foreach ( $pages as $slug => $data ) {
        $existing = get_page_by_path( $slug );
        if ( $existing ) {
            update_post_meta( $existing->ID, '_wp_page_template', 'template-page-luxe.php' );
            continue;
        }
        $page_id = wp_insert_post( [
            'post_title'   => $data['title'],
            'post_name'    => $slug,
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => $data['content'],
        ] );
        if ( $page_id && ! is_wp_error( $page_id ) ) {
            update_post_meta( $page_id, '_wp_page_template', 'template-page-luxe.php' );
        }
    }

    update_option( 'nl_legal_pages_seeded', 'v2' );
}

/* ============================================================
   NL STORE — Catégories produit (one-shot) + images de catégorie
   Crée Parfums / Bébé / Vêtements / Hygiène et leur affecte les
   visuels du thème (assets/imgs/cat-*). Idempotent, auto-désactivé.
   ============================================================ */
add_action( 'admin_init', 'nl_seed_categories', 15 );
function nl_seed_categories() {
    if ( get_option( 'nl_categories_seeded' ) === 'v1' ) {
        return;
    }
    if ( ! current_user_can( 'manage_woocommerce' ) || ! taxonomy_exists( 'product_cat' ) ) {
        return;
    }

    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $dir  = trailingslashit( get_stylesheet_directory() ) . 'assets/imgs/';
    $cats = [
        'parfums'   => [ 'Parfums',   'cat-parfums.jpeg' ],
        'bebe'      => [ 'Bébé',      'cat-bebe.jpeg' ],
        'vetements' => [ 'Vêtements', 'cat-vetements.jpeg' ],
        'hygiene'   => [ 'Hygiène',   'cat-hygiene.jpg' ],
    ];

    $pending = 0;
    foreach ( $cats as $slug => $c ) {
        list( $name, $file ) = $c;
        $term = term_exists( $slug, 'product_cat' );
        if ( ! $term ) {
            $term = wp_insert_term( $name, 'product_cat', [ 'slug' => $slug ] );
        }
        if ( is_wp_error( $term ) ) {
            $pending++;
            continue;
        }
        $term_id = (int) ( is_array( $term ) ? $term['term_id'] : $term );
        if ( get_term_meta( $term_id, 'thumbnail_id', true ) ) {
            continue; // image déjà présente
        }
        $path = $dir . $file;
        if ( ! file_exists( $path ) ) {
            continue;
        }
        $upload = wp_upload_bits( $file, null, file_get_contents( $path ) );
        if ( ! empty( $upload['error'] ) ) {
            $pending++;
            continue;
        }
        $ft  = wp_check_filetype( $upload['file'], null );
        $att = wp_insert_attachment( [
            'post_mime_type' => $ft['type'],
            'post_title'     => $name,
            'post_status'    => 'inherit',
        ], $upload['file'] );
        if ( is_wp_error( $att ) || ! $att ) {
            $pending++;
            continue;
        }
        wp_update_attachment_metadata( $att, wp_generate_attachment_metadata( $att, $upload['file'] ) );
        update_term_meta( $term_id, 'thumbnail_id', $att );
    }

    if ( 0 === $pending ) {
        update_option( 'nl_categories_seeded', 'v1' );
    }
}

/* ============================================================
   NL STORE — Page Contact auto (one-shot)
   Crée une page « Contact » utilisant template-contact.php.
   ============================================================ */
add_action( 'admin_init', 'nl_seed_contact_page', 25 );
function nl_seed_contact_page() {
    if ( get_option( 'nl_contact_page_seeded' ) === 'v1' ) {
        return;
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $existing = get_page_by_path( 'contact' );
    if ( $existing ) {
        update_post_meta( $existing->ID, '_wp_page_template', 'template-contact.php' );
        update_option( 'nl_contact_page_seeded', 'v1' );
        return;
    }
    $pid = wp_insert_post( [
        'post_title'   => 'Contact',
        'post_name'    => 'contact',
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_content' => '',
    ] );
    if ( $pid && ! is_wp_error( $pid ) ) {
        update_post_meta( $pid, '_wp_page_template', 'template-contact.php' );
        update_option( 'nl_contact_page_seeded', 'v1' );
    }
}

/* ============================================================
   NL STORE — Traitement du formulaire de contact
   Nonce + honeypot + sanitisation + wp_mail. Retourne un statut.
   ============================================================ */
function nl_handle_contact_form() {
    if ( empty( $_POST['nl_contact_submit'] ) ) {
        return null;
    }
    if ( ! isset( $_POST['nl_contact_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nl_contact_nonce'] ) ), 'nl_contact' ) ) {
        return [ 'ok' => false, 'msg' => 'Session expirée, merci de réessayer.' ];
    }
    if ( ! empty( $_POST['nl_website'] ) ) { // pot de miel anti-bot
        return [ 'ok' => true, 'msg' => 'Merci, votre message a bien été envoyé.' ];
    }
    $name    = sanitize_text_field( wp_unslash( $_POST['nl_name'] ?? '' ) );
    $email   = sanitize_email( wp_unslash( $_POST['nl_email'] ?? '' ) );
    $message = sanitize_textarea_field( wp_unslash( $_POST['nl_message'] ?? '' ) );
    if ( '' === $name || ! is_email( $email ) || '' === $message ) {
        return [ 'ok' => false, 'msg' => 'Merci de renseigner un nom, un e-mail valide et un message.' ];
    }
    $info    = nl_company_info();
    $to      = $info['email'] ?: get_option( 'admin_email' );
    $subject = '[NL Store] Nouveau message de ' . $name;
    $body    = "Nom : {$name}\nE-mail : {$email}\n\nMessage :\n{$message}";
    $headers = [ 'Reply-To: ' . $name . ' <' . $email . '>' ];
    $sent    = wp_mail( $to, $subject, $body, $headers );
    return $sent
        ? [ 'ok' => true, 'msg' => 'Merci ' . $name . ', votre message a bien été envoyé. Nous vous répondrons rapidement.' ]
        : [ 'ok' => false, 'msg' => "Une erreur est survenue à l'envoi. Réessayez, ou écrivez-nous directement par e-mail." ];
}
