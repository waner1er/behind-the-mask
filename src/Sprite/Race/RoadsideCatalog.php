<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Race;

use JsonSerializable;
use Vigilante\PixelArt\Compositor;
use Vigilante\PixelArt\Grid;
use Vigilante\PixelArt\Layer;
use Vigilante\PixelArt\PixelFont;

/**
 * Le bord de la route de la descente (palmiers, lampadaires, donut géant, panneaux),
 * les obstacles sur la chaussée et l'arche d'arrivée. Clé « race.roadside » des données du jeu.
 * Les panneaux reprennent les enseignes du thème du niveau (« sign-SKATE »...).
 */
final class RoadsideCatalog implements JsonSerializable
{
    private const PALETTE = [
        'K' => '#120a18',
        'G' => '#2f7a3a', 'g' => '#58b04a', 'T' => '#8a5a30', 't' => '#5e3a1e',  // palmier
        'P' => '#6e6e82', 'p' => '#b8b8c8', 'Y' => '#ffe07a', 'y' => '#ffb020',  // lampadaire
        'O' => '#ff7a1e', 'o' => '#c04a10', 'W' => '#f4f4f4', 'w' => '#c8c8d4',  // cône
        'R' => '#e8203a', 'r' => '#8f1424', 'B' => '#2a4a9a', 'b' => '#7fa0ff',
        'D' => '#c88a4a', 'd' => '#8a5a2a', 'L' => '#ff7ab8', 'l' => '#ffb0d8',  // donut
        'N' => '#1c1a20', 'n' => '#3a3644',
    ];

    private const PALM_CROWN = [
        '..........GG.......GG.........',
        '.......GGGGgG....GGgGGG.......',
        '....GGGGg...GG..GG...gGGGG....',
        '..GGGg.......GGGG.......gGGG..',
        '.GGg......GGGGggGGGG......gGG.',
        'GG......GGg..GTTG..gGG......GG',
        'G......GG...GgTTgG...GG......G',
        '......G....GG.TT.GG....G......',
        '..........G...TT...G..........',
        '..............Tt..............',
    ];

    private const CONE = [
        '....O....',
        '...OOo...',
        '...WWw...',
        '..OOOOo..',
        '..OOOOo..',
        '.WWWWWWw.',
        '.OOOOOOo.',
        'ooooooooo',
    ];

    private const BARRIER = [
        'RRRWWWRRRWWWRRRWWWRRRWWWRR',
        'RRRWWWRRRWWWRRRWWWRRRWWWRR',
        'rrrwwwrrrwwwrrrwwwrrrwwwrr',
        '..P....................P..',
        '..P....................P..',
        '.PPP..................PPP.',
        'PP.PP................PP.PP',
    ];

    private const DONUT = [
        '.......DDDDDDDD.......',
        '....DDLLLLLLLLLLDD....',
        '..DDLLlLLLbLLLLlLLDD..',
        '.DLLLLLRLLLLLLLLbLLLD.',
        'DLLbLLLLLL....LLLLLLLD',
        'DLLLLLlLL......LLRLLLD',
        'DdLLLLLLL......LLLLLdD',
        'DdDLLRLLLL....LLLLLDdD',
        '.dDDLLLLLLLbLLLLLDDDd.',
        '..ddDDDLLLLLLLDDDDdd..',
        '....ddDDDDDDDDDDdd....',
        '.......dddddddd.......',
    ];

    /** @var array<string, list<string>>|null */
    private ?array $grids = null;

    /** @param list<string> $signs textes des panneaux (enseignes du thème) */
    public function __construct(private array $signs = [])
    {
    }

    /** @return array<string, list<string>> */
    public function grids(): array
    {
        if ($this->grids !== null) {
            return $this->grids;
        }
        $sprites = [
            'palm' => self::palm(),
            'lamp' => self::lamp(),
            'cone' => self::CONE,
            'barrier' => self::BARRIER,
            'donut' => self::donut(),
        ];
        foreach ($this->signs as $text) {
            $sprites["sign-$text"] = self::billboard($text);
        }
        $grids = array_map(
            fn(array $rows) => Compositor::compose(max(array_map('strlen', $rows)), count($rows), [new Layer($rows)]),
            $sprites,
        );

        return $this->grids = $grids + ['gate' => self::gate()];
    }

