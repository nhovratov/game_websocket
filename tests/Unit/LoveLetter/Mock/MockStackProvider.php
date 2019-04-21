<?php
namespace MyApp\Mock;

use MyApp\LoveLetter\Card\Baron;
use MyApp\LoveLetter\Card\Countess;
use MyApp\LoveLetter\Card\Guardian;
use MyApp\LoveLetter\Card\King;
use MyApp\LoveLetter\Card\Maid;
use MyApp\LoveLetter\Card\Priest;
use MyApp\LoveLetter\Card\Prince;
use MyApp\LoveLetter\Card\Princess;
use MyApp\LoveLetter\StackProvider;

class MockStackProvider extends StackProvider
{
    protected $testcase = 'guardian';

    public function getStack()
    {
        $stack = [];
        $methodName = "{$this->testcase}Stack";
        $this->$methodName($stack);
        return $stack;
    }

    public function setTestCase($case)
    {
        $this->testcase = $case;
    }

    protected function guardianStack(&$stack)
    {
        $this->insertCards($stack, Guardian::getCard(), 7);
    }

    protected function maidStack(&$stack)
    {
        $this->insertCards($stack, Maid::getCard(), 6);
        $this->insertCards($stack, Guardian::getCard(), 1);
        $this->insertCards($stack, Maid::getCard(), 1);
    }

    protected function priestStack(&$stack)
    {
        $this->insertCards($stack, Priest::getCard(), 8);
    }

    protected function baronWinStack(&$stack)
    {
        $this->insertCards($stack, Princess::getCard(), 1);
        $this->insertCards($stack, Guardian::getCard(), 5);
        $this->insertCards($stack, Baron::getCard(), 1);
    }

    protected function baronLooseStack(&$stack)
    {
        $this->insertCards($stack, Baron::getCard(), 1);
        $this->insertCards($stack, Guardian::getCard(), 5);
        $this->insertCards($stack, Princess::getCard(), 1);
    }

    protected function baronEqualStack(&$stack)
    {
        $this->insertCards($stack, Baron::getCard(), 10);
        $this->insertCards($stack, Guardian::getCard(), 4);
        $this->insertCards($stack, Princess::getCard(), 2);
    }

    protected function princessStack(&$stack)
    {
        $this->insertCards($stack, Princess::getCard(), 10);
    }

    protected function princeLooseStack(&$stack)
    {
        $this->insertCards($stack, Princess::getCard(), 11);
        $this->insertCards($stack, Prince::getCard(), 1);
    }

    protected function princeNormalStack(&$stack)
    {
        $this->insertCards($stack, King::getCard(), 10);
        $this->insertCards($stack, Baron::getCard(), 1);
        $this->insertCards($stack, Prince::getCard(), 1);
    }

    protected function kingStack(&$stack)
    {
        $this->insertCards($stack, Baron::getCard(), 5);
        $this->insertCards($stack, Prince::getCard(), 1);
        $this->insertCards($stack, King::getCard(), 1);
    }

    protected function countessStack(&$stack)
    {
        $this->insertCards($stack, Prince::getCard(), 7);
        $this->insertCards($stack, Countess::getCard(), 1);
    }
}
