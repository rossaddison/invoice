# Cycle ORM Foreign Key Constraint Violation Issue

## Problem

When persisting child entities with foreign key relationships in Cycle ORM, you may encounter:

```
Cycle\Database\Exception\StatementException\ConstrainException (Code #23000)
SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: 
a foreign key constraint fails (`yii3_i`.`inv_item_allowance_charge`, 
CONSTRAINT `inv_item_allowance_charge_foreign_inv_id_...` FOREIGN KEY (`inv_id`) REFERENCES `inv` (`id`))
```

## Root Cause

This error occurs when Cycle ORM cannot determine the correct persistence order for entities with `BelongsTo` relationships. The issue happens when:

1. **Setting only foreign key IDs without relationship objects**
2. **Nullifying relationship objects while maintaining FK IDs**
3. **Not loading parent entities before saving child entities**

### Example of Problematic Code

```php
// InvItemAllowanceChargeService.php - PROBLEMATIC
public function saveInvItemAllowanceCharge(InvItemAllowanceCharge $model, array $array, float $vat_or_tax): void
{
    // This nullifies the relationship objects!
    $model->nullifyRelationOnChange(
        (int) $array['allowance_charge_id'], 
        (int) $array['inv_item_id'], 
        (int) $array['inv_id']
    );
    
    // Only setting FK IDs - no relationship objects
    $model->setInv_id((int) $array['inv_id']);
    $model->setInv_item_id((int) $array['inv_item_id']);
    $model->setAllowance_charge_id((int) $array['allowance_charge_id']);
    
    // Cycle ORM doesn't know the persistence order!
    $this->repository->save($model);
}
```

## Why This Happens

Cycle ORM uses relationship objects (not just FK IDs) to build a dependency graph and determine:
- Which entities to persist first
- What order to execute INSERT/UPDATE operations
- How to handle cascading operations

When relationship objects are `null` but FK IDs are set, Cycle ORM:
1. Sees the FK ID values
2. Tries to INSERT the child record
3. **Fails** because the parent record might not exist or isn't persisted yet
4. Cannot cascade/defer the operation correctly

## Solutions

### Solution 1: Load and Set Relationship Objects (Recommended)

Modify the service to accept repositories and load the actual entities:

```php
// InvItemAllowanceChargeService.php - FIXED
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\InvItem\InvItemRepository as IIR;
use App\Invoice\AllowanceCharge\AllowanceChargeRepository as ACR;

final readonly class InvItemAllowanceChargeService
{
    public function __construct(
        private ACIIR $repository,
        private IR $invRepository,
        private IIR $invItemRepository,
        private ACR $allowanceChargeRepository
    ) {}

    public function saveInvItemAllowanceCharge(
        InvItemAllowanceCharge $model, 
        array $array, 
        float $vat_or_tax
    ): void {
        // Load and set the actual relationship entities
        if (isset($array['inv_id'])) {
            $inv = $this->invRepository->findOne(['id' => (int) $array['inv_id']]);
            if ($inv) {
                $model->setInv($inv);
            }
            $model->setInv_id((int) $array['inv_id']);
        }
        
        if (isset($array['inv_item_id'])) {
            $invItem = $this->invItemRepository->findOne(['id' => (int) $array['inv_item_id']]);
            if ($invItem) {
                $model->setInvItem($invItem);
            }
            $model->setInv_item_id((int) $array['inv_item_id']);
        }
        
        if (isset($array['allowance_charge_id'])) {
            $ac = $this->allowanceChargeRepository->findOne(['id' => (int) $array['allowance_charge_id']]);
            if ($ac) {
                $model->setAllowanceCharge($ac);
            }
            $model->setAllowance_charge_id((int) $array['allowance_charge_id']);
        }
        
        if (isset($array['amount'])) {
            $model->setAmount((float) $array['amount']);
        }
        
        $model->setVatOrTax($vat_or_tax);
        
        // Now Cycle ORM knows the correct persistence order
        $this->repository->save($model);
    }
}
```

### Solution 2: Pass Entities Directly

When you already have the parent entities loaded, pass them directly:

