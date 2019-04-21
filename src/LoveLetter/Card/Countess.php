<?php

namespace MyApp\LoveLetter\Card;

use MyApp\LoveLetter\LoveLetter;

/**
 * Wenn du zusätzlich König oder Prinz auf der Hand hast, musst du die Gräfin ausspielen.
 */
class Countess extends AbstractCard implements EffectInterface
{
    public static $name = 'Gräfin';
    public static $value = 7;
    public static $text = 'Wenn du zusätzlich König oder Prinz auf der Hand hast, musst du die Gräfin ausspielen.';

    public static function activate(LoveLetter $game, $params = [])
    {
        $game->setWaitFor(LoveLetter::CONFIRM_DISCARD_CARD);
        $game->setStatus($game->getActivePlayerName() . ' muss seine Karte auf den Ablagestapel legen ...');
    }
}
