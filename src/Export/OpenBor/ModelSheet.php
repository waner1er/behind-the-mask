<?php

declare(strict_types=1);

namespace Vigilante\Export\OpenBor;

use Vigilante\Sprite\Character\Skeleton;
use Vigilante\Sprite\SpriteSheet;

/**
 * Les images d'un modèle OpenBOR : la planche du jeu web, agrandie pour les boss (comme le
 * « scale » du JS, mais au pixel près) et complétée d'images couchées pour les chutes.
 * Clés des images : « animation.numéro » (« attack.1 », « fall.0 »).
 */
final readonly class ModelSheet
{
    /**
     * @param array<array-key, string> $palette
     * @param array<string, list<string>> $frames
     * @param array<string, string> $anchors point d'ancrage propre à certaines images (« x y »)
     */
    public function __construct(
        public array $palette,
        public array $frames,
        public int $anchorX,
        public int $anchorY,
        public float $scale = 1,
        public array $anchors = [],
    ) {
    }

    /**
     * Planche d'un objet (bonus, projectile) : ancré au milieu de son bord inférieur.
     *
     * @param array<array-key, string> $palette
     * @param array<string, list<string>> $frames
     */
    public static function prop(array $palette, array $frames): self
    {
        $anchors = [];
        foreach ($frames as $key => $grid) {
            $anchors[$key] = intdiv(strlen($grid[0]), 2) . ' ' . count($grid);
        }

        return new self($palette, $frames, 0, 0, 1, $anchors);
    }

    /** @param float $scale multiple de 0,5 (1, 1,5, 2...) */
    public static function from(SpriteSheet $sheet, float $scale = 1): self
    {
        $frames = [];
        foreach ($sheet->frames as $anim => $images) {
            foreach ($images as $i => $grid) {
                $frames["$anim.$i"] = self::enlarge($grid, $scale);
            }
        }
        // couché sur le dos, la tête vers l'arrière : l'image « K.O. » (ou l'attente) pivotée
        $frames['fall.0'] = self::rotate($frames['dead.0'] ?? $frames['idle.1']);
        $fall = $frames['fall.0'];

        return new self(
            $sheet->palette,
            $frames,
            (int) round(Skeleton::ANCHOR_X * $scale),
            (int) round(Skeleton::ANCHOR_Y * $scale),
            $scale,
            ['fall.0' => intdiv(strlen($fall[0]), 2) . ' ' . count($fall)],
        );
    }

    /** Point d'ancrage d'une image (les images couchées et les objets ont le leur). */
    public function anchor(string $key): string
    {
        return $this->anchors[$key] ?? "$this->anchorX $this->anchorY";
    }

    /**
     * Agrandissement au plus proche voisin : ×1,5 répète une ligne et une colonne sur deux.
     *
     * @param list<string> $grid
     * @return list<string>
     */
    public static function enlarge(array $grid, float $scale): array
    {
        if ($scale === 1.0) {
            return $grid;
        }
        $height = count($grid);
        $width = strlen($grid[0]);
        $rows = [];
        for ($y = 0, $newHeight = (int) round($height * $scale); $y < $newHeight; $y++) {
            $source = $grid[min($height - 1, (int) floor($y / $scale))];
            $row = '';
            for ($x = 0, $newWidth = (int) round($width * $scale); $x < $newWidth; $x++) {
                $row .= $source[min($width - 1, (int) floor($x / $scale))];
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Quart de tour vers l'arrière (le personnage regarde à droite : il tombe la tête à gauche),
     * en ne gardant que les lignes et colonnes utiles.
     *
     * @param list<string> $grid
     * @return list<string>
     */
    public static function rotate(array $grid): array
    {
        $height = count($grid);
        $width = strlen($grid[0]);
        $rows = [];
        for ($x = $width - 1; $x >= 0; $x--) {
            $row = '';
            for ($y = 0; $y < $height; $y++) {
                $row .= $grid[$y][$x];
            }
            $rows[] = $row;
        }

        return self::trim($rows);
    }

    /**
     * @param list<string> $rows
     * @return list<string>
     */
    private static function trim(array $rows): array
    {
        $rows = array_values(array_filter($rows, fn($row) => trim($row, '.') !== ''));
        $left = min(array_map(fn($row) => strspn($row, '.'), $rows));
        $right = max(array_map(fn($row) => strlen(rtrim($row, '.')), $rows));

        return array_map(fn($row) => '.' . substr($row, $left, $right - $left) . '.', $rows);
    }
}
