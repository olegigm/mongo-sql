<?php

namespace MongoSQL\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class QueryCommand extends Command
{
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
                // parse query
                $output->writeln($command);
                $command = '';
            }
        }

        $output->writeln('bye');
    }
}