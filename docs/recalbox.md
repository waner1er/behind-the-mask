# La borne Recalbox (OpenBOR)

Le jeu web reste la version de référence. Pour la borne, `composer build:openbor` en fabrique la version arcade pour **OpenBOR**, le moteur de beat'em up intégré à Recalbox (Recalbox n'a pas de navigateur, et PiFBA / fba_libretro n'acceptent que les ROMs de vraies cartes d'arcade). Tout vient des mêmes sources que le web : `config/`, sprites et décors PHP, bruitages et scènes du JS.

```bash
composer build:openbor                 # ~6 min → build/openbor/recalbox/ (à copier sur la borne)
php tools/build-openbor.php --fast     # mise au point : réutilise captures, sons, décors et musiques
```

Il faut Node et Chrome (comme pour les tests e2e : décors, scènes et bruitages sont rendus par Chrome), `ffmpeg` avec libvorbis (musique en Ogg) et ImageMagick (`convert`, GIF animés). Rien de `build/openbor/` n'est commité.

## Installer sur la borne

1. Copier **le contenu** de `build/openbor/recalbox/` dans `share/roms/openbor/` (partage réseau `\\RECALBOX\share`, ou clé USB) : `Vigilante.pak`, `gamelist.xml` (nom, description, nombre de joueurs) et `media/images/Vigilante.jpg` (la pochette de l'album). Le `.pak` doit garder le nom `Vigilante.pak`, c'est celui de la fiche.
2. Menu Recalbox → *Options des manettes* : déclarer la manette 1 (joueur 1) et la manette 2 (joueur 2), si ce n'est pas déjà fait.
3. Redémarrer la borne (Recalbox relit `gamelist.xml` au démarrage) : *Vigilante - Behind the Mask* apparaît dans le système OpenBOR, avec sa pochette.

**Raspberry Pi 2** : le tableau de compatibilité de Recalbox ne liste plus le Pi 2 pour OpenBOR (Pi 0/1, 3, 4, 5). Vérifier que le système OpenBOR existe bien dans la version installée sur la borne ; un Pi 2 v1.2 a le même processeur que le Pi 3 et accepte l'image Pi 3.

## Les boutons

Recalbox transmet lui-même à OpenBOR le stick et les boutons de chaque joueur (`openborControllers.py` de Recalbox : A = ATTACK, B = ATTACK2, X = ATTACK3, Y = ATTACK4, L1 = JUMP, R1 = SPECIAL). Le module donne une action à **chaque** bouton, les mêmes pour Pete (1P) et VigiBapt (2P) :

| Bouton Recalbox | Dans Vigilante | Touche du web |
|---|---|---|
| stick | marcher | flèches |
| A | frapper (katana / guitare), ramasser un objet | ESPACE |
| B | coup de pied sauté | B |
| X ou R1 | attaque en skate | C |
| Y | lancer un vinyle | V |
| A + Y | WALL OF DEATH (si on a ramassé la caisse « ? ») | ESPACE + V |
| L1 | sauter (puis A en l'air : coup de pied) | — |
| START | démarrer, passer un plan de l'intro, rejoindre la partie (2P), continuer après un game over | Entrée |
| HOTKEY + START | quitter et revenir à Recalbox | — |

**Jetons** : 9 crédits par partie. Au GAME OVER, START dans les 10 secondes repart avec 3 nouvelles vies (un crédit en moins).

## Ce qui est comme dans le jeu web

- L'**intro** et la **fin** (scènes filmées dans le jeu web, texte en machine à écrire), le **générique** qui défile.
- Les **9 niveaux** : décors en parallaxe, musique de chaque morceau, titre de mission et paroles au départ, « WAVE n », vagues qui bloquent la caméra, paroles criées par les ennemis qui tombent.
- Tous les **ennemis** (coups, ruées, lancers, armes lâchées, bières), les **9 boss** agrandis comme sur le web, avec leur attaque spéciale (sbires, projectiles, charge, téléportation), « WARNING! » et leur réplique.
- **Vinyles** (5 au départ, 20 au plus), **otages** à libérer, **caisse « ? »** et **Wall of Death**, **armes** ramassables, **cœur** tous les 10 K.O., **vie en plus** tous les 10 000 points.
- Les **bruitages** du jeu web (enregistrés depuis son synthé), la police Press Start 2P, les menus en français.

## Ce qui diffère (moteur OpenBOR)

- Les objets se ramassent avec **A** (règle d'OpenBOR), pas en marchant dessus.
- Une arme ramassée se perd quand on est mis à terre (le web la reprend au bout de 15 s).
- Score par joueur (le web additionne les deux joueurs), menus et tableau des scores d'OpenBOR.
- L'IA d'OpenBOR choisit ses coups selon la distance : mêmes coups et mêmes délais moyens que le web, mais pas exactement les mêmes décisions.

## Comment c'est fait

`src/Export/OpenBor/`, assemblé par `ModuleBuilder`, et les scripts du jeu dans `openbor/scripts/` :

| Classe ou fichier | Rôle |
|---|---|
| `HeroModels`, `EnemyModels`, `BossModels`, `PropModels` | Les modèles : mêmes sprites, timings (`js/config.js`, images à 60/s → centièmes) et portées que le JS ; points de vie × 10. |
| `CharacterModel`, `ModelSheet`, `Animation`, `Frame`, `Hit` | Fiche d'un modèle, planche (agrandie au pixel près pour les boss, images couchées pour les chutes), animations, zones de coup. |
| `LevelExport` | Un niveau : plans, musique, apparitions triées par position de caméra, textes (scripts générés). |
| `StoryScene` | Intro (`prologue.txt`) et fin (`ending.txt`) : un GIF par plan, texte réécrit, générique. Pas `intro.txt` : le moteur le joue au démarrage. |
| `SoundBank` | Bruitages du web enregistrés en WAV, et sons au nom imposé par le moteur. |
| `SceneLayers`, `Quantizer` | Décors SVG découpés en plans, rendus par Chrome, ramenés à 255 couleurs. |
| `IndexedImage`, `PngEncoder`, `FontSheet`, `Latin1` | PNG 8 bits à palette (index 0 = transparent), polices (ASCII + Latin-1, ombre portée), textes en ISO-8859-1. |
| `SystemAssets`, `MenuTranslation`, `RecalboxGamelist` | Fichiers à chemin fixe (polices, ombres, écrans), menus en français, fiche et pochette Recalbox. |
| `PakWriter` | Archive `.pak` (format de borpak), identique d'un build à l'autre ; un fichier identique n'y est stocké qu'une fois. |
| `Toolchain` | Outils externes : `tools/rasterize-svg.mjs`, `tools/capture-story.mjs`, `tools/record-sounds.mjs` (Chrome), `ffmpeg`, `convert`. |
| `openbor/scripts/*.c` | Scripts OpenBOR : HUD (vinyles, Wall of Death), messages et cris, vie en plus, Wall of Death, attaques spéciales, cœurs. |

## Pièges du moteur de Recalbox

Fork OpenBOR v3.0 de 2020 (gitlab.com/Bkg2k/openbor, branche `recalbox-compliant`), repérés en le compilant et en le faisant jouer :

- `data/bgs/logo` et `data/bgs/titleb` sont **obligatoires** (arrêt sinon).
- PNG en palette **8 bits exactement** ; la case 0 de chaque police donne sa palette à toutes les lettres (vide : plantage).
- Un ennemi qui apparaît à moins de 30 px de l'écran **tombe du ciel**, sauf avec `nodropspawn 1` dans `models.txt`.
- Les apparitions d'un niveau sont lues **dans l'ordre** : chacune attend sa position de caméra (`at`), il faut les trier.
- Un modèle déclaré `know` n'est chargé que s'il est cité par un niveau : projectiles, bonus et entités créées par script doivent être en `load`.
- `spawn1`/`spawn2` : profondeur comptée depuis le haut de la zone de marche ; `coords` des ennemis : profondeur absolue. `z` se règle dans `levels.txt`.
- Le MP se recharge seul par défaut : `mpset max 2 0 0 0 0` le fige (il sert de stock de vinyles).
- Sans chrono : `settime 0` et `notime 1` dans chaque niveau.
- `alias` garde les guillemets : les noms affichés utilisent des espaces insécables (0xA0).
- Scripts : pas d'`changeopenborvariant("quake")` ; le temps compte 200 unités par seconde.
