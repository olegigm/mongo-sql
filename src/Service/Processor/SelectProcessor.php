<?php

namespace MongoSQL\Service\Processor;



class SelectProcessor extends AbstractProcessor
{
    const KEYWORD_SELECT = 'SELECT';
    const KEYWORD_ALL = '*';
    const KEYWORD_FROM = 'FROM';
    const KEYWORD_WHERE = 'WHERE';
    const KEYWORD_ORDER = 'ORDER';
    const KEYWORD_ORDER_ASC = 'ASC';
    const KEYWORD_ORDER_DESC = 'DESC';
    const KEYWORD_LIMIT = 'LIMIT';

    /**
     * @var array
     */
    private $filter = [];

    /**
     * @var array
     */
    private $options = [];

    private $operators = [
        '=' => '=',
        '<>' => '$ne',
        '!=' => '$ne',
        '>' => '$gt',
        '>=' => '$gte',
        '<' => '$lt',
        '<=' => '$lte',
    ];

    public function execute(array $parsed): ProcessorResult
    {
        if (!$this->isDatabaseSelected()) {
            return new ProcessorResult(ProcessorResult::TYPE_STRING, 'Use DB first');
        }

        if (!array_key_exists(static::KEYWORD_FROM, $parsed)) {
            return new ProcessorResult(ProcessorResult::TYPE_STRING, 'The SELECT query must have FROM sentence');
        }

        $fromTable = $parsed[static::KEYWORD_FROM][0]['table'];
        if (!$this->inListCollections($fromTable)) {
            return new ProcessorResult(
                ProcessorResult::TYPE_STRING,
                'Table ' . $fromTable . ' is not exists in ' . $this->database->getDatabaseName() . ' database'
            );
        }

        $this->setOptionSelect($parsed[static::KEYWORD_SELECT]);

        if (array_key_exists(static::KEYWORD_WHERE, $parsed)) {
            $this->setFilterWhere($parsed[static::KEYWORD_WHERE]);
        }

        if (array_key_exists(static::KEYWORD_ORDER, $parsed)) {
            $this->setOptionOrder($parsed[static::KEYWORD_ORDER]);
        }

        if (array_key_exists(static::KEYWORD_LIMIT, $parsed)) {
            $this->setOptionLimit($parsed[static::KEYWORD_LIMIT]);
        }

        return new ProcessorResult(
            ProcessorResult::TYPE_TABLE,
            '',
            $this->find($fromTable, $this->filter, $this->options)
        );
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


    private function setOptionSelect(array $select)
    {
        $selectExpr = mb_strtoupper($select[0]['base_expr']);
        if ($selectExpr == static::KEYWORD_ALL) {
            $this->options = [];
        } else {
            foreach ($select as $expression) {
                $this->options['projection'][$expression['base_expr']] = 1;
            }
            if (!array_key_exists('id', $this->options)) {
                $this->options['projection']['_id'] = 0;
            }
        }
    }

    private function setFilterWhere(array $where)
    {
        $expressions = [];

        $i = 0;
        $expressions[$i]['type'] = '';
        foreach ($where as $key => $expression) {
            if ($expression['expr_type'] == 'operator' && in_array($expression['base_expr'], ['and', 'or'])) {
                $i++;
                $logicOperator = $expression['base_expr'];
                $expressions[$i]['type'] = $logicOperator;
            }

            $expressions[$i][$expression['expr_type']] = $expression['base_expr'];
        }

        $first = [];
        $filter = [];
        foreach ($expressions as $expression) {
            $operator = $this->operators[$expression['operator']];
            if ($operator == '=') {
                $row = [$expression['colref'] => $expression['const']];
            } else {
                $row = [$expression['colref'] => [$operator => $expression['const']]];
            }

            if (empty($expression['type'])) {
                $first = $row;
            } else {
                if (isset($first)) {
                    $row = $first + $row;
                    unset($first);
                }

                $filter[] = [$expression['type'] => $row];
            }
        }
        $this->filter = $filter;
    }

    private function setOptionOrder(array $order)
    {
        foreach ($order as $expression) {
            if ($expression['base_expr'] == 'id') {
                $expression['base_expr'] = '_id';
            }
            $exprValue = ($expression['direction'] == static::KEYWORD_ORDER_ASC) ? 1 : -1;
            $this->options['sort'] = [$expression['base_expr'] => $exprValue];
        }
    }

    private function setOptionLimit(array $limit)
    {
        $offset = (int)$limit['offset'];
        $rowcount = (int)$limit['rowcount'];
        if (!empty($rowcount)) {
            $this->options['limit'] = $rowcount;
        }
        if (!empty($offset)) {
            $this->options['skip'] = $offset;
        }
    }
}