<?php

declare(strict_types=1);

namespace App\Command\Invoice;

use App\Infrastructure\Persistence\{
    AllowanceCharge\AllowanceCharge, InvAllowanceCharge\InvAllowanceCharge,
    InvItemAllowanceCharge\InvItemAllowanceCharge, Inv\Inv,
    InvAmount\InvAmount, InvCustom\InvCustom, InvItem\InvItem,
    InvItemAmount\InvItemAmount, InvRecurring\InvRecurring,
    InvSentLog\InvSentLog, InvTaxRate\InvTaxRate
};
use App\Infrastructure\Persistence\Merchant\Merchant;
use App\Infrastructure\Persistence\Payment\Payment;
use App\Infrastructure\Persistence\PaymentCustom\PaymentCustom;
use App\Infrastructure\Persistence\PaymentMethod\PaymentMethod;
use App\Infrastructure\Persistence\PaymentPeppol\PaymentPeppol;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Cycle\Command\CycleDependencyProxy;

final class InvTruncate1Command extends Command
{
    protected static string $defaultName = 'invoice/inv/truncate1';

    public function __construct(
        private readonly CycleDependencyProxy $promise,
    ) {
        parent::__construct();
    }

    #[\Override]
    public function configure(): void
    {
        $this
            ->setDescription('Truncates, i.e removes all records, in the tables related to invoices.')
            ->setHelp('inv_item_allowance_charge, inv_allowance_charge, allowance_charge, merchant, payment_custom, payment, payment_method, payment_peppol, inv_recurring, inv_sent_log, inv_item_amount, inv_amount, inv_item, inv_tax_rate, inv tables will be truncated until there are no records left in them.');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** Note tables must be truncated in this sequence in order to avoid integrity constraint violations **/

        $io = new SymfonyStyle($input, $output);

        $tables = ['inv_item_allowance_charge', 'inv_allowance_charge', 'allowance_charge', 'merchant', 'payment_custom', 'payment', 'payment_method', 'payment_peppol', 'inv_recurring', 'inv_sent_log', 'inv_item_amount', 'inv_amount', 'inv_item', 'inv_tax_rate', 'inv_custom', 'inv'];

        foreach ($tables as $table) {
            $this->promise
                ->getDatabaseProvider()
                ->database()
                ->delete($table)
                ->run();
        }

        $entityClasses = [
            InvItemAllowanceCharge::class, InvAllowanceCharge::class,
            AllowanceCharge::class, Merchant::class, PaymentCustom::class,
            Payment::class, PaymentMethod::class, PaymentPeppol::class,
            InvRecurring::class, InvSentLog::class, InvItemAmount::class,
            InvAmount::class, InvItem::class, InvTaxRate::class,
            InvCustom::class, Inv::class,
        ];

        $totalCount = 0;
        foreach ($entityClasses as $entityClass) {
            $findAll = $this->promise->getORM()->getRepository($entityClass)->findAll();
            $totalCount += count(is_array($findAll) ? $findAll : iterator_to_array($findAll));
        }

        if (0 === $totalCount) {
            $io->success('Done');
            return ExitCode::OK;
        }
        $io->error('Unspecified error');
        return ExitCode::UNSPECIFIED_ERROR;
    }
}
