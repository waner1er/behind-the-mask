# Référence JavaScript

Le jeu est écrit en **modules ES natifs** (aucun bundler) et en classes. Point d'entrée : `js/main.js`, chargé par `<script type="module">`. PHP génère une *import map* qui ajoute `?v=<date>` à chaque module, pour que le navigateur recharge un module dès qu'il change.

## Conventions

- Une classe par fichier, nommée comme le fichier (`world/Camera.js` → `Camera`). Fichiers courts (≈ 200 lignes au plus).
- Membres privés en `#`, constantes de réglage en `static` ou dans `config.js`.
- Chaque système reçoit `game` dans son constructeur et y retrouve ce dont il a besoin (`game.state`, `game.input`, `game.sfx()`, les autres systèmes). C'est le seul couplage ; les systèmes ne s'importent pas entre eux.
- Unités : durées en **images** (60 par seconde), distances en **pixels d'écran** (320 × 180).
- Vérification syntaxique : `npm run lint:js`. Tests de bout en bout : `npm run test:e2e`.

## Démarrage

```
main.js
 ├─ new Game(données du #game-data)
 ├─ KeyboardControls, TouchStick (sticks), MobileGuard, audio.unlockOnGesture()
 └─ GameLoop(update, draw).start()   ← après le chargement de la police « Press Start 2P »
```

`index.php?debug` (ou `index.html?debug`) expose le jeu dans la console : `window.game` (par exemple `game.campaign.startLevel(8)`, `game.state`).

## Une image de jeu

`GameLoop` appelle `Game.update()` à pas fixe (1/60 s, rattrapage plafonné à 100 ms), puis `Game.draw()` une fois par rafraîchissement de l'écran.

`Game.update()` :

1. `tick` et `modeTimer` avancent ; en démo, `DemoPilot.play()` simule les touches ;
2. `M` coupe/rétablit le son ;
3. `modes[state.mode].update()` ;
4. ambiance (`Ambience`) : chaos (sauf accueil/menu/chargement), puis étincelles, textes et pluie ;
5. `Campaign.updateScore()` : vie bonus tous les 10 000 points, meilleur score ;
6. fin du message temporaire, panneau de commandes, `input.endFrame()`.

