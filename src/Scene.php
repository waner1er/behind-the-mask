<?php

/**
 * Décor urbain façon BD à l'encre (comme la pochette de Vigilante).
 * Résolution "borne d'arcade" : 320 x 180.
 *
 * Chaque niveau a son thème (voir src/levels.php) : ciel, astre, couleur
 * d'accent, enseignes et graffitis tirés des paroles du morceau.
 *
 * Tout est procédural : la graine (seed) du thème donne toujours la même rue.
 * Les plans qui défilent font exactement 320 px : le JS les affiche deux fois
 * côte à côte et les décale selon la caméra.
 */
final class Scene
{
    public const WIDTH = 320;
    public const HEIGHT = 180;
    public const GROUND = 140; // ligne où les immeubles touchent le trottoir

    public const PAPER = '#ece8dc';
    public const INK = '#0e0d0c';

    // Couleurs selon le ciel : fond, trame, skyline, fenêtres de la skyline
    private const SKIES = [
        'paper' => ['sky' => self::PAPER, 'dots' => '#9d998f', 'skyline' => '#a9a59a', 'windows' => '#7d796f'],
        'dusk' => ['sky' => '#ebc79a', 'dots' => '#b4865a', 'skyline' => '#a07a58', 'windows' => '#7a5a3e'],
        'grey' => ['sky' => '#b9b9b4', 'dots' => '#8a8a85', 'skyline' => '#8e8e89', 'windows' => '#6e6e6a'],
        'night' => ['sky' => '#0f0e13', 'dots' => '#34313c', 'skyline' => '#26242c', 'windows' => '#c8c2a8'],
    ];

    // Éléments de l'apocalypse
    private const FIRE_COLORS = ['y' => '#fff6b0', 'Y' => '#ffd23f', 'O' => '#ff8a1e', 'r' => '#e8203a', 'R' => '#8a1010'];
    private const FIRE = [
        ['...y...', '..yYy..', '.yYOYy.', '.YOOOY.', 'YOOrOOY', 'OOrrrOO', '.OrRrO.', '..rRr..'],
        ['..y....', '..Yy.y.', '.yYOYY.', '.YOOOOY', 'YOOrrOY', 'OOrrrOO', '.OrRrO.', '..rRr..'],
    ];
    private const WRECK_COLORS = ['k' => '#141210', 'd' => '#3a3632', 'e' => '#5a5650', 'r' => '#7a3a1e', 'g' => '#2a2a30', 'o' => '#6a2a1e', 'O' => '#8a3a24'];
    private const CAR = [
        '.........kkkkkkkkkkkk.........',
        '........kgggkkkkgggggk........',
        '.......kgggkkkkkkggggek.......',
        '..kkkkkkkkkkkkkkkkkkkkkkkkk...',
        '.kdddddddddddrddddddddddddek..',
        'kdddrrdddddddddddddddrddddddk.',
        'kddddddddddddddddddddddddddek.',
        'kkkkkkkkkkkkkkkkkkkkkkkkkkkkk.',
        '..kkkk...............kkkk.....',
        '..kdek...............kdek.....',
        '...kk.................kk......',
    ];
    private const BARREL = ['.kkkkkk.', 'kOOOOOOk', 'koOOOOok', 'kkkkkkkk', 'koOOOOok', 'koOOOOok', 'kkkkkkkk', 'koOOOOok', '.kkkkkk.'];
    private const RUBBLE = ['....k.....', '..kdek.k..', '.kdddekdk.', 'kddkdddddk'];

    private string $accent;
    private float $chaos;
    private array $sky;
    private array $signs;
    private array $tags;

    /**
     * @param array $theme voir src/levels.php
     * @param string[] $tags graffitis (paroles en majuscules)
     * @param array $props sprites des accessoires (src/sprites/props.php)
     */
    public function __construct(private array $theme, array $tags, private array $props)
    {
        $this->accent = $theme['accent'] ?? '#e8203a';
        $this->sky = self::SKIES[$theme['sky'] ?? 'paper'];
        $this->signs = $theme['signs'] ?? ['BAR'];
        $this->tags = $tags ?: ['VIGILANTE'];
        $this->chaos = $theme['chaos'] ?? 0;
    }

