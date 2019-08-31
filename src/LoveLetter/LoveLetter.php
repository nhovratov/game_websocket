<?php
/**
 * Created by PhpStorm.
 * User: NIKITA
 * Date: 12.05.2018
 * Time: 21:35
 */

namespace NH\LoveLetter;

use NH\GameInterface;
use NH\LoveLetter\Card\Baron;
use NH\LoveLetter\Card\Countess;
use NH\LoveLetter\Card\Guardian;
use NH\LoveLetter\Card\King;
use NH\LoveLetter\Card\Maid;
use NH\LoveLetter\Card\Priest;
use NH\LoveLetter\Card\Prince;
use NH\LoveLetter\Card\Princess;
use NH\Player;
use NH\StateInterface;

class LoveLetter implements GameInterface
{
    const GUARDIANCOUNT = 5;
    const PRIESTCOUNT = 2;
    const BARONCOUNT = 2;
    const MAIDCOUNT = 2;
    const PRINCECOUNT = 2;
    const KINGCOUNT = 1;
    const COUNTESSCOUNT = 1;
    const PRINCESSCOUNT = 1;

    const START_NEW_GAME = 'startNewGame';
    const SELECT_FIRST_PLAYER = 'selectFirstPlayer';
    const START_NEW_ROUND = 'startNewRound';
    const CHOOSE_CARD = 'chooseCard';
    const CHOOSE_PLAYER = 'choosePlayer';
    const CHOOSE_ANY_PLAYER = 'chooseAnyPlayer';
    const CHOOSE_GUARDIAN_EFFECT_CARD = 'chooseGuardianEffectCard';
    const CONFIRM_DISCARD_CARD = 'confirmDiscardCard';
    const PLACE_MAID_CARD = 'placeMaidCard';

    const CARDS = [
        1 => Guardian::class,
        2 => Baron::class,
        3 => Priest::class,
        4 => Maid::class,
        5 => Prince::class,
        6 => King::class,
        7 => Countess::class,
        8 => Princess::class
    ];

    const PLAYER_WINS = [
        2 => 5,
        3 => 4,
        4 => 3
    ];

