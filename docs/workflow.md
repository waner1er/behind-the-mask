# Développer, tester, publier

## Prérequis

- PHP ≥ 8.2 et [Composer](https://getcomposer.org/)
- Node.js ≥ 20 (SCSS et tests de bout en bout) et Google Chrome (tests de bout en bout)

```bash
composer install     # autoload PSR-4 + outils de qualité (vendor/, non commité)
npm install          # sass + playwright-core (node_modules/, non commité)
```

## Développer

```bash
composer serve       # le jeu sur http://localhost:8000, généré à la volée par PHP
npm run css:watch    # recompile build/style.css à chaque modification de style.scss
```

`index.php?debug` expose le jeu dans la console du navigateur (`window.game`) : `game.campaign.startLevel(8)` pour aller au dernier niveau, `game.state` pour inspecter la partie.

## Vérifier

| Commande | Ce qu'elle vérifie |
|---|---|
| `composer lint` | Style PSR-12 (`phpcs.xml.dist`). `vendor/bin/phpcbf` corrige automatiquement ce qui peut l'être. |
| `composer analyse` | PHPStan niveau 6 (`phpstan.neon.dist`). |
| `composer test` | PHPUnit : moteur de pixel art, lecture des paroles, vagues, hasard reproductible, **snapshots** des décors et des sprites. |
| `composer check` | Les trois à la suite. |
| `npm run lint:js` | Syntaxe de tous les modules JavaScript. |
| `npm run test:e2e` | Le jeu dans Chrome headless avec une horloge virtuelle (≈ 1 min) : la démo complète jusqu'au générique, le choix d'un morceau, le game over. `CHROME_PATH` pour un autre navigateur. |

### Snapshots

`tests/snapshots/*.sha1` contient l'empreinte de chaque décor et de tous les sprites. Si un test snapshot échoue :

- **le changement est voulu** (nouvelle couleur, sprite retouché, niveau modifié) : `UPDATE_SNAPSHOTS=1 composer test`, puis commiter les nouvelles empreintes avec le changement ;
- **il ne l'est pas** : un tirage aléatoire a été ajouté, retiré ou déplacé dans un décor, ou une donnée a changé par erreur (voir [php.md](php.md#déterminisme--la-règle-dor-des-décors)).

## Publier sur GitHub Pages

GitHub Pages n'exécute pas PHP : on génère une version statique **et on la commite**.

```bash
composer check && npm run test:e2e
composer build       # écrit index.html et scenes/level-N.html
git add index.html scenes && git commit -m "Build"
git push
```

Si `style.scss` a changé : `npm run css` avant le build (`build/style.css` est commité lui aussi).

Ne jamais modifier `index.html` ni `scenes/` à la main : ils sont écrasés à chaque build.

## Structure du dépôt

```
index.php, scene.php     points d'entrée (développement)
bootstrap.php            autoload + Application
tools/build.php          version statique (production)
config/                  contenu : réglages, niveaux, scénario
src/                     PHP (namespace Vigilante\)
templates/page.php       la borne d'arcade (HTML)
js/                      le jeu (modules ES)
style.scss → build/      styles de la borne
medias/                  audio, paroles, logo
tests/                   PHPUnit, snapshots, e2e
docs/                    cette documentation
index.html, scenes/      générés par tools/build.php (GitHub Pages)
```
