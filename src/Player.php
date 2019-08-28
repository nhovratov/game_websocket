<?php
/**
 * Created by PhpStorm.
 * User: NIKITA
 * Date: 10.05.2018
 * Time: 19:57
 */

namespace NH;

use NH\LoveLetter\PlayerState;
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
    private $sessionId = '';

    /**
     * @var PlayerState
     */
    private $playerState = null;

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
        $this->sessionId = $uid;
    }

    /**
     * Local game state
     * @return array
     */
    public function getLocalState()
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "isHost" => $this->isHost
        ];
    }

    /**
     * @param PlayerState $playerState
     */
    public function setPlayerState(PlayerState $playerState)
    {
        $this->playerState = $playerState;
    }

    /**
     * @return PlayerState
     */
    public function getPlayerState()
    {
        return $this->playerState;
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
        return $this->sessionId === $value;
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
