<?php

declare(strict_types=1);

namespace App\Command\Invoice;

use App\Invoice\Entity\AllowanceCharge;
use App\Invoice\Entity\InvAllowanceCharge;
use App\Invoice\Entity\InvItemAllowanceCharge;
use App\Invoice\Entity\Inv;
use App\Invoice\Entity\InvAmount;
use App\Invoice\Entity\InvCustom;
use App\Invoice\Entity\InvItem;
use App\Invoice\Entity\InvItemAmount;
use App\Invoice\Entity\InvRecurring;
use App\Invoice\Entity\InvSentLog;
use App\Invoice\Entity\InvTaxRate;
use App\Invoice\Entity\Merchant;
use App\Invoice\Entity\Payment;
use App\Invoice\Entity\PaymentCustom;
use App\Invoice\Entity\PaymentMethod;
use App\Invoice\Entity\PaymentPeppol;
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

        if (0 === count(is_array($findAll = $this->promise
                ->getORM()
                ->getRepository(InvItemAllowanceCharge::class)->findAll()) ? $findAll : iterator_to_array($findAll)) +
            count(is_array($findAll = $this->promise
                ->getORM()
                ->getRepository(InvAllowanceCharge::class)->findAll()) ? $findAll : iterator_to_array($findAll)) +
            count(is_array($findAll = $this->promise
                ->getORM()
                ->getRepository(AllowanceCharge::class)->findAll()) ? $findAll : iterator_to_array($findAll)) +
            count(is_array($findAll = $this->promise
                ->getORM()
                ->getRepository(Merchant::class)->findAll()) ? $findAll : iterator_to_array($findAll)) +
            count(is_array($findAll = $this->promise
                ->getORM()
                ->getRepository(PaymentCustom::class)->findAll()) ? $findAll : iterator_to_array($findAll)) +
            count(is_array($findAll = $this->promise
                ->getORM()
                ->getRepository(Payment::class)->findAll()) ? $findAll : iterator_to_array($findAll)) +
            count(is_array($findAll = $this->promise
                ->getORM()
                ->getRepository(PaymentMethod::class)->findAll()) ? $findAll : iterator_to_array($findAll)) +
            count(is_array($findAll = $this->promise
                ->getORM()
                ->getRepository(PaymentPeppol::class)->findAll()) ? $findAll : iterator_to_array($findAll)) +
            count(is_array($findAll = $this->promise
                ->getORM()
                ->getRepository(InvRecurring::class)->findAll()) ? $findAll : iterator_to_array($findAll)) +
            count(is_array($findAll = $this->promise
                ->getORM()
                ->getRepository(InvSentLog::class)->findAll()) ? $findAll : iterator_to_array($findAll)) +
            count(is_array($findAll = $this->promise
                ->getORM()
                ->getRepository(InvItemAmount::class)->findAll()) ? $findAll : iterator_to_array($findAll)) +
            count(is_array($findAll = $this->promise
                ->getORM()
                ->getRepository(InvAmount::class)->findAll()) ? $findAll : iterator_to_array($findAll)) +
            count(is_array($findAll = $this->promise
                ->getORM()
                ->getRepository(InvItem::class)->findAll()) ? $findAll : iterator_to_array($findAll)) +
            count(is_array($findAll = $this->promise
                ->getORM()
                ->getRepository(InvTaxRate::class)->findAll()) ? $findAll : iterator_to_array($findAll)) +
            count(is_array($findAll = $this->promise
                ->getORM()
                ->getRepository(InvCustom::class)->findAll()) ? $findAll : iterator_to_array($findAll)) +
            count(is_array($findAll = $this->promise
                ->getORM()
                ->getRepository(Inv::class)->findAll()) ? $findAll : iterator_to_array($findAll))) {
            $io->success('Done');
            return ExitCode::OK;
        }
        $io->error('Unspecified error');
        return ExitCode::UNSPECIFIED_ERROR;
    }
}
