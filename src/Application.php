<?php

declare(strict_types=1);

namespace Vigilante;

use Vigilante\Album\Album;
use Vigilante\Album\AlbumLoader;
use Vigilante\Album\PhraseExtractor;
use Vigilante\Game\GameData;
use Vigilante\Level\LevelFactory;
use Vigilante\Level\SceneCatalog;
use Vigilante\Scene\SceneRenderer;
use Vigilante\Sprite\PropCatalog;
use Vigilante\Support\ConfigRepository;
use Vigilante\View\PageRenderer;

/**
 * Point d'assemblage de l'application : crée chaque service une seule fois, à la demande.
 * C'est le seul endroit qui connaît les dépendances concrètes (injection de dépendances « à la main »).
 */
final class Application
{
    private ?ConfigRepository $config = null;
    private ?Album $album = null;
    private ?PropCatalog $props = null;
    private ?LevelFactory $levels = null;

    public function __construct(public readonly string $root)
    {
    }

    public function config(): ConfigRepository
    {
        return $this->config ??= new ConfigRepository($this->root . '/config');
    }

    public function album(): Album
    {
        return $this->album ??= (new AlbumLoader($this->root, $this->config()->get('game')['album']['directory']))->load();
    }

    public function props(): PropCatalog
    {
        return $this->props ??= new PropCatalog();
    }

    public function levels(): LevelFactory
    {
        return $this->levels ??= new LevelFactory(
            $this->config()->get('levels'),
            new PhraseExtractor($this->config()->get('game')['album']['excluded']),
        );
    }

    public function scenes(): SceneCatalog
    {
        return new SceneCatalog($this->album(), $this->levels(), new SceneRenderer($this->props()));
    }

    public function gameData(): GameData
    {
        return new GameData($this->config(), $this->album(), $this->levels(), $this->props());
    }

    public function page(): PageRenderer
    {
        return new PageRenderer($this);
    }
}
