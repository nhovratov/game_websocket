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
     * @var string
     */
    protected $priestEffectVisibleCard = '';

    /**
     * @var string
     */
    protected $allowedAction = '';

    /**
     * @var array
     */
    protected $discardPile = [];

    public function getState()
    {
        return [
            'cards' => $this->cards,
            'openEffectCards' => $this->openEffectCards,
            'priestEffectVisibleCard' => $this->priestEffectVisibleCard,
            'allowedAction' => $this->allowedAction
        ];
    }

    public function reset()
    {
        $this->cards = [];
        $this->openEffectCards = [];
        $this->allowedAction = '';
        $this->discardPile = [];
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
     * @return string
     */
    public function getPriestEffectVisibleCard(): string
    {
        return $this->priestEffectVisibleCard;
    }

    /**
     * @param string $priestEffectVisibleCard
     */
    public function setPriestEffectVisibleCard(string $priestEffectVisibleCard)
    {
        $this->priestEffectVisibleCard = $priestEffectVisibleCard;
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
}
