# Mémoire — nlstore-lead-dev

Index des entrées mémoire. Max 200 lignes. Une ligne par entrée.

- [Stack & architecture](stack-architecture.md) — Thème enfant Astra v3.0.0, fichiers clés, CI/CD FTP, cache-busting par filemtime (correctif majeur aa893a7), branche feature/templating mergée sur main
- [Design system Maison NL](design-system-maison-nl.md) — Tokens CSS `--nl-*` (palette noir & or, espacement 8pt, typo fluide clamp, easings), cartes catégories pleine image overlay, cartes produit noir/or, fonds animés par section, carousel mobile
- [WooCommerce — thème](woocommerce-theme.md) — Déclinaison boutique/fiche/panier/checkout/compte ; correctif critique : `.amount { color: inherit }` pour prix invisibles sur cartes ivoire
- [Fonctions & helpers](fonctions-helpers.md) — nl_asset_ver() cache-busting, nl_icon() SVG Lucide (15 icônes), [nl_footer], [nl_promo_banner], [nl_weekly_promos_carousel], [nl_testimonials_carousel], script inline nl-interactions (reveal, parallax, sliders, wishlist, carousel cat)
- [Cartes produit WooCommerce](fonctions-woo-cartes-produit.md) — Badge -X% (nl_sale_flash_percent), cœur wishlist localStorage (nl_wishlist_button), bouton panier carré, slider scroll-snap + flèches
- [Catalogue produits](catalogue-produits.md) — Collection Privée Édition La Dorée Paris 50ml (~15 €) : Aïsha, Baccara, Moula, Scandal F/H, Invicts, Kirké, Coco Vanille, Crème Brûlée + brumes Kenzie/Yara
- [Audit sécurité](audit-securite.md) — nlstore-auditor : 0 critique ; correctifs appliqués (Lucide auto-hébergé, Swiper épinglé puis auto-hébergé, dbDelta) ; faux positif : get_page_by_path() non déprécié
- [TODOs ouverts](todos-ouverts.md) — Hero image montage manquante, visuels produits nets requis, URLs réseaux sociaux manquantes dans nl_company_info() ; merge feature/templating FAIT
- [Décision — Navigation AJAX refusée](decision-navigation-ajax.md) — SPA/Barba.js/Swup incompatible avec ce stack (DOMContentLoaded non relancé, nonces WooCommerce, passerelles paiement) ; alternatives : instant.page + transitions CSS + WebP
