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
    const CARDTYPES = [
        [
            'name' => 'Prinzessin',
            'value' => 8,
            'count' => 1,
            'effect' => 'Wenn du die Prinzessin ablegst, scheidest du aus ...'
        ],

        [
            'name' => 'Gräfin',
            'value' => 7,
            'count' => 1,
            'effect' => 'Wenn du zusätzlich König oder Prinz auf der Hand hast, musst du die Gräfin ausspielen.'
        ],

        [
            'name' => 'König',
            'value' => 6,
            'count' => 1,
            'effect' => 'Tausche deine Handkarte mit der eines Mitspielers'
        ],

        [
            'name' => 'Prinz',
            'value' => 5,
            'count' => 2,
            'effect' => 'Wähle einen Spieler, der seine Handkarte ablegt und eine neue Karte zieht.'
        ],

        [
            'name' => 'Zofe',
            'value' => 4,
            'count' => 2,
            'effect' => 'Du bist bis zu deinem nächsten Zug geschützt.'
        ],

        [
            'name' => 'Baron',
            'value' => 3,
            'count' => 2,
            'effect' => 'Vergleiche deine Handkarte mit der eines Mitspielers. Der Spieler mit dem niedrigeren Wert scheidet aus ...'
        ],

        [
            'name' => 'Priester',
            'value' => 2,
            'count' => 2,
            'effect' => 'Schaue dir die Handkarte eines Mitspielers an.'
        ],

        [
            'name' => 'Wächterin',
            'value' => 1,
            'count' => 5,
            'effect' => 'Errätst du die Handkarte eines Mitspielers, scheidet dieser aus ... Gilt nicht für "Wächterin"!'
        ]

    ];

    /**
     * @var array
     */
    protected $players = [];

    /**
     * @var bool
     */
    protected $gameStarted = false;

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
    protected $depositedCards = [];

    /**
     * @var array
     */
    protected $outOfGameCards = [];

    /**
     * @var bool
     */
    protected $firstPlayerSelected = false;

    /**
     * @var int
     */
    protected $playerTurn;

    /**
     * @var bool
     */
    protected $waitingForPlayerToChooseCard = false;

    /**
     * @var bool
     */
    protected $waitingForPlayerToChoosePlayer = false;

    /**
     * @var array
     */
    protected $outOfGamePlayers = [];

    /**
     * @var array
     */
    protected $guardianEffectDefault = [
        'cardSelected' => true,
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

    public function __construct()
    {
        $this->generateStack();
        $this->guardianEffect = $this->guardianEffectDefault;
    }

    public function start($players)
    {
        $this->players = $players;
        $this->gameStarted = true;
        /** @var Player $player */
        foreach ($this->players as $player) {
            $currentState = $player->getGameState();
            $currentState['cards'] = [$this->drawCard()];
            $player->setGameState($currentState);
        }
        $this->reserve[] = $this->drawCard();
        if ($this->players->count() === 2) {
            for ($i = 0; $i < 3; $i++) {
                $this->outOfGameCards[] = $this->drawCard();
            }
        }
        $this->status = 'Host wählt ersten Spieler...';
        $this->updateState();
    }

    public function handleAction($action, $params)
    {
        switch ($action) {
            case 'selectFirstPlayer':
                $this->selectFirstPlayer($params['id']);
                break;
            case 'chooseCard':
                $this->playerChoosesCard($params);
                break;
            case 'selectPlayerForEffect':
                $this->handleEffect($this->activeCard, $params);
                $this->updateState();
                break;
            case 'depositActiveCard':
                $this->depositActiveCard();
                break;
            case 'selectGuardianEffectCard':
                $this->handleEffect($this->activeCard, $params);
                $this->updateState();
                break;
            default:
        }
    }

    public function updateState()
    {
        if (!$this->gameStarted) {
            return;
        }
        $playerinfo = [];
        if ($this->gameStarted) {
            $playerinfo = $this->getPlayers();
        }
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
    }

    protected function generateStack()
    {
        foreach (self::CARDTYPES as $type) {
            for ($i = 0; $i < $type['count']; $i++) {
                $this->stack[] = [
                    'name' => $type['name'],
                    'value' => $type['value'],
                    'effect' => $type['effect'],
                ];
            }
        }
        shuffle($this->stack);
    }

    protected function drawCard()
    {
        return array_pop($this->stack);
    }

    protected function selectFirstPlayer($id)
    {
        $this->firstPlayerSelected = true;
        $player = $this->getPlayerById($id);
        $this->startNewTurn($player);
        $this->updateState();
    }

    protected function playerChoosesCard($params)
    {
        $index = $params['index'];
        $player = $this->getActivePlayer();
        $state = $player->getGameState();
        $this->activeCard = array_splice($state['cards'], $index, 1)[0];
        $this->openCards[] = $this->activeCard;
        $this->activeCard['effectFinished'] = false;
        $this->handleEffect($this->activeCard);
        $player->setGameState($state);
        $this->waitingForPlayerToChooseCard = false;
        $this->updateState();
    }

    protected function depositActiveCard()
    {
        $this->depositedCards[] = array_splice($this->openCards, 0, 1)[0];
        $activePlayer = $this->getNextPlayer();
        $this->startNewTurn($activePlayer);
        $this->updateState();
    }

    protected function startNewTurn($player)
    {
        $this->playerTurn = $player->getId();
        $this->drawCardForActivePlayer();
        $this->waitingForPlayerToChooseCard = true;
        $this->status = "{$player->getName()} ist dran ...";
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
        while (true) {
            if (!$this->players->valid()) {
                $this->players->rewind();
            }
            $player = $this->players->current();
            if ($player->getId() == $this->playerTurn) {
                $this->players->next();
                if (!$this->players->valid()) {
                    $this->players->rewind();
                }
                return $this->players->current();
            }
            $this->players->next();
        }
        return false;
    }

    public function getGlobalState()
    {
        $msg = [
            'gameStarted' => $this->gameStarted,
            'firstPlayerSelected' => $this->firstPlayerSelected,
            'playerTurn' => $this->playerTurn,
            'waitingForPlayerToChooseCard' => $this->waitingForPlayerToChooseCard,
            'waitingForPlayerToChoosePlayer' => $this->waitingForPlayerToChoosePlayer,
            'outOfGameCards' => $this->outOfGameCards,
            'depositedCards' => $this->depositedCards,
            'openCards' => $this->openCards,
            'activeCard' => $this->activeCard,
            'guardianEffectCardSelected' => $this->guardianEffect['cardSelected'],
            'guardianEffectSelectableCards' => $this->guardianEffect['selectableCards'],
            'guardianEffectChosenPlayer' => $this->guardianEffect['name'],
            'status' => $this->status,
            'outOfGamePlayers' => $this->outOfGamePlayers
        ];
        return $msg;
    }

    /**
     * @return array
     */
    public function getStack(): array
    {
        return $this->stack;
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
        return $players;
    }

    protected function getPlayerById($id)
    {
        foreach ($this->players as $player) {
            if ($player->getId() == $id) {
                return $player;
            }
        }
        return false;
    }

    /**
     * Errätst du die Handkarte eines Mitspielers, scheidet dieser aus ... Gilt nicht für "Wächterin"!
     * @param array $params
     */
    protected function guardianEffect($params)
    {
        // Initial invocation without parameters, let player select other player.
        if ($this->guardianEffect['step'] === 1) {
            $this->waitingForPlayerToChoosePlayer = true;
            $this->status = $this->getActivePlayer()->getName() . ' sucht Mitspieler für Karteneffekt "' . $this->activeCard['name'] . '" aus ...';
            $this->guardianEffect['step'] = 2;
            return;
        }

        // Player has selected other player. Now let him guess a card.
        if ($this->guardianEffect['step'] === 2) {
            $this->waitingForPlayerToChoosePlayer = false;
            $id = $params['id'];
            $chosenPlayer = $this->getPlayerById($id);
            $this->guardianEffect['id'] = $id;
            $this->guardianEffect['name'] = $chosenPlayer->getName();
            $this->guardianEffect['cardSelected'] = false;
            $this->guardianEffect['step'] = 3;
            return;
        }

        // Check if player was right, then the other player is out.
        $chosenPlayer = $this->getPlayerById($this->guardianEffect['id']);
        $card = $params['card'];
        if ($card === $chosenPlayer->getGameState()['cards'][0]['name']) {
            $this->outOfGamePlayers[] = $chosenPlayer->getId();
            $this->status = $card .  '! Richtig geraten! ' . $chosenPlayer->getName() . ' scheidet aus! ';
        } else {
            $this->status = $card . '! Falsch geraten! ';
        }
        $this->activeCard['effectFinished'] = true;
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
            $this->waitingForPlayerToChoosePlayer = true;
            $this->status = $this->getActivePlayer()->getName() . ' sucht Mitspieler für Karteneffekt "' . $this->activeCard['name'] . '" aus ...';
            return;
        }
        $this->waitingForPlayerToChoosePlayer = false;
        // TODO Implement functionality
        $this->activeCard['effectFinished'] = true;
        $this->status = $this->getActivePlayer()->getName() . ' muss seine Karte auf den Ablagestapel legen ...';
    }

    /**
     * Vergleiche deine Handkarte mit der eines Mitspielers. Der Spieler mit dem niedrigeren Wert scheidet aus ...
     * @param array $params
     */
    protected function baronEffect($params)
    {
        if (!$params) {
            $this->waitingForPlayerToChoosePlayer = true;
            $this->status = $this->getActivePlayer()->getName() . ' sucht Mitspieler für Karteneffekt "' . $this->activeCard['name'] . '" aus ...';
            return;
        }
        $this->waitingForPlayerToChoosePlayer = false;
        // TODO Implement functionality
        $this->activeCard['effectFinished'] = true;
        $this->status = $this->getActivePlayer()->getName() . ' muss seine Karte auf den Ablagestapel legen ...';
    }

    /**
     * Du bist bis zu deinem nächsten Zug geschützt.
     */
    protected function maidEffect()
    {
        // You are protected
        // TODO Implement functionality
        $this->activeCard['effectFinished'] = true;
        $this->status = $this->getActivePlayer()->getName() . ' muss seine Karte auf den Ablagestapel legen ...';
    }

    /**
     * Wähle einen Spieler, der seine Handkarte ablegt und eine neue Karte zieht.
     * @param array $params
     */
    protected function princeEffect($params)
    {
        if (!$params) {
            $this->waitingForPlayerToChoosePlayer = true;
            $this->status = $this->getActivePlayer()->getName() . ' sucht Mitspieler für Karteneffekt "' . $this->activeCard['name'] . '" aus ...';
            return;
        }
        $this->waitingForPlayerToChoosePlayer = false;
        // TODO Implement functionality
        $this->activeCard['effectFinished'] = true;
        $this->status = $this->getActivePlayer()->getName() . ' muss seine Karte auf den Ablagestapel legen ...';
    }

    /**
     * Tausche deine Handkarte mit der eines Mitspielers
     * @param array $params
     */
    protected function kingEffect($params)
    {
        if (!$params) {
            $this->waitingForPlayerToChoosePlayer = true;
            $this->status = $this->getActivePlayer()->getName() . ' sucht Mitspieler für Karteneffekt "' . $this->activeCard['name'] . '" aus ...';
            return;
        }
        $this->waitingForPlayerToChoosePlayer = false;
        // TODO Implement functionality
        $this->activeCard['effectFinished'] = true;
        $this->status = $this->getActivePlayer()->getName() . ' muss seine Karte auf den Ablagestapel legen ...';
    }

    /**
     * Wenn du zusätzlich König oder Prinz auf der Hand hast, musst du die Gräfin ausspielen.
     */
    protected function countessEffect()
    {
        $this->activeCard['effectFinished'] = true;
        $this->status = $this->getActivePlayer()->getName() . ' muss seine Karte auf den Ablagestapel legen ...';
    }

    /**
     * Wenn du die Prinzessin ablegst, scheidest du aus ...
     */
    protected function princessEffect()
    {
        // You are out
        // TODO Implement functionality
        $this->activeCard['effectFinished'] = true;
        $this->status = $this->getActivePlayer()->getName() . ' muss seine Karte auf den Ablagestapel legen ...';
    }

}
