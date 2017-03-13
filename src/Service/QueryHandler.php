<?php

namespace MongoSQL\Service;

use PHPSQLParser\PHPSQLParser;
use MongoDB\Client;
use MongoDB\Database;
use MongoSQL\Service\Exception\UnknownProcessorException;
use MongoSQL\Service\Processor\ProcessorResult;
use MongoSQL\Service\Processor\SelectProcessor;
use MongoSQL\Service\Processor\ShowProcessor;

class QueryHandler
{
    const KEYWORD_USE = 'USE';
    const KEYWORD_SHOW = 'SHOW';
    const KEYWORD_SELECT = 'SELECT';

    /** @var PHPSQLParser */
    private $parser;

    /** @var Client */
    private $client;

    /** @var Database */
    private $database;


    public function __construct(PHPSQLParser $sqlParser, Client $client)
    {
        $this->parser = $sqlParser;
        $this->client = $client;
    }

    /**
     * @param string $sql
     * @return ProcessorResult|string
     * @throws UnknownProcessorException
     */
    public function handle(string $sql)
    {
        $parsed = $this->parser->parse($sql);
        $keyword = mb_strtoupper(array_keys($parsed)[0]);
        $result = '';

        switch ($keyword) {
            case static::KEYWORD_SHOW :
                $processor = new ShowProcessor($this->client, $this->database);
                $result = $processor->execute($parsed);
                break;
            case static::KEYWORD_USE :
                $dbName = $parsed[static::KEYWORD_USE][1];

                if ($this->selectDatabase($dbName)) {
                    $result = new ProcessorResult(ProcessorResult::TYPE_STRING, 'The DB ' . $dbName . ' has been selected');
                } else {
                    $result = new ProcessorResult(ProcessorResult::TYPE_STRING, 'The DB ' . $dbName . ' is not exists on this server');
                }
                break;
            case static::KEYWORD_SELECT :
                $processor = new SelectProcessor($this->client, $this->database);
                $result = $processor->execute($parsed);
                break;
            default :
                $keyword = ucfirst(strtolower($keyword));
                throw new UnknownProcessorException($keyword);
        }

        return $result;
    }

    /**
     * @param string $dbName
     * @return bool
     */
    private function selectDatabase(string $dbName)
    {
        if ($this->inListDatabases($dbName)) {
            $this->database = $this->client->selectDatabase($dbName);

            return true;
        }

        return false;
    }

    /**
     * @param $dbName
     * @return bool
     */
    private function inListDatabases($dbName)
    {
        $dbs = $this->client->listDatabases();
        foreach ($dbs as $db) {
            if (mb_strtoupper($db->getName()) === mb_strtoupper($dbName)) {
                return true;
            }
        }

        return false;
    }
}