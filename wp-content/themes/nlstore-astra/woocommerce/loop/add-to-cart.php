<?php
/**
 * Loop Add to Cart — NL Store override
 * Ajoute une flèche "›" au texte du bouton
 *
 * @package NLStore Astra
 * @version 9.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $product;

$aria_describedby = isset( $args['aria-describedby_text'] )
    ? sprintf( 'aria-describedby="woocommerce_loop_add_to_cart_link_describedby_%s"', esc_attr( $product->get_id() ) )
    : '';

$button_text = esc_html( $product->add_to_cart_text() );

echo apply_filters(
    'woocommerce_loop_add_to_cart_link',
    sprintf(
        '<a href="%s" %s data-quantity="%s" class="%s" %s><span>%s</span><span class="nl-btn-arrow">›</span></a>',
        esc_url( $product->add_to_cart_url() ),
        $aria_describedby,
        esc_attr( isset( $args['quantity'] ) ? $args['quantity'] : 1 ),
        esc_attr( isset( $args['class'] ) ? $args['class'] : 'button' ),
        isset( $args['attributes'] ) ? wc_implode_html_attributes( $args['attributes'] ) : '',
        $button_text
    ),
    $product,
    $args
);

if ( isset( $args['aria-describedby_text'] ) ) : ?>
    <span id="woocommerce_loop_add_to_cart_link_describedby_<?php echo esc_attr( $product->get_id() ); ?>" class="screen-reader-text">
        <?php echo esc_html( $args['aria-describedby_text'] ); ?>
    </span>
<?php endif;
