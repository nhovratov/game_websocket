<?php

namespace NH\LoveLetter;

use NH\LoveLetter\Card\Baron;
use NH\LoveLetter\Card\Countess;
use NH\LoveLetter\Card\Guardian;
use NH\LoveLetter\Card\King;
use NH\LoveLetter\Card\Maid;
use NH\LoveLetter\Card\Priest;
use NH\LoveLetter\Card\Prince;

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
        $this->insertCards($stack, King::getCard(), LoveLetter::KINGCOUNT);
        $this->insertCards($stack, Countess::getCard(), LoveLetter::COUNTESSCOUNT);
        $this->insertCards($stack, Prince::getCard(), LoveLetter::PRINCESSCOUNT);
        shuffle($stack);
        $this->counter = 1;
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
}
