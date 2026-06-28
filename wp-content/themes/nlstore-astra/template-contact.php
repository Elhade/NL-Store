<?php
/**
 * Template Name: NL Store — Contact
 * Template Post Type: page
 *
 * Page de contact : formulaire (nonce + honeypot + wp_mail),
 * coordonnées société et carte de géolocalisation.
 */

get_header();

$info    = nl_company_info();
$result  = nl_handle_contact_form();
$map_src = 'https://maps.google.com/maps?q=' . rawurlencode( $info['map_query'] ) . '&z=15&output=embed';

$tel_digits = preg_replace( '/\D+/', '', $info['phone'] );
$tel_intl   = ( 0 === strpos( (string) $tel_digits, '0' ) ) ? '+33' . substr( $tel_digits, 1 ) : ( $tel_digits ? '+' . $tel_digits : '' );
$wa_digits  = preg_replace( '/\D+/', '', $info['whatsapp'] );
if ( 0 === strpos( (string) $wa_digits, '0' ) ) {
    $wa_digits = '33' . substr( $wa_digits, 1 );
}

// Repopulation sûre des champs après erreur
$old_name    = isset( $_POST['nl_name'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_POST['nl_name'] ) ) ) : '';
$old_email   = isset( $_POST['nl_email'] ) ? esc_attr( sanitize_email( wp_unslash( $_POST['nl_email'] ) ) ) : '';
$old_message = isset( $_POST['nl_message'] ) ? esc_textarea( sanitize_textarea_field( wp_unslash( $_POST['nl_message'] ) ) ) : '';
?>

<main class="nl-contact-page">

    <section class="nl-contact-hero">
        <p class="nl-section-label">Nous contacter</p>
        <h1>Parlons-en</h1>
        <p class="nl-contact-sub">Une question sur un produit, une commande ou la livraison à Mayotte ? Écrivez-nous, on vous répond vite.</p>
    </section>

    <section class="nl-contact-wrap">
        <div class="nl-contact-grid">

            <div class="nl-contact-form-card nl-reveal">
                <h2>Envoyer un message</h2>

                <?php if ( $result ) : ?>
                    <div class="nl-contact-alert <?php echo $result['ok'] ? 'is-ok' : 'is-err'; ?>">
                        <?php echo esc_html( $result['msg'] ); ?>
                    </div>
                <?php endif; ?>

                <?php if ( ! $result || ! $result['ok'] ) : ?>
                <form method="post" class="nl-contact-form" novalidate>
                    <?php wp_nonce_field( 'nl_contact', 'nl_contact_nonce' ); ?>
                    <input type="hidden" name="nl_contact_submit" value="1">

                    <p class="nl-hp" aria-hidden="true">
                        <label>Ne pas remplir <input type="text" name="nl_website" tabindex="-1" autocomplete="off"></label>
                    </p>

                    <div class="nl-field">
                        <label for="nl_name">Nom *</label>
                        <input id="nl_name" name="nl_name" type="text" required value="<?php echo $old_name; ?>">
                    </div>
                    <div class="nl-field">
                        <label for="nl_email">E-mail *</label>
                        <input id="nl_email" name="nl_email" type="email" required value="<?php echo $old_email; ?>">
                    </div>
                    <div class="nl-field">
                        <label for="nl_message">Message *</label>
                        <textarea id="nl_message" name="nl_message" rows="6" required><?php echo $old_message; ?></textarea>
                    </div>

                    <button type="submit" class="nl-contact-submit">Envoyer le message <?php echo nl_icon( 'arrow-right' ); ?></button>
                </form>
                <?php endif; ?>
            </div>

            <aside class="nl-contact-info nl-reveal">
                <h2>Nos coordonnées</h2>
                <ul class="nl-contact-list">
                    <?php if ( $info['address'] ) : ?>
                        <li><?php echo nl_icon( 'map-pin' ); ?><span><?php echo esc_html( $info['address'] ); ?></span></li>
                    <?php endif; ?>
                    <?php if ( $info['phone'] ) : ?>
                        <li><?php echo nl_icon( 'phone' ); ?><a href="tel:<?php echo esc_attr( $tel_intl ); ?>"><?php echo esc_html( $info['phone'] ); ?></a></li>
                    <?php endif; ?>
                    <?php if ( $info['whatsapp'] ) : ?>
                        <li><?php echo nl_icon( 'message-circle' ); ?><a href="https://wa.me/<?php echo esc_attr( $wa_digits ); ?>" target="_blank" rel="noopener">WhatsApp · <?php echo esc_html( $info['whatsapp'] ); ?></a></li>
                    <?php endif; ?>
                    <?php if ( $info['email'] ) : ?>
                        <li><?php echo nl_icon( 'mail' ); ?><a href="mailto:<?php echo esc_attr( $info['email'] ); ?>"><?php echo esc_html( $info['email'] ); ?></a></li>
                    <?php endif; ?>
                </ul>

                <div class="nl-contact-map">
                    <iframe src="<?php echo esc_url( $map_src ); ?>" title="Localisation NL Store — Tsingoni, Mayotte" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                </div>
            </aside>

        </div>
    </section>

</main>

<?php get_footer();
