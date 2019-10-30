<?php
/**
 * Created by PhpStorm.
 * User: NIKITA
 * Date: 10.05.2018
 * Time: 19:53
 */

namespace NH;

use NH\LoveLetter\LoveLetter;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class Game implements MessageComponentInterface
{
    protected $players;

    protected $globalState = [];

    protected $game;

    protected $secret = 'f$iz98HdidHDL?!:';

    public function __construct()
    {
        $this->players = new \SplObjectStorage();
        $this->game = new LoveLetter();
    }

    /**
     * When a new connection is opened it will be passed to this method
     * @param ConnectionInterface $conn The socket/connection that just connected to your application
     * @throws \Exception
     */
    function onOpen(ConnectionInterface $conn)
    {
        // noop
    }

    /**
     * This is called before or after a socket is closed (depends on how it's closed).  SendMessage to $conn will not result in an error if it has already been closed.
     * @param ConnectionInterface $conn The socket/connection that is closing/closed
     * @throws \Exception
     */
    function onClose(ConnectionInterface $conn)
    {
        $this->removeClient($conn);
        $this->update();
    }

    /**
     * If there is an error with one of the sockets, or somewhere in the application where an Exception is thrown,
     * the Exception is sent back down the stack, handled by the Server and bubbled back up the application through this method
     * @param ConnectionInterface $conn
     * @param \Exception $e
     * @throws \Exception
     */
    function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occured: {$e->getMessage()}\n";
        $conn->close();
    }

    /**
     * Triggered when a client sends data through the socket
     * @param \Ratchet\ConnectionInterface $from The socket/connection that sent the message to your application
     * @param string $msg The message received
     * @throws \Exception
     */
    function onMessage(ConnectionInterface $from, $msg)
    {
        $msg = json_decode($msg, true);

        if (!isset($msg['id'])) {
            $this->removeClient($from);
            $this->update();
            return;
        }

        if ($msg['id'] !== '') {
            $player = $this->getPlayerById($msg['id']);
            if (!$player) {
                $player = $this->createNewPlayer($from);
            } elseif (!$player->getClient()) {
                $player->setClient($from);
            }
        } else {
            $player = $this->createNewPlayer($from);
        }

        if (isset($msg['name']) && $msg['name'] !== '') {
            $player->setName($msg['name']);
        }

        if (isset($msg['action']) && $msg['action'] !== '') {
            $params = [];
            if (isset($msg['params']) && is_array($msg['params'])) {
                $params = $msg['params'];
            }
            $params['uid'] = $msg['id'];
            if ($msg['action'] === 'start') {
                if (!$player->isHost()) {
                    $this->removeClient($player);
                    $this->update();
                    return;
                }
                $players = clone $this->players;
                foreach ($players as $p) {
                    if (!$p->getClient()) {
                        $players->detach($p);
                    }
                }
                $players->rewind();
                $params['players'] = $players;
            }
            $this->game->handleAction($params);
        }
        $this->update();
        $this->game->updateState();
    }

    protected function update()
    {
        $this->globalState['players'] = $this->getPlayers();
        foreach ($this->players as $player) {
            if (!$player->getClient()) {
                continue;
            }
            $player->getClient()->send(json_encode([
                'global' => $this->globalState,
                'local' => $player->getLocalState()
            ]));
        }
    }

    protected function createNewPlayer($from)
    {
        $id = $this->getUniqueId();
        $sessionId = md5(time() . $this->secret . $id);
        $player = new Player($from, $id, $sessionId);
        $this->players->attach($player);
        if ($this->players->count() === 1) {
            $player->setIsHost(true);
            $this->globalState['hostid'] = $player->getId();
        }
        $from->send(json_encode([
            'global' => $this->globalState,
            'local' => [
                'newId' => $sessionId,
                'id' => $id
            ]
        ]));
        return $player;
    }

    protected function removeClient($conn)
    {
        /** @var Player $player */
        foreach ($this->players as $player) {
            if ($player->getClient() === $conn) {
                $conn->close();
                $player->removeClient();
                return true;
            }
        }
        return false;
    }

    /**
     * @param $id
     * @return object|null
     */
    protected function getPlayerById($id): ?Player
    {
        /** @var Player $player */
        foreach ($this->players as $player) {
            if ($player->isUserIdentifier($id)) {
                return $player;
            }
        }
        return null;
    }

    /**
     * @return array
     */
    protected function getPlayers(): array
    {
        $players = [];
        foreach ($this->players as $player) {
            $players[] = [
                "id" => $player->getId(),
                "name" => $player->getName(),
                "connected" => (bool)$player->getClient()
            ];
        }
        return $players;
    }

    protected function getUniqueId()
    {
        static $count = 0;
        $count += 1;
        return $count;
    }
}
