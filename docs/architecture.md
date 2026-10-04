# Architecture

*Vigilante – Behind the Mask* est un beat'em up d'arcade : un niveau par morceau de l'album, un boss au bout de chaque rue. Le projet est volontairement découpé en deux moitiés qui ne se parlent que par des **données** :

- **PHP fabrique** : il lit les paroles de l'album, compose tout le pixel art (sprites en texte, décors procéduraux en SVG) et assemble la configuration du jeu.
- **JavaScript anime** : il reçoit ces données en JSON, transforme les grilles de pixels en images et fait tourner le jeu à 60 images par seconde.

```
 medias/audio/…/paroles.md ─┐
 config/game.php ───────────┤                       ┌─► <svg> décor du niveau 1 (dans la page)
 config/levels.php ─────────┼─► PHP (src/) ─────────┼─► <script id="game-data"> JSON : niveaux, sprites, scénario…
 config/story.php ──────────┘   Vigilante\…         └─► scene.php?level=N  /  scenes/level-N.html (autres décors)
                                                                   │
                                                                   ▼
                                       JavaScript (js/) : Game → modes, systèmes, rendu canvas + parallaxe SVG
```

## Deux façons de servir le jeu

| | Développement | Production (GitHub Pages) |
|---|---|---|
| Page | `index.php`, générée à chaque requête | `index.html`, générée par `php tools/build.php` |
| Décors | `scene.php?level=N` | `scenes/level-N.html`, `scenes/level-peace.html` |
| Commande | `composer serve` (ou `php -S localhost:8000`) | `composer build`, puis commit et push |

GitHub Pages n'exécute pas PHP : **les fichiers générés (`index.html`, `scenes/`) sont commités**. La seule différence entre les deux versions est la clé `sceneUrl` des données du jeu (voir `Vigilante\Game\GameData`).

## Ce que PHP envoie au JavaScript

Le JSON de `#game-data` (construit par `Vigilante\Game\GameData`) contient :

| Clé | Contenu | Source |
|---|---|---|
| `width`, `height`, `floor` | écran 320 × 180, bande du sol où marchent les personnages | `Scene\Screen` |
| `levelLength` | longueur d'une rue (5 écrans) | `Level\LevelFactory::LENGTH` |
| `enemies`, `weapons`, `weaponDuration` | caractéristiques des ennemis et des armes ramassables | `config/game.php` |
| `levels[]` | un niveau par morceau : titre, audio, paroles, couleur, vagues, boss, caisse « ? », otages | `Level\LevelFactory` + `config/levels.php` |
| `story` | intro, fin et générique | `config/story.php` |
| `links`, `logo` | liens de l'album, logo | `paroles.md`, `config/game.php` |
| `sceneUrl` | modèle d'URL des décors (`%d` = numéro du niveau ou `peace`) | `GameData` |
| `sprites` | toutes les animations des personnages (`hero`, ennemis, boss, `hero-bat`…), `anchor`, `weaponIcons` | `Sprite\CharacterCatalog` |
| `items` | accessoires, projectiles et bonus | `Sprite\PropCatalog` |

Une grille de pixels est une liste de chaînes : **1 caractère = 1 pixel**, `.` = transparent, chaque lettre renvoie à une couleur de la palette du sprite.

## Le décor : parallaxe SVG

Chaque décor est un SVG de trois plans qui défilent à des vitesses différentes (`data-factor` 0.25, 0.6 et 1) devant un ciel fixe. Chaque plan fait exactement un écran de large et il est dessiné **deux fois côte à côte** : le JavaScript (`world/Backdrop.js`) le décale selon la caméra, modulo la largeur de l'écran, ce qui donne une rue infinie.

Les décors sont **procéduraux mais reproductibles** : la graine (`seed`) du thème du niveau donne toujours la même rue (`Support\SeededRandom`). Des tests « snapshot » le garantissent.

## Les personnages : canvas

Les acteurs sont dessinés sur un `<canvas>` en résolution × 2 (les boss grossissent par pas de 0,5 en gardant des pixels nets), triés par profondeur (`y`). Les sprites viennent de PHP sous forme de grilles et sont convertis une fois pour toutes en petits canvas (`render/SpriteBank.js`), en version normale et en version « flash » blanche (coup reçu).

## Pour aller plus loin

- [php.md](php.md) : référence du code PHP.
- [javascript.md](javascript.md) : référence du code JavaScript.
- [content.md](content.md) : ajouter un niveau, un boss, un ennemi, une arme, une scène.
- [workflow.md](workflow.md) : installation, tests, build et déploiement.
