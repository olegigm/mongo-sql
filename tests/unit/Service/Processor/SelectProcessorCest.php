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
    public function filterPrepareTest(UnitTester $I)
    {
        $I->wantToTest('filter prepare');

        $filters = $this->getFiltersPrepareFilter();
        $checkFilters = $this->getCheckFiltersPrepareFilter();

        $prepareFilter = $this->getMethod('prepareFilter');
        $selectProcessor = new SelectProcessor(new Client(), $I->getConfig('database_name'));
        $result = $prepareFilter->invokeArgs($selectProcessor, [$filters]);

        $I->assertEquals($checkFilters, $result);
    }

    // tests
    public function buildAndFilterTest(UnitTester $I)
    {
        $I->wantToTest('building And filter');

        $filters = $this->getFiltersBuildAndFilter();
        $checkFilters = $this->getCheckFiltersBuildAndFilter();

        $prepareFilter = $this->getMethod('buildAndFilter');
        $selectProcessor = new SelectProcessor(new Client(), $I->getConfig('database_name'));
        $result = $prepareFilter->invokeArgs($selectProcessor, [$filters, 'and']);

        $I->assertEquals($checkFilters, $result);
    }

    // tests
    public function gtLtFilterPrepareTest(UnitTester $I)
    {
        $I->wantToTest('filter prepare for gt & lt');

        $filters = $this->getFiltersGtLtPrepareFilter();
        $checkFilters = $this->getCheckFiltersGtLtPrepareFilter();

        $prepareFilter = $this->getMethod('prepareFilter');
        $selectProcessor = new SelectProcessor(new Client(), $I->getConfig('database_name'));
        $result = $prepareFilter->invokeArgs($selectProcessor, [$filters]);

        $I->assertEquals($checkFilters, $result);
    }

    // test
    public function filterBuildTest(UnitTester $I)
    {
        $I->wantToTest('filter builder');

        $filters = $this->getCheckFiltersPrepareFilter();
        $checkFilters = $this->getCheckFiltersBuildFilter();

        $buildFilter = $this->getMethod('buildFilter');
        $selectProcessor = new SelectProcessor(new Client(), $I->getConfig('database_name'));
        $result = $buildFilter->invokeArgs($selectProcessor, [$filters]);

        $I->assertEquals($checkFilters, $result);
    }

    // test
    public function gtLtFilterBuildTest(UnitTester $I)
    {
        $I->wantToTest('filter builder for gt & lt');

        $filters = $this->getCheckFiltersGtLtPrepareFilter();
        $checkFilters = $this->getCheckFiltersGtLtBuildFilter();

        $buildFilter = $this->getMethod('buildFilter');
        $selectProcessor = new SelectProcessor(new Client(), $I->getConfig('database_name'));
        $result = $buildFilter->invokeArgs($selectProcessor, [$filters]);

        $I->assertEquals($checkFilters, $result);
    }

    private function getFiltersPrepareFilter()
    {
        return [
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
    }

    private function getCheckFiltersPrepareFilter()
    {
        return [
            ['and' => [
                'position' => [
                    '<' => ['5'],
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
    }

    private function getCheckFiltersBuildFilter()
    {
        return ['$and' => [
            ['position' => ['$lt' => 5]],
            ['$or' => [
                ['author' => ['$in' => [
                            'Brit Bennett',
                            'Adam Haslett',
                ]]],
                ['title' => 'The Regional Office Is Under Attack!'],
            ]],
        ]];
    }

    private function getFiltersGtLtPrepareFilter()
    {
        return [
            [
                'condition' => [
                    ['expr_type' => 'colref', 'base_expr' => 'position'],
                    ['expr_type' => 'operator', 'base_expr' => '>'],
                    ['expr_type' => 'const', 'base_expr' => '1'],
                ],
                'type' => 'and',
            ],
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
            ],
        ];
    }

    private function getCheckFiltersGtLtPrepareFilter()
    {
        return [
            ['and' => [
                'position' => [
                    '>' => ['1'],
                    '<' => ['5'],
                ],
                'author' => [
                    '=' => ['Brit Bennett'],
                ],
            ]],
        ];
    }

    private function getCheckFiltersGtLtBuildFilter()
    {
        return ['$and' => [
            ['position' => [
                '$gt' => 1,
                '$lt' => 5,
            ]],
            ['author' => 'Brit Bennett'],
        ]];
    }

    private function getFiltersBuildAndFilter()
    {
        return ['and' => [
            'position' => [
                '>' => ['1'],
                '<' => ['5'],
            ],
            'author' => [
                '=' => ['Brit Bennett'],
            ],
        ]];
    }

    private function getCheckFiltersBuildAndFilter()
    {
        return [
            ['position' => [
                '$gt' => 1,
                '$lt' => 5,
            ]],
            ['author' => 'Brit Bennett'],
        ];
    }
}
