<?php

namespace MongoSQL\Service\Exception;


class UnknownResultType extends \Exception
{
    protected $argument;

    public function __construct($argument) {
        $this->argument = $argument;
        parent::__construct("Unknown Result type: \n" . $argument, 10);
    }

    public function getArgument() {
        return $this->argument;
    }
}