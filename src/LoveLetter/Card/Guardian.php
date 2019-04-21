<?php

namespace MyApp\LoveLetter\Card;

use MyApp\LoveLetter\LoveLetter;

/**
 * Errätst du die Handkarte eines Mitspielers, scheidet dieser aus ... Gilt nicht für "Wächterin"!
 */
class Guardian extends AbstractCard implements EffectInterface
{
    public static $name = 'Wächterin';
    public static $value = 1;
    public static $text = 'Errätst du die Handkarte eines Mitspielers, scheidet dieser aus ... Gilt nicht für "Wächterin"!';

    public static function activate(LoveLetter $game, $params = [])
    {
        // Initial invocation without parameters, let player select other player.
        if ($game->getWaitFor() === LOVELETTER::CHOOSE_CARD) {
            $game->setStatus($game->getActivePlayerName() . ' sucht Mitspieler für Karteneffekt "' . $game->getActiveCardName() . '" aus ...');
            $game->setWaitFor(LOVELETTER::CHOOSE_PLAYER);
            return;
        }

        // Player has selected other player. Now let him guess a card.
        if ($game->getWaitFor() === LOVELETTER::CHOOSE_PLAYER) {
            $id = (int)$params['id'];
            $chosenPlayer = $game->getPlayerById($id);
            $game->setGuardianEffectId($id);
            $game->setGuardianEffectName($chosenPlayer->getName());
            $game->setWaitFor(LOVELETTER::CHOOSE_GUARDIAN_EFFECT_CARD);
            return;
        }

        // Check if player was right, then the other player is out.
        $chosenPlayer = $game->getPlayerById($game->getGuardianEffect()['id']);
        $card = $params['card'];
        $chosenPlayerState = $chosenPlayer->getGameState();
        $chosenPlayerCard = array_slice($chosenPlayerState->getCards(), 0, 1)[0];
        if ($card === $chosenPlayerCard['name']) {
            $game->addOutOfGamePlayer($chosenPlayer->getId());
            $cards = $chosenPlayerState->getCards();
            $game->discardCard($cards);
            $chosenPlayerState->setCards($cards);
            $game->setStatus($card . '! Richtig geraten! ' . $chosenPlayer->getName() . ' scheidet aus! ');
        } else {
            $game->setStatus($card . '! Falsch geraten! ');
        }
        $game->setStatus($game->getStatus() . $game->getActivePlayerName() . ' muss seine Karte auf den Ablagestapel legen ...');
        $game->setWaitFor(LOVELETTER::CONFIRM_DISCARD_CARD);
        $game->resetGuardianEffect();
    }
}
