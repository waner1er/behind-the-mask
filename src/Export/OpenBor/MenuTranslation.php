<?php

declare(strict_types=1);

namespace Vigilante\Export\OpenBor;

/** Les menus du moteur en français (data/translation.txt, format msgid / msgstr). */
final class MenuTranslation
{
    private const TEXTS = [
        'Start Game' => 'JOUER',
        'New Game' => 'NOUVELLE PARTIE',
        'Load Game' => 'CONTINUER LA PARTIE',
        'Options' => 'OPTIONS',
        'How To Play' => 'COMMENT JOUER',
        'Hall Of Fame' => 'MEILLEURS SCORES',
        'Quit' => 'QUITTER',
        'Back' => 'RETOUR',
        'Continue' => 'CONTINUER',
        'End Game' => 'ABANDONNER',
        'GAME OVER' => 'GAME OVER',
        'Pause' => 'PAUSE',
        'Loading...' => 'CHARGEMENT...',
        'Press Start' => 'APPUIE SUR START',
        'Complete' => 'MISSION COMPLETE!',
        'Clear Bonus' => 'BONUS',
        'Life bonus' => 'BONUS VIE',
        'Credit %i' => 'JETONS %i',
        'Credits:' => 'JETONS :',
        'Yes' => 'OUI',
        'No' => 'NON',
        'Video Options' => 'IMAGE',
        'Sound Options' => 'SON',
        'Control Options' => 'COMMANDES',
        'Music Volume:' => 'MUSIQUE :',
        'SFX Volume:' => 'BRUITAGES :',
    ];

    public static function file(): string
    {
        $lines = [];
        foreach (self::TEXTS as $english => $french) {
            $lines[] = 'msgid "' . $english . '"';
            $lines[] = 'msgstr "' . $french . '"';
            $lines[] = '';
        }

        return Latin1::encode(implode("\n", $lines));
    }
}
