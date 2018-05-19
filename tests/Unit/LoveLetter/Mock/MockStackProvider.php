<?php
namespace MyApp\Mock;

use MyApp\LoveLetter\LoveLetter;
use MyApp\LoveLetter\StackProvider;

class MockStackProvider extends StackProvider
{
    public function getStack()
    {
        $stack = [];
        $this->insertCards($stack, LoveLetter::PRIESTCARD, LoveLetter::PRIESTCOUNT);
        $this->insertCards($stack, LoveLetter::BARONCARD, LoveLetter::BARONCOUNT);
        $this->insertCards($stack, LoveLetter::MAIDCARD, LoveLetter::MAIDCOUNT);
        $this->insertCards($stack, LoveLetter::PRINCECARD, LoveLetter::PRINCECOUNT);
        $this->insertCards($stack, LoveLetter::KINGCARD, LoveLetter::COUNTESSCOUNT);
        $this->insertCards($stack, LoveLetter::COUNTESSCARD, LoveLetter::COUNTESSCOUNT);
        $this->insertCards($stack, LoveLetter::PRINCESSCARD, LoveLetter::PRINCESSCOUNT);
        $this->insertCards($stack, LoveLetter::GUARDIANCARD, LoveLetter::GUARDIANCOUNT);
        return $stack;
    }
}
