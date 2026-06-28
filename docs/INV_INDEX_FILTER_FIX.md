# Invoice Index Filter Fix — `RequestInputParametersResolver` Missing from Middleware Dispatcher

## Symptom

On `inv/index`, selecting any filter dropdown (invoice number, client, status, year-month, etc.)
and submitting the form produced a correctly-formed URL such as:

```
/inv?filterInvNumber=INV251&filterFamilyName=&filterStatus=&filterClient=&...
```

But the grid returned **all rows unchanged** — the filter was silently ignored every time.

---

## Root Cause

### Background: two Yii3 approaches to reading query parameters

| Approach | Attribute | Works when |
|---|---|---|
| Individual parameter injection | `#[Query('filterInvNumber')]` on a method param | `HydratorAttributeParametersResolver` is in the composite |
| DTO class injection | `#[FromQuery]` on the class + implements `RequestInputInterface` | `RequestInputParametersResolver` is in the composite |

The old `inv/index` action used individual `#[Query(...)]` attributes — one per filter field —
directly on the controller method signature. These were resolved by
`HydratorAttributeParametersResolver`, which **was** registered.

A later refactor consolidated all eleven filter fields into a single
`InvIndexFilter` DTO class:

```php
#[FromQuery]
final class InvIndexFilter implements RequestInputInterface
{
    public ?string $filterInvNumber = null;
    public ?string $filterClient    = null;
    // ... nine more fields
}
```

However, the corresponding resolver — `RequestInputParametersResolver` — was **never added**
to `config/web/di/middleware-dispatcher.php`.

### What happened at runtime

`CompositeParametersResolver` tried its single registered resolver
(`HydratorAttributeParametersResolver`) against the `InvIndexFilter $filter` parameter.
Because the parameter itself carries no `#[Query]` or `#[Body]` attribute,
`ParameterAttributesHandler::handle()` returned an unresolved result.
The composite returned nothing for that parameter, so Yii3's DI container stepped in and
created a fresh `InvIndexFilter` with **all properties at their declared defaults (`null`)**.

Back in `Trait/Index.php`, every filter check reads:

```php
if (isset($filter->filterInvNumber) && !empty($filter->filterInvNumber)) { ... }
```

`isset(null)` is `false` — so not a single filter branch ever fired, and the full
unfiltered result set was always returned.

The same silent failure affected `InvGuestFilter`, which follows the identical pattern.

---

## Fix

### 1. `config/web/di/middleware-dispatcher.php`

Added `RequestInputParametersResolver` to the `CompositeParametersResolver`:

```php
use Yiisoft\Input\Http\RequestInputParametersResolver;

return [
    ParametersResolverInterface::class => [
        'class' => CompositeParametersResolver::class,
        '__construct()' => [
            Reference::to(HydratorAttributeParametersResolver::class),
            Reference::to(RequestInputParametersResolver::class),  // ← added
        ],
    ],
];
```

`RequestInputParametersResolver` is already defined by the vendor package in
`vendor/yiisoft/input-http/config/di-web.php` (wired with a `ValidatingHydrator`).
It only activates for parameters whose type implements `RequestInputInterface`, so there
is no risk to any other controller parameter resolution.

### 2. `filterClient` / `filterGuestClient` — ID-based matching

**Problem (post-resolver fix):** Both methods split `getClientFullName()` on the first space and
matched `client_name` + `client_surname` separately. This failed in two cases:

- A client whose entire name is stored in `client_name` with no surname (e.g. name = `"Non Foreign"`,
  surname = null) — the split produced `$firstName = "Non"`, `$secondName = "Foreign"`, but the
  query `WHERE client_name = 'Non' AND client_surname = 'Foreign'` found nothing.
- Any case mismatch between `client_full_name` stored in the DB and the individual fields.

**Fix:** The dropdown option key was changed from `getClientFullName()` to `(string) $client->reqId()`
in `optionsDataClientsFilter()` and `optionsDataUserClientsFilter()`. Both filter methods now receive
the client ID string and query `WHERE client.id = N` — unambiguous regardless of how names are stored
or capitalised.

```php
// optionsDataClientsFilter — key is now the ID, value is the display name
$optionsDataClients[(string) $client->reqId()] = $client->getClientFullName();

// filterClient — query by ID
public function filterClient(string $clientId): EntityReader
{
    $query = $this->select()
        ->load(['client'])
        ->where(['client.id' => (int) $clientId])
        ->where('deleted_at', null);
    return $this->prepareDataReader($query);
}
```

### 3. `filterFamilyName` — PHP-side ID collection

**Problem:** The original query used `->load('items')` then filtered with:

```php
->where(['items.product.family.family_name' => $invFamilyName])
```

Cycle ORM cannot resolve a three-level nested relation path (`items → product → family`) in a
WHERE clause when only the top-level relation is loaded via `load()`. The filter silently matched
no rows.

**Fix:** Iterate all invoices with `findAllPreloaded()`, use the existing PHP-side
`getFirstItemFamilyName()` method (which traverses the lazy-loaded chain correctly), collect
matching IDs, then query `WHERE id IN (...)` — the same pattern used by `filterCreditInvNumber`.

```php
public function filterFamilyName(string $invFamilyName): EntityReader
{
    $trimmed = ltrim(rtrim($invFamilyName));
    $ids = [];
    foreach ($this->findAllPreloaded() as $inv) {
        if ($inv->getFirstItemFamilyName() === $trimmed) {
            $ids[] = (string) $inv->reqId();
        }
    }
    $query = $ids === []
        ? $this->select()->where(['id' => '0'])->where('deleted_at', null)
        : $this->select()->where(['id' => ['in' => new Parameter($ids)]])->where('deleted_at', null);
    return $this->prepareDataReader($query);
}
```

---

## Files Changed

| File | Change |
|---|---|
| `config/web/di/middleware-dispatcher.php` | Add `RequestInputParametersResolver` to composite |
| `src/Invoice/Inv/Trait/InvFilterTrait.php` | Fix `filterClient`, `filterGuestClient`, `filterFamilyName` |
| `src/Invoice/Inv/Trait/OptionsData.php` | Use client ID (not full name) as dropdown option key |
| `src/Invoice/Inv/InvService.php` | Honour user-supplied `date_tax_point` before auto-calculating |
| `src/Widget/FormFields.php` | Add `onclick => this.showPicker()` to `dateCreatedField` |

---

## Why It Worked on Older Branches

The `sonarqube-parameter-reduction` branch pre-dates the DTO refactor. Its `inv/index`
action still used individual `#[Query('filterInvNumber')]` parameters, which are handled
by the already-registered `HydratorAttributeParametersResolver`. Switching to that branch
made filters appear to work, confirming the breakage was introduced by the DTO consolidation
without the matching resolver registration.

---

## Verification

- Psalm errorLevel 1: zero errors across all changed files.
- `?filterInvNumber=INV251` correctly returns only the INV251 row.
- Client filter confirmed working for clients with combined first+surname names and for
  clients whose full name is stored entirely in `client_name`.
- Family name filter confirmed working via PHP-side ID collection.
