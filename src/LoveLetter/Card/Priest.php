<?php

namespace NH\LoveLetter\Card;

use NH\LoveLetter\LoveLetter;

/**
 * Schaue dir die Handkarte eines Mitspielers an.
 */
class Priest extends AbstractCard implements EffectInterface
{
    public static $id = 3;
    public static $name = 'Priester';
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

        if ($game->getWaitFor() === LOVELETTER::CHOOSE_PLAYER) {
            $playerId = $params['id'];
            $selectedPlayer = $game->getPlayerById($playerId);
            $enemyName = $selectedPlayer->getName();
            $activePlayerGameState = $game->getActivePlayerGameState();
            $selectedPlayerGameState = $selectedPlayer->getGameState();
            foreach ($selectedPlayerGameState->getCards() as $card) {
                $activePlayerGameState->setPriestEffectVisibleCard($card['name']);
            }
            $game->setWaitFor(LOVELETTER::FINISH_LOOKING_AT_CARD);
            $game->setStatus('Merke dir diese Karte von ' . $enemyName . ' und drücke auf ok!');
            return;
        }

        $game->setWaitFor(LOVELETTER::CONFIRM_DISCARD_CARD);
        $game->setStatus($game->getActivePlayerName() . ' muss seine Karte auf den Ablagestapel legen ...');
    }
}
