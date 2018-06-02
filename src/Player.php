<?php
/**
 * Created by PhpStorm.
 * User: NIKITA
 * Date: 10.05.2018
 * Time: 19:57
 */

namespace MyApp;


use Ratchet\ConnectionInterface;

class Player
{
    /**
     * @var ConnectionInterface
     */
    protected $client;

    /**
     * @var int
     */
    protected $id = 0;

    /**
     * @var StateInterface
     */
    protected $gameState = null;

    /**
     * @var string
     */
    protected $name = '';

    public function __construct($client, $id)
    {
        $this->client = $client;
        $this->id = $id;
    }

    public function getState()
    {
        return [
            "id" => $this->id,
            "name" => $this->name
        ];
    }

    /**
     * @param StateInterface $gameState
     */
    public function setGameState(StateInterface $gameState)
    {
        $this->gameState = $gameState;
    }

    /**
     * @return StateInterface
     */
    public function getGameState()
    {
        return $this->gameState;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return ConnectionInterface
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
     * @param ConnectionInterface $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

}
