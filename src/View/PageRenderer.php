<?php

declare(strict_types=1);

namespace Vigilante\View;

use Vigilante\Application;
use Vigilante\Scene\Screen;

/** La page du jeu : la borne d'arcade, le décor du premier niveau et les données du jeu. */
final readonly class PageRenderer
{
    public function __construct(private Application $app)
    {
    }

    /** @param bool $static version statique pour GitHub Pages (voir Build\StaticSiteBuilder) */
    public function render(bool $static = false): string
    {
        $game = $this->app->config()->get('game');
        $assets = new AssetVersioner($this->app->root);

        return (new Template($this->app->root . '/templates'))->render('page', [
            'band' => $game['band'],
            'title' => $game['title'],
            'year' => $this->app->album()->year ?? 2026,
            'logo' => $game['logo'],
            'width' => Screen::WIDTH,
            'height' => Screen::HEIGHT,
            'scene' => $this->app->scenes()->level(0),
            'gameJson' => $this->app->gameData()->toJson($static),
            'assets' => $assets,
            'importMap' => $assets->importMap('js'),
        ]);
    }
}
