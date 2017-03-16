<?php
namespace Service\Processor;
use MongoSQL\Service\Processor\ProcessorResult;
use \UnitTester;

class ProcessorResultCest
{
    public function _before(UnitTester $I)
    {
    }

    public function _after(UnitTester $I)
    {
    }

    // tests
    public function stringResultTest(UnitTester $I)
    {
        $I->wantToTest('ProcessorResult with string data');

        $strMessage = 'Testing ProcessorResult with string data';
        $stringResult = new ProcessorResult(
            ProcessorResult::TYPE_STRING,
            $strMessage
        );

        $I->assertEquals(ProcessorResult::TYPE_STRING, $stringResult->getType());
        $I->assertEquals($strMessage, $stringResult->getStrData());
        $I->assertEquals($strMessage, $stringResult->getData());
    }

    // tests
    public function arrayResultTest(UnitTester $I)
    {
        $I->wantToTest('ProcessorResult with array data');

        $arrayData = ['Testing ProcessorResult with array data'];
        $arrayResult = new ProcessorResult(
            ProcessorResult::TYPE_TABLE,
            '',
            $arrayData
        );

        $I->assertEquals(ProcessorResult::TYPE_TABLE, $arrayResult->getType());
        $I->assertEquals($arrayData, $arrayResult->getTableData());
        $I->assertEquals($arrayData, $arrayResult->getData());
    }
}
