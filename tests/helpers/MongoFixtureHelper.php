<?php

namespace MongoSQL\Tests\helpers;

class MongoFixtureHelper
{
    const DATA_DIR = __DIR__ . '/../_data/';

    /**
     * @return array
     */
    public static function getAllFixtures()
    {
        return [
            'books' => [
                'collection' => 'books',
                'dataFile' => MongoFixtureHelper::DATA_DIR . 'books.php'
            ]
        ];
    }
}