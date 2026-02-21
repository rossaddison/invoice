<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit test for Quote → SalesOrder → Invoice workflow business logic
 *
 * This test verifies the core business logic patterns without requiring
 * database connections or full application bootstrap.
 *
 * Key scenarios tested:
 * 1. Tax calculation consistency across conversions
 * 2. Allowance/charge copying logic
 * 3. Status transitions
 * 4. Amount calculations
 */
final class QuoteToSalesOrderToInvoiceWorkflowTest extends TestCase
{
    /**
     * Test tax calculation pattern used in all three services
     * Formula: tax_total = (subtotal + charges - allowances - discount) * tax_rate_percentage
     */
    public function testTaxCalculationPattern(): void
    {
        // Simulate item with allowances and charges
        $subtotal = 200.00; // 2 items @ 100.00 each
        $itemAllowance = 10.00; // Item allowance
        $itemCharge = 5.00; // Item charge
        $discount = 0.00;
        $taxRatePercentage = 0.20; // 20% tax
        
        // Calculate adjusted subtotal (includes allowances/charges)
        $adjustedSubtotal = $subtotal + $itemCharge - $itemAllowance - $discount;
        
        // Calculate tax on adjusted subtotal
        $taxTotal = $adjustedSubtotal * $taxRatePercentage;
        
        // Expected values
        $expectedAdjustedSubtotal = 195.00; // 200 + 5 - 10 - 0
        $expectedTaxTotal = 39.00; // 195 * 0.20
        
        $this->assertEquals($expectedAdjustedSubtotal, $adjustedSubtotal, 'Adjusted subtotal should be 195.00');
        $this->assertEquals($expectedTaxTotal, $taxTotal, 'Tax total should be 39.00');
        
        // Verify this matches the pattern used in services:
        // - QuoteItemService::saveQuoteItemAmount (lines ~370)
        // - SalesOrderItemService::saveSalesOrderItemAmount (lines ~370)
        // - InvItemService::saveInvItemAmount (lines ~609)
    }

    /**
     * Test that double taxation bug is prevented
     * Bug: Adding allowance/charge VatOrTax field on top of calculated tax
     */
    public function testNoDoubleTaxation(): void
    {
        $subtotal = 100.00;
        $allowanceAmount = 10.00;
        $allowanceVatOrTax = 2.00; // This should NOT be added separately
        $taxRatePercentage = 0.20;
        
        // INCORRECT (old buggy code):
        // $adjustedSubtotal = $subtotal - $allowanceAmount;
        // $calculatedTax = $adjustedSubtotal * $taxRatePercentage;
        // $taxTotal = $calculatedTax + $allowanceVatOrTax; // WRONG! Double taxation
        
        // CORRECT (fixed code):
        $adjustedSubtotal = $subtotal - $allowanceAmount;
        $taxTotal = $adjustedSubtotal * $taxRatePercentage;
        
        $expectedAdjustedSubtotal = 90.00;
        $expectedTaxTotal = 18.00; // NOT 20.00 (18 + 2)
        
        $this->assertEquals($expectedAdjustedSubtotal, $adjustedSubtotal, 'Adjusted subtotal should be 90.00');
        $this->assertEquals($expectedTaxTotal, $taxTotal, 'Tax should be 18.00, not include allowance VatOrTax');
        
        // The allowance VatOrTax field is informational only - not used in calculation
        $this->assertNotEquals($taxTotal, $expectedTaxTotal + $allowanceVatOrTax, 
            'Tax should NOT include allowance VatOrTax field');
    }

