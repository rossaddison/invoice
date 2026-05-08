# Cycle ORM Join Optimization Guide

## Overview

This document provides guidance on optimizing database queries using Cycle ORM's `load()` method to perform eager loading via joins. Proper use of joins can dramatically reduce the number of database queries and eliminate N+1 query problems.

## Understanding Lazy vs Eager Loading

### Lazy Loading (Default: `LOAD_PROMISE`)

Most relations in the schema are configured with `Relation::LOAD => Relation::LOAD_PROMISE`, which means:

- Related entities are **not** loaded automatically
- Data is fetched only when accessed (on-demand)
- Can cause **N+1 query problems** in list views
- Suitable for detail views where relations are rarely accessed

**Example Problem (N+1 Queries):**
```php
// This loads 100 invoices with 1 query
$invoices = $invRepository->select()->fetchAll();

// But accessing client for each invoice triggers 100 additional queries!
foreach ($invoices as $invoice) {
    echo $invoice->getClient()->getClient_name(); // Query executed here!
}
// Total: 101 queries (1 for invoices + 100 for clients)
```

### Eager Loading (Using `load()`)

Using `load()` performs a JOIN or separate query to fetch related data upfront:

```php
// This loads 100 invoices AND their clients with just 2-3 queries
$invoices = $invRepository
    ->select()
    ->load('client')
    ->fetchAll();

foreach ($invoices as $invoice) {
    echo $invoice->getClient()->getClient_name(); // No query! Data already loaded
}
// Total: 2-3 queries (1 for invoices + 1-2 for clients via JOIN or INLOAD)
```

### Pre-Configured Eager Loading (`LOAD_EAGER`)

The `identity` entity has `Relation::LOAD => Relation::LOAD_EAGER` for its `user` relation:

```php
// Identity automatically loads user relation - no need for load()
$identity = $identityRepository->findOne(['id' => $id]);
$user = $identity->getUser(); // Already loaded, no additional query
```

## High Priority Tables for Joining

These tables benefit most from eager loading in list/grid views:

### 1. Invoice (`inv`) Relationships

**Primary joins for invoice lists:**
```php
$invoices = $invRepository
    ->select()
    ->load('client')        // BELONGS_TO: Get client name, email, etc.
    ->load('user')          // BELONGS_TO: Get user who created invoice
    ->load('group')         // BELONGS_TO: Get invoice group/category
    ->load('invAmount')     // HAS_ONE: Get total amounts for display
    ->orderBy('date_created', 'DESC')
    ->fetchAll();
```

**When to use:**
- Invoice list pages (`inv/index.php`)
- Invoice reports and exports
- Dashboard summaries
- Invoice search results

**Performance gain:** Reduces 4N+1 queries to 4-5 queries total.

### 2. Quote (`quote`) Relationships

**Primary joins for quote lists:**
```php
$quotes = $quoteRepository
    ->select()
    ->load('client')        // BELONGS_TO: Client information
    ->load('user')          // BELONGS_TO: Quote creator
    ->load('group')         // BELONGS_TO: Quote group
    ->load('quoteAmount')   // HAS_ONE: Total amounts
    ->orderBy('date_created', 'DESC')
    ->fetchAll();
```

**When to use:**
- Quote list pages (`quote/index.php`)
- Quote status reports
- Client quote history
- Quote-to-SO conversion views

**Performance gain:** Reduces 4N+1 queries to 4-5 queries total.

### 3. Sales Order (`salesOrder`) Relationships

**Primary joins for sales order lists:**
```php
$salesOrders = $salesOrderRepository
    ->select()
    ->load('client')                // BELONGS_TO: Client details
    ->load('user')                  // BELONGS_TO: SO creator
    ->load('group')                 // BELONGS_TO: SO group
    ->load('quote')                 // BELONGS_TO: Original quote reference
    ->load('sales_order_amount')    // HAS_ONE: Total amounts
    ->orderBy('date_created', 'DESC')
    ->fetchAll();
```

