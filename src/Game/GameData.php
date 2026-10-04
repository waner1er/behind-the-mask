<?php

declare(strict_types=1);

namespace Vigilante\Game;

use Vigilante\Album\Album;
use Vigilante\Level\LevelFactory;
use Vigilante\Scene\Screen;
use Vigilante\Sprite\CharacterCatalog;
use Vigilante\Sprite\PropCatalog;
use Vigilante\Support\ConfigRepository;

/**
 * Toutes les données envoyées au JavaScript, en JSON dans la page (#game-data).
 * Voir docs/architecture.md pour le détail de chaque clé.
 */
final readonly class GameData
{
    public function __construct(
        private ConfigRepository $config,
        private Album $album,
        private LevelFactory $levels,
        private PropCatalog $props,
    ) {
    }

    /**
     * @param bool $static version statique (GitHub Pages) : les décors sont des fichiers HTML
     * @return array<string, mixed>
     */
    public function toArray(bool $static): array
    {
        $game = $this->config->get('game');

        return [
            'width' => Screen::WIDTH,
            'height' => Screen::HEIGHT,
            'levelLength' => LevelFactory::LENGTH,
            'floor' => ['min' => Screen::GROUND + 6, 'max' => Screen::HEIGHT - 6],
            'enemies' => $game['enemies'],
            'weaponDuration' => $game['weaponDuration'],
            'weapons' => $game['weapons'],
            'levels' => array_map($this->levels->build(...), $this->album->tracks),
            'links' => $this->album->links,
            'story' => $this->config->get('story'),
            'logo' => $game['logo'],
            'sceneUrl' => $static ? 'scenes/level-%d.html' : 'scene.php?level=%d',
            'sprites' => CharacterCatalog::all(),
            'items' => $this->props,
        ];
    }

    public function toJson(bool $static): string
    {
        return json_encode($this->toArray($static), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_THROW_ON_ERROR);
    }
}