    /** Tout le décor, prêt à être injecté dans le <svg> de l'écran. */
    public function render(): string
    {
        $layers = [
            ['factor' => 0.25, 'svg' => $this->skyline()],
            ['factor' => 0.6, 'svg' => $this->buildings()],
            ['factor' => 1, 'svg' => $this->street()],
        ];

        $svg = $this->background();
        foreach ($layers as $i => $layer) {
            $svg .= sprintf(
                '<g class="layer" data-factor="%s">%s<g transform="translate(%d 0)">%s</g></g>',
                $layer['factor'], $layer['svg'], self::WIDTH, $layer['svg']
            );
            if ($i === 0) {
                $svg .= $this->haze();
            }
        }

        return $svg;
    }

    public static function rect(int $x, int $y, int $w, int $h, string $fill, string $attrs = ''): string
    {
        return sprintf(
            '<rect x="%d" y="%d" width="%d" height="%d" fill="%s"%s/>',
            $x, $y, $w, $h, $fill, $attrs !== '' ? ' ' . $attrs : ''
        );
    }

    private static function text(int $x, int $y, string $label, string $color, int $size, string $class, string $attrs = ''): string
    {
        return sprintf(
            '<text x="%d" y="%d" class="%s" fill="%s" style="color:%s" font-size="%d" text-anchor="middle"%s>%s</text>',
            $x, $y, $class, $color, $color, $size, $attrs !== '' ? ' ' . $attrs : '', htmlspecialchars($label)
        );
    }

    /** Ciel avec une trame de points (demi-teinte de BD) en haut, et le soleil ou la lune. */
    private function background(): string
    {
        $dots = $this->sky['dots'];
        $defs = '<pattern id="halftone-a" width="4" height="4" patternUnits="userSpaceOnUse">' . self::rect(0, 0, 1, 1, $dots) . self::rect(2, 2, 1, 1, $dots) . '</pattern>'
            . '<pattern id="halftone-b" width="4" height="4" patternUnits="userSpaceOnUse">' . self::rect(0, 0, 1, 1, $dots) . '</pattern>'
            . '<pattern id="halftone-c" width="8" height="4" patternUnits="userSpaceOnUse">' . self::rect(0, 0, 1, 1, $dots) . '</pattern>';

        $svg = "<defs>$defs</defs>"
            . self::rect(0, 0, self::WIDTH, self::GROUND, $this->sky['sky'])
            . self::rect(0, 0, self::WIDTH, 24, 'url(#halftone-a)')
            . self::rect(0, 24, self::WIDTH, 24, 'url(#halftone-b)')
            . self::rect(0, 48, self::WIDTH, 24, 'url(#halftone-c)');

        if (($this->theme['sky'] ?? '') === 'night') {
            $svg .= $this->stars();
        }

        // la ville brûle : lueur d'incendie à l'horizon
        if ($this->chaos > 0) {
            foreach ([[60, 0.25], [90, 0.45], [115, 0.7]] as [$y, $strength]) {
                $svg .= self::rect(0, $y, self::WIDTH, self::GROUND - $y, '#ff5a1e', sprintf('opacity="%.2f"', $this->chaos * $strength * 0.35));
            }
        }

        return $svg . match ($this->theme['celestial'] ?? 'none') {
            'sun' => $this->sun(),
            'moon' => $this->moon(),
            default => '',
        };
    }

    /** Soleil couleur d'accent cerclé d'encre, rayé en bas façon affiche. */
    private function sun(int $cx = 258, int $cy = 46, int $radius = 16): string
    {
        $rows = $this->circle($radius, fn(int $x, int $y, float $d) => match (true) {
            $d <= $radius - 1 => ($y > 4 && $y % 3 === 0) ? 'p' : 'r',
            $d <= $radius + 0.5 => 'k',
            default => '.',
        });

        return PixelArt::toSvg($rows, ['r' => $this->accent, 'p' => $this->sky['sky'], 'k' => self::INK], $cx - $radius - 1, $cy - $radius - 1);
    }

