<?php

namespace NH\LoveLetter\Card;

use NH\LoveLetter\LoveLetter;

/**
 * Du bist bis zu deinem nächsten Zug geschützt.
 */
class Maid extends AbstractCard implements EffectInterface
{
    public static $id = 4;
    public static $name = 'maid';
    public static $value = 4;
    public static $text = 'Du bist bis zu deinem nächsten Zug geschützt.';

    public static function activate(LoveLetter $game, $params = [])
    {
        $game->addProtectedPlayer();
        $game->setWaitFor(LoveLetter::PLACE_MAID_CARD);
        $game->setStatus($game->getActivePlayerName() . ' ist für eine Runde geschützt und muss seine Karte vor sich offen hinlegen ...');
    }
}
