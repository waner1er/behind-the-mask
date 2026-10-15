# Modifier le contenu du jeu

La plupart des changements de contenu se font **sans toucher au code** : dans `config/` et dans le fichier des paroles. Après chaque modification : `composer check` (et `UPDATE_SNAPSHOTS=1 composer test` si un décor ou un sprite a changé volontairement), essai dans le navigateur, puis `composer build` avant de publier (voir [workflow.md](workflow.md)).

## Les paroles

`medias/audio/Vigilante - Behind the Mask/paroles.md` est la source des niveaux : **un morceau = un niveau**, dans l'ordre du fichier.

```markdown
# Vigilante - Behind the Mask
Année: 2026

## 01. Walk Straight
Première ligne des paroles / deuxième phrase
...
---
## 02. Metal Slug (feat. Guillaume - Circles)
...
---
- [Bandcamp](https://…)
```

- `## NN. Titre (feat. Invité)` ouvre un morceau ; `---` le ferme.
- Les deux premières lignes s'affichent au début du niveau ; les phrases courtes (coupées sur les `/`) deviennent graffitis et cris des ennemis. `config/game.php` → `album.excluded` liste les phrases à ne jamais afficher.
- Le fichier audio est trouvé par son numéro : `… - 01 Walk Straight.mp3` dans le même dossier (MP3, sinon OGG, sinon WAV — les WAV ne sont pas commités).
- Les liens `- [Nom](https://…)` s'affichent dans le générique.

## Un niveau

Chaque morceau a son entrée dans `config/levels.php`, indexée par son numéro de piste :

```php
3 => [
    'theme' => [
        'chaos' => 0.25,          // 0 = ville intacte, 1 = apocalypse (feux, fumée, épaves, braises)
        'seed' => 303,            // graine du décor : change-la pour une autre rue
        'sky' => 'night',         // paper | dusk | grey | night (étoiles, fenêtres claires)
        'celestial' => 'moon',    // sun | moon | none
        'accent' => '#9b4dff',    // couleur d'accent (néons, soleil, HUD)
        'signs' => ['MOTEL', '24H', 'DINER', 'BAR'],
        'fog' => true,            // options : fog, rain, datacenter (baies de serveurs)
    ],
    'tags' => ['WAKE UP', 'TOO LATE'],   // graffitis prioritaires
    'boss' => [
        'scale' => 2,             // taille, multiple de 0.5
        'sprite' => 'nightmare',  // identifiant dans Sprite\Boss\BossRoster
        'name' => 'THE NIGHTMARE',
        'hp' => 42, 'speed' => 0.9, 'damage' => 14, 'reach' => 36,
        'special' => 'teleport',  // summon | throw (+ 'projectile') | charge | teleport | static | components, ou une liste
        'every' => 200,           // images entre deux attaques spéciales
        'line' => 'WE WERE BROTHERS IN THE SAME FIGHT',
        // option : garde. Au bout de 3 coups encaissés coup sur coup (moins de 90 images entre deux),
        // il ne bronche plus et repousse le héros (push = élan du recul) : on ne le bat plus en martelant
        'guard' => ['hits' => 3, 'window' => 90, 'push' => 9, 'line' => 'BACK TO WORK!'],
    ],
],
```

Par défaut, les vagues d'ennemis ne se configurent pas : `Level\WaveGenerator` en crée trois, de plus en plus nombreuses, en débloquant des ennemis au fil de l'album (`UNLOCKS`). La caisse « ? » et les otages sont placés automatiquement.

