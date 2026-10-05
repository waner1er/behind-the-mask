<?php

declare(strict_types=1);

namespace Vigilante\Export\OpenBor;

use Vigilante\Level\LevelFactory;
use Vigilante\Scene\Screen;
use Vigilante\Support\SeededRandom;

/**
 * Un niveau OpenBOR, comme dans le jeu web : les plans du décor (fond fixe, parallaxe, rue),
 * la musique, les textes (intro de mission, « WAVE n », paroles criées par les ennemis),
 * les vagues qui bloquent la caméra (Camera.js), les otages, la caisse « ? » et le boss.
 */
final readonly class LevelExport
{
    /** Profondeur où marchent les personnages (le trottoir), comme data.floor côté JS. */
    public const Z_MIN = Screen::GROUND + 6;
    public const Z_MAX = Screen::HEIGHT - 6;

    /** Un ennemi sans arme lâche une bière une fois sur sept environ (Combat.#loot : 15 %). */
    private const BEER_CHANCE = 15;

    /**
     * @param array<string, mixed> $level données d'un niveau (LevelFactory::build)
     * @param array<array-key, IndexedImage> $layers facteur de défilement => plan du décor
     * @param array<string, array<string, mixed>> $enemies config/game.php « enemies »
     */
    public function __construct(
        private array $level,
        private array $layers,
        private string $music,
        private array $enemies,
    ) {
    }

    public function number(): int
    {
        return (int) $this->level['number'];
    }

    public function path(): string
    {
        return sprintf('data/levels/%02d.txt', $this->number());
    }

    /** @return array<string, string> */
    public function files(): array
    {
        $dir = sprintf('data/bgs/%02d', $this->number());
        $files = [];
        $lines = ["music\t$this->music"];
        foreach ($this->layers as $factor => $image) {
            $factor = (string) $factor; // « 0 » et « 1 » sont devenus des clés entières
            $file = $factor === '0' ? "$dir/sky.png" : "$dir/layer-" . str_replace('.', '_', $factor) . '.png';
            $files[$file] = PngEncoder::encode($image);
            $lines[] = match ($factor) {
                '0' => "background\t$file\t0\t0",
                '1' => "panel\t$file",
                // xratio, zratio, x, z, espacements, répétition infinie en x, une fois en z, transparence
                default => "bglayer\t$file\t$factor\t0\t0\t0\t0\t0\t-1\t1\t1\t0",
            };
        }
        $lines[] = "order\t" . str_repeat('a', intdiv(LevelFactory::LENGTH, Screen::WIDTH));
        $lines[] = "direction\tright";
        // pas de chrono, comme dans le jeu web (il ne compte que le « continue » après un game over)
        $lines[] = "settime\t0";
        $lines[] = "notime\t1";
        // départ des joueurs : profondeur comptée depuis le haut du trottoir
        $lines[] = "spawn1\t70 6";
        $lines[] = sprintf("spawn2\t48 %d", self::Z_MAX - self::Z_MIN - 6);

        // OpenBOR lit les apparitions dans l'ordre et attend chaque position de caméra (at) :
        // on les range par position (à égalité, dans l'ordre où elles sont ajoutées)
        $groups = [];
        $script = sprintf('data/scripts/levels/%02d.c', $this->number());
        $files[$script] = $this->introScript();
        $groups[] = [0, $this->event($script, 0)];

        $random = new SeededRandom($this->number() * 53);
        foreach ($this->level['pows'] as $x) {
            $groups[] = $this->placed('Pow', $x, self::Z_MIN + 1, $this->powReward($random));
        }
        $box = $this->level['wodBox'];
        $groups[] = $this->placed('Wodbox', $box['x'], self::Z_MIN + $box['y'], 'Wod');

        foreach ($this->level['waves'] as $i => $wave) {
            if ($i > 0 && !isset($wave['boss'])) {
                $script = sprintf('data/scripts/levels/%02d-wave%d.c', $this->number(), $i + 1);
                $files[$script] = $this->waveScript($i + 1, $random);
                $groups[] = [$wave['at'], $this->event($script, $wave['at'])];
            }
            $groups[] = [$wave['at'], $this->wave($wave, $random)];
        }
        usort($groups, fn($a, $b) => $a[0] <=> $b[0]);
        foreach ($groups as [, $group]) {
            $lines = [...$lines, '', ...$group];
        }
        $files[$this->path()] = Latin1::encode(implode("\n", $lines) . "\n");

        return $files;
    }

    /**
     * La caméra s'arrête (wait) et les ennemis arrivent des deux côtés, comme dans Camera.js.
     * Un ennemi armé lâche son arme ; les autres, parfois une bière.
     *
     * @param array{at: int, enemies: list<string>, boss?: array<string, mixed>} $wave
     * @return list<string>
     */
    private function wave(array $wave, SeededRandom $random): array
    {
        $lines = ['wait', "at\t{$wave['at']}"];
        $span = self::Z_MAX - self::Z_MIN;
        foreach ($wave['enemies'] as $i => $type) {
            $fromRight = $i % 2 === 0;
            $x = $fromRight ? Screen::WIDTH + 16 + $i * 14 : -16 - $i * 14;
            $weapon = $this->enemies[$type]['weapon'] ?? null;
            $item = $weapon !== null ? 'Item_' . $weapon : ($random->chance(self::BEER_CHANCE) ? 'Beer' : null);
            $lines[] = 'spawn	' . EnemyModels::modelName($type);
            // le nom affiché au-dessus de sa barre de vie, comme dans le jeu web (« SKIN À BATTE »)
            $lines[] = 'alias	' . self::displayName($this->enemies[$type]['name']);
            if ($item !== null) {
                $lines[] = "item\t$item";
            }
            $lines[] = sprintf("coords\t%d %d", $x, self::Z_MIN + ($i * 11) % $span);
            $lines[] = "at\t{$wave['at']}";
        }
        if (isset($wave['boss'])) {
            $lines[] = 'spawn	' . BossModels::modelName((string) $wave['boss']['sprite']);
            $lines[] = 'alias	' . self::displayName((string) $wave['boss']['name']);
            $lines[] = 'boss	1';
            $lines[] = sprintf("coords\t%d %d", Screen::WIDTH + 30, intdiv(self::Z_MIN + self::Z_MAX, 2));
            $lines[] = "at\t{$wave['at']}";
        }

        return $lines;
    }

    /**
     * Nom affiché d'un ennemi : un seul mot pour le moteur (qui garde les guillemets), d'où des
     * espaces insécables (0xA0 en Latin-1, une case vide de la police).
     */
    private static function displayName(string $name): string
    {
        return str_replace(' ', "\u{00A0}", $name);
    }

    /**
     * Un objet posé dans le niveau (otage, caisse), qui apparaît quand il entre dans l'écran.
     *
     * @return array{int, list<string>} position de caméra, lignes
     */
    private function placed(string $model, int $x, int $z, string $item): array
    {
        $at = max(0, $x - Screen::WIDTH + 20);

        return [$at, ["spawn\t$model", "item\t$item", sprintf("coords\t%d %d", $x - $at, $z), "at\t$at"]];
    }

    /** Ce que lâche un otage libéré (Hostages.free) : vinyles, bière ou une arme. */
    private function powReward(SeededRandom $random): string
    {
        $roll = $random->int(0, 99);

        return match (true) {
            $roll < 50 => 'Vinyls',
            $roll < 75 => 'Beer',
            default => 'Item_' . $random->pick(PropModels::PICKABLE),
        };
    }

    /**
     * Un « événement » invisible qui lance un script quand la caméra atteint $at.
     *
     * @return list<string>
     */
    private function event(string $script, int $at): array
    {
        return ['spawn	Event', "spawnscript\t$script", "coords\t160 " . self::Z_MIN, "at\t$at"];
    }

    /** Début du niveau : les paroles que crient les ennemis, et le titre (IntroMode, 3,5 s). */
    private function introScript(): string
    {
        $level = $this->level;
        $lines = ['#include "data/scripts/lib.c"', '', 'void main()', '{'];
        $lines[] = '    setglobalvar("shoutCount", ' . count($level['shouts']) . ');';
        foreach ($level['shouts'] as $i => $shout) {
            $lines[] = "    setglobalvar(\"shoutLine$i\", " . Latin1::literal($shout) . ');';
        }
        $intro = [...$level['intro'], '', ''];
        $lines[] = sprintf(
            '    message(%s, %s, %s, %s, %s, 3.5);',
            Latin1::literal("MISSION {$level['number']} · START!"),
            Latin1::literal($level['title']),
            Latin1::literal($level['feat'] !== null ? "FEAT. {$level['feat']}" : ''),
            Latin1::literal($intro[0]),
            Latin1::literal($intro[1]),
        );
        $lines[] = '}';

        return Latin1::encode(implode("\n", $lines) . "\n");
    }

    /** « WAVE n » et une phrase des paroles (Camera.#spawnWave). */
    private function waveScript(int $number, SeededRandom $random): string
    {
        $shouts = array_values(array_filter((array) $this->level['shouts'], 'is_string'));
        $lyric = $shouts !== [] ? $random->pick($shouts) : '';

        return Latin1::encode(implode("\n", [
            '#include "data/scripts/lib.c"',
            '',
            'void main()',
            '{',
            sprintf('    message("WAVE %d", "", %s, "", "", 1.8);', $number, Latin1::literal($lyric)),
            '}',
        ]) . "\n");
    }
}