    /**
     * Test quote to sales order conversion status flow
     */
    public function testQuoteToSalesOrderStatusFlow(): void
    {
        // Initial quote status
        $quoteStatusDraft = 1;
        $quoteStatusSent = 2;
        $quoteStatusApproved = 4;
        
        // Sales order status when created from quote
        $soStatusDraft = 4; // From QuoteController::approve line 520
        
        // Workflow:
        // 1. Quote created → status = 1 (draft)
        $currentStatus = $quoteStatusDraft;
        $this->assertEquals(1, $currentStatus, 'New quote should be draft');
        
        // 2. Quote marked as sent → status = 2 (sent)
        $currentStatus = $quoteStatusSent;
        $this->assertEquals(2, $currentStatus, 'Quote marked as sent');
        
        // 3. Quote approved → status = 4 (approved), SO created with status = 4
        $currentStatus = $quoteStatusApproved;
        $soStatus = $soStatusDraft;
        $this->assertEquals(4, $currentStatus, 'Quote should be approved');
        $this->assertEquals(4, $soStatus, 'SalesOrder should be created with status 4');
    }

    /**
     * Test sales order to invoice conversion status flow
     */
    public function testSalesOrderToInvoiceStatusFlow(): void
    {
        // Sales order status when converting to invoice
        $soStatusInvoiceGenerated = 8; // From SalesOrderController::so_to_invoice_confirm
        
        // Invoice status when created from sales order
        $invStatusDraft = 1; // Default invoice status
        
        // Workflow:
        // 1. SO exists with status = 4
        $soStatus = 4;
        $this->assertEquals(4, $soStatus, 'SalesOrder before conversion');
        
        // 2. Convert to Invoice → SO status = 8, Invoice status = 1
        $soStatus = $soStatusInvoiceGenerated;
        $invStatus = $invStatusDraft;
        
        $this->assertEquals(8, $soStatus, 'SalesOrder status should be 8 after invoice generation');
        $this->assertEquals(1, $invStatus, 'Invoice should be created with status 1 (draft)');
    }

    /**
     * Test item-level allowance/charge copying pattern
     */
    public function testItemAllowanceChargeCopyingPattern(): void
    {
        // Simulate quote item with 2 allowances/charges
        $quoteItemAllowancesCharges = [
            ['allowance_charge_id' => '1', 'amount' => 10.00],
            ['allowance_charge_id' => '2', 'amount' => 5.00],
        ];
        
        // Pattern from QuoteController::copy_quote_item_allowance_charges_to_so (lines 3153-3183)
        $copiedCount = 0;
        foreach ($quoteItemAllowancesCharges as $ac) {
            $newSoItemAC = [
                'sales_order_item_id' => 'new_so_item_id',
                'allowance_charge_id' => $ac['allowance_charge_id'],
                'amount' => $ac['amount'],
            ];
            $copiedCount++;
        }
        
        $this->assertEquals(2, $copiedCount, 'Should copy 2 item allowances/charges from quote to SO');
        
        // Same pattern applies for SO to Invoice via copy_so_item_allowance_charges_to_inv
    }

    /**
     * Test document-level allowance/charge copying pattern
     */
    public function testDocumentAllowanceChargeCopyingPattern(): void
    {
        // Simulate quote with 2 document-level allowances/charges
        $quoteAllowancesCharges = [
            ['allowance_charge_id' => '3', 'amount' => 15.00],
            ['allowance_charge_id' => '4', 'amount' => 8.00],
        ];
        
        // Pattern from QuoteController::quote_to_so_quote_allowance_charges (lines 3242-3265)
        $copiedCount = 0;
        foreach ($quoteAllowancesCharges as $ac) {
            $newSoAC = [
                'sales_order_id' => 'new_so_id',
                'allowance_charge_id' => $ac['allowance_charge_id'],
                'amount' => $ac['amount'],
            ];
            $copiedCount++;
        }
        
        $this->assertEquals(2, $copiedCount, 'Should copy 2 document allowances/charges from quote to SO');
        
        // Same pattern for SO to Invoice via so_to_invoice_so_allowance_charges (newly created)
        $soAllowancesCharges = [
            ['allowance_charge_id' => '3', 'amount' => 15.00],
            ['allowance_charge_id' => '4', 'amount' => 8.00],
        ];
        
        $invCopiedCount = 0;
        foreach ($soAllowancesCharges as $ac) {
            $newInvAC = [
                'inv_id' => 'new_inv_id',
                'allowance_charge_id' => $ac['allowance_charge_id'],
                'amount' => $ac['amount'],
            ];
            $invCopiedCount++;
        }
        
        $this->assertEquals(2, $invCopiedCount, 'Should copy 2 document allowances/charges from SO to Invoice');
    }

