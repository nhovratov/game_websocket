<?php

namespace MyApp\LoveLetter\Card;

use MyApp\LoveLetter\LoveLetter;

interface EffectInterface
{
    public static function activate(LoveLetter $game, $params = []);
}
