<?php
/**
 * Created by PhpStorm.
 * User: NIKITA
 * Date: 12.05.2018
 * Time: 21:35
 */

namespace MyApp;


class LoveLetter implements GameInterface
{
    protected $players;

    protected $state;

    public function start($players)
    {
        $this->players = $players;
        $this->state['gameStarted'] = true;
        $this->updateState();
    }

    public function updateState()
    {
        foreach ($this->players as $player) {
            $msg = [
                'dataType' => 'game',
                'global' => $this->state,
                'local' => $player->getGameState()
            ];
            $player->getClient()->send(json_encode($msg));
        }
    }
}
