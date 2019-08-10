<?php

namespace NH\LoveLetter\Card;

abstract class AbstractCard
{
    public static $id;
    public static $name;
    public static $value;
    public static $text;

    public static function getCard()
    {
        return [
            'id' => static::$id,
            'name' => static::$name,
            'value' => static::$value,
            'text' => static::$text
        ];
    }
}
