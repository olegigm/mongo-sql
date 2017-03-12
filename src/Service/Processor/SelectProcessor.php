<?php

namespace MongoSQL\Service\Processor;



class SelectProcessor extends AbstractProcessor
{
    const KEYWORD_SELECT = 'SELECT';
    const KEYWORD_ALL = '*';
    const KEYWORD_FROM = 'FROM';
    const KEYWORD_WHERE = 'WHERE';

    public function execute(array $parsed): ProcessorResult
    {
        if (!$this->isDatabaseSelected()) {
            return new ProcessorResult('string', 'Use DB first');
        }

        if (!array_key_exists(static::KEYWORD_FROM, $parsed)) {
            return new ProcessorResult('string', 'The SELECT query must have FROM sentence');
        }

        $fromTable = $parsed[static::KEYWORD_FROM][0]['table'];
        if (!$this->inListCollections($fromTable)) {
            return new ProcessorResult(
                'string',
                'Table ' . $fromTable . ' is not exists in ' . $this->database->getDatabaseName() . ' database'
            );
        }

        $selectExpr = mb_strtoupper($parsed[static::KEYWORD_SELECT][0]['base_expr']);
        if ($selectExpr == static::KEYWORD_ALL) {
            return new ProcessorResult('table', '', $this->find($fromTable, [], ['limit' => 10]));
        }

        return new ProcessorResult('string', json_encode($parsed));
    }

    /**
     * @param $tableName
     * @param array $filter
     * @param array $options
     * @return array
     */
    protected function find($tableName, $filter = [], array $options = [])
    {
        return $this->database
            ->selectCollection($tableName)
            ->find($filter, $options)
            ->toArray();
    }
}