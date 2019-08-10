<?php

namespace NH\LoveLetter\Card;

use NH\LoveLetter\LoveLetter;

/**
 * Vergleiche deine Handkarte mit der eines Mitspielers. Der Spieler mit dem niedrigeren Wert scheidet aus ...
 */
class Baron extends AbstractCard implements EffectInterface
{
    public static $id = 2;
    public static $name = 'Baron';
    public static $value = 2;
    public static $text = 'Vergleiche deine Handkarte mit der eines Mitspielers. Der Spieler mit dem niedrigeren Wert scheidet aus ...';

    public static function activate(LoveLetter $game, $params = [])
    {
        if ($game->getWaitFor() === LOVELETTER::CHOOSE_CARD) {
            $game->setStatus($game->getActivePlayerName() . ' sucht Mitspieler für Karteneffekt "' . $game->getActiveCardName() . '" aus ...');
            $game->setWaitFor(LOVELETTER::CHOOSE_PLAYER);
            return;
        }

        $id = (int)$params['id'];
        $enemy = $game->getPlayerById($id);
        $activePlayerState = $game->getActivePlayerGameState();
        $enemyPlayerState = $enemy->getGameState();
        $activePlayerCard = $game->getHandCard($activePlayerState->getCards());
        $enemyPlayerCard = $game->getHandCard($enemyPlayerState->getCards());
        switch ($activePlayerCard['value'] <=> $enemyPlayerCard['value']) {
            case 0:
                $game->setStatus('Karten haben den gleichen Wert...keiner fliegt raus. ');
                break;
            case 1:
                $game->addOutOfGamePlayer($enemy->getId());
                $game->setStatus('Die Karte von ' . $game->getActivePlayerName() . ' war höher! ');
                break;
            case -1:
                $game->addOutOfGamePlayer($game->getActivePlayerId());
                $game->setStatus('Die Karte von ' . $enemy->getName() . ' war höher! ');
                break;
        }

        $game->setWaitFor(LOVELETTER::CONFIRM_DISCARD_CARD);
        $game->setStatus($game->getStatus() . $game->getActivePlayerName() . ' muss seine Karte auf den Ablagestapel legen ...');
    }
}
