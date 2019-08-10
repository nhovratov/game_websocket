<?php
/**
 * Created by PhpStorm.
 * User: NIKITA
 * Date: 10.05.2018
 * Time: 19:53
 */

namespace MyApp;

use MyApp\LoveLetter\LoveLetter;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class Game implements MessageComponentInterface
{
    protected $players;

    protected $globalState = [];

    protected $game;

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
    }

    /**
     * This is called before or after a socket is closed (depends on how it's closed).  SendMessage to $conn will not result in an error if it has already been closed.
     * @param ConnectionInterface $conn The socket/connection that is closing/closed
     * @throws \Exception
     */
    function onClose(ConnectionInterface $conn)
    {
        /** @var Player $player */
        foreach ($this->players as $player) {
            if ($player->getClient() === $conn) {
                $player->removeClient();
                break;
            }
        }
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
        if (isset($msg['id'])) {
            $player = $this->getPlayerById($msg['id']);
            if (!$player) {
                $player = $this->createNewPlayer($from);
                if ($this->players->count() === 1) {
                    $player->setIsHost(true);
                    $this->globalState['hostid'] = $player->getId();
                }
            } else {
                if (!$player->getClient()) {
                    $player = $this->getPlayerById($msg['id']);
                    $player->setClient($from);
                }
                if (isset($msg['name'])) {
                    $player->setName($msg['name']);
                }
            }
        } else {
            $this->createNewPlayer($from);
        }

        if (isset($msg['action'])) {
            $params = [];
            if (key_exists('params', $msg)) {
                $params = $msg['params'];
            }
            switch ($msg['action']) {
                case 'start':
                    $players = clone $this->players;
                    foreach ($players as $p) {
                        if (!$p->getClient()) {
                            $players->detach($p);
                        }
                    }
                    $players->rewind();
                    $this->game->start($players);
                    return;
                default:
                    $this->game->handleAction($params);
                    return;
            }
        }
        $this->update();
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
                'local' => $player->getState($this->players)
            ]));
        }
        $this->game->updateState();
    }

    protected function createNewPlayer($from)
    {
        /** @var Session $session */
        $session = $from->Session;
        $session->start();
        $sessionId = md5(time());
        $session->set('id', $sessionId);
        $uid = $this->getUniqueId();
        $player = new Player($from, $uid);
        $this->players->attach($player);
        $from->send(json_encode([
            'global' => $this->globalState,
            'local' => [
                'newId' => $sessionId,
                'id' => $uid
            ]
        ]));
        return $player;
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
