---
name: woocommerce-theme
description: Déclinaison WooCommerce du thème Maison NL — correctif prix invisibles, pages boutique/fiche/panier/checkout/compte
metadata:
  type: project
---

## WooCommerce — thème Maison NL

Fichier : `woocommerce.css` (enqueué conditionnellement si WooCommerce actif, après `style.css`).

### Correctif critique : prix invisibles sur cartes ivoire

**Problème** : `style.css` applique une règle globale `span { color: white }`. Sur les cartes produit à fond ivoire (`--nl-ivory`), les prix (dans des `<span>`) devenaient invisibles (blanc sur ivoire).

**Correctif appliqué** (commit `559223d`) :

```css
.woocommerce .amount,
.woocommerce .amount span,
.woocommerce .woocommerce-Price-amount,
.woocommerce .woocommerce-Price-currencySymbol,
.woocommerce .price ins,
.woocommerce .price > span,
.woocommerce bdi {
  color: inherit !important;
}
```

Les montants héritent de la couleur de leur conteneur (`.price` doré, totaux, etc.) au lieu d'être forcés en blanc.

### Support thème déclaré dans `functions.php`

- `thumbnail_image_width` : 600px / `single_image_width` : 900px
- Grille par défaut : 4 colonnes, 3 lignes
- Gallery features : zoom, lightbox, slider

### Pages couvertes par `woocommerce.css`

- Boutique (`woocommerce-shop`) : en-tête, résultats, tri
- Fiche produit (`single-product`)
- Panier (`woocommerce-cart`)
- Checkout (`woocommerce-checkout`)
- Mon compte (`woocommerce-account`)
- Archives catégorie (`tax-product_cat`)

**Why:** Le CSS WooCommerce est séparé de `style.css` pour isoler les surcharges Woo et ne pas alourdir les pages non-Woo.

**How to apply:** Toute règle ciblant des sélecteurs WooCommerce va dans `woocommerce.css`, pas dans `style.css`.

Voir aussi : [[design-system-maison-nl]]
