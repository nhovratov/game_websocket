<?php
/**
 * Created by PhpStorm.
 * User: NIKITA
 * Date: 12.05.2018
 * Time: 21:35
 */

namespace MyApp\LoveLetter;

use MyApp\GameInterface;

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

    public function __construct()
    {
        $this->generateStack();
    }

    public function start($players)
    {
        $this->players = $players;
        $this->gameStarted = true;
        foreach ($this->players as $player) {
            $currentState = $player->getGameState();
            $currentState['cards'] = $this->convertObjectArrayToAssocArray([$this->drawCard()]);
            $player->setGameState($currentState);
        }
        $this->reserve[] = $this->drawCard();
        if ($this->players->count() === 2) {
            for ($i = 0; $i < 3; $i++) {
                $this->outOfGameCards[] = $this->drawCard();
            }
        }
        $this->updateState();
    }

    public function updateState()
    {
        foreach ($this->players as $player) {
            $msg = [
                'dataType' => 'game',
                'global' => $this->getGlobalState(),
                'local' => $player->getGameState()
            ];
            $player->getClient()->send(json_encode($msg));
        }
    }

    protected function generateStack()
    {
        foreach (self::CARDTYPES as $type) {
            for ($i = 0; $i < $type['count']; $i++) {
                $this->stack[] = new Card($type['name'], $type['value'], $type['effect']);
            }
        }
        shuffle($this->stack);
    }

    protected function drawCard()
    {
        return array_pop($this->stack);
    }

    public function getGlobalState()
    {
        return [
            'gameStarted' => $this->gameStarted,
            'outOfGameCards' => $this->convertObjectArrayToAssocArray($this->outOfGameCards)
        ];
    }

    protected function convertObjectArrayToAssocArray($objArray)
    {
        $assocArray = [];
        foreach ($objArray as $element) {
            $assocArray[] = $element->toArray();
        }
        return $assocArray;
    }

    /**
     * @return array
     */
    public function getStack(): array
    {
        return $this->stack;
    }
}
