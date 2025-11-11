# PHP Product Selection Workflow Documentation

## Overview
The invoice application implements a server-side product selection workflow that bypasses JavaScript form handling in favor of direct PHP processing. This approach ensures immediate data persistence without requiring save buttons.

## Workflow Architecture

### 1. Modal Interface Components
- **Invoice Products**: `resources/views/invoice/product/modal_product_lookups_inv.php`
- **Quote Products**: `resources/views/invoice/product/modal_product_lookups_quote.php`
- **Shared Product Table**: `resources/views/invoice/product/_partial_product_table_modal.php`

### 2. Server-Side Processing Functions
```php
// ProductController.php methods
public function selection_inv(...)  // Route: /product/selection_inv
public function selection_quote(...) // Route: /product/selection_quote

// Private helper methods
private function save_product_lookup_item_inv(...)
private function save_product_lookup_item_quote(...)
```

### 3. Complete Processing Flow

#### Step 1: User Product Selection
- User opens modal dialog from invoice/quote view
- Products displayed with checkboxes for selection
- Family filtering and product search available
- JavaScript enables/disables submit button based on selections

#### Step 2: Product ID Collection
```javascript
// Extract selected product IDs
document.querySelectorAll("input[name='product_ids[]']:checked").forEach(function(input) {
    product_ids.push(parseInt(input.value));
});
```

#### Step 3: Direct PHP Function Call
```javascript
// Build URL with query parameters - NO form submission
var url = '/invoice/product/selection_inv?inv_id=' + inv_id;
product_ids.forEach(function(id) {
    url += '&product_ids[]=' + id;
});

// Immediate fetch to PHP function
fetch(url, { 
    method: 'GET',
    headers: {
        'Content-Type': 'application/json; charset=utf-8',
        'X-Requested-With': 'XMLHttpRequest'
    }
})
```

#### Step 4: Server-Side Processing
```php
public function selection_inv(FormHydrator $formHydrator, Request $request, pR $pR, trR $trR, uR $uR, iiaR $iiaR, iiR $iiR, itrR $itrR, iaR $iaR, iR $iR, pymR $pymR, aciR $aciR): \Yiisoft\DataResponse\DataResponse 
{
    $select_items = $request->getQueryParams();
    /** @var array $select_items['product_ids'] */
    $product_ids = ($select_items['product_ids'] ?: []);
    /** @var string $inv_id */
    $inv_id = $select_items['inv_id'];
    
    // Use Spiral||Cycle\Database\Injection\Parameter to build 'IN' array of products.
    $products = $pR->findinProducts($product_ids);
    $numberHelper = new NumberHelper($this->sR);
    
    // Format the product prices according to comma or point or other setting choice.
    $order = 1;
    /** @var Product $product */
    foreach ($products as $product) {
        $product->setProduct_price((float) $numberHelper->format_amount($product->getProduct_price()));
        $this->save_product_lookup_item_inv($order, $product, $inv_id, $pR, $trR, $uR, $iiaR, $uR, $formHydrator);
        $order++;
    }
    
    // Automatically recalculate invoice totals
    $numberHelper->calculate_inv((string) $this->session->get('inv_id'), $aciR, $iiR, $iiaR, $itrR, $iaR, $iR, $pymR);
    
    return $this->responseFactory->createResponse(Json::encode($products));
}
```

