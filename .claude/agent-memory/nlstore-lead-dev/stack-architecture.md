---
name: stack-architecture
description: Stack technique du projet NL Store — thème enfant Astra, WordPress, WooCommerce, CI/CD FTP
metadata:
  type: project
---

## Stack & architecture

- **WordPress** avec **WooCommerce** (thème enfant Astra)
- **Thème parent** : Astra (dépendance déclarée dans `style.css` via `Template: astra`)
- **Thème enfant** : `nlstore-astra` (répertoire : `wp-content/themes/nlstore-astra/`)
- **Version courante** : 3.0.0 (design system « Maison NL », noir & or raffiné)
- Constante PHP : `CHILD_THEME_NLSTORE_ASTRA_VERSION` définie dans `functions.php`

## Fichiers clés du thème enfant

| Fichier | Rôle |
|---|---|
| `style.css` | Design tokens `--nl-*`, tous les styles front |
| `woocommerce.css` | Surcharge WooCommerce (boutique, fiche, panier, checkout, compte) |
| `functions.php` | Enqueues, helpers, shortcodes, admin promos |
| `template-accueil.php` | Template page d'accueil (hero, catégories, produits, promos) |
| `assets/imgs/` | Images fallback catégories (bebe, hygiene, parfums, vetements) |
| `woocommerce/loop/add-to-cart.php` | Override template WooCommerce |

## CI/CD

- Branche `main` = déploiement FTP production automatique (GitHub Actions)
- Branche de travail active : `feature/templating` (en cours de merge vers main)
- Merge `feature/templating` → `main` déclenche le déploiement prod

**Why:** Architecture thème enfant choisie pour isoler les personnalisations des mises à jour Astra sans patch du core.

**How to apply:** Toujours travailler dans `nlstore-astra/`. Ne jamais modifier le thème parent Astra directement.
