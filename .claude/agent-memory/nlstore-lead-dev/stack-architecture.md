---
name: stack-architecture
description: Stack technique du projet NL Store — thème enfant Astra, WordPress, WooCommerce, CI/CD FTP, cache-busting filemtime
metadata:
  type: project
---

## Stack & architecture

- **WordPress** avec **WooCommerce** (thème enfant Astra)
- **Thème parent** : Astra (dépendance déclarée dans `style.css` via `Template: astra`)
- **Thème enfant** : `nlstore-astra` (répertoire : `wp-content/themes/nlstore-astra/`)
- **Version constante** : `CHILD_THEME_NLSTORE_ASTRA_VERSION = '3.0.0'` — utilisée uniquement comme fallback, PAS comme version d'enqueue des CSS
- Dernier commit mergé sur main : `aa893a7`

## Fichiers clés du thème enfant

| Fichier | Rôle |
|---|---|
| `style.css` | Design tokens `--nl-*`, tous les styles front |
| `woocommerce.css` | Surcharge WooCommerce (boutique, fiche, panier, checkout, compte) |
| `functions.php` | Enqueues, helpers, shortcodes, admin promos |
| `template-accueil.php` | Template page d'accueil (hero, catégories, slider produits, promos) |
| `assets/imgs/` | Images fallback catégories (bebe, hygiene, parfums, vetements) |
| `assets/swiper/` | Swiper v11.2.10 auto-hébergé (JS + CSS) |
| `woocommerce/loop/add-to-cart.php` | Override template WooCommerce |

## CI/CD

- Branche `main` = déploiement FTP production automatique (GitHub Actions)
- Branche `feature/templating` mergée sur `main` au commit `aa893a7`
- La branche `feature/templating` est considérée **fermée** (merge effectué)

## Décision technique clé — Cache-busting par filemtime

**Problème antérieur** : `style.css` et `woocommerce.css` étaient enqueués avec `?ver=3.0.0` (constante figée). Toute mise à jour CSS déployée en prod était ignorée par les navigateurs/CDN jusqu'au vidage manuel du cache.

**Correctif** (`aa893a7`) : fonction `nl_asset_ver( $relative_path )` dans `functions.php` :

```php
function nl_asset_ver( $relative_path ) {
    $file  = get_stylesheet_directory() . '/' . ltrim( $relative_path, '/' );
    $mtime = file_exists( $file ) ? filemtime( $file ) : false;
    return $mtime ? (string) $mtime : CHILD_THEME_NLSTORE_ASTRA_VERSION;
}
```

`nl_asset_ver('style.css')` retourne le timestamp Unix de la dernière modification du fichier. Le `?ver=` change automatiquement à chaque déploiement FTP — sans aucune action manuelle.

**Why:** La version constante `3.0.0` ne changeait jamais entre déploiements, causant des problèmes de cache silencieux (utilisateurs voyant l'ancien CSS).

**How to apply:** Passer systématiquement `nl_asset_ver('chemin/relatif')` au 4e argument de `wp_enqueue_style()` pour tout asset thème. La constante reste en fallback si le fichier est introuvable sur le serveur.

Voir aussi : [[fonctions-helpers]]
