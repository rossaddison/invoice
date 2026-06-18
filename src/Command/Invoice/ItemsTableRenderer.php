<?php

declare(strict_types=1);

namespace App\Command\Invoice;

use App\Infrastructure\Persistence\AllowanceCharge\AllowanceCharge;
use App\Infrastructure\Persistence\InvAllowanceCharge\InvAllowanceCharge;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Infrastructure\Persistence\InvItemAllowanceCharge\InvItemAllowanceCharge;
use App\Infrastructure\Persistence\InvTaxRate\InvTaxRate;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Output\OutputInterface;

final class ItemsTableRenderer
{
    /**
     * @param InvTaxRate[] $invTaxRates
     * @param InvAllowanceCharge[] $invAllowanceCharges
     * @param AllowanceCharge[] $allowanceCharges
     * @param InvItem[] $invItems
     * @param InvItemAllowanceCharge[] $invItemAllowanceCharges
     * @param TaxRate[] $taxRates
     */
    public function __construct(
        private array $invTaxRates,
        private array $invAllowanceCharges,
        private array $allowanceCharges,
        private array $invItems,
        private array $invItemAllowanceCharges,
        private array $taxRates,
    ) {}

    /**
     * @return array{afterDiscount: float, itemTax: float}
     */
    public function renderInvItemTable(OutputInterface $output): array
    {
        $table = new Table($output);
        $table->setHeaders(
            [
                'Name',
                'Desc',
                'Qty',
                'P/U',
                '(D/U)',
                'Subtot',
                '(ESD)',
                'After Disc',
                'Tax %',
                'Tax',
                'Total',
            ],
        );

        $invItems = $this->invItems;
        $itemCount = count($invItems);
        $discountedSubTotal = 0.0;
        $itemTaxTotal = 0.0;
        $grandTotal = 0.0;

        foreach ($invItems as $index => $invItem) {
            $itemId = (int) $index + 1;
            $isLast = ((int) $index === $itemCount - 1);
            $totals = $this->renderInvItemRow($table, $invItem, $itemId, $isLast);
            $discountedSubTotal += $totals['discountedSubTotal'];
            $itemTaxTotal += $totals['itemTaxTotal'];
            $grandTotal += $totals['grandTotal'];
        }

        $table->addRow(new TableSeparator());
        $table->addRow(
            [
                '','','','','','','',
                "\033[32m" . $this->format($discountedSubTotal) . "\033[0m",
                '',
                "\033[32m" . $this->format($itemTaxTotal) . "\033[0m",
                "\033[32m" . $this->format($grandTotal) . "\033[0m",
            ],
        );

        $table->setColumnMaxWidth(2, 15);
        $table->setColumnMaxWidth(3, 15);
        $table->render();
        $output->writeln('');
        $output->writeln('Note: ESD = Early Settlement Discount');
        return [
            'afterDiscount' => $discountedSubTotal,
            'itemTax' => $itemTaxTotal,
        ];
    }

