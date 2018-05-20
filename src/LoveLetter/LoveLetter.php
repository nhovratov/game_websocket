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

    const WAIT_FOR_FIRST_PLAYER_SELECT = 'firstPlayerSelect';
    const WAIT_FOR_CHOOSE_CARD = 'chooseCard';
    const WAIT_FOR_CHOOSE_PLAYER = 'choosePlayer';
    const WAIT_FOR_CHOOSE_GUARDIAN_EFFECT_CARD = 'chooseGuardianEffectCard';
    const WAIT_FOR_DISCARD_CARD = 'discardCard';
    const WAIT_FOR_START_NEW_GAME = 'startNewGame';

    /**
     * @var array
     */
    protected $players = [];

    /**
     * @var StackProvider
     */
    protected $stackProvider = null;

    /**
     * @var bool
     */
    protected $gameStarted = false;

    /**
     * @var bool
     */
    protected $gameFinished = false;

    /**
     * @var array
     */
    protected $stack = [];

    /**
     * @var array
     */
    protected $reserve = [];

    /**
     * @var array
     */
    protected $openCards = [];

    /**
     * @var array
     */
    protected $discardPile = [];

    /**
     * @var array
     */
    protected $outOfGameCards = [];

    /**
     * @var int
     */
    protected $playerTurn;

    /**
     * @var string
     */
    protected $waitFor = '';

    /**
     * @var array
     */
    protected $outOfGamePlayers = [];


    /**
     * @var string
     */
    protected $winner = '';

    /**
     * @var array
     */
    protected $guardianEffectDefault = [
        'step' => 1,
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

    /**
     * @var array
     */
    protected $guardianEffect = [];

    /**
     * @var string
     */
    protected $status = '';

    /**
     * @var array
     */
    protected $activeCard = [];

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
            $currentState = $player->getGameState();
            $currentState['cards'][] = $this->drawCard();
            $player->setGameState($currentState);
        }
        $this->reserve[] = $this->drawCard();
        if ($this->players->count() === 2) {
            for ($i = 0; $i < 3; $i++) {
                $this->outOfGameCards[] = $this->drawCard();
            }
        }
        $this->status = 'Host wählt ersten Spieler...';
        $this->waitFor = self::WAIT_FOR_FIRST_PLAYER_SELECT;
        $this->updateState();
    }

    public function handleAction($action, $params = [])
    {
        switch ($action) {
            case 'selectFirstPlayer':
                $this->selectFirstPlayer($params['id']);
                break;
            case 'chooseCard':
                $this->chooseCard($params);
                break;
            case 'selectPlayerForEffect':
                $this->handleEffect($this->activeCard, $params);
                $this->updateState();
                break;
            case 'discardActiveCard':
                $this->discardActiveCard();
                break;
            case 'selectGuardianEffectCard':
                $this->handleEffect($this->activeCard, $params);
                $this->updateState();
                break;
            default:
        }
    }

    protected function resetGame()
    {
        $this->stack = [];
        $this->reserve = [];
        $this->discardPile = [];
        $this->outOfGameCards = [];
        $this->activeCard = [];
        $this->openCards = [];
        $this->guardianEffect = $this->guardianEffectDefault;
        $this->winner = '';
        $this->outOfGamePlayers = [];
        $this->gameFinished = false;
        foreach ($this->players as $player) {
            $player->setGameState([]);
        }
        $this->players->rewind();
        $this->updateState();
    }

    public function getGlobalState()
    {
        return [
            'gameStarted' => $this->gameStarted,
            'gameFinished' => $this->gameFinished,
            'winner' => $this->winner,
            'playerTurn' => $this->playerTurn,
            'waitFor' => $this->waitFor,
            'outOfGameCards' => $this->outOfGameCards,
            'discardPile' => $this->discardPile,
            'reserve' => $this->reserve,
            'openCards' => $this->openCards,
            'activeCard' => $this->activeCard,
            'guardianEffectSelectableCards' => $this->guardianEffect['selectableCards'],
            'guardianEffectChosenPlayer' => $this->guardianEffect['name'],
            'status' => $this->status,
            'outOfGamePlayers' => $this->outOfGamePlayers
        ];
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
                'local' => $player->getGameState()
            ];
            if ($playerinfo) {
                $msg['global']['players'] = $playerinfo;
            }
            $player->getClient()->send(json_encode($msg));
        }
        $this->players->rewind();
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

    protected function drawCard()
    {
        return array_pop($this->stack);
    }

    protected function selectFirstPlayer($id)
    {
        $player = $this->getPlayerById($id);
        $this->startNewTurn($player);
        $this->waitFor = self::WAIT_FOR_CHOOSE_CARD;
        $this->updateState();
    }

    protected function chooseCard($params)
    {
        $index = $params['index'];
        $player = $this->getActivePlayer();
        $state = $player->getGameState();
        $this->activeCard = array_splice($state['cards'], $index, 1)[0];
        $this->openCards[] = $this->activeCard;
        $this->activeCard['effectFinished'] = false;
        $this->handleEffect($this->activeCard);
        $player->setGameState($state);
        $this->updateState();
    }

    protected function discardActiveCard()
    {
        $this->discardPile[] = array_splice($this->openCards, 0, 1)[0];
        $activePlayer = $this->getNextPlayer();
        $this->activeCard = [];
        $this->startNewTurn($activePlayer);
        $this->updateState();
    }

    protected function startNewTurn($player)
    {
        $this->playerTurn = $player->getId();
        $this->drawCardForActivePlayer();
        $this->waitFor = self::WAIT_FOR_CHOOSE_CARD;
        $this->status = "{$player->getName()} ist dran ...";
    }

    protected function drawCardForActivePlayer()
    {
        $player = $this->getActivePlayer();
        $state = $player->getGameState();
        $state['cards'][] = $this->drawCard();
        $player->setGameState($state);
    }

    protected function getActivePlayer()
    {
        return $this->getPlayerById($this->playerTurn);
    }

    protected function getNextPlayer()
    {
        // First find the active player
        while (true) {
            if (!$this->players->valid()) {
                $this->players->rewind();
            }
            $player = $this->players->current();
            if ($player->getId() == $this->playerTurn) {
                $this->players->next();
                // Now find the next player who is not out of the game
                while (true) {
                    if (!$this->players->valid()) {
                        $this->players->rewind();
                    }
                    if (in_array($this->players->current()->getId(), $this->outOfGamePlayers)) {
                        $this->players->next();
                    } else {
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


    protected function getPlayerById($id)
    {
        foreach ($this->players as $player) {
            if ($player->getId() == $id) {
                $this->players->rewind();
                return $player;
            }
        }
        $this->players->rewind();
        return false;
    }

    protected function checkIfGameIsFinished()
    {
        if (count($this->outOfGamePlayers) === $this->players->count() - 1) {
            $this->gameFinished = true;
            $this->gameStarted = false;
            $victoriousPlayer = $this->getNextPlayer();
            $this->winner = $victoriousPlayer->getId();
            $this->status = $victoriousPlayer->getName() . " hat gewonnen!";
            $this->waitFor = 'startNewGame';
            $this->updateState();
            return true;
        } else {
            return false;
        }
    }

    protected function handleEffect($card, $params = [])
    {
        switch ($card['name']) {
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
        if ($this->guardianEffect['step'] === 1) {
            $this->status = $this->getActivePlayer()->getName() . ' sucht Mitspieler für Karteneffekt "' . $this->activeCard['name'] . '" aus ...';
            $this->guardianEffect['step'] = 2;
            $this->waitFor = self::WAIT_FOR_CHOOSE_PLAYER;
            return;
        }

        // Player has selected other player. Now let him guess a card.
        if ($this->guardianEffect['step'] === 2) {
            $id = $params['id'];
            $chosenPlayer = $this->getPlayerById($id);
            $this->guardianEffect['id'] = $id;
            $this->guardianEffect['name'] = $chosenPlayer->getName();
            $this->guardianEffect['step'] = 3;
            $this->waitFor = self::WAIT_FOR_CHOOSE_GUARDIAN_EFFECT_CARD;
            return;
        }

        // Check if player was right, then the other player is out.
        $chosenPlayer = $this->getPlayerById($this->guardianEffect['id']);
        $card = $params['card'];
        $chosenPlayerState = $chosenPlayer->getGameState();
        if ($card === $chosenPlayerState['cards'][0]['name']) {
            $this->outOfGamePlayers[] = $chosenPlayer->getId();
            $this->discardPile[] = array_splice($chosenPlayerState['cards'], 0, 1)[0];
            $chosenPlayer->setGameState($chosenPlayerState);
            $this->status = $card . '! Richtig geraten! ' . $chosenPlayer->getName() . ' scheidet aus! ';
            if ($this->checkIfGameIsFinished()) {
                return;
            }
        } else {
            $this->status = $card . '! Falsch geraten! ';
        }
        $this->waitFor = self::WAIT_FOR_DISCARD_CARD;
        $this->guardianEffect = $this->guardianEffectDefault;
        $this->status .= $this->getActivePlayer()->getName() . ' muss seine Karte auf den Ablagestapel legen ...';
    }

    /**
     * Schaue dir die Handkarte eines Mitspielers an.
     * @param array $params
     */
    protected function priestEffect($params)
    {
        if (!$params) {
            $this->status = $this->getActivePlayer()->getName() . ' sucht Mitspieler für Karteneffekt "' . $this->activeCard['name'] . '" aus ...';
            $this->waitFor = self::WAIT_FOR_CHOOSE_PLAYER;
            return;
        }
        // TODO Implement functionality
        $this->waitFor = self::WAIT_FOR_DISCARD_CARD;
        $this->status = $this->getActivePlayer()->getName() . ' muss seine Karte auf den Ablagestapel legen ...';
    }

    /**
     * Vergleiche deine Handkarte mit der eines Mitspielers. Der Spieler mit dem niedrigeren Wert scheidet aus ...
     * @param array $params
     */
    protected function baronEffect($params)
    {
        if (!$params) {
            $this->status = $this->getActivePlayer()->getName() . ' sucht Mitspieler für Karteneffekt "' . $this->activeCard['name'] . '" aus ...';
            $this->waitFor = self::WAIT_FOR_CHOOSE_PLAYER;
            return;
        }
        // TODO Implement functionality
        $this->waitFor = self::WAIT_FOR_DISCARD_CARD;
        $this->status = $this->getActivePlayer()->getName() . ' muss seine Karte auf den Ablagestapel legen ...';
    }

    /**
     * Du bist bis zu deinem nächsten Zug geschützt.
     */
    protected function maidEffect()
    {
        // You are protected
        // TODO Implement functionality
        $this->waitFor = self::WAIT_FOR_DISCARD_CARD;
        $this->status = $this->getActivePlayer()->getName() . ' muss seine Karte auf den Ablagestapel legen ...';
    }

    /**
     * Wähle einen Spieler, der seine Handkarte ablegt und eine neue Karte zieht.
     * @param array $params
     */
    protected function princeEffect($params)
    {
        if (!$params) {
            $this->status = $this->getActivePlayer()->getName() . ' sucht Mitspieler für Karteneffekt "' . $this->activeCard['name'] . '" aus ...';
            $this->waitFor = self::WAIT_FOR_CHOOSE_PLAYER;
            return;
        }
        // TODO Implement functionality
        $this->waitFor = self::WAIT_FOR_DISCARD_CARD;
        $this->status = $this->getActivePlayer()->getName() . ' muss seine Karte auf den Ablagestapel legen ...';
    }

    /**
     * Tausche deine Handkarte mit der eines Mitspielers
     * @param array $params
     */
    protected function kingEffect($params)
    {
        if (!$params) {
            $this->status = $this->getActivePlayer()->getName() . ' sucht Mitspieler für Karteneffekt "' . $this->activeCard['name'] . '" aus ...';
            $this->waitFor = self::WAIT_FOR_CHOOSE_PLAYER;
            return;
        }
        // TODO Implement functionality
        $this->waitFor = self::WAIT_FOR_DISCARD_CARD;
        $this->status = $this->getActivePlayer()->getName() . ' muss seine Karte auf den Ablagestapel legen ...';
    }

    /**
     * Wenn du zusätzlich König oder Prinz auf der Hand hast, musst du die Gräfin ausspielen.
     */
    protected function countessEffect()
    {
        $this->waitFor = self::WAIT_FOR_DISCARD_CARD;
        $this->status = $this->getActivePlayer()->getName() . ' muss seine Karte auf den Ablagestapel legen ...';
    }

    /**
     * Wenn du die Prinzessin ablegst, scheidest du aus ...
     */
    protected function princessEffect()
    {
        // You are out
        // TODO Implement functionality
        $this->waitFor = self::WAIT_FOR_DISCARD_CARD;
        $this->status = $this->getActivePlayer()->getName() . ' muss seine Karte auf den Ablagestapel legen ...';
    }

}
