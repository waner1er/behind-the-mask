<?php

declare(strict_types=1);

namespace Vigilante\Http;

use Vigilante\Level\SceneCatalog;

/** scene.php?level=3 : le décor SVG d'un niveau ; scene.php?level=peace : la ville libérée. */
final readonly class SceneController
{
    public function __construct(private SceneCatalog $scenes)
    {
    }

    /** @param array<string, mixed> $query */
    public function handle(array $query): void
    {
        $level = (string) ($query['level'] ?? '0');

        if ($level === SceneCatalog::PEACE) {
            header('Content-Type: text/html; charset=utf-8');
            echo $this->scenes->peace();

            return;
        }

        $index = (int) $level;
        if (!$this->scenes->has($index)) {
            http_response_code(404);

            return;
        }

        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        echo $this->scenes->level($index);
    }
}
