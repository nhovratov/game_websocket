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
            'effect' => 'Wenn du die Prinzessin ablegst, scheidest du aus.'
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
            'effect' => 'Vergleiche deine Handkarte mit der eines Mitspielers. Der Spieler mit dem niedrigeren Wert scheidet aus.'
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
            'effect' => 'Errätst du die Handkarte eines Mitspielers, scheidet dieser aus. Gilt nicht für "Wächterin"!'
        ]

    ];

    protected $players;

    protected $gameStarted = false;

    protected $stack = [];

    protected $reserve = [];

    protected $outOfGameCards = [];

    protected $firstPlayerSelected = false;

    protected $playerTurn;

    protected $status = '';

    public function __construct()
    {
        $this->generateStack();
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
                    'effect' => $type['effect']
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
        $player = $this->getPlayerById($id);
        $this->playerTurn = $id;
        $this->firstPlayerSelected = true;
        $this->status = 'Erster Spieler ' . $player->getName() . ' ist dran...';
        $this->drawCardForActivePlayer();
        $this->updateState();
    }

    protected function drawCardForActivePlayer()
    {
        $player = $this->getPlayerById($this->playerTurn);
        $state = $player->getGameState();
        $state['cards'][] = $this->drawCard();
        $player->setGameState($state);
    }

    public function getGlobalState()
    {
        $msg = [
            'gameStarted' => $this->gameStarted,
            'firstPlayerSelected' => $this->firstPlayerSelected,
            'playerTurn' => $this->playerTurn,
            'outOfGameCards' => $this->outOfGameCards,
            'status' => $this->status
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

}
