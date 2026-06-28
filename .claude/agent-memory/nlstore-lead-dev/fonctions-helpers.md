---
name: fonctions-helpers
description: Helpers et shortcodes PHP du thème — nl_icon(), nl_asset_ver(), [nl_footer], [nl_promo_banner], [nl_weekly_promos_carousel], [nl_testimonials_carousel], interactions JS inline
metadata:
  type: project
---

## Helpers & shortcodes — `functions.php`

### nl_asset_ver() — cache-busting par filemtime

Retourne le timestamp de modification du fichier comme version d'enqueue.
Garantit que `?ver=` change à chaque déploiement FTP sans action manuelle.
Fallback : `CHILD_THEME_NLSTORE_ASTRA_VERSION` ('3.0.0') si le fichier est absent.
Voir détail dans [[stack-architecture]].

### nl_icon() — SVG Lucide auto-hébergé

```php
echo nl_icon('truck');                     // usage standard
echo nl_icon('arrow-right', 'ma-classe'); // classe custom
```

Icônes disponibles (tracés Lucide ISC, tableau statique `$paths`) :
`truck`, `shield-check`, `headphones`, `arrow-right`, `chevron-left`, `chevron-right`,
`flame`, `quote`, `message-circle`, `map-pin`, `phone`, `mail`, `instagram`, `facebook`, `heart`

**Aucune dépendance CDN.** Retourne un `<svg aria-hidden="true" focusable="false">`.

### nl_enqueue_interactions() — Script inline nl-interactions

Handle WordPress : `nl-interactions` (script inline, sans fichier physique, enqueué en pied de page).
Contient les fonctions suivantes, toutes initialisées au `DOMContentLoaded` :

- **nlReveal()** : IntersectionObserver sur `.nl-reveal`, `.nl-reveal-left`, `.nl-reveal-right`, `.nl-reveal-scale`, `.nl-stagger`. Ajoute `.nl-in`. Threshold 0.12, rootMargin -8% bas.
- **nlParallax()** : parallax léger via rAF sur `.nl-parallax` (attribut `data-speed`). Désactivé si `prefers-reduced-motion` ou largeur < 1025px.
- **nlSliders()** : flèches prev/next pour `.nl-products-slider`. Scroll horizontal de 2 cartes par clic.
- **nlWishlist()** : bascule `.nl-wishlist` avec persistance localStorage (`nl_wishlist` → Set de IDs produit).
- **nlCatCarousel()** : carousel auto mobile sur `.nl-categories-grid` — avance toutes les 2s, boucle, pause au touchstart, reprend 3.5s après touchend. Crée dynamiquement les puces `.nl-cat-dots`. Actif uniquement si `max-width: 768px` et `prefers-reduced-motion` non actif.

### nl_wishlist_button() — Cœur favoris sur cartes produit

Hook : `woocommerce_after_shop_loop_item` priorité 7.
Injecte un `<button class="nl-wishlist" data-id="{id}">` avec `nl_icon('heart')`.
État persisté côté client en localStorage (aucun backend).

### nl_sale_flash_percent() — Badge soldes en pourcentage

Filtre : `woocommerce_sale_flash`.
Remplace « Promo ! » par `-X%` calculé dynamiquement (ex. `-20%`).
Sélecteur CSS : `.woocommerce span.onsale`.

### [nl_footer] — Footer luxe

Shortcode : `nl_render_footer()`.
Aussi injecté automatiquement via `wp_footer` (priorité 5) — pas besoin de placer le shortcode manuellement.
Coordonnées configurables via le filtre WordPress `nl_company_info`.

Valeurs hardcodées dans `nl_company_info()` (source unique) :
- Société : MADI ALI — EI, SIREN 812 234 094, SIRET 812 234 094 00017, APE 47.11B
- Adresse : Imp. de la Place Publique, Mroalé — 97680 Tsingoni, Mayotte
- `phone` : 07 66 53 38 47
- `whatsapp` : 07 66 53 38 47
- `email` : contact@nl.store.ghost-service.fr
- `instagram` : vide (à renseigner via filtre `nl_company_info`)
- `facebook` : vide (à renseigner via filtre `nl_company_info`)

### [nl_promo_banner] — Bannière promo configurable

Configurable via WP Admin > NL Store > Bannière Promo.
Stocké dans `wp_options` clé `nl_promo_banner`.
Gradient couleur, texte et lien paramétrables. N'affiche rien si `is_active = 0`.
Aussi injecté via `wp_body_open` priorité 5 (garde anti-double-rendu via flag statique).

### [nl_weekly_promos_carousel] — Carousel promos de la semaine

Lit la table `{prefix}_nl_weekly_promos`.
Configurable via WP Admin > NL Store > Promotions Semaine.
Swiper auto-hébergé (voir ci-dessous).

### [nl_testimonials_carousel] — Carousel avis WooCommerce

Lit les commentaires WooCommerce (`comment_type = 'review'`) avec note >= `min_rating` (défaut 4).
Paramètres shortcode : `title`, `max_reviews` (défaut 12), `min_rating` (défaut 4).

### Swiper v11.2.10 — auto-hébergé

Fichiers : `assets/swiper/swiper-bundle.min.js` + `assets/swiper/swiper-bundle.min.css`.
Chargé conditionnellement : pages d'accueil, template `template-accueil.php`, ou pages contenant les shortcodes promos/avis.
Filtre WordPress `nl_enqueue_swiper` pour forcer/inhiber le chargement.
**Aucune dépendance CDN.**

Note : la mémoire précédente indiquait CDN jsDelivr v11.0.0 — c'est périmé. Swiper est auto-hébergé depuis la correction post-audit (v11.2.10).

### nl_create_promotions_table()

Crée `{prefix}_nl_weekly_promos` via `dbDelta()`.
Déclenchée sur `after_switch_theme`. Vérification lazy dans la page admin promos (SHOW TABLES) pour éviter dbDelta systématique.

Voir aussi : [[design-system-maison-nl]], [[catalogue-produits]]
