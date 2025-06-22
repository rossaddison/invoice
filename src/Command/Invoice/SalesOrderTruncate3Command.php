<?php

declare(strict_types=1);

namespace App\Command\Invoice;

use App\Invoice\Entity\SalesOrder;
use App\Invoice\Entity\SalesOrderAmount;
use App\Invoice\Entity\SalesOrderItem;
use App\Invoice\Entity\SalesOrderItemAmount;
use App\Invoice\Entity\SalesOrderTaxRate;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Cycle\Command\CycleDependencyProxy;

final class SalesOrderTruncate3Command extends Command
{
    protected static string $defaultName = 'invoice/salesorder/truncate3';

    public function __construct(
        private CycleDependencyProxy $promise,
    ) {
        parent::__construct();
    }

    #[\Override]
    public function configure(): void
    {
        $this
            ->setDescription('Truncates, i.e removes all records, in the tables related to salesorders.')
            ->setHelp('sales_order_item_amount, sales_order_amount, sales_order_item, sales_order_tax_rate, sales_order tables will be truncated until there are no records left in them.');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** Note tables must be truncated in this sequence in order to avoid integrity constraint violations **/

        $io = new SymfonyStyle($input, $output);

        $tables = ['sales_order_item_amount', 'sales_order_amount', 'sales_order_item', 'sales_order_tax_rate', 'sales_order'];

        foreach ($tables as $table) {
            $this->promise
                ->getDatabaseProvider()
                ->database()
                ->delete($table)
                ->run();
        }

        if (0 === count(is_array($findAll = $this->promise
                ->getORM()
                ->getRepository(SalesOrderItemAmount::class)->findAll()) ? $findAll : iterator_to_array($findAll)) +
            count(is_array($findAll = $this->promise
                ->getORM()
                ->getRepository(SalesOrderAmount::class)->findAll()) ? $findAll : iterator_to_array($findAll)) +
            count(is_array($findAll = $this->promise
                ->getORM()
                ->getRepository(SalesOrderItem::class)->findAll()) ? $findAll : iterator_to_array($findAll)) +
            count(is_array($findAll = $this->promise
                ->getORM()
                ->getRepository(SalesOrderTaxRate::class)->findAll()) ? $findAll : iterator_to_array($findAll)) +
            count(is_array($findAll = $this->promise
                ->getORM()
                ->getRepository(SalesOrder::class)->findAll()) ? $findAll : iterator_to_array($findAll))
        ) {
            $io->success('Done');
            return ExitCode::OK;
        }
        $io->error('Unspecified error');
        return ExitCode::UNSPECIFIED_ERROR;
    }
}