**With nested joins (quote chain):**
```php
$salesOrders = $salesOrderRepository
    ->select()
    ->load('client')
    ->load('quote')
    ->load('quote.client')          // Nested: Get quote's client if different
    ->load('quote.quoteAmount')     // Nested: Compare quote vs SO amounts
    ->load('sales_order_amount')
    ->fetchAll();
```

**When to use:**
- Sales order list pages
- Order fulfillment tracking
- Quote-to-SO conversion workflows
- SO-to-Invoice conversion

### 4. Quote Item (`quoteItem`) Relationships

**Primary joins for quote item details:**
```php
$quoteItems = $quoteItemRepository
    ->select()
    ->load('quote')         // BELONGS_TO: Parent quote
    ->load('tax_rate')      // BELONGS_TO: Tax calculation details
    ->load('product')       // BELONGS_TO: Product information (nullable)
    ->load('task')          // BELONGS_TO: Task details if applicable (nullable)
    ->where('quote_id', $quoteId)
    ->fetchAll();
```

**With full context chain:**
```php
$quoteItems = $quoteItemRepository
    ->select()
    ->load('quote')
    ->load('quote.client')          // Nested: Client info from parent
    ->load('quote.quoteAmount')     // Nested: Quote totals
    ->load('tax_rate')
    ->load('product')
    ->where('quote_id', $quoteId)
    ->fetchAll();
```

**When to use:**
- Quote detail/edit pages
- Quote item amount calculations
- Product-to-quote associations
- Quote PDF generation

### 5. Invoice Item (`invItem`) Relationships

**Primary joins for invoice item details:**
```php
$invItems = $invItemRepository
    ->select()
    ->load('inv')           // BELONGS_TO: Parent invoice
    ->load('tax_rate')      // BELONGS_TO: Tax details
    ->load('product')       // BELONGS_TO: Product information
    ->where('inv_id', $invId)
    ->fetchAll();
```

**When to use:**
- Invoice detail/edit pages
- Invoice amount calculations
- Invoice PDF generation
- Product sales reports

### 6. Sales Order Item (`salesOrderItem`) Relationships

**Primary joins:**
```php
$soItems = $salesOrderItemRepository
    ->select()
    ->load('sales_order')   // BELONGS_TO: Parent SO (note: outer_key is array)
    ->load('tax_rate')      // BELONGS_TO: Tax details
    ->load('product')       // BELONGS_TO: Product information
    ->where('sales_order_id', $soId)
    ->fetchAll();
```

## Medium Priority Tables for Joining

### 7. QuoteItemAllowanceCharge Relationships

**All three relations typically needed together:**
```php
$allowanceCharges = $quoteItemAllowanceChargeRepository
    ->select()
    ->load('allowance_charge')  // BELONGS_TO: Type/name of allowance/charge
    ->load('quote_item')         // BELONGS_TO: Parent item
    ->load('quote')              // BELONGS_TO: Parent quote
    ->where('quote_id', $quoteId)
    ->fetchAll();
```

**When to use:**
- Quote amount calculations
- Quote item amount aggregation
- Allowance/charge reports

### 8. Client (`client`) Relationships

**For client lists with postal addresses:**
```php
$clients = $clientRepository
    ->select()
    ->load('postal_address')    // If BELONGS_TO relation exists
    ->where('client_active', true)
    ->orderBy('client_name', 'ASC')
    ->fetchAll();
```

**Avoid loading collections in lists:**
```php
// ❌ DO NOT DO THIS - causes massive data duplication
$clients = $clientRepository
    ->select()
    ->load('invs')                  // HAS_MANY - could be hundreds per client!
    ->load('delivery_locations')    // HAS_MANY
    ->fetchAll();
```

### 9. Company (`company`) with Private Details

**For company display:**
```php
$company = $companyRepository
    ->select()
    ->load('companyPrivates')   // HAS_MANY: VAT, tax code, logo, etc.
    ->where('current', 1)
    ->fetchOne();
```

**When to use:**
- Company settings page
- Invoice/Quote header rendering
- Company profile display

### 10. Payment (`payment`) Relationships

