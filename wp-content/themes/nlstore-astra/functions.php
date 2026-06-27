<?php
/**
 * NLStore Astra — Child Theme
 */

define( 'CHILD_THEME_NLSTORE_ASTRA_VERSION', '3.0.0' );

/* ----------------------------------------------------------
   STYLES & FONTS
---------------------------------------------------------- */
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
        CHILD_THEME_NLSTORE_ASTRA_VERSION,
        'all'
    );

    // WooCommerce dedicated stylesheet
    if ( class_exists( 'WooCommerce' ) ) {
        wp_enqueue_style(
            'nlstore-woocommerce-css',
            get_stylesheet_directory_uri() . '/woocommerce.css',
            [ 'nlstore-astra-theme-css' ],
            CHILD_THEME_NLSTORE_ASTRA_VERSION,
            'all'
        );
    }
}
add_action( 'wp_enqueue_scripts', 'nl_enqueue_assets', 15 );

/* ----------------------------------------------------------
   LUCIDE ICONS + SCROLL REVEAL
   Icônes : https://lucide.dev — usage <i data-lucide="truck"></i>
---------------------------------------------------------- */
function nl_enqueue_interactions() {
    // Lucide icon library (rendu via data-lucide + lucide.createIcons())
    // Version épinglée (stabilité + cache-busting WP). MàJ volontaire uniquement.
    wp_enqueue_script(
        'lucide-icons',
        'https://unpkg.com/lucide@1.21.0/dist/umd/lucide.min.js',
        [],
        '1.21.0',
        true
    );

    $js = <<<'JS'
(function () {
  function nlRenderIcons() {
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
      window.lucide.createIcons();
    }
  }
  // Reveal au scroll
  function nlReveal() {
    var els = document.querySelectorAll('.nl-reveal');
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
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { nlRenderIcons(); nlReveal(); });
  } else {
    nlRenderIcons(); nlReveal();
  }
  // Re-rend les icônes injectées après coup (mini-cart, AJAX…)
  document.addEventListener('nl:icons', nlRenderIcons);
})();
JS;
    wp_add_inline_script( 'lucide-icons', $js );
}
add_action( 'wp_enqueue_scripts', 'nl_enqueue_interactions', 20 );

