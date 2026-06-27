---
name: todos-ouverts
description: TODOs critiques non encore faits — hero image, visuels produits, footer coordonnées, déploiement
metadata:
  type: project
---

## TODOs ouverts — post v3.0.0

### Hero composité (bloquant pour le visuel final)

- Le design maquette prévoit un hero avec une composition (produits sur soie/tissu luxueux).
- Actuellement : fond = `assets/imgs/cat-parfums.jpeg` (photo generique via `--nl-hero-bg`).
- **Action** : créer/déposer une image montage dans `assets/imgs/` et mettre à jour la variable CSS `--nl-hero-bg` dans `template-accueil.php`.

### Images produits et catégories (bloquant pour la boutique)

- Les visuels sources des parfums Collection Privée ont des bandeaux dorés incrustés (nom du parfum en surimpression). Ces visuels ne conviennent pas tels quels pour la boutique WooCommerce.
- **Action** : obtenir des visuels nets (fond uni ou suppression bandeaux) pour chaque produit.
- Les images de catégories WooCommerce sont absentes → le template tombe sur les fallback thème.
- **Action** : uploader des images propres pour chaque catégorie (`bebe`, `parfums`, `vetements`, `hygiene`) dans WP Admin > Produits > Catégories.

### [nl_footer] — coordonnées à renseigner

- Le shortcode `[nl_footer]` est implémenté et les mentions légales MADI ALI / SIRET sont correctes.
- **Manquant** : `phone`, `email`, `whatsapp`, `instagram` (URL réel), `facebook` (URL réel) — actuellement vides ou `#`.
- **Action** : ajouter un filtre `nl_company_info` dans `functions.php` (ou dans un fichier de config) pour renseigner ces valeurs.
- **Action** : placer `[nl_footer]` dans le Footer Builder d'Astra (widget HTML/shortcode).

### Déploiement — merge feature/templating → main

- Le merge de `feature/templating` vers `main` déclenche automatiquement le déploiement FTP prod.
- **Action** : valider visuellement sur staging avant le merge.

**Why:** Ces points sont les seuls bloquants entre l'état actuel (code complet v3.0.0) et un site fonctionnel en production.

Voir aussi : [[fonctions-helpers]], [[catalogue-produits]], [[stack-architecture]]
