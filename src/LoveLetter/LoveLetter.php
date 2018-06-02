<?php
/**
 * Created by PhpStorm.
 * User: NIKITA
 * Date: 12.05.2018
 * Time: 21:35
 */

namespace MyApp\LoveLetter;

use MyApp\GameInterface;
use MyApp\Player;

class LoveLetter implements GameInterface
{

    const GUARDIANCARD = [
        'name' => 'Wächterin',
        'value' => 1,
        'effect' => 'Errätst du die Handkarte eines Mitspielers, scheidet dieser aus ... Gilt nicht für "Wächterin"!'
    ];

    const PRIESTCARD = [
        'name' => 'Priester',
        'value' => 2,
        'effect' => 'Schaue dir die Handkarte eines Mitspielers an.'
    ];

    const BARONCARD = [
        'name' => 'Baron',
        'value' => 3,
        'effect' => 'Vergleiche deine Handkarte mit der eines Mitspielers. Der Spieler mit dem niedrigeren Wert scheidet aus ...'
    ];

    const MAIDCARD = [
        'name' => 'Zofe',
        'value' => 4,
        'effect' => 'Du bist bis zu deinem nächsten Zug geschützt.'
    ];

    const PRINCECARD = [
        'name' => 'Prinz',
        'value' => 5,
        'effect' => 'Wähle einen Spieler, der seine Handkarte ablegt und eine neue Karte zieht.'
    ];

    const KINGCARD = [
        'name' => 'König',
        'value' => 6,
        'effect' => 'Tausche deine Handkarte mit der eines Mitspielers'
    ];

    const COUNTESSCARD = [
        'name' => 'Gräfin',
        'value' => 7,
        'effect' => 'Wenn du zusätzlich König oder Prinz auf der Hand hast, musst du die Gräfin ausspielen.'
    ];

    const PRINCESSCARD = [
        'name' => 'Prinzessin',
        'value' => 8,
        'effect' => 'Wenn du die Prinzessin ablegst, scheidest du aus ...'
    ];

    const GUARDIANCOUNT = 5;
    const PRIESTCOUNT = 2;
    const BARONCOUNT = 2;
    const MAIDCOUNT = 2;
    const PRINCECOUNT = 2;
    const KINGCOUNT = 1;
    const COUNTESSCOUNT = 1;
    const PRINCESSCOUNT = 1;

    const WAIT_FOR_SELECT_FIRST_PLAYER = 'selectFirstPlayer';
    const WAIT_FOR_CHOOSE_CARD = 'chooseCard';
    const WAIT_FOR_CHOOSE_PLAYER = 'choosePlayer';
    const WAIT_FOR_CHOOSE_ANY_PLAYER = 'chooseAnyPlayer';
    const WAIT_FOR_CHOOSE_GUARDIAN_EFFECT_CARD = 'chooseGuardianEffectCard';
    const WAIT_FOR_CONFIRM_DISCARD_CARD = 'confirmDiscardCard';
    const WAIT_FOR_START_NEW_GAME = 'startNewGame';
    const WAIT_FOR_FINISH_LOOKING_AT_CARD = 'finishLookingAtCard';
    const WAIT_FOR_PLACE_MAID_CARD = 'placeMaidCard';

    const GUARDIAN_EFFECT_DEFAULT = [
        'name' => '',
        'selectableCards' => [
            'Priester',
            'Baron',
            'Zofe',
            'Prinz',
            'König',
            'Gräfin',
            'Prinzessin'
        ]
    ];

    // Intern Game state only provided to public if necessary

    /**
     * @var \SplObjectStorage
     */
    protected $players = [];

    /**
     * @var StackProvider
     */
    protected $stackProvider = null;

    /**
     * @var array
     */
    protected $stack = [];

    /**
     * @var array
     */
    protected $reserve = [];


    // Global state visible for all players

    /**
     * @var bool
     */
    protected $gameStarted = false;

    /**
     * @var bool
     */
    protected $gameFinished = false;

    /**
     * @var Player
     */
    protected $activePlayer = null;

    /**
     * @var string
     */
    protected $waitFor = '';

    /**
     * @var string
     */
    protected $status = '';

    /**
     * @var null
     */
    protected $activeCard = null;

    /**
     * @var array
     */
    protected $outOfGameCards = [];

    /**
     * @var array
     */
    protected $discardPile = [];

    /**
     * @var array
     */
    protected $protectedPlayers = [];

    /**
     * @var array
     */
    protected $outOfGamePlayers = [];

    /**
     * @var string
     */
    protected $winners = [];

    /**
     * @var array
     */
    protected $guardianEffect = [];


