---
name: pixel-art
description: Dessiner ou retoucher le pixel art de Vigilante généré en PHP — sprites du héros, des ennemis et des boss, armes, objets, ou décors SVG procéduraux (immeubles, ciel, rue). À utiliser pour toute demande de sprite, couleur, palette, nouveau personnage, nouvel élément de décor, ou quand un test snapshot échoue.
---

# Pixel art : sprites et décors

Références : `docs/content.md#dessiner-en-pixel-art`, `docs/php.md` (namespaces `PixelArt`, `Sprite`, `Scene`).

## Principes

- Un sprite = liste de chaînes, **1 caractère = 1 pixel**, `.` = transparent, chaque lettre = une couleur de la palette.
- `Compositor::compose()` empile des `Layer` (lignes + position `x`/`y` + `recolor(map)`) et détoure chaque calque d'un contour `K`.
- Tous les personnages partagent `Character\Skeleton` (jambes, bras, cycle de marche) : on ne dessine que tête, torse et accessoires, avec les lettres génériques (`S/s` peau, `C/c` haut, `A/a/v` pantalon, `O/o/q` chaussures, `X` semelle, `H/h` manches, `D` ceinture). Repère : regarde à droite, tête vers y = 4, torse y = 16, jambes y = 28, largeur ~23 px.
- Armes : `WeaponCatalog::get()` — `LineWeapon` (trait, segments relatifs à la main pour `walk`/`windup`/`strike`) ou `HeldSprite` (objet, positions relatives à la main ; `strike: null` = lancé). Couleurs `N/n M/m L j P/p`.

## Recettes

- **Retoucher un boss** : `src/Sprite/Boss/<Nom>.php` (`palette()`, `head()`, `torso()`, options `armColors()`, `weapon()`, `back()`, `over()`, `front()`).
- **Nouveau boss** : nouvelle classe `final` qui étend `BossDesign`, ajoutée à `BossRoster::sheets()`, référencée par `boss.sprite` dans `config/levels.php`.
- **Nouvel ennemi** : `EnemyRoster::enemies()` → `Skinheads::look([...couleurs], WeaponCatalog::get('…'))` ou `Masculinists::look(...)`, + `config/game.php`.
- **Objet / bonus / projectile** : `Sprite\PropCatalog::SPRITES` (palette commune `PALETTE`).
- **Décor** : un élément = un `Painter` (`src/Scene/Painter/`), appelé depuis le plan concerné (`src/Scene/Layer/`). Utiliser le `SeededRandom` du plan, jamais `mt_rand`/`rand`. Couleurs partagées dans `Scene\Palette`.

## Vérifier (obligatoire)

```bash
composer test                                   # quel snapshot a changé ?
node tools/screenshot.mjs <niveau 1-9|title|peace> 5 /tmp/apercu.png   # REGARDER l'image produite
UPDATE_SNAPSHOTS=1 composer test                # une fois le rendu validé
composer check
```

Un snapshot qui change alors qu'on ne voulait pas changer le dessin = régression : le plus souvent un tirage aléatoire ajouté, supprimé ou déplacé dans un plan (l'ordre des tirages fait le décor), ou une palette fusionnée dans le mauvais sens (`$a + $b` garde les clés de `$a`).

## Pièges

- `BossDesign` : `SKIN + palette()`, la peau commune l'emporte (voulu pour l'instant, voir `docs/php.md`).
- Les clés numériques de palette (`'1'`) deviennent des entiers PHP.
- Les héros (Pete `HeroParts`, VigiBapt `BaptParts`, tous deux `HeroLook`) utilisent `1 2 3 4` pour les couleurs des armes ramassées (`HeroParts::WEAPON_MAP`) car `M m L j` sont pris par la casquette de Pete.
- Les boss sont agrandis par `scale` côté JS : dessiner à la taille normale.
