<?php

declare(strict_types=1);

namespace Vigilante\Export\OpenBor;

use Vigilante\PixelArt\Compositor;
use Vigilante\PixelArt\Layer;
use Vigilante\Sprite\CharacterCatalog;
use Vigilante\Sprite\PropCatalog;
use Vigilante\Sprite\SpriteSheet;
use Vigilante\Sprite\Weapon\WeaponCatalog;

/**
 * Tout ce qui n'est pas un combattant : projectiles lancés (en cloche, comme dans le jeu web),
 * bonus à ramasser, otages, caisse « ? » et la charge du Wall of Death.
 */
final class PropModels
{
    /** Projectiles lancés par les ennemis et les boss : sprite => dégâts (×10, comme les PV). */
    public const THROWN = [
        'bottle' => 12, 'grenade' => 14, 'phone' => 15, 'heart' => 16,
        'knife' => 15, 'bat' => 14, 'dumbbell' => 22,
    ];

    /** Armes qu'on peut ramasser, dans l'ordre de « weapons » des héros (weapnum 1, 2...). */
    public const PICKABLE = WeaponCatalog::PICKABLE;

    /** Un bonus reste 10 s par terre (PICKUP_LIFETIME.default), le cœur 30 s (PICKUP_LIFETIME.life). */
    private const LIFESPAN = 10;

    public static function modelName(string $sprite): string
    {
        return ucfirst($sprite);
    }

    /** @return list<CharacterModel> */
    public static function all(): array
    {
        $props = new PropCatalog();
        $palette = $props->jsonSerialize()['palette'];
        $sprites = $props->jsonSerialize()['sprites'];
        $weapons = WeaponCatalog::icons();
        $weaponPalette = WeaponCatalog::COLORS + ['K' => '#0c0a10'];

        $models = [self::vinyl($palette, $sprites['vinyl'])];
        foreach (self::THROWN as $sprite => $damage) {
            $grid = $sprites[$sprite] ?? self::trim($weapons[$sprite]);
            $models[] = self::thrown($sprite, isset($sprites[$sprite]) ? $palette : $weaponPalette, $grid, $damage);
        }
        $models[] = self::item('Beer', $palette, $sprites['beer'], 'health	30');
        $models[] = self::item('Life', $palette, $sprites['life'], 'health	50', 30);
        $models[] = self::item('Vinyls', $palette, $sprites['vinyls'], 'mp	' . 5 * HeroModels::MP_PER_VINYL);
        $models[] = self::item('Wod', $palette, $sprites['wod'], 'score	1');
        foreach (self::PICKABLE as $i => $weapon) {
            $models[] = self::item('Item_' . $weapon, $weaponPalette, self::trim($weapons[$weapon]), 'subtype	weapon', weapnum: $i + 1);
        }
        $models[] = self::pow();
        $models[] = self::wodBox($palette, $sprites['wodbox']);
        $models[] = self::wall();
        $models[] = self::event();

        return $models;
    }

    /** Invisible : il ne sert qu'à lancer un script (spawnscript) quand la caméra arrive à un endroit. */
    private static function event(): CharacterModel
    {
        return new CharacterModel('Event', ModelSheet::prop(['K' => '#000000'], ['idle.0' => ['.']]), [
            'type	none',
            'lifespan	1',
        ], [new Animation('idle', [Frame::at('idle.0', 60)])], hittable: false);
    }

    /**
     * Le vinyle du héros : il tournoie, retombe et explose en notes (rayon VINYL.radius, dégâts VINYL.damage).
     *
     * @param array<array-key, string> $palette
     * @param list<string> $grid
     */
    private static function vinyl(array $palette, array $grid): CharacterModel
    {
        $frames = self::spin($grid) + self::blast();

        return new CharacterModel('Vinyl', ModelSheet::prop($palette + self::BLAST_COLORS, $frames), [
            'type	none',
            'speed	14',
            'jumpheight	2',
            'shadow	1',
            'candamage	enemy obstacle',
        ], [
            new Animation('idle', self::spinFrames(), loop: true),
            new Animation('attack1', [
                Frame::at('blast.0', 6, new Hit(-30, 30, 40, true, 20, 20), commands: ['sound	' . SoundBank::path('scratch')]),
                Frame::at('blast.1', 12),
            ]),
        ], hittable: false);
    }