    /**
     * LoveLetter constructor.
     * @param StackProvider $stackProvider
     */
    public function __construct($stackProvider = null)
    {
        if ($stackProvider !== null) {
            $this->stackProvider = $stackProvider;
        } else {
            $this->stackProvider = new StackProvider();
        }
    }

    public function start($players)
    {
        $this->players = $players;
        $this->resetGame();
        $this->stack = $this->stackProvider->getStack();
        $this->gameStarted = true;
        /** @var Player $player */
        foreach ($this->players as $player) {
            $player->setGameState(new PlayerState());
            $gameState = new PlayerState();
            $gameState->addCard($this->drawCard());
            $player->setGameState($gameState);
        }
        $this->reserve[] = $this->drawCard();
        if ($this->players->count() === 2) {
            for ($i = 0; $i < 3; $i++) {
                $this->outOfGameCards[] = $this->drawCard();
            }
        }
        $this->status = 'Host wählt ersten Spieler...';
        $this->waitFor = self::WAIT_FOR_SELECT_FIRST_PLAYER;
        $this->updateState();
    }

    public function handleAction($action, $params = [])
    {
        switch ($action) {
            case self::WAIT_FOR_CHOOSE_CARD:
                $this->activateCardAction($params);
                break;
            case self::WAIT_FOR_CONFIRM_DISCARD_CARD:
                $this->discardActiveCardAction();
                break;
            case self::WAIT_FOR_CHOOSE_PLAYER:
                $this->handleEffectAction($params);
                break;
            case self::WAIT_FOR_CHOOSE_GUARDIAN_EFFECT_CARD:
                $this->handleEffectAction($params);
                break;
            case self::WAIT_FOR_FINISH_LOOKING_AT_CARD:
                $this->handleEffectAction();
                break;
            case self::WAIT_FOR_PLACE_MAID_CARD:
                $this->placeMaidCardAction();
                break;
            case self::WAIT_FOR_CHOOSE_ANY_PLAYER:
                $this->handleEffectAction($params);
                break;
            case self::WAIT_FOR_SELECT_FIRST_PLAYER:
                $this->setupFirstTurnAction($params);
        }
        $this->updateState();
    }

    public function updateState()
    {
        if (!$this->players) {
            return;
        }
        $playerinfo = $this->getPlayers();
        /** @var Player $player */
        foreach ($this->players as $player) {
            if (!$player->getClient()) {
                continue;
            }
            $msg = [
                'dataType' => 'game',
                'global' => $this->getGlobalState(),
                'local' => $player->getGameState()->getState()
            ];
            if ($playerinfo) {
                $msg['global']['players'] = $playerinfo;
            }
            $player->getClient()->send(json_encode($msg));
        }
        $this->players->rewind();
    }

    protected function setupFirstTurnAction($params)
    {
        $this->activePlayer = $this->getPlayerById($params['id']);
        $this->drawCardForActivePlayer();
        $this->waitFor = self::WAIT_FOR_CHOOSE_CARD;
        $this->status = "{$this->activePlayer->getName()} ist dran ...";
    }

    protected function activateCardAction($params)
    {
        $index = $params['index'];
        /** @var PlayerState $gameState */
        $gameState = $this->activePlayer->getGameState();
        $cards = $gameState->getCards();
        $this->transferCard($cards, $this->activeCard, $index);
        $gameState->setCards($cards);
        $this->handleEffectAction();
    }

    protected function discardActiveCardAction()
    {
        $this->discardPile[] = $this->activeCard;
        $this->activeCard = null;
        $this->nextTurn();
    }

    protected function placeMaidCardAction()
    {
        /** @var PlayerState $gameState */
        $gameState = $this->activePlayer->getGameState();
        $gameState->addOpenEffectCard($this->activeCard);
        $this->activeCard = null;
        $this->nextTurn();
    }

    public function getGlobalState()
    {
        $visibleDiscardedCard = [];
        $numDicardedCards = count($this->discardPile);
        if ($numDicardedCards > 0) {
            $visibleDiscardedCard = [$this->discardPile[$numDicardedCards - 1]];
        }
        $playerTurn = $this->activePlayer ? $this->activePlayer->getId() : '';
        return [
            'gameStarted' => $this->gameStarted,
            'gameFinished' => $this->gameFinished,
            'playerTurn' => $playerTurn,
            'waitFor' => $this->waitFor,
            'status' => $this->status,
            'activeCard' => $this->activeCard,
            'outOfGameCards' => $this->outOfGameCards,
            'discardPile' => $visibleDiscardedCard,
            'protectedPlayers' => $this->protectedPlayers,
            'outOfGamePlayers' => $this->outOfGamePlayers,
            'winners' => $this->winners,
            'guardianEffectSelectableCards' => $this->guardianEffect['selectableCards'],
            'guardianEffectChosenPlayer' => $this->guardianEffect['name'],
        ];
    }

