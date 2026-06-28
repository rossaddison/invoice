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

### 2. `src/Invoice/Inv/Trait/InvFilterTrait.php`

`filterGuestClient()` (line 96) and `filterClient()` (line 122) both called
`explode(' ', $fullName)` and then accessed `$nameParts[1]` without a guard.
A single-word client name produced an undefined array key. Fixed with `?? ''`:

```php
$secondName = $nameParts[1] ?? '';
```

---

## Files Changed

| File | Change |
|---|---|
| `config/web/di/middleware-dispatcher.php` | Add `RequestInputParametersResolver` to composite |
| `src/Invoice/Inv/Trait/InvFilterTrait.php` | Guard `$nameParts[1]` with `?? ''` (×2) |

---

## Why It Worked on Older Branches

The `sonarqube-parameter-reduction` branch pre-dates the DTO refactor. Its `inv/index`
action still used individual `#[Query('filterInvNumber')]` parameters, which are handled
by the already-registered `HydratorAttributeParametersResolver`. Switching to that branch
made filters appear to work, confirming the breakage was introduced by the DTO consolidation
without the matching resolver registration.

---

## Verification

- Psalm errorLevel 1: zero errors on both changed files.
- `?filterInvNumber=INV251` now correctly returns only the INV251 row.
- All other filter fields (`filterClient`, `filterStatus`, `filterDateCreatedYearMonth`, etc.)
  confirmed working via the same mechanism.