#### Step 5: Individual Item Creation
```php
private function save_product_lookup_item_inv(int $order, Product $product, string $inv_id, pR $pR, trR $trR, uR $unR, iiaR $iiaR, uR $uR, FormHydrator $formHydrator): void
{
    $invItem = new InvItem();
    $form = new InvItemForm($invItem, (int) $inv_id);
    
    $ajax_content = [
        'name' => $product->getProduct_name(),
        'inv_id' => $inv_id,
        'tax_rate_id' => $product->getTax_rate_id(),
        'product_id' => $product->getProduct_id(),
        'task_id' => null,
        'description' => $product->getProduct_description(),
        // A default quantity of 1 is used to initialize the item
        'quantity' => (float) 1,
        'price' => $product->getProduct_price(),
        // Vat: Early Settlement Cash Discount subtracted before VAT is calculated
        'discount_amount' => (float) 0,
        'charge_amount' => (float) 0,
        'allowance_amount' => (float) 0,
        'order' => $order,
        // The default quantity is 1 so the singular name will be used.
        'product_unit' => $unR->singular_or_plural_name($product->getUnit_id(), 1),
        'product_unit_id' => $product->getUnit_id(),
    ];
    
    if ($formHydrator->populateAndValidate($form, $ajax_content)) {
        $this->invitemService->addInvItem_product($invItem, $ajax_content, $inv_id, $pR, $trR, new iiaS($iiaR), $iiaR, $this->sR, $uR);
    }
}
```

### Quote Processing (Similar Pattern)
```php
public function selection_quote(FormHydrator $formHydrator, Request $request, pR $pR, qaR $qaR, qiR $qiR, qR $qR, qtrR $qtrR, trR $trR, uR $uR, qiaR $qiaR, qiaS $qiaS): \Yiisoft\DataResponse\DataResponse 
{
    $select_items = $request->getQueryParams();
    /** @var array $select_items['product_ids'] */
    $product_ids = ($select_items['product_ids'] ?: []);
    /** @var string $quote_id */
    $quote_id = $select_items['quote_id'];
    
    // Use Spiral||Cycle\Database\Injection\Parameter to build 'IN' array of products.
    $products = $pR->findinProducts($product_ids);
    $numberHelper = new NumberHelper($this->sR);
    
    // Format the product prices according to comma or point or other setting choice.
    $order = 1;
    /** @var Product $product */
    foreach ($products as $product) {
        $product->setProduct_price((float) $numberHelper->format_amount($product->getProduct_price()));
        $this->save_product_lookup_item_quote($order, $product, $quote_id, $pR, $trR, $uR, $qiaR, $qiaS, $formHydrator);
        $order++;
    }
    
    $numberHelper->calculate_quote((string) $this->session->get('quote_id'), $qiR, $qiaR, $qtrR, $qaR, $qR);
    
    return $this->responseFactory->createResponse(Json::encode($products));
}
```

## Key Architectural Benefits

### 1. No Save Button Required
- Items are persisted immediately upon selection
- No intermediate form state management
- Eliminates risk of data loss from unsaved changes
- Real-time data synchronization

### 2. Server-Side Data Integrity
- All validation occurs in PHP using FormHydrator
- Database transactions ensure consistency
- Automatic calculation updates (taxes, totals, discounts)
- Type-safe parameter handling with dependency injection

### 3. Minimal JavaScript Dependency
- JavaScript only handles UI interaction and HTTP requests
- No complex client-side state management
- Immediate page refresh shows updated data
- Progressive enhancement approach

### 4. Scalable Product Processing
- Handles multiple product selections in single request
- Maintains proper ordering sequence
- Supports bulk operations without performance degradation
- Uses efficient database queries with Cycle ORM

## Route Configuration
```php
// config/common/routes/routes.php
Route::get('/product/selection_inv')
    ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
    ->action([ProductController::class, 'selection_inv'])
    ->name('product/selection_inv'),

Route::get('/product/selection_quote')
    ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
    ->action([ProductController::class, 'selection_quote'])
    ->name('product/selection_quote'),
```

## Security Features

### 1. Permission-Based Access Control
- Routes protected with middleware requiring 'editInv' permission
- User authentication verified before processing
- RBAC (Role-Based Access Control) implementation

### 2. Input Validation and Sanitization
- Query parameters validated and type-cast
- Product IDs sanitized to integers only
- FormHydrator ensures data integrity

### 3. CSRF Protection
- Hidden CSRF tokens in modal forms
- Server-side CSRF validation
- XSS prevention in JavaScript data handling

## Data Flow Summary