    protected function gameIsFinished()
    {
        // TODO Check if stack is empty, if so player with highest card wins
        // If there cards have equal values all player with that value win the game
        if (count($this->outOfGamePlayers) === $this->players->count() - 1) {
            $this->gameFinished = true;
            $this->gameStarted = false;
            $victoriousPlayer = $this->getNextPlayer();
            $this->winners[] = $victoriousPlayer->getId();
            $this->status = $victoriousPlayer->getName() . " hat gewonnen!";
            $this->waitFor = 'startNewGame';
            return true;
        } else {
            return false;
        }
    }

    protected function nextTurn()
    {
        $this->gameIsFinished();
        $this->activePlayer = $this->getNextPlayer();
        if (in_array($this->activePlayer->getId(), $this->protectedPlayers)) {
            /** @var PlayerState $gameState */
            $gameState = $this->activePlayer->getGameState();
            $openEffectCards = $gameState->getOpenEffectCards();
            $this->transferCard($openEffectCards, $this->discardPile);
            $gameState->setOpenEffectCards($openEffectCards);
            $key = array_search($this->activePlayer->getId(), $this->protectedPlayers);
            array_splice($this->protectedPlayers, $key, 1);
        }
        $this->drawCardForActivePlayer();
        $this->waitFor = self::WAIT_FOR_CHOOSE_CARD;
        $this->status = "{$this->activePlayer->getName()} ist dran ...";
    }

    /**
     * @return mixed
     */
    public function getPlayers()
    {
        $players = [];
        foreach ($this->players as $player) {
            $players[] = [
                "id" => $player->getId(),
                "name" => $player->getName(),
            ];
        }
        $this->players->rewind();
        return $players;
    }

    protected function resetGame()
    {
        $this->stack = [];
        $this->reserve = [];
        $this->discardPile = [];
        $this->outOfGameCards = [];
        $this->activeCard = null;
        $this->guardianEffect = self::GUARDIAN_EFFECT_DEFAULT;
        $this->winners = [];
        $this->outOfGamePlayers = [];
        $this->gameFinished = false;
        $this->activePlayer = null;
        foreach ($this->players as $player) {
            if ($player->getGameState()) {
                $player->getGameState()->reset();
            }
        }
        $this->players->rewind();
    }

    protected function drawCardForActivePlayer()
    {
        /** @var PlayerState $gameState */
        $gameState = $this->activePlayer->getGameState();
        $gameState->addCard($this->drawCard());
    }

    protected function drawCard()
    {
        $card = array_pop($this->stack);
        if (is_null($card)) {
            return array_pop($this->reserve);
        } else {
            return $card;
        }
    }

    protected function transferCard(&$from, &$to, $index = 0)
    {
        if (is_null($to) || is_string(key($to))) {
            $to = array_splice($from, $index, 1)[0];
            return $to;
        } else {
            $to[] = array_splice($from, $index, 1)[0];
            return $to[count($to) - 1];
        }
    }

    /**
     * @return bool|Player
     */
    protected function getNextPlayer()
    {
        // First find the active player
        while (true) {
            if (!$this->players->valid()) {
                $this->players->rewind();
            }
            $player = $this->players->current();
            if ($player->getId() == $this->activePlayer->getId()) {
                $this->players->next();
                // Now find the next player who is not out of the game
                while (true) {
                    if (!$this->players->valid()) {
                        $this->players->rewind();
                    }
                    if (in_array($this->players->current()->getId(), $this->outOfGamePlayers)) {
                        $this->players->next();
                    } else {
                        /** @var Player $nextPlayer */
                        $nextPlayer = $this->players->current();
                        $this->players->rewind();
                        return $nextPlayer;
                    }
                }
            }
            $this->players->next();
        }
        return false;
    }


    /**
     * @param $id
     * @return bool|Player
     */
    protected function getPlayerById($id)
    {
        /** @var Player $player */
        foreach ($this->players as $player) {
            if ($player->getId() == $id) {
                $this->players->rewind();
                return $player;
            }
        }
        $this->players->rewind();
        return false;
    }