**For payment tracking:**
```php
$payments = $paymentRepository
    ->select()
    ->load('inv')           // Assuming BELONGS_TO relation exists
    ->load('inv.client')    // Nested: Client who made payment
    ->orderBy('payment_date', 'DESC')
    ->fetchAll();
```

## Advanced Join Techniques

### Using `JoinableLoader::INLOAD` for Large Datasets

For large result sets, use `INLOAD` method which performs a separate `WHERE IN` query instead of JOIN:

```php
use Cycle\ORM\Select\JoinableLoader;

$invoices = $invRepository
    ->select()
    ->load('client', ['method' => JoinableLoader::INLOAD])  // Separate query with WHERE IN
    ->load('user', ['method' => JoinableLoader::INLOAD])
    ->load('invAmount')  // Regular JOIN for HAS_ONE
    ->fetchAll();
```

**When to use INLOAD:**
- Large result sets (100+ records)
- Relations with many duplicates
- Better for HAS_MANY relations when you do need to load them

### Nested Relations (Multi-Level Joins)

Load relations of relations using dot notation:

```php
$salesOrders = $salesOrderRepository
    ->select()
    ->load('quote')                     // Level 1: Load quote
    ->load('quote.client')              // Level 2: Load quote's client
    ->load('quote.quoteAmount')         // Level 2: Load quote's amount
    ->load('quote.items')               // Level 2: Load quote items
    ->load('quote.items.product')       // Level 3: Load products for each item
    ->fetchAll();
```

**Caution:** Deep nesting can cause performance issues. Limit to 2-3 levels.

### Conditional Loading Based on Relationship

Only load related data when it exists:

```php
$quoteItems = $quoteItemRepository
    ->select()
    ->load('quote')
    ->load('tax_rate')
    ->load('product')       // Nullable: Only loaded if product_id is set
    ->load('task')          // Nullable: Only loaded if task_id is set
    ->fetchAll();
```

Cycle ORM automatically handles nullable relations - no special configuration needed.

## Tables to Avoid Joining

### ❌ HAS_MANY Collections in List Views

**Anti-pattern example:**
```php
// ❌ BAD: Loading all invoices for every client
$clients = $clientRepository
    ->select()
    ->load('invs')              // Could load thousands of invoices!
    ->load('delivery_locations') // Could load many locations per client
    ->fetchAll();
```

**Result:** If you have 100 clients with average 50 invoices each, you load 5,000 invoices unnecessarily.

**Better approach:**
```php
// ✅ GOOD: Load clients only, query invoices separately when needed
$clients = $clientRepository->select()->fetchAll();

// Later, load invoices for a specific client
$invoices = $invRepository
    ->select()
    ->where('client_id', $clientId)
    ->load('invAmount')
    ->fetchAll();
```

### Tables Where Lazy Loading is Acceptable

- **Detail views:** Single entity display where relations are conditionally shown
- **Rarely accessed relations:** Custom fields, notes that aren't always displayed
- **Optional features:** Peppol data, custom values that most entities don't have

## Performance Comparison

### Without Joins (Lazy Loading)

```php
// Display 100 invoices with client names
$invoices = $invRepository->select()->limit(100)->fetchAll();

foreach ($invoices as $invoice) {
    echo $invoice->getNumber() . ' - ' . $invoice->getClient()->getClient_name();
}
```

**Queries executed:** 101 (1 for invoices + 100 for clients) ❌

### With Joins (Eager Loading)

```php
// Display 100 invoices with client names
$invoices = $invRepository
    ->select()
    ->load('client')
    ->limit(100)
    ->fetchAll();

foreach ($invoices as $invoice) {
    echo $invoice->getNumber() . ' - ' . $invoice->getClient()->getClient_name();
}
```

**Queries executed:** 2-3 (1 for invoices + 1-2 for clients via JOIN/INLOAD) ✅

**Performance improvement:** 97-98% reduction in queries!

## Practical Implementation Checklist

### For List/Grid Views (Index Pages)

