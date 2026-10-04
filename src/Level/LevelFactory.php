<?php

declare(strict_types=1);

namespace Vigilante\Level;

use Vigilante\Album\PhraseExtractor;
use Vigilante\Album\Track;
use Vigilante\Scene\Screen;
use Vigilante\Scene\Theme;
use Vigilante\Support\SeededRandom;

/** Assemble chaque niveau (un par morceau) à partir de l'album et de config/levels.php. */
final readonly class LevelFactory
{
    /** Longueur de la rue d'un niveau, en pixels. */
    public const LENGTH = Screen::WIDTH * 5;

    private const GRAFFITI_LENGTH = 14;
    private const SHOUT_LENGTH = 22;

    /** @param array<int, array<string, mixed>> $config niveaux indexés par numéro de piste */
    public function __construct(private array $config, private PhraseExtractor $phrases)
    {
    }

    public function theme(Track $track): Theme
    {
        return Theme::fromArray($this->configFor($track)['theme']);
    }

    /**
     * Graffitis du décor : ceux du thème d'abord, puis des phrases courtes des paroles.
     *
     * @return list<string>
     */
    public function tags(Track $track): array
    {
        $short = $this->phrases->extract($track->lyrics, self::GRAFFITI_LENGTH);

        return array_values(array_unique(array_merge($this->configFor($track)['tags'], $short)));
    }

    /**
     * Le niveau tel que le lit le JavaScript.
     *
     * @return array<string, mixed>
     */
    public function build(Track $track): array
    {
        $config = $this->configFor($track);
        $theme = Theme::fromArray($config['theme']);
        $number = $track->number;

        return [
            'number' => $number,
            'title' => strtoupper($track->title),
            'feat' => $track->feat !== null ? strtoupper($track->feat) : null,
            'audio' => $track->audio,
            'intro' => array_map('strtoupper', array_slice($track->lyrics, 0, 2)),
            'shouts' => $this->phrases->extract($track->lyrics, self::SHOUT_LENGTH),
            'accent' => $theme->accent,
            'rain' => $theme->rain,
            'fog' => $theme->fog,
            'chaos' => $theme->chaos,
            'boss' => $config['boss'],
            'waves' => WaveGenerator::generate($number, $config['boss']),
            'wodBox' => $this->mysteryBox($number),
            // otages à libérer (position x dans le niveau)
            'pows' => [480 + $number * 7, 1040 - $number * 5],
        ];
    }

    /**
     * Caisse mystère contenant le Wall of Death : à un endroit différent dans chaque niveau.
     *
     * @return array{x: int, y: int} x dans le niveau, y = profondeur sous le trottoir
     */
    private function mysteryBox(int $number): array
    {
        $random = new SeededRandom($number * 131 + 7);

        return ['x' => $random->int(140, self::LENGTH - Screen::WIDTH - 40), 'y' => $random->int(2, 26)];
    }

    /** @return array<string, mixed> */
    private function configFor(Track $track): array
    {
        return $this->config[$track->number] ?? $this->config[1];
    }
}
