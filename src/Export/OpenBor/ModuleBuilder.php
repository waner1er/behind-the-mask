<?php

declare(strict_types=1);

namespace Vigilante\Export\OpenBor;

use RuntimeException;
use Vigilante\Application;
use Vigilante\Sprite\CharacterCatalog;
use Vigilante\Sprite\SpriteSheet;

/**
 * Le module OpenBOR du jeu (Vigilante.pak), pour la borne Recalbox : le même jeu que la version web,
 * fabriqué depuis les mêmes données (config/, sprites et décors PHP, bruitages et scènes du JS).
 * Pete en 1P, VigiBapt en 2P ; l'intro, les 9 niveaux et leurs boss, la fin et le générique.
 */
final readonly class ModuleBuilder
{
    /** Nom du fichier sur la borne : la fiche gamelist.xml y fait référence. */
    public const PAK_NAME = 'Vigilante';

    /** Héros par joueur : 1P Pete, 2P VigiBapt (mêmes noms de sprites que côté JS). */
    private const HEROES = ['hero' => 'Pete', 'bapt' => 'Bapt'];

    /** Jetons : autant de « continues » que de crédits (GAME OVER → START pour continuer). */
    private const CREDITS = 9;

    /**
     * @param bool $reuse garder les captures, sons, décors et musiques du build précédent (mise au point :
     *                    ne pas l'utiliser après avoir changé une scène, un son, un décor ou l'album)
     */
    public function __construct(
        private Application $app,
        private string $outDir,
        private Toolchain $tools,
        private bool $reuse = false,
    ) {
    }

    /** @return string le fichier .pak écrit */
    public function build(): string
    {
        $work = "$this->outDir/work";
        if (!$this->reuse || !is_dir($work)) {
            $this->reset($work);
        }

        $files = [
            'data/video.txt' => "video\t320x180\n",
            'data/translation.txt' => MenuTranslation::file(),
            ...(new SystemAssets($this->font(), $this->app->root . '/' . $this->game()['logo']))->files(),
            ...$this->scripts(),
            ...$this->sounds($work),
        ];

        $models = $this->models();
        foreach ($models as $model) {
            $files = [...$files, ...$model->files()];
        }
        $levels = $this->levels($work);
        foreach ($levels as $level) {
            $files = [...$files, ...$level->files()];
        }
        foreach ($this->levelData() as $level) {
            $files[BossModels::spawnScriptPath((string) $level['boss']['sprite'])] = BossModels::spawnScript($level['boss']);
        }
        $files = [...$files, ...$this->story($work, 'intro', 'prologue', 'introTrack')];
        $files = [...$files, ...$this->story($work, 'ending', 'ending', 'endingTrack')];
        $files['data/models.txt'] = $this->modelsText($models);
        $files['data/levels.txt'] = $this->levelsText($levels);
        $files += $this->music($work);

        $this->writeTree("$this->outDir/data", $files);
        $pak = "$this->outDir/" . self::PAK_NAME . '.pak';
        file_put_contents($pak, PakWriter::pack($files));
        $this->writeRecalboxFiles();

        return $pak;
    }

    /** @return list<CharacterModel> */
    private function models(): array
    {
        $sprites = CharacterCatalog::all();
        $game = $this->game();
        $models = [];
        foreach (self::HEROES as $type => $name) {
            $models[] = HeroModels::hero($name, $this->sheet($sprites, $type), $game['weapons']['staff']);
            foreach (PropModels::PICKABLE as $weapon) {
                $models[] = HeroModels::hero($name, $this->sheet($sprites, "$type-$weapon"), $game['weapons'][$weapon], $weapon);
            }
        }
        foreach ($game['enemies'] as $type => $config) {
            $models[] = EnemyModels::enemy($type, $this->sheet($sprites, $type), $config);
        }
        foreach ($this->levelData() as $level) {
            $models[] = BossModels::boss($this->sheet($sprites, (string) $level['boss']['sprite']), $level['boss']);
        }

        return [...$models, ...PropModels::all()];
    }

    /** @return list<array<string, mixed>> les niveaux du jeu web (LevelFactory::build), dans l'ordre de l'album */
    private function levelData(): array
    {
        return array_map($this->app->levels()->build(...), $this->app->album()->tracks);
    }

    /** @return list<LevelExport> */
    private function levels(string $work): array
    {
        $scenes = $this->app->scenes();
        $svgDir = "$work/scenes";
        if (!is_dir($svgDir)) {
            mkdir($svgDir, 0777, true);
            foreach ($this->levelData() as $index => $level) {
                foreach (SceneLayers::split($scenes->level($index)) as $factor => $svg) {
                    file_put_contents(sprintf('%s/%02d-%s.svg', $svgDir, $level['number'], $factor), $svg);
                }
            }
            $this->tools->rasterize($svgDir);
        }

        $exports = [];
        foreach ($this->levelData() as $level) {
            $layers = [];
            foreach (glob(sprintf('%s/%02d-*.png', $svgDir, $level['number'])) ?: [] as $png) {
                $layers[substr(basename($png, '.png'), 3)] = Quantizer::fromPngFile($png);
            }
            uksort($layers, fn($a, $b) => (float) $a <=> (float) $b);
            $exports[] = new LevelExport($level, $layers, $this->musicPath($level['number']), $this->game()['enemies']);
        }

        return $exports;
    }

    /**
     * Une séquence animée du jeu web (config/story.php), filmée plan par plan.
     * Pas « intro.txt » : ce nom-là, le moteur le joue au démarrage, avant même l'écran titre.
     *
     * @return array<string, string>
     */
    private function story(string $work, string $sequence, string $name, string $trackKey): array
    {
        $captures = "$work/story-$sequence";
        if (!is_file("$captures/manifest.json")) {
            $this->tools->captureStory($captures, $sequence);
        }
        $story = $this->app->config()->get('story');
        $scene = new StoryScene($captures, $this->font(), $this->tools, $sequence === 'ending' ? $story['credits'] : []);

        return $scene->files($name, $this->musicPath((int) $story[$trackKey]), $work);
    }

    /** @return array<string, string> les scripts fixes (openbor/scripts/) */
    private function scripts(): array
    {
        $files = [];
        foreach (glob($this->app->root . '/openbor/scripts/*.c') ?: [] as $script) {
            $files['data/scripts/' . basename($script)] = Latin1::encode((string) file_get_contents($script));
        }

        return $files;
    }

    /** @return array<string, string> */
    private function sounds(string $work): array
    {
        $directory = "$work/sounds";
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
            $this->tools->recordSounds($directory, SoundBank::recordings());
        }

        return SoundBank::files($directory);
    }

    private function musicPath(int $track): string
    {
        return sprintf('data/music/%02d.ogg', $track);
    }

    /** @return array<string, string> les morceaux de l'album ; l'écran titre et les menus jouent l'intro */
    private function music(string $work): array
    {
        $album = $this->app->root . '/' . $this->game()['album']['directory'];
        $files = [];
        foreach ($this->app->album()->tracks as $track) {
            $wav = glob(sprintf('%s/*- %02d *.wav', $album, $track->number))[0]
                ?? throw new RuntimeException("Pas de WAV pour le morceau $track->number");
            $ogg = sprintf('%s/%02d.ogg', $work, $track->number);
            if (!is_file($ogg)) {
                $this->tools->encodeMusic($wav, $ogg);
            }
            $files[$this->musicPath($track->number)] = (string) file_get_contents($ogg);
        }
        $title = $files[$this->musicPath((int) $this->app->config()->get('story')['introTrack'])];
        $files['data/music/remix.ogg'] = $title;
        $files['data/music/menu.ogg'] = $title;

        return $files;
    }

    /** @param list<CharacterModel> $models */
    private function modelsText(array $models): string
    {
        // sans ça, un ennemi qui apparaît près de l'écran tombe du ciel (tradition de Beats of Rage)
        $lines = ["nodropspawn\t1"];
        $loaded = [...array_values(self::HEROES), ...array_map(fn(CharacterModel $m) => $m->name, PropModels::all())];
        foreach ($models as $model) {
            // chargés d'office : les héros, et tout ce qui apparaît en cours de jeu sans être cité par
            // un niveau (projectiles, bonus, Wall of Death) ; le reste est chargé par les niveaux
            $command = in_array($model->name, $loaded, true) ? 'load' : 'know';
            $lines[] = "$command\t$model->name\t{$model->path()}";
        }

        return implode("\n", $lines) . "\n";
    }

    /** @param list<LevelExport> $levels */
    private function levelsText(array $levels): string
    {
        $lines = [
            'set	Vigilante',
            'lives	3',
            'credits	' . self::CREDITS,
            'maxplayers	2',
            sprintf('z	%d %d', LevelExport::Z_MIN, LevelExport::Z_MAX),
            // pas d'écran de sélection : Pete en 1P, VigiBapt en 2P, comme dans le jeu web
            'skipselect	' . implode(' ', self::HEROES),
            'scene	data/scenes/prologue.txt',
        ];
        // HUD comme dans le jeu web : nom et score, barre de vie et vies en haut (vinyles : update.c),
        // l'ennemi qu'on frappe en bas ; pas de jauge de « MP » (ce sont les vinyles)
        foreach ([1, 2] as $p) {
            array_push(
                $lines,
                "p{$p}score	8 3 0 0 0 0 0",
                "p{$p}life	8 13",
                "p{$p}lifex	112 12 0",
                "p{$p}lifen	120 12 2",
                "p{$p}mp	0 400",
                "e{$p}life	8 170",
                "e{$p}name	8 160 1",
            );
        }
        foreach ($levels as $level) {
            $lines[] = "file\t{$level->path()}";
        }
        $lines[] = 'scene	data/scenes/ending.txt';

        return implode("\n", $lines) . "\n";
    }

    /** À copier tel quel dans share/roms/openbor/ : le jeu, sa fiche et sa pochette. */
    private function writeRecalboxFiles(): void
    {
        $gamelist = new RecalboxGamelist(
            self::PAK_NAME,
            $this->app->root . '/' . $this->game()['album']['directory'] . '/cover.jpg',
            $this->app->album()->year,
        );
        $root = "$this->outDir/recalbox";
        $this->reset($root);
        foreach ($gamelist->files() as $path => $content) {
            if (!is_dir(dirname("$root/$path"))) {
                mkdir(dirname("$root/$path"), 0777, true);
            }
            file_put_contents("$root/$path", $content);
        }
        copy("$this->outDir/" . self::PAK_NAME . '.pak', "$root/" . self::PAK_NAME . '.pak');
    }

    /** @return array<string, mixed> */
    private function game(): array
    {
        return $this->app->config()->get('game');
    }

    private function font(): string
    {
        return $this->app->root . '/medias/fonts/PressStart2P-Regular.ttf';
    }

    /** @param array<string, mixed> $sprites */
    private function sheet(array $sprites, string $type): SpriteSheet
    {
        $sheet = $sprites[$type] ?? null;

        return $sheet instanceof SpriteSheet ? $sheet : throw new RuntimeException("Sprite inconnu : $type");
    }

    /**
     * Le module décompressé à côté du .pak, pour le relire ou le tester sans archive.
     *
     * @param array<string, string> $files
     */
    private function writeTree(string $root, array $files): void
    {
        $this->reset($root);
        foreach ($files as $path => $content) {
            $file = dirname($root) . '/' . $path;
            if (!is_dir(dirname($file))) {
                mkdir(dirname($file), 0777, true);
            }
            file_put_contents($file, $content);
        }
    }

    private function reset(string $directory): void
    {
        if (is_dir($directory)) {
            exec('rm -rf ' . escapeshellarg($directory));
        }
        mkdir($directory, 0777, true);
    }
}
