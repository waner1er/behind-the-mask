<?php

declare(strict_types=1);

namespace Vigilante\Export\OpenBor;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use Vigilante\Scene\Screen;

/**
 * Découpe un décor SVG du jeu en plans : le fond fixe (ciel), puis chaque .layer[data-factor].
 * Chaque plan devient un document SVG autonome de la taille de l'écran, à fond transparent ;
 * comme le dessin d'un plan se répète tous les 320 px, ce premier écran est sa « tuile ».
 */
final class SceneLayers
{
    /** @return array<array-key, string> facteur de défilement (0 = fixe ; « 0 » et « 1 » deviennent des entiers) => SVG */
    public static function split(string $scene): array
    {
        $document = new DOMDocument();
        $svg = '<svg xmlns="http://www.w3.org/2000/svg">' . $scene . '</svg>';
        if (!$document->loadXML($svg)) {
            throw new RuntimeException('Décor SVG invalide');
        }
        $root = $document->documentElement ?? throw new RuntimeException('Décor SVG vide');
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('s', 'http://www.w3.org/2000/svg');

        $layers = [];
        foreach ($xpath->query('/s:svg/s:g[contains(concat(" ", @class, " "), " layer ")]') ?: [] as $layer) {
            if ($layer instanceof DOMElement) {
                $layers[$layer->getAttribute('data-factor')] = $layer;
            }
        }

        $result = ['0' => self::wrap($document, $root, fn($node) => !in_array($node, $layers, true))];
        foreach ($layers as $factor => $layer) {
            $result[(string) $factor] = self::wrap(
                $document,
                $root,
                fn($node) => $node === $layer || ($node instanceof DOMElement && $node->tagName === 'defs'),
            );
        }

        return $result;
    }

    /** @param callable(\DOMNode): bool $keep */
    private static function wrap(DOMDocument $document, DOMElement $root, callable $keep): string
    {
        $content = '';
        foreach ($root->childNodes as $node) {
            if ($keep($node)) {
                $content .= $document->saveXML($node);
            }
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %1$d %2$d" width="%1$d" height="%2$d" '
            . 'shape-rendering="crispEdges">%3$s</svg>',
            Screen::WIDTH,
            Screen::HEIGHT,
            $content,
        );
    }
}
