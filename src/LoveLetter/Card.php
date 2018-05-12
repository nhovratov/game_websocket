<?php
/**
 * Created by PhpStorm.
 * User: NIKITA
 * Date: 12.05.2018
 * Time: 23:18
 */

namespace MyApp\LoveLetter;


class Card
{
    protected $name = "";

    protected $value = 0;

    protected $effect = "";

    public function __construct($name, $value, $effect)
    {
        $this->name = $name;
        $this->value = $value;
        $this->effect = $effect;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }


    /**
     * @return int
     */
    public function getValue(): int
    {
        return $this->value;
    }


    /**
     * @return string
     */
    public function getEffect(): string
    {
        return $this->effect;
    }

    public function toArray()
    {
        return [
            'name' => $this->name,
            'value' => $this->value,
            'effect' => $this->effect
        ];
    }

}
