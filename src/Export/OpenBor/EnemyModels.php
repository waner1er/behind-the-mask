<?php

declare(strict_types=1);

namespace Vigilante\Export\OpenBor;

use Vigilante\Sprite\SpriteSheet;

/**
 * Les ennemis des vagues (config/game.php) en modèles OpenBOR, avec les coups de js/actors/EnemyAI.js :
 * coup normal (attack1), ruée (attack2, moves.lunge), lancer (attack3, moves.throw ou projectile).
 * L'IA d'OpenBOR choisit l'attaque selon la distance (range), comme #chooseMove.
 */
final class EnemyModels
{
    /** Coup d'un ennemi (ATTACK.enemy) : il arme longtemps, ce qui laisse le temps de l'esquiver. */
    private const STRIKE = ['windup' => 26, 'hit' => 4, 'strike' => 8, 'end' => 8];

    /** Pixels par image du jeu web → vitesse OpenBOR (mesurée dans le moteur). */
    public const SPEED_SCALE = 7;

    public static function modelName(string $type): string
    {
        return ucfirst($type);
    }

    /**
     * @param array{hp: int, speed: float, damage: int, reach?: int, score?: int, moves?: array<string, int>,
     *     projectile?: string, throws?: string, weapon?: string, every?: int} $config
     */
    public static function enemy(string $type, SpriteSheet $sheet, array $config): CharacterModel
    {
        $reach = $config['reach'] ?? 26;
        $moves = $config['moves'] ?? [];
        $thrown = $config['throws'] ?? $config['projectile'] ?? null;
        $t = self::STRIKE;
        $animations = [
            new Animation('idle', [Frame::at('idle.0', 30), Frame::at('idle.1', 30)], loop: true),
            new Animation('walk', array_map(fn($i) => Frame::at("walk.$i", 10), [0, 1, 2, 3]), loop: true),
        ];

        // les purs lanceurs (hooligans) gardent leurs distances : pas de coup au corps à corps
        $strikes = !isset($config['projectile']) || isset($moves['strike']);
        $attacks = []; // [commandes de l'animation, images]
        if ($strikes) {
            $attacks[] = [["range	0 $reach"], [
                Frame::at('attack.0', $t['windup']),
                Frame::at('attack.1', $t['hit'], new Hit(2, $reach, $config['damage']), commands: ['sound	' . SoundBank::path('whoosh')]),
                Frame::at('attack.1', $t['strike']),
                Frame::at('attack.0', $t['end']),
            ]];
        }
        // EnemyAI.#chooseMove : chaque coup spécial a un poids face au simple coup (strike) ;
        // on en tire un délai moyen entre deux ruées ou deux lancers
        $weights = array_sum($moves) ?: 1;
        $cooldown = fn(int $weight, int $every) => '@cmd	special ' . (int) round($every * $weights / max(1, $weight));
        if (isset($moves['lunge'])) {
            // il prend son élan (14 images), puis fonce à 3,2 px par image pendant 18 images
            $dash = [];
            for ($i = 0; $i < 6; $i++) {
                $dash[] = Frame::at('attack.1', 3, new Hit(-4, 12, $config['damage']), 10, $i === 0 ? ['sound	' . SoundBank::path('lunge')] : []);
            }
            $attacks[] = [['range	34 80'], [
                Frame::at('attack.0', 14, commands: [$cooldown($moves['lunge'], 80)]),
                ...$dash,
                Frame::at('idle.0', 10),
            ]];
        }
        if ($thrown !== null) {
            $attacks[] = [['range	50 170', 'custbomb	' . PropModels::modelName($thrown)], [
                Frame::at('attack.0', 18, commands: ['tossframe	1 26', $cooldown($moves['throw'] ?? 3, (int) ($config['every'] ?? 90))]),
                Frame::at('attack.1', 14, commands: ['sound	' . SoundBank::path('throw')]),
                Frame::at('attack.0', 10),
            ]];
        }
        foreach ($attacks as $i => [$extra, $frames]) {
            $animations[] = new Animation('attack' . ($i + 1), $frames, extra: $extra);
        }

        return new CharacterModel(self::modelName($type), ModelSheet::from($sheet), [
            'type	enemy',
            'health	' . $config['hp'] * HeroModels::HP_SCALE,
            'speed	' . max(1, (int) round($config['speed'] * self::SPEED_SCALE)),
            'score	' . ($config['score'] ?? 100) . ' 1',
            'shadow	3',
            'diesound	' . SoundBank::death($type),
            'ondeathscript	data/scripts/enemy_death.c',
            'animationscript	data/scripts/special.c',
            // un ennemi ne frappe pas à chaque occasion (EnemyAI : délai de 55 à 120 images)
            'attackthrottle	0.4 1',
        ], [
            ...$animations,
            new Animation('pain', [Frame::at('idle.1', 18, commands: ['sound	' . SoundBank::path('hit')])]),
            new Animation('fall', [Frame::at('fall.0', 60)]),
            new Animation('rise', [Frame::at('idle.0', 24)]),
        ]);
    }
}
