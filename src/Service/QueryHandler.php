<?php

namespace MongoSQL\Service;

use MongoDB\Exception\InvalidArgumentException;
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
                try {
                    $this->selectDatabase($dbName);
                    $result = new ProcessorResult(
                        ProcessorResult::TYPE_STRING,
                        sprintf('Switched to db %s', $dbName)
                    );
                } catch (InvalidArgumentException $exception) {
                    $result = new ProcessorResult(
                        ProcessorResult::TYPE_STRING,
                        sprintf('Error switching to db %s : %s ', $dbName, $exception->getMessage())
                    );
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
     * @return Database
     * @throws InvalidArgumentException for parameter/option parsing errors
     */
    private function selectDatabase(string $dbName)
    {
        $this->database = $this->client->selectDatabase($dbName);

        return $this->database;
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