    const GUARDIAN_EFFECT_DEFAULT = [
        'id' => 0,
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
    protected $protectedPlayers = [];

    /**
     * @var array
     */
    protected $outOfGamePlayers = [];

    /**
     * @var array
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
        $this->stackProvider = $stackProvider ?? new StackProvider();
        $this->waitFor = self::START_NEW_GAME;
    }

    public function handleAction($params = [])
    {
        if (key_exists('players', $params)) {
            $players = $params['players'];
            // Start new game with new players
            if ($this->waitFor == self::START_NEW_GAME) {
                if (count($players) < 2) {
                    return;
                }
                $this->players = $players;
                foreach ($this->players as $player) {
                    if ($player->isHost()) {
                        $this->activePlayer = $player;
                    }
                }
            }
        }

        // Game has not started yet
        if (!$this->activePlayer) {
            return;
        }

        // This player has not the turn
        if (!$this->activePlayer->isUserIdentifier($params['uid'])) {
            return;
        }

        switch ($this->getAllowedActionByPlayer($this->activePlayer)) {
            case self::CHOOSE_PLAYER:
            case self::CHOOSE_GUARDIAN_EFFECT_CARD:
            case self::CHOOSE_ANY_PLAYER:
                $this->handleEffectAction($params);
                break;
            case self::CHOOSE_CARD:
                $this->activateCardAction($params);
                break;
            case self::CONFIRM_DISCARD_CARD:
                $this->discardActiveCardAction();
                break;
            case self::PLACE_MAID_CARD:
                $this->placeMaidCardAction();
                break;
            case self::SELECT_FIRST_PLAYER:
                $this->setupFirstTurnAction($params);
                break;
            case self::START_NEW_ROUND:
                $this->startAction();
                $this->setupFirstTurnAction();
                break;
            case self::START_NEW_GAME:
                $this->startAction();
                break;
        }

        // After each end of effect, we check if the game is finished by now
        if ($this->isRoundFinished()) {
            foreach ($this->winners as $winner) {
                $player = $this->getPlayerById($winner);
                $wins = $player->getPlayerState()->addWin();
                /** TODO Handle multiple winners */
                if ($wins == self::PLAYER_WINS[count($this->players)]) {
                    $this->gameFinished = true;
                }
            }
            // Prepare new game if someone has enough wins, else start new round
            if ($this->gameFinished) {
                $this->gameStarted = false;
                $this->waitFor = self::START_NEW_GAME;
            } else {
                // TODO Handle multiple winners
                $this->activePlayer = $this->getPlayerById($this->winners[0]);
                $this->waitFor = self::START_NEW_ROUND;
            }
        }

        $this->updateState();
    }

    public function updateState()
    {
        if (!$this->players) {
            return;
        }

        /** @var Player $player */
        $playerInfo = [];
        foreach ($this->players as $player) {
            $playerInfo[] = [
                'id' => $player->getId(),
                'name' => $player->getName(),
                'discardPile' => $player->getPlayerState()->getDiscardPile(),
                'wins' => $player->getPlayerState()->getWins()
            ];
        }

        foreach ($this->players as $player) {
            if (!$player->getClient()) {
                continue;
            }
            $state = $player->getPlayerState();
            $state->setAllowedAction($this->getAllowedActionByPlayer($player));
            $msg = [
                'dataType' => 'game',
                'local' => $state->getState(),
                'global' => [
                    'gameStarted' => $this->gameStarted,
                    'gameFinished' => $this->gameFinished,
                    'playerTurn' => $this->activePlayer ? $this->getActivePlayerId() : '',
                    'waitFor' => $this->waitFor,
                    'status' => $this->status,
                    'activeCard' => $this->activeCard,
                    'outOfGameCards' => $this->outOfGameCards,
                    'protectedPlayers' => $this->protectedPlayers,
                    'outOfGamePlayers' => $this->outOfGamePlayers,
                    'winners' => $this->winners,
                    'guardianEffectSelectableCards' => $this->guardianEffect['selectableCards'],
                    'guardianEffectChosenPlayer' => $this->guardianEffect['name'],
                ]
            ];
            if ($playerInfo) {
                $msg['global']['players'] = $playerInfo;
            }
            $player->getClient()->send(json_encode($msg));
        }
    }

    /**
     * @param Player $player
     * @return string
     */
    protected function getAllowedActionByPlayer(Player $player)
    {
        if (!$this->gameStarted && $player->isHost()) {
            return self::START_NEW_GAME;
        }

        $isPlayerTurn = $player->getId() === $this->getActivePlayerId();
        switch ($this->waitFor) {
            case self::CHOOSE_PLAYER:
                if (!$isPlayerTurn) {
                    return '';
                }
                return $this->isAnyOtherPlayerSelectable() ? $this->waitFor : self::CONFIRM_DISCARD_CARD;
                break;

            case self::SELECT_FIRST_PLAYER:
                return $player->isHost() ? $this->waitFor : '';
                break;

            default:
                return $isPlayerTurn ? $this->waitFor : '';
        }
    }

    protected function nextTurn()
    {
        $this->activePlayer = $this->getNextPlayer();
        foreach ($this->players as $player) {
            $player->getPlayerState()->resetEffectVisibleCard();
        }
        // Remove protected status from active player
        if (in_array($this->activePlayer->getId(), $this->protectedPlayers)) {
            /** @var PlayerState $state */
            $state = $this->activePlayer->getPlayerState();
            $state->discardOpenEffectCard();
            $key = array_search($this->activePlayer->getId(), $this->protectedPlayers);
            array_splice($this->protectedPlayers, $key, 1);
        }
        $this->drawCardForActivePlayer();
        $this->waitFor = self::CHOOSE_CARD;
        $this->status = "{$this->activePlayer->getName()} ist dran ...";
    }

    protected function drawCardForActivePlayer()
    {
        /** @var PlayerState $state */
        $state = $this->activePlayer->getPlayerState();
        $state->addCard($this->drawCard());
    }

    protected function startAction()
    {
        // Brand new game started, else new round
        if ($brandNewGame = !$this->gameStarted) {
            $this->status = 'Host wählt ersten Spieler...';
            $this->waitFor = self::SELECT_FIRST_PLAYER;
        }

        // Reset state
        $this->gameStarted = true;
        $this->gameFinished = false;
        $this->stack = $this->stackProvider->getStack();
        $this->protectedPlayers = [];
        $this->outOfGameCards = [];
        $this->activeCard = null;
        $this->resetGuardianEffect();
        $this->winners = [];
        $this->outOfGamePlayers = [];

        // Draw cards
        foreach ($this->players as $player) {
            if (!$state = $player->getPlayerState()) {
                $state = new PlayerState();
                $player->setPlayerState($state);
            } else {
                $state->reset();
                if ($brandNewGame) {
                    $state->resetWins();
                }
            }
            $state->addCard($this->drawCard());
        }
        $this->reserve[0] = $this->drawCard();
        if ($this->players->count() === 2) {
            for ($i = 0; $i < 3; $i++) {
                $this->outOfGameCards[] = $this->drawCard();
            }
        }
    }

    protected function setupFirstTurnAction($params = [])
    {
        if ($this->waitFor == self::SELECT_FIRST_PLAYER) {
            if (!key_exists('id', $params)) {
                return;
            }
            $id = (int)$params['id'];
            $player = $this->getPlayerById($id);
            if (!$player) {
                return;
            }
            $this->activePlayer = $player;
        }

        $this->drawCardForActivePlayer();
        $this->waitFor = self::CHOOSE_CARD;
        $this->status = "{$this->activePlayer->getName()} ist dran ...";
    }

    /**
     * Expected command: [key => 13]
     *
     * @param $params
     * @return bool
     */
    protected function activateCardAction($params)
    {
        if (!key_exists('key', $params)) {
            return false;
        }
        $key = $params['key'];
        /** @var PlayerState $state */
        $state = $this->activePlayer->getPlayerState();
        $cards = $state->getCards();
        if (!Validator::validateCardCanBeSet($cards, $key)) {
            return false;
        }
        $this->activeCard = $cards[$key];
        $index = array_search($key, array_keys($cards));
        array_slice($cards, $index, 1);
        unset($cards[$key]);
        $state->setCards($cards);
        $this->handleEffectAction();
        return true;
    }

    protected function discardActiveCardAction()
    {
        $this->activePlayer->getPlayerState()->addToDicardPile($this->activeCard);
        $this->activeCard = null;
        $this->nextTurn();
    }

    protected function placeMaidCardAction()
    {
        /** @var PlayerState $state */
        $state = $this->activePlayer->getPlayerState();
        $state->addOpenEffectCard($this->activeCard);
        $this->activeCard = null;
        $this->nextTurn();
    }

    protected function handleEffectAction($params = [])
    {
        $cardClass = self::CARDS[$this->activeCard['id']];
        $cardClass::activate($this, $params);
    }

    protected function isRoundFinished()
    {
        // The only states, where the game can be determined as finished
        if (!in_array($this->waitFor, [self::CHOOSE_PLAYER, self::CONFIRM_DISCARD_CARD])) {
            return false;
        }

        if ($this->waitFor === self::CHOOSE_PLAYER && $this->isAnyOtherPlayerSelectable()) {
            return false;
        }

        // If there is only one player left, he has won.
        if (count($this->outOfGamePlayers) === $this->players->count() - 1) {
            $victoriousPlayer = $this->getNextPlayer();
            $this->winners[] = $victoriousPlayer->getId();
            $this->status = $victoriousPlayer->getName() . " hat gewonnen!";
            return true;
        }

        // If there are no cards left at the end of someones turn, players with the highest card win.
        if (count($this->stack) === 0) {
            $highestValue = 0;
            /** @var Player $player */
            foreach ($this->players as $player) {
                /** @var PlayerState $state */
                $state = $player->getPlayerState();
                $card = current($state->getCards());
                $currentValue = $card['value'];
                if ($currentValue > $highestValue) {
                    $highestValue = $currentValue;
                }
            }
            foreach ($this->players as $player) {
                /** @var PlayerState $state */
                $state = $player->getPlayerState();
                $card = current($state->getCards());
                if ($card['value'] === $highestValue) {
                    $this->winners[] = $player->getId();
                } else {
                    $this->outOfGamePlayers[] = $player->getId();
                }
            }
            $victoriousPlayer = [];
            foreach ($this->winners as $winner) {
                $victoriousPlayer[] = $this->getPlayerById($winner)->getName();
            }
            $this->status = 'Gewinner: ' . implode(', ', $victoriousPlayer);
            return true;
        }
        return false;
    }

    protected function isAnyOtherPlayerSelectable()
    {
        /** @var Player $player */
        $players = clone $this->players;
        foreach ($players as $player) {
            $isSelectable =
                   !in_array($player->getId(), $this->outOfGamePlayers)
                && !in_array($player->getId(), $this->protectedPlayers);

            if ($isSelectable && $player->getId() !== $this->getActivePlayerId()) {
                return true;
            }
        }
        return false;
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
            if ($player->getId() === $this->activePlayer->getId()) {
                $this->players->next();
                // Now find the next player who is not out of the game
                while (true) {
                    if (!$this->players->valid()) {
                        $this->players->rewind();
                    }
                    if (in_array($this->players->current()->getId(), $this->outOfGamePlayers)) {
                        $this->players->next();
                    } else {
                        return $this->players->current();
                    }
                }
            }
            $this->players->next();
        }
        return false;
    }

    public function drawCard()
    {
        if (count($this->stack) > 0) {
            return array_pop($this->stack);
        } else {
            return array_pop($this->reserve);
        }
    }

    /**
     * @param int $id
     * @return bool|Player
     */
    public function getPlayerById(int $id)
    {
        /** @var Player $player */
        foreach ($this->players as $player) {
            if ($player->getId() === $id) {
                $this->players->rewind();
                return $player;
            }
        }
        return false;
    }

    /**
     * @return int
     */
    public function getActivePlayerId(): int
    {
        return $this->activePlayer->getId();
    }

    /**
     * @return string
     */
    public function getActivePlayerName(): string
    {
        return $this->activePlayer->getName();
    }

    /**
     * @return StateInterface
     */
    public function getActivePlayerState(): StateInterface
    {
        return $this->activePlayer->getPlayerState();
    }

    /**
     * @return string
     */
    public function getActiveCardName(): string
    {
        return $this->activeCard['name'];
    }

    /**
     * @param int $id
     */
    public function addOutOfGamePlayer(int $id)
    {
        $this->outOfGamePlayers[] = $id;
    }

    public function addProtectedPlayer()
    {
        $this->protectedPlayers[] = $this->getActivePlayerId();
    }

    /**
     * @return string
     */
    public function getWaitFor(): string
    {
        return $this->waitFor;
    }

    /**
     * // TODO Allow only predefined constants
     * @param string $str
     */
    public function setWaitFor(string $str)
    {
        $this->waitFor = $str;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus($str)
    {
        $this->status = $str;
    }

    /**
     * //TODO Move to guardian class
     * @return array
     */
    public function getGuardianEffect(): array
    {
        return $this->guardianEffect;
    }

    /**
     * @void
     */
    public function resetGuardianEffect()
    {
        $this->guardianEffect = self::GUARDIAN_EFFECT_DEFAULT;
    }

    /**
     * @param int $id
     */
    public function setGuardianEffectId(int $id)
    {
        $this->guardianEffect['id'] = $id;
    }

    /**
     * @param string $name
     */
    public function setGuardianEffectName(string $name)
    {
        $this->guardianEffect['name'] = $name;
    }
}
