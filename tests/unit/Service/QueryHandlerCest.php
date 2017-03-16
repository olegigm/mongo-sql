<?php

use MongoSQL\Service\Processor\ProcessorResult;
use MongoSQL\Service\QueryHandler;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use MongoSQL\Tests\helpers\MongoFixtureHelper;

class QueryHandlerCest
{
    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * @var QueryHandler
     */
    private $queryHandler;

    public function _before(UnitTester $I)
    {
        // load fixture
        $I->haveMongoFixtures($I->getConfig('database_name'), MongoFixtureHelper::getAllFixtures());

        $container = new ContainerBuilder();

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../../config'));
        $loader->load('services.php');

        $this->container = $container;
        $this->queryHandler = $this->container->get('queryHandler');
    }

    public function _after(UnitTester $I)
    {
    }

    // tests
    public function testContainer(UnitTester $I)
    {
        $I->wantTo('Test Container');
        $I->assertTrue($this->queryHandler instanceof QueryHandler);
    }

    public function testUseDB(UnitTester $I)
    {
        $I->wantToTest('select database');
        $databaseName = $I->getConfig('database_name');
        $sql = 'use ' . $databaseName;
        $result = $this->queryHandler->handle($sql);
        $I->assertTrue($result instanceof ProcessorResult);
        $I->assertEquals($result->getType(), ProcessorResult::TYPE_STRING);
        $I->assertEquals($result->getStrData(), sprintf('Switched to db %s', $databaseName));
    }
}