    /** Lune blanche avec cratères et halo. */
    private function moon(int $cx = 258, int $cy = 40, int $radius = 14): string
    {
        $craters = [[-4, -3, 2], [3, 4, 3], [5, -5, 1], [-6, 5, 1]];
        $rows = $this->circle($radius, function (int $x, int $y, float $d) use ($radius, $craters) {
            if ($d > $radius) {
                return '.';
            }
            foreach ($craters as [$kx, $ky, $kr]) {
                if (($x - $kx) ** 2 + ($y - $ky) ** 2 <= $kr * $kr) {
                    return 'c';
                }
            }

            return $x + $y > $radius * 0.7 ? 'b' : 'a';
        });

        $halo = '';
        foreach ([[$radius + 8, 0.06], [$radius + 4, 0.1]] as [$r, $opacity]) {
            for ($y = -$r; $y <= $r; $y++) {
                $half = (int) floor(sqrt($r * $r - $y * $y));
                $halo .= self::rect($cx - $half, $cy + $y, $half * 2 + 1, 1, $this->accent, sprintf('opacity="%.2f"', $opacity));
            }
        }

        return $halo . PixelArt::toSvg($rows, ['a' => self::PAPER, 'b' => '#c9c3b2', 'c' => '#a8a290'], $cx - $radius - 1, $cy - $radius - 1);
    }

    /** @return string[] grille d'un disque, le callback choisit le caractère de chaque pixel */
    private function circle(int $radius, callable $pixel): array
    {
        $rows = [];
        for ($y = -$radius - 1; $y <= $radius + 1; $y++) {
            $row = '';
            for ($x = -$radius - 1; $x <= $radius + 1; $x++) {
                $row .= $pixel($x, $y, sqrt($x * $x + $y * $y));
            }
            $rows[] = $row;
        }

        return $rows;
    }

    private function stars(): string
    {
        mt_srand($this->theme['seed']);
        $svg = '';
        for ($i = 0; $i < 50; $i++) {
            $x = mt_rand(0, self::WIDTH - 1);
            $y = mt_rand(2, 100);
            $attrs = mt_rand(0, 2) === 0 ? sprintf('class="blink" style="animation-delay:-%.1fs"', mt_rand(0, 20) / 10) : '';
            $svg .= self::rect($x, $y, 1, 1, self::PAPER, $attrs);
        }

        return $svg;
    }

    /** Arrière-plan lointain : silhouettes de gratte-ciels. */
    private function skyline(): string
    {
        mt_srand($this->theme['seed'] + 1);
        $color = $this->sky['skyline'];
        $svg = '';

        for ($x = 0; $x < self::WIDTH; $x += $w) {
            $w = mt_rand(10, 26);
            if (self::WIDTH - ($x + $w) < 10) {
                $w = self::WIDTH - $x;
            }
            $top = self::GROUND - mt_rand(40, 100);
            $svg .= self::rect($x, $top, $w, self::GROUND - $top, $color);

            for ($wy = $top + 4; $wy < self::GROUND - 4; $wy += 4) {
                for ($wx = $x + 2; $wx < $x + $w - 2; $wx += 3) {
                    if (mt_rand(0, 99) < 16) {
                        $svg .= self::rect($wx, $wy, 1, 2, $this->sky['windows']);
                    }
                }
            }

            if ($w > 14 && mt_rand(0, 2) === 0) {
                $step = mt_rand(4, 10);
                $top -= $step;
                $svg .= self::rect($x + 3, $top, $w - 6, $step, $color);
            }

            if (mt_rand(0, 3) === 0) {
                $height = mt_rand(6, 14);
                $ax = $x + intdiv($w, 2);
                $svg .= self::rect($ax, $top - $height, 1, $height, $color);
                $svg .= self::rect($ax, $top - $height - 1, 1, 1, $this->accent, sprintf('class="blink" style="animation-delay:-%.1fs"', mt_rand(0, 20) / 10));
            }
        }

        return $svg . $this->smoke();
    }

