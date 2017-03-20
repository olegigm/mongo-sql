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
        $result = $this->useDatabase($databaseName);

        $I->assertTrue($result instanceof ProcessorResult);
        $I->assertEquals($result->getType(), ProcessorResult::TYPE_STRING);
        $I->assertEquals($result->getStrData(), sprintf('Switched to db %s', $databaseName));
    }


    public function selectQueryExecuteTest(UnitTester $I)
    {
        $I->wantToTest('query data');

        $this->useDatabase($I->getConfig('database_name'));

        $sql = 'select author, title, position from books where position > 1 and position < 5';
        $result = $this->queryHandler->handle($sql);

        $I->assertTrue($result instanceof ProcessorResult);
        $I->assertEquals($result->getType(), ProcessorResult::TYPE_TABLE);
        $I->assertEquals($this->checkSelectResultArray(), $this->resultToArray($result->getData()));
    }

    /**
     * @param string $databaseName
     * @return ProcessorResult|string
     */
    private function useDatabase($databaseName)
    {
        $sql = 'use ' . $databaseName;
        return $this->queryHandler->handle($sql);
    }

    private function checkSelectResultArray()
    {
        return [
                [
                    0 => 'Brit Bennett',
                    1 => 'The Mothers',
                    2 => 2,
                ],
                [
                    0 => 'Adam Haslett',
                    1 => 'Imagine Me Gone',
                    2 => 3,
                ],
                [
                    0 => 'Yaa Gyasi',
                    1 => 'Homegoing',
                    2 => 4,
                ],
        ];
    }

    private function resultToArray(array $data) : array
    {
        $data = json_decode(json_encode($data), true);
        $headers = array_keys($data[0]);

        $rows = [];
        foreach ($data as $key => $row) {
            foreach ($headers as $column) {
                if (array_key_exists($column, $row)) {
                    if ($column == '_id') {
                        $rows[$key][] = $row[$column]['$oid'];
                    } else {
                        $rows[$key][] = (is_array($row[$column])) ? json_encode($row[$column]) : $row[$column];
                    }
                } else {
                    $rows[$key][] = '';
                }
            }
        }

        return $rows;
    }


}
