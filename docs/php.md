# Référence PHP

Tout le code PHP vit dans `src/` sous le namespace `Vigilante\` (autoload PSR-4 par Composer). Il ne fait qu'une chose : **produire des données et du SVG** à partir de l'album et de `config/`. Aucun état global, aucune base de données.

## Conventions

- **PSR-4** (`Vigilante\` → `src/`), **PSR-12** (`composer lint`), `declare(strict_types=1)` partout, **PHPStan niveau 6** (`composer analyse`).
- Classes `final` par défaut ; objets valeur `readonly` (`Track`, `Theme`, `Layer`, `EnemyLook`…) ; énumérations pour les choix fermés (`Sky`, `Celestial`, `Pose`, `Stance`).
- Un fichier = une responsabilité, court (≈ 150 lignes au plus ; les fichiers de pixel art peuvent être plus longs).
- Les dépendances sont injectées par le constructeur ; seul `Vigilante\Application` sait comment tout assembler.
- Les fonctions pures et sans état (dessin de pixels, rendu SVG) sont des méthodes statiques de classes `final`.
- Le contenu (chiffres du gameplay, niveaux, scénario) est dans `config/`, pas dans le code.
- Commentaires en français, uniquement pour expliquer le *pourquoi* ou une intention de game design.

## Cycle d'une requête

```
index.php ─► bootstrap.php ─► new Application(racine)
         └─► Application::page()->render()          → templates/page.php
