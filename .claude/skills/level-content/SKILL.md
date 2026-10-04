---
name: level-content
description: Ajouter ou modifier le contenu du jeu Vigilante sans toucher au moteur — un niveau ou un morceau de l'album, un boss (stats, attaque spéciale, réplique), un type d'ennemi, une arme, l'intro, la fin ou le générique. À utiliser dès qu'on parle de niveau, boss, ennemi, vague, difficulté, scénario, paroles ou générique.
---

# Contenu du jeu : niveaux, boss, ennemis, scénario

Référence complète : `docs/content.md`. Ce skill donne la marche à suivre.

## 1. Identifier où ça se règle

| Demande | Fichier |
|---|---|
| ambiance d'un niveau (ciel, chaos, couleur, enseignes, pluie, brouillard) | `config/levels.php` → `theme` |
| graffitis d'un niveau | `config/levels.php` → `tags` (+ phrases courtes des paroles) |
| boss : vie, vitesse, dégâts, attaque spéciale, fréquence, réplique, taille | `config/levels.php` → `boss` |
| ennemis : vie, vitesse, dégâts, portée, attaques, arme lâchée | `config/game.php` → `enemies` |
| armes ramassées par le héros | `config/game.php` → `weapons`, `weaponDuration` |
| quels ennemis apparaissent à quel niveau | `src/Level/WaveGenerator.php` → `UNLOCKS` |
| textes de l'intro / de la fin / générique | `config/story.php` |
| morceaux, paroles, liens | `medias/audio/Vigilante - Behind the Mask/paroles.md` |

## 2. Modifier

- Valeurs : durées en **images** (60 = 1 s), distances en **pixels** (écran 320 × 180), `scale` de boss en multiples de 0.5.
- `special` d'un boss : `summon`, `throw` (avec `projectile` : `grenade`, `bottle`, `phone`, `heart`…), `charge`, `teleport`.
- Nouveau boss ou ennemi : il faut aussi son sprite → suivre le skill `pixel-art` (classe dans `src/Sprite/Boss/` + `BossRoster`, ou `EnemyRoster`), puis la config.
- Nouveau plan de scène animée (`scene` inconnue dans `config/story.php`) : il faut un réalisateur JS → skill `gameplay`.
- Changer `seed` ou `theme` change le décor : c'est attendu.

## 3. Vérifier

```bash
composer check                       # la config est lue par les tests (boss sans sprite = échec)
UPDATE_SNAPSHOTS=1 composer test     # seulement si le décor ou les sprites ont changé VOLONTAIREMENT
node tools/screenshot.mjs <1-9> 5 /tmp/niveau.png   # puis regarder l'image
npm run test:e2e                     # la démo doit toujours aller au bout du jeu
```

Pour jouer un niveau précis : `composer serve`, ouvrir `http://localhost:8000/?debug`, puis `game.campaign.startLevel(n - 1)` dans la console.

## Pièges

- Un morceau sans entrée dans `config/levels.php` reprend la config du niveau 1.
- Le dernier morceau de l'album déclenche la scène de fin : ajouter un morceau après le Docteur Mask déplace la fin.
- Les textes affichés sont en majuscules ; la police ne connaît que les caractères latins courants.
- La difficulté monte aussi toute seule avec l'avancée dans l'album (`EnemyAI::damage`, nombre d'attaquants à partir du niveau 6).
