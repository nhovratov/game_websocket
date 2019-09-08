<?php

namespace NH\LoveLetter\Card;

use NH\LoveLetter\LoveLetter;

/**
 * Vergleiche deine Handkarte mit der eines Mitspielers. Der Spieler mit dem niedrigeren Wert scheidet aus ...
 */
class Baron extends AbstractCard implements EffectInterface
{
    public static $id = 3;
    public static $name = 'baron';
    public static $value = 3;
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
        $activePlayerState = $game->getActivePlayerState();
        $enemyPlayerState = $enemy->getPlayerState();
        $activePlayerCard = $activePlayerState->getHandCard();
        $enemyPlayerCard = $enemyPlayerState->getHandCard();
        $activePlayerState->setEffectVisibleCard($enemyPlayerCard);
        $enemyPlayerState->setEffectVisibleCard($activePlayerCard);
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
