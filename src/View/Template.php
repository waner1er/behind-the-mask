<?php

declare(strict_types=1);

namespace Vigilante\View;

/** Rend un gabarit PHP de templates/ avec ses variables. */
final readonly class Template
{
    public function __construct(private string $directory)
    {
    }

    /** @param array<string, mixed> $variables */
    public function render(string $name, array $variables): string
    {
        $file = $this->directory . '/' . $name . '.php';
        $render = static function (string $__file, array $__variables): void {
            extract($__variables);
            require $__file;
        };

        ob_start();
        try {
            $render($file, $variables);
        } finally {
            $html = (string) ob_get_clean();
        }

        return $html;
    }
}
