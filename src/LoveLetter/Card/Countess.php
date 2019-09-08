<?php

namespace NH\LoveLetter\Card;

use NH\LoveLetter\LoveLetter;

/**
 * Wenn du zusätzlich König oder Prinz auf der Hand hast, musst du die Gräfin ausspielen.
 */
class Countess extends AbstractCard implements EffectInterface
{
    public static $id = 7;
    public static $name = 'countess';
    public static $value = 7;
    public static $text = 'Wenn du zusätzlich König oder Prinz auf der Hand hast, musst du die Gräfin ausspielen.';

    public static function activate(LoveLetter $game, $params = [])
    {
        $game->setWaitFor(LoveLetter::CONFIRM_DISCARD_CARD);
        $game->setStatus($game->getActivePlayerName() . ' muss seine Karte auf den Ablagestapel legen ...');
    }
}
