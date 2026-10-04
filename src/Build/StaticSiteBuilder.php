<?php

declare(strict_types=1);

namespace Vigilante\Build;

use Vigilante\Application;
use Vigilante\Level\SceneCatalog;

/**
 * Génère la version statique du jeu pour GitHub Pages (qui n'exécute pas PHP) :
 * index.html et un fichier scenes/level-N.html par décor.
 */
final readonly class StaticSiteBuilder
{
    public function __construct(private Application $app)
    {
    }

    /** @return list<string> fichiers écrits, relatifs à la racine du projet */
    public function build(): array
    {
        $written = [$this->write('index.html', $this->app->page()->render(static: true))];

        $scenes = $this->app->scenes();
        foreach (array_keys($this->app->album()->tracks) as $index) {
            $written[] = $this->write("scenes/level-$index.html", $scenes->level($index));
        }
        $written[] = $this->write('scenes/level-' . SceneCatalog::PEACE . '.html', $scenes->peace());

        return $written;
    }

    private function write(string $file, string $content): string
    {
        $path = $this->app->root . '/' . $file;
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, $content);

        return $file;
    }
}
