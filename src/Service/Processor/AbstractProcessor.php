<?php

namespace MongoSQL\Service\Processor;


use MongoDB\Client;
use MongoDB\Database;

abstract class AbstractProcessor implements ProcessorInterface
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var Database
     */
    protected $database;


    public function __construct(Client $client, $database)
    {
        $this->client = $client;
        $this->database = $database;
    }

    /**
     * @return bool
     */
    protected function isDatabaseSelected()
    {
        if ($this->database instanceof Database) {
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    protected function listDatabases()
    {
        $result = '';
        $dbs = $this->client->listDatabases();
        foreach ($dbs as $db) {
            $result .= $db->getName() . PHP_EOL;
        }

        return $result;
    }

    /**
     * @return string
     */
    protected function listCollections()
    {
        $result = '';
        $collections = $this->database->listCollections();

        foreach ($collections as $collection) {
            $result .= $collection->getName() . PHP_EOL;
        }

        return $result;
    }

    /**
     * @param $tableName
     * @return bool
     */
    protected function inListCollections($tableName)
    {
        $upperTableName = mb_strtoupper($tableName);
        $collections = $this->database->listCollections();

        foreach ($collections as $collection) {
            if (mb_strtoupper($collection->getName()) === $upperTableName) {
                return true;
            }
        }

        return false;
    }
}