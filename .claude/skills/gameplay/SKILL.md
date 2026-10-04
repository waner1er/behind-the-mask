---
name: gameplay
description: Modifier les mécaniques du jeu Vigilante en JavaScript — commandes et attaques du héros, IA des ennemis et des boss, bonus, Wall of Death, caméra et vagues, écrans (accueil, menus, game over), scènes animées, son, HUD, mode démo. À utiliser pour tout changement de comportement en jeu ou bug JavaScript.
---

# Gameplay (JavaScript)

Référence : `docs/javascript.md` (carte des modules, ordre d'une image, états, `GameState`).

## Se repérer

- `js/Game.js` assemble tout ; chaque système reçoit `game` et passe par lui : `game.state`, `game.input`, `game.sfx(nom)`, `game.shout(texte, x, y, couleur)`, `game.spawn(type, x, y)`, `game.combat`, `game.pickups`…
- Un écran = un `Mode` (`js/modes/`) ; la partie = `PlayingMode` → `PlayerController`, `EnemyAI` (+ `BossAI`), `Projectiles`, `Pickups`, `Hostages`, `WallOfDeath`, `Camera`.
- Coups et K.O. : `world/Combat.js`. Explosions : `world/Explosions.js`.
- Réglages chiffrés : `js/config.js` (fenêtres d'attaque, skate, saut, vinyles, vies) ; caractéristiques des ennemis et armes : `config/game.php` (PHP).
- Un `Fighter` a un `state` (`idle`, `walk`, `attack`, `hurt`, `dead`, `jump`, `skate`, `lunge`, `charge`…) et `t` = images dans cet état. Changer d'état : `fighter.setState('…')`.

## Conventions

- Une classe par fichier, membres privés `#`, fichiers courts ; un nouveau système est créé dans le constructeur de `Game` et appelé depuis le mode concerné.
- Durées en images (60/s), distances en pixels d'écran (320 × 180), `dir` = 1 (droite) ou -1.
- Le rendu ne décide de rien : la logique vit dans `update()`, `render/` ne fait que dessiner l'état.
- Pas de bundler ni de dépendance : modules ES natifs. Un nouveau fichier est servi automatiquement (l'import map PHP liste `js/**/*.js`).
- Nouveau bruitage : recette dans `audio/sounds.js` (`(synth, ...args) => …`), joué par `game.sfx('nom')`.
- Nouveau plan de scène animée : classe qui étend `Director` dans `js/story/directors/`, enregistrée dans `index.js`.

## Vérifier

```bash
npm run lint:js
npm run test:e2e        # la démo doit finir le jeu ; MORCEAUX, game over, sourdine
```

Pour tester à la main : `composer serve`, `http://localhost:8000/?debug`, puis dans la console `game.campaign.startLevel(n)`, `game.state.player.wods = 2`, `game.state.player.hp = 1`…

Si le changement touche le mode démo (cibles, déplacements), le test e2e de démo complète est le juge : un blocage = la démo ne revient jamais à l'accueil.

## Pièges

- `input.pressed` ne dure qu'une image (vidé en fin d'`update`) ; `input.held` = touche maintenue. En démo, `DemoPilot` remplit ces ensembles lui-même ; un vrai appui quitte la démo (`input.interceptor`).
- La caméra ne recule jamais : ne pas faire viser au héros (ou à la démo) quelque chose derrière le bord gauche.
- `state.cast` (figurants) est dessiné dans tous les modes : le vider quand on quitte une scène animée.
- Le contexte audio n'existe qu'après un geste du joueur ; `AudioEngine` ignore les appels avant.
