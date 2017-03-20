<?php

namespace MongoSQL\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use MongoSQL\Service\QueryHandler;
use MongoSQL\Service\Processor\ProcessorResult;
use MongoSQL\Service\Exception\UnknownResultType;

class QueryCommand extends Command
{
    /**
     * @var QueryHandler
     */
    private $queryHandler;

    public function __construct($queryHandler, $name = null)
    {
        parent::__construct($name);

        $this->queryHandler = $queryHandler;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName("query")
            ->setDescription("Query to MongoDB")
            ->setDefinition([])
            ->setHelp(<<<EOT
The <info>query</info> command provides query API to MongoDB in SQL type 
EOT
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $command = '';
        $dialog = $this->getHelper('question');
        $question = new Question('> ');

        while (trim($command) != 'exit') {
            $answer = $dialog->ask($input, $output, $question);

            $command .= ' ' . trim($answer);
            if (substr($answer, -1) == ';') {
                $output->writeln($command);

                // parse query
                /** @var ProcessorResult */
                $result = $this->queryHandler->handle($command);
                switch ($result->getType()) {
                    case ProcessorResult::TYPE_STRING :
                        $output->writeln($result->getStrData());
                        break;
                    case ProcessorResult::TYPE_TABLE :
                        $this->tableRender($output, $result->getTableData());
                        break;
                    default :
                        throw new UnknownResultType($result->getType());
                }

                $command = '';
            }
        }

        $output->writeln('bye');
    }

    private function tableRender(OutputInterface $output, array $data)
    {
        $rows = [];
        $data = json_decode(json_encode($data), true);
        $headers = array_keys($data[0]);

        foreach ($data as $key => $row) {
            foreach ($headers as $column) {
                if (array_key_exists($column, $row)) {
                    if ($column == '_id') {
                        $rows[$key][] = $row[$column]['$oid'];
                    } else {
                        $rows[$key][] = (is_array($row[$column])) ? json_encode($row[$column]) : $row[$column];
                    }
                } else {
                    $rows[$key][] = '';
                }
            }
        }

        foreach ($headers as $key => $header) {
            if ($header == '_id') {
                $headers[$key] = 'id';
                break;
            }
        }

        $table = new Table($output);
        $table->setHeaders($headers);
        $table->addRows($rows);
        $table->render();
    }
}