    /**
     * @param array{afterDiscount: float, itemTax: float} $summaryTableData
     */
    public function renderSummaryTable(OutputInterface $output, array $summaryTableData): void
    {
        $table = new Table($output);
        $table->addRow([
            'After Item Discount',
            "\033[32m" . $this->format($summaryTableData['afterDiscount']) . "\033[0m",
        ]);
        $table->addRow([
            'Add: Item Tax Total',
            "\033[32m" . $this->format($summaryTableData['itemTax']) . "\033[0m",
        ]);
        $table->addRow(new TableSeparator());
        $withItemTax = $summaryTableData['afterDiscount'] + $summaryTableData['itemTax'];
        $table->addRow([
            'With Item Tax',
            "\033[32m" . $this->format($withItemTax) . "\033[0m",
        ]);

        $table->addRow(new TableSeparator());
        $totalInvTaxRate = 0.0;
        $taxRates = $this->invTaxRates;
        $firstRate = $taxRates[0] ?? null;
        $secondRate = $taxRates[1] ?? null;
        foreach ($taxRates as $invTaxRate) {
            $totalInvTaxRate = $totalInvTaxRate + ($invTaxRate->getInvTaxRateAmount() ?? 0.00);
        }
        $table->addRow([
            'Invoice Taxes (20% '
                . $this->format($firstRate !== null ? ($firstRate->getInvTaxRateAmount() ?? 0.00) : 0.00)
                . ', 15% '
                . $this->format($secondRate !== null ? ($secondRate->getInvTaxRateAmount() ?? 0.00) : 0.00)
                . ')',
            $this->format($totalInvTaxRate),
        ]);

        $table->addRow(new TableSeparator());
        $invAllowanceChargeTotal = 0.0;
        foreach ($this->invAllowanceCharges as $invAllowanceCharge) {
            $amount = (float) $invAllowanceCharge->getAmount();
            $offSet = ($invAllowanceCharge->reqAllowanceChargeId()) - 1;
            $allowanceCharge = $this->allowanceCharges[$offSet] ?? null;
            $isCharge = $allowanceCharge?->getIdentifier() ?? false;
            $reason = $allowanceCharge?->getReason() ?? 'N/A';

            if ($isCharge) {
                $table->addRow([
                    'Add: ' . $reason . ' (charge)',
                    "\033[32m" . $this->format($amount) . "\033[0m",
                ]);
                $invAllowanceChargeTotal += $amount;
            } else {
                $table->addRow([
                    'Less: ' . $reason . ' (allowance)',
                    "\033[31m" . $this->formatBracketed($amount) . "\033[0m",
                ]);
                $invAllowanceChargeTotal -= $amount;
            }
        }
        $afterAllowanceCharge = $withItemTax + $totalInvTaxRate + $invAllowanceChargeTotal;
        $beforeDiscountTotal = $afterAllowanceCharge;
        $table->addRow(new TableSeparator());
        $table->addRow([
            'Before Invoice Discount Total',
            $this->format($beforeDiscountTotal),
        ]);
        $discount = 0.10 * $beforeDiscountTotal;
        $table->addRow([
            '(Invoice Discount as 10% of Before Discount Total)',
            "\033[31m" . $this->formatBracketed($discount) . "\033[0m",
        ]);
        $total = $beforeDiscountTotal - $discount;
        $table->addRow(new TableSeparator());
        $table->addRow([
            'Total',
            $this->format($total),
        ]);
        $table->render();
    }