    /** Colonnes de fumée qui montent des incendies. */
    private function smoke(): string
    {
        $svg = '';
        for ($i = 0, $count = (int) round($this->chaos * 4); $i < $count; $i++) {
            $x = mt_rand(10, self::WIDTH - 30);
            $base = self::GROUND - mt_rand(40, 70);
            $plume = '';
            for ($j = 0; $j < 12; $j++) {
                $width = 6 + (int) ($j * 1.6);
                $sway = (int) round(sin($j * 0.7 + $i) * 3) + $j;
                $plume .= self::rect($x + $sway - intdiv($width, 2), $base - $j * 7, $width, 8, '#2e2b28', sprintf('opacity="%.2f"', 0.55 - $j * 0.04));
            }
            $svg .= sprintf('<g class="smoke" style="animation-delay:-%ds">%s</g>', mt_rand(0, 8), $plume);
        }

        return $svg;
    }

    /** Petit feu animé (deux images qui alternent). */
    private function fire(int $x, int $y): string
    {
        return '<g class="fire">'
            . '<g class="fire__a">' . PixelArt::toSvg(self::FIRE[0], self::FIRE_COLORS, $x, $y) . '</g>'
            . '<g class="fire__b">' . PixelArt::toSvg(self::FIRE[1], self::FIRE_COLORS, $x, $y) . '</g>'
            . '</g>';
    }

    /** Voile de la couleur du ciel qui éclaircit la skyline (perspective atmosphérique). */
    private function haze(): string
    {
        $svg = '';
        foreach ([[96, 0.15], [112, 0.2], [124, 0.25]] as [$y, $opacity]) {
            $svg .= self::rect(0, $y, self::WIDTH, self::GROUND - $y, $this->sky['sky'], sprintf('opacity="%.2f"', $opacity));
        }

        return $svg;
    }

    /** Plan intermédiaire : immeubles noirs détourés de blanc. */
    private function buildings(): string
    {
        mt_srand($this->theme['seed'] + 2);
        $svg = '';
        $index = 0;

        for ($x = 0; $x < self::WIDTH; $x += $w) {
            $w = mt_rand(46, 70);
            if (self::WIDTH - ($x + $w) < 46) {
                $w = self::WIDTH - $x;
            }
            $svg .= $this->building($x, $w, mt_rand(50, 84), $index++);
        }

        return $svg;
    }