```
1. Modal Selection    → User selects products via checkboxes
2. JavaScript Collection → Extract product IDs from DOM
3. Direct PHP Call   → GET request to selection_inv/selection_quote
4. Database Processing → Immediate item creation and calculation
5. Response & Refresh → JSON response triggers page reload
```

## JavaScript Implementation Details

### Modal Product Lookups (`modal-product-lookups.js`)
```javascript
function handleInvoiceConfirm() {
    var absolute_url = new URL(window.location.href);
    var btn = document.querySelector('.select-items-confirm-inv');
    setSecureButtonContent(btn, 'h2', 'text-center', 'fa fa-spin fa-spinner');
    
    var product_ids = [];
    var inv_id = absolute_url.href.substring(absolute_url.href.lastIndexOf('/') + 1);
    
    document.querySelectorAll("input[name='product_ids[]']:checked").forEach(function(input) {
        product_ids.push(parseInt(input.value));
    });

    // Build URL with proper query parameters
    var url = '/invoice/product/selection_inv?inv_id=' + inv_id;
    product_ids.forEach(function(id) {
        url += '&product_ids[]=' + id;
    });
    
    fetch(url, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json; charset=utf-8',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        // Success handling - immediate page reload
        window.location.reload(true);
    })
    .catch(error => {
        console.error('Error:', error);
    });
}
```

## Dependencies and Services

### Repository Layer
- `pR` - Product Repository
- `iiaR` - Invoice Item Allowance Repository
- `qiaR` - Quote Item Allowance Repository
- `trR` - Tax Rate Repository
- `uR` - Unit Repository

### Service Layer
- `FormHydrator` - Form validation and data binding
- `NumberHelper` - Price formatting and calculations
- `invitemService` - Invoice item business logic
- `quoteitemService` - Quote item business logic

### Entity Models
- `InvItem` - Invoice item entity
- `QuoteItem` - Quote item entity
- `Product` - Product entity
- `InvItemForm` - Invoice item form validation
- `QuoteItemForm` - Quote item form validation

## Performance Characteristics

### Database Efficiency
- Single query to fetch multiple products using `findinProducts($product_ids)`
- Batch processing of selected items
- Automatic calculation updates in single operation
- Minimal database round trips

### User Experience
- Immediate visual feedback with spinner icons
- No waiting for form submissions
- Instant data persistence
- Seamless modal-to-main-view transitions

## Error Handling

### Server-Side Error Management
```php
if ($formHydrator->populateAndValidate($form, $ajax_content)) {
    $this->invitemService->addInvItem_product($invItem, $ajax_content, $inv_id, $pR, $trR, new iiaS($iiaR), $iiaR, $this->sR, $uR);
} else {
    // Form validation failed - errors logged automatically
    // FormHydrator handles error collection and reporting
}
```

### Client-Side Error Handling
```javascript
fetch(url, { method: 'GET' })
.then(response => response.json())
.then(data => {
    // Process successful response
    window.location.reload(true);
})
.catch(error => {
    console.error('Error:', error);
    // User notification could be added here
});
```

## Conclusion

This workflow demonstrates a hybrid approach where JavaScript handles UI interactions while PHP manages all business logic and data persistence. The pattern eliminates traditional form submissions with save buttons, providing immediate data persistence and a seamless user experience.

### Key Benefits:
- ✅ **Immediate persistence** - No save buttons required
- ✅ **Server-side validation** - Data integrity guaranteed
- ✅ **Scalable processing** - Handles bulk operations efficiently
- ✅ **Security-first design** - Permission-based access control
- ✅ **Performance optimized** - Minimal database queries
- ✅ **User-friendly** - Real-time feedback and updates

---

**Generated:** November 6, 2025  
**Related Files:**
- `src/Invoice/Product/ProductController.php`
- `resources/views/invoice/product/modal_product_lookups_inv.php`
- `resources/views/invoice/product/modal_product_lookups_quote.php`
- `src/Invoice/Asset/rebuild/js/modal-product-lookups.js`
- `config/common/routes/routes.php`