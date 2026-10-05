# AGENTS.md

Guide pour les agents de code (et les humains pressés) qui travaillent sur ce dépôt.

## Le projet

*Vigilante – Behind the Mask* : beat'em up d'arcade en pixel art, un niveau par morceau de l'album, publié sur GitHub Pages.

- **PHP** (`src/`, namespace `Vigilante\`, PSR-4) **fabrique** : sprites en texte, décors SVG procéduraux, données du jeu en JSON.
- **JavaScript** (`js/`, modules ES, classes) **anime** : boucle à 60 images/s, IA, rendu canvas, son synthétisé.
- Ils ne communiquent que par les données (`#game-data` dans la page, décors chargés à la demande).

Documentation : `docs/architecture.md` (vue d'ensemble), `docs/php.md` et `docs/javascript.md` (références), `docs/content.md` (niveaux, boss, ennemis, sprites, scénario), `docs/workflow.md` (commandes, tests, publication), `docs/recalbox.md` (version borne OpenBOR).

## Commandes

```bash
composer install && npm install    # une fois
composer serve                     # http://localhost:8000 (?debug → window.game dans la console)
composer check                     # phpcs PSR-12 + PHPStan niveau 6 + PHPUnit
npm run lint:js                    # syntaxe des modules JS
npm run test:e2e                   # le jeu complet dans Chrome headless (~1 min)
UPDATE_SNAPSHOTS=1 composer test   # valider un changement VOULU de décor ou de sprite
node tools/screenshot.mjs 4 5 out.png   # capture du niveau 4 après 5 s de jeu (title | peace | 1-9)
composer build                     # index.html + scenes/ pour GitHub Pages
composer build:openbor             # build/openbor/Vigilante.pak pour la borne Recalbox (OpenBOR)
npm run css                        # style.scss → build/style.css
```

## Où est quoi

| Je veux… | Fichiers |
|---|---|
| régler un niveau, un boss (stats, attaque spéciale) | `config/levels.php` |
| régler les ennemis, les armes ramassables | `config/game.php` |
| changer l'intro, la fin, le générique | `config/story.php` (+ `js/story/directors/` pour un nouveau plan) |
| dessiner un boss / un ennemi / le héros | `src/Sprite/Boss/`, `src/Sprite/Character/` |
| ajouter une arme | `src/Sprite/Weapon/WeaponCatalog.php` |
| changer le dessin des décors | `src/Scene/Layer/`, `src/Scene/Painter/` |
| changer une mécanique de jeu | `js/actors/`, `js/world/`, `js/config.js` |
| changer un écran (accueil, menus, game over) | `js/modes/` |
| changer un bruitage | `js/audio/sounds.js` |
| changer la borne (HTML / styles) | `templates/page.php`, `style.scss` |
| adapter la version borne Recalbox (OpenBOR) | `src/Export/OpenBor/`, `docs/recalbox.md` |

## Règles

1. **Ne jamais éditer `index.html` ni `scenes/`** : générés par `composer build`, à commiter après le build seulement quand on publie.
2. **Les décors sont déterministes.** Chaque plan a son `SeededRandom` ; ajouter, retirer ou réordonner un tirage change le décor. Un snapshot qui casse = changement visuel : volontaire → `UPDATE_SNAPSHOTS=1 composer test` et commiter les `.sha1` ; sinon, c'est une régression.
3. **Le contenu va dans `config/`**, pas dans le code. Le code lit la config.
4. **PHP** : PSR-12, `declare(strict_types=1)`, classes `final`, objets valeur `readonly`, enums pour les choix fermés, dépendances injectées (seule `Vigilante\Application` assemble). Fichiers courts, une responsabilité.
5. **JavaScript** : une classe par fichier ; un système reçoit `game` et passe par lui (`game.state`, `game.sfx()`, `game.combat`…) ; durées en images, distances en pixels d'écran ; pas de bundler, pas de dépendance d'exécution.
6. **Pixel art** : 1 caractère = 1 pixel, `.` = transparent, `K` = contour automatique. Respecter les lettres du squelette commun (voir `docs/content.md`).
7. **Commentaires en français**, courts, pour le *pourquoi* ou l'intention de game design — pas de paraphrase du code.
8. Avant de rendre la main : `composer check`, `npm run lint:js`, et `npm run test:e2e` si le JS ou les données du jeu ont changé.

## Pièges connus

- `index.php` a besoin de `vendor/` : sans `composer install`, il répond « Dépendances manquantes ».
- Générer la page prend ~2,5 s (tous les sprites sont composés à chaque requête) : normal en développement, la production est statique.
- Les clés de palette numériques (`'1'`…`'4'` du héros) deviennent des entiers en PHP : typer `array<array-key, string>`.
- `BossDesign` fusionne `SKIN + palette()` : la peau commune l'emporte sur celle d'un boss (comportement d'origine, voir `docs/php.md`).
- Le son ne démarre qu'après un geste du joueur (politique des navigateurs) ; les tests e2e bloquent les fichiers audio.

## Skills

Des skills détaillent les tâches courantes (dans `.claude/skills/`) : `level-content` (niveaux, boss, ennemis, scénario), `pixel-art` (sprites et décors), `gameplay` (mécaniques JS), `release` (vérifier et publier).