    /** Premier plan : trottoir, route, lampadaires et accessoires. */
    private function street(): string
    {
        mt_srand($this->theme['seed'] + 3);
        $ground = self::GROUND;
        $svg = '';

        // trottoir
        $svg .= self::rect(0, $ground, self::WIDTH, 1, self::INK);
        $svg .= self::rect(0, $ground + 1, self::WIDTH, 16, '#5e5a52');
        $svg .= self::rect(0, $ground + 1, self::WIDTH, 1, '#7a766c');
        $svg .= self::rect(0, $ground + 8, self::WIDTH, 1, '#4a4740');
        for ($x = 6; $x < self::WIDTH; $x += 24) {
            $svg .= self::rect($x, $ground + 1, 1, 16, '#3e3b35');
        }
        for ($i = 0; $i < 40; $i++) {
            $svg .= self::rect(mt_rand(0, self::WIDTH - 2), mt_rand($ground + 2, $ground + 15), mt_rand(1, 2), 1, '#4a4740');
        }

        // bordure et caniveau
        $svg .= self::rect(0, $ground + 17, self::WIDTH, 1, self::PAPER);
        $svg .= self::rect(0, $ground + 18, self::WIDTH, 2, '#8a8579');
        $svg .= self::rect(0, $ground + 20, self::WIDTH, 1, self::INK);

        // route
        $svg .= self::rect(0, $ground + 21, self::WIDTH, self::HEIGHT - $ground - 21, '#25231f');
        for ($i = 0; $i < 260; $i++) {
            $svg .= self::rect(mt_rand(0, self::WIDTH - 1), mt_rand($ground + 22, self::HEIGHT - 1), 1, 1, mt_rand(0, 1) ? '#302d28' : '#1a1916');
        }
        for ($x = 4; $x < self::WIDTH; $x += 40) {
            $svg .= self::rect($x, 171, 16, 2, self::PAPER) . self::rect($x, 173, 16, 1, '#77736a');
        }
        $svg .= self::text(210, 168, 'VIGILANTE', $this->accent, 5, 'graffiti', 'opacity=".7"');
        $svg .= self::rect(80, 166, 14, 3, self::INK) . self::rect(82, 167, 10, 1, '#4a4740');

        $svg .= $this->lamp(36, false) . $this->lamp(196, true);

        // gravats, baril en feu, épave de voiture calcinée
        for ($i = 0, $count = (int) round($this->chaos * 4); $i < $count; $i++) {
            $svg .= PixelArt::toSvg(self::RUBBLE, self::WRECK_COLORS, mt_rand(0, self::WIDTH - 12), $ground + mt_rand(3, 12));
        }
        if ($this->chaos >= 0.15) {
            $svg .= PixelArt::toSvg(self::BARREL, self::WRECK_COLORS, 150, $ground - 3) . $this->fire(150, $ground - 10);
        }
        if ($this->chaos >= 0.3) {
            $svg .= PixelArt::toSvg(self::CAR, self::WRECK_COLORS, 112, $ground - 5) . $this->fire(118, $ground - 13) . $this->fire(128, $ground - 15);
        }

        $place = fn(string $name, int $x) => PixelArt::toSvg(
            $this->props['sprites'][$name],
            $this->props['palette'],
            $x,
            $ground + 6 - count($this->props['sprites'][$name])
        );

        return $svg . $place('hydrant', 92) . $place('trash', 238) . $place('box', 252) . $place('box', 286);
    }