- [ ] Load all BELONGS_TO relations displayed in the grid
- [ ] Load HAS_ONE relations for amounts/totals
- [ ] Avoid loading HAS_MANY collections
- [ ] Consider INLOAD method for 100+ records

### For Detail/Edit Views

- [ ] Evaluate if lazy loading is sufficient
- [ ] Load only relations actually displayed on the page
- [ ] Use nested loads for breadcrumb trails
- [ ] Load HAS_MANY only when displaying the collection

### For Reports/Exports

- [ ] Always use eager loading for all displayed data
- [ ] Use INLOAD method for large datasets
- [ ] Consider chunking very large exports
- [ ] Monitor memory usage with nested loads

### For API Endpoints

- [ ] Eager load relations included in JSON response
- [ ] Use field selection to limit data transferred
- [ ] Document which relations are included
- [ ] Provide query parameters for client-controlled loading

## Common Repository Patterns

### Base Repository Method for List Queries

```php
// In InvRepository.php
public function findAllWithRelations(array $criteria = []): array
{
    $query = $this->select()
        ->load('client')
        ->load('user')
        ->load('group')
        ->load('invAmount');
    
    foreach ($criteria as $field => $value) {
        $query->where($field, $value);
    }
    
    return $query
        ->orderBy('date_created', 'DESC')
        ->fetchAll();
}
```

### Repository Method for Detail Queries

```php
// In QuoteRepository.php
public function findOneWithFullContext(int $id): ?Quote
{
    return $this->select()
        ->where('id', $id)
        ->load('client')
        ->load('user')
        ->load('group')
        ->load('quoteAmount')
        ->load('items')                 // Load collection for detail view
        ->load('items.product')
        ->load('items.tax_rate')
        ->fetchOne();
}
```

## Debugging Join Queries

### Enable Query Logging

```php
// In development, log queries to see what's being executed
use Cycle\Database\DatabaseManager;

$dbal = $container->get(DatabaseManager::class);
$dbal->database('default')->getLogger()->listen(function ($query) {
    echo $query->getStatement() . "\n";
});
```

### Count Queries in Controller

```php
// Temporary debugging code
$queryCount = 0;
$dbal->database('default')->getLogger()->listen(function () use (&$queryCount) {
    $queryCount++;
});

// Your repository call here
$invoices = $invRepository->select()->load('client')->fetchAll();

echo "Queries executed: " . $queryCount;
```

## Summary Table

| Entity | Recommended Joins | Priority | Use Case |
|--------|------------------|----------|----------|
| `inv` | client, user, group, invAmount | **High** | Invoice lists, reports |
| `quote` | client, user, group, quoteAmount | **High** | Quote lists, conversions |
| `salesOrder` | client, user, group, quote, sales_order_amount | **High** | SO lists, tracking |
| `quoteItem` | quote, tax_rate, product, task | **High** | Item details, calculations |
| `invItem` | inv, tax_rate, product | **High** | Item details, PDFs |
| `salesOrderItem` | sales_order, tax_rate, product | **High** | SO item details |
| `quoteItemAllowanceCharge` | allowance_charge, quote_item, quote | **Medium** | Amount calculations |
| `invItemAllowanceCharge` | allowance_charge, inv_item, inv | **Medium** | Amount calculations |
| `client` | postal_address (avoid HAS_MANY) | **Medium** | Client lists |
| `company` | companyPrivates | **Medium** | Company settings |
| `payment` | inv, inv.client | **Medium** | Payment tracking |
| `quoteTaxRate` | quote, tax_rate | **Low** | Tax calculations |
| `invTaxRate` | inv, tax_rate | **Low** | Tax calculations |

## Related Documentation

- [Cycle ORM HasOne outerKey Issue](CYCLE_ORM_HASONE_OUTERKEY_ISSUE.md) - Important configuration notes
- [TypeScript Build Process](TYPESCRIPT_BUILD_PROCESS.md) - Frontend optimization
- Cycle ORM Documentation: https://cycle-orm.dev/docs/query-builder-complex

---

**Last Updated:** January 2, 2026  
**Maintainer:** Development Team  
**Schema Location:** `runtime/schema.php`
