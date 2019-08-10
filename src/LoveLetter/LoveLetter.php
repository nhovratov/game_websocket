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
    const CHOOSE_CARD = 'chooseCard';
    const CHOOSE_PLAYER = 'choosePlayer';
    const CHOOSE_ANY_PLAYER = 'chooseAnyPlayer';
    const CHOOSE_GUARDIAN_EFFECT_CARD = 'chooseGuardianEffectCard';
    const CONFIRM_DISCARD_CARD = 'confirmDiscardCard';
    const FINISH_LOOKING_AT_CARD = 'finishLookingAtCard';
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
    }

    public function start($players)
    {
        // Game needs at least 2 players
        if (!self::isGameReady($players)) {
            return;
        }
        $this->players = $players;
        $this->resetGame();
        $this->stack = $this->stackProvider->getStack();
        $this->gameStarted = true;
        /** @var Player $player */
        foreach ($this->players as $player) {
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
        $this->activePlayer = $this->getHost();
        $this->status = 'Host wählt ersten Spieler...';
        $this->waitFor = self::SELECT_FIRST_PLAYER;
        $this->updateState();
    }

    public function handleAction($params = [])
    {
        // No actions allowed yet
        if (!$this->waitFor) {
            return;
        }

        // Some player tried to hijack the current players turn.
        if (!$this->activePlayer->isUserIdentifier($params['uid'])) {
            echo "Player {$params['uid']} tried to hijack this turn. This will be reported.\n";
            return;
        }

        switch ($this->getAllowedActionByPlayer($this->activePlayer)) {
            case self::CHOOSE_PLAYER:
            case self::CHOOSE_GUARDIAN_EFFECT_CARD:
            case self::FINISH_LOOKING_AT_CARD:
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
        }
        $this->updateState();
    }

    public function updateState()
    {
        if (!$this->players) {
            return;
        }
        $playerinfo = $this->getPlayerInfo();
        /** @var Player $player */
        foreach ($this->players as $player) {
            if (!$player->getClient()) {
                continue;
            }
            $gameState = $player->getGameState();
            $gameState->setAllowedAction($this->getAllowedActionByPlayer($player));
            $msg = [
                'dataType' => 'game',
                'global' => $this->getGlobalState(),
                'local' => $gameState->getState()
            ];
            if ($playerinfo) {
                $msg['global']['players'] = $playerinfo;
            }
            $player->getClient()->send(json_encode($msg));
        }
    }

    public function getGlobalState()
    {
        $visibleDiscardedCard = [];
        $numDicardedCards = count($this->discardPile);
        if ($numDicardedCards > 0) {
            $visibleDiscardedCard = [$this->discardPile[$numDicardedCards - 1]];
        }
        $playerTurn = $this->activePlayer ? $this->getActivePlayerId() : '';
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

    public function drawCard($useReserve = false)
    {
        $card = array_pop($this->stack);
        if (is_null($card)) {
            if ($useReserve) {
                return array_pop($this->reserve);
            } else {
                return false;
            }
        }
        return $card;
    }

    public function discardCard(&$cards)
    {
        return $this->transferCard($cards, $this->discardPile);
    }

    /**
     * @param \SplObjectStorage $players
     * @return bool
     */
    public static function isGameReady(\SplObjectStorage $players)
    {
        return $players->count() >= 2;
    }

    /**
     * @param Player $player
     * @return string
     */
    protected function getAllowedActionByPlayer(Player $player)
    {
        if (!$this->gameStarted) {
            return '';
        }

        switch ($this->waitFor) {
            case self::CHOOSE_PLAYER:
                if (!$this->isPlayerTurn($player)) {
                    return '';
                }
                return $this->isAnyOtherPlayerSelectable() ? $this->waitFor : self::CONFIRM_DISCARD_CARD;
                break;

            case self::SELECT_FIRST_PLAYER:
                return $player->isHost() ? $this->waitFor : '';
                break;

            default:
                return $this->isPlayerTurn($player) ? $this->waitFor : '';
        }
    }

    protected function resetGame()
    {
        $this->stackProvider->setCounter(1);
        $this->stack = [];
        $this->reserve = [];
        $this->discardPile = [];
        $this->protectedPlayers = [];
        $this->outOfGameCards = [];
        $this->activeCard = null;
        $this->resetGuardianEffect();
        $this->winners = [];
        $this->outOfGamePlayers = [];
        $this->gameFinished = false;
        $this->activePlayer = null;
        foreach ($this->players as $player) {
            if ($player->getGameState()) {
                $player->getGameState()->reset();
            }
        }
    }

    protected function nextTurn()
    {
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
        $this->waitFor = self::CHOOSE_CARD;
        $this->status = "{$this->activePlayer->getName()} ist dran ...";
    }

    protected function drawCardForActivePlayer()
    {
        /** @var PlayerState $gameState */
        $gameState = $this->activePlayer->getGameState();
        $gameState->addCard($this->drawCard());
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

    protected function finishGame()
    {
        $this->gameFinished = true;
        $this->gameStarted = false;
        $this->waitFor = self::START_NEW_GAME;
    }

    protected function setupFirstTurnAction($params)
    {
        $this->activePlayer = $this->getPlayerById($params['id']);
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
        /** @var PlayerState $gameState */
        $gameState = $this->activePlayer->getGameState();
        $cards = $gameState->getCards();
        if (!Validator::validateCardCanBeSet($cards, $key)) {
            return false;
        }
        $this->activeCard = $cards[$key];
        $index = array_search($key, array_keys($cards));
        array_slice($cards, $index, 1);
        unset($cards[$key]);
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

    protected function handleEffectAction($params = [])
    {
        $cardClass = self::CARDS[$this->activeCard['id']];
        $cardClass::activate($this, $params);
        // After each end of effect, we check if the game is finished by now
        $this->isGameFinished();
    }

    protected function isGameFinished()
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
            $this->finishGame();
            $victoriousPlayer = $this->getNextPlayer();
            $this->winners[] = $victoriousPlayer->getId();
            $this->status = $victoriousPlayer->getName() . " hat gewonnen!";
            return true;
        }

        // If there are no cards left at the end of someones turn, players with the highest card win.
        if (count($this->stack) === 0) {
            $this->finishGame();
            $highestValue = 0;
            /** @var Player $player */
            foreach ($this->players as $player) {
                /** @var PlayerState $state */
                $state = $player->getGameState();
                $card = current($state->getCards());
                $currentValue = $card['value'];
                if ($currentValue > $highestValue) {
                    $highestValue = $currentValue;
                }
            }
            foreach ($this->players as $player) {
                /** @var PlayerState $state */
                $state = $player->getGameState();
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

    /**
     * @param Player $player
     * @return bool
     */
    protected function isPlayerSelectable($player)
    {
        return !in_array($player->getId(), $this->outOfGamePlayers)
            && !in_array($player->getId(), $this->protectedPlayers);
    }

    protected function isAnyOtherPlayerSelectable()
    {
        /** @var Player $player */
        $players = clone $this->players;
        foreach ($players as $player) {
            if ($this->isPlayerSelectable($player) && $player->getId() !== $this->getActivePlayerId()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param Player $player
     * @return bool
     */
    protected function isPlayerTurn(Player $player)
    {
        return $player->getId() === $this->getActivePlayerId();
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
                        /** @var Player $nextPlayer */
                        $nextPlayer = $this->players->current();
                        return $nextPlayer;
                    }
                }
            }
            $this->players->next();
        }
        return false;
    }

    /**
     * Used for brief overview of ingame players
     * // TODO Extend with more states like proteced, outOfGame...
     * @return mixed
     */
    public function getPlayerInfo()
    {
        $players = [];
        $clonedPlayers = clone $this->players;
        foreach ($clonedPlayers as $player) {
            $players[] = [
                "id" => $player->getId(),
                "name" => $player->getName(),
            ];
        }
        return $players;
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
     * @param array $cards
     * @return array
     */
    public function getHandCard(array $cards)
    {
        return array_slice($cards, 0, 1)[0];
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
    public function getActivePlayerGameState(): StateInterface
    {
        return $this->activePlayer->getGameState();
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

    /**
     * @param int $id
     */
    public function addProtectedPlayer(int $id)
    {
        $this->protectedPlayers[] = $id;
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

    /**
     * @return Player
     */
    public function getHost(): ?Player
    {
        /** @var Player $player */
        foreach ($this->players as $player) {
            if ($player->isHost()) {
                return $player;
            }
        }
        return null;
    }
}
