<?php

declare(strict_types=1);

namespace Vigilante\Export\OpenBor;

/**
 * Les bruitages du jeu web (js/audio/sounds.js), enregistrés en WAV par tools/record-sounds.mjs,
 * et les sons que le moteur va chercher à des noms fixes (data/sounds/beat1.wav...).
 */
final class SoundBank
{
    /** Ennemis qui ont leur propre cri de mort (VOICES de sounds.js). */
    public const VOICES = ['skinhead', 'batter', 'chainer', 'hooligan', 'masculinist', 'knifer', 'gymbro'];

    /** Tailles de boss : leur cri est d'autant plus grave qu'ils sont gros. */
    public const BOSS_SCALES = ['1.5', '2', '2.5', '3'];

    private const SOUNDS = [
        'hit', 'heavyHit', 'hurt', 'metal', 'glass', 'explosion', 'throw', 'skate', 'pickup', 'select', 'start',
        'warning', 'teleport', 'clear', 'scratch', 'slash', 'whoosh', 'oneup', 'wod', 'kick', 'land', 'lunge',
        'empty', 'freed', 'gameOver', 'heroDeath',
    ];

    /** Sons du moteur => bruitage du jeu web. */
    private const ENGINE = [
        'beat1' => 'hit', 'block' => 'metal', 'fall' => 'land', 'get' => 'pickup', 'money' => 'pickup',
        'jump' => 'kick', 'indirect' => 'hit', 'punch' => 'whoosh', '1up' => 'oneup', 'timeover' => 'gameOver',
        'beep' => 'select', 'beep2' => 'start', 'bike' => 'skate', 'go' => 'select', 'pause' => 'select',
    ];

    /** @return list<string> arguments de tools/record-sounds.mjs (« death:skinhead »...) */
    public static function recordings(): array
    {
        return [
            ...self::SOUNDS,
            ...array_map(fn($voice) => "death:$voice", self::VOICES),
            ...array_map(fn($scale) => "bossDeath:$scale", self::BOSS_SCALES),
        ];
    }

    public static function path(string $name): string
    {
        return "data/sounds/$name.wav";
    }

    /** Cri de mort d'un ennemi (une voix par type, comme sounds.js). */
    public static function death(string $type): string
    {
        return self::path(in_array($type, self::VOICES, true) ? "death-$type" : 'death-skinhead');
    }

    public static function bossDeath(float $scale): string
    {
        $closest = 0;
        foreach (self::BOSS_SCALES as $i => $size) {
            if (abs((float) $size - $scale) < abs((float) self::BOSS_SCALES[$closest] - $scale)) {
                $closest = $i;
            }
        }

        return self::path('bossDeath-' . self::BOSS_SCALES[$closest]);
    }

    /**
     * @param string $directory dossier des WAV enregistrés (nom[-argument].wav)
     * @return array<string, string>
     */
    public static function files(string $directory): array
    {
        $files = [];
        foreach (glob("$directory/*.wav") ?: [] as $wav) {
            $files[self::path(basename($wav, '.wav'))] = (string) file_get_contents($wav);
        }
        foreach (self::ENGINE as $engine => $sound) {
            $files[self::path($engine)] = $files[self::path($sound)];
        }

        return $files;
    }
}
