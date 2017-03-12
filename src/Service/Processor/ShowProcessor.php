<?php

namespace MongoSQL\Service\Processor;


class ShowProcessor extends AbstractProcessor
{
    const KEYWORD_SHOW = 'SHOW';
    const KEYWORD_DATABASES = 'DATABASES';
    const KEYWORD_TABLES = 'TABLES';

    /**
     * @param array $parsed
     * @return ProcessorResult
     */
    public function execute(array $parsed): ProcessorResult
    {
        $expr = mb_strtoupper($parsed[static::KEYWORD_SHOW][0]['base_expr']);
        if ($expr == static::KEYWORD_DATABASES) {
            $result = $this->listDatabases();
            return new ProcessorResult('string', $result);
        }

        if ($expr == static::KEYWORD_TABLES) {
            if (!$this->isDatabaseSelected()) {
                return new ProcessorResult('string', 'Use DB first');
            }

            $result = $this->listCollections();
            return new ProcessorResult('string', $result);
        }

        return new ProcessorResult('string', 'Unknown query');
    }
}