---
name: release
description: Vérifier puis publier le jeu Vigilante sur GitHub Pages — contrôles qualité (PSR-12, PHPStan, PHPUnit, snapshots, e2e), build statique de index.html et scenes/, compilation du SCSS, commit. À utiliser avant un commit important, pour « déployer », « publier », « mettre en ligne » ou « builder ».
---

# Vérifier et publier

Référence : `docs/workflow.md`.

GitHub Pages n'exécute pas PHP : la version en ligne est `index.html` + `scenes/*.html` générés par `tools/build.php` et **commités**, avec `build/style.css`.

## Étapes

1. Dépendances à jour : `composer install` et `npm install`.
2. Contrôles — tout doit passer :
   ```bash
   composer check        # phpcs PSR-12, PHPStan niveau 6, PHPUnit (snapshots compris)
   npm run lint:js
   npm run test:e2e
   ```
   Un snapshot en échec n'est **pas** à mettre à jour d'office : vérifier que le changement visuel est voulu (`node tools/screenshot.mjs …`), sinon corriger.
3. Styles, si `style.scss` a changé : `npm run css`.
4. Build : `composer build` → réécrit `index.html` et `scenes/` (les `?v=` changent à chaque build : normal).
5. Relire `git status` : seuls des fichiers attendus ; jamais `vendor/`, `node_modules/` ni de `.wav`.
6. Commiter le build avec les changements qui l'ont motivé. Pousser uniquement si l'utilisateur le demande.

## Pièges

- Oublier le build : le site en ligne garde l'ancienne version du jeu, même si le code a changé.
- Ne jamais corriger `index.html` ou `scenes/` à la main : corriger la source (`templates/`, `src/`, `config/`) puis rebuilder.
- Les fichiers audio sont des MP3 lourds : ne pas en ajouter sans raison, ne jamais commiter les WAV.
