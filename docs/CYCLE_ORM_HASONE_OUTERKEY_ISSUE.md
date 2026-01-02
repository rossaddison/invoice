# Cycle ORM HasOne Relationship and outerKey Issue

## Overview

This document describes a critical issue with Cycle ORM's `HasOne` relationship attribute when dealing with camelCase foreign key references in related entities.

## The Problem

When defining a `HasOne` relationship in Cycle ORM, if the related entity uses a **camelCase property name** for the foreign key, you must explicitly specify the `outerKey` parameter in the relationship attribute. Failure to do so can result in runtime errors or incorrect relationship mapping.

### Example Scenario

Consider two entities:
- `QuoteItem` - the parent entity
- `QuoteItemAmount` - the child entity with a foreign key back to `QuoteItem`

## Incorrect Configuration (Causes Issues)

```php
// In QuoteItem entity
#[HasOne(target: QuoteItemAmount::class)]
private ?QuoteItemAmount $quote_item_amount = null;
```

**Problem**: When `QuoteItemAmount` entity has a property like `$quote_item_id` , Cycle ORM may not correctly infer the foreign key column name, leading to:
- SQL errors during relationship loading
- NULL values when accessing related entities
- Unexpected behavior in queries

## Correct Configuration (Solution)

```php
// In QuoteItem entity
#[HasOne(target: QuoteItemAmount::class, outerKey: 'quote_item_id')]
private ?QuoteItemAmount $quote_item_amount = null;
```

**Explanation**: The `outerKey: 'quote_item_id'` parameter explicitly tells
Cycle ORM:
- The foreign key property name in the `QuoteItemAmount` entity is `quote_item_id`
- This property references the primary key of the `QuoteItem` entity
- Leaving outerKey out could result in the table being injected with
  a foreign key e.g. quoteItem_id in addition to the currently existing
  quote_item_id. Therefore causing relation issues.

## Why This Happens

Cycle ORM's automatic inference mechanism may struggle with:
1. **Naming Convention Mismatches**: When property names use camelCase but
   database columns use snake_case.
2. **Custom Property Names**: When the foreign key doesn't follow standard
   naming patterns
3. **Multi-word Entity Names**: Entities like `QuoteItemAmount` where the
   foreign key could be ambiguous

## Best Practices

### 1. Always Specify outerKey for HasOne, and HasMany Relationships

```php
#[HasOne(target: RelatedEntity::class, outerKey: 'foreign_key_property')]
private ?RelatedEntity $relation = null;
```

### 2. Use Consistent Naming

- Database columns: `snake_case` (e.g., `quote_item_id`)
- Entity properties: Match database columns exactly or use explicit mapping
- Relationship keys: Always specify `outerKey` when the foreign key property exists

### 3. Document Complex Relationships

```php
/**
 * @var QuoteItemAmount|null
 * Related via QuoteItemAmount.quote_item_id -> QuoteItem.id
 */
#[HasOne(target: QuoteItemAmount::class, outerKey: 'quote_item_id')]
private ?QuoteItemAmount $quote_item_amount = null;
```

## Common Patterns in This Codebase

### Quote and QuoteAmount

```php
// In Quote entity
#[HasOne(target: QuoteAmount::class, outerKey: 'quote_id')]
private ?QuoteAmount $quote_amount = null;
```

### QuoteItem and QuoteItemAmount

```php
// In QuoteItem entity
#[HasOne(target: QuoteItemAmount::class, outerKey: 'quote_item_id')]
private ?QuoteItemAmount $quote_item_amount = null;
```

### Invoice and InvAmount

```php
// In Inv entity
#[HasOne(target: InvAmount::class, outerKey: 'inv_id')]
private ?InvAmount $inv_amount = null;
```

### SalesOrder and SalesOrderAmount

```php
// In SalesOrder entity
#[HasOne(target: SalesOrderAmount::class, outerKey: 'sales_order_id')]
private ?SalesOrderAmount $sales_order_amount = null;
```

## Debugging Tips

### 1. Check SQL Queries

Enable Cycle ORM query logging to see if relationships are being resolved correctly:

```php
// Check generated SQL for JOIN conditions
$quote = $quoteRepository->findOne(['id' => $id]);
$amount = $quote->getQuote_amount(); // Check if this triggers correct SQL
```

### 2. Verify Foreign Key Properties

Ensure the related entity has the correct property:

```php
// In QuoteItemAmount entity
class QuoteItemAmount
{
    #[Column(type: 'string')]
    private string $quote_item_id = ''; // Must match outerKey value
}
```

### 3. Test Relationship Loading

```php
// Test both directions of the relationship
$quoteItem = $quoteItemRepository->findById($id);
$amount = $quoteItem->getQuote_item_amount(); // Should not be null if record exists

// Verify the foreign key value
if ($amount) {
    assert($amount->getQuote_item_id() === $quoteItem->getId());
}
```

## Migration Guide

If you have existing `HasOne` relationships without `outerKey` specified:

### Step 1: Identify All HasOne Relationships

```bash
grep -r "#\[HasOne" src/Invoice/Entity/
```

### Step 2: Determine the Foreign Key Property

For each relationship, check the target entity to find the foreign key property name.

### Step 3: Add outerKey Parameter

Update each `HasOne` attribute with the explicit `outerKey`:

```php
// Before
#[HasOne(target: QuoteItemAmount::class)]

// After
#[HasOne(target: QuoteItemAmount::class, outerKey: 'quote_item_id')]
```

### Step 4: Test Thoroughly

- Run unit tests
- Test relationship loading in development
- Verify Psalm/static analysis passes
- Check database queries are optimized

## Related Cycle ORM Attributes

### BelongsTo (Inverse Relationship)

When defining the inverse relationship, use `innerKey`:

```php
// In QuoteItemAmount entity
#[BelongsTo(target: QuoteItem::class, innerKey: 'quote_item_id')]
private ?QuoteItem $quote_item = null;
```

### HasMany (One-to-Many)

Similar considerations apply:

```php
// In Quote entity
#[HasMany(target: QuoteItem::class, outerKey: 'quote_id')]
private ArrayCollection $items;
```

## Additional Resources

- [Cycle ORM Documentation: Relations](https://cycle-orm.dev/docs/relations)
- [Cycle ORM Annotated Entities](https://cycle-orm.dev/docs/annotated-relations)
- Project Entity Files: `src/Invoice/Entity/`

## Conclusion

Always explicitly specify the `outerKey` parameter when defining `HasOne` relationships in Cycle ORM, especially when:
- Working with camelCase property names
- The foreign key property name doesn't follow automatic inference patterns
- You want clear, maintainable code that documents relationship structure

This small addition prevents runtime errors and makes relationship mapping explicit and reliable.
