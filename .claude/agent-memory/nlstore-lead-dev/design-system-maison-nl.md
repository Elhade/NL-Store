---
name: design-system-maison-nl
description: Design system v3.0.0 « Maison NL » — palette noir & or, tokens CSS, typographie fluide, composants visuels, cartes produit et catégories
metadata:
  type: project
---

## Design system « Maison NL » v3.0.0

Tous les tokens sont définis dans `style.css` sous `:root { --nl-* }`.

### Palette principale

| Token | Valeur | Usage |
|---|---|---|
| `--nl-black` | `#060503` | Fond principal |
| `--nl-surface` | `#141210` | Cartes, surfaces |
| `--nl-ivory` | `#f8f4ec` | Ancien fond clair (référence, non utilisé sur cartes catégories) |
| `--nl-gold` | `#d4af37` | Couleur dorée principale |
| `--nl-gold-bright` | `#f1d57e` | Accents lumineux, badge catégorie |
| `--nl-gold-ink` | fond sombre | Texte sur fond or (bouton hover) |
| `--nl-white` | `#f6f1e7` | Texte principal |
| `--nl-grey` | gris clair | Sous-titre cartes catégories |

### Typographie

- Titre : `Cormorant Garamond` (serif élégant), chargé depuis Google Fonts
- Corps : `Montserrat` (sans-serif moderne), chargé depuis Google Fonts
- Échelle fluide : `clamp()` sur `--nl-fs-display` (3rem→5.25rem) jusqu'à `--nl-fs-eyebrow` (0.72rem)

### Espacement

Grille 8pt : `--nl-space-1` (4px) à `--nl-space-10` (128px).
Section verticale fluide : `--nl-section-y: clamp(56px, 9vw, 112px)`.
Gouttière fluide : `--nl-gutter: clamp(20px, 5vw, 56px)`.
Conteneur max : `--nl-container: 1280px`.

### Easings

- `--nl-ease: cubic-bezier(0.22, 1, 0.36, 1)` — décélération naturelle
- `--nl-ease-spring: cubic-bezier(0.34, 1.56, 0.64, 1)` — effet ressort

### Cartes catégories — design pleine image + overlay

Structure HTML (dans `template-accueil.php`) :
```
<a class="nl-cat-card">
  <div class="nl-cat-card__img [nl-cat-card__img--empty]">  ← fond plein image (background-image inline)
  <span class="nl-cat-card__badge">                         ← coin haut-gauche (position absolute, top:16 left:16)
  <div class="nl-cat-card__content">                        ← overlay bas (position absolute, bottom:0)
    <h3 class="nl-cat-card__title">
    <p  class="nl-cat-card__sub">
    <span class="nl-cat-card__count">                       ← conditionnel (count > 0)
    <span class="nl-cat-card__btn">Découvrir [arrow-right]
```

Points clés :
- Pas de classe `nl-cat-card__body` (ancienne version supprimée — aucune occurrence résiduelle dans style.css ni template)
- L'overlay sombre vient du pseudo-élément `::after` sur `.nl-cat-card__img` (dégradé 180deg, opaque en bas)
- `.nl-cat-card__img--empty` : fond dégradé de remplacement + `✦` centré si pas d'image
- Badge : `position: absolute; top: 16px; left: 16px; z-index: 3` — fond semi-transparent + blur + bordure or
- Content : `position: absolute; bottom: 0; z-index: 3` — flex column, padding 26/24px
- Effet hover : `translateY(-8px)` + shadow gold + scale image à 1.07 + bouton fond or
- Reflet « shine » au survol : pseudo-élément `::after` sur `.nl-cat-card` (partagé avec les cartes produit WooCommerce)

### Cartes produit WooCommerce — design noir/or

- Fond sombre (`.nl-surface` / `#141210`), non ivoire
- Prix en or (`--nl-gold`) avec prix barré en gris
- Badge `span.onsale` : affiche `-X%` (via `nl_sale_flash_percent`)
- Cœur wishlist : `.nl-wishlist` bouton absolu coin haut-droite (via `nl_wishlist_button`)
- Bouton panier : carré (border-radius réduit)
- Reflet shine partagé avec `.nl-cat-card` via le même `::after`

### Fonds fixes animés par section

- **Catégories** : fond sombre simple, pas d'animation distincte
- **Produits populaires** (`.nl-products-section`) : stries dorées verticales animées (`nlStreaks`) + radial gradient + `background-attachment: fixed`
- **Promo aurore** : fond aurore animé (gradient radial)
- **Promo parallax** : sections avec `.nl-parallax` (attribut `data-speed`)

### Carousel catégories mobile

- Grid horizontale scrollable sur mobile (`overflow-x: scroll`, `scroll-snap-type: x mandatory`)
- Puces `.nl-cat-dots` : affichées uniquement mobile (masquées via `display: none` par défaut, activées via media query dans style.css)
- Autoplay toutes les 2s via `nlCatCarousel()` dans `nl-interactions`

### Autres composants

- **Header** : glassmorphisme (backdrop-filter)
- **Hero** : section cinématique avec `--nl-hero-bg` (image cat-parfums.jpeg en fallback)
- **Boutons** : reflet lumineux permanent sur `.nl-btn-hero--primary` et `.nl-btn-view-all`
- **Reveal au scroll** : `.nl-reveal` → `.nl-in` via IntersectionObserver
- **Scrollbar** : personnalisée couleur or
- **Fond page** : dégradé radial + attachment fixed + vignette film-grain (pseudo-éléments `::before`/`::after` sur `body`)
- **Footer Astra** : masqué en CSS (`.site-footer`) — remplacé par `nl_render_footer()` via `wp_footer`

**Why:** Design « maison de luxe » adapté au positionnement premium NL Store sur Mayotte. Le glassmorphisme et les dégradés sont intentionnels et ne doivent pas être simplifiés.

**How to apply:** Utiliser uniquement les tokens `--nl-*` pour toute nouvelle règle CSS. Toujours ajouter les classes `nl-cat-card__*` selon la structure ci-dessus — ne pas créer de variante `__body`.

Voir aussi : [[woocommerce-theme]], [[fonctions-helpers]]
