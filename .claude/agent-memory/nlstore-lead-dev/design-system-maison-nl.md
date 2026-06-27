---
name: design-system-maison-nl
description: Design system v3.0.0 « Maison NL » — palette noir & or, tokens CSS, typographie fluide, composants visuels
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
| `--nl-ivory` | `#f8f4ec` | Cartes produit (fond clair) |
| `--nl-gold` | `#d4af37` | Couleur dorée principale |
| `--nl-gold-bright` | `#f1d57e` | Accents lumineux |
| `--nl-white` | `#f6f1e7` | Texte principal |

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

### Composants visuels

- **Header** : glassmorphisme (backdrop-filter)
- **Hero** : section cinématique avec `--nl-hero-bg` (image en CSS var), monogramme/logo avec dégradé
- **Boutons** : reflet lumineux au hover, variantes primaire (or) et secondaire (outline)
- **Cartes catégories/produits** : hover-lift (translateY + shadow-gold)
- **Footer** : fond sombre profond, grille 3 colonnes, mentions légales intégrées
- **Reveal au scroll** : classe `.nl-reveal` → `.nl-in` via IntersectionObserver
- **Scrollbar** : personnalisée couleur or (`--nl-gold`)
- **Fond page** : dégradé radial + attachment fixed + vignette film-grain (pseudo-éléments `::before`/`::after` sur `body`)

**Why:** Design « maison de luxe » adapté au positionnement premium NL Store sur Mayotte. Le glassmorphisme et les dégradés sont intentionnels et ne doivent pas être simplifiés.

**How to apply:** Utiliser uniquement les tokens `--nl-*` pour toute nouvelle règle CSS. Ne pas coder de valeurs hexadécimales en dur.

Voir aussi : [[woocommerce-theme]], [[fonctions-helpers]]
