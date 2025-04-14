<?php

declare(strict_types=1);

namespace App\Command\Invoice;

use Cycle\Database\DatabaseManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Console\ExitCode;

final class AutoIncrementSetToOneAfterTruncate6Command extends Command
{
    protected static $defaultName = 'invoice/autoincrementsettooneafter/truncate6';

    public function __construct(
        private DatabaseManager $dbal
    ) {
        parent::__construct();
    }

    #[\Override]
    public function configure(): void
    {
        $this->setDescription('Ensures Autoincrement is 1 for each truncated table so that e.g. admin on id 1 can be assigned an admin role, and observer on id 2 can be assigned an observer role.');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $tables = $this->dbal->database('default')->getTables();
        
        foreach ($tables as $table) {
            $name = $table->getName();
            $this->dbal->database('default')->execute("ALTER TABLE `{$name}` AUTO_INCREMENT = 1");
            echo "Table '{$name}' AUTO_INCREMENT reset to 1.\n";
        }

        $io->success('Done');
        return ExitCode::OK;
    }
}
