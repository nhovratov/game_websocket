<?php

namespace NH\LoveLetter\Card;

use NH\LoveLetter\LoveLetter;

/**
 * Schaue dir die Handkarte eines Mitspielers an.
 */
class Priest extends AbstractCard implements EffectInterface
{
    public static $id = 3;
    public static $name = 'priest';
    public static $value = 3;
    public static $text = 'Schaue dir die Handkarte eines Mitspielers an.';

    public static function activate(LoveLetter $game, $params = [])
    {
        static $enemyName = null;

        if ($game->getWaitFor() === LOVELETTER::CHOOSE_CARD) {
            $game->setStatus($game->getActivePlayerName() . ' sucht Mitspieler für Karteneffekt "' . $game->getActiveCardName() . '" aus ...');
            $game->setWaitFor(LOVELETTER::CHOOSE_PLAYER);
            return;
        }

        $playerId = $params['id'];
        $selectedPlayer = $game->getPlayerById($playerId);
        $activePlayerState = $game->getActivePlayerState();
        $selectedPlayerState = $selectedPlayer->getPlayerState();
        foreach ($selectedPlayerState->getCards() as $card) {
            $activePlayerState->setEffectVisibleCard($card);
        }
        $game->setWaitFor(LOVELETTER::CONFIRM_DISCARD_CARD);
        $game->setStatus($game->getActivePlayerName() . ' muss seine Karte auf den Ablagestapel legen ...');
        return;
    }
}
