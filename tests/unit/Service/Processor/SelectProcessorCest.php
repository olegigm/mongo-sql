<?php
namespace Service\Processor;

use MongoDB\Client;
use \UnitTester;
use ReflectionClass;
use MongoSQL\Tests\helpers\MongoFixtureHelper;
use MongoSQL\Service\Processor\SelectProcessor;

class SelectProcessorCest
{
    protected function getMethod($name)
    {
        $class = new ReflectionClass(SelectProcessor::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

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

    }

    public function analyzeFilterTest(UnitTester $I)
    {
        $I->wantToTest('filter analyzer');

        $filters = [
            [
                'condition' => [
                    ['expr_type' => 'colref', 'base_expr' => 'position'],
                    ['expr_type' => 'operator', 'base_expr' => '<'],
                    ['expr_type' => 'const', 'base_expr' => '5'],
                ],
                'type' => 'and',
            ],
            [
                'condition' => [
                    ['expr_type' => 'colref', 'base_expr' => 'author'],
                    ['expr_type' => 'operator', 'base_expr' => '='],
                    ['expr_type' => 'const', 'base_expr' => 'Brit Bennett'],
                ],
                'type' => 'or',
            ],
            [
                'condition' => [
                    ['expr_type' => 'colref', 'base_expr' => 'author'],
                    ['expr_type' => 'operator', 'base_expr' => '='],
                    ['expr_type' => 'const', 'base_expr' => 'Adam Haslett'],
                ],
                'type' => 'or',
            ],
            [
                'condition' => [
                    ['expr_type' => 'colref', 'base_expr' => 'title'],
                    ['expr_type' => 'operator', 'base_expr' => '='],
                    ['expr_type' => 'const', 'base_expr' => 'The Regional Office Is Under Attack!'],
                ],
            ],
        ];

        $checkFilters = [
            ['and' => [
                [
                    [
                        'expr_type' => 'colref',
                        'base_expr' => 'position',
                    ],
                    [
                        'expr_type' => 'operator',
                        'base_expr' => '<',
                    ],
                    [
                        'expr_type' => 'const',
                        'base_expr' => '5',
                    ],
                ],
            ]],
            ['or' => [
                'author' => [
                    'in' => [
                        'Brit Bennett',
                        'Adam Haslett',
                    ],
                ],
                'title' => [
                    '=' => [
                        'The Regional Office Is Under Attack!',
                    ],
                ],
            ]],
        ];

        $analyzeFilter = $this->getMethod('analyzeFilter');
        $selectProcessor = new SelectProcessor(new Client(), $I->getConfig('database_name'));
        $result = $analyzeFilter->invokeArgs($selectProcessor, [$filters]);

        $I->assertEquals($checkFilters, $result);
    }
}
