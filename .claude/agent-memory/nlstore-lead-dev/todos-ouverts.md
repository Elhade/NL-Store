---
name: todos-ouverts
description: TODOs critiques non encore faits — hero image, visuels produits, réseaux sociaux footer
metadata:
  type: project
---

## TODOs ouverts — post merge aa893a7 (2026-06-28)

### Hero image composite (bloquant pour le visuel final)

- Le design maquette prévoit un hero avec une composition (produits sur soie/tissu luxueux).
- Actuellement : fond = `assets/imgs/cat-parfums.jpeg` (photo générique via `--nl-hero-bg` dans `template-accueil.php`).
- **Action** : créer/déposer une image montage dans `assets/imgs/` et mettre à jour la variable CSS `--nl-hero-bg` dans `template-accueil.php`.

### Images produits (bloquant pour la boutique)

- Les visuels sources des parfums Collection Privée ont des bandeaux dorés incrustés (nom du parfum en surimpression). Ces visuels ne conviennent pas tels quels pour la boutique WooCommerce.
- **Action** : obtenir des visuels nets (fond uni ou suppression bandeaux) pour chaque produit.
- Les images de catégories WooCommerce sont en fallback thème (assets/imgs) — convenables mais non idéales.
- **Action** : uploader des images propres pour chaque catégorie dans WP Admin > Produits > Catégories si nécessaire.

### Réseaux sociaux footer (non bloquant mais visible)

- Le shortcode `[nl_footer]` est fonctionnel. Coordonnées MADI ALI / SIRET / phone / email / whatsapp sont renseignées dans `nl_company_info()`.
- **Manquant** : URL Instagram et Facebook (champs `instagram` et `facebook` dans `nl_company_info()` retournent des chaînes vides — les icônes ne s'affichent pas).
- **Action** : renseigner les URLs réelles via un filtre `nl_company_info` ou directement dans `functions.php`.

### Déploiement — FAIT

- Branche `feature/templating` mergée sur `main` (commit `aa893a7`).
- Le déploiement FTP prod se déclenche automatiquement au merge sur main.
- Ce TODO est clos.

**Why:** Ces points sont les seuls bloquants ou manquants entre l'état code actuel et un site visuellement complet en production.

Voir aussi : [[fonctions-helpers]], [[catalogue-produits]], [[stack-architecture]]
