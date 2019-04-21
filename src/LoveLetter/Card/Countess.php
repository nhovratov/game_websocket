<?php

namespace MyApp\LoveLetter\Card;

use MyApp\LoveLetter\LoveLetter;

/**
 * Wenn du zusätzlich König oder Prinz auf der Hand hast, musst du die Gräfin ausspielen.
 */
class Countess extends AbstractCard implements EffectInterface
{
    const NAME = 'Gräfin';
    const VALUE = 7;
    const TEXT = 'Wenn du zusätzlich König oder Prinz auf der Hand hast, musst du die Gräfin ausspielen.';

    public static function activate(LoveLetter $game, $params = [])
    {
        $game->setWaitFor(LoveLetter::CONFIRM_DISCARD_CARD);
        $game->setStatus($game->getActivePlayerName() . ' muss seine Karte auf den Ablagestapel legen ...');
    }
}
