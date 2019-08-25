<?php

namespace NH\LoveLetter;

use NH\LoveLetter\Card\Countess;
use NH\LoveLetter\Card\King;
use NH\LoveLetter\Card\Prince;

class Validator
{
    public static function validateCardCanBeSet($cards, $chosenCard)
    {
        $originalCards = $cards;
        $cards = array_values($originalCards);
        $cardNames = [$cards[0]['name'], $cards[1]['name']];

        // Invalid key provided
        if (!key_exists($chosenCard, $originalCards)) {
            return false;
        }
        $chosenCardName = $originalCards[$chosenCard]['name'];

        // Wenn Prinz oder König gewählt wurde und man zusätzlich die Gräfin in der Hand hält
        if (
            in_array($chosenCardName, [Prince::$name, King::$name])
            && in_array(Countess::$name, $cardNames)
        ) {
            return false;
        }
        return true;
    }
}
