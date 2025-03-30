<?php

declare(strict_types=1);

namespace App\Command\Invoice;

use App\Invoice\Entity\Gentor;
use App\Invoice\Entity\GentorRelation;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Cycle\Command\CycleDependencyProxy;

final class GeneratorTruncateCommand extends Command
{
    protected static $defaultName = 'invoice/generator/truncate';

    public function __construct(
        private CycleDependencyProxy $promise,
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this
            ->setDescription('Truncates, i.e removes all records, in the tables related to generating code through the Generator.')
            ->setHelp('The specific tables Gentor and GentorRelation are truncated.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** Note tables must be truncated in this sequence in order to avoid integrity constraint violations **/

        $io = new SymfonyStyle($input, $output);

        $tables = ['gentor_relation', 'gentor'];

        foreach ($tables as $table) {
            $this->promise
                ->getDatabaseProvider()
                ->database()
                ->delete($table)
                ->run();
        }

        if (0 === count($this->promise
                ->getORM()
                ->getRepository(GentorRelation::class)->findAll()) +
            count($this->promise
                ->getORM()
                ->getRepository(Gentor::class)->findAll())
        ) {
            $io->success('Done');
            return ExitCode::OK;
        }
        $io->error('Unspecified error');
        return ExitCode::UNSPECIFIED_ERROR;
    }
}