/* Évite le flash de mise en page : dimensionne les icônes Lucide */
function nl_icons_inline_css() {
    echo '<style id="nl-icons-css">'
        . '[data-lucide]{width:1em;height:1em;display:inline-block;vertical-align:-0.125em;stroke-width:2;}'
        . '.nl-hero-trust [data-lucide]{width:15px;height:15px;}'
        . '.nl-socials [data-lucide]{width:18px;height:18px;}'
        . '</style>';
}
add_action( 'wp_head', 'nl_icons_inline_css', 99 );

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

    ob_start();
    ?>
    <section class="nl-testimonials-carousel-wrapper" style="background-color: #0b0b0b; padding: 80px 40px;">
        <h2 class="nl-testimonials-title" style="font-size: 36px; font-family: 'Cormorant Garamond', Georgia, serif; font-weight: 700; color: #f8f5ef; text-align: center; margin: 0 0 60px 0; letter-spacing: -0.5px;">
            <?php echo esc_html($atts['title']); ?>
        </h2>

        <div class="swiper nl-testimonials-swiper" style="position: relative; width: 100%; max-width: 900px; margin: 0 auto; padding: 0 40px;">
            <div class="swiper-wrapper nl-testimonials-swiper-wrapper">
                <?php
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

                if ($reviews && count($reviews) > 0) {
                    foreach ($reviews as $review) {
                        $rating = intval($review->rating) ?: 5;
                        $stars = '';
                        for ($i = 1; $i <= 5; $i++) {
                            $stars .= ($i <= $rating) ? '★' : '☆';
                        }
                        ?>
                        <div class="swiper-slide nl-testimonials-slide" style="width: 100%; height: auto; display: flex; align-items: stretch;">
                            <div class="nl-testimonial-card" style="background-color: #050505; border: 1px solid rgba(212, 175, 55, 0.15); border-radius: 8px; padding: 30px; width: 100%; display: flex; flex-direction: column; justify-content: space-between; box-sizing: border-box; transition: all 0.3s ease;">
                                <div class="nl-testimonial-rating" style="font-size: 16px; color: #d4af37; letter-spacing: 2px; margin: 0 0 15px 0; display: flex; gap: 4px;">
                                    <?php echo esc_html( $stars ); ?>
                                </div>
                                <p class="nl-testimonial-text" style="font-size: 13px; color: #b8b8b8; font-style: italic; line-height: 1.8; margin: 0 0 15px 0; flex-grow: 1;">
                                    <?php echo wp_kses_post($review->comment_content); ?>
                                </p>
                                <p class="nl-testimonial-author" style="font-size: 12px; color: #f8f5ef; font-weight: 700; margin: 0;">
                                    <?php echo esc_html($review->comment_author ?: 'Anonymous'); ?>
                                </p>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<p style="text-align: center; color: #999; width: 100%; padding: 40px;">Aucun avis trouvé pour le moment.</p>';
                }
                ?>
            </div>

            <div class="swiper-pagination nl-testimonials-pagination" style="display: flex; justify-content: center; gap: 8px; margin-top: 30px; padding: 0 !important;"></div>

            <div class="swiper-button-prev nl-testimonials-prev" style="position: absolute; top: 50%; transform: translateY(-50%); left: 0; width: 44px; height: 44px; background-color: rgba(212, 175, 55, 0.1); border: 1px solid rgba(212, 175, 55, 0.3); border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #d4af37; transition: all 0.3s ease; z-index: 10; font-size: 20px; font-weight: bold;">‹</div>
            <div class="swiper-button-next nl-testimonials-next" style="position: absolute; top: 50%; transform: translateY(-50%); right: 0; width: 44px; height: 44px; background-color: rgba(212, 175, 55, 0.1); border: 1px solid rgba(212, 175, 55, 0.3); border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #d4af37; transition: all 0.3s ease; z-index: 10; font-size: 20px; font-weight: bold;">›</div>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

add_shortcode('nl_testimonials_carousel', 'nl_testimonials_carousel_shortcode');

// Enqueue sur wp_enqueue_scripts pour que le CSS Swiper atterrisse bien dans le <head>.
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_script('swiper-js', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', [], '11.0.0', true);
    wp_enqueue_style('swiper-css', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', [], '11.0.0');
    
    $css = '
    .nl-testimonials-carousel-wrapper {
        background-color: #0b0b0b;
        padding: 80px 40px;
        position: relative;
    }
    .nl-testimonials-title {
        font-size: 36px;
        font-family: \'Cormorant Garamond\', Georgia, serif;
        font-weight: 700;
        color: #f8f5ef;
        text-align: center;
        margin: 0 0 60px 0;
        letter-spacing: -0.5px;
    }
    .nl-testimonials-swiper {
        position: relative;
        width: 100%;
        max-width: 900px;
        margin: 0 auto;
        padding: 0 40px;
    }
    .nl-testimonials-swiper-wrapper {
        display: flex;
        gap: 20px;
    }
    .nl-testimonial-card:hover {
        border-color: rgba(212, 175, 55, 0.3) !important;
        box-shadow: 0 8px 24px rgba(212, 175, 55, 0.1);
    }
    .nl-testimonials-pagination {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-top: 30px;
        padding: 0 !important;
    }
    .nl-testimonials-pagination .swiper-pagination-bullet {
        width: 10px;
        height: 10px;
        background-color: rgba(212, 175, 55, 0.3);
        border-radius: 50%;
        cursor: pointer;
        transition: all 0.3s ease;
        border: 1px solid rgba(212, 175, 55, 0.2);
    }
    .nl-testimonials-pagination .swiper-pagination-bullet-active {
        background-color: #d4af37;
        border-color: #d4af37;
        box-shadow: 0 0 10px rgba(212, 175, 55, 0.4);
    }
    .swiper-wrapper {
        display: flex;
    }
    .swiper-slide {
        flex-shrink: 0;
        width: 100%;
        height: 100%;
        position: relative;
    }
    @media (max-width: 768px) {
        .nl-testimonials-carousel-wrapper {
            padding: 60px 20px;
        }
        .nl-testimonials-title {
            font-size: 28px;
            margin-bottom: 40px;
        }
        .nl-testimonials-swiper {
            padding: 0 30px;
        }
        .nl-testimonial-card {
            padding: 25px !important;
        }
    }
    @media (max-width: 480px) {
        .nl-testimonials-carousel-wrapper {
            padding: 40px 15px;
        }
        .nl-testimonials-title {
            font-size: 24px;
            margin-bottom: 30px;
        }
        .nl-testimonials-swiper {
            padding: 0;
        }
        .nl-testimonial-card {
            padding: 20px !important;
        }
        .nl-testimonial-text {
            font-size: 12px !important;
            line-height: 1.6 !important;
        }
        .nl-testimonials-prev,
        .nl-testimonials-next {
            display: none !important;
        }
        .nl-testimonials-pagination {
            margin-top: 20px;
            gap: 6px;
        }
        .nl-testimonials-pagination .swiper-pagination-bullet {
            width: 8px;
            height: 8px;
        }
    }
    ';
    
    wp_add_inline_style('swiper-css', $css);
    
    $js = '
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof Swiper !== "undefined") {
            const wrapper = document.querySelector(".nl-testimonials-carousel-wrapper");
            if (wrapper) {
                const swiperElement = wrapper.querySelector(".nl-testimonials-swiper");
                new Swiper(swiperElement, {
                    slidesPerView: 1,
                    spaceBetween: 20,
                    loop: true,
                    grabCursor: true,
                    autoplay: {
                        delay: 5000,
                        disableOnInteraction: false,
                        pauseOnMouseEnter: true,
                    },
                    pagination: {
                        el: wrapper.querySelector(".nl-testimonials-pagination"),
                        type: "bullets",
                        clickable: true,
                    },
                    navigation: {
                        nextEl: wrapper.querySelector(".nl-testimonials-next"),
                        prevEl: wrapper.querySelector(".nl-testimonials-prev"),
                    },
                    breakpoints: {
                        480: { slidesPerView: 1 },
                        768: { slidesPerView: 1 },
                    },
                });
            }
        }
    });
    ';
    
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
}

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
        $banner_data = array(
            'text' => sanitize_text_field($_POST['banner_text'] ?? ''),
            'link_text' => sanitize_text_field($_POST['banner_link_text'] ?? ''),
            'link_url' => esc_url_raw($_POST['banner_link_url'] ?? ''),
            'bg_gradient_from' => sanitize_hex_color($_POST['bg_gradient_from'] ?? '#d4af37'),
            'bg_gradient_to' => sanitize_hex_color($_POST['bg_gradient_to'] ?? '#c9a22e'),
            'text_color' => sanitize_hex_color($_POST['text_color'] ?? '#050505'),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        );
        
        update_option('nl_promo_banner', $banner_data);
        echo '<div class="notice notice-success"><p>✅ Bannière mise à jour!</p></div>';
    }
    
    $banner = get_option('nl_promo_banner', array(
        'text' => 'Offre limitée - Pack Bébé à 29,90€',
        'link_text' => 'Commander maintenant',
        'link_url' => '/produit/pack-bebe-complet/',
        'bg_gradient_from' => '#d4af37',
        'bg_gradient_to' => '#c9a22e',
        'text_color' => '#050505',
        'is_active' => 1,
    ));
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
                    <th><label for="banner_text">Texte *</label></th>
                    <td>
                        <input type="text" id="banner_text" name="banner_text" value="<?php echo esc_attr($banner['text'] ?? ''); ?>" class="large-text" required>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="banner_link_text">Texte du lien *</label></th>
                    <td>
                        <input type="text" id="banner_link_text" name="banner_link_text" value="<?php echo esc_attr($banner['link_text'] ?? ''); ?>" class="large-text" required>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="banner_link_url">URL du lien *</label></th>
                    <td>
                        <input type="url" id="banner_link_url" name="banner_link_url" value="<?php echo esc_attr($banner['link_url'] ?? ''); ?>" class="large-text" required>
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
    
    nl_create_promotions_table();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('nl_weekly_promos_nonce')) {
        if (isset($_POST['action']) && $_POST['action'] === 'add_promo') {
            $promo_data = array(
                'title' => sanitize_text_field($_POST['promo_title']),
                'description' => sanitize_textarea_field($_POST['promo_description']),
                'price' => sanitize_text_field($_POST['promo_price']),
                'image_url' => esc_url_raw($_POST['promo_image']),
                'link_url' => esc_url_raw($_POST['promo_link']),
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
    
    $promos = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");
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
    $banner = get_option('nl_promo_banner');
    
    if (!$banner || !$banner['is_active']) {
        return '';
    }
    
    $gradient = sprintf(
        'linear-gradient(90deg, %s 0%%, %s 100%%)',
        esc_attr($banner['bg_gradient_from']),
        esc_attr($banner['bg_gradient_to'])
    );
    
    ob_start();
    ?>
    <div style="background: <?php echo esc_attr( $gradient ); ?>; padding: 25px 40px; text-align: center;">
        <p style="color: <?php echo esc_attr($banner['text_color']); ?>; text-transform: uppercase; font-size: 12px; letter-spacing: 1px; margin: 0; font-weight: bold;">
            <?php echo esc_html($banner['text']); ?> - 
            <a href="<?php echo esc_url($banner['link_url']); ?>" style="color: <?php echo esc_attr($banner['text_color']); ?>; text-decoration: underline;">
                <?php echo esc_html($banner['link_text']); ?>
            </a>
        </p>
    </div>
    <?php
    return ob_get_clean();
}

add_shortcode('nl_weekly_promos_carousel', 'nl_render_weekly_promos_carousel');

function nl_render_weekly_promos_carousel() {
    global $wpdb;
    $table = $wpdb->prefix . 'nl_weekly_promos';
    $promos = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC LIMIT 10");
    
    if (empty($promos)) {
        return '<p style="text-align: center; color: #999;">Aucune promotion disponible</p>';
    }
    
    wp_enqueue_script('swiper-js', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', [], '11.0.0', true);
    wp_enqueue_style('swiper-css', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', [], '11.0.0');
    
    ob_start();
    ?>
    <section style="background-color: #0b0b0b; padding: 30px; position: relative;">
        
        <div style="text-align: center; margin-bottom: 30px;">
            <span style="display: inline-block; padding: 8px 16px; border-radius: 20px; background: rgba(212,175,55,0.1); border: 1px solid rgba(212,175,55,0.25); color: #d4af37; font-size: 11px; font-weight: bold; letter-spacing: 2px; text-transform: uppercase;">PROMOTIONS DE LA SEMAINE</span>
            <h2 style="font-size: 36px !important; font-family: 'Cormorant Garamond', Georgia, serif; margin: 60px 0 60px 0 !important; letter-spacing: -0.5px;">Nos Coups de Cœur</h2>
        </div>
        
        <div class="swiper nl-promos-swiper" style="position: relative; width: 100%; max-width: 1000px; margin: 0 auto; padding: 0 40px;">
            <div class="swiper-wrapper">
                <?php foreach ($promos as $promo): ?>
                <div class="swiper-slide" style="height: auto;">
                    <div style="background: linear-gradient(135deg, rgba(255,255,255,0.03), transparent 20%), linear-gradient(180deg, rgba(25,25,25,0.95), rgba(10,10,10,0.98)); border: 1.5px solid rgba(212,175,55,0.22); border-radius: 16px; overflow: hidden; box-shadow: 0 16px 36px rgba(0,0,0,0.32); transition: all 0.35s ease; display: flex; flex-direction: column;">
                        
                        <div style="height: 300px; overflow: hidden; border-radius: 14px 14px 0 0;">
                            <img src="<?php echo esc_url($promo->image_url); ?>" alt="<?php echo esc_attr($promo->title); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.35s ease;">
                        </div>
                        
                        <div style="padding: 25px; display: flex; flex-direction: column; flex-grow: 1;">
                            <h3 style="font-size: 24px; font-family: 'Cormorant Garamond', Georgia, serif; color: #f8f5ef; font-weight: 700; margin: 0 0 10px 0;">
                                <?php echo esc_html($promo->title); ?>
                            </h3>
                            
                            <p style="font-size: 13px; color: #b8b8b8; line-height: 1.8; margin: 0 0 15px 0; flex-grow: 1;">
                                <?php echo esc_html($promo->description); ?>
                            </p>
                            
                            <div style="font-size: 32px; font-weight: 700; color: #d4af37; margin: 15px 0;">
                                <?php echo esc_html($promo->price); ?>
                            </div>
                            
                            <a href="<?php echo esc_url($promo->link_url); ?>" style="background: linear-gradient(135deg, #e4c46a, #d4af37); color: #0a0a0a !important; border: none; border-radius: 12px; padding: 12px 24px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; font-size: 12px; box-shadow: 0 10px 26px rgba(212,175,55,0.25); transition: all 0.3s ease; text-decoration: none; display: inline-block; text-align: center;">
                                Découvrir
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div style="position: absolute; top: 50%; left: 0; transform: translateY(-50%); width: 44px; height: 44px; background: rgba(212,175,55,0.1); border: 1px solid rgba(212,175,55,0.3); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #d4af37; cursor: pointer; z-index: 10;" class="swiper-button-prev">‹</div>
            
            <div style="position: absolute; top: 50%; right: 0; transform: translateY(-50%); width: 44px; height: 44px; background: rgba(212,175,55,0.1); border: 1px solid rgba(212,175,55,0.3); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #d4af37; cursor: pointer; z-index: 10;" class="swiper-button-next">›</div>
            
            <div class="swiper-pagination" style="display: flex; justify-content: center; gap: 8px; margin-top: 30px; padding: 0 !important;"></div>
        </div>
    </section>
    
    <style>
        .nl-promos-swiper .swiper-slide:hover {
            transform: translateY(-8px);
        }
        .nl-promos-swiper .swiper-pagination-bullet {
            width: 10px;
            height: 10px;
            background-color: rgba(212,175,55,0.3);
            border-radius: 50%;
            border: 1px solid rgba(212,175,55,0.2);
        }
        .nl-promos-swiper .swiper-pagination-bullet-active {
            background-color: #d4af37;
            border-color: #d4af37;
            box-shadow: 0 0 10px rgba(212,175,55,0.4);
        }
        @media (max-width: 767px) {
            .nl-promos-swiper {
                padding: 0 !important;
            }
            .swiper-button-prev, .swiper-button-next {
                display: none !important;
            }
        }
    </style>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Swiper !== 'undefined') {
            new Swiper('.nl-promos-swiper', {
                slidesPerView: 1,
                spaceBetween: 20,
                loop: true,
                grabCursor: true,
                autoplay: {
                    delay: 5000,
                    disableOnInteraction: false,
                    pauseOnMouseEnter: true,
                },
                pagination: {
                    el: '.nl-promos-swiper .swiper-pagination',
                    type: 'bullets',
                    clickable: true,
                },
                navigation: {
                    nextEl: '.nl-promos-swiper .swiper-button-next',
                    prevEl: '.nl-promos-swiper .swiper-button-prev',
                },
                breakpoints: {
                    768: { slidesPerView: 2, spaceBetween: 24 },
                    1024: { slidesPerView: 3, spaceBetween: 24 },
                }
            });
        }
    });
    </script>
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

function nl_render_footer() {
    $shop_url    = class_exists( 'WooCommerce' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/boutique/' );
    $account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/mon-compte/' );
    $cart_url    = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/panier/' );
    $contact_url = get_permalink( get_page_by_path( 'contact' ) ) ?: home_url( '/contact/' );

    // Coordonnées société (configurables via filtre)
    $info = apply_filters( 'nl_company_info', [
        'name'     => 'NL Store',
        'baseline' => 'Tout pour bébé, parfums et vêtements — Exclusivement pour Mayotte.',
        'address'  => 'Imp. de la Place Publique, Mroalé, 97680 Tsingoni, Mayotte',
        'phone'    => '',
        'whatsapp' => '',
        'email'    => '',
        'instagram'=> '#',
        'facebook' => '#',
        'siret'    => '812 234 094 00017',
    ] );

    ob_start(); ?>
    <footer class="nl-footer-luxury">
        <div class="nl-footer-bg"></div>
        <div class="nl-footer-content">
            <div class="nl-footer-grid">

                <div class="nl-footer-brand">
                    <h2><?php echo esc_html( $info['name'] ); ?></h2>
                    <p><?php echo esc_html( $info['baseline'] ); ?></p>
                    <?php if ( $info['address'] ) : ?>
                        <p class="nl-footer-meta"><i data-lucide="map-pin"></i> <?php echo esc_html( $info['address'] ); ?></p>
                    <?php endif; ?>
                    <?php if ( $info['phone'] ) : ?>
                        <p class="nl-footer-meta"><i data-lucide="phone"></i> <a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $info['phone'] ) ); ?>"><?php echo esc_html( $info['phone'] ); ?></a></p>
                    <?php endif; ?>
                    <?php if ( $info['email'] ) : ?>
                        <p class="nl-footer-meta"><i data-lucide="mail"></i> <a href="mailto:<?php echo esc_attr( $info['email'] ); ?>"><?php echo esc_html( $info['email'] ); ?></a></p>
                    <?php endif; ?>
                    <div class="nl-socials">
                        <?php if ( $info['whatsapp'] ) : ?>
                            <a href="https://wa.me/<?php echo esc_attr( preg_replace( '/\D+/', '', $info['whatsapp'] ) ); ?>" aria-label="WhatsApp" target="_blank" rel="noopener"><i data-lucide="message-circle"></i></a>
                        <?php endif; ?>
                        <a href="<?php echo esc_url( $info['instagram'] ); ?>" aria-label="Instagram" target="_blank" rel="noopener"><i data-lucide="instagram"></i></a>
                        <a href="<?php echo esc_url( $info['facebook'] ); ?>" aria-label="Facebook" target="_blank" rel="noopener"><i data-lucide="facebook"></i></a>
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
                    <h3>Aide & Compte</h3>
                    <ul>
                        <li><a href="<?php echo esc_url( $account_url ); ?>">Mon compte</a></li>
                        <li><a href="<?php echo esc_url( $cart_url ); ?>">Mon panier</a></li>
                        <li><a href="<?php echo esc_url( $contact_url ); ?>">Contact</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/livraison/' ) ); ?>">Livraison à Mayotte</a></li>
                    </ul>
                </div>

            </div>

            <div class="nl-footer-bottom">
                <p>© <?php echo esc_html( date_i18n( 'Y' ) ); ?> <?php echo esc_html( $info['name'] ); ?> — MADI ALI · SIRET <?php echo esc_html( $info['siret'] ); ?> · APE 47.11B. Tous droits réservés.</p>
            </div>
        </div>
    </footer>
    <?php
    return ob_get_clean();
}