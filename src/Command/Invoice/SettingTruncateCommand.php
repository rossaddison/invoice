<?php

declare(strict_types=1);

namespace App\Command\Invoice;

use App\Invoice\Entity\Setting;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Cycle\Command\CycleDependencyProxy;

final class SettingTruncateCommand extends Command
{
    protected static $defaultName = 'invoice/setting/truncate';

    public function __construct(
        private CycleDependencyProxy $promise,
    ) {
        parent::__construct();
    }

    #[\Override]
    public function configure(): void
    {
        $this
            ->setDescription('Truncates, i.e removes all records, in the setting table.')
            ->setHelp('The setting table is truncated.');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** Note tables must be truncated in this sequence in order to avoid integrity constraint violations **/

        $io = new SymfonyStyle($input, $output);

        $tables = ['setting'];

        foreach ($tables as $table) {
            $this->promise
                ->getDatabaseProvider()
                ->database()
                ->delete($table)
                ->run();
        }

        $findAll = $this->promise
                ->getORM()
                ->getRepository(Setting::class)->findAll();
        if (0 === count(is_array($findAll) ? $findAll : iterator_to_array($findAll))
        ) {
            $io->success('Done');
            return ExitCode::OK;
        }
        $io->error('Unspecified error');
        return ExitCode::UNSPECIFIED_ERROR;
    }
}
