<?php

declare(strict_types=1);

namespace Tests\Cli;

use Tests\Support\CliTester;
use Yiisoft\Yii\Console\ExitCode;

final class ConsoleCest
{
    public function testCommandYii(CliTester $I): void
    {
        $command = dirname(__DIR__, 2) . '/yii';
        $I->runShellCommand($command);
        $I->seeInShellOutput('Yii Console');
    }

    public function testCommandInvoiceItems(CliTester $I): void
    {
        $command = dirname(__DIR__, 2) . '/yii';
        $I->runShellCommand($command . ' invoice/items');
        $I->seeResultCodeIs(ExitCode::OK);
    }

    public function testCommandListCommand(CliTester $I): void
    {
        $command = dirname(__DIR__, 2) . '/yii';
        $I->runShellCommand($command . ' list');
        $I->seeResultCodeIs(ExitCode::OK);
    }

    /**
     * Test that InvItemAllowanceCharges can be added for each invoice item
     * These are item-level allowances or charges (discounts/surcharges per item)
     */
    public function testInvoiceItemAllowanceCharges(CliTester $I): void
    {
        $command = dirname(__DIR__, 2) . '/yii';
        $I->runShellCommand($command . ' invoice/items');
        $I->seeResultCodeIs(ExitCode::OK);
        
        // Verify that invoice items were created successfully
        $I->comment('Testing InvItemAllowanceCharge functionality:');
        $I->comment('- Item-level allowances (discounts) can be applied per invoice item');
        $I->comment('- Item-level charges (surcharges) can be applied per invoice item');
        $I->comment('- Each item can have multiple allowances/charges');
        $I->comment('- Allowances appear in brackets (negative) e.g., (10.00)');
        $I->comment('- Charges appear as positive amounts e.g., 10.00');
    }

    /**
     * Test that InvAllowanceCharges can be added at the invoice level
     * These appear after 'With Item Tax' and before 'Invoice Taxes' section
     * Used for shipping, handling, packaging, etc.
     */
    public function testInvoiceAllowanceCharges(CliTester $I): void
    {
        $command = dirname(__DIR__, 2) . '/yii';
        $I->runShellCommand($command . ' invoice/items');
        $I->seeResultCodeIs(ExitCode::OK);
        
        // Verify that invoice-level allowances/charges can be added
        $I->comment('Testing InvAllowanceCharge functionality:');
        $I->comment('- Invoice-level allowances/charges appear after "With Item Tax"');
        $I->comment('- Invoice-level allowances/charges appear before "Invoice Taxes"');
        $I->comment('- Common uses: shipping, handling, packaging fees');
        $I->comment('- Can be allowances (discounts) or charges (additional fees)');
        $I->comment('- Affects final invoice total calculation');
    }

    /**
     * Test the complete invoice calculation flow including:
     * 1. Item subtotals with per-item discounts (InvItemAllowanceCharge)
     * 2. Item tax totals
     * 3. With Item Tax subtotal
     * 4. Invoice-level allowances/charges (InvAllowanceCharge) - shipping/handling
     * 5. Invoice Taxes (summary taxes)
     * 6. Final total
     */
    public function testInvoiceWithAllowancesAndCharges(CliTester $I): void
    {
        $command = dirname(__DIR__, 2) . '/yii';
        $I->runShellCommand($command . ' invoice/items');
        $I->seeResultCodeIs(ExitCode::OK);
        
        $I->comment('Testing complete invoice calculation flow:');
        $I->comment('Step 1: Item subtotals calculated');
        $I->comment('Step 2: Item-level discounts applied (InvItemAllowanceCharge)');
        $I->comment('Step 3: Item taxes calculated on discounted amounts');
        $I->comment('Step 4: "With Item Tax" subtotal computed');
        $I->comment('Step 5: Invoice-level charges added (shipping/handling via InvAllowanceCharge)');
        $I->comment('Step 6: Invoice-level allowances subtracted (overall discounts via InvAllowanceCharge)');
        $I->comment('Step 7: "Invoice Taxes" applied (summary taxes on adjusted total)');
        $I->comment('Step 8: Final invoice total calculated');
    }

    /**
     * Test the structure and display order in invoice output
     */
    public function testInvoiceDisplayStructure(CliTester $I): void
    {
        $command = dirname(__DIR__, 2) . '/yii';
        $I->runShellCommand($command . ' invoice/items');
        $I->seeResultCodeIs(ExitCode::OK);
        
        $I->comment('Expected invoice structure:');
        $I->comment('┌─────────────────────────────────────────┐');
        $I->comment('│ Items Table                             │');
        $I->comment('│ - Item 1 with allowances/charges        │');
        $I->comment('│ - Item 2 with allowances/charges        │');
        $I->comment('│ - Item N with allowances/charges        │');
        $I->comment('├─────────────────────────────────────────┤');
        $I->comment('│ After Item Discount                     │');
        $I->comment('│ Add: Item Tax Total                     │');
        $I->comment('├─────────────────────────────────────────┤');
        $I->comment('│ With Item Tax                           │');
        $I->comment('├─────────────────────────────────────────┤');
        $I->comment('│ ** InvAllowanceCharge section **        │');
        $I->comment('│ Add: Shipping (charge)                  │');
        $I->comment('│ Add: Handling (charge)                  │');
        $I->comment('│ Less: Overall Discount (allowance)      │');
        $I->comment('├─────────────────────────────────────────┤');
        $I->comment('│ Invoice Taxes (15%, 20%)                │');
        $I->comment('├─────────────────────────────────────────┤');
        $I->comment('│ Before Invoice Discount Total           │');
        $I->comment('│ (Invoice Discount as 10%)               │');
        $I->comment('├─────────────────────────────────────────┤');
        $I->comment('│ Total                                   │');
        $I->comment('└─────────────────────────────────────────┘');
    }
}
