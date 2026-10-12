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

GitHub Pages n'exécute pas PHP : le site publié est une version statique, **générée par GitHub à chaque push sur `main`** (`.github/workflows/pages.yml`). Rien à builder ni à commiter : pousser suffit.

Le workflow lance `composer check`, `npm run lint:js` et `npm run test:e2e`, puis `npm run css` et `composer build`, et publie `build/site/`. Si un contrôle échoue, rien n'est publié : le site en ligne reste le précédent (le relancer depuis l'onglet *Actions* après correction, ou pousser le correctif).

Prérequis, une fois : *Settings › Pages › Source* = **GitHub Actions**.

Pour voir le site statique en local, tel qu'il sera publié :

```bash
npm run css && composer build      # vide puis remplit build/site/ (non commité)
php -S localhost:8001 -t build/site
```

`build/site/` contient la page (`index.html`, `scenes/`) et tout ce qu'elle lit : `js/`, CSS, polices, logo, musiques de l'album (`Build\SiteBundleBuilder`, le même que pour l'app iOS).

## Préparer l'app iOS

```bash
npm run css          # si style.scss a changé : build/style.css est copié tel quel
composer build:ios   # vide puis remplit ios/www/ (≈ 30 Mo) et redessine l'icône de l'app
```

`ios/www/` contient la même chose que `build/site/` : tout ce que la page lit (version statique, `js/`, CSS, polices, logo, musiques de l'album) et rien d'autre : ni PHP, ni appel réseau. Xcode le copie tel quel dans l'app. Il n'est **pas commité** (`.gitignore`) : on le régénère avant chaque compilation de l'app.

Ensuite : ouvrir `ios/Vigilante.xcodeproj` dans Xcode et lancer (⌘R) sur un simulateur ou un iPhone branché. L'app Swift (`ios/Vigilante/`) ne connaît pas le jeu :

| Fichier | Rôle |
|---|---|
| `VigilanteApp.swift` | Plein écran, sans barre d'état ; session audio `.playback` (la musique joue même en mode silencieux). |
| `GameView.swift` | La `WKWebView` qui charge `app://localhost/index.html`. En Debug, inspectable depuis Safari › Développement. |
| `Assets.xcassets/AppIcon.appiconset/AppIcon.png` | L'icône, dessinée par `Build\AppIcon` à partir du sprite de Pete. Commitée ; elle ne change que si le sprite change. |
| `HapticsHandler.swift` | Reçoit `window.webkit.messageHandlers.haptics.postMessage(style)` (`js/input/Haptics.js`) et fait vibrer l'iPhone. |
| `AppSchemeHandler.swift` | Sert les fichiers de `www/` sous `app://` (en `file://`, WebKit bloque les modules ES et les `fetch()`). |

Le paysage, le nom affiché et l'identifiant (`fr.banvillet.vigilante`) sont dans les réglages de la cible (onglet *Build Settings*, clés `INFOPLIST_KEY_…`). Si `ios/www/` est vide, la compilation s'arrête avec un message qui rappelle la commande à lancer.

### Envoyer une version sur TestFlight

```bash
composer ios:testflight              # CSS + ios/www + archive + envoi à App Store Connect
sh tools/ios-testflight.sh --no-upload   # s'arrête à build/ios/Vigilante.ipa signé (vérification)
```

- Signature automatique avec le compte Apple configuré dans Xcode (équipe `GDFM8ML2BH`). L'archive est faite sans signature, puis l'export la signe pour l'App Store : aucun iPhone n'a besoin d'être enregistré sur le compte.
- Le numéro de build est la date (`202610121827`) : toujours croissant, rien à commiter. La version affichée (`1.0`) est `MARKETING_VERSION` dans le projet Xcode.
- Le questionnaire sur le chiffrement est déjà répondu (`ITSAppUsesNonExemptEncryption = NO`).
- Après l'envoi, Apple traite le build (10 à 30 min) ; il apparaît ensuite dans l'app TestFlight des testeurs internes, sans vérification par Apple.

## Structure du dépôt

```
index.php, scene.php     points d'entrée (développement)
bootstrap.php            autoload + Application
tools/build.php          site statique pour GitHub Pages → build/site/
config/                  contenu : réglages, niveaux, scénario
src/                     PHP (namespace Vigilante\)
templates/page.php       la borne d'arcade (HTML)
js/                      le jeu (modules ES)
style.scss → build/      styles de la borne
medias/                  audio, paroles, logo
tests/                   PHPUnit, snapshots, e2e
docs/                    cette documentation
ios/                     l'app iOS (projet Xcode + Swift)
build/site/              généré par tools/build.php (GitHub Pages, non commité)
ios/www/                 généré par tools/build-ios.php (app iOS, non commité)
build/ios/               archive et .ipa de tools/ios-testflight.sh (non commités)
```
