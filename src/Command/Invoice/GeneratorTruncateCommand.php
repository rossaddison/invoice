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

    #[\Override]
    public function configure(): void
    {
        $this
            ->setDescription('Truncates, i.e removes all records, in the tables related to generating code through the Generator.')
            ->setHelp('The specific tables Gentor and GentorRelation are truncated.');
    }

    #[\Override]
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

        if (0 === count(is_array($findAll = $this->promise
                ->getORM()
                ->getRepository(GentorRelation::class)->findAll()) ? $findAll : iterator_to_array($findAll)) +
            count(is_array($findAll = $this->promise
                ->getORM()
                ->getRepository(Gentor::class)->findAll()) ? $findAll : iterator_to_array($findAll))
        ) {
            $io->success('Done');
            return ExitCode::OK;
        }
        $io->error('Unspecified error');
        return ExitCode::UNSPECIFIED_ERROR;
    }
}
