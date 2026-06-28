---
name: fonctions-woo-cartes-produit
description: Personnalisations WooCommerce des cartes produit — badge %, wishlist cœur localStorage, design noir/or
metadata:
  type: project
---

## Cartes produit WooCommerce — personnalisations thème

### Badge soldes — nl_sale_flash_percent()

Filtre `woocommerce_sale_flash` (priorité 10).
Calcule le pourcentage de remise : `round((regular - sale) / regular * 100)`.
Rendu HTML : `<span class="onsale">-X%</span>`.
Fallback si données invalides : `<span class="onsale">Promo</span>`.

Sélecteur CSS dans `style.css` : `.woocommerce span.onsale` — position absolue coin haut-gauche, fond doré, typographie majuscule.

### Wishlist cœur — nl_wishlist_button()

Hook `woocommerce_after_shop_loop_item` priorité 7 (entre fermeture lien produit à 5 et bouton panier à 10).
Rendu : `<button class="nl-wishlist" data-id="{id}" aria-label="..." aria-pressed="false">`.
Icône `nl_icon('heart')`.

Comportement JS (dans `nl-interactions` inline) :
- Lit/écrit le Set des IDs en `localStorage` clé `nl_wishlist`
- Bascule `.is-active` + `aria-pressed` au clic
- Restaure l'état actif au chargement de page
- Aucun backend — persistance purement client

### Bouton panier

Override template : `woocommerce/loop/add-to-cart.php`.
Style : carré (border-radius réduit vs style Astra arrondi).

### Slider produits populaires

Conteneur : `.nl-products-grid.nl-products-slider` dans `template-accueil.php`.
Flèches `.nl-slider-prev` / `.nl-slider-next` gérées par `nlSliders()`.
Défilement `scroll-snap-type: x mandatory` sur la liste `ul.products`.
Affiche 12 produits : featurs en priorité, puis catégorie parfums, puis tous.

**Why:** Ces personnalisations enrichissent l'expérience achat sans plugin tiers — la wishlist localStorage est délibérément légère (pas de synchronisation compte utilisateur dans la v3).

**How to apply:** Ne pas ajouter de plugin wishlist tiers (Yith, etc.) sans d'abord vérifier la compatibilité avec `nl-wishlist`. Le hook priorité 7 doit rester entre 5 et 10 pour que le bouton soit correctement positionné hors du lien produit.

Voir aussi : [[woocommerce-theme]], [[fonctions-helpers]]
