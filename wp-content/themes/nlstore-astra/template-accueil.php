<?php
/**
 * Template Name: NL Store — Accueil
 * Template Post Type: page
 *
 * Usage :
 *  1. Créer une nouvelle Page dans WP Admin > Pages > Ajouter
 *  2. Dans "Attributs de page > Modèle", choisir "NL Store — Accueil"
 *  3. Publier, puis aller dans Réglages > Lecture et définir cette page
 *     comme "Page d'accueil statique"
 */

get_header();

// ── Logo custom WordPress ──
$logo_id  = get_theme_mod('custom_logo');
$logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'medium') : '';

// ── URLs ──
$shop_url    = class_exists('WooCommerce') ? wc_get_page_permalink('shop') : home_url('/boutique/');
$contact_url = get_permalink(get_page_by_path('contact')) ?: home_url('/contact/');

// ── Hero background depuis les assets du thème ──
$hero_bg = get_theme_file_uri('assets/imgs/cat-parfums.jpeg');

// ── Catégories config (fallback = assets thème) ──
$categories_config = [
    [
        'slug'        => 'bebe',
        'label'       => 'Bébé',
        'badge'       => 'Top vente',
        'subtitle'    => 'Tout pour bébé',
        'unit'        => 'produits',
        'fallback_img'=> get_theme_file_uri('assets/imgs/cat-bebe.jpeg'),
    ],
    [
        'slug'        => 'parfums',
        'label'       => 'Parfums',
        'badge'       => 'Nouveau',
        'subtitle'    => 'Pour femme et homme',
        'unit'        => 'références',
        'fallback_img'=> get_theme_file_uri('assets/imgs/cat-parfums.jpeg'),
    ],
    [
        'slug'        => 'vetements',
        'label'       => 'Vêtements',
        'badge'       => 'Nouveau',
        'subtitle'    => 'Bébé et enfant',
        'unit'        => 'produits',
        'fallback_img'=> get_theme_file_uri('assets/imgs/cat-vetements.jpeg'),
    ],
    [
        'slug'        => 'hygiene',
        'label'       => 'Hygiène',
        'badge'       => 'Top vente',
        'subtitle'    => 'Soins et bien-être',
        'unit'        => 'produits',
        'fallback_img'=> get_theme_file_uri('assets/imgs/cat-hygiene.jpg'),
    ],
];
?>

<main id="nl-home" class="nl-home-page">

    <!-- ====================================================
         HERO
    ==================================================== -->
    <section class="nl-hero-section" style="--nl-hero-bg: url('<?php echo esc_url($hero_bg); ?>')">

        <div class="nl-hero-inner">
            <p class="nl-hero-surtitre">Pour Mayotte et ses alentours</p>

            <?php if ($logo_url): ?>
                <img src="<?php echo esc_url($logo_url); ?>" alt="NL Store" class="nl-hero-logo-img nl-parallax" data-speed="-0.06">
            <?php else: ?>
                <div class="nl-hero-logo-fallback nl-parallax" data-speed="-0.06">NL</div>
            <?php endif; ?>

            <h1 class="nl-hero-brand">NL STORE</h1>
            <h2 class="nl-hero-subtitle">Tout pour bébé, parfums et vêtements</h2>
            <p class="nl-hero-tagline">Qualité &nbsp;·&nbsp; Prix doux &nbsp;·&nbsp; Livraison à Mayotte et alentours</p>

            <div class="nl-hero-buttons">
                <a href="<?php echo esc_url($shop_url); ?>" class="nl-btn-hero nl-btn-hero--primary">
                    Découvrir la boutique <?php echo nl_icon('arrow-right'); ?>
                </a>
                <a href="<?php echo esc_url($contact_url); ?>" class="nl-btn-hero nl-btn-hero--secondary">
                    <?php echo nl_icon('message-circle'); ?> Nous contacter
                </a>
            </div>

        </div>
    </section>

    <div class="nl-divider" aria-hidden="true">
        <span class="nl-divider__line"></span>
        <span class="nl-divider__dot">✦</span>
        <span class="nl-divider__line"></span>
    </div>

    <!-- ====================================================
         CATÉGORIES
    ==================================================== -->
    <section class="nl-categories-section">
        <div class="nl-section-header nl-reveal">
            <p class="nl-section-label">NOS CATÉGORIES</p>
            <h2 class="nl-section-main-title">Qu'est-ce que vous cherchez ?</h2>
            <p class="nl-section-sub">Découvrez nos univers soigneusement sélectionnés</p>
        </div>

        <div class="nl-categories-grid nl-stagger">
            <?php foreach ($categories_config as $cat):
                $term      = get_term_by('slug', $cat['slug'], 'product_cat');
                $cat_url   = ($term && !is_wp_error($term)) ? get_term_link($term) : $shop_url;
                $thumb_id  = ($term && !is_wp_error($term)) ? get_term_meta($term->term_id, 'thumbnail_id', true) : 0;
                $thumb_url = $thumb_id ? wp_get_attachment_image_url($thumb_id, 'large') : $cat['fallback_img'];
                $count     = ($term && !is_wp_error($term)) ? (int) $term->count : 0;

                // Dernier recours : première image produit de la catégorie
                if (!$thumb_url && $term && !is_wp_error($term)) {
                    $first_product = wc_get_products([
                        'category' => [$cat['slug']],
                        'limit'    => 1,
                        'status'   => 'publish',
                    ]);
                    if (!empty($first_product)) {
                        $pid = get_post_thumbnail_id($first_product[0]->get_id());
                        $thumb_url = $pid ? wp_get_attachment_image_url($pid, 'large') : '';
                    }
                }
            ?>
            <a href="<?php echo esc_url($cat_url); ?>" class="nl-cat-card">
                <div class="nl-cat-card__img<?php echo $thumb_url ? '' : ' nl-cat-card__img--empty'; ?>"
                    <?php if ($thumb_url): ?>style="background-image:url('<?php echo esc_url($thumb_url); ?>')"<?php endif; ?>>
                </div>
                <?php if (!empty($cat['badge'])): ?>
                    <span class="nl-cat-card__badge"><?php echo esc_html($cat['badge']); ?></span>
                <?php endif; ?>
                <div class="nl-cat-card__content">
                    <h3 class="nl-cat-card__title"><?php echo esc_html($cat['label']); ?></h3>
                    <p class="nl-cat-card__sub"><?php echo esc_html($cat['subtitle']); ?></p>
                    <?php if ($count > 0): ?>
                        <span class="nl-cat-card__count"><?php echo esc_html($count . ' ' . $cat['unit']); ?></span>
                    <?php endif; ?>
                    <span class="nl-cat-card__btn">Découvrir <?php echo nl_icon('arrow-right'); ?></span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ====================================================
