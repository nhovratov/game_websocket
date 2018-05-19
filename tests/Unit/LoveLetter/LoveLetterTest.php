<?php
/**
 * Created by PhpStorm.
 * User: NIKITA
 * Date: 12.05.2018
 * Time: 23:44
 */

use MyApp\LoveLetter\LoveLetter;
use MyApp\Mock\MockStackProvider;
use MyApp\Player;
use PHPUnit\Framework\TestCase;
use Ratchet\Mock\Connection;

class LoveLetterTest extends TestCase
{
    protected $mockStackProvider;

    public function setUp()
    {
        $this->mockStackProvider = new MockStackProvider();
    }

    /**
     * @test
     */
    public function testStart2()
    {
        $players = new SplObjectStorage();
        $game = new LoveLetter($this->mockStackProvider);
        $player1 = new Player(new Connection(), 1);
        $player1->setName('John');
        $players->attach($player1);

        $player2 = new Player(new Connection(), 2);
        $player2->setName('Mikel');
        $players->attach($player2);

        $game->start($players);
        $state = $game->getGlobalState();

        $this->assertCount(2, $game->getPlayers());
        $players->rewind();
        $this->assertTrue($state['gameStarted']);
        $this->assertEquals($game::WAIT_FOR_FIRST_PLAYER_SELECT, $state['waitFor']);
        foreach ($players as $player) {
            $playerState = $player->getGameState();
            $this->assertCount(1, $playerState['cards']);
            $this->assertEquals('Wächterin', $playerState['cards'][0]['name']);
        }
        $players->rewind();
        $this->assertCount(1, $state['reserve']);
        $this->assertEquals('Wächterin', $state['reserve'][0]['name']);
        $this->assertCount(3, $state['outOfGameCards']);
        $this->assertEquals('Wächterin', $state['outOfGameCards'][0]['name']);
        $this->assertEquals('Wächterin', $state['outOfGameCards'][1]['name']);
        $this->assertEquals('Prinzessin', $state['outOfGameCards'][2]['name']);

        // John begins
        $game->handleAction('selectFirstPlayer', ['id' => 1]);
        $state = $game->getGlobalState();
        $this->assertEquals($game::WAIT_FOR_CHOOSE_CARD, $state['waitFor']);
        $this->assertEquals(1, $state['playerTurn']);

        // Player chooses Guardian card
        $game->handleAction('chooseCard', ['index' => 0]);
        $state = $game->getGlobalState();
        $this->assertEquals($game::WAIT_FOR_CHOOSE_PLAYER, $state['waitFor']);
        $this->assertEquals('Wächterin', $state['activeCard']['name']);
        $this->assertEquals('Wächterin', $state['openCards'][0]['name']);

        // Select Mikel for Effect card
        $game->handleAction('selectPlayerForEffect', ['id' => 2]);
        $state = $game->getGlobalState();
        $this->assertEquals($game::WAIT_FOR_CHOOSE_GUARDIAN_EFFECT_CARD, $state['waitFor']);

        // Select Baron (wrong)
        $game->handleAction('selectGuardianEffectCard', ['card' => 'Baron']);
        $state = $game->getGlobalState();
        $this->assertFalse($state['gameFinished']);

        // John was wrong so he discards his card
        $game->handleAction('discardActiveCard');
        $state = $game->getGlobalState();
        $this->assertCount(0, $state['openCards']);
        $this->assertEmpty($state['activeCard']);
        $this->assertEquals(2, $state['playerTurn']);
        $this->assertEquals($game::WAIT_FOR_CHOOSE_CARD, $state['waitFor']);
    }

    /**
     * @test
     */
    public function testStart3()
    {
        $players = new SplObjectStorage();
        $game = new LoveLetter($this->mockStackProvider);
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
    }

}
