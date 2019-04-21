<?php
namespace MyApp\Mock;

use MyApp\LoveLetter\LoveLetter;
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
        $this->insertCards($stack, LoveLetter::GUARDIANCARD, 7);
    }

    protected function maidStack(&$stack)
    {
        $this->insertCards($stack, LoveLetter::MAIDCARD, 6);
        $this->insertCards($stack, LoveLetter::GUARDIANCARD, 1);
        $this->insertCards($stack, LoveLetter::MAIDCARD, 1);
    }

    protected function priestStack(&$stack)
    {
        $this->insertCards($stack, LoveLetter::PRIESTCARD, 8);
    }

    protected function baronWinStack(&$stack)
    {
        $this->insertCards($stack, LoveLetter::PRINCESSCARD, 1);
        $this->insertCards($stack, LoveLetter::GUARDIANCARD, 5);
        $this->insertCards($stack, LoveLetter::BARONCARD, 1);
    }

    protected function baronLooseStack(&$stack)
    {
        $this->insertCards($stack, LoveLetter::BARONCARD, 1);
        $this->insertCards($stack, LoveLetter::GUARDIANCARD, 5);
        $this->insertCards($stack, LoveLetter::PRINCESSCARD, 1);
    }

    protected function baronEqualStack(&$stack)
    {
        $this->insertCards($stack, LoveLetter::BARONCARD, 10);
        $this->insertCards($stack, LoveLetter::GUARDIANCARD, 4);
        $this->insertCards($stack, LoveLetter::PRINCESSCARD, 2);
    }

    protected function princessStack(&$stack)
    {
        $this->insertCards($stack, LoveLetter::PRINCESSCARD, 10);
    }
}
