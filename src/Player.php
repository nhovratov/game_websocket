<?php
/**
 * Created by PhpStorm.
 * User: NIKITA
 * Date: 10.05.2018
 * Time: 19:57
 */

namespace MyApp;

use MyApp\LoveLetter\LoveLetter;
use Ratchet\ConnectionInterface;

class Player
{
    /**
     * @var ConnectionInterface
     */
    private $client;

    /**
     * @var int
     */
    private $id = 0;

    /**
     * @var string
     */
    private $uid = '';

    /**
     * @var StateInterface
     */
    private $gameState = null;

    /**
     * @var string
     */
    private $name = '';

    /**
     * @var bool
     */
    private $isHost = false;

    public function __construct($client, $id, $uid)
    {
        $this->client = $client;
        $this->id = $id;
        $this->uid = $uid;
    }

    public function getState($players)
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "isHost" => $this->isHost,
            "canStartGame" => $this->isHost && LoveLetter::isGameReady($players)
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
     * @param $value string
     * @return bool
     */
    public function isUserIdentifier($value): bool
    {
        return $this->uid === $value;
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

    /**
     * @return bool
     */
    public function isHost(): bool
    {
        return $this->isHost;
    }

    /**
     * @param bool $isHost
     */
    public function setIsHost(bool $isHost)
    {
        $this->isHost = $isHost;
    }
}
