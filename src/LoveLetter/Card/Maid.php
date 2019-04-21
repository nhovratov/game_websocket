<?php

namespace MyApp\LoveLetter\Card;

use MyApp\LoveLetter\LoveLetter;

/**
 * Du bist bis zu deinem nächsten Zug geschützt.
 */
class Maid extends AbstractCard implements EffectInterface
{
    const NAME = 'Zofe';
    const VALUE = 4;
    const TEXT = 'Du bist bis zu deinem nächsten Zug geschützt.';

    public static function activate(LoveLetter $game, $params = [])
    {
        $game->addProtectedPlayer($game->getActivePlayerId());
        $game->setWaitFor(LoveLetter::PLACE_MAID_CARD);
        $game->setStatus($game->getActivePlayerName() . ' ist für eine Runde geschützt und muss seine Karte vor sich offen hinlegen ...');
    }
}
