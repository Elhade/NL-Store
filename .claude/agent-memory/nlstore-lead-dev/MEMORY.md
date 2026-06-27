# Mémoire — nlstore-lead-dev

Index des entrées mémoire. Max 200 lignes. Une ligne par entrée.

- [Stack & architecture](stack-architecture.md) — Thème enfant Astra v3.0.0, fichiers clés, CI/CD FTP : merge main = déploiement prod automatique, branche active feature/templating
- [Design system Maison NL](design-system-maison-nl.md) — Tokens CSS `--nl-*` (palette noir & or, espacement 8pt, typo fluide clamp, easings), composants hero/cartes/footer/scrollbar dorée
- [WooCommerce — thème](woocommerce-theme.md) — Déclinaison boutique/fiche/panier/checkout/compte ; correctif critique : `.amount { color: inherit }` pour prix invisibles sur cartes ivoire
- [Fonctions & helpers](fonctions-helpers.md) — nl_icon() SVG Lucide auto-hébergé (10 icônes), [nl_footer], [nl_promo_banner], [nl_weekly_promos_carousel], [nl_testimonials_carousel], scroll reveal IntersectionObserver
- [Catalogue produits](catalogue-produits.md) — Collection Privée Édition La Dorée Paris 50ml (~15 €) : Aïsha, Baccara, Moula, Scandal F/H, Invicts, Kirké, Coco Vanille, Crème Brûlée + brumes Kenzie/Yara
- [Audit sécurité](audit-securite.md) — nlstore-auditor : 0 critique ; correctifs appliqués (Lucide auto-hébergé, Swiper épinglé, dbDelta) ; faux positif : get_page_by_path() non déprécié
- [TODOs ouverts](todos-ouverts.md) — Hero image montage manquante, visuels produits nets requis, [nl_footer] coordonnées à renseigner via filtre nl_company_info, placement Footer Builder Astra
