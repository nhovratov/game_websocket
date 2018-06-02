<?php
/**
 * Created by PhpStorm.
 * User: NIKITA
 * Date: 02.06.2018
 * Time: 17:05
 */

namespace MyApp\LoveLetter;

use MyApp\StateInterface;

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

    public function getState()
    {
        return [
            'cards' => $this->cards,
            'openEffectCards' => $this->openEffectCards,
            'priestEffectVisibleCard' => $this->priestEffectVisibleCard
        ];
    }

    public function reset()
    {
        $this->cards = [];
        $this->openEffectCards = [];
    }

    /**
     * @param array $card
     */
    public function addCard($card)
    {
        $this->cards[] = $card;
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

}
