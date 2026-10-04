<?php

declare(strict_types=1);

namespace Vigilante\Sprite;

use JsonSerializable;
use Vigilante\PixelArt\Compositor;
use Vigilante\PixelArt\Layer;

/** Accessoires du trottoir, projectiles des boss et bonus à ramasser. */
final class PropCatalog implements JsonSerializable
{
    private const PALETTE = [
        'K' => '#120a18',
        'R' => '#e8203a', 'r' => '#8f1424', 'H' => '#ff7a86',
        'W' => '#b8b8c8', 'w' => '#6e6e82',
        'C' => '#77736a', 'c' => '#4e4b45', 'D' => '#2e2c28',
        'Y' => '#ffd23f', 'y' => '#c08a1a',
        'F' => '#c79a62', 'f' => '#8a6538',
        'G' => '#3f8a3a', 'g' => '#a8e0a0',
        'O' => '#4a5a2a', 'o' => '#7a8a40',
        'P' => '#1a1a22', 'p' => '#7fd4ff',
        'L' => '#ff3ea5', 'l' => '#ffb0d8',
        'B' => '#e8b020', 'b' => '#f4f4f4',
    ];

    private const SPRITES = [
        'hydrant' => [
            '...RR...',
            '..RHRr..',
            '.RRRRRr.',
            '..wWWw..',
            '.RHRRRr.',
            'WRHRRRrW',
            'wRHRRRrw',
            '.RHRRRr.',
            '.RHRRRr.',
            '.RRRRrr.',
            'wWWWWWWw',
        ],
        'trash' => [
            '.....Y......',
            '..WWWyWWWW..',
            '.WWWWWWWWWw.',
            '..DDDDDDDD..',
            '..CcCCcCCc..',
            '..CcCCcCCc..',
            '..CcCCcCCc..',
            '..CcCCcCCc..',
            '..DDDDDDDD..',
            '..CcCCcCCc..',
            '..CcCCcCCc..',
            '..CcCCcCCc..',
            '..CcCCcCCc..',
            '..DDDDDDDD..',
        ],
        'box' => [
            'FFFFFFFfFFF',
            'FFFFFFFfFFf',
            'fffffffffff',
            'FFFFFFFFFFf',
            'FFFKKFFFFFf',
            'FFFFFFFFFFf',
            'FFFFFFFFFFf',
        ],
        'bottle' => ['..g.', '..G.', '.GGG', '.GgG', '.GGG', '.GGG'],
        'grenade' => ['.WW.', 'OOOO', 'OoOO', 'OOOO', '.OO.'],
        'phone' => ['PPPP', 'PppP', 'PppP', 'PppP', 'PPPP'],
        'heart' => ['LL.LL', 'LlLLL', 'LLLLL', '.LLL.', '..L..'],
        'beer' => ['.WW.', 'BBBB', 'BbbB', 'BbbB', 'BBBB', 'BBBB'],
        // gros cœur : rend la moitié de la vie
        'life' => ['.RR...RR.', 'RHHR.RRRr', 'RHRRRRRRr', 'RRRRRRRRr', '.RRRRRRr.', '..RRRRr..', '...RRr...', '....r....'],
        // caisse mystère, une par niveau : elle contient le Wall of Death
        'wodbox' => [
            'WWFFFFFFFFWW',
            'WFfFFFFFFfFW',
            'FFFFRRRRFFFF',
            'FFFRRFFRRFFF',
            'FfFFFFFRRFfF',
            'FFFFFFRRFFFF',
            'FFFFFRRFFFFF',
            'FfFFFFFFFFfF',
            'FFFFFRRFFFFF',
            'WFfFFFFFFfFW',
            'WWFFFFFFFFWW',
        ],
        'wod' => ['..RRRRR..', '.RRRRRRR.', 'RbRRRRRbR', 'RbRRRRRbR', 'RbRRbRRbR', 'RbRbRbRbR', 'RRbRRRbRR', '.RRRRRRR.', '..RRRRR..'],
        'vinyls' => ['ffffffffff', 'fPPPPPPPPf', 'fPPwPPPPPf', 'fPPPRRPPPf', 'fPPPRRPPPf', 'fPPPPPwPPf', 'ffffffffff', 'fFFFFFFFFf', 'ffffffffff'],
        'vinyl' => [
            '....PPPP....',
            '..PPPPPPPP..',
            '.PPwwPPPPPP.',
            '.PwPPPPPPPP.',
            'PPPPPRRPPPPP',
            'PPPPRRRRPPPP',
            'PPPPRRKRPPPP',
            'PPPPPRRPPPPP',
            '.PPPPPPPPwP.',
            '.PPPPPPPwPP.',
            '..PPPPPPPP..',
            '....PPPP....',
        ],
    ];

    /** @var array<string, list<string>>|null */
    private ?array $grids = null;

    /** @return array<string, string> */
    public function palette(): array
    {
        return self::PALETTE;
    }

    /** @return list<string> */
    public function grid(string $name): array
    {
        return $this->grids()[$name];
    }

    /** @return array<string, list<string>> */
    public function grids(): array
    {
        return $this->grids ??= array_map(
            fn(array $rows) => Compositor::compose(max(array_map('strlen', $rows)), count($rows), [new Layer($rows)]),
            self::SPRITES,
        );
    }

    /** @return array{palette: array<string, string>, sprites: array<string, list<string>>} */
    public function jsonSerialize(): array
    {
        return ['palette' => self::PALETTE, 'sprites' => $this->grids()];
    }
}
