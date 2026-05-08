# Credit Note Creation Workflow

## Overview

This document details the complete credit note creation process in the rossaddison/invoice system. Understanding this workflow is essential for:
- System maintenance and improvements
- Implementing Peppol e-credit-note functionality
- Troubleshooting credit note issues
- Extending partial credit capabilities

---

## Table of Contents

- [Architecture Overview](#architecture-overview)
- [User Interface Flow](#user-interface-flow)
- [Backend Processing](#backend-processing)
- [Database Structure](#database-structure)
- [Reversal Logic](#reversal-logic)
- [Peppol Considerations](#peppol-considerations)
- [Extension Points](#extension-points)

---

## Architecture Overview

### Key Principles

1. **Credit Notes are Invoices**: Credit notes use `group_id = 4` to differentiate them from regular invoices
2. **Full Reversal Only**: The current implementation creates complete reversals of the original invoice
3. **Three-Stage Reversal**: Line items, invoice totals, and tax summaries are reversed independently
4. **Reverse Relationship**: The **original invoice** stores the credit note ID (not the other way around)

### Technology Stack

- **Backend**: PHP (Yii3 Framework)
- **Frontend**: TypeScript (compiled to IIFE)
- **Database**: MySQL
- **ORM**: Cycle ORM
- **UI Framework**: Bootstrap 5

---

## User Interface Flow

### 1. Trigger Conditions

The "Create Credit Invoice" option appears when **ALL** conditions are met:

```php
// Location: resources/views/invoice/inv/view.php
if (($readOnly === true || $inv->getStatus_id() === 4)
    && $invEdit
    && !(int) $inv->getCreditinvoice_parent_id() > 0) {
    // Show create credit invoice option
}
```

**Conditions:**
- Invoice is read-only **OR** status is 4 (Paid)
- User has edit permissions
- Invoice is NOT already a credit note

### 2. User Interface Elements

#### Menu Option
```php
<a href="#create-credit-inv" 
   data-bs-toggle="modal" 
   data-invoice-id="<?= $inv->getId(); ?>" 
   style="text-decoration:none">
   <i class="fa fa-minus fa-margin"></i>
   <?= Html::encode($translator->translate('create.credit.invoice')); ?>
</a>
```

#### Button Widget
```php
// Location: src/Widget/ButtonsToolbarFull
$primaryButtons[] = $this->createModalButton(
    'credit',                    // Button type
    '#create-credit-inv',        // Modal selector
    'fa-minus',                  // Font Awesome icon
    'btn-danger',                // Red button styling
    $this->translator->translate('create.credit.invoice'),
    ['data-invoice-id' => $invId],
);
```

### 3. Modal Interaction

**Modal View**: `resources/views/invoice/inv/modal_create_credit`

The modal:
- Displays invoice details
- Asks for confirmation
- May collect reason for credit (implementation-specific)

### 4. TypeScript/JavaScript Submission

**Location**: `src/typescript/` → compiled to `src/Invoice/Asset/rebuild/js/invoice-typescript-iife.js`

```typescript
// Event handler for credit confirmation
$(document).on('click', '#create-credit-confirm', function () {
    const invoiceId = $(this).data('invoice-id');
    
    // AJAX submission to backend
    $.ajax({
        url: '/invoice/inv/create_credit_confirm',
        method: 'POST',
        data: { inv_id: invoiceId, /* other params */ },
        success: function(response) {
            if (response.success === 1) {
                // Reload page or redirect
                location.reload();
            }
        }
    });
});
```

---

## Backend Processing

### Controller Action

**Location**: `src/Invoice/Inv/InvController.php`

```php
public function create_credit_confirm(
    Request $request,
    FormHydrator $formHydrator, 
    IR $iR,           // Invoice Repository
    GR $gR,           // Group Repository
    IIR $iiR,         // Invoice Item Repository
    IIAR $iiaR,       // Invoice Item Amount Repository
    UCR $ucR,         // User Client Repository
    UIR $uiR,         // User Invoice Repository
    UR $uR            // User Repository
): \Yiisoft\DataResponse\DataResponse
```

### Processing Steps

#### Step 1: Load Original Invoice
```php
$body = $request->getQueryParams();
$basis_inv = $iR->repoInvLoadedquery((string) $body['inv_id']);
```

#### Step 2: Set Original Invoice to Read-Only
```php
$basis_inv->setIs_read_only(true);
```

**Purpose**: Prevents modifications to the original invoice after credit is issued.

#### Step 3: Create Credit Note Header
```php
$ajax_body = [
    'client_id' => $body['client_id'],
    'group_id' => 4,  // ← CRITICAL: Identifies this as a Credit Note
    'user_id' => $body['user_id'],
    'status_id' => $basis_inv->getStatus_id(),
    'is_read_only' => true,  // Credit notes are read-only by default
    'number' => $gR->generate_number(4, true),  // Auto-generate credit note number
    'discount_amount' => $basis_inv->getDiscount_amount(),
    'discount_percent' => $basis_inv->getDiscount_percent(),
    'url_key' => '',
    'password' => $body['password'],
    'payment_method' => 0,
    'terms' => '',
    'delivery_location_id' => $basis_inv->getDelivery_location_id(),
];
```

#### Step 4: Save Credit Note Invoice
```php
$new_inv = new Inv();
$form = new InvForm($new_inv);

if ($formHydrator->populateAndValidate($form, $ajax_body)) {
    $user = $this->active_user($client_id, $uR, $ucR, $uiR);
    $saved_inv = $this->inv_service->saveInv(
        $user, 
        $new_inv,
        $ajax_body, 
        $this->sR, 
        $gR
    );
    $saved_inv_id = $saved_inv->getId();
}
```

#### Step 5: Initialize Credit Components (Three-Stage Reversal)

```php
// Stage 1: Copy and reverse line items
$this->inv_item_service->initializeCreditInvItems(
    (int) $basis_inv_id, 
    $saved_inv_id, 
    $iiR, 
    $iiaR,
    $this->sR
);

// Stage 2: Copy and reverse invoice totals
$this->inv_amount_service->initializeCreditInvAmount(
    new InvAmount(), 
    (int) $basis_inv_id, 
    $saved_inv_id
);

// Stage 3: Copy and reverse tax rate summaries
$this->inv_tax_rate_service->initializeCreditInvTaxRate(
    (int) $basis_inv_id, 
    $saved_inv_id
);
```

#### Step 6: Link Original Invoice to Credit Note
```php
// IMPORTANT: Original invoice points to credit note
$basis_inv->setCreditinvoice_parent_id((int) $saved_inv_id);
$iR->save($basis_inv);
```

#### Step 7: Return Success Response
```php
$parameters = [
    'success' => 1,
    'flash_message' => $this->translator->translate('credit.note.creation.successful'),
];

return $this->factory->createResponse(Json::encode($parameters));
```

---

## Database Structure

### Entity Relationships

```
┌─────────────────┐
│   inv (Invoice) │
├─────────────────┤
│ id              │◄─────┐
│ group_id        │      │
│ status_id       │      │
│ creditinvoice_  │      │ Reverse relationship:
│   parent_id     │──────┘ Original invoice stores
│ is_read_only    │        credit note ID
└─────────────────┘
        │
        │ 1:N
        ▼
┌─────────────────┐
│   inv_item      │
├─────────────────┤
│ id              │
│ inv_id          │
│ product_id      │
│ quantity        │◄────── NEGATED for credit notes
│ price           │◄────── Stays POSITIVE
│ discount_amount │
└─────────────────┘
        │
        │ 1:1
        ▼
┌─────────────────┐
│ inv_item_amount │
├─────────────────┤
│ inv_item_id     │
│ subtotal        │◄────── ALL reversed (× -1)
│ tax_total       │◄────── ALL reversed (× -1)
│ discount        │◄────── ALL reversed (× -1)
│ total           │◄────── ALL reversed (× -1)
└─────────────────┘

┌─────────────────┐
│   inv_amount    │
├─────────────────┤
│ inv_id          │
│ item_subtotal   │◄────── ALL reversed (× -1)
│ item_tax_total  │◄────── ALL reversed (× -1)
│ tax_total       │◄────── ALL reversed (× -1)
│ total           │◄────── ALL reversed (× -1)
│ paid            │◄────── RESET to 0.00
│ balance         │◄────── Reversed (× -1)
│ sign            │◄────── Set to 1
└─────────────────┘

┌─────────────────┐
│  inv_tax_rate   │
├─────────────────┤
│ inv_id          │
│ tax_rate_id     │◄────── Same as original
│ include_item_tax│◄────── Same as original
│ inv_tax_rate_   │◄────── Reversed (× -1)
│   amount        │
└─────────────────┘
```

### Group ID Classification

| Group ID | Type | Description |
|----------|------|-------------|
| 1 | Quote | Quotation/Estimate |
| 2 | Sales Order | Confirmed order |
| 3 | Invoice | Regular invoice |
| **4** | **Credit Note** | **Credit invoice** |

### Invoice Status IDs

| Status ID | Status | Notes |
|-----------|--------|-------|
| 1 | Draft | Not sent to customer |
| 2 | Sent | Sent to customer |
| 3 | Viewed | Customer has viewed |
| **4** | **Paid** | **Common trigger for credit notes** |
| 5 | Overdue | Past due date |

---

## Reversal Logic

### Stage 1: Line Items

**Service**: `InvItemService::initializeCreditInvItems()`  
**Location**: `src/Invoice/InvItem/InvItemService.php`

#### Strategy: Negative Quantities, Positive Prices

```php
foreach ($items as $item) {
    $new_item = new InvItem();
    $new_item->setInv_id((int) $new_inv_id);
    $new_item->setTax_rate_id((int) $item->getTax_rate_id());
    
    // Copy product or task reference
    null !== $item->getProduct_id() 
        ? $new_item->setProduct_id((int) $item->getProduct_id())
        : $new_item->setTask_id((int) $item->getTask_id());
    
    // Copy descriptions
    $new_item->setName($item->getName() ?? '');
    $new_item->setDescription($item->getDescription() ?? '');
    $new_item->setNote($item->getNote() ?? '');
    
    // ⚡ KEY REVERSAL: Negate quantity
    $new_item->setQuantity(($item->getQuantity() ?? 1.00) * -1.00);
    
    // Keep prices and discounts positive
    $new_item->setPrice($item->getPrice() ?? 0.00);
    $new_item->setDiscount_amount($item->getDiscount_amount() ?? 0.00);
    
    // Other fields
    $new_item->setOrder(0);
    $new_item->setIs_recurring($item->getIs_recurring() ?? false);
    $new_item->setProduct_unit($item->getProduct_unit() ?? '');
    $new_item->setProduct_unit_id((int) $item->getProduct_unit_id());
    $new_item->setDate($item->getDate_added());
    
    $iiR->save($new_item);
}
```

#### Item Amount Reversal

```php
// Get calculated amounts from original
$basis_item_amount = $iiaR->repoInvItemAmountquery((string) $item->getId());

if ($basis_item_amount) {
    $new_item_amount = new InvItemAmount();
    $new_item_amount->setInv_item_id((int) $new_item->getId());
    
    // Reverse all calculated amounts
    $new_item_amount->setSubtotal(
        ($basis_item_amount->getSubtotal() ?? 0.00) * -1.00
    );
    $new_item_amount->setTax_total(
        ($basis_item_amount->getTax_total() ?? 0.00) * -1.00
    );
    $new_item_amount->setDiscount(
        ($basis_item_amount->getDiscount() ?? 0.00) * -1.00
    );
    $new_item_amount->setTotal(
        ($basis_item_amount->getTotal() ?? 0.00) * -1.00
    );
    
    $iiaR->save($new_item_amount);
}
```

#### Example

**Original Line Item:**
```
Product: Widget A
Quantity: 5
Price: $100.00
Discount: $10.00
─────────────────
Subtotal: $500.00
Discount: $50.00
Taxable: $450.00
Tax (20%): $90.00
Total: $540.00
```

**Credit Note Line Item:**
```
Product: Widget A
Quantity: -5        ← NEGATED
Price: $100.00      ← SAME
Discount: $10.00    ← SAME
─────────────────
Subtotal: -$500.00  ← REVERSED
Discount: -$50.00   ← REVERSED
Taxable: -$450.00   ← REVERSED
Tax (20%): -$90.00  ← REVERSED
Total: -$540.00     ← REVERSED
```

### Stage 2: Invoice Totals

**Service**: `InvAmountService::initializeCreditInvAmount()`  
**Location**: `src/Invoice/InvAmount/InvAmountService.php`

```php
public function initializeCreditInvAmount(
    InvAmount $model,
    int $basis_inv_id,
    string $new_inv_id
): void {
    $basis_invoice = $this->repository->repoInvquery($basis_inv_id);
    
    $model->setInv_id((int) $new_inv_id);
    $model->setSign(1);
    
    // Reverse all invoice-level totals
    $model->setItem_subtotal(
        ($basis_invoice->getItem_subtotal() ?: 0.00) * -1.00
    );
    $model->setItem_tax_total(
        ($basis_invoice->getItem_tax_total() ?: 0.00) * -1.00
    );
    $model->setPackhandleship_total(
        ($basis_invoice->getPackhandleship_total() ?: 0.00) * -1.00
    );
    $model->setPackhandleship_tax(
        ($basis_invoice->getPackhandleship_tax() ?: 0.00) * -1.00
    );
    $model->setTax_total(
        ($basis_invoice->getTax_total() ?? 0.00) * -1.00
    );
    $model->setTotal(
        ($basis_invoice->getTotal() ?? 0.00) * -1.00
    );
    
    // ⚡ CRITICAL: Reset paid to zero
    $model->setPaid(0.00);
    
    $model->setBalance(
        ($basis_invoice->getBalance() ?? 0.00) * -1.00
    );
    
    $this->repository->save($model);
}
```

#### Example

**Original Invoice Totals:**
```
Item Subtotal:           $1,000.00
Item Tax Total:            $200.00
Shipping Total:             $50.00
Shipping Tax:                $5.00
──────────────────────────────────
Tax Total:                 $205.00
Grand Total:             $1,255.00
Paid:                    $1,255.00 ✅
Balance:                     $0.00
```

**Credit Note Totals:**
```
Item Subtotal:          -$1,000.00
Item Tax Total:           -$200.00
Shipping Total:            -$50.00
Shipping Tax:               -$5.00
──────────────────────────────────
Tax Total:                -$205.00
Grand Total:            -$1,255.00
Paid:                        $0.00 ⚠️ RESET
Balance:                -$1,255.00 ← Owed to customer
```

### Stage 3: Tax Rate Summaries

**Service**: `InvTaxRateService::initializeCreditInvTaxRate()`  
**Location**: `src/Invoice/InvTaxRate/InvTaxRateService.php`

```php
public function initializeCreditInvTaxRate(
    int $basis_inv_id,
    ?string $new_inv_id
): void {
    $basis_invoice_tax_rates = 
        $this->repository->repoInvquery((string) $basis_inv_id);
    
    foreach ($basis_invoice_tax_rates as $basis_invoice_tax_rate) {
        $new_invoice_tax_rate = new InvTaxRate();
        
        $new_invoice_tax_rate->setInv_id((int) $new_inv_id);
        $new_invoice_tax_rate->setTax_rate_id(
            (int) $basis_invoice_tax_rate->getTax_rate_id()
        );
        
        // Copy tax inclusion setting
        if ($basis_invoice_tax_rate->getInclude_item_tax() == 1 
            || $basis_invoice_tax_rate->getInclude_item_tax() == 0) {
            $new_invoice_tax_rate->setInclude_item_tax(
                $basis_invoice_tax_rate->getInclude_item_tax() ?? 0
            );
        }
        
        // Reverse the tax amount
        $new_invoice_tax_rate->setInv_tax_rate_amount(
            ($basis_invoice_tax_rate->getInv_tax_rate_amount() ?? 0.00) * -1.00
        );
        
        $this->repository->save($new_invoice_tax_rate);
    }
}
```

#### Tax Rate Aggregation Purpose

The `inv_tax_rate` table stores tax summaries for:
- Tax compliance reporting
- VAT returns
- Tax authority submissions
- Financial reports

#### Include Item Tax Flag

| Value | Meaning | Example |
|-------|---------|---------|
| **1** | Item-level tax | VAT, Sales Tax on products |
| **0** | Invoice-level tax | Special levies, environmental charges |

#### Example

**Original Tax Breakdown:**
```
Tax Rate 1 (VAT 20%):     $200.00  [Include Item Tax: 1]
Tax Rate 2 (State 5%):     $50.00  [Include Item Tax: 1]
─────────────────────────────────
Total Tax:                $250.00
```

**Credit Note Tax Breakdown:**
```
Tax Rate 1 (VAT 20%):    -$200.00  [Include Item Tax: 1]
Tax Rate 2 (State 5%):    -$50.00  [Include Item Tax: 1]
─────────────────────────────────
Total Tax:               -$250.00
```

---

## Complete Example Walkthrough

### Scenario

Customer purchased:
- 5 × Widget A @ $100 each
- 10 × Widget B @ $50 each
- VAT at 20%
- Shipping: $25

Payment received in full. Customer now requests full credit.

### Original Invoice

```
Invoice #INV-001234
Status: Paid
Group: Invoice (3)

Line Items:
┌────────────┬──────────┬─────────┬───────────┬─────────┬─────────┐
│ Product    │ Quantity │  Price  │ Subtotal  │ Tax 20% │  Total  │
├────────────┼──────────┼─────────┼───────────┼─────────┼─────────┤
│ Widget A   │    5     │ $100.00 │  $500.00  │ $100.00 │ $600.00 │
│ Widget B   │   10     │  $50.00 │  $500.00  │ $100.00 │ $600.00 │
└────────────┴──────────┴─────────┴───────────┴─────────┴─────────┘

Invoice Totals:
  Item Subtotal:         $1,000.00
  Item Tax (20%):          $200.00
  Shipping:                 $25.00
  Shipping Tax (20%):        $5.00
  ───────────────────────────────
  Total Tax:               $205.00
  Grand Total:           $1,230.00
  Paid:                  $1,230.00
  Balance:                   $0.00

Tax Summary:
  VAT 20%:                 $205.00
```

### Credit Note Created

```
Credit Note #CN-000567
Status: Paid (inherited)
Group: Credit Note (4)
Parent Invoice: INV-001234 (via creditinvoice_parent_id)

Line Items:
┌────────────┬──────────┬─────────┬───────────┬─────────┬──────────┐
│ Product    │ Quantity │  Price  │ Subtotal  │ Tax 20% │  Total   │
├────────────┼──────────┼─────────┼───────────┼─────────┼──────────┤
│ Widget A   │   -5     │ $100.00 │ -$500.00  │-$100.00 │ -$600.00 │
│ Widget B   │  -10     │  $50.00 │ -$500.00  │-$100.00 │ -$600.00 │
└────────────┴──────────┴─────────┴───────────┴─────────┴──────────┘

Invoice Totals:
  Item Subtotal:        -$1,000.00
  Item Tax (20%):         -$200.00
  Shipping:                -$25.00
  Shipping Tax (20%):       -$5.00
  ───────────────────────────────
  Total Tax:              -$205.00
  Grand Total:          -$1,230.00
  Paid:                      $0.00  ⚠️
  Balance:              -$1,230.00  ← Customer credit

Tax Summary:
  VAT 20%:                -$205.00
```

### Net Effect

```
Customer Account Balance:
  Original Invoice:     +$1,230.00 (charged)
  Credit Note:          -$1,230.00 (credited)
  ─────────────────────────────────
  Net Balance:               $0.00
```

---

## Peppol Considerations

### Peppol BIS Billing 3.0 Credit Note Requirements

When implementing Peppol e-credit-notes, the following must be considered:

#### 1. Document Type Code

**Current Implementation:**
- Uses `group_id = 4` internally

**Peppol Requirement:**
- UBL Invoice document type code: **`381`** (Credit Note) or **`384`** (Corrected Invoice)

**Implementation Location:**
```php
// Likely in: src/Invoice/Helpers/Peppol/PeppolArrays.php or similar
// When generating UBL XML:
if ($invoice->getGroup_id() === 4) {
    $documentTypeCode = '381'; // Credit Note
}
```

#### 2. Invoice Reference

**Peppol Requirement:**
- Credit notes MUST reference the original invoice using `<cac:BillingReference>`

**Implementation:**
```xml
<cac:BillingReference>
    <cac:InvoiceDocumentReference>
        <cbc:ID>INV-001234</cbc:ID>
        <cbc:IssueDate>2025-01-15</cbc:IssueDate>
    </cac:InvoiceDocumentReference>
</cac:BillingReference>
```

**Data Source:**
```php
// Get original invoice via creditinvoice_parent_id
$originalInvoice = $iR->repoInvLoadedquery(
    (string) $creditNote->getCreditinvoice_parent_id()
);
```

#### 3. Negative Line Items

**Current Implementation:** ✅ Already compliant
- Quantities are negative
- Prices remain positive
- Calculation produces negative amounts

**Peppol Requirement:**
- Line items should show negative `InvoicedQuantity`
- `LineExtensionAmount` should be negative
- Tax amounts should be negative

#### 4. Credit Note Reason

**Peppol Optional Element:**
```xml
<cbc:CreditNoteTypeCode>380</cbc:CreditNoteTypeCode>
<cbc:Note>Full refund - customer cancellation</cbc:Note>
```

**Enhancement Needed:**
- Add `credit_reason` field to modal
- Store in database (could use `inv.terms` or new field)
- Include in UBL generation

#### 5. Tax Category Handling

**Current Implementation:** ✅ Tax rates preserved
- `inv_tax_rate` maintains tax rate references
- Amounts are properly negated

**Peppol Requirement:**
- Tax category codes must match original invoice
- Negative tax amounts are acceptable

#### 6. Payment Means on Credit Notes

**Peppol Guidance:**
- Credit notes typically don't include `<cac:PaymentMeans>`
- If refund method is specified, it should be clear

**Current Implementation:**
```php
'payment_method' => 0,  // No payment method for credit notes
```

### Peppol UBL XML Generation Enhancement Points

#### File Locations to Modify

1. **Peppol Generator Service**
   - Location: `src/Invoice/Helpers/Peppol/PeppolService.php` (or similar)
   - Add credit note detection
   - Generate appropriate UBL structure

2. **UBL Array Builders**
   - Location: `src/Invoice/Helpers/Peppol/PeppolArrays.php` (or similar)
   - Build `<cac:BillingReference>` for credit notes
   - Set correct document type codes

3. **XML Template**
   - May use PHP array → XML conversion
   - Ensure negative amounts render correctly
   - Include credit-specific elements

#### Example Enhancement

```php
// In PeppolService or similar
public function generateCreditNoteUBL(Inv $creditNote, Inv $originalInvoice): string
{
    $ublArray = [
        'Invoice' => [
            '@xmlns' => 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2',
            '@xmlns:cac' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2',
            '@xmlns:cbc' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2',
            
            'cbc:CustomizationID' => 'urn:cen.eu:en16931:2017#compliant#urn:fdc:peppol.eu:2017:poacc:billing:3.0',
            'cbc:ProfileID' => 'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0',
            
            // Credit Note specific
            'cbc:ID' => $creditNote->getNumber(),
            'cbc:IssueDate' => $creditNote->getDate_created(),
            'cbc:InvoiceTypeCode' => '381', // Credit Note
            
            // Reference to original invoice
            'cac:BillingReference' => [
                'cac:InvoiceDocumentReference' => [
                    'cbc:ID' => $originalInvoice->getNumber(),
                    'cbc:IssueDate' => $originalInvoice->getDate_created(),
                ]
            ],
            
            // ... rest of UBL structure
        ]
    ];
    
    return $this->arrayToXml($ublArray);
}
```

### Peppol Validation Requirements

Credit notes must pass:
1. **Schematron Validation** (EN 16931 rules)
2. **Peppol BIS Rules** (PEPPOL-EN16931-* rules)
3. **Country-Specific Extensions** (if applicable)

**Testing Tools:**
- [Ecosio Validation](https://ecosio.com) - Mentioned in README as tested
- [Peppol Testbed](https://peppol.helger.com/public/menuitem-validation-bis3)
- [OpenPeppol Validator](https://docs.peppol.eu/poacc/billing/3.0/)

---

## Extension Points

### 1. Partial Credit Implementation

To allow crediting only some items or partial quantities:

#### Modal Enhancement
```php
// resources/views/invoice/inv/modal_create_credit.php
// Add checkboxes for line item selection
foreach ($items as $item) {
    echo '<input type="checkbox" name="items[]" value="' . $item->getId() . '" checked>';
    echo '<input type="number" name="qty[' . $item->getId() . ']" 
          value="' . $item->getQuantity() . '" 
          max="' . $item->getQuantity() . '">';
}
```

#### Service Enhancement
```php
// InvItemService::initializeCreditInvItems()
public function initializeCreditInvItems(
    int $basis_inv_id,
    string $new_inv_id,
    array $selectedItems,  // NEW: Selected item IDs
    array $quantities,     // NEW: Quantities to credit
    // ... other params
): void {
    foreach ($selectedItems as $itemId) {
        $item = $iiR->repoInvItemquery($itemId);
        $creditQuantity = $quantities[$itemId] ?? $item->getQuantity();
        
        // Create credit item with specified quantity
        $new_item->setQuantity($creditQuantity * -1.00);
        
        // ... rest of logic
    }
}
```

### 2. Credit Reason Tracking

#### Database Migration
```sql
ALTER TABLE inv ADD COLUMN credit_reason VARCHAR(255) NULL;
ALTER TABLE inv ADD COLUMN credit_reason_code VARCHAR(10) NULL;
```

#### Form Enhancement
```php
// modal_create_credit.php
<select name="credit_reason_code">
    <option value="DAMAGED">Damaged goods</option>
    <option value="WRONG">Wrong item shipped</option>
    <option value="CANCEL">Order cancellation</option>
    <option value="RETURN">Customer return</option>
    <option value="OTHER">Other</option>
</select>
<textarea name="credit_reason"></textarea>
```

#### Entity Update
```php
// src/Invoice/Entity/Inv.php
private ?string $credit_reason = null;
private ?string $credit_reason_code = null;

// Add getters and setters
```

### 3. Credit Approval Workflow

For businesses requiring approval before issuing credits:

#### Status Enhancement
```php
// Add new status: "Pending Credit Approval"
// Status ID: 6 (example)

// InvController::create_credit_confirm()
$ajax_body = [
    // ...
    'status_id' => 6,  // Pending approval instead of copying original status
];
```

#### Approval Action
```php
// InvController::approve_credit()
public function approve_credit(Request $request, IR $iR): Response
{
    $creditNoteId = $request->getAttribute('id');
    $creditNote = $iR->repoInvLoadedquery($creditNoteId);
    
    // Verify it's a credit note and pending
    if ($creditNote->getGroup_id() === 4 
        && $creditNote->getStatus_id() === 6) {
        
        // Approve it
        $creditNote->setStatus_id(2); // Sent/Approved
        $iR->save($creditNote);
        
        // Optionally send to customer
        // ...
    }
}
```

### 4. Automatic Credit Note Application

Currently, credit notes are created but not automatically applied to customer balance.

#### Customer Balance Tracking
```php
// Add to Customer entity or create CustomerBalance entity
class CustomerBalance
{
    private ?int $client_id = null;
    private float $total_invoiced = 0.00;
    private float $total_paid = 0.00;
    private float $total_credited = 0.00;
    
    public function getBalance(): float
    {
        return $this->total_invoiced - $this->total_paid - $this->total_credited;
    }
}
```

#### Update on Credit Creation
```php
// InvController::create_credit_confirm()
// After creating credit note:

$customerBalance = $customerBalanceRepo->findByClient($client_id);
$customerBalance->setTotal_credited(
    $customerBalance->getTotal_credited() + abs($saved_inv->getTotal())
);
$customerBalanceRepo->save($customerBalance);
```

### 5. Credit Note Reversal

To allow canceling/reversing a credit note:

```php
// InvController::reverse_credit()
public function reverse_credit(Request $request, IR $iR): Response
{
    $creditNoteId = $request->getAttribute('id');
    $creditNote = $iR->repoInvLoadedquery($creditNoteId);
    
    // Verify it's a credit note
    if ($creditNote->getGroup_id() === 4) {
        // Option 1: Delete the credit note
        // $iR->delete($creditNote);
        
        // Option 2: Mark as void
        $creditNote->setStatus_id(7); // Void status
        $iR->save($creditNote);
        
        // Reset original invoice
        $originalInvoiceId = $creditNote->getCreditinvoice_parent_id();
        if ($originalInvoiceId) {
            $originalInvoice = $iR->repoInvLoadedquery($originalInvoiceId);
            $originalInvoice->setCreditinvoice_parent_id(null);
            $originalInvoice->setIs_read_only(false);
            $iR->save($originalInvoice);
        }
    }
}
```

### 6. Multi-Currency Credit Notes

For international businesses:

```php
// Ensure exchange rates are preserved
$new_inv->setCurrency_id($basis_inv->getCurrency_id());
$new_inv->setExchange_rate($basis_inv->getExchange_rate());

// Store original currency amounts for Peppol
// UBL requires DocumentCurrencyCode and may include TaxCurrencyCode
```

### 7. Credit Note Reporting

#### Sales Report Enhancement
```sql
-- Include credit notes in sales reports
SELECT 
    inv.number,
    inv.group_id,
    CASE 
        WHEN inv.group_id = 4 THEN 'Credit Note'
        ELSE 'Invoice'
    END as type,
    inv_amount.total,
    inv.date_created
FROM inv
LEFT JOIN inv_amount ON inv.id = inv_amount.inv_id
WHERE inv.group_id IN (3, 4)  -- Invoices and Credit Notes
ORDER BY inv.date_created DESC;
```

#### Dashboard Widget
```php
// Show credit note statistics
$creditNotesThisMonth = $iR->countByGroupAndDateRange(4, $startDate, $endDate);
$totalCreditedAmount = $iR->sumTotalByGroupAndDateRange(4, $startDate, $endDate);
```

---

## Testing Checklist

### Unit Tests

- [ ] Test credit note creation from paid invoice
- [ ] Test credit note creation from read-only invoice
- [ ] Verify original invoice becomes read-only
- [ ] Verify quantities are negated
- [ ] Verify prices remain positive
- [ ] Verify all amounts are reversed
- [ ] Test tax rate reversal
- [ ] Test with multiple tax rates
- [ ] Test with shipping charges
- [ ] Test with discounts

### Integration Tests

- [ ] Full workflow from UI to database
- [ ] Verify AJAX response structure
- [ ] Test with different invoice statuses
- [ ] Test permission checks
- [ ] Verify credit note number generation
- [ ] Test relationship linking (creditinvoice_parent_id)

### Peppol Validation Tests

- [ ] Generate UBL XML for credit note
- [ ] Validate against EN 16931 Schematron rules
- [ ] Validate against Peppol BIS 3.0 rules
- [ ] Test with Ecosio validator
- [ ] Verify BillingReference element
- [ ] Verify negative amounts in XML
- [ ] Test with different tax categories
- [ ] Test multi-currency scenarios

### Edge Cases

- [ ] Credit note from credit note (should be prevented)
- [ ] Credit note from draft invoice (should be prevented)
- [ ] Very large quantities/amounts
- [ ] Zero-amount invoices
- [ ] Invoices with only shipping
- [ ] Multiple discounts
- [ ] Rounding edge cases

---

## Troubleshooting

### Common Issues

#### Issue: Credit Note Not Created

**Symptoms:**
- Button appears but nothing happens
- AJAX call fails
- No error message shown

**Debugging Steps:**
1. Check browser console for JavaScript errors
2. Verify AJAX endpoint is correct: `/invoice/inv/create_credit_confirm`
3. Check server logs for PHP errors
4. Verify user has edit permissions
5. Confirm original invoice is not already credited

**Solution:**
```javascript
// Add error handling to TypeScript
$.ajax({
    // ...
    error: function(xhr, status, error) {
        console.error('Credit note creation failed:', error);
        alert('Failed to create credit note: ' + xhr.responseText);
    }
});
```

#### Issue: Amounts Not Reversed Correctly

**Symptoms:**
- Credit note totals are positive
- Taxes not calculated correctly

**Debugging Steps:**
1. Check if item amounts were saved: `SELECT * FROM inv_item_amount WHERE inv_item_id = ?`
2. Verify multiplication by -1.00: `SELECT quantity FROM inv_item WHERE inv_id = ?`
3. Check for null coalescing issues: `?? 0.00`

**Solution:**
Ensure all three initialization methods completed:
```php
// Add logging
Log::info('Credit items initialized', ['inv_id' => $saved_inv_id]);
Log::info('Credit amounts initialized', ['inv_id' => $saved_inv_id]);
Log::info('Credit tax rates initialized', ['inv_id' => $saved_inv_id]);
```

#### Issue: Original Invoice Not Linked

**Symptoms:**
- `creditinvoice_parent_id` is null or wrong
- Can create multiple credits from same invoice

**Debugging Steps:**
1. Check if original invoice was saved: `SELECT creditinvoice_parent_id FROM inv WHERE id = ?`
2. Verify the ID is being set correctly in controller

**Solution:**
```php
// Add validation
if ($basis_inv->getCreditinvoice_parent_id() > 0) {
    throw new \Exception('Invoice already has a credit note');
}
```

#### Issue: Peppol XML Validation Fails

**Symptoms:**
- Schematron errors
- Missing required elements
- Invalid document type

**Common Validation Errors:**
1. **BR-CO-15**: Missing BillingReference
2. **BR-16**: Negative amounts not properly formatted
3. **BR-52**: Tax category mismatch

**Solution:**
```php
// Ensure proper UBL structure
$ublArray = [
    'cbc:InvoiceTypeCode' => '381',  // Must be 381 for credit note
    'cac:BillingReference' => [      // REQUIRED for credit notes
        'cac:InvoiceDocumentReference' => [
            'cbc:ID' => $originalInvoice->getNumber(),
        ]
    ],
    // Negative amounts should be:
    'cbc:InvoicedQuantity' => [
        '@unitCode' => 'C62',
        '#' => '-5'  // Negative as string
    ],
];
```

---

## Performance Considerations

### Database Queries

The current implementation makes multiple queries:

1. Load original invoice: 1 query
2. Load invoice items: 1 query
3. For each item, load item amount: N queries
4. Load tax rates: 1 query
5. Multiple save operations: 3N + 3 queries

**Optimization Opportunities:**

```php
// Batch load item amounts
$itemIds = array_map(fn($item) => $item->getId(), $items);
$itemAmounts = $iiaR->findByItemIds($itemIds);

// Index by item ID for O(1) lookup
$amountsByItemId = [];
foreach ($itemAmounts as $amount) {
    $amountsByItemId[$amount->getInv_item_id()] = $amount;
}
```

### Transaction Wrapping

Ensure atomicity:

```php
// InvController::create_credit_confirm()
DB::beginTransaction();
try {
    // All credit note creation logic
    
    DB::commit();
} catch (\Exception $e) {
    DB::rollback();
    // Return error
}
```

---

## Security Considerations

### Permission Checks

**Current Implementation:**
```php
if (($readOnly === true || $inv->getStatus_id() === 4)
    && $invEdit
    && !(int) $inv->getCreditinvoice_parent_id() > 0)
```

**Enhancement:**
```php
// Add explicit permission check
if (!$this->userCan('create_credit_note')) {
    throw new ForbiddenException();
}

// Add audit logging
$this->auditLog->log('credit_note_created', [
    'user_id' => $currentUser->getId(),
    'original_invoice_id' => $basis_inv_id,
    'credit_note_id' => $saved_inv_id,
    'amount' => abs($saved_inv->getTotal()),
]);
```

### CSRF Protection

Ensure CSRF tokens are validated:
```php
// In form
<input type="hidden" name="_csrf" value="<?= $csrf ?>">

// In controller
if (!$request->getCsrfToken()->validate()) {
    throw new BadRequestException('Invalid CSRF token');
}
```

### Input Validation

```php
// Validate invoice ID exists and user has access
$basis_inv = $iR->repoInvLoadedquery((string) $body['inv_id']);
if (!$basis_inv) {
    throw new NotFoundException('Invoice not found');
}

// Verify user owns this invoice or has permission
if ($basis_inv->getClient_id() !== $currentUser->getClient_id() 
    && !$currentUser->isAdmin()) {
    throw new ForbiddenException();
}
```

---

## Related Files Reference

### Backend (PHP)

| File | Purpose |
|------|---------|
| `src/Invoice/Inv/InvController.php` | Main controller with `create_credit_confirm()` |
| `src/Invoice/InvItem/InvItemService.php` | Line item reversal logic |
| `src/Invoice/InvAmount/InvAmountService.php` | Invoice total reversal logic |
| `src/Invoice/InvTaxRate/InvTaxRateService.php` | Tax rate reversal logic |
| `src/Invoice/Entity/Inv.php` | Invoice entity with `creditinvoice_parent_id` |
| `src/Invoice/Entity/InvItem.php` | Line item entity |
| `src/Invoice/Entity/InvAmount.php` | Invoice amount entity |
| `src/Invoice/Entity/InvTaxRate.php` | Tax rate summary entity |
| `src/Invoice/Inv/InvRepository.php` | Invoice database operations |

### Frontend

| File | Purpose |
|------|---------|
| `resources/views/invoice/inv/view.php` | Invoice view with credit option |
| `resources/views/invoice/inv/modal_create_credit.php` | Credit confirmation modal |
| `src/Widget/ButtonsToolbarFull.php` | Toolbar button widget |
| `src/typescript/*.ts` | TypeScript source (compiled to IIFE) |
| `src/Invoice/Asset/rebuild/js/invoice-typescript-iife.js` | Compiled JavaScript |

### Peppol (Future Enhancement)

| File | Purpose |
|------|---------|
| `src/Invoice/Helpers/Peppol/PeppolService.php` | UBL generation service (likely) |
| `src/Invoice/Helpers/Peppol/PeppolArrays.php` | UBL array builders (likely) |

---

## Glossary

| Term | Definition |
|------|------------|
| **Basis Invoice** | The original invoice from which a credit note is created |
| **Credit Note** | A negative invoice that reverses all or part of an original invoice |
| **Group ID** | Invoice type identifier (4 = Credit Note) |
| **UBL** | Universal Business Language - XML standard for e-invoicing |
| **Peppol** | Pan-European Public Procurement Online - e-invoicing network |
| **BIS** | Business Interoperability Specifications - Peppol's business rules |
| **EN 16931** | European standard for electronic invoicing |
| **Invoice Type Code** | UBL code identifying document type (381 = Credit Note) |
| **BillingReference** | UBL element linking credit note to original invoice |
| **creditinvoice_parent_id** | Database field storing credit note ID in original invoice |
| **IIFE** | Immediately Invoked Function Expression - JavaScript pattern |

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2025-01-22 | Initial documentation based on current implementation |

---

## Contributing

When modifying the credit note workflow:

1. Update this documentation
2. Add unit tests for new functionality
3. Update Peppol XML generation if applicable
4. Test with actual Peppol validators
5. Consider backward compatibility
6. Update database migrations if schema changes

---

## Support and Resources

### Internal Resources
- Main README: `/README.md`
- Peppol Integration Docs: `/docs/PEPPOL_INTEGRATION.md` (if exists)
- Database Schema: Check migration files

### External Resources
- [Peppol BIS Billing 3.0](https://docs.peppol.eu/poacc/billing/3.0/)
- [UBL 2.1 Specification](http://docs.oasis-open.org/ubl/UBL-2.1.html)
- [EN 16931 Standard](https://ec.europa.eu/cefdigital/wiki/display/CEFDIGITAL/Electronic+invoicing)
- [Ecosio Validator](https://ecosio.com/en/peppol/)
- [OpenPeppol](https://peppol.org/)

---

**Document Maintainer**: Development Team  
**Last Updated**: 2025-01-22  
**Next Review**: When implementing Peppol e-credit-note functionality
