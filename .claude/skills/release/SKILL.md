---
name: release
description: Vérifier puis publier le jeu Vigilante sur GitHub Pages — contrôles qualité (PSR-12, PHPStan, PHPUnit, snapshots, e2e), compilation du SCSS, build statique dans build/site/, push sur main (le workflow GitHub publie). À utiliser avant un commit important, pour « déployer », « publier », « mettre en ligne » ou « builder ».
---

# Vérifier et publier

Référence : `docs/workflow.md`.

GitHub Pages n'exécute pas PHP. La version en ligne est générée par le workflow `.github/workflows/pages.yml` à chaque push sur `main` : contrôles, tests e2e, `npm run css`, `composer build` (→ `build/site/`), puis publication. **Rien de généré n'est commité.**

## Étapes

1. Dépendances à jour : `composer install` et `npm install`.
2. Contrôles — tout doit passer (le workflow les relance et ne publie pas sinon) :
   ```bash
   composer check        # phpcs PSR-12, PHPStan niveau 6, PHPUnit (snapshots compris)
   npm run lint:js
   npm run test:e2e
   ```
   Un snapshot en échec n'est **pas** à mettre à jour d'office : vérifier que le changement visuel est voulu (`node tools/screenshot.mjs …`), sinon corriger.
3. Styles, si `style.scss` a changé : `npm run css` (`build/style.css` est commité, pour le développement local).
4. Vérifier le site statique si besoin : `composer build` puis `php -S localhost:8001 -t build/site`.
5. Relire `git status` : seuls des fichiers attendus ; jamais `vendor/`, `node_modules/`, `build/site/` ni de `.wav`.
6. Commiter. Pousser uniquement si l'utilisateur le demande ; ensuite, suivre le workflow *Pages* dans l'onglet *Actions*.

## Pièges

- Un workflow en échec = le site en ligne reste l'ancien : lire le journal de l'étape qui a cassé, corriger, pousser.
- Ne jamais commiter `index.html` ni `scenes/` à la racine : la page de développement est `index.php`.
- Les fichiers audio sont des MP3 lourds : ne pas en ajouter sans raison, ne jamais commiter les WAV.
