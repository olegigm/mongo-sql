<?php

namespace MongoSQL\Service\Processor;


class ProcessorResult
{
    const TYPE_STRING = 'string';
    const TYPE_TABLE = 'table';

    /** @var  string */
    private $type;

    /** @var  string */
    private $strData;

    /** @var  array */
    private $tableData;

    public function __construct(string $type, string $stringData = '', array $tableData = [])
    {
        $this->type = $type;
        $this->strData = $stringData;
        $this->tableData = $tableData;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string|array|null
     */
    public function getData()
    {
        switch ($this->type) {
            case static::TYPE_STRING :
                return $this->getStrData();
                break;
            case static::TYPE_TABLE :
                return $this->getTableData();
                break;
        }

        return null;
    }

    /**
     * @return string
     */
    public function getStrData(): string
    {
        return $this->strData;
    }

    /**
     * @return array
     */
    public function getTableData(): array
    {
        return $this->tableData;
    }
}