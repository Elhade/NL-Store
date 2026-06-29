---
name: decision-navigation-ajax
description: Décision architecturale tranchée — navigation SPA/AJAX/pjax refusée pour NL Store (WooCommerce + mobile Mayotte) ; alternatives recommandées
metadata:
  type: feedback
---

## Décision — Navigation AJAX (Barba.js / Swup / pjax) : REFUSÉE

**Date de décision :** 2026-06-29

**Verdict : Non, ne pas implémenter.**

### Raisons déterminantes

1. **Blocage JS certain** : toutes les fonctions de `nl_enqueue_interactions` (`nlReveal`, `nlParallax`, `nlSliders`, `nlWishlist`, `nlCatCarousel`) sont initialisées sur `DOMContentLoaded`. Cet événement ne se déclenche qu'une fois par chargement complet — avec navigation AJAX, tout cesse de fonctionner après la première transition. Swiper (carousels avis + promos) est aussi câblé sur `DOMContentLoaded`.

2. **Risque critique WooCommerce** : les nonces de sécurité (panier, checkout, coupon) sont générés côté PHP à chaque rendu. Une navigation AJAX injectant du HTML sans rechargement PHP peut servir des nonces périmés ou incohérents — les ajouts au panier et les passages en caisse peuvent échouer.

3. **Passerelles de paiement incompatibles** : les scripts des passerelles (Stripe, PayPal, etc.) s'initialisent à `DOMContentLoaded` et ne prévoient pas de réinitialisation dans un contexte AJAX. Cause numéro un d'échec de checkout sur WordPress SPA.

4. **Bénéfice réel faible** pour l'audience mobile Mayotte : la latence réseau et le poids des ressources dominent — le AJAX charge quand même les données depuis le serveur, la "fluidité" est illusoire sur connexion instable.

5. **Conséquences supplémentaires** : analytics faussés (pas de `pageview` auto), risques SEO sur `<head>` (title, meta, canonical), accessibilité à câbler manuellement (focus, aria-live), complexité de maintenance multipliée.

**Why:** Le ratio risque/bénéfice est défavorable sur ce stack. WooCommerce + nonces + JS custom sur DOMContentLoaded + hébergement mutualisé = terrain hostile pour une SPA.

### Alternatives validées (80% de fluidité, 5% du risque)

1. **instant.page** — précharge les pages au survol/au toucher, transparence totale pour WooCommerce et analytics. Script à auto-héberger dans `assets/`.
2. **Transitions CSS d'entrée de page** — animation `@keyframes` sur `body { animation: nl-page-in 0.25s ease-out; }`, zéro JS, effet immédiat.
3. **Optimisation LCP/images** — conversion WebP des images `assets/imgs/` + `fetchpriority="high"` sur le hero + lazy loading vérifié sur les cartes produit. Impact le plus fort sur la vitesse réelle mobile.

**How to apply:** Si la question est relancée, pointer vers cette décision. Ne pas implémenter de couche AJAX de navigation sans résoudre d'abord : (a) réécriture complète de `nl_enqueue_interactions` pour exposition d'une API `reinit()`, (b) exclusion stricte des pages WooCommerce (panier, checkout, compte, pages produit), (c) câblage analytics et focus accessibilité — ce qui annule la majeure partie du bénéfice.

Voir aussi : [[fonctions-helpers]], [[woocommerce-theme]], [[stack-architecture]]