`Renderer.draw()` : parallaxe du décor SVG (`Backdrop.scroll`), puis sur le canvas (avec la caméra) : otages, bonus, (data center de l'intro), caisses « ? », personnages triés par `y` avec leurs ombres, projectiles, explosions, (figurants de la fin), notes, étincelles, textes ; enfin la météo, le flash blanc et le HUD.

## Modes (`state.mode`)

| Mode | Classe | Rôle |
|---|---|---|
| `title` | `modes/TitleMode` | Accueil : JOUER, MORCEAUX, DÉMO. |
| `select` | `modes/SelectMode` | Choix du morceau (décor et musique suivent). |
| `story` | `modes/StoryMode` | Intro ou fin, déroulée par `story/StoryPlayer`. |
| `loading` | `modes/Mode` | Chargement du décor d'un niveau (rien à faire). |
| `intro` | `modes/IntroMode` | Titre du niveau et premières paroles. |
| `playing` | `modes/PlayingMode` | La partie. |
| `clear` | `modes/ClearMode` | MISSION COMPLETE, puis niveau suivant. |
| `gameover` | `modes/GameOverMode` | START recommence le niveau. |

`modes/Campaign` enchaîne la partie : `playIntro()`, `startLevel(n)`, `levelClear()`, `nextLevel()`, `respawn()`, `updateScore()`.

## Carte des modules

| Dossier | Classe | Rôle |
|---|---|---|
| `js/` | `Game` | Assemble tous les systèmes ; `spawn()`, `sfx()`, `shout()`, `setMode()`, `goHome()`, `toggleMute()`. |
| | `config.js` | Réglages : fenêtres d'attaque, skate, saut, vinyles, vies, démo… |
| `core/` | `GameLoop` | Boucle à pas fixe. |
| | `GameState` | Tout l'état mutable de la partie (voir plus bas). |
| `input/` | `Input` | Touches maintenues (`held`) et appuyées cette image (`pressed`), `axisX`/`axisY`, `interceptor` (la démo s'interrompt au premier appui). |
| | `KeyboardControls` | Clavier et boutons de la borne (`data-key`). En duo, les pavés des joueurs (`DUO_KEYS`) vont à `game.pads[slot]`, traduits en touches du solo ; `game.inputOf(p)` donne les commandes d'un héros. |
| | `TouchStick` | Stick tactile ou dessiné → flèches. |
| | `MobileGuard` | Pas de zoom ni de défilement ; plein écran et paysage au premier appui. |
| `audio/` | `AudioEngine` | Contexte audio créé au premier geste, mixage, `play(nom)`, musique, sourdine. |
| | `Synth` | `tone()`, `noise()`, `scream()` (voix à formants) : tous les bruitages sont synthétisés. |
| | `sounds.js` | Recettes des bruitages (`hit`, `slash`, `wod`, `gameOver`…). |
| | `MusicPlayer` | Morceaux téléchargés, décodés et joués en boucle sans coupure. |
| `world/` | `Fighter` | Un personnage (héros, ennemi, boss, figurant) et sa machine à états. |
| | `Backdrop` | Décor SVG : chargement à la demande, cache, parallaxe. |
| | `Camera` | Avance vers la droite, se bloque à chaque vague, fait apparaître ennemis et boss. |
| | `Combat` | Coups du héros (`heroHits`), coups encaissés (`damagePlayer`), K.O. (`killEnemy`) et butin. |
| | `Pickups` | Bonus au sol : apparition, chute, ramassage. |
| | `Projectiles`, `Explosions` | Objets lancés ; explosions (le vinyle explose en notes de musique). |
| | `Hostages`, `MysteryBoxes` | Otages à libérer ; caisse « ? » contenant le Wall of Death. |
| | `WallOfDeath` | La charge des gros durs. |
| | `Ambience` | Braises, cendres, explosions au loin, pluie ; vieillissement des effets. |
| `actors/` | `PlayerController` | Commandes du héros (marche, katana, saut, vinyle, skate, Wall of Death). |
| | `EnemyAI` | File d'attaque, approche, ruée, lancer. |
| | `BossAI` | Attaques spéciales : `charge`, `throw`, `teleport`, `summon`. |
| | `DemoPilot` | Le « cerveau » de Pete en mode démo. |
| `story/` | `StoryPlayer` | Déroule les étapes de `config/story.php`. |
| | `Typewriter` | Texte lettre par lettre, `[PAUSE n]`. |
| | `Cast` | Figurants des scènes animées. |
| `story/directors/` | `Director` et un réalisateur par plan | `pete`, `villains`, `mask`, `go`, `boom`, `peace`, `credits` (registre : `index.js`). |
| `render/` | `Renderer` | Ordre de dessin de chaque image. |
| | `SpriteBank` | Grilles PHP → canvas (normal + flash), notes, icônes d'armes, citoyens. |
| | `FighterPainter` | Image d'animation selon l'état, chute au K.O., traînées de vitesse. |
| | `EffectPainter`, `WeatherPainter`, `StoryPainter`, `PixelBrush` | Effets, météo, décors de l'intro et de la fin, primitives (disque, ombre, cœur…). |
| `ui/` | `Hud`, `ControlPanel` | Affichage DOM (score, vies, barres, messages, dialogues, générique) ; joystick et boutons qui réagissent. |
| `util/` | `math.js`, `HiscoreStore` | `rand`, `clamp`, `pick`, `pad` ; meilleur score dans `localStorage`. |

## L'état (`GameState`)

| Champ | Contenu |
|---|---|
| `mode`, `modeTimer`, `tick` | Écran en cours, images depuis son début, images depuis le lancement. |
| `menu`, `selected`, `demo`, `duo` | Choix de l'accueil, morceau choisi, mode démo, partie à deux. |
| `levelIndex`, `level`, `cam`, `locked`, `waveIndex` | Niveau (données PHP), position de la caméra, caméra bloquée par une vague, vague suivante. |
| `score`, `hiscore`, `kills`, `lives`, `nextLife` | Compteurs ; score commun, `lives[slot]` par joueur. |
| `players`, `boss`, `enemies`, `toughGuys`, `wodPower` | Personnages en jeu ; `players` = les héros (`p.slot` 0 = Pete, 1 = VigiBapt), `nearestPlayer(x, y)` = celui que vise un ennemi. |
| `projectiles`, `pickups`, `boxes`, `pows` | Objets. |
| `cast`, `citizens`, `birds`, `fx` | Figurants des scènes animées. |
| `explosions`, `notes`, `particles`, `flashes`, `sparks`, `texts`, `rain`, `shake`, `flash` | Effets. |
| `messageUntil`, `lastEnemy`, `lastEnemyUntil` | Message temporaire ; ennemi dont la barre de vie s'affiche. |

## Les états d'un `Fighter`

| État | Qui | Sens |
|---|---|---|
| `idle`, `walk` | tous | Au repos, en marche. |
| `attack` | tous | Coup ; il touche entre `attackTiming.hitFrom` et `hitTo` (`config.js`, `ATTACK`). |
| `hurt`, `dead` | tous | Coup reçu (recul), K.O. (tombe, clignote puis disparaît). |
| `jump`, `skate`, `vinyl` | héros | Coup de pied sauté, attaque en glisse, lancer de vinyle. |
| `lunge-wind`, `lunge`, `throw` | ennemis | Élan puis ruée ; lancer d'arme ou de projectile. |
| `charge-wind`, `charge`, `vanish`, `summon` | boss | Attaques spéciales. |

`t` compte les images passées dans l'état courant (remis à 0 par `setState`), `anim` sert au cycle des animations.

## Ajouter…

- **Un mode** : une classe qui étend `Mode` (méthode `update()`), enregistrée dans `Game.modes`, atteinte par `game.setMode('nom')`.
- **Un système** : une classe `(game)` créée dans le constructeur de `Game`, appelée depuis le mode concerné (souvent `PlayingMode`).
- **Un bruitage** : une recette dans `audio/sounds.js`, jouée par `game.sfx('nom')`.
- **Un plan de scène animée** : une classe qui étend `Director` (`enter()`, `update()`), enregistrée dans `story/directors/index.js`, utilisée par `scene` dans `config/story.php`.
