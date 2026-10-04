# Vigilante – Behind the Mask

Beat'em up façon borne d'arcade (esprit Metal Slug) autour de l'album **Behind the Mask** de Vigilante (2026).
Un niveau par morceau, le morceau en musique de fond et un boss dédié à la fin de chaque niveau.

**▶ Jouer : https://waner1er.github.io/behind-the-mask/**

## Commandes

| Touche | Action |
|---|---|
| ← → ↑ ↓ | marcher |
| Espace | coup de jo |
| B | coup de pied sauté |
| V | lancer une bombe |
| C | attaque en skate |
| Entrée | start / continuer |
| M | couper la musique |

## Comment c'est fait

- **PHP** génère tout le pixel art : les sprites sont décrits en texte (1 caractère = 1 pixel) dans `src/sprites/`, le décor est procédural (`src/Scene.php`), les niveaux viennent des paroles de l'album (`medias/audio/…/paroles.md`, lues par `src/Album.php`).
- **JavaScript** (`js/game.js`) fait tourner le jeu à 60 images/s ; `js/sfx.js` synthétise les bruitages 16 bits et joue la musique.
- **SCSS** (`style.scss` → `build/style.css`) dessine la borne.

## Lancer en local

```bash
php -S localhost:8000          # le jeu, généré à la volée par PHP
sass --watch style.scss build/style.css
```

## Publier (GitHub Pages)

GitHub Pages n'exécute pas PHP : on génère une version statique avant de pousser.

```bash
php tools/build.php            # écrit index.html et scenes/level-N.html
```

Musique et paroles © 2026 Vigilante.
