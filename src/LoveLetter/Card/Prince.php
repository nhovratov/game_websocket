<?php

namespace MyApp\LoveLetter\Card;

use MyApp\LoveLetter\LoveLetter;

/**
 * Wenn du die Prinzessin ablegst, scheidest du aus ...
 */
class Prince extends AbstractCard implements EffectInterface
{
    const NAME = 'Prinz';
    const VALUE = 5;
    const TEXT = 'Wähle einen Spieler, der seine Handkarte ablegt und eine neue Karte zieht.';

    public static function activate(LoveLetter $game, $params = [])
    {
        if ($game->getWaitFor() === LOVELETTER::CHOOSE_CARD) {
            $game->setStatus($game->getActivePlayerName() . ' sucht Spieler für Karteneffekt "' . $game->getActiveCardName() . '" aus ...');
            $game->setWaitFor(LOVELETTER::CHOOSE_ANY_PLAYER);
            return;
        }

        $chosenPlayer = $game->getPlayerById($params['id']);
        $gameState = $chosenPlayer->getGameState();
        $cards = $gameState->getCards();
        $card = $game->discardCard($cards);
        $game->setStatus('Die Karte ' . $card['name'] . ' von ' . $chosenPlayer->getName() . ' wurde abgeworfen ');
        if ($card['name'] === 'Prinzessin') {
            $game->addOutOfGamePlayer($chosenPlayer->getId());
            $game->setStatus($game->getStatus() . 'und ist deshalb ausgeschieden. ');
        } else {
            $gameState->setCards($cards);
            $gameState->addCard($game->drawCard(true));
            $game->setStatus($game->getStatus() . 'und eine neue Karte wurde gezogen. ');
        }
        $game->setWaitFor(LOVELETTER::CONFIRM_DISCARD_CARD);
        $game->setStatus($game->getStatus() . $game->getActivePlayerName() . ' muss seine Karte auf den Ablagestapel legen ...');
    }
}
