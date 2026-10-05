<?php

declare(strict_types=1);

namespace Vigilante\Export\OpenBor;

use Vigilante\Sprite\SpriteSheet;

/**
 * Les boss (config/levels.php) : sprite agrandi au pixel près (scale), coup ATTACK.boss et
 * attaque spéciale de js/actors/BossAI.js toutes les « every » images (script boss.c) :
 * summon (deux sbires), throw (projectile), charge (traverse l'écran), teleport (dans ton dos).
 */
final class BossModels
{
    /** Coup d'un boss (ATTACK.boss). */
    private const STRIKE = ['windup' => 18, 'hit' => 4, 'strike' => 8, 'end' => 8];

    public static function modelName(string $sprite): string
    {
        return 'Boss_' . $sprite;
    }

    public static function spawnScriptPath(string $sprite): string
    {
        return 'data/scripts/boss/' . self::modelName($sprite) . '.c';
    }

    /**
     * Arrivée du boss (Camera.#spawnBoss) : « WARNING! », son nom, sa réplique ; sa première
     * attaque spéciale vient au bout de 3 s.
     *
     * @param array<string, mixed> $boss
     */
    public static function spawnScript(array $boss): string
    {
        $scale = (float) $boss['scale'];

        return Latin1::encode(implode("\n", [
            '#include "data/scripts/lib.c"',
            '',
            'void main()',
            '{',
            '    void self = getlocalvar("self");',
            '    setentityvar(self, 2, now() + webFrames(180));',
            sprintf('    message("WARNING!", "", %s, "", "", 2.7);', Latin1::literal((string) $boss['name'])),
            '    sound("warning");',
            sprintf(
                '    shout(%s, getentityproperty(self, "x") - 40, getentityproperty(self, "z") - %d, 0);',
                Latin1::literal((string) $boss['line']),
                (int) round(50 * $scale),
            ),
            '}',
        ]) . "\n");
    }

    /** @param array<string, mixed> $boss un boss de config/levels.php */
    public static function boss(SpriteSheet $sheet, array $boss): CharacterModel
    {
        $scale = (float) $boss['scale'];
        $damage = (int) $boss['damage'];
        $reach = (int) ($boss['reach'] ?? 26); // à l'échelle 1 : le modèle l'agrandit
        $t = self::STRIKE;
        $cooldown = '@cmd	special ' . (int) $boss['every'];

        $special = match ((string) $boss['special']) {
            'summon' => [
                new Animation('attack2', [
                    Frame::at('attack.0', 20, commands: [$cooldown]),
                    Frame::at('attack.1', 16, commands: ['@cmd	summonMinions', 'sound	' . SoundBank::path('warning')]),
                ], extra: ['range	0 400']),
            ],
            'throw' => [
                new Animation('attack2', [
                    Frame::at('attack.0', 18, commands: [$cooldown, 'tossframe	1 ' . (int) (26 * $scale)]),
                    Frame::at('attack.1', 14, commands: ['sound	' . SoundBank::path('throw')]),
                ], extra: ['range	40 260', 'custbomb	' . PropModels::modelName((string) $boss['projectile'])]),
            ],
            'charge' => [
                new Animation('attack2', [
                    Frame::at('attack.0', 34, commands: [$cooldown]),
                    // elle fonce à 3,4 px par image et renverse ce qu'elle traverse (dégâts × 1,5)
                    ...array_map(fn($i) => Frame::at(
                        'attack.1',
                        4,
                        new Hit(-14, 14, (int) round($damage * 1.5), true, 30, 24),
                        14,
                        $i === 0 ? ['sound	' . SoundBank::path('skate')] : [],
                    ), range(0, 14)),
                    Frame::at('idle.0', 12),
                ], extra: ['range	0 320']),
            ],
            'teleport' => [
                new Animation('attack2', [
                    Frame::at('idle.0', 26, commands: [$cooldown, 'sound	' . SoundBank::path('teleport')]),
                    Frame::at('attack.0', 6, commands: ['@cmd	teleportBehind']),
                    Frame::at('attack.1', 6, new Hit(2, $reach, $damage), commands: ['sound	' . SoundBank::path('whoosh')]),
                    Frame::at('attack.0', 12),
                ], extra: ['range	0 400']),
            ],
            default => throw new \InvalidArgumentException("Attaque spéciale inconnue : {$boss['special']}"),
        };

        return new CharacterModel(self::modelName((string) $boss['sprite']), ModelSheet::from($sheet, $scale), [
            'type	enemy',
            'health	' . (int) $boss['hp'] * HeroModels::HP_SCALE,
            'speed	' . max(1, (int) round((float) $boss['speed'] * EnemyModels::SPEED_SCALE)),
            'score	5000 1',
            'shadow	' . min(6, (int) round(3 * $scale)),
            'nodrop	1',
            'diesound	' . SoundBank::bossDeath($scale),
            'animationscript	data/scripts/special.c',
            'onspawnscript	' . self::spawnScriptPath((string) $boss['sprite']),
        ], [
            new Animation('idle', [Frame::at('idle.0', 30), Frame::at('idle.1', 30)], loop: true),
            new Animation('walk', array_map(fn($i) => Frame::at("walk.$i", 10), [0, 1, 2, 3]), loop: true),
            new Animation('attack1', [
                Frame::at('attack.0', $t['windup']),
                Frame::at('attack.1', $t['hit'], new Hit(2, $reach, $damage), commands: ['sound	' . SoundBank::path('whoosh')]),
                Frame::at('attack.1', $t['strike']),
                Frame::at('attack.0', $t['end']),
            ], extra: ['range	0 ' . (int) round($reach * $scale)]),
            ...$special,
            new Animation('pain', [Frame::at('idle.1', 10, commands: ['sound	' . SoundBank::path('heavyHit')])]),
            new Animation('fall', [Frame::at('fall.0', 80)]),
            new Animation('rise', [Frame::at('idle.0', 24)]),
        ]);
    }
}
