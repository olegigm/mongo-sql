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
            $exprType = mb_strtolower($expression['base_expr']);
            if ($expression['expr_type'] == 'operator' && in_array($exprType, ['and', 'or'])) {
                $filters[$i]['type'] = $exprType;
                $i++;
                continue;
            }

            $filters[$i]['condition'][] = [
                'expr_type' => $expression['expr_type'],
                'base_expr' => $this->clearBaseExpr($expression)
            ];
        }

        $filters = $this->prepareFilter($filters);
        $filters = $this->buildFilter($filters);

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
    private function buildFilter(array $filters) : array
    {
        $conditions = [];
        $filter = $filters[0];

        $typeKey = key($filter);
        $type = $this->operators[$typeKey];
        $items = [];
        if ($typeKey == 'and') {
            $items = $this->buildAndFilter($filter, $typeKey);
        } elseif ($typeKey == 'or') {
            $items = $this->buildOrFilter($filter, $typeKey);
        }
        $conditions[$type] = $items;

        array_shift($filters);
        if (!empty($filters)) {
            $conditions[$type][] = $this->buildFilter($filters);
        }

        return $conditions;
    }

    /**
     * Prepare filter
     * @param $filters
     * @return array
     */
    private function prepareFilter($filters)
    {
        $filters = $this->typingFilter($filters);
        $filters = $this->compactFilter($filters);

        $conditions = [];
        foreach ($filters as $filter) {
            if ('and' == key($filter)) {
                $filter = $this->gtLt($filter);
            }
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
     * Prepare gt & lt filter
     * @param array $filter
     * @return array
     */
    private function gtLt(array $filter) : array
    {
        $and = $filter['and'];
        $gtLt = [];

        foreach ($and as $item) {
            $gtLt[$item[0]['base_expr']][] = $item;
        }

        $conditions = [];
        foreach ($gtLt as $key => $value) {
            foreach ($value as $element) {
                $item = $element;
                $sign = $item[1]['base_expr'];
                $conditions[$key][$sign][] = $item[2]['base_expr'];
            }
        }

        $filter['and'] = $conditions;

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

    /**
     * @param $value array|mixed
     * @return array|mixed
     */
    private function takeValue($value)
    {
        if (is_array($value) && count($value) == 1 && array_key_exists(0, $value)) {
            $value = $value[0];
        }

        return $value;
    }

    /**
     * @param array $filter
     * @param $typeKey
     * @return array
     */
    private function buildAndFilter(array $filter, $typeKey) : array
    {
        $items = [];

        foreach ($filter[$typeKey] as $key => $item) {
            $left = $key;
            $itemValues = [];
            foreach ($item as $operatorKey => $value) {
                $operator = $this->operators[$operatorKey];
                $value = $this->takeValue($value);
                $value = $this->cast($value);
                if (empty($operator)) {
                    $itemValues[] = $value;
                } else {
                    $itemValues[$operator] = $value;
                }
            }

            $itemValues = $this->takeValue($itemValues);

            if (empty($sign)) {
                $item = [$left => $itemValues];
            } else {
                $item = [$left => [$sign => $itemValues]];
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * @param array $filter
     * @param $typeKey
     * @return array
     */
    private function buildOrFilter(array $filter, $typeKey) : array
    {
        $items = [];

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

        return $this->takeValue($items);
    }

    /**
     * Set types to all filters
     * @param array $filters
     * @return array
     */
    private function typingFilter(array $filters) : array
    {
        $defaultFilterType = 'and';
        $conditions = [];
        $prevFilter = [];

        foreach ($filters as $filter) {
            if (isset($filter['type'])) {
                $conditions[] = [$filter['type'] => $filter];
            } else {
                if (isset($prevFilter['type'])) {
                    $conditions[] = [$prevFilter['type'] => $filter];
                } else {
                    $conditions[] = [$defaultFilterType => $filter];
                }
            }
            $prevFilter = $filter;
        }

        return $conditions;
    }

    /**
     * @param array $filters
     * @return array
     */
    private function compactFilter(array $filters) : array
    {
        $conditions = [];
        $key = -1;
        foreach ($filters as $filter) {
            $type = key($filter);
            if (!isset($prevType) || $prevType != $type) {
                $key++;
            }

            $conditions[$key][$type][] = $filter[$type]['condition'];

            $prevType = $type;
        }

        return $conditions;
    }

}