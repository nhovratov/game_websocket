<?php
namespace NH\Mock;

use NH\LoveLetter\Card\Baron;
use NH\LoveLetter\Card\Countess;
use NH\LoveLetter\Card\Guardian;
use NH\LoveLetter\Card\King;
use NH\LoveLetter\Card\Maid;
use NH\LoveLetter\Card\Priest;
use NH\LoveLetter\Card\Prince;
use NH\LoveLetter\Card\Princess;
use NH\LoveLetter\StackProvider;

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

    protected function guardianWinStack(&$stack)
    {
        $this->insertCards($stack, Guardian::getCard(), 7);
        $this->insertCards($stack, Countess::getCard(), 1);
        $this->insertCards($stack, Guardian::getCard(), 1);
    }

    protected function maidStack(&$stack)
    {
        $this->insertCards($stack, Maid::getCard(), 7);
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
        $this->insertCards($stack, King::getCard(), 5);
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