    private function building(int $x, int $w, int $h, int $index): string
    {
        $night = ($this->theme['sky'] ?? '') === 'night';
        $wall = $night ? ['#1e1c24', '#24212b'][$index % 2] : ['#141312', '#1b1a18', '#121110', '#1f1d1a'][$index % 4];
        $top = self::GROUND - $h;
        $svg = self::rect($x, $top, $w, $h, $wall);

        // hachures de briques et arête détourée
        for ($i = 0, $n = intdiv($w * $h, 40); $i < $n; $i++) {
            $svg .= self::rect(mt_rand($x + 2, $x + $w - 3), mt_rand($top + 4, self::GROUND - 1), 2, 1, '#2e2c28');
        }
        $svg .= self::rect($x, $top, 1, $h, self::PAPER, 'opacity=".5"');

        // toit : château d'eau ou clim, au trait blanc
        if ($w > 52 && mt_rand(0, 1) === 0) {
            $svg .= $this->waterTower($x + $w - 20, $top);
        } else {
            $svg .= self::rect($x + 6, $top - 5, 10, 5, self::INK) . self::rect($x + 6, $top - 5, 10, 1, self::PAPER) . self::rect($x + 8, $top - 3, 6, 1, '#5e5a52');
        }

        // corniche
        $svg .= self::rect($x, $top, $w, 1, self::PAPER) . self::rect($x, $top + 1, $w, 2, self::INK) . self::rect($x, $top + 3, $w, 1, self::PAPER, 'opacity=".6"');

        // fenêtres
        $columns = intdiv($w - 8, 9);
        $offset = $x + intdiv($w - ($columns * 9 - 4), 2);
        $floors = [];
        for ($wy = $top + 9; $wy + 8 < self::GROUND - 26; $wy += 13) {
            $floors[] = $wy;
            for ($c = 0; $c < $columns; $c++) {
                $svg .= $this->window($offset + $c * 9, $wy);
            }
        }

        if ($w >= 56 && count($floors) > 1 && mt_rand(0, 1) === 0) {
            $svg .= $this->fireEscape($offset + ($columns - 2) * 9 - 2, $floors);
        }

        // incendies : aux fenêtres et sur les toits
        if ($floors && mt_rand(0, 99) < $this->chaos * 80) {
            $svg .= $this->fire($offset + mt_rand(0, $columns - 1) * 9 - 1, $floors[array_rand($floors)] - 2);
        }
        if (mt_rand(0, 99) < $this->chaos * 50) {
            $svg .= $this->fire($x + mt_rand(4, $w - 12), $top - 8);
        }

        // rez-de-chaussée : bandeau, porte, vitrine, affiche
        $base = self::GROUND - 22;
        $svg .= self::rect($x + 1, $base, $w - 1, 1, self::PAPER) . self::rect($x + 1, $base + 1, $w - 1, 2, self::INK);
        $svg .= self::rect($x + 6, self::GROUND - 12, 8, 12, self::PAPER) . self::rect($x + 7, self::GROUND - 11, 6, 11, self::INK);
        $svg .= self::rect($x + 17, self::GROUND - 11, $w - 23, 8, self::PAPER) . self::rect($x + 18, self::GROUND - 10, $w - 25, 6, '#2a2824');
        $svg .= $this->poster($x + $w - 12, self::GROUND - 20, $index);

        // enseigne lumineuse ou graffiti tiré des paroles
        $center = $x + intdiv($w, 2);
        if ($index % 3 !== 2) {
            $label = $this->signs[$index % count($this->signs)];
            $color = $index % 2 ? self::PAPER : $this->accent;
            $width = strlen($label) * 6 + 6;
            $flicker = $index % 4 === 1 ? ' neon--flicker' : '';
            $svg .= self::rect($center - intdiv($width, 2) - 1, $base - 12, $width + 2, 12, self::PAPER);
            $svg .= self::rect($center - intdiv($width, 2), $base - 11, $width, 10, self::INK);
            $svg .= self::text($center, $base - 3, $label, $color, 6, 'neon' . $flicker);
        } else {
            $label = $this->tags[intdiv($index, 3) % count($this->tags)];
            $size = strlen($label) * 5 > $w ? 4 : 5;
            $color = intdiv($index, 3) % 2 ? $this->accent : self::PAPER;
            $svg .= self::text($center, $base - 4, $label, $color, $size, 'graffiti', sprintf('transform="rotate(-5 %d %d)"', $center, $base - 6));
        }

        return $svg;
    }

    private function window(int $x, int $y): string
    {
        $svg = self::rect($x - 1, $y - 1, 7, 9, self::PAPER);
        $roll = mt_rand(0, 99);

        // vitre brisée et traces de suie
        if (mt_rand(0, 99) < $this->chaos * 45) {
            return $svg . self::rect($x, $y, 5, 7, self::INK)
                . self::rect($x, $y, 2, 1, self::PAPER) . self::rect($x, $y + 1, 1, 2, self::PAPER)
                . self::rect($x + 4, $y + 5, 1, 2, self::PAPER) . self::rect($x + 3, $y + 6, 1, 1, self::PAPER)
                . self::rect($x - 1, $y - 4, 7, 3, '#2e2b28', 'opacity=".6"');
        }

        if ($roll < 55) {
            $svg .= self::rect($x, $y, 5, 7, self::INK) . self::rect($x + 3, $y + 1, 1, 1, self::PAPER) . self::rect($x + 2, $y + 2, 1, 1, self::PAPER);
        } elseif ($roll < 85) {
            $svg .= self::rect($x, $y, 5, 7, '#f7f3e6');
            if ($roll > 76) {
                $svg .= self::rect($x + 1, $y + 2, 2, 2, self::INK) . self::rect($x + 1, $y + 4, 3, 3, self::INK);
            }
        } else {
            $svg .= self::rect($x, $y, 5, 7, $roll > 95 ? $this->accent : self::INK);
            $svg .= self::rect($x, $y + 1, 5, 1, '#5e5a52') . self::rect($x, $y + 3, 5, 1, '#5e5a52') . self::rect($x, $y + 5, 5, 1, '#5e5a52');
        }

        return $svg;
    }

