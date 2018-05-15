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

class LoveLetterTest extends TestCase
{

    public function setUp()
    {

    }

    /**
     * @test
     */
    public function testStart2()
    {
        $players = new SplObjectStorage();
        $game = new LoveLetter();
        $players->attach(new Player(new Connection(), 1));
        $players->attach(new Player(new Connection(), 2));

        $this->assertCount(16, $game->getStack());

        $game->start($players);
        $state = $game->getGlobalState();

        $this->assertCount(2, $game->getPlayers());
        $players->rewind();
        $this->assertTrue($state['gameStarted']);
        $this->assertCount(1, $state['reserve']);
        $this->assertCount(3, $state['outOfGameCards']);
        foreach ($players as $player) {
            $this->assertCount(1, $player->getGameState()['cards']);
        }
    }

    /**
     * @test
     */
    public function testStart3()
    {
        $players = new SplObjectStorage();
        $game = new LoveLetter();
        $players->attach(new Player(new Connection(), 1));
        $players->attach(new Player(new Connection(), 2));
        $players->attach(new Player(new Connection(), 3));

        $this->assertCount(16, $game->getStack());

        $game->start($players);
        $state = $game->getGlobalState();

        $this->assertCount(3, $game->getPlayers());
        $players->rewind();
        $this->assertTrue($state['gameStarted']);
        $this->assertCount(1, $state['reserve']);
        $this->assertCount(0, $state['outOfGameCards']);
        foreach ($players as $player) {
            $this->assertCount(1, $player->getGameState()['cards']);
        }
    }

    /**
     * @test
     */
    public function testStart4()
    {
        $players = new SplObjectStorage();
        $game = new LoveLetter();
        $players->attach(new Player(new Connection(), 1));
        $players->attach(new Player(new Connection(), 2));
        $players->attach(new Player(new Connection(), 3));
        $players->attach(new Player(new Connection(), 4));

        $this->assertCount(16, $game->getStack());

        $game->start($players);
        $state = $game->getGlobalState();

        $this->assertCount(4, $game->getPlayers());
        $players->rewind();
        $this->assertTrue($state['gameStarted']);
        $this->assertCount(1, $state['reserve']);
        $this->assertCount(0, $state['outOfGameCards']);
        foreach ($players as $player) {
            $this->assertCount(1, $player->getGameState()['cards']);
        }
    }
}