    /** @return array{palette: array<string, string>, sprites: array<string, list<string>>} */
    public function jsonSerialize(): array
    {
        return ['palette' => self::PALETTE, 'sprites' => $this->grids()];
    }

    /**
     * Palmier de Los Angeles : un tronc très haut qui ondule, une touffe de palmes en haut.
     *
     * @return list<string>
     */
    private static function palm(): array
    {
        $rows = self::PALM_CROWN;
        for ($y = 0; $y < 56; $y++) {
            $x = 14 + (int) round(sin($y / 14) * 2);
            $bark = $y % 4 === 0 ? 'tTt' : 'TTt';
            $rows[] = str_pad(str_repeat('.', $x) . $bark, 30, '.');
        }

        return $rows;
    }

    /** @return list<string> lampadaire, la lumière penchée vers la droite (la route, à gauche du joueur) */
    private static function lamp(): array
    {
        $rows = [
            '.pPPPPPPPPP...',
            'pP........PP..',
            'P..........yY.',
            'P.........yYYy',
        ];
        for ($y = 0; $y < 46; $y++) {
            $rows[] = 'Pp............';
        }
        $rows[] = 'PPp...........';

        return $rows;
    }

    /** @return list<string> le donut géant sur son mât, comme devant les boutiques de L.A. */
    private static function donut(): array
    {
        $rows = self::DONUT;
        for ($y = 0; $y < 30; $y++) {
            $rows[] = '.........PPp..........';
        }

        return $rows;
    }

    /**
     * Panneau publicitaire sur deux pieds, au texte d'une enseigne du thème.
     *
     * @return list<string>
     */
    private static function billboard(string $text): array
    {
        $width = PixelFont::width($text) + 10;
        $text = array_map(fn(string $line) => 'RnnnN' . strtr($line, ['.' => 'N']) . 'NnnnR', PixelFont::write($text, 'Y'));
        $border = str_repeat('R', $width);
        $blank = 'R' . str_repeat('N', $width - 2) . 'R';
        $leg = str_pad(str_repeat('.', 3) . 'Pp', $width - 5, '.') . 'Pp...';

        return [$border, $blank, ...$text, $blank, $border, ...array_fill(0, 14, $leg)];
    }

    /**
     * L'arche d'arrivée, aussi large que la route : deux poteaux et une banderole à damier.
     *
     * @return list<string>
     */
    private static function gate(): array
    {
        $width = 250;
        $text = 'SKATEPARK';
        $textWidth = PixelFont::width($text) * 2;
        $left = intdiv($width - $textWidth, 2);
        $rows = [];
        for ($y = 0; $y < 16; $y++) {
            $row = '';
            for ($x = 0; $x < $width; $x++) {
                $row .= match (true) {
                    $x < 6 || $x >= $width - 6 => 'P',
                    $x >= $left - 3 && $x < $left + $textWidth + 3 && $y >= 1 && $y <= 14 => 'R',
                    default => (intdiv($x, 4) + intdiv($y, 4)) % 2 ? 'W' : 'N',
                };
            }
            $rows[] = $row;
        }
        // texte en gros : chaque pixel de la police devient un carré de 2 × 2
        foreach (PixelFont::write($text, 'W') as $y => $line) {
            $big = strtr(implode('', array_map(fn(string $c) => $c . $c, str_split($line))), ['.' => 'R']);
            $rows[2 * $y + 3] = substr_replace($rows[2 * $y + 3], $big, $left, $textWidth);
            $rows[2 * $y + 4] = substr_replace($rows[2 * $y + 4], $big, $left, $textWidth);
        }
        for ($y = 0; $y < 60; $y++) {
            $rows[] = 'PPPPPp' . str_repeat(Grid::EMPTY, $width - 12) . 'PPPPPp';
        }

        return Compositor::compose($width, count($rows), [new Layer($rows)]);
    }
}
