<?php

namespace Helper;

use MongoDB\Client;
use MongoDB\Database;
use MongoDB\Collection;

class MyMongoHelper extends \Codeception\Module
{
    /** @var Client */
    private $client;

    /**
     * @param string $databaseName
     * @param array $fixtures
     */
    public function haveMongoFixtures(string $databaseName, array $fixtures)
    {
        $this->clearDatabase($databaseName);

        /** @var Database */
        $database = $this->getClient()->selectDatabase($databaseName);

        foreach ($fixtures as $fixture) {
            /** @var Collection */
            $collection = $database->selectCollection($fixture['collection']);
            $documents = require($fixture['dataFile']);

            $collection->insertMany($documents);
        }
    }

    /**
     * @return Client
     */
    public function getClient(): Client
    {
        if (!$this->client instanceof Client) {
            $this->client = new Client();
        }

        return $this->client;
    }

    /**
     * @param string $databaseName
     */
    private function clearDatabase(string $databaseName)
    {
        $this->getClient()->selectDatabase($databaseName)->drop();
    }

}