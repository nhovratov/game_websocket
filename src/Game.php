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
        $this->globalState['status'] = 'Willkommen!';
    }

    /**
     * When a new connection is opened it will be passed to this method
     * @param ConnectionInterface $conn The socket/connection that just connected to your application
     * @throws \Exception
     */
    function onOpen(ConnectionInterface $conn)
    {
        // noop
        echo "ONOPEN\n";
    }

    /**
     * This is called before or after a socket is closed (depends on how it's closed).  SendMessage to $conn will not result in an error if it has already been closed.
     * @param ConnectionInterface $conn The socket/connection that is closing/closed
     * @throws \Exception
     */
    function onClose(ConnectionInterface $conn)
    {
        echo "ONCLOSE\n";
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
        echo "START ONMESSAGE\n";
        $msg = json_decode($msg, true);

        // User have to identify himself
        if (!isset($msg['id'])) {
            $this->removeClient($from);
            $this->update();
            return;
        }

        // Client wants to identify
        if ($msg['id'] !== '') {
            echo "Id {$msg['id']} provided.\n";
            $player = $this->getPlayerById($msg['id']);
            // if id provided is invalid, create a new player
            if (!$player) {
                echo "Id {$msg['id']} does not exist. Create new player.\n";
                $player = $this->createNewPlayer($from);
            // if player already exists, get him a new connection if needed
            } elseif (!$player->getClient()) {
                echo "Id exists but no client is set. Set new client.\n";
                $player = $this->getPlayerById($msg['id']);
                $player->setClient($from);
            } else {
                // Everything is fine
                echo "Id {$msg['id']} identified successfully.\n";
            }
            // If player wants to connect without id, create new player
        } else {
            echo "New connection established. Create player.\n";
            $player = $this->createNewPlayer($from);
        }

        if (isset($msg['name']) && $msg['name'] !== '') {
            echo "Name {$msg['name']} provided. Set name.\n";
            $player->setName($msg['name']);
        }

        if (isset($msg['action'])) {
            $params = [];
            if (isset($msg['params'])) {
                $params = $msg['params'];
            }
            $params['uid'] = $msg['id'];
            switch ($msg['action']) {
                // This action requires host rights
                case 'start':
                    if (!$player->isHost()) {
                        break;
                    }
                    $players = clone $this->players;
                    foreach ($players as $p) {
                        if (!$p->getClient()) {
                            $players->detach($p);
                        }
                    }
                    $players->rewind();
                    $this->game->start($players);
                    break;
                default:
                    $this->game->handleAction($params);
            }
        }
        $this->update();
        echo "END ONMESSAGE";
    }

    protected function update()
    {
        $this->globalState['players'] = $this->getPlayers();
        foreach ($this->players as $player) {
            if (!$player->getClient()) {
                continue;
            }
            echo "Send current state to {$player->getId()}\n";
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
        echo "SEND: New id " . $sessionId . " and id " . $id . "\n";
        if ($this->players->count() === 1) {
            echo "Player is new host\n";
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
