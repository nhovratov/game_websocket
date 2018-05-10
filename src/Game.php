<?php
/**
 * Created by PhpStorm.
 * User: NIKITA
 * Date: 10.05.2018
 * Time: 19:53
 */

namespace MyApp;

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class Game implements MessageComponentInterface
{
    /**
     * @var array
     */
    protected $stack = ['One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight'];

    protected $players;

    protected $clients;

    public function __construct()
    {
        $this->mixStack();
        $this->players = new \SplObjectStorage();
        $this->clients = new \SplObjectStorage();
    }

    public function start()
    {
        foreach ($this->players as $player) {
            $player->setCards(array_splice($this->stack, 0, 4));
            $player->getClient()->send(json_encode($player->getCards()));
        }
    }

    /**
     * When a new connection is opened it will be passed to this method
     * @param  ConnectionInterface $conn The socket/connection that just connected to your application
     * @throws \Exception
     */
    function onOpen(ConnectionInterface $conn)
    {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);
        $this->players->attach(new Player($conn));
        echo $this->players->count() . "\n";
        if ($this->players->count() == 2) {
            echo "Game starts! \n";
            $this->start();
        }
        echo "New connection! ({$conn->resourceId})\n";
    }

    /**
     * This is called before or after a socket is closed (depends on how it's closed).  SendMessage to $conn will not result in an error if it has already been closed.
     * @param  ConnectionInterface $conn The socket/connection that is closing/closed
     * @throws \Exception
     */
    function onClose(ConnectionInterface $conn)
    {
        // The connection is closed, remove it, as w can no longer send it messages
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    /**
     * If there is an error with one of the sockets, or somewhere in the application where an Exception is thrown,
     * the Exception is sent back down the stack, handled by the Server and bubbled back up the application through this method
     * @param  ConnectionInterface $conn
     * @param  \Exception $e
     * @throws \Exception
     */
    function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occured: {$e->getMessage()}\n";
        $conn->close();
    }

    /**
     * Triggered when a client sends data through the socket
     * @param  \Ratchet\ConnectionInterface $from The socket/connection that sent the message to your application
     * @param  string $msg The message received
     * @throws \Exception
     */
    function onMessage(ConnectionInterface $from, $msg)
    {
        $numReceiver = count($this->clients) - 1;
        echo sprintf(
            'Connection %d sending message "%s" to %d other connection%s' . "\n",
            $from->resourceId, $msg, $numReceiver, $numReceiver == 1 ? '': 's'
        );

        foreach ($this->clients as $client) {
            if ($from !== $client) {
                // The sender is not the receiver, send to each client connected
                $client->send($msg);
            }
        }

    }

    public function mixStack()
    {
        shuffle($this->stack);
    }

    /**
     * @return array
     */
    public function getStack(): array
    {
        return $this->stack;
    }

    /**
     * @param array $stack
     */
    public function setStack(array $stack)
    {
        $this->stack = $stack;
    }

}
