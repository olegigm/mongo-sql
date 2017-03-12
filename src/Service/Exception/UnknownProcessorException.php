<?php

namespace MongoSQL\Service\Exception;


class UnknownProcessorException extends \Exception
{
    protected $argument;

    public function __construct($argument) {
        $this->argument = $argument;
        parent::__construct("Unknown Processor : \n" . $argument, 10);
    }

    public function getArgument() {
        return $this->argument;
    }
}