    /**
     * @return array{discountedSubTotal: float, itemTaxTotal: float, grandTotal: float}
     */
    private function renderInvItemRow(
        Table $table,
        InvItem $invItem,
        int $itemId,
        bool $isLast,
    ): array {
        $quantity = $invItem->getQuantity() ?? 0;
        $price = $invItem->getPrice() ?? 0;
        $percentage = $invItem->reqTaxRateId() == '1' ? 15 : 20;
        $subTotal = $quantity * $price;
        $discount = 1;
        $netDiscount = ($quantity * ($price - $discount));
        $totalDiscount = $quantity * $discount;
        $itemTax = $netDiscount * ($percentage / 100);
        $itemTotal = $netDiscount + $itemTax;

        $hasAllowancesOrCharges = false;
        foreach ($this->invItemAllowanceCharges as $invItemAllowanceCharge) {
            if ($invItemAllowanceCharge->reqInvItemId() == $itemId) {
                $hasAllowancesOrCharges = true;
                break;
            }
        }

        $colorCode = $hasAllowancesOrCharges ? '' : "\033[35m";
        $colorEnd = $hasAllowancesOrCharges ? '' : "\033[0m";

        $table->addRow(
            [
                $colorCode . ($invItem->getName() ?? 'None Available') . $colorEnd,
                $colorCode . ($invItem->getDescription() ?? 'None Available') . $colorEnd,
                $colorCode . (float) $quantity . $colorEnd,
                $colorCode . $this->format((float) $price) . $colorEnd,
                $colorCode . $this->format($discount) . $colorEnd,
                $colorCode . $this->format($subTotal) . $colorEnd,
                "\033[31m" . $this->formatBracketed($totalDiscount) . "\033[0m",
                $colorCode . $this->format($subTotal - $totalDiscount) . $colorEnd,
                $colorCode . $this->format($percentage) . $colorEnd,
                $colorCode . $this->format($itemTax) . $colorEnd,
                $colorCode . $this->format($itemTotal) . $colorEnd,
            ],
        );

        $itemAllowanceAmount = 0.0;
        $itemAllowanceTax = 0.0;
        $itemChargeAmount = 0.0;
        $itemChargeTax = 0.0;

        foreach ($this->invItemAllowanceCharges as $invItemAllowanceCharge) {
            if ($invItemAllowanceCharge->reqInvItemId() != $itemId) {
                continue;
            }
            $allowanceChargeId = $invItemAllowanceCharge->reqAllowanceChargeId();
            $allowanceCharge = $this->allowanceCharges[$allowanceChargeId - 1] ?? null;
            $isCharge = $allowanceCharge?->getIdentifier() ?? false;
            $reason = $allowanceCharge?->getReason() ?? 'N/A';
            $amount = (float) $invItemAllowanceCharge->getAmount();
            $vatOrTax = (float) $invItemAllowanceCharge->getVatOrTax();
            $taxRateId = $allowanceCharge?->getTaxRateId() ?? 1;
            $taxRatePercent = 0;
            foreach ($this->taxRates as $taxRate) {
                if ($taxRate->reqId() === $taxRateId) {
                    $taxRatePercent = $taxRate->getTaxRatePercent();
                    break;
                }
            }
            if ($isCharge) {
                $itemChargeAmount += $amount;
                $itemChargeTax += $vatOrTax;
                $chargeTotal = $amount + $vatOrTax;
                $table->addRow([
                    '  -> ' . $reason,
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    "\033[32m" . $this->format($amount) . "\033[0m",
                    $this->format((float) ($taxRatePercent ?? 0.00)),
                    $this->format($vatOrTax),
                    "\033[32m" . $this->format($chargeTotal) . "\033[0m",
                ]);
            } else {
                $itemAllowanceAmount += $amount;
                $itemAllowanceTax += $vatOrTax;
                $allowanceTotal = $amount + $vatOrTax;
                $table->addRow([
                    '  -> ' . $reason,
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    "\033[31m" . $this->formatBracketed($amount) . "\033[0m",
                    $this->format((float) ($taxRatePercent ?? 0.00)),
                    "\033[31m" . $this->formatBracketed($vatOrTax) . "\033[0m",
                    "\033[31m" . $this->formatBracketed($allowanceTotal) . "\033[0m",
                ]);
            }
        }

        $grandTotal = $itemTotal;
        if ($itemAllowanceAmount > 0 || $itemChargeAmount > 0) {
            $subtotalTax = $itemTax + $itemChargeTax - $itemAllowanceTax;
            $subtotalAmount = $itemTotal + ($itemChargeAmount + $itemChargeTax) - ($itemAllowanceAmount + $itemAllowanceTax);
            $table->addRow([
                '  Subtotal',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                "\033[35m" . $this->format($subtotalTax) . "\033[0m",
                "\033[35m" . $this->format($subtotalAmount) . "\033[0m",
            ]);
            $grandTotal = $subtotalAmount;
        }

        if (!$isLast) {
            $table->addRow(new TableSeparator());
        }

        return [
            'discountedSubTotal' => $netDiscount + $itemChargeAmount - $itemAllowanceAmount,
            'itemTaxTotal' => $itemTax + $itemChargeTax - $itemAllowanceTax,
            'grandTotal' => $grandTotal,
        ];
    }

    private function format(float $number): string
    {
        $formatted_number = sprintf('%.2f', $number);
        return str_pad($formatted_number, 10, ' ', STR_PAD_LEFT);
    }

    private function formatBracketed(float $number): string
    {
        $formatted_number = sprintf('%.2f', -abs($number));
        return str_pad($formatted_number, 10, ' ', STR_PAD_LEFT);
    }
}
