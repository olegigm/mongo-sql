<?php
namespace Service\Processor;

use \UnitTester;
use MongoDB\Client;
use PHPSQLParser\PHPSQLParser;
use MongoSQL\Service\Processor\ProcessorResult;
use MongoSQL\Service\Processor\ShowProcessor;
use MongoSQL\Tests\helpers\MongoFixtureHelper;

class ShowProcessorCest
{
    public function _before(UnitTester $I)
    {
        // load fixture
        $I->haveMongoFixtures($I->getConfig('database_name'), MongoFixtureHelper::getAllFixtures());
    }

    public function _after(UnitTester $I)
    {
    }

    // tests
    public function tryToTest(UnitTester $I)
    {
        $I->wantToTest('show processor');

        $dbName = $I->getConfig('database_name');
        $command = 'show databases';
        $parsed = (new PHPSQLParser())->parse($command);
        $processor = new ShowProcessor(new Client(), $dbName);
        $result = $processor->execute($parsed);

        $I->assertTrue($result instanceof ProcessorResult);
        $I->assertContains($dbName, $result->getData());


    }
}
