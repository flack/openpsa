<?php

use PHPUnit\Framework\TestListenerDefaultImplementation;
use PHPUnit\Framework\TestSuite;

class openpsa_listener
{
    use TestListenerDefaultImplementation;

    public function endTestSuite(TestSuite $suite): void
    {

    }
}