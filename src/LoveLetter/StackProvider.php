<?php
/**
 * Created by PhpStorm.
 * User: NIKITA
 * Date: 19.05.2018
 * Time: 16:06
 */

namespace MyApp\LoveLetter;

class StackProvider
{
    protected $counter = 1;

    public function getStack()
    {
        $stack = [];
        $this->insertCards($stack, LoveLetter::GUARDIANCARD, LoveLetter::GUARDIANCOUNT);
        $this->insertCards($stack, LoveLetter::PRIESTCARD, LoveLetter::PRIESTCOUNT);
        $this->insertCards($stack, LoveLetter::BARONCARD, LoveLetter::BARONCOUNT);
        $this->insertCards($stack, LoveLetter::MAIDCARD, LoveLetter::MAIDCOUNT);
        $this->insertCards($stack, LoveLetter::PRINCECARD, LoveLetter::PRINCECOUNT);
        $this->insertCards($stack, LoveLetter::KINGCARD, LoveLetter::COUNTESSCOUNT);
        $this->insertCards($stack, LoveLetter::COUNTESSCARD, LoveLetter::COUNTESSCOUNT);
        $this->insertCards($stack, LoveLetter::PRINCESSCARD, LoveLetter::PRINCESSCOUNT);
        shuffle($stack);
        return $stack;
    }

    protected function insertCards(&$stack, $card, $count = 1)
    {
        for ($i = 0; $i < $count; $i++) {
            $card['id'] = $this->counter;
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
