<?php

declare(strict_types=1);

namespace Vigilante\Level;

use OutOfRangeException;
use Vigilante\Album\Album;
use Vigilante\Scene\Celestial;
use Vigilante\Scene\RaceSceneRenderer;
use Vigilante\Scene\SceneRenderer;
use Vigilante\Scene\Sky;
use Vigilante\Scene\Theme;

/** Les décors SVG du jeu : un par niveau, plus la ville libérée de la fin. */
final readonly class SceneCatalog
{
    public const PEACE = 'peace';

    /** Préfixe des décors de descente en skate : « race-6 » pour le niveau 7. */
    public const RACE = 'race-';

    private const PEACE_TAGS = ['MERCI PETE', 'LIBRES !', 'BYE BYE IA', 'TOUT EST GRATUIT', 'PEACE'];

    public function __construct(
        private Album $album,
        private LevelFactory $levels,
        private SceneRenderer $renderer,
        private RaceSceneRenderer $raceRenderer = new RaceSceneRenderer(),
    ) {
    }

    /** @param int $index position du morceau dans l'album, à partir de 0 */
    public function has(int $index): bool
    {
        return $this->album->track($index) !== null;
    }

    public function level(int $index): string
    {
        $track = $this->album->track($index) ?? throw new OutOfRangeException("Pas de niveau $index");

        return $this->renderer->render($this->levels->theme($track), $this->levels->tags($track));
    }

    /** Le niveau commence-t-il par une descente en skate ? */
    public function hasRace(int $index): bool
    {
        $track = $this->album->track($index);

        return $track !== null && $this->levels->hasRace($track);
    }

    /** Le ciel de la descente en skate d'un niveau (la route est dessinée par le JavaScript). */
    public function race(int $index): string
    {
        $track = $this->album->track($index);
        if ($track === null || !$this->levels->hasRace($track)) {
            throw new OutOfRangeException("Pas de descente au niveau $index");
        }

        return $this->raceRenderer->render($this->levels->theme($track));
    }

    public function peace(): string
    {
        $theme = new Theme(
            seed: 2026,
            sky: Sky::Paper,
            celestial: Celestial::Sun,
            accent: '#ffc62a',
            signs: ['LOVE', 'GRATUIT', 'JARDIN', 'CAFÉ', 'LIBRE'],
            peace: true,
        );

        return $this->renderer->render($theme, self::PEACE_TAGS);
    }
}
