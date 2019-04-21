<?php

namespace MyApp\LoveLetter\Card;

use MyApp\LoveLetter\LoveLetter;

/**
 * Tausche deine Handkarte mit der eines Mitspielers.
 */
class King extends AbstractCard implements EffectInterface
{
    public static $name = 'König';
    public static $value = 6;
    public static $text = 'Tausche deine Handkarte mit der eines Mitspielers.';

    public static function activate(LoveLetter $game, $params = [])
    {
        if ($game->getWaitFor() === LoveLetter::CHOOSE_CARD) {
            $game->setStatus($game->getActivePlayerName() . ' sucht Mitspieler für Karteneffekt "' . $game->getActiveCardName() . '" aus ...');
            $game->setWaitFor(LoveLetter::CHOOSE_PLAYER);
            return;
        }

        $chosenPlayer = $game->getPlayerById($params['id']);
        $chosenPlayerState = $chosenPlayer->getGameState();
        $activePlayerState = $game->getActivePlayerGameState();

        // Swap cards
        $activePlayerCards = $activePlayerState->getCards();
        $chosenPlayerCards = $chosenPlayerState->getCards();
        $activePlayerCard = array_slice($activePlayerCards, 0, 1)[0];
        unset($activePlayerCards[$activePlayerCard['id']]);
        $chosenPlayerCard = array_slice($chosenPlayerCards, 0, 1)[0];
        unset($chosenPlayerCards[$chosenPlayerCard['id']]);
        $chosenPlayerCards[$activePlayerCard['id']] = $activePlayerCard;
        $activePlayerCards[$chosenPlayerCard['id']] = $chosenPlayerCard;
        $chosenPlayerState->setCards($chosenPlayerCards);
        $activePlayerState->setCards($activePlayerCards);

        $game->setWaitFor(LoveLetter::CONFIRM_DISCARD_CARD);
        $game->setStatus($chosenPlayer->getName() . ' und ' . $game->getActivePlayerName() . ' haben Karten getauscht. ');
        $game->setStatus($game->getStatus() . $game->getActivePlayerName() . ' muss seine Karte auf den Ablagestapel legen ...');
    }
}