    /**
     * Ce que lancent les ennemis : touche en vol, se brise à l'atterrissage (grenade : explose) ;
     * une arme qui rate sa cible reste par terre, à ramasser.
     *
     * @param array<array-key, string> $palette
     * @param list<string> $grid
     */
    private static function thrown(string $sprite, array $palette, array $grid, int $damage): CharacterModel
    {
        $frames = self::spin($grid) + self::blast();
        $flying = array_map(
            fn(Frame $frame) => new Frame($frame->sprite, $frame->delay, new Hit(-6, 6, $damage, false, 10, 12)),
            self::spinFrames(),
        );
        $landing = match ($sprite) {
            'grenade' => [
                Frame::at('blast.0', 6, new Hit(-20, 20, $damage, true, 20, 20), commands: ['sound	' . SoundBank::path('explosion')]),
                Frame::at('blast.1', 12),
            ],
            'bottle', 'phone', 'heart' => [Frame::at('blast.1', 8, commands: ['sound	' . SoundBank::path('glass')])],
            // l'arme tombe par terre : elle devient un bonus à ramasser
            default => [Frame::at('spin.0', 6, commands: ['sound	' . SoundBank::path('metal'), 'spawnframe	0 0 0 0 0'])],
        };

        return new CharacterModel(self::modelName($sprite), ModelSheet::prop($palette + self::BLAST_COLORS, $frames), [
            'type	none',
            'speed	10',
            'jumpheight	1.6',
            'shadow	1',
            'candamage	player',
        ], [
            new Animation('idle', $flying, loop: true),
            new Animation('attack1', $landing, extra: in_array($sprite, self::PICKABLE, true) ? ['subentity	Item_' . $sprite] : []),
        ], hittable: false);
    }

    /**
     * @param array<array-key, string> $palette
     * @param list<string> $grid
     * @param int|null $weapnum arme ramassable : numéro dans « weapons » des héros
     */
    private static function item(
        string $name,
        array $palette,
        array $grid,
        string $effect,
        int $lifespan = self::LIFESPAN,
        ?int $weapnum = null,
    ): CharacterModel {
        return new CharacterModel($name, ModelSheet::prop($palette, ['idle.0' => $grid]), [
            'type	item',
            $effect,
            ...($weapnum !== null ? ["weapnum	$weapnum"] : []),
            "lifespan	$lifespan",
            'shadow	1',
        ], [new Animation('idle', [Frame::at('idle.0', 60)], loop: true)], hittable: false);
    }

    /** L'otage ligoté : un coup le libère (+1000), il détale vers la gauche en lâchant un bonus. */
    private static function pow(): CharacterModel
    {
        $sheet = self::sheet(CharacterCatalog::all(), 'pow');
        $frames = ['tied.0' => $sheet->frames['tied'][0], 'tied.1' => $sheet->frames['tied'][1]];

        return new CharacterModel('Pow', ModelSheet::prop($sheet->palette, $frames), [
            'type	obstacle',
            'health	1',
            'nolife	1',
            'score	1000 0',
            'diesound	' . SoundBank::path('freed'),
            'shadow	2',
        ], [
            new Animation('idle', [Frame::at('tied.0', 30), Frame::at('tied.1', 30)], loop: true),
            new Animation('fall', array_map(fn($i) => Frame::at('tied.' . ($i % 2), 6, move: -13), range(0, 11))),
        ]);
    }

    /**
     * La caisse « ? » : deux coups pour la casser, elle libère le Wall of Death.
     *
     * @param array<array-key, string> $palette
     * @param list<string> $grid
     */
    private static function wodBox(array $palette, array $grid): CharacterModel
    {
        return new CharacterModel('Wodbox', ModelSheet::prop($palette, ['idle.0' => $grid]), [
            'type	obstacle',
            'health	20',
            'nolife	1',
            'shadow	2',
            'diesound	' . SoundBank::path('explosion'),
            'hitfx	' . SoundBank::path('metal'),
        ], [
            new Animation('idle', [Frame::at('idle.0', 60)], loop: true),
            new Animation('pain', [Frame::at('idle.0', 8)]),
            new Animation('fall', [Frame::at('idle.0', 4)]),
        ]);
    }

