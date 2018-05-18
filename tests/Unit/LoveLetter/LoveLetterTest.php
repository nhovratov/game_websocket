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
        $players->rewind();

        $game->handleAction('selectFirstPlayer', ['id' => 1]);
        $state = $game->getGlobalState();
        $this->assertTrue($state['firstPlayerSelected']);
        $this->assertEquals(1, $state['playerTurn']);

        $game->handleAction('chooseCard', ['index' => 1]);
        $state = $game->getGlobalState();
        $this->assertFalse($state['waitingForPlayerToChooseCard']);
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
        $players->rewind();

        $game->handleAction('selectFirstPlayer', ['id' => 1]);
        $state = $game->getGlobalState();
        $this->assertTrue($state['firstPlayerSelected']);
        $this->assertEquals(1, $state['playerTurn']);

        $game->handleAction('chooseCard', ['index' => 1]);
        $state = $game->getGlobalState();
        $this->assertFalse($state['waitingForPlayerToChooseCard']);
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
        $players->rewind();

        $game->handleAction('selectFirstPlayer', ['id' => 1]);
        $state = $game->getGlobalState();
        $this->assertTrue($state['firstPlayerSelected']);
        $this->assertEquals(1, $state['playerTurn']);

        $game->handleAction('chooseCard', ['index' => 1]);
        $state = $game->getGlobalState();
        $this->assertFalse($state['waitingForPlayerToChooseCard']);
    }
}