scene.php ─► SceneController::handle($_GET)          → SceneCatalog::level(n) | peace()
tools/build.php ─► StaticSiteBuilder::build()        → index.html + scenes/*.html
```

`bootstrap.php` charge `vendor/autoload.php` (il faut avoir lancé `composer install`) et renvoie l'`Application`.

## Carte des namespaces

### `Vigilante\` (racine)

| Classe | Rôle |
|---|---|
| `Application` | Composition root : crée chaque service une seule fois, à la demande (`config()`, `album()`, `props()`, `levels()`, `scenes()`, `gameData()`, `page()`). |

### `Album` — lire l'album

| Classe | Rôle |
|---|---|
| `AlbumLoader` | Charge `paroles.md` d'un dossier d'album et trouve les fichiers audio. |
| `LyricsParser` | Analyse le Markdown (titre, année, morceaux, paroles, liens). Format dans [content.md](content.md#les-paroles). |
| `AudioLocator` | Trouve le fichier audio d'un morceau par son numéro (`… - 03 …`), MP3 de préférence. |
| `PhraseExtractor` | Découpe les paroles en phrases courtes en majuscules (graffitis, cris), sans les phrases exclues. |
| `Album`, `Track`, `Link` | Objets valeur. `Link` est sérialisable en JSON. |

### `PixelArt` — le moteur de pixel art

| Classe | Rôle |
|---|---|
| `Layer` | Calque : lignes de texte, position `x`/`y`, recoloration `map`. `shift()`, `recolor()`, `Layer::at()`. |
| `Compositor` | `compose(largeur, hauteur, calques)` : empile les calques et détoure chacun d'un contour `K` (look « Metal Slug »). |
| `Line` | `Line::between(x0, y0, x1, y1, couleur, ombre)` : trait de 2 px (armes longues, katana). |
| `SvgRenderer` | `render(grille, palette, x, y)` : grille → `<rect>` SVG, pixels identiques fusionnés. |
| `Grid` | Grille vide, rognage des colonnes vides. Constante `Grid::EMPTY = '.'`. |

### `Scene` — les décors SVG

| Classe | Rôle |
|---|---|
| `SceneRenderer` | Assemble un décor : fond fixe, puis trois plans en parallaxe (skyline, immeubles, rue) + voile. |
| `Theme` | Ambiance d'un niveau (graine, ciel, astre, couleur d'accent, enseignes, chaos, ville libérée, data center, pluie, brouillard). `Theme::fromArray()` lit `config/levels.php`. |
| `Sky`, `Celestial` | Enums : couleurs de chaque ciel ; soleil, lune ou rien. |
| `Screen` | Constantes `WIDTH` (320), `HEIGHT` (180), `GROUND` (140). |
| `Palette` | Couleurs partagées (`PAPER`, `INK`, `CONCRETE`…). |
| `Svg` | Constructeurs de balises : `rect()`, `text()`, `opacity()`, `blink()`, `animated()`. |
| `Layer\SceneLayer` | Interface d'un plan : `render(): string`. |
| `Layer\BackgroundLayer` | Ciel, trame de points, étoiles, lueur d'incendie, astre. |
| `Layer\SkylineLayer` | Gratte-ciels lointains et fumées (graine + 1). |
| `Layer\HazeLayer` | Voile atmosphérique devant la skyline. |
| `Layer\BuildingsLayer` | Rangée d'immeubles (graine + 2), dessinés par `BuildingPainter`. |
| `Layer\StreetLayer` | Trottoir, route, lampadaires, jardins ou ruines, accessoires (graine + 3). |
| `Painter\BuildingPainter` | Un immeuble : mur, toit, étages, incendies, rez-de-chaussée, enseigne. |
| `Painter\WindowPainter` | Fenêtres (éteintes, allumées, volets, brisées, fleuries) et baies de serveurs. |
| `Painter\FacadePainter` | Rez-de-chaussée, affiche, escalier de secours, enseigne ou graffiti. |
| `Painter\RooftopPainter` | Château d'eau, climatiseur. |
| `Painter\CelestialPainter` | Soleil rayé et lune à cratères. |
| `Painter\LampPainter`, `Painter\FirePainter` | Lampadaire et cône de lumière ; feu animé en CSS. |
| `Painter\DecorSprites` | Petits sprites du décor (feu, épave, baril, gravats, arbre) et leurs palettes. |

### `Sprite` — les personnages et objets

| Classe | Rôle |
|---|---|
| `CharacterCatalog` | `all()` : toutes les animations envoyées au JS (`hero`, ennemis, `tough1-3`, `hero-<arme>`, boss, `pow`, `anchor`, `weaponIcons`). |
| `PropCatalog` | Accessoires du trottoir, projectiles et bonus (palette + grilles). |
| `SpriteSheet` | Palette + animations (`idle`, `walk`, `attack`, `dead`, `skate`, `jump`, `kick`…). |
| `Pose` | Enum `Walk` / `Windup` / `Strike` : position d'une arme tenue. |
| `Character\Skeleton` | Squelette commun : jambes, bras, cycle de marche, ancrage, positions des mains. |
| `Character\HeroParts`, `HeroFrame`, `HeroBuilder`, `Stance` | Le héros : pièces, une image, toutes les animations (avec katana ou arme ramassée), gardes. |
| `Character\EnemyLook` | Apparence d'un ennemi : palette, tête, torse, couleurs des bras, arme, calques `back`/`over`/`front`. |
| `Character\EnemyFrame`, `EnemyBuilder` | Une image d'ennemi ; toutes ses animations. |
| `Character\Skinheads`, `Masculinists` | Les deux gangs de base : `look(couleurs, arme)`. |
| `Character\EnemyRoster` | Les déclinaisons d'ennemis des vagues et les gros durs du Wall of Death. |
| `Character\Hostage` | L'otage ligoté. |
| `Boss\BossDesign` | Classe abstraite d'un boss : `palette()`, `head()`, `torso()` + options (`armColors()`, `weapon()`, `back()`, `over()`, `front()`). |
| `Boss\Manager` … `Boss\Mask` | Un boss par morceau. `Boss\Nightmare` réutilise le sprite du héros avec une autre palette. |
| `Boss\BossRoster` | Les boss indexés par leur identifiant de sprite (`config/levels.php` → `boss.sprite`). |
| `Weapon\Weapon` | Interface : `layer(Pose, main): ?Layer`. |
| `Weapon\LineWeapon`, `Weapon\HeldSprite` | Arme tracée d'un trait / objet tenu en main, positions relatives à la main pour chaque pose. |
| `Weapon\WeaponCatalog` | Toutes les armes (`get('bat')`), couleurs, armes ramassables, icônes au sol. |

### `Level` — les niveaux

| Classe | Rôle |
|---|---|
| `LevelFactory` | Un niveau JS par morceau (`build(Track)`), thème et graffitis d'un morceau. `LENGTH` = longueur d'une rue. |
| `WaveGenerator` | Trois vagues d'ennemis (déblocage progressif des types), puis le boss. Reproductible. |
| `SceneCatalog` | Décor SVG d'un niveau (`level(n)`) ou de la ville libérée (`peace()`). |

### `Game`, `View`, `Http`, `Build`, `Support`

| Classe | Rôle |
|---|---|
| `Game\GameData` | Le JSON envoyé au JavaScript (voir [architecture.md](architecture.md#ce-que-php-envoie-au-javascript)). |
| `View\PageRenderer` | Rend `templates/page.php` (borne, décor du niveau 1, données). |
| `View\Template` | Rend un gabarit PHP avec ses variables. |
| `View\AssetVersioner` | `?v=<date>` sur les fichiers et import map des modules JS (cache navigateur). |
| `Http\SceneController` | `scene.php?level=N|peace`. |
| `Build\StaticSiteBuilder` | Écrit `index.html` et `scenes/*.html` pour GitHub Pages. |
| `Support\ConfigRepository` | Lit les fichiers de `config/` (une seule fois chacun). |
| `Support\SeededRandom` | Hasard reproductible (Mt19937, même suite que `mt_rand`) : `int()`, `chance()`, `oneIn()`, `pick()`, `tenths()`. |

## Déterminisme : la règle d'or des décors

Un décor dépend de **l'ordre exact des tirages** de son `SeededRandom`. Ajouter, retirer ou déplacer un tirage dans un plan change toute la suite du plan (mais pas les autres plans, qui ont chacun leur graine). C'est voulu quand on retouche un décor ; c'est un bug sinon. Les tests `SceneSnapshotTest` le détectent : après une retouche volontaire, régénérer les empreintes avec `UPDATE_SNAPSHOTS=1 composer test`.

## Étendre

- **Nouvel élément de décor** : un `Painter` (classe `final readonly`, dépendances au constructeur) appelé depuis le plan concerné ; un nouveau plan implémente `SceneLayer` et s'ajoute dans `SceneRenderer::render()`.
- **Nouvelle arme** : une entrée dans `WeaponCatalog::get()` (`LineWeapon` ou `HeldSprite`) ; si le héros peut la ramasser, l'ajouter à `PICKABLE` et à `config/game.php` (`weapons`).
- **Nouveau boss** : une classe qui étend `BossDesign`, ajoutée à `BossRoster`. Voir [content.md](content.md#ajouter-un-boss).
- **Nouvel ennemi** : un `look()` dans `EnemyRoster::enemies()` + ses caractéristiques dans `config/game.php`. Voir [content.md](content.md#ajouter-un-ennemi).

## Particularité conservée

`BossDesign` fusionne `SKIN + palette()` : les couleurs de peau `S`/`s` communes à tous les boss **l'emportent** sur celles d'un boss. Les teintes de peau prévues pour le Troll (visage éclairé par l'écran) et le Dealer ne s'appliquent donc pas. C'est le comportement d'origine, conservé à l'identique par le refactoring ; pour l'activer, inverser la fusion (`$this->palette() + self::SKIN`) puis régénérer les snapshots.