    /**
     * WALL OF DEATH : cinq gros durs (×1,5) qui chargent côte à côte à travers l'écran.
     * Les dégâts sont faits par le script wallSweep (petits ennemis K.O., boss : la moitié de sa vie).
     */
    private static function wall(): CharacterModel
    {
        $all = CharacterCatalog::all();
        $guys = ['tough1', 'tough2', 'tough3', 'tough1', 'tough2'];
        $palette = [];
        $frames = [];
        for ($step = 0; $step < 4; $step++) {
            $layers = [];
            foreach ($guys as $i => $type) {
                $sheet = self::sheet($all, $type);
                $map = self::renaming($sheet->palette, $i);
                foreach ($sheet->palette as $char => $color) {
                    $palette[$map[(string) $char]] = $color;
                }
                $grid = $sheet->frames['walk'][($step + $i) % 4];
                $layers[] = (new Layer($grid, $i * 14, ($i * 7) % 18))->recolor($map);
            }
            $frames["walk.$step"] = ModelSheet::enlarge(Compositor::compose(5 * 14 + 40, 60, $layers, null, 0), 1.5);
        }
        $anchors = array_map(fn($grid) => intdiv(strlen($grid[0]), 2) . ' ' . count($grid), $frames);

        return new CharacterModel('Wall', new ModelSheet($palette, $frames, 0, 0, 1, $anchors), [
            'type	none',
            'lifespan	3',
            'animationscript	data/scripts/wall.c',
        ], [
            new Animation('idle', array_map(
                fn($i) => Frame::at("walk.$i", 3, move: 8, commands: ['@cmd	wallSweep']),
                [0, 1, 2, 3],
            ), loop: true),
        ], hittable: false);
    }

    /** @param array<string, mixed> $all */
    private static function sheet(array $all, string $name): SpriteSheet
    {
        $sheet = $all[$name] ?? null;

        return $sheet instanceof SpriteSheet ? $sheet : throw new \RuntimeException("Sprite inconnu : $name");
    }

    private const BLAST_COLORS = ['Q' => '#ffd23f', 'q' => '#ff8a1e', 'Z' => '#ffffff'];

    /**
     * Éclat : un disque blanc, jaune et orange, puis des étincelles.
     *
     * @return array<string, list<string>>
     */
    private static function blast(): array
    {
        $frames = [];
        foreach ([0 => [9, 0.0], 1 => [12, 0.6]] as $i => [$radius, $hollow]) {
            $rows = [];
            for ($y = -$radius; $y <= $radius; $y++) {
                $row = '';
                for ($x = -$radius; $x <= $radius; $x++) {
                    $d = sqrt($x * $x + $y * $y) / $radius;
                    $row .= match (true) {
                        $d > 1, $d < $hollow, ($hollow > 0 && ($x + $y) % 3 !== 0) => '.',
                        $d < 0.4 => 'Z',
                        $d < 0.75 => 'Q',
                        default => 'q',
                    };
                }
                $rows[] = $row;
            }
            $frames["blast.$i"] = $rows;
        }

        return $frames;
    }

    /**
     * Le projectile tourne d'un quart de tour à chaque image.
     *
     * @param list<string> $grid
     * @return array<string, list<string>>
     */
    private static function spin(array $grid): array
    {
        $frames = ['spin.0' => $grid];
        for ($i = 1; $i < 4; $i++) {
            $grid = ModelSheet::rotate($grid);
            $frames["spin.$i"] = $grid;
        }

        return $frames;
    }

    /** @return list<Frame> */
    private static function spinFrames(): array
    {
        return array_map(fn($i) => Frame::at("spin.$i", 4), [0, 1, 2, 3]);
    }

    /**
     * @param list<string> $grid
     * @return list<string>
     */
    private static function trim(array $grid): array
    {
        return array_values(array_filter($grid, fn($row) => trim($row, '.') !== ''));
    }

    /**
     * Chaque gros dur a ses couleurs : celles des gros durs 2 et 3 prennent des caractères
     * libres (0x80+ et 0xC0+), dans l'ordre de leur palette. K (le contour) est commun.
     *
     * @param array<array-key, string> $palette
     * @return array<string, string> caractère d'origine => caractère dans la planche du mur
     */
    private static function renaming(array $palette, int $guy): array
    {
        $set = $guy % 3; // gros durs 1 et 4, 2 et 5 sont identiques
        $map = [];
        $i = 0;
        foreach (array_keys($palette) as $char) {
            $char = (string) $char;
            $map[$char] = $set === 0 || $char === 'K' ? $char : chr(($set === 1 ? 0x80 : 0xC0) + $i++);
        }

        return $map;
    }
}