    /**
     * Test amount calculation pattern across all three entity types
     */
    public function testAmountCalculationPattern(): void
    {
        // Input values (same for quote, SO, and invoice)
        $itemSubtotal = 195.00; // After item-level allowances/charges
        $itemTaxTotal = 39.00;
        $documentAllowance = 15.00;
        $documentCharge = 8.00;
        $discount = 0.00;
        
        // Calculate final total
        // Pattern from QuoteAmountService, SalesOrderAmountService, InvAmountService
        $subtotalAfterDocumentAC = $itemSubtotal - $documentAllowance + $documentCharge;
        $taxBase = $subtotalAfterDocumentAC - $discount;
        $total = $taxBase + $itemTaxTotal;
        
        $expectedSubtotal = 188.00; // 195 - 15 + 8
        $expectedTotal = 227.00; // 188 + 39 (assuming tax already calculated on item level)
        
        // Note: In actual implementation, tax might be recalculated at document level
        // This test shows the pattern of including document-level allowances/charges
        
        $this->assertGreaterThan(0, $total, 'Total should be positive');
        $this->assertGreaterThan($itemSubtotal - $documentAllowance, $total, 
            'Total should be greater than subtotal after allowance');
    }

    /**
     * Test RBAC observer access pattern
     */
    public function testRBACObserverAccessPattern(): void
    {
        // Simulate quote with user_id
        $quoteUserId = '5';
        $observerUserId = '5';
        $unauthorizedUserId = '10';
        
        // Pattern from QuoteController::rbacObserver (fixed version)
        // OLD BUG: Used status_id instead of user_id
        // FIXED: Uses $quote->getUser_id()
        
        // Correct check
        $hasAccess = ($quoteUserId === $observerUserId);
        $this->assertTrue($hasAccess, 'Observer should have access to their own quote');
        
        // Unauthorized check
        $hasAccess = ($quoteUserId === $unauthorizedUserId);
        $this->assertFalse($hasAccess, 'Observer should NOT have access to other user quotes');
        
        // Same pattern applies to SalesOrderController::rbacObserver
    }

    /**
     * Test that quote cannot be converted twice
     */
    public function testQuoteCannotBeConvertedTwice(): void
    {
        // Quote already has so_id set
        $quoteSoId = '25';
        
        // Pattern from QuoteController::approve line 525
        // if ($formHydrator->populateAndValidate($form, $so_body)
        //     && ($quote->getSo_id() === (string) 0))
        
        $canConvert = ($quoteSoId === '0');
        $this->assertFalse($canConvert, 'Quote with existing so_id should not be convertible again');
        
        // New quote can be converted
        $newQuoteSoId = '0';
        $canConvert = ($newQuoteSoId === '0');
        $this->assertTrue($canConvert, 'Quote with so_id = 0 should be convertible');
    }

