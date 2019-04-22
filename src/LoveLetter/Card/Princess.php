<?php

namespace MyApp\LoveLetter\Card;

use MyApp\LoveLetter\LoveLetter;

/**
 * Wenn du die Prinzessin ablegst, scheidest du aus ...
 */
class Princess extends AbstractCard implements EffectInterface
{
    public static $id = 8;
    public static $name = 'Prinzessin';
    public static $value = 8;
    public static $text = 'Wenn du die Prinzessin ablegst, scheidest du aus ...';

    public static function activate(LoveLetter $game, $params = [])
    {
        $game->addOutOfGamePlayer($game->getActivePlayerId());
        $game->setWaitFor(LoveLetter::CONFIRM_DISCARD_CARD);
        $game->setStatus($game->getActivePlayerName() . ' muss seine Karte auf den Ablagestapel legen ...');
    }
}
