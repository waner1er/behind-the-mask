<?php

declare(strict_types=1);

namespace Vigilante\Export\OpenBor;

/**
 * Un modèle OpenBOR (personnage, projectile, objet) : sa fiche texte (data/chars/<nom>/<nom>.txt)
 * et une image PNG par image utilisée de sa planche, toutes avec la même palette.
 */
final readonly class CharacterModel
{
    /** Corps qu'on peut toucher, autour des pieds (x, y depuis le sol, largeur, hauteur), à l'échelle 1. */
    private const BODY = [-8, -34, 16, 34];

    /**
     * @param list<string> $header commandes d'en-tête (« type player », « health 100 »...)
     * @param list<Animation> $animations
     * @param bool $hittable false pour un projectile ou un objet : pas de corps à toucher
     */
    public function __construct(
        public string $name,
        private ModelSheet $sheet,
        private array $header,
        private array $animations,
        private bool $hittable = true,
    ) {
    }

    public function directory(): string
    {
        return "data/chars/$this->name";
    }

    public function path(): string
    {
        return "{$this->directory()}/$this->name.txt";
    }

    /** @return array<string, string> fichiers du module (chemin => contenu) */
    public function files(): array
    {
        $files = [];
        $chars = array_map('strval', array_keys($this->sheet->palette));
        sort($chars);
        foreach ($this->usedSprites() as $key) {
            $image = IndexedImage::fromGrid($this->sheet->frames[$key], $chars, $this->sheet->palette);
            $files[$this->spritePath($key)] = PngEncoder::encode($image);
        }
        $files[$this->path()] = $this->text();

        return $files;
    }

    private function text(): string
    {
        $lines = ["name\t$this->name", ...$this->header];
        foreach ($this->animations as $animation) {
            $lines[] = '';
            $lines[] = "anim\t$animation->name";
            $lines[] = "\tloop\t" . ($animation->loop ? 1 : 0);
            foreach ($animation->extra as $command) {
                $lines[] = "\t$command";
            }
            $attacking = false;
            $offset = null;
            foreach ($animation->frames as $frame) {
                $anchor = $this->sheet->anchor($frame->sprite);
                if ($anchor !== $offset) {
                    $lines[] = "\toffset\t$anchor";
                    if ($this->hittable) {
                        $lines[] = "\tbbox\t" . $this->body($anchor, $frame->sprite);
                    }
                    $offset = $anchor;
                }
                $lines[] = "\tdelay\t$frame->delay";
                $lines[] = "\tmove\t$frame->move";
                if ($frame->hit !== null) {
                    $lines[] = "\tattack\t" . $this->attack($anchor, $frame->hit);
                    $attacking = true;
                } elseif ($attacking) {
                    $lines[] = "\tattack\t0";
                    $attacking = false;
                }
                foreach ($frame->commands as $command) {
                    $lines[] = "\t$command";
                }
                $lines[] = "\tframe\t" . $this->spritePath($frame->sprite);
            }
        }

        return implode("\n", $lines) . "\n";
    }

    /** Zone de corps : debout, ou allongé pour une image de chute. */
    private function body(string $anchor, string $sprite): string
    {
        [$ax, $ay] = array_map('intval', explode(' ', $anchor));
        if (str_starts_with($sprite, 'fall.')) {
            $grid = $this->sheet->frames[$sprite];

            return '1 ' . max(0, $ay - 10) . ' ' . (strlen($grid[0]) - 2) . ' 10';
        }
        $scale = $this->sheet->scale;
        [$bx, $by, $bw, $bh] = self::BODY;

        return sprintf('%d %d %d %d', $ax + $bx * $scale, $ay + $by * $scale, $bw * $scale, $bh * $scale);
    }

    /** attack x y largeur hauteur dégâts renverse imparable sans-flash pause profondeur */
    private function attack(string $anchor, Hit $hit): string
    {
        [$ax, $ay] = array_map('intval', explode(' ', $anchor));
        $scale = $this->sheet->scale;

        return sprintf(
            '%d %d %d %d %d %d 0 0 %d %d',
            $ax + $hit->from * $scale,
            $ay - $hit->above * $scale,
            ($hit->to - $hit->from) * $scale,
            $hit->height * $scale,
            $hit->damage,
            $hit->knockdown ? 1 : 0,
            $hit->pause,
            (int) round(7 + 4 * ($scale - 1)),
        );
    }

    /** @return list<string> images de la planche réellement utilisées, sans doublon */
    private function usedSprites(): array
    {
        $keys = [];
        foreach ($this->animations as $animation) {
            foreach ($animation->frames as $frame) {
                $keys[$frame->sprite] = true;
            }
        }

        return array_keys($keys);
    }

    private function spritePath(string $key): string
    {
        return "{$this->directory()}/" . str_replace('.', '', $key) . '.png';
    }
}
