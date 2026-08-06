# Authentication DI Crash Fix — August 2026

## Summary

A `composer update` that bumped `yiisoft/auth` surfaced an application-wide
500 error:

```
No definition or class found for "Yiisoft\Auth\Middleware\Authentication" ID.
— Yiisoft\Di\NotFoundException (Code #0)
No definition or class found or resolvable for
"Yiisoft\Auth\AuthenticatorInterface" while building
"Yiisoft\Auth\Middleware\Authentication" -> "Yiisoft\Auth\AuthenticatorInterface".
```

## Root cause

`Yiisoft\Auth\Middleware\Authentication` requires a
`Yiisoft\Auth\AuthenticatorInterface` DI binding — one has never existed
anywhere in this app, in any version of `yiisoft/auth`: no implementation
class, no config binding. The middleware has been unconstructable since the
day it was first referenced; the composer update most likely just exposed
it, by invalidating a stale compiled DI container cache that had been
masking the failure.

## Two-part fix

**Part 1** (commit `1066ac8e`) removed a duplicate
`->middleware(Authentication::class)` from
`config/common/routes/routes-backend.php`'s `/backend/hmrc` group. This
looked like the whole fix — a live curl confirmed `/backend/hmrc` returning
a clean `403` instead of `500` — but only covered that one narrow route
group.

**Part 2, the actual high-impact fix** (commit `4fb3a633`): the true root
cause was `src/Middleware/RoutePermission.php`'s `invoiceGroup()` static
method, which wraps almost every `/invoice/*` route in the entire app
(~70+ route files call it) and applied the exact same broken middleware.
Confirmed via live curl that `/invoice/client_invoices` 500'd before this
fix and returned a clean `403` after. Removed alongside it:

- The same dead pattern in
  `resources/views/invoice/generator/templates_protected/_route.php`, the
  Gii-style route-scaffold template used to generate new modules — left
  unfixed, it would have reintroduced this exact crash into every future
  generated module's routes.
- Two inert docblock examples in `ProductImageController.php` and
  `UploadController.php` that documented (but didn't execute) the same
  broken pattern.

The real, working RBAC gate everywhere in this app is
`RoutePermission::check()` — session-based, via `AccessChecker` —
completely independent of `Yiisoft\Auth`. `Authentication::class` was
always redundant dead weight riding alongside it, not a functioning
authentication layer.

## Verification

- `php -l` clean on all 4 changed files.
- Full-project Psalm (`vendor/bin/psalm --no-cache`): no errors found.
- Full Testo suite: 756/756 passing.
- Full PHPUnit suite: 3,877/3,877 passing (23 pre-existing Cycle ORM
  `createMock()` notices only, no new issues).
- Live curl across a broad route sample
  (`invoice/{inv,client,quote,product,productimage,upload}`,
  `backend/hmrc`) — all return clean `403`s instead of `500`s.
