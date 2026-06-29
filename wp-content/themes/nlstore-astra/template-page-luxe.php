<?php
/**
 * Template Name: NL Store — Page
 * Template Post Type: page
 *
 * Gabarit de contenu luxe : en-tête + zone de texte riche.
 * Utilisé par FAQ, CGV, Support & Assistance, etc.
 */

get_header();

while ( have_posts() ) :
    the_post();
    ?>
    <main class="nl-page">
        <section class="nl-page-hero">
            <p class="nl-section-label">NL Store</p>
            <h1><?php the_title(); ?></h1>
        </section>
        <section class="nl-page-wrap">
            <div class="nl-richtext nl-reveal">
                <?php the_content(); ?>
            </div>
        </section>
    </main>
    <?php
endwhile;

get_footer();
