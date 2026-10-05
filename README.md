# Vigilante – Behind the Mask

Beat'em up façon borne d'arcade (esprit Metal Slug) autour de l'album **Behind the Mask** de Vigilante (2026).
Un niveau par morceau, le morceau en musique de fond et un boss dédié à la fin de chaque niveau.

**▶ Jouer : https://waner1er.github.io/behind-the-mask/**

## Commandes

Au clavier, ou sur mobile avec le stick et les boutons dessinés sur la borne (portrait ou paysage).

| Touche | Action |
|---|---|
| ← → ↑ ↓ | marcher |
| Espace | coup de katana |
| B | coup de pied sauté |
| V | lancer un vinyle (il explose en notes de musique) |
| Espace + V | WALL OF DEATH (trouvé dans une caisse « ? », une par niveau) |
| C | attaque en skate |
| Entrée | start / continuer |
| M | couper la musique |

**À deux** (menu « 2 JOUEURS », clavier AZERTY) : Pete (1P) et VigiBapt (2P, à la guitare) jouent en même temps, avec un score commun et des vies par joueur.

| Action | 1P · Pete | 2P · VigiBapt |
|---|---|---|
| marcher | Z Q S D | O K L M |
| frapper | V | , |
| skate | C | ; |
| vinyle | X | : |
| coup de pied sauté | W | ! |
| WALL OF DEATH | V + X | , + : |
| start | Entrée | Entrée |

## Comment c'est fait

- **PHP** (`src/`, namespace `Vigilante\`) génère tout le pixel art : les sprites sont décrits en texte (1 caractère = 1 pixel), les décors sont procéduraux (SVG), les niveaux viennent des paroles de l'album (`medias/audio/…/paroles.md`) et de `config/`.
- **JavaScript** (`js/`, modules ES) fait tourner le jeu à 60 images/s et synthétise les bruitages 16 bits.
- **SCSS** (`style.scss` → `build/style.css`) dessine la borne.

Documentation : [architecture](docs/architecture.md) · [référence PHP](docs/php.md) · [référence JavaScript](docs/javascript.md) · [modifier le contenu](docs/content.md) · [développer, tester, publier](docs/workflow.md).

## Lancer en local

```bash
composer install && npm install
composer serve                 # http://localhost:8000, généré à la volée par PHP
npm run css:watch              # styles de la borne
```

## Vérifier

```bash
composer check                 # PSR-12, PHPStan, PHPUnit (dont snapshots des décors et sprites)
npm run test:e2e               # le jeu en entier dans Chrome headless
```

## Publier (GitHub Pages)

GitHub Pages n'exécute pas PHP : on génère une version statique, qu'on commite.

```bash
composer build                 # écrit index.html et scenes/level-N.html
```

Musique et paroles © 2026 Vigilante.