<<<<<<< HEAD
         RÉASSURANCE (configurable au back-office) — juste après les catégories
=======
         PHOTOMATON EN MAIRIE (configurable au back-office) — juste après les catégories
    ==================================================== -->
    <?php echo nl_render_photomaton(); ?>

    <div class="nl-divider" aria-hidden="true">
        <span class="nl-divider__line"></span>
        <span class="nl-divider__dot">✦</span>
        <span class="nl-divider__line"></span>
    </div>
    
    <!-- ====================================================
         RÉASSURANCE (configurable au back-office) — entre catégories et best-sellers
>>>>>>> c083a47 (feat(photomaton): add divider element to enhance visual separation in homepage template)
    ==================================================== -->
    <?php echo nl_render_reassurance(); ?>

    <!-- ====================================================
         PHOTOMATON EN MAIRIE (configurable au back-office) — après la réassurance
    ==================================================== -->
    <?php echo nl_render_photomaton(); ?>

    <div class="nl-divider" aria-hidden="true">
        <span class="nl-divider__line"></span>
        <span class="nl-divider__dot">✦</span>
        <span class="nl-divider__line"></span>
    </div>

    <!-- ====================================================
         PRODUITS POPULAIRES
    ==================================================== -->
    <section class="nl-products-section">
        <div class="nl-reveal" style="text-align:center;">
            <p class="nl-section-label">NOS BEST-SELLERS</p>
            <div class="nl-section-title-wrap">
                <span class="nl-section-line"></span>
                <h2 class="nl-section-title">Produits Populaires</h2>
                <span class="nl-section-line"></span>
            </div>
        </div>

        <div class="nl-products-grid nl-products-slider nl-reveal">
            <button type="button" class="nl-slider-nav nl-slider-prev" aria-label="Produits précédents"><?php echo nl_icon('chevron-left'); ?></button>
            <?php
            if (class_exists('WooCommerce')) {
                $featured_ids = wc_get_featured_product_ids();
                if (!empty($featured_ids)) {
                    echo do_shortcode('[products limit="12" columns="4" visibility="featured" orderby="date" order="DESC"]');
                } else {
                    $parfums = get_term_by('slug', 'parfums', 'product_cat');
                    if ($parfums && !is_wp_error($parfums) && $parfums->count > 0) {
                        echo do_shortcode('[products limit="12" columns="4" category="parfums" orderby="date" order="DESC"]');
                    } else {
                        echo do_shortcode('[products limit="12" columns="4" orderby="date" order="DESC"]');
                    }
                }
            }
            ?>
            <button type="button" class="nl-slider-nav nl-slider-next" aria-label="Produits suivants"><?php echo nl_icon('chevron-right'); ?></button>
        </div>

        <div class="nl-products-view-all">
            <a href="<?php echo esc_url($shop_url); ?>" class="nl-btn-view-all">
                Voir tous les produits <?php echo nl_icon('arrow-right'); ?>
            </a>
        </div>
    </section>

    <!-- ====================================================
         MARQUES (bande défilante) — entre produits et promotions
    ==================================================== -->
    <?php echo do_shortcode('[nl_brands_marquee]'); ?>

    <div class="nl-divider" aria-hidden="true">
        <span class="nl-divider__line"></span>
        <span class="nl-divider__dot">✦</span>
        <span class="nl-divider__line"></span>
    </div>

    <!-- ====================================================
         PROMOTIONS DE LA SEMAINE
    ==================================================== -->
    <section class="nl-promo-week-section">
        <div class="nl-section-title-wrap nl-reveal">
            <?php echo nl_icon('flame', 'nl-icon nl-flame'); ?>
            <h2 class="nl-section-title">Promotions de la semaine</h2>
            <?php echo nl_icon('flame', 'nl-icon nl-flame'); ?>
        </div>

        <div class="nl-promo-week-grid">
            <div class="nl-promo-week-left nl-reveal-left">
                <?php echo do_shortcode('[nl_weekly_promos_carousel]'); ?>
            </div>

            <div class="nl-promo-week-right nl-reveal-right">
                <?php echo do_shortcode('[nl_testimonials_carousel title="Avis clients"]'); ?>
            </div>
        </div>
    </section>

</main><!-- #nl-home -->

<?php get_footer(); ?>
