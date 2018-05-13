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

    protected $openCards = [];

    protected $outOfGameCards = [];

    protected $firstPlayerSelected = false;

    /**
     * @var int
     */
    protected $playerTurn;

    protected $waitingForPlayerToChooseCard = false;

    protected $status = '';

    protected $activeCard;

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
            case 'chooseCard':
                $this->playerChoosesCard($params);
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
        $this->status = $player->getName() . ' ist dran...';
        $this->drawCardForActivePlayer();
        $this->waitingForPlayerToChooseCard = true;
        $this->updateState();
    }

    protected function playerChoosesCard($params)
    {
        $index = $params['index'];
        $player = $this->getActivePlayer();
        $state = $player->getGameState();
        $this->activeCard = array_splice($state['cards'], $index, 1)[0];
        $this->openCards[] = $this->activeCard;
        $this->handleEffect($this->activeCard);
        $player->setGameState($state);
        $this->waitingForPlayerToChooseCard = false;
        $this->updateState();
    }

    protected function handleEffect($card)
    {
        switch ($card['name']) {
            case 'Wächterin':
                $this->guardianEffect();
                break;
            case 'Priester':
                $this->priestEffect();
                break;
            case 'Baron':
                $this->baronEffect();
                break;
            case 'Zofe':
                $this->maidEffect();
                break;
            case 'Prinz':
                $this->princeEffect();
                break;
            case 'König':
                $this->kingEffect();
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

    public function getGlobalState()
    {
        $msg = [
            'gameStarted' => $this->gameStarted,
            'firstPlayerSelected' => $this->firstPlayerSelected,
            'playerTurn' => $this->playerTurn,
            'waitingForPlayerToChooseCard' => $this->waitingForPlayerToChooseCard,
            'outOfGameCards' => $this->outOfGameCards,
            'openCards' => $this->openCards,
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

    /**
     * Errätst du die Handkarte eines Mitspielers, scheidet dieser aus. Gilt nicht für "Wächterin"!
     * @param bool $params
     */
    protected function guardianEffect($params = false)
    {
        echo "Wächterin Effekt aktivieren";
        // 1. Player chooses other player
    }

    protected function priestEffect()
    {
        echo "Priester Effekt aktivieren";
    }

    protected function baronEffect()
    {
        echo "Baron Effekt aktivieren";
    }

    protected function maidEffect()
    {
        echo "Zofe Effekt aktivieren";
    }

    protected function princeEffect()
    {
        echo "Prinz Effekt aktivieren";
    }

    protected function kingEffect()
    {
        echo "König Effekt aktivieren";
    }

    protected function countessEffect()
    {
        echo "Gräfin Effekt aktivieren";
    }

    protected function princessEffect()
    {
        echo "Prinzessin Effekt aktivieren";
    }

}
