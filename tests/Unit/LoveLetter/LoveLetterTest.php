<?php
/**
 * Created by PhpStorm.
 * User: NIKITA
 * Date: 12.05.2018
 * Time: 23:44
 */

use MyApp\LoveLetter\LoveLetter;
use MyApp\Player;
use PHPUnit\Framework\TestCase;
use Ratchet\Mock\Connection;

class LoveLetterTest extends TestCase {

    /**
     * @var LoveLetter
     */
    protected $game;

    /**
     * @var SplObjectStorage
     */
    protected $players;

    public function setUp()
    {
        $this->game = new MyApp\LoveLetter\LoveLetter();
        $this->players = new SplObjectStorage();
    }

    /**
     * @test
     */
    public function testStart2()
    {
        $this->players->attach(new Player(new Connection(), 1));
        $this->players->attach(new Player(new Connection(), 2));
        $this->game->start($this->players);
        $state = $this->game->getGlobalState();

        $this->assertCount(2, $this->game->getPlayers());
        $this->players->rewind();
        $this->assertTrue($state['gameStarted']);
        $this->assertCount(1, $state['reserve']);
        $this->assertCount(3, $state['outOfGameCards']);
        foreach ($this->players as $player) {
            $this->assertCount(1, $player->getGameState()['cards']);
        }
    }

    /**
     * @test
     */
    public function testStart3()
    {
        $this->players->attach(new Player(new Connection(), 1));
        $this->players->attach(new Player(new Connection(), 2));
        $this->players->attach(new Player(new Connection(), 3));
        $this->game->start($this->players);
        $state = $this->game->getGlobalState();

        $this->assertCount(3, $this->game->getPlayers());
        $this->players->rewind();
        $this->assertTrue($state['gameStarted']);
        $this->assertCount(1, $state['reserve']);
        $this->assertCount(0, $state['outOfGameCards']);
        foreach ($this->players as $player) {
            $this->assertCount(1, $player->getGameState()['cards']);
        }
    }

    /**
     * @test
     */
    public function testStart4()
    {
        $this->players->attach(new Player(new Connection(), 1));
        $this->players->attach(new Player(new Connection(), 2));
        $this->players->attach(new Player(new Connection(), 3));
        $this->players->attach(new Player(new Connection(), 4));
        $this->game->start($this->players);
        $state = $this->game->getGlobalState();

        $this->assertCount(4, $this->game->getPlayers());
        $this->players->rewind();
        $this->assertTrue($state['gameStarted']);
        $this->assertCount(1, $state['reserve']);
        $this->assertCount(0, $state['outOfGameCards']);
        foreach ($this->players as $player) {
            $this->assertCount(1, $player->getGameState()['cards']);
        }
    }

}