    protected function handleEffectAction($params = [])
    {
        switch ($this->activeCard['name']) {
            case 'Wächterin':
                $this->guardianEffect($params);
                break;
            case 'Priester':
                $this->priestEffect($params);
                break;
            case 'Baron':
                $this->baronEffect($params);
                break;
            case 'Zofe':
                $this->maidEffect();
                break;
            case 'Prinz':
                $this->princeEffect($params);
                break;
            case 'König':
                $this->kingEffect($params);
                break;
            case 'Gräfin':
                $this->countessEffect();
                break;
            case 'Prinzessin':
                $this->princessEffect();
                break;
            default:
                // do nothing
        }
    }

    /**
     * Errätst du die Handkarte eines Mitspielers, scheidet dieser aus ... Gilt nicht für "Wächterin"!
     * @param array $params
     */
    protected function guardianEffect($params)
    {
        // Initial invocation without parameters, let player select other player.
        if ($this->waitFor === self::WAIT_FOR_CHOOSE_CARD) {
            $this->status = $this->activePlayer->getName() . ' sucht Mitspieler für Karteneffekt "' . $this->activeCard['name'] . '" aus ...';
            $this->waitFor = self::WAIT_FOR_CHOOSE_PLAYER;
            return;
        }

        // Player has selected other player. Now let him guess a card.
        if ($this->waitFor === self::WAIT_FOR_CHOOSE_PLAYER) {
            $id = $params['id'];
            $chosenPlayer = $this->getPlayerById($id);
            $this->guardianEffect['id'] = $id;
            $this->guardianEffect['name'] = $chosenPlayer->getName();
            $this->waitFor = self::WAIT_FOR_CHOOSE_GUARDIAN_EFFECT_CARD;
            return;
        }

        // Check if player was right, then the other player is out.
        $chosenPlayer = $this->getPlayerById($this->guardianEffect['id']);
        $card = $params['card'];
        /** @var PlayerState $chosenPlayerState */
        $chosenPlayerState = $chosenPlayer->getGameState();
        if ($card === $chosenPlayerState->getCards()[0]['name']) {
            $this->outOfGamePlayers[] = $chosenPlayer->getId();
            $cards = $chosenPlayerState->getCards();
            $this->transferCard($cards, $this->discardPile, 0);
            $chosenPlayerState->setCards($cards);
            if ($this->gameIsFinished()) {
                return;
            } else {
                $this->status = $card . '! Richtig geraten! ' . $chosenPlayer->getName() . ' scheidet aus! ';
            }
        }
        $this->waitFor = self::WAIT_FOR_CONFIRM_DISCARD_CARD;
        $this->guardianEffect = self::GUARDIAN_EFFECT_DEFAULT;
        $this->status =
            $card . '! Falsch geraten! '
            . $this->activePlayer->getName() . ' muss seine Karte auf den Ablagestapel legen ...';
    }

    /**
     * Schaue dir die Handkarte eines Mitspielers an.
     * @param array $params
     */
    protected function priestEffect($params)
    {
        static $enemyName = null;

        if ($this->waitFor === self::WAIT_FOR_CHOOSE_CARD) {
            $this->status = $this->activePlayer->getName() . ' sucht Mitspieler für Karteneffekt "' . $this->activeCard['name'] . '" aus ...';
            $this->waitFor = self::WAIT_FOR_CHOOSE_PLAYER;
            return;
        }

        if ($this->waitFor === self::WAIT_FOR_CHOOSE_PLAYER) {
            $playerId = $params['id'];
            $selectedPlayer = $this->getPlayerById($playerId);
            $enemyName = $selectedPlayer->getName();
            /** @var PlayerState $activePlayerGameState */
            $activePlayerGameState = $this->activePlayer->getGameState();
            /** @var PlayerState $selectedPlayerGameState */
            $selectedPlayerGameState = $selectedPlayer->getGameState();
            $activePlayerGameState->setPriestEffectVisibleCard($selectedPlayerGameState->getCards()[0]['name']);
            $this->waitFor = self::WAIT_FOR_FINISH_LOOKING_AT_CARD;
            $this->status = 'Merke dir diese Karte von ' . $enemyName . ' und drücke auf ok!';
            return;
        }

        $this->waitFor = self::WAIT_FOR_CONFIRM_DISCARD_CARD;
        $this->status = $this->activePlayer->getName() . ' muss seine Karte auf den Ablagestapel legen ...';
    }