```php
// SalesOrderController.php - copy_so_item_allowance_charges_to_inv
private function copy_so_item_allowance_charges_to_inv(
    string $origSoItemId, 
    ACSOIR $acsoiR, 
    Inv $inv,  // Pass the Inv entity
    InvItem $newInvItem,  // Pass the InvItem entity
    ACIIR $aciiR
): void {
    $all = $acsoiR->repoSalesOrderItemquery($origSoItemId);
    
    foreach ($all as $salesOrderItemAllowanceCharge) {
        $acInvItem = new InvItemAllowanceCharge();
        
        // Set the relationship objects directly
        $acInvItem->setInv($inv);
        $acInvItem->setInvItem($newInvItem);
        $acInvItem->setAllowanceCharge(
            $salesOrderItemAllowanceCharge->getAllowanceCharge()
        );
        
        // Also set FK IDs for consistency
        $acInvItem->setInv_id((int) $inv->getId());
        $acInvItem->setInv_item_id((int) $newInvItem->getId());
        $acInvItem->setAllowance_charge_id(
            (int) $salesOrderItemAllowanceCharge->getAllowanceCharge()?->getId()
        );
        
        // Set other properties
        $acInvItem->setAmount((float) $salesOrderItemAllowanceCharge->getAmount());
        $acInvItem->setVatOrTax((float) $salesOrderItemAllowanceCharge->getVatOrTax() ?: 0.00);
        
        // Cycle ORM now knows the correct order
        $aciiR->save($acInvItem);
    }
}
```

### Solution 3: Remove nullifyRelationOnChange

If the `nullifyRelationOnChange` method is causing issues, consider:

1. **Remove it entirely** if relationships don't actually change
2. **Only nullify when IDs actually change** (not on every save)
3. **Reload entities after nullifying** before saving

```php
// Entity method - IMPROVED
public function nullifyRelationOnChange(int $allowance_charge_id, int $inv_item_id, int $inv_id): void
{
    // Only nullify if the ID is actually changing
    if ($this->allowance_charge_id !== null && $this->allowance_charge_id != $allowance_charge_id) {
        $this->allowance_charge = null;
    }
    if ($this->inv_item_id !== null && $this->inv_item_id != $inv_item_id) {
        $this->inv_item = null;
    }
    if ($this->inv_id !== null && $this->inv_id != $inv_id) {
        $this->inv = null;
    }
}
```

## Best Practices

### 1. Always Set Relationship Objects When Available

```php
// GOOD
$invItem->setInv($inv);  // Set the object
$invItem->setInv_id((int) $inv->getId());  // Set the FK

// BAD
$invItem->setInv_id($inv_id);  // Only FK, no object
```

### 2. Load Entities Before Creating Children

```php
// GOOD - Load parent first
$inv = $invRepository->findOne(['id' => $inv_id]);
if ($inv) {
    $invItem = new InvItem();
    $invItem->setInv($inv);  // Relationship object set
    $invItemRepository->save($invItem);
}

// BAD - Just use ID
$invItem = new InvItem();
$invItem->setInv_id($inv_id);  // No relationship object
$invItemRepository->save($invItem);  // May fail!
```

### 3. Understand Cycle ORM Persistence

Cycle ORM's EntityManager:
- Analyzes relationship objects to build a dependency graph
- Determines INSERT order based on `BelongsTo` relationships
- Cascades persist operations from parent to child
- **Requires relationship objects to work correctly**

## Common Scenarios

### Scenario 1: Copying Records Between Tables

When copying from SalesOrder to Invoice:

```php
// Load the target parent entity
$inv = $invRepository->findOne(['id' => $inv_id]);

foreach ($sourceItems as $sourceItem) {
    $newItem = new InvItem();
    $newItem->setInv($inv);  // Set the Inv object!
    // ... copy other properties
    $repository->save($newItem);
}
```

### Scenario 2: Creating Related Entities

```php
// Create parent first
$inv = new Inv();
// ... set inv properties
$invRepository->save($inv);

// Create child with relationship
$invItem = new InvItem();
$invItem->setInv($inv);  // Set the object
$invItem->setInv_id((int) $inv->getId());  // Also set FK
$invItemRepository->save($invItem);
```

### Scenario 3: Updating Relationships

```php
// Load both entities
$invItem = $invItemRepository->findOne(['id' => $item_id]);
$newInv = $invRepository->findOne(['id' => $new_inv_id]);

if ($invItem && $newInv) {
    $invItem->setInv($newInv);  // Update object
    $invItem->setInv_id((int) $newInv->getId());  // Update FK
    $invItemRepository->save($invItem);
}
```

## Debugging Tips

1. **Check if relationship objects are null**: `var_dump($entity->getInv())`
2. **Verify FK IDs are set**: `var_dump($entity->getInv_id())`
3. **Enable Cycle ORM query logging** to see SQL execution order
4. **Check entity state before save**: Ensure all `BelongsTo` relationships have objects set
5. **Use Cycle's `transaction()` method** for complex multi-entity operations

## Related Documentation

- [docs/CYCLE_ORM_HASONE_OUTERKEY_ISSUE.md](CYCLE_ORM_HASONE_OUTERKEY_ISSUE.md)
- [docs/CYCLE_ORM_JOIN_OPTIMIZATION.md](CYCLE_ORM_JOIN_OPTIMIZATION.md)
- Official Cycle ORM Docs: https://cycle-orm.dev/docs/relation-belongs-to