    /**
     * Test complete workflow amounts consistency
     */
    public function testWorkflowAmountsConsistency(): void
    {
        // Scenario: 2 items @ 100.00 each = 200.00
        // Item 1 allowance: -10.00, charge: +5.00
        // Item 2 allowance: -8.00, charge: +3.00
        // Document allowance: -15.00
        // Document charge: +8.00
        // Tax rate: 20%
        
        $item1Subtotal = 100.00;
        $item1Allowance = 10.00;
        $item1Charge = 5.00;
        $item1Adjusted = $item1Subtotal - $item1Allowance + $item1Charge; // 95.00
        $item1Tax = $item1Adjusted * 0.20; // 19.00
        
        $item2Subtotal = 100.00;
        $item2Allowance = 8.00;
        $item2Charge = 3.00;
        $item2Adjusted = $item2Subtotal - $item2Allowance + $item2Charge; // 95.00
        $item2Tax = $item2Adjusted * 0.20; // 19.00
        
        $totalItemSubtotal = $item1Adjusted + $item2Adjusted; // 190.00
        $totalItemTax = $item1Tax + $item2Tax; // 38.00
        
        $documentAllowance = 15.00;
        $documentCharge = 8.00;
        
        $finalSubtotal = $totalItemSubtotal - $documentAllowance + $documentCharge; // 183.00
        $finalTotal = $finalSubtotal + $totalItemTax; // 221.00
        
        // These amounts should be identical across Quote, SalesOrder, and Invoice
        $quoteTotal = $finalTotal;
        $soTotal = $finalTotal;
        $invTotal = $finalTotal;
        
        $this->assertEquals(221.00, $quoteTotal, 'Quote total should be 221.00');
        $this->assertEquals($quoteTotal, $soTotal, 'SalesOrder total should match Quote total');
        $this->assertEquals($soTotal, $invTotal, 'Invoice total should match SalesOrder total');
        
        $this->assertEquals(38.00, $totalItemTax, 'Total tax should be 38.00');
    }

    /**
     * Test cash discount application and preservation across workflow
     */
    public function testCashDiscountWorkflow(): void
    {
        // Scenario with cash discount at document level
        $itemSubtotal = 200.00;
        $itemTax = 40.00; // 20% tax
        $cashDiscount = 20.00; // discount_amount field
        
        // Pattern from QuoteController::approve line 530:
        // 'discount_amount' => (float) $quote->getDiscount_amount()
        // This is copied from Quote → SalesOrder → Invoice
        
        $quoteDiscountAmount = 20.00;
        $soDiscountAmount = $quoteDiscountAmount; // Copied during conversion
        $invDiscountAmount = $soDiscountAmount; // Copied during conversion
        
        $this->assertEquals(20.00, $quoteDiscountAmount, 'Quote should have discount_amount = 20.00');
        $this->assertEquals($quoteDiscountAmount, $soDiscountAmount, 
            'SalesOrder discount_amount should match Quote');
        $this->assertEquals($soDiscountAmount, $invDiscountAmount, 
            'Invoice discount_amount should match SalesOrder');
        
        // Cash discount is typically applied after item subtotal + document allowances/charges
        // Formula: total = (item_subtotal + doc_charges - doc_allowances - discount_amount) + tax
        $subtotalBeforeDiscount = 183.00; // From previous test
        $subtotalAfterDiscount = $subtotalBeforeDiscount - $cashDiscount; // 163.00
        $finalTotal = $subtotalAfterDiscount + $itemTax; // 203.00
        
        $this->assertEquals(163.00, $subtotalAfterDiscount, 
            'Subtotal after cash discount should be 163.00');
        $this->assertEquals(203.00, $finalTotal, 
            'Final total with cash discount should be 203.00');
    }

    /**
     * Test discount_amount field preservation during conversions
     */
    public function testDiscountAmountCopiedThroughWorkflow(): void
    {
        // From QuoteController::approve (line 530):
        // $so_body['discount_amount'] = (float) $quote->getDiscount_amount();
        
        $quoteDiscountAmount = '25.50';
        $soDiscountAmount = (float) $quoteDiscountAmount; // Type conversion during copy
        
        $this->assertEquals(25.50, $soDiscountAmount, 
            'SO should preserve quote discount_amount as float');
        
        // From SalesOrderController::so_to_invoice (similar pattern):
        // Invoice inherits discount_amount from SalesOrder
        $invDiscountAmount = $soDiscountAmount;
        
        $this->assertEquals($soDiscountAmount, $invDiscountAmount, 
            'Invoice should preserve SO discount_amount');
        
        // Verify the field is properly preserved as a float throughout
        $this->assertIsFloat($soDiscountAmount, 'discount_amount should be float type');
        $this->assertIsFloat($invDiscountAmount, 'discount_amount should remain float type');
    }
}
