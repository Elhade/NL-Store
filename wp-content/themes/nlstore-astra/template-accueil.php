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
 *  L'ancienne page d'accueil reste accessible via son URL et n'est pas supprimée.
 */

get_header();

// ── Héro : image de fond = image mise en avant de la page (ou fallback CSS) ──
$hero_bg_url = has_post_thumbnail() ? get_the_post_thumbnail_url(null, 'full') : '';

// ── Logo custom WordPress ──
$logo_id  = get_theme_mod('custom_logo');
$logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'medium') : '';

// ── URL boutique WooCommerce ──
$shop_url = class_exists('WooCommerce') ? wc_get_page_permalink('shop') : home_url('/boutique/');

// ── Catégories : slug WooCommerce => config affichage ──
$categories_config = [
    [
        'slug'  => 'bebe',
        'label' => 'Bébè',
        'items' => ['Biberons', 'Couches', 'Lingettes'],
    ],
    [
        'slug'  => 'parfums',
        'label' => 'Parfums',
        'items' => ['Femme', 'Homme', 'Brumes Dubai'],
    ],
    [
        'slug'  => 'vetements',
        'label' => 'Vêtements',
        'items' => ['Bébé', 'Enfant'],
    ],
    [
        'slug'  => 'hygiene',
        'label' => 'Hygiène',
        'items' => ['Crèmes', 'Lingettes', 'Produits bébé'],
    ],
];
?>

<main id="nl-home" class="nl-home-page">

    <!-- ====================================================
         HERO
    ==================================================== -->
    <section class="nl-hero-section"<?php if ($hero_bg_url): ?> style="background-image: linear-gradient(135deg, rgba(5,5,5,0.52), rgba(5,5,5,0.76)), url('<?php echo esc_url($hero_bg_url); ?>') !important; background-size: cover !important; background-position: center !important;"<?php endif; ?>>

        <div class="nl-hero-inner">
            <p class="nl-hero-surtitre">Exclusivement pour Mayotte</p>

            <?php if ($logo_url): ?>
                <img src="<?php echo esc_url($logo_url); ?>" alt="NL Store" class="nl-hero-logo-img">
            <?php else: ?>
                <div class="nl-hero-logo-fallback">NL</div>
            <?php endif; ?>

            <h1 class="nl-hero-brand">NL STORE</h1>
            <h2 class="nl-hero-subtitle">Tout pour bébé, parfums et vêtements</h2>
            <p class="nl-hero-tagline">Qualité &nbsp;·&nbsp; Prix doux &nbsp;·&nbsp; Livraison rapide</p>

            <a href="<?php echo esc_url($shop_url); ?>" class="nl-btn-hero">
                Découvrir la boutique
            </a>
        </div>
    </section>

    <!-- ====================================================
         CATÉGORIES
    ==================================================== -->
    <section class="nl-categories-section">
        <div class="nl-categories-grid">
            <?php foreach ($categories_config as $cat):
                $term      = get_term_by('slug', $cat['slug'], 'product_cat');
                $cat_url   = ($term && !is_wp_error($term)) ? get_term_link($term) : $shop_url;
                $thumb_id  = ($term && !is_wp_error($term)) ? get_term_meta($term->term_id, 'thumbnail_id', true) : 0;
                $thumb_url = $thumb_id ? wp_get_attachment_image_url($thumb_id, 'medium_large') : '';
            ?>
            <a href="<?php echo esc_url($cat_url); ?>" class="nl-cat-card">
                <div class="nl-cat-card__img<?php echo $thumb_url ? '' : ' nl-cat-card__img--empty'; ?>"
                    <?php if ($thumb_url): ?>style="background-image:url('<?php echo esc_url($thumb_url); ?>')"<?php endif; ?>>
                </div>
                <div class="nl-cat-card__body">
                    <h3><?php echo esc_html($cat['label']); ?></h3>
                    <ul>
                        <?php foreach ($cat['items'] as $item): ?>
                            <li><?php echo esc_html($item); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ====================================================
         PRODUITS POPULAIRES
    ==================================================== -->
    <section class="nl-products-section">
        <div class="nl-section-title-wrap">
            <span class="nl-section-line"></span>
            <h2 class="nl-section-title">Produits Populaires</h2>
            <span class="nl-section-line"></span>
        </div>

        <div class="nl-products-grid">
            <?php
            if (class_exists('WooCommerce')) {
                echo do_shortcode('[products limit="4" columns="4" orderby="popularity" status="publish"]');
            }
            ?>
        </div>
    </section>

    <!-- ====================================================
         PROMOTIONS DE LA SEMAINE
    ==================================================== -->
    <section class="nl-promo-week-section">
        <div class="nl-section-title-wrap">
            <span class="nl-section-deco">✦</span>
            <h2 class="nl-section-title">Promotions de la semaine</h2>
            <span class="nl-section-deco">✦</span>
        </div>

        <div class="nl-promo-week-grid">
            <!-- Promo featured -->
            <div class="nl-promo-week-left">
                <?php echo do_shortcode('[nl_weekly_promos_carousel]'); ?>
            </div>

            <!-- Avis clients -->
            <div class="nl-promo-week-right">
                <?php echo do_shortcode('[nl_testimonials_carousel title="Avis clients"]'); ?>
            </div>
        </div>
    </section>

</main><!-- #nl-home -->

<?php get_footer(); ?>
