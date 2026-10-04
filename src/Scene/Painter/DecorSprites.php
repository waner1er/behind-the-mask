<?php

declare(strict_types=1);

namespace Vigilante\Scene\Painter;

/** Petits sprites du décor (1 caractère = 1 pixel) et leurs palettes. */
final class DecorSprites
{
    public const FIRE_COLORS = ['y' => '#fff6b0', 'Y' => '#ffd23f', 'O' => '#ff8a1e', 'r' => '#e8203a', 'R' => '#8a1010'];

    /** Deux images qui alternent. */
    public const FIRE = [
        ['...y...', '..yYy..', '.yYOYy.', '.YOOOY.', 'YOOrOOY', 'OOrrrOO', '.OrRrO.', '..rRr..'],
        ['..y....', '..Yy.y.', '.yYOYY.', '.YOOOOY', 'YOOrrOY', 'OOrrrOO', '.OrRrO.', '..rRr..'],
    ];

    public const WRECK_COLORS = [
        'k' => '#141210', 'd' => '#3a3632', 'e' => '#5a5650', 'r' => '#7a3a1e',
        'g' => '#2a2a30', 'o' => '#6a2a1e', 'O' => '#8a3a24',
    ];

    public const CAR = [
        '.........kkkkkkkkkkkk.........',
        '........kgggkkkkgggggk........',
        '.......kgggkkkkkkggggek.......',
        '..kkkkkkkkkkkkkkkkkkkkkkkkk...',
        '.kdddddddddddrddddddddddddek..',
        'kdddrrdddddddddddddddrddddddk.',
        'kddddddddddddddddddddddddddek.',
        'kkkkkkkkkkkkkkkkkkkkkkkkkkkkk.',
        '..kkkk...............kkkk.....',
        '..kdek...............kdek.....',
        '...kk.................kk......',
    ];

    public const BARREL = ['.kkkkkk.', 'kOOOOOOk', 'koOOOOok', 'kkkkkkkk', 'koOOOOok', 'koOOOOok', 'kkkkkkkk', 'koOOOOok', '.kkkkkk.'];

    public const RUBBLE = ['....k.....', '..kdek.k..', '.kdddekdk.', 'kddkdddddk'];

    public const TREE_COLORS = ['L' => '#3fbf4a', 'l' => '#2a8a34', 'p' => '#ff8ac8', 'T' => '#7a4a2a', 't' => '#4a2a14'];

    public const TREE = [
        '....LLLLL.....',
        '..LLLlLLpLL...',
        '.LLlLLLLLlLL..',
        'LLpLLLlLLLLLL.',
        'LlLLLLLLLpLlLL',
        'LLLLlLLLLLLLLL',
        '.LLLLpLLlLLLL.',
        '..LLLlLLLLLL..',
        '...LLLLLLLL...',
        '.....TTt......',
        '.....TTt......',
        '.....TTt......',
        '.....TTt......',
        '....TTTTt.....',
    ];

    public const FLOWER_PETALS = ['#ff3ea5', '#ffd23f', '#3ef0ff', '#ff8a1e', '#ffffff', '#c58aff'];
}
