<?php

declare(strict_types=1);

namespace Vigilante\Export\OpenBor;

use GdImage;
use RuntimeException;
use Vigilante\Scene\Screen;

/**
 * Une séquence animée (intro, fin) en scène OpenBOR : un GIF animé par plan, filmé dans le jeu web
 * (tools/capture-story.mjs), avec le texte réécrit en Press Start 2P à sa taille native (8 px)
 * pour rester lisible en 320×180 — la machine à écrire suit celle du jeu web, lettre pour lettre.
 * Le générique (plan « credits ») défile par-dessus la ville libérée.
 */
final readonly class StoryScene
{
    private const CHARS_PER_LINE = 37;
    private const LINE_HEIGHT = 10;
    private const MARGIN = 6;

    /** @param list<array{string, string}> $credits générique de config/story.php : [nom, métier] */
    public function __construct(
        private string $captures,
        private string $fontFile,
        private Toolchain $tools,
        private array $credits = [],
    ) {
    }

    /**
     * @param string $name nom de la scène (« intro »)
     * @param string $music musique de la scène (chemin dans le module)
     * @return array<string, string> le fichier de scène et ses GIF
     */
    public function files(string $name, string $music, string $work): array
    {
        $manifest = json_decode((string) file_get_contents("$this->captures/manifest.json"), true, flags: JSON_THROW_ON_ERROR);
        $files = [];
        $lines = ["music\t$music\t1"];
        foreach ($manifest as $i => $step) {
            $frames = [];
            $count = count($step['frames']);
            foreach ($step['frames'] as $n => $frame) {
                $png = sprintf('%s/%s-%02d-%03d.png', $work, $name, $i, $n);
                $image = $this->compose("$this->captures/{$frame['file']}", $step['text'], $frame['typed']);
                if ($step['scene'] === 'credits') {
                    $this->rollCredits($image, $n / max(1, $count - 1));
                }
                imagepng($image, $png);
                $frames[] = $png;
            }
            if ($frames === []) {
                continue;
            }
            $gif = sprintf('%s/%s-%02d.gif', $work, $name, $i);
            // une capture toutes les « every » images du jeu web (60 par seconde)
            $this->tools->animatedGif($frames, $gif, (int) round($step['every'] * 100 / 60));
            $path = sprintf('data/scenes/%s/%02d.gif', $name, $i + 1);
            $files[$path] = (string) file_get_contents($gif);
            // START passe au plan suivant (comme dans le jeu web), pas à la fin de la scène
            $lines[] = "animation\t$path\t0\t0\t1";
        }
        $files["data/scenes/$name.txt"] = implode("\n", $lines) . "\n";

        return $files;
    }

    /** Une image : la capture réduite à 320×180, la boîte de dialogue et le texte déjà tapé. */
    private function compose(string $capture, string $text, int $typed): GdImage
    {
        $source = @imagecreatefrompng($capture);
        if (!$source instanceof GdImage) {
            throw new RuntimeException("Capture illisible : $capture");
        }
        $image = imagecreatetruecolor(Screen::WIDTH, Screen::HEIGHT);
        imagecopyresized($image, $source, 0, 0, 0, 0, Screen::WIDTH, Screen::HEIGHT, Screen::WIDTH * 2, Screen::HEIGHT * 2);
        if ($text === '') {
            return $image;
        }

        $lines = self::wrap($text);
        $paper = imagecolorallocate($image, 0xec, 0xe8, 0xdc);
        $bottom = self::MARGIN + 6 + count($lines) * self::LINE_HEIGHT;
        imagefilledrectangle($image, self::MARGIN, self::MARGIN, Screen::WIDTH - self::MARGIN, $bottom, imagecolorallocate($image, 0x0c, 0x0a, 0x10));
        imagerectangle($image, self::MARGIN, self::MARGIN, Screen::WIDTH - self::MARGIN, $bottom, $paper);

        $left = $typed;
        foreach ($lines as $row => $line) {
            $shown = mb_substr($line, 0, max(0, $left));
            $left -= mb_strlen($line) + 1; // + l'espace avalée par le retour à la ligne
            $y = self::MARGIN + 4 + ($row + 1) * self::LINE_HEIGHT - 2;
            // 6 points = 8 px, taille native de la police ; couleur négative = sans lissage
            imagettftext($image, 6, 0, self::MARGIN + 6, $y, -$paper, $this->fontFile, $shown);
        }
        if ($typed >= mb_strlen($text)) {
            $red = imagecolorallocate($image, 0xe8, 0x20, 0x3a);
            $x = Screen::WIDTH - self::MARGIN - 9;
            imagefilledpolygon($image, [$x, $bottom - 8, $x + 4, $bottom - 6, $x, $bottom - 4], $red);
        }

        return $image;
    }

    /**
     * Le générique : nom en jaune, métier en blanc, qui montent de l'écran jusqu'à disparaître.
     *
     * @param float $progress 0 au début, 1 à la fin du défilement
     */
    private function rollCredits(GdImage $image, float $progress): void
    {
        // voile sombre sur la ville : le générique reste lisible sur le ciel clair
        imagealphablending($image, true);
        imagefilledrectangle($image, 0, 0, Screen::WIDTH, Screen::HEIGHT, imagecolorallocatealpha($image, 0x0c, 0x0a, 0x10, 50));
        $yellow = imagecolorallocate($image, 0xff, 0xd2, 0x3f);
        $paper = imagecolorallocate($image, 0xec, 0xe8, 0xdc);
        $red = imagecolorallocate($image, 0xe8, 0x20, 0x3a);
        $lines = [];
        foreach ($this->credits as [$who, $job]) {
            foreach (self::wrap($who) as $line) {
                $lines[] = [$line, $yellow];
            }
            foreach (self::wrap($job) as $line) {
                $lines[] = [$line, $paper];
            }
            $lines[] = ['', $paper];
        }
        $lines[] = ['THANKS FOR PLAYING', $red];
        $height = count($lines) * self::LINE_HEIGHT;
        $top = (int) round(Screen::HEIGHT - $progress * ($height + Screen::HEIGHT / 2));
        foreach ($lines as $row => [$text, $color]) {
            $y = $top + $row * self::LINE_HEIGHT;
            if ($text === '' || $y < -self::LINE_HEIGHT || $y > Screen::HEIGHT + self::LINE_HEIGHT) {
                continue;
            }
            $box = imagettfbbox(6, 0, $this->fontFile, $text) ?: [0, 0, 0, 0, 0, 0, 0, 0];
            $x = intdiv(Screen::WIDTH - ($box[2] - $box[0]), 2);
            imagettftext($image, 6, 0, $x + 1, $y + 1, -imagecolorallocate($image, 0x0c, 0x0a, 0x10), $this->fontFile, $text);
            imagettftext($image, 6, 0, $x, $y, -$color, $this->fontFile, $text);
        }
    }

    /** @return list<string> le texte coupé entre les mots, comme dans la boîte du jeu web */
    private static function wrap(string $text): array
    {
        $lines = [''];
        foreach (explode(' ', $text) as $word) {
            $current = $lines[count($lines) - 1];
            if ($current !== '' && mb_strlen("$current $word") > self::CHARS_PER_LINE) {
                $lines[] = $word;
            } else {
                $lines[count($lines) - 1] = $current === '' ? $word : "$current $word";
            }
        }

        return $lines;
    }
}
