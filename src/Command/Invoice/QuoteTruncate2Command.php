<?php

declare(strict_types=1);

namespace App\Command\Invoice;

use App\Invoice\Entity\Quote;
use App\Invoice\Entity\QuoteAmount;
use App\Invoice\Entity\QuoteItem;
use App\Invoice\Entity\QuoteItemAmount;
use App\Invoice\Entity\QuoteTaxRate;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Cycle\Command\CycleDependencyProxy;

final class QuoteTruncate2Command extends Command
{
    protected static $defaultName = 'invoice/quote/truncate2';

    public function __construct(
        private CycleDependencyProxy $promise,
    ) {
        parent::__construct();
    }

    #[\Override]
    public function configure(): void
    {
        $this
            ->setDescription('Truncates, i.e removes all records, in the tables related to quotes.')
            ->setHelp('quote_item_amount, quote_amount, quote_item, quote_tax_rate, quote tables will be truncated until there are no records left in them.');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** Note tables must be truncated in this sequence in order to avoid integrity constraint violations **/

        $io = new SymfonyStyle($input, $output);

        $tables = ['quote_item_amount', 'quote_amount', 'quote_item', 'quote_tax_rate', 'quote_custom', 'quote'];

        foreach ($tables as $table) {
            $this->promise
                ->getDatabaseProvider()
                ->database()
                ->delete($table)
                ->run();
        }
        
        if (0 === count(is_array($findAll = $this->promise
                ->getORM()
                ->getRepository(QuoteItemAmount::class)->findAll()) ? $findAll : iterator_to_array($findAll)) +
            count(is_array($findAll = $this->promise
                ->getORM()
                ->getRepository(QuoteAmount::class)->findAll()) ? $findAll : iterator_to_array($findAll)) +
            count(is_array($findAll = $this->promise
                ->getORM()
                ->getRepository(QuoteItem::class)->findAll()) ? $findAll : iterator_to_array($findAll)) +
            count(is_array($findAll = $this->promise
                ->getORM()
                ->getRepository(QuoteTaxRate::class)->findAll()) ? $findAll : iterator_to_array($findAll)) +
            count(is_array($findAll = $this->promise
                ->getORM()
                ->getRepository(Quote::class)->findAll()) ? $findAll : iterator_to_array($findAll))
        ) {
            $io->success('Done');
            return ExitCode::OK;
        }
        $io->error('Unspecified error');
        return ExitCode::UNSPECIFIED_ERROR;
    }
}
