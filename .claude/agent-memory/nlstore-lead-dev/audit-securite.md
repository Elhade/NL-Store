---
name: audit-securite
description: Résultat de l'audit nlstore-auditor — 0 critique, correctifs appliqués, faux positif écarté
metadata:
  type: project
---

## Audit nlstore-auditor — résultat

**Bilan** : 0 critique au dernier audit (post-correctifs).

### Correctifs appliqués suite à l'audit

| Commit | Correctif |
|---|---|
| `48be0c4` | Durcissement sécurité général (échappements, nonces) |
| `9a1330c` | Lucide auto-hébergé (suppression dépendance CDN unpkg non-épinglée) |
| Épinglage Swiper | Swiper version `11.0.0` épinglée sur jsDelivr (était non-épinglée) |
| dbDelta perf | `nl_create_promotions_table()` déplacée sur `after_switch_theme` uniquement |
| Enqueue Swiper | Swiper enqueué via `wp_enqueue_script()` (non plus chargé en dur dans le HTML) |

### Faux positif écarté

`get_page_by_path()` signalé comme déprécié par l'audit — **non confirmé** : la fonction est toujours valide dans WordPress. L'audit avait un référentiel erroné sur ce point.

**Why:** Documenter le faux positif pour éviter de re-ouvrir ce point à chaque audit ou review de code.

**How to apply:** Ne pas remplacer `get_page_by_path()` — son usage reste correct.

Voir aussi : [[fonctions-helpers]], [[stack-architecture]]
