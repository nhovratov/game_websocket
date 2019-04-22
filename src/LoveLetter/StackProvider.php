<?php

namespace MyApp\LoveLetter;

use MyApp\LoveLetter\Card\Baron;
use MyApp\LoveLetter\Card\Countess;
use MyApp\LoveLetter\Card\Guardian;
use MyApp\LoveLetter\Card\King;
use MyApp\LoveLetter\Card\Maid;
use MyApp\LoveLetter\Card\Priest;
use MyApp\LoveLetter\Card\Prince;

class StackProvider
{
    protected $counter = 1;

    public function getStack()
    {
        $stack = [];
        $this->insertCards($stack, Guardian::getCard(), LoveLetter::GUARDIANCOUNT);
        $this->insertCards($stack, Priest::getCard(), LoveLetter::PRIESTCOUNT);
        $this->insertCards($stack, Baron::getCard(), LoveLetter::BARONCOUNT);
        $this->insertCards($stack, Maid::getCard(), LoveLetter::MAIDCOUNT);
        $this->insertCards($stack, Prince::getCard(), LoveLetter::PRINCECOUNT);
        $this->insertCards($stack, King::getCard(), LoveLetter::COUNTESSCOUNT);
        $this->insertCards($stack, Countess::getCard(), LoveLetter::COUNTESSCOUNT);
        $this->insertCards($stack, Prince::getCard(), LoveLetter::PRINCESSCOUNT);
        shuffle($stack);
        return $stack;
    }

    protected function insertCards(&$stack, $card, $count)
    {
        for ($i = 0; $i < $count; $i++) {
            $card['cardnumber'] = $this->counter;
            $stack[] = $card;
            $this->counter += 1;
        }
    }

    /**
     * @return int
     */
    public function getCounter(): int
    {
        return $this->counter;
    }

    /**
     * @param int $counter
     */
    public function setCounter(int $counter)
    {
        $this->counter = $counter;
    }
}
