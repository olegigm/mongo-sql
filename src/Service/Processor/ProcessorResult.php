<?php

namespace MongoSQL\Service\Processor;


class ProcessorResult
{
    const ALLOWED_TYPES = ['string', 'table'];

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