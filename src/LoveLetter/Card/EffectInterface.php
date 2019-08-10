<?php

namespace NH\LoveLetter\Card;

use NH\LoveLetter\LoveLetter;

interface EffectInterface
{
    public static function activate(LoveLetter $game, $params = []);
}
