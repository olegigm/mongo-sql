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
        '=' => '',
        '<>' => '$ne',
        '!=' => '$ne',
        '>' => '$gt',
        '>=' => '$gte',
        '<' => '$lt',
        '<=' => '$lte',
        'in' => '$in',
        'and' => '$and',
        'or' => '$or'
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
                sprintf('Table %s is not exists in %s database', $fromTable, $this->database->getDatabaseName())
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

    /**
     * @param array $select
     */
    private function setOptionSelect(array $select)
    {
        $selectExpr = mb_strtoupper($select[0]['base_expr']);
        if ($selectExpr == static::KEYWORD_ALL) {
            $this->options = [];
        } else {
            foreach ($select as $expression) {
                $this->options['projection'][$expression['base_expr']] = 1;
            }
            if (!array_key_exists('id', $this->options['projection'])) {
                $this->options['projection']['_id'] = 0;
            }
        }
    }

    /**
     * @param array $where
     */
    private function setFilterWhere(array $where)
    {
        $filters = []; $i = 0;
        foreach ($where as $key => $expression) {
            if ($expression['expr_type'] == 'operator' && in_array($expression['base_expr'], ['and', 'or'])) {
                $filters[$i]['type'] = $expression['base_expr'];
                $i++;
                continue;
            }

            $filters[$i]['condition'][] = [
                'expr_type' => $expression['expr_type'],
                'base_expr' => $this->clearBaseExpr($expression)
            ];
        }

        $filters = $this->filterPrepare($filters);
        $filters = $this->filterBuild($filters);

        $this->filter = $filters;
    }

    /**
     * @param array $order
     */
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

    /**
     * @param array $limit
     */
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

    /**
     * Build filter
     * @param array $filters
     * @return array
     */
    private function filterBuild(array $filters) : array
    {
        $conditions = [];
        $filter = $filters[0];

        $typeKey = key($filter);
        $type = $this->operators[$typeKey];
        $items = [];
        if ($typeKey == 'and') {
            foreach ($filter[$typeKey] as $item) {
                $left = $item[0]['base_expr'];
                $signKey = $item[1]['base_expr'];
                $sign = $this->operators[$signKey];
                $right = $this->cast($item[2]['base_expr']);
                if (empty($sign)) {
                    $item = [$left => $right];
                } else {
                    $item = [$left => [$sign => $right]];
                }
                $items = $items + $item;
            }

            $conditions[$type][] = $items;

        } elseif ($typeKey == 'or') {
            foreach ($filter[$typeKey] as $key => $item) {
                $left = $key;
                $signKey = key($item);
                $sign = $this->operators[$signKey];
                $right = $this->cast($item[$signKey]);
                if ($signKey == 'in') {
                    $item = [$left => [$sign => $right]];
                } else {
                    if (is_array($right) && count($right) == 1) {
                        $right = $right[0];
                    }

                    if (empty($sign)) {
                        $item = [$left => $right];
                    } else {
                        $item = [$left => [$sign => $right]];
                    }
                }

                $items[] = $item;
            }

            $conditions[$type] = $items;
        }

        array_shift($filters);
        if (!empty($filters)) {
            $conditions[$type][] = $this->filterBuild($filters);
        }

        return $conditions;
    }

    /**
     * Prepare filter
     * @param $filters
     * @return array
     */
    private function filterPrepare($filters)
    {
        $conditions = [];
        $prevFilter = null;

        foreach ($filters as $filter) {
            if (!isset($filter['type'])) {
                $conditions[] = [$prevFilter['type'] => $filter];
            } else {
                $conditions[] = [$filter['type'] => $filter];
            }
            $prevFilter = $filter;
        }

        $filters = [];
        $prevFilter = null;
        $key = -1;
        foreach ($conditions as $filter) {
            $type = key($filter);
            if (!isset($prevType) || $prevType != $type) {
                $key++;
            }

            $filters[$key][$type][] = $filter[$type]['condition'];

            $prevType = $type;
        }

        $conditions = [];
        foreach ($filters as $filter) {
            if ('or' == key($filter)) {
                $filter = $this->orToIn($filter);
            }
            $conditions[] = $filter;
        }

        return $conditions;
    }

    /**
     * @param array $expression
     * @return string
     */
    private function clearBaseExpr(array $expression) : string
    {
        if ($expression['expr_type'] == 'const') {
            return trim($expression['base_expr'], '\'');
        }

        return $expression['base_expr'];
    }

    /**
     * Build in into or
     * @param $filter
     * @return array
     */
    private function orToIn($filter) : array
    {
        $or = $filter['or'];
        $in = [];
        foreach ($or as $item) {
                $in[$item[0]['base_expr']][] = $item;
        }

        if (count($or) == count($in)) {
            return $filter;
        }

        $conditions = [];
        foreach ($in as $key => $value) {
            if (count($value) > 1) {
                foreach ($value as $item) {
                    $sign = ($item[1]['base_expr'] == '=') ? 'in' : $item[1]['base_expr'];
                    $conditions[$key][$sign][] = $item[2]['base_expr'];
                }
            } else {
                $item = $value[0];
                $sign = $item[1]['base_expr'];
                $conditions[$key][$sign][] = $item[2]['base_expr'];
            }
        }

        if (count($conditions) > 1) {
            $filter['or'] = $conditions;
        } else {
            $filter = $conditions;
        }

        return $filter;
    }

    /**
     * Cast type of value
     * @param $value
     * @return float|int|mixed
     */
    private function cast($value)
    {
        $int = (int)$value;
        if ((string)$int === $value) {
            return $int;
        }

        $double = (double)$value;
        if ((string)$double === $value) {
            return $double;
        }

        return $value;
    }

}