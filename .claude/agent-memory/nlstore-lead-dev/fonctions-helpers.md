---
name: fonctions-helpers
description: Helpers et shortcodes PHP du thème — nl_icon(), [nl_footer], [nl_promo_banner], [nl_weekly_promos_carousel], [nl_testimonials_carousel], scroll reveal
metadata:
  type: project
---

## Helpers & shortcodes — `functions.php`

### nl_icon() — SVG Lucide auto-hébergé

```php
echo nl_icon('truck');                    // usage standard
echo nl_icon('shield-check', 'ma-classe'); // classe custom
```

Icônes disponibles (tracés Lucide ISC, statiques dans `$paths`) :
`truck`, `shield-check`, `headphones`, `arrow-right`, `message-circle`, `map-pin`, `phone`, `mail`, `instagram`, `facebook`

**Aucune dépendance CDN.** Les tracés SVG sont intégrés directement dans le tableau statique `$paths`. Retourne un `<svg aria-hidden="true" focusable="false">`.

Contexte : icônes d'abord chargées via CDN unpkg (audit nlstore-auditor → risque dépendance externe), puis épinglées, puis auto-hébergées inline (commit `9a1330c`).

### nl_enqueue_interactions() — Reveal au scroll

Handle WordPress : `nl-interactions` (script inline, sans fichier physique).
Classe CSS déclencheur : `.nl-reveal` → ajoute `.nl-in` quand l'élément entre dans le viewport.
IntersectionObserver avec `threshold: 0.12` et `rootMargin: 0px 0px -8% 0px`.
Fallback : si IntersectionObserver absent, `.nl-in` appliqué immédiatement.

### [nl_footer] — Footer luxe

Shortcode : `nl_render_footer()`.
**À placer dans le Footer Builder d'Astra** (widget HTML/shortcode).
Coordonnées configurables via le filtre WordPress `nl_company_info`.

Valeurs par défaut (hardcodées) :
- Société : MADI ALI, SIRET 812 234 094 00017, APE 47.11B
- Adresse : Imp. de la Place Publique, Mroalé, 97680 Tsingoni, Mayotte
- `phone`, `email`, `whatsapp` : vides (à renseigner via filtre)
- `instagram`, `facebook` : `#` (à renseigner via filtre)

### [nl_promo_banner] — Bannière promo configurable

Configurable via WP Admin > NL Store > Bannière Promo.
Stocké dans `wp_options` clé `nl_promo_banner`.
Gradient couleur, texte et lien paramétrables. N'affiche rien si `is_active = 0`.

### [nl_weekly_promos_carousel] — Carousel promos de la semaine

Lit la table `{prefix}_nl_weekly_promos` (créée par `nl_create_promotions_table()`).
Configurable via WP Admin > NL Store > Promotions Semaine.
Charge Swiper@11 depuis CDN jsDelivr.

### [nl_testimonials_carousel] — Carousel avis WooCommerce

Lit les commentaires WooCommerce (`comment_type = 'review'`) avec note >= `min_rating` (défaut 4).
Paramètres shortcode : `title`, `max_reviews` (défaut 12), `min_rating` (défaut 4).
Charge également Swiper@11.

### nl_create_promotions_table() — Table promos

Crée `{prefix}_nl_weekly_promos` via `dbDelta()`.
Déclenchée uniquement sur le hook `after_switch_theme` (évite dbDelta à chaque chargement).

### Swiper@11

Enqueué depuis CDN jsDelivr (`cdn.jsdelivr.net/npm/swiper@11`).
Version épinglée : `11.0.0`.

**Why:** Swiper est chargé depuis CDN (jsDelivr) car il est utilisé dans des shortcodes optionnels. L'audit a validé cette approche (version épinglée).

Voir aussi : [[design-system-maison-nl]], [[catalogue-produits]]
