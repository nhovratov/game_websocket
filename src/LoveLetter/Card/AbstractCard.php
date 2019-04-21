<?php

namespace MyApp\LoveLetter\Card;

abstract class AbstractCard
{
    public static $name = '';
    public static $value = 0;
    public static $text = '';

    public static function getCard()
    {
        return [
            'name' => static::$name,
            'value' => static::$value,
            'text' => static::$text
        ];
    }
}
