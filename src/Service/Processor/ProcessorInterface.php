<?php

namespace MongoSQL\Service\Processor;


interface ProcessorInterface
{
    public function execute(array $parsed) : ProcessorResult;
}