    /**
     * Vergleiche deine Handkarte mit der eines Mitspielers. Der Spieler mit dem niedrigeren Wert scheidet aus ...
     * @param array $params
     */
    protected function baronEffect($params)
    {
        if ($this->waitFor === self::WAIT_FOR_CHOOSE_CARD) {
            $this->status = $this->activePlayer->getName() . ' sucht Mitspieler für Karteneffekt "'
                . $this->activeCard['name'] . '" aus ...';
            $this->waitFor = self::WAIT_FOR_CHOOSE_PLAYER;
            return;
        }

        $id = $params['id'];
        $enemy = $this->getPlayerById($id);
        /** @var PlayerState $activePlayerState */
        $activePlayerState = $this->activePlayer->getGameState();
        /** @var PlayerState $enemyPlayerState */
        $enemyPlayerState = $enemy->getGameState();
        switch ($activePlayerState->getCards()[0]['value'] <=> $enemyPlayerState->getCards()[0]['value']) {
            case 0:
                $this->status = 'Karten haben den gleichen Wert...keiner fliegt raus. ';
                break;
            case 1:
                $this->outOfGamePlayers[] = $enemy->getId();
                if ($this->gameIsFinished()) {
                    return;
                } else {
                    $this->status = 'Die Karte von ' . $this->activePlayer->getName() . ' war höher! ';
                }
                break;
            case -1:
                $this->outOfGamePlayers[] = $this->activePlayer->getId();
                if ($this->gameIsFinished()) {
                    return;
                } else {
                    $this->status = 'Die Karte von ' . $enemy->getName() . ' war höher! ';
                }
                break;
        }

        $this->waitFor = self::WAIT_FOR_CONFIRM_DISCARD_CARD;
        $this->status .= $this->activePlayer->getName() . ' muss seine Karte auf den Ablagestapel legen ...';
    }

    /**
     * Du bist bis zu deinem nächsten Zug geschützt.
     */
    protected function maidEffect()
    {
        $this->protectedPlayers[] = $this->activePlayer->getId();
        $this->waitFor = self::WAIT_FOR_PLACE_MAID_CARD;
        $this->status = $this->activePlayer->getName() . ' ist für eine Runde geschützt und muss seine Karte vor sich offen hinlegen ...';
    }

    /**
     * Wähle einen Spieler, der seine Handkarte ablegt und eine neue Karte zieht.
     * @param array $params
     */
    protected function princeEffect($params)
    {
        if ($this->waitFor === self::WAIT_FOR_CHOOSE_CARD) {
            $this->status = $this->activePlayer->getName() . ' sucht Spieler für Karteneffekt "' . $this->activeCard['name'] . '" aus ...';
            $this->waitFor = self::WAIT_FOR_CHOOSE_ANY_PLAYER;
            return;
        }

        $chosenPlayer = $this->getPlayerById($params['id']);
        /** @var PlayerState $gameState */
        $gameState = $chosenPlayer->getGameState();
        $cards = $gameState->getCards();
        // TODO Check if princess was discarded
        $card = $this->transferCard($cards, $this->discardPile);
        $gameState->setCards($cards);
        $gameState->addCard($this->drawCard());
        $this->waitFor = self::WAIT_FOR_CONFIRM_DISCARD_CARD;
        $this->status = 'Die Karte ' . $card['name'] . ' von ' . $chosenPlayer->getName() . ' wurde abgeworfen und eine neue Karte wurde gezogen. ';
        $this->status .= $this->activePlayer->getName() . ' muss seine Karte auf den Ablagestapel legen ...';
    }

    /**
     * Tausche deine Handkarte mit der eines Mitspielers
     * @param array $params
     */
    protected function kingEffect($params)
    {
        if (!$params) {
            $this->status = $this->activePlayer->getName() . ' sucht Mitspieler für Karteneffekt "' . $this->activeCard['name'] . '" aus ...';
            $this->waitFor = self::WAIT_FOR_CHOOSE_PLAYER;
            return;
        }
        // TODO Implement functionality
        $this->waitFor = self::WAIT_FOR_CONFIRM_DISCARD_CARD;
        $this->status = $this->activePlayer->getName() . ' muss seine Karte auf den Ablagestapel legen ...';
    }

    /**
     * Wenn du zusätzlich König oder Prinz auf der Hand hast, musst du die Gräfin ausspielen.
     */
    protected function countessEffect()
    {
        // TODO Must be implemented clientside
        $this->waitFor = self::WAIT_FOR_CONFIRM_DISCARD_CARD;
        $this->status = $this->activePlayer->getName() . ' muss seine Karte auf den Ablagestapel legen ...';
    }

    /**
     * Wenn du die Prinzessin ablegst, scheidest du aus ...
     */
    protected function princessEffect()
    {
        // You are out
        // TODO Implement functionality
        $this->waitFor = self::WAIT_FOR_CONFIRM_DISCARD_CARD;
        $this->status = $this->activePlayer->getName() . ' muss seine Karte auf den Ablagestapel legen ...';
    }

}