Un niveau peut aussi être **plus long** et décrire ses vagues une à une (c'est le cas de *Walk Straight*) :

```php
'length' => 9,                    // longueur de la rue, en écrans (5 par défaut)
'stages' => [                     // réparties régulièrement, le boss reste au bout de la rue
    ['enemies' => 3, 'reinforcements' => 2],     // renforts quand il n'en reste qu'un debout
    'midboss',                                   // le boss de mi-niveau
    ['enemies' => 3, 'extra' => ['knifer'], 'pincer' => true],
],                                // extra = ennemis imposés, pincer = attaque en tenaille
'midboss' => [                    // mêmes clés qu'un boss, + 'score' (5000 par défaut)
    'sprite' => 'tv', 'name' => 'WALK STRAIGHT',
    'entrance' => 'door',         // il défonce une porte du décor au lieu d'entrer par la droite
    'special' => ['static', 'components'],       // une liste = attaques en alternance
    /* … */
    'staticLine' => 'WATCHING TV... NOW FORGET IT!',
],
```

`special` = `static` : l'écran grésille puis lâche une onde de neige en anneau qui étourdit le héros qu'elle traverse (on l'esquive en sautant ou en s'éloignant ; réglages `STATIC` dans `js/config.js`). `components` : il crache trois composants électroniques en éventail, qui explosent en retombant avec un « PLING! » (`COMPONENTS`). L'entrée par la porte se règle avec `DOOR`.

### Un niveau bonus (*Beer Church*)

Avec une clé `bonus`, le niveau se termine (après le boss et MISSION COMPLETE) par un **niveau bonus** façon voiture de *Street Fighter II* : Pete, ivre, titube et a un temps limité pour démolir un gros conteneur à verre. S'il y arrive, il touche la prime plus 100 points par seconde restante ; sinon TIME OVER, sans pénalité. Puis niveau suivant.

```php
'bonus' => [
    'title' => 'BONUS STAGE',
    'target' => 'BOTTLE BANK',          // nom affiché (barre de vie et consigne)
    'hp' => 36,                         // coups à encaisser (le katana fait 1, les armes plus)
    'time' => 40,                       // secondes
    'score' => 10000,                   // prime si le conteneur est détruit
    'line' => 'HIC! MORE AND MORE...',  // la réplique de Pete
],
```

L'ivresse (roulis, hoquets « HIC! » qui le déportent, inclinaison) se règle avec `DRUNK` dans `js/config.js`. Le sprite du conteneur et ses quatre états d'usure sont dessinés par `Sprite\BottleBank`. Les textes affichés en jeu restent en anglais.

### Une descente en skate (*Skate is a Drug*)

Avec une clé `race`, le niveau commence par une **descente en skate vue de dos**, en perspective façon *Out Run* : Pete (et VigiBapt à deux) dévale une rue de Los Angeles au coucher du soleil, se faufile entre les skinheads en rollers, les gym bros en BMX et les flics à moto, et les frappe au katana à gauche ou à droite (comme dans *Street Racer*). À l'arche **SKATEPARK**, le jeu repasse en vue de côté, au bout de la rue du niveau, pour le boss (la dernière vague, celle de `boss`).

```php
'race' => [
    'seed' => 717,                       // bord de la route, obstacles et bonus (reproductibles)
    'sections' => [                      // le tracé : [longueur en segments, virage, dénivelé]
        [100, 0, 0],                     // virage : -6 (à gauche) à 6 (à droite)
        [150, 2, -20],                   // dénivelé en segments : négatif = ça descend
    ],
    'traffic' => [                       // qui débarque quand Pete atteint le segment « at »
        ['at' => 60, 'riders' => ['roller', 'roller']],
        ['at' => 480, 'riders' => ['moto']],     // « COPS ARE BEHIND! » : ils arrivent par-derrière
    ],
    'obstacles' => ['every' => 38, 'pool' => ['cone', 'barrier', 'trash']],   // à éviter, ou à sauter d'un ollie
],
```

Un segment fait à peu près 1/36 de seconde à vitesse de croisière : 2 000 segments ≈ une minute de descente. Les ennemis de la course sont dans `config/game.php` → `riders` (vie, dégâts, vitesse par rapport à Pete, `from` = `front` ou `behind`, `attack` = `strike` ou `ram`). Le bord de la route (palmiers, lampadaires, donut géant, panneaux aux enseignes du thème), les obstacles, les cœurs, les vinyles et le Wall of Death (à mi-parcours, pour le boss) sont placés par `Level\RaceTrack`. Les réglages de conduite (vitesses, portée du katana, virages) sont dans `RACE` (`js/config.js`).

Commandes pendant la descente : GAUCHE/DROITE pour se faufiler, HAUT pour pousser, BAS pour freiner, ESPACE pour un coup de katana (du côté de l'ennemi le plus proche), B pour un **ollie** (au-dessus des obstacles et des coups), C pour une poussée qui renverse tout, V pour un vinyle lancé droit devant, ESPACE + V pour le Wall of Death.

Pour un **nouveau morceau** : l'ajouter à `paroles.md` avec son fichier audio, puis une entrée dans `config/levels.php` (sinon il reprend la config du niveau 1). Le dernier morceau de l'album déclenche la scène de fin.

## Les ennemis et les armes

`config/game.php` → `enemies` : nom affiché, `hp`, `speed`, `damage`, `reach` (portée), `score`, et :

- `moves` : attaques possibles et leur poids (`strike` = continuer d'approcher, `lunge` = ruée, `throw` = lancer, `combo` = deux coups enchaînés, poids face à `strike`) ;
- `weapon` : arme lâchée au K.O. (ramassable par le héros) ; `throws` : arme lancée ;
- `projectile` + `every` : lanceur pur (bouteilles du hooligan).

`config/game.php` → `weapons` : dégâts, portée et recul des armes que le héros ramasse (`staff` = son katana).

## Ajouter un ennemi

1. Son apparence dans `src/Sprite/Character/EnemyRoster.php` → `enemies()`, en déclinant un gang :
   ```php
   'punk' => Skinheads::look(['C' => '#3a3a44', 'c' => '#26262e'], WeaponCatalog::get('chain')),
   ```
   Les couleurs passées remplacent celles de la palette du gang (`Skinheads::PALETTE`, `Masculinists::PALETTE`).
2. Ses caractéristiques dans `config/game.php` → `enemies` (même clé).
3. Le niveau où il apparaît : `Level\WaveGenerator::UNLOCKS`. Sa voix : `js/audio/sounds.js` → `VOICES` (facultatif).
4. `UPDATE_SNAPSHOTS=1 composer test` (les sprites ont changé).

## Ajouter un boss

1. Une classe dans `src/Sprite/Boss/`, sur le modèle de `Manager.php` :
   ```php
   final class Dj extends BossDesign
   {
       protected function palette(): array { return ['C' => '#222', 'c' => '#111', /* ... */]; }
       protected function head(): Layer { return new Layer([/* lignes de pixels */], 0, 5); }
       protected function torso(): Layer { return new Layer([/* ... */], 0, 16); }
       protected function weapon(): Weapon { return WeaponCatalog::get('bat'); } // facultatif
   }
   ```
   Options : `armColors()` (gants, bras nus), `back()` (cape, bazooka), `over()` (chapeau, ceinture), `front()` (bouclier).
2. L'enregistrer dans `Boss\BossRoster::sheets()` sous un identifiant (`'dj' => new Dj()`).
3. L'utiliser dans `config/levels.php` → `boss.sprite`.

## Dessiner en pixel art

Un sprite est une liste de chaînes : **1 caractère = 1 pixel**, `.` = transparent, chaque lettre = une couleur de la palette. `Compositor` empile les calques et détoure chacun d'un contour `K` (noir).

Le squelette commun (`Character\Skeleton`) utilise des lettres génériques que chaque personnage recolore :

| Lettre | Partie | | Lettre | Partie |
|---|---|---|---|---|
| `S` / `s` | peau / ombre | | `A` / `a` / `v` | pantalon (clair, ombre, jambe du fond) |
| `C` / `c` | haut du corps / ombre | | `O` / `o` / `q` | chaussures |
| `H` / `h` | manche / ombre (bras partagés) | | `X` | semelle |
| `K` | contour | | `D` | ceinture |

Les armes utilisent `N`/`n` (bois), `M`/`m` (métal), `L` (cuir), `j` (or), `P`/`p` (téléphone, bouclier) : leurs couleurs sont dans `WeaponCatalog::COLORS`. Repère : le personnage regarde à droite, pieds centrés en x = 18, tête vers y = 4, torse à y = 16, jambes à y = 28.

Pour vérifier un sprite : lancer le jeu (`composer serve`) avec `?debug`, puis par exemple `game.campaign.startLevel(2)` dans la console.

## Le scénario

`config/story.php` : `intro` (avant le niveau 1), `ending` (après le dernier boss), `credits` (générique), et les morceaux qui les accompagnent (`introTrack`, `endingTrack`).

Une étape : `['scene' => 'pete', 'text' => '…']`, avec en option `shout` (bulle au-dessus de Pete), `action => 'skate'`, `duration` (passe toute seule au bout de N images). `[PAUSE 3]` dans un texte fige la machine à écrire 3 secondes. Deux étapes de suite sur le même plan : l'animation continue.

Plans disponibles : `pete`, `villains`, `mask`, `go` (intro) ; `boom`, `peace`, `credits` (fin). Un nouveau plan = un réalisateur JavaScript (voir [javascript.md](javascript.md#ajouter)).

## Les décors

Le décor d'un niveau se règle par son `theme` (ci-dessus). Pour changer *comment* les décors sont dessinés, voir `src/Scene/` et [php.md](php.md#déterminisme--la-règle-dor-des-décors) : toute retouche change les empreintes des snapshots.
