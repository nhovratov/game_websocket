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
    public function testStartWithTwoPlayers()
    {
        $players = new SplObjectStorage();
        $game = new LoveLetter($this->mockStackProvider);
        $player1 = new Player(new Connection(), 1);
        $player2 = new Player(new Connection(), 2);
        $players->attach($player1);
        $players->attach($player2);

        $game->start($players);
        $state = $game->getGlobalState();

        $this->assertCount(2, $game->getPlayerInfo());
        $this->assertTrue($state['gameStarted']);
        $this->assertCount(3, $state['outOfGameCards']);
        $this->assertEquals($game::SELECT_FIRST_PLAYER, $state['waitFor']);

        $playerState = $player1->getGameState();
        $this->assertCount(1, $playerState->getCards());

        $cards = $playerState->getCards();
        $handCard = current($cards);
        $this->assertEquals('Wächterin', $handCard['name']);

        $playerState = $player2->getGameState();
        $this->assertCount(1, $playerState->getCards());
        $cards = $playerState->getCards();
        $handCard = current($cards);
        $this->assertEquals('Wächterin', $handCard['name']);

        $this->assertEquals('Wächterin', $state['outOfGameCards'][0]['name']);
        $this->assertEquals('Wächterin', $state['outOfGameCards'][1]['name']);
        $this->assertEquals('Wächterin', $state['outOfGameCards'][2]['name']);
    }

    /**
     * @test
     */
    public function testStartWithThreePlayers()
    {
        $players = new SplObjectStorage();
        $game = new LoveLetter($this->mockStackProvider);
        $players->attach(new Player(new Connection(), 1));
        $players->attach(new Player(new Connection(), 2));
        $players->attach(new Player(new Connection(), 3));

        $game->start($players);
        $state = $game->getGlobalState();

        $this->assertCount(3, $game->getPlayerInfo());
        $this->assertTrue($state['gameStarted']);
        $this->assertCount(0, $state['outOfGameCards']);
    }


    /**
     * @test
     */
    public function testGuardians()
    {
        $players = new SplObjectStorage();
        $this->mockStackProvider->setTestCase('guardian');
        $game = new LoveLetter($this->mockStackProvider);
        $john = new Player(new Connection(), 1);
        $john->setName('John');
        $players->attach($john);

        $mikel = new Player(new Connection(), 2);
        $mikel->setName('Mikel');
        $players->attach($mikel);

        $game->start($players);

        // John begins
        $game->handleAction(['id' => 1]);
        $state = $game->getGlobalState();
        $this->assertEquals($game::CHOOSE_CARD, $state['waitFor']);
        $this->assertEquals(1, $state['playerTurn']);

        // John chooses Guardian card
        $game->handleAction(['key' => 7]);
        $state = $game->getGlobalState();
        $this->assertEquals($game::CHOOSE_PLAYER, $state['waitFor']);
        $this->assertEquals('Wächterin', $state['activeCard']['name']);

        // Select Mikel for Effect card
        $game->handleAction(['id' => 2]);
        $state = $game->getGlobalState();
        $this->assertEquals($game::CHOOSE_GUARDIAN_EFFECT_CARD, $state['waitFor']);

        // Select Baron (wrong)
        $game->handleAction(['card' => 'Baron']);

        // No cards left, game is finished with a tie
        $state = $game->getGlobalState();
        $this->assertTrue($state['gameFinished']);
        $this->assertCount(2, $state['winners']);
        $this->assertEquals($game::START_NEW_GAME, $state['waitFor']);
    }

    /**
     * @test
     */
    public function testMaid()
    {
        $players = new SplObjectStorage();
        $this->mockStackProvider->setTestCase('maid');
        $game = new LoveLetter($this->mockStackProvider);
        $john = new Player(new Connection(), 1);
        $john->setName('John');
        $players->attach($john);

        $mikel = new Player(new Connection(), 2);
        $mikel->setName('Mikel');
        $players->attach($mikel);

        $game->start($players);

        // John begins
        $game->handleAction(['id' => 1]);

        // John chooses Maid card
        $game->handleAction(['key' => 8]);
        $state = $game->getGlobalState();
        $this->assertEquals($game::PLACE_MAID_CARD, $state['waitFor']);
        $this->assertEquals('Zofe', $state['activeCard']['name']);

        // Place maid card
        $game->handleAction();
        $state = $game->getGlobalState();
        $this->assertEquals(2, $state['playerTurn']);
        $this->assertEquals($game::CHOOSE_CARD, $state['waitFor']);

        // Select Guardian, but no selectable player left
        $game->handleAction(['key' => 7]);

        // No cards left, game is finished with a tie
        $state = $game->getGlobalState();
        $this->assertTrue($state['gameFinished']);
        $this->assertCount(2, $state['winners']);
        $this->assertEquals($game::START_NEW_GAME, $state['waitFor']);
    }

    /**
     * @test
     */
    public function testPriest()
    {
        $players = new SplObjectStorage();
        $this->mockStackProvider->setTestCase('priest');
        $game = new LoveLetter($this->mockStackProvider);
        $john = new Player(new Connection(), 1);
        $john->setName('John');
        $players->attach($john);

        $mikel = new Player(new Connection(), 2);
        $mikel->setName('Mikel');
        $players->attach($mikel);

        $game->start($players);

        // John begins
        $game->handleAction(['id' => 1]);

        // John chooses priest card
        $game->handleAction(['key' => 8]);
        $state = $game->getGlobalState();
        $this->assertEquals($game::CHOOSE_PLAYER, $state['waitFor']);
        $this->assertEquals('Priester', $state['activeCard']['name']);

        // John chooses Mikel to look into his card
        $game->handleAction(['id' => 2]);
        $state = $game->getGlobalState();
        $this->assertEquals($game::FINISH_LOOKING_AT_CARD, $state['waitFor']);
        $johnState = $john->getGameState();
        $this->assertEquals('Priester', $johnState->getPriestEffectVisibleCard());

        // John finishes looking at Mikels cards and discards his card
        $game->handleAction();
        $game->handleAction();

        // Mikel chooses priest card
        $game->handleAction(['key' => 7]);

        // Mikel chooses John to look in his card
        $game->handleAction(['id' => 1]);

        // Mikel finishes looking
        $game->handleAction();

        // No cards left, game is finished with a tie
        $state = $game->getGlobalState();
        $this->assertTrue($state['gameFinished']);
        $this->assertCount(2, $state['winners']);
        $this->assertEquals($game::START_NEW_GAME, $state['waitFor']);
    }

    /**
     * @test
     */
    public function testBaronWin()
    {
        $players = new SplObjectStorage();
        $this->mockStackProvider->setTestCase('baronWin');
        $game = new LoveLetter($this->mockStackProvider);
        $john = new Player(new Connection(), 1);
        $john->setName('John');
        $players->attach($john);

        $mikel = new Player(new Connection(), 2);
        $mikel->setName('Mikel');
        $players->attach($mikel);

        $game->start($players);

        // John begins
        $game->handleAction(['id' => 1]);

        // John chooses baron card
        $game->handleAction(['key' => 7]);
        $state = $game->getGlobalState();
        $this->assertEquals($game::CHOOSE_PLAYER, $state['waitFor']);
        $this->assertEquals('Baron', $state['activeCard']['name']);

        // John chooses Mikel to compare cards
        $game->handleAction(['id' => 2]);

        // Johns card 'princess(8)' is higher than Mikels card 'guardian(1)'
        $state = $game->getGlobalState();
        $this->assertTrue($state['gameFinished']);
        $this->assertCount(1, $state['winners']);
        $this->assertEquals(2, $state['outOfGamePlayers'][0]);
        $this->assertEquals($game::START_NEW_GAME, $state['waitFor']);
    }

    /**
     * @test
     */
    public function testBaronLoose()
    {
        $players = new SplObjectStorage();
        $this->mockStackProvider->setTestCase('baronLoose');
        $game = new LoveLetter($this->mockStackProvider);
        $john = new Player(new Connection(), 1);
        $john->setName('John');
        $players->attach($john);

        $mikel = new Player(new Connection(), 2);
        $mikel->setName('Mikel');
        $players->attach($mikel);

        $game->start($players);

        // Mikel begins
        $game->handleAction(['id' => 2]);

        // Mikel chooses baron card
        $game->handleAction(['key' => 1]);

        // Mikel chooses John to compare cards
        $game->handleAction(['id' => 1]);

        // Johns card 'princess(8)' is higher than Mikels card 'guardian(1)'
        $state = $game->getGlobalState();
        $this->assertTrue($state['gameFinished']);
        $this->assertCount(1, $state['winners']);
        $this->assertEquals(2, $state['outOfGamePlayers'][0]);
        $this->assertEquals($game::START_NEW_GAME, $state['waitFor']);
    }

    /**
     * @test
     */
    public function testBaronEqual()
    {
        $players = new SplObjectStorage();
        $this->mockStackProvider->setTestCase('baronEqual');
        $game = new LoveLetter($this->mockStackProvider);
        $john = new Player(new Connection(), 1);
        $john->setName('John');
        $players->attach($john);

        $mikel = new Player(new Connection(), 2);
        $mikel->setName('Mikel');
        $players->attach($mikel);

        $game->start($players);

        // Mikel begins
        $game->handleAction(['id' => 2]);

        // Mikel chooses baron card
        $game->handleAction(['key' => 10]);

        // Mikel chooses John to compare cards
        $game->handleAction(['id' => 1]);

        // Johns card 'princess(8)' is equal to Mikels card 'princess(8)'
        $state = $game->getGlobalState();
        $this->assertFalse($state['gameFinished']);
    }
}