    /** Affiche de concert collée sur le mur (avec le masque). */
    private function poster(int $x, int $y, int $index): string
    {
        $paper = $index % 2 ? '#f7f3e6' : $this->accent;
        $ink = $index % 2 ? $this->accent : self::INK;

        return self::rect($x, $y, 8, 11, $paper)
            . self::rect($x + 1, $y + 1, 6, 1, $ink)
            . self::rect($x + 2, $y + 3, 4, 4, self::INK)
            . self::rect($x + 3, $y + 4, 1, 1, $paper) . self::rect($x + 5, $y + 4, 1, 1, $paper)
            . self::rect($x + 1, $y + 8, 6, 1, $ink)
            . self::rect($x + 1, $y + 10, 4, 1, $ink);
    }

    private function fireEscape(int $x, array $floors): string
    {
        $svg = '';
        foreach ($floors as $i => $y) {
            $level = $y + 8;
            $svg .= self::rect($x, $level, 18, 1, self::PAPER) . self::rect($x, $level - 4, 18, 1, self::PAPER);
            for ($p = 0; $p < 18; $p += 4) {
                $svg .= self::rect($x + $p, $level - 4, 1, 4, self::PAPER);
            }
            if (isset($floors[$i + 1])) {
                for ($k = 0; $k < 12; $k++) {
                    $svg .= self::rect($x + 3 + $k, $level + 1 + $k, 1, 1, self::PAPER);
                }
            }
        }

        return $svg;
    }

    private function waterTower(int $x, int $roof): string
    {
        $svg = self::rect($x + 2, $roof - 5, 1, 5, self::INK) . self::rect($x + 10, $roof - 5, 1, 5, self::INK)
            . self::rect($x + 2, $roof - 3, 9, 1, self::INK);
        $svg .= self::rect($x, $roof - 17, 13, 12, self::INK);
        $svg .= self::rect($x, $roof - 17, 1, 12, self::PAPER) . self::rect($x + 12, $roof - 17, 1, 12, self::PAPER);
        for ($y = $roof - 15; $y < $roof - 5; $y += 3) {
            $svg .= self::rect($x + 1, $y, 11, 1, '#5e5a52');
        }

        return $svg . self::rect($x + 1, $roof - 19, 11, 2, self::INK) . self::rect($x + 1, $roof - 19, 11, 1, self::PAPER) . self::rect($x + 4, $roof - 20, 5, 1, self::INK);
    }

    private function lamp(int $x, bool $flicker): string
    {
        $class = $flicker ? 'lamp-light lamp-light--flicker' : 'lamp-light';

        $light = '';
        for ($i = 0, $y = 79; $y < self::GROUND + 8; $i++, $y += 8) {
            $half = 3 + $i * 3;
            $light .= self::rect($x + 10 - $half, $y, $half * 2, 8, '#fff6d0', 'opacity=".06"');
        }
        $light .= self::rect($x - 10, self::GROUND + 3, 40, 6, '#fff6d0', 'opacity=".12"');

        return "<g class=\"$class\">$light</g>"
            . self::rect($x, 76, 2, self::GROUND + 4 - 76, self::INK)
            . self::rect($x + 1, 76, 1, self::GROUND + 4 - 76, '#5e5a52')
            . self::rect($x - 1, self::GROUND, 4, 4, self::INK)
            . self::rect($x, 74, 10, 2, self::INK)
            . self::rect($x + 6, 76, 8, 2, self::INK)
            . self::rect($x + 7, 78, 6, 1, '#fff6d0', "class=\"$class\"");
    }
}
