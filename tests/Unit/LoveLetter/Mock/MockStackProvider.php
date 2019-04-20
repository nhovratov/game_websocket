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
}
