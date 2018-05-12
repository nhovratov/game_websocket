<?php
/**
 * Created by PhpStorm.
 * User: NIKITA
 * Date: 12.05.2018
 * Time: 21:35
 */

namespace MyApp;


class LoveLetter
{
    protected $players;

    public function start($players)
    {
        $this->players = $players;
        // Start things up
    }
}
