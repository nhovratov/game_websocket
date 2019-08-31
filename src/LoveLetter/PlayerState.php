<?php
/**
 * Created by PhpStorm.
 * User: NIKITA
 * Date: 02.06.2018
 * Time: 17:05
 */

namespace NH\LoveLetter;

use NH\StateInterface;

class PlayerState implements StateInterface
{

    /**
     * @var array
     */
    protected $cards = [];

    /**
     * @var array
     */
    protected $openEffectCards = [];

    /**
     * @var array
     */
    protected $effectVisibleCard = [];

    /**
     * @var string
     */
    protected $allowedAction = '';

    /**
     * @var array
     */
    protected $discardPile = [];

    /**
     * @var int
     */
    protected $wins = 0;

    public function getState()
    {
        return [
            'cards' => $this->cards,
            'openEffectCards' => $this->openEffectCards,
            'effectVisibleCard' => $this->effectVisibleCard,
            'allowedAction' => $this->allowedAction,
            'wins' => $this->wins
        ];
    }

    public function reset()
    {
        $this->cards = [];
        $this->openEffectCards = [];
        $this->effectVisibleCard = [];
        $this->allowedAction = '';
        $this->discardPile = [];
    }

    public function resetWins()
    {
        $this->wins = 0;
    }

    /**
     * @param array $card
     */
    public function addCard($card)
    {
        $this->cards[$card['cardnumber']] = $card;
    }

    public function addOpenEffectCard($card)
    {
        $this->openEffectCards[] = $card;
    }

    /**
     * @return array
     */
    public function getCards(): array
    {
        return $this->cards;
    }

    /**
     * @param array $cards
     */
    public function setCards(array $cards)
    {
        $this->cards = $cards;
    }

    /**
     * @return array
     */
    public function getOpenEffectCards(): array
    {
        return $this->openEffectCards;
    }

    /**
     * @param array $openEffectCards
     */
    public function setOpenEffectCards(array $openEffectCards)
    {
        $this->openEffectCards = $openEffectCards;
    }

    /**
     * @return array
     */
    public function getEffectVisibleCard(): array
    {
        return $this->effectVisibleCard;
    }

    /**
     * @param string $effectVisibleCard
     */
    public function setEffectVisibleCard(array $effectVisibleCard)
    {
        $this->effectVisibleCard = $effectVisibleCard;
    }

    /**
     * @return string
     */
    public function getAllowedAction(): string
    {
        return $this->allowedAction;
    }

    /**
     * @param string $allowedAction
     */
    public function setAllowedAction(string $allowedAction)
    {
        $this->allowedAction = $allowedAction;
    }

    /**
     * @return array
     */
    public function getDiscardPile(): array
    {
        return $this->discardPile;
    }

    /**
     * @param array $card
     */
    public function addToDicardPile($card)
    {
        $this->discardPile[] = $card;
    }

    protected function discardCard(&$cards)
    {
        $this->addToDicardPile(array_splice($cards, 0, 1)[0]);
    }

    public function discardHandCard()
    {
        $this->discardCard($this->cards);
    }

    public function discardOpenEffectCard()
    {
        $this->discardCard($this->openEffectCards);
    }

    /**
     * @return int
     */
    public function addWin()
    {
        $this->wins += 1;
        return $this->wins;
    }

    /**
     * @return array
     */
    public function getHandCard()
    {
        return array_slice($this->cards, 0, 1)[0];
    }

    /**
     * @return int
     */
    public function getWins(): int
    {
        return $this->wins;
    }

    public function resetEffectVisibleCard()
    {
        $this->effectVisibleCard = [];
    }
}
