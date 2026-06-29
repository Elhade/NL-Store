<?php
/**
 * Template Name: NL Store — Nos marques
 * Template Post Type: page
 *
 * Page listant les marques avec lesquelles NL Store travaille.
 * Données fournies par nl_brands() (filtrable). Logo si disponible,
 * sinon wordmark doré.
 */

get_header();

$brands = function_exists( 'nl_brands' ) ? nl_brands() : [];
?>

<main class="nl-marques-page">

    <section class="nl-marques-hero">
        <p class="nl-section-label">Nos partenaires</p>
        <h1>Nos marques</h1>
        <p class="nl-contact-sub">Les maisons et marques que nous avons soigneusement sélectionnées pour vous, à Mayotte.</p>
    </section>

    <section class="nl-marques-wrap">
        <div class="nl-marques-grid">
            <?php foreach ( $brands as $b ) :
                $burl = ! empty( $b['url'] ) ? $b['url'] : '';
                $tag  = $burl ? 'a' : 'div';
            ?>
            <<?php echo $tag; ?> class="nl-marque-card nl-reveal"<?php if ( $burl ) : ?> href="<?php echo esc_url( $burl ); ?>" target="_blank" rel="noopener"<?php endif; ?>>
                <div class="nl-marque-card__logo">
                    <?php if ( ! empty( $b['logo'] ) ) : ?>
                        <img src="<?php echo esc_url( nl_brand_logo_url( $b['logo'] ) ); ?>" alt="<?php echo esc_attr( $b['name'] ); ?>" loading="lazy" decoding="async">
                    <?php else : ?>
                        <span class="nl-brand-wordmark"><?php echo esc_html( $b['name'] ); ?></span>
                    <?php endif; ?>
                </div>
                <h3><?php echo esc_html( $b['name'] ); ?></h3>
                <?php if ( ! empty( $b['desc'] ) ) : ?>
                    <p><?php echo esc_html( $b['desc'] ); ?></p>
                <?php endif; ?>
            </<?php echo $tag; ?>>
            <?php endforeach; ?>
        </div>
    </section>

</main>

<?php get_footer();
