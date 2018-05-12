<?php
/**
 * Created by PhpStorm.
 * User: NIKITA
 * Date: 10.05.2018
 * Time: 19:57
 */

namespace MyApp;


class Player
{
    protected $client;

    protected $id;

    protected $gameState;

    public function __construct($client, $id)
    {
        $this->client = $client;
        $this->id = $id;
    }

    public function getState()
    {
        return [
            "id" => $this->id
        ];
    }

    public function getGameState()
    {
        return $this->gameState;
    }

    /**
     * @param mixed $gameState
     */
    public function setGameState($gameState)
    {
        $this->gameState = $gameState;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getClient()
    {
        return $this->client;
    }

    public function removeClient()
    {
        $this->client = null;
    }

    /**
     * @param mixed $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

}
