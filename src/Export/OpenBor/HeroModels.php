<?php

declare(strict_types=1);

namespace Vigilante\Export\OpenBor;

use Vigilante\Sprite\SpriteSheet;

/**
 * Pete (1P) et VigiBapt (2P) en modèles OpenBOR, avec les commandes du jeu web réparties sur
 * tous les boutons de la borne (Recalbox : A = ATTACK, B = ATTACK2, X = ATTACK3, Y = ATTACK4,
 * L1 = JUMP, R1 = SPECIAL) pour qu'aucun bouton du panneau ne soit muet :
 *
 *   A frappe · B coup de pied sauté (la touche B du web) · X ou R1 skate · Y vinyle
 *   A + Y Wall of Death (ESPACE + V du web) · L1 saut, puis A en l'air : coup de pied
 *
 * Timings de js/config.js (ATTACK.hero, SKATE, JUMP, VINYL), points de vie × 10.
 */
final class HeroModels
{
    public const HP_SCALE = 10;

    /** Un vinyle = 10 points de « MP » : le stock de vinyles (5 au départ, 20 au plus, js/config.js VINYL). */
    public const MP_PER_VINYL = 10;

    /**
     * @param string $base nom du héros (« Pete », « Bapt »)
     * @param string|null $armedWith arme ramassée (« bat »...) : le modèle « Pete_bat »
     * @param array{damage: int, reach: int, knockback: float} $weapon arme tenue (config/game.php, staff = arme fétiche)
     */
    public static function hero(string $base, SpriteSheet $sheet, array $weapon, ?string $armedWith = null): CharacterModel
    {
        $armed = $armedWith !== null;
        $name = $armed ? self::armedName($base, $armedWith) : $base;
        $damage = $weapon['damage'] * self::HP_SCALE;
        $sheet = ModelSheet::from($sheet);
        $sound = fn(string $name) => 'sound	' . SoundBank::path($name);

        return new CharacterModel($name, $sheet, [
            'type	player',
            'health	100',
            // 20 vinyles au plus, sans recharge avec le temps (update.c en donne 5 à l'entrée en jeu)
            'mpset	' . 20 * self::MP_PER_VINYL . ' 2 0 0 0 0',
            'speed	9',
            'shadow	3',
            // un saut monte à ~26 px comme dans le web (JUMP.impulse 3,4 avec une gravité de 0,22 par image)
            'jumpheight	2.3',
            'diesound	' . SoundBank::path('heroDeath'),
            'animationscript	data/scripts/hero.c',
            // les touches en plus de ATTACK (A) et JUMP (L1) ; SPECIAL (R1) = « special »
            'com	a2 freespecial',
            'com	a3 freespecial3',
            'com	a + a4 freespecial4',
            'com	a4 freespecial2',
            'com	a4 + a freespecial4',
            // armes ramassables (weapnum 1 à 4 des objets Item_*), perdues quand on est mis à terre
            'weapons	' . implode(' ', array_map(fn($w) => self::armedName($base, $w), PropModels::PICKABLE)) . " $base",
            'weaploss	1',
        ], [
            new Animation('idle', [Frame::at('idle.0', 30), Frame::at('idle.1', 30)], loop: true),
            new Animation('walk', array_map(fn($i) => Frame::at("walk.$i", 8), [0, 1, 2, 3]), loop: true),
            new Animation('attack1', [
                Frame::at('attack.0', 5),
                Frame::at('attack.1', 5, new Hit(4, $weapon['reach'], $damage), commands: [$sound($armed ? 'whoosh' : 'slash')]),
                Frame::at('attack.1', 4),
                Frame::at('attack.0', 6),
            ]),
            new Animation('jump', [Frame::at('jump.0', 60)]),
            new Animation('jumpattack', [Frame::at('kick.0', 40, new Hit(-4, 30, 2 * self::HP_SCALE, true, 26, 14), commands: [$sound('kick')])]),
            // B : le coup de pied sauté du web, d'un seul bouton (impulsion JUMP.impulse, en avançant)
            new Animation('freespecial', [
                Frame::at('jump.0', 6, commands: [$sound('kick')]),
                Frame::at('kick.0', 34, new Hit(-4, 30, 2 * self::HP_SCALE, true, 26, 14)),
                Frame::at('jump.0', 4),
            ], extra: ['jumpframe	0 2.3 1.7', 'landframe	2']),
            new Animation('freespecial2', self::vinyl(), extra: ['energycost	' . self::MP_PER_VINYL . ' 1 0', 'custbomb	Vinyl']),
            new Animation('freespecial3', self::skate($damage, $sound('skate')), extra: ['energycost	0']),
            new Animation('special', self::skate($damage, $sound('skate')), extra: ['energycost	0']),
            new Animation('freespecial4', [
                Frame::at('attack.1', 30, commands: ['@cmd	wallOfDeath']),
                Frame::at('idle.0', 10),
            ], extra: ['energycost	0']),
            new Animation('pain', [Frame::at('idle.1', 16, commands: [$sound('hurt')])]),
            new Animation('fall', [Frame::at('fall.0', 60, commands: [$sound('heavyHit')])]),
            new Animation('rise', [Frame::at('idle.0', 20)]),
            new Animation('get', [Frame::at('idle.0', 10, commands: [$sound('pickup'), '@cmd	onGet'])]),
            new Animation('select', [Frame::at('idle.0', 30), Frame::at('idle.1', 30)], loop: true),
        ]);
    }

    public static function armedName(string $base, string $weapon): string
    {
        return "{$base}_$weapon";
    }

    /**
     * VINYL : il tournoie, retombe à ~80 px et explose en notes (modèle Vinyl, PropModels).
     *
     * @return list<Frame>
     */
    private static function vinyl(): array
    {
        return [
            Frame::at('attack.0', 6, commands: ['tossframe	1 18']),
            Frame::at('attack.1', 10, commands: ['sound	' . SoundBank::path('throw')]),
        ];
    }

    /**
     * SKATE : ~110 px en un peu plus d'une demi-seconde, invulnérable, renverse tout sur son passage.
     *
     * @return list<Frame>
     */
    private static function skate(int $damage, string $sound): array
    {
        $frames = [];
        for ($i = 0; $i < 10; $i++) {
            $frames[] = Frame::at('skate.' . ($i % 2), 3, new Hit(-6, 36, $damage, true), 11, $i === 0 ? [$sound] : []);
        }

        return $frames;
    }
}
