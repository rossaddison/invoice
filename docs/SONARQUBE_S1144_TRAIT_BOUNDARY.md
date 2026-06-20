# SonarQube S1144 — False Positive: Private Methods Called Across Trait Boundaries

## The Problem

SonarQube S1144 flags private methods it cannot find any callers for:

> "Remove this unused private 'methodName' method."

This becomes a **false positive** when:

1. A method is declared `private` inside a **class file**, and
2. Its only caller is a `$this->method()` call inside a **trait file** that the class composes.

SonarQube's cross-file analysis cannot trace `$this->method()` calls that cross PHP
trait file boundaries. It sees the method as having zero callers and raises S1144
even though the method is genuinely used at runtime.

## Concrete Example (June 2026)

Three private methods in two controllers were flagged:

```
ERROR: php:S1144 - src/Invoice/Inv/InvController.php:388
  Remove this unused private 'displayeditdeletebuttons' method.

ERROR: php:S1144 - src/Invoice/Inv/InvController.php:397
  Remove this unused private 'flashnoenabledgateways' method.

ERROR: php:S1144 - src/Auth/Controller/AuthController.php:814
  Remove this unused private 'redirecttoadminmustmakeactive' method.
```

In each case the method *was* used — just from a trait file:

| Method (class file) | Caller (trait file) |
|---|---|
| `InvController::displayEditDeleteButtons` ([InvController.php:388](../src/Invoice/Inv/InvController.php#L388)) | `$show_buttons = $this->displayEditDeleteButtons($read_only);` — [View.php:72](../src/Invoice/Inv/Trait/View.php#L72) |
| `InvController::flashNoEnabledGateways` ([InvController.php:397](../src/Invoice/Inv/InvController.php#L397)) | `$this->flashNoEnabledGateways($enabled_gateways, ...);` — [View.php:49](../src/Invoice/Inv/Trait/View.php#L49) |
| `AuthController::redirectToAdminMustMakeActive` ([AuthController.php:814](../src/Auth/Controller/AuthController.php#L814)) | `return $this->redirectToAdminMustMakeActive();` — [Callback.php:1295](../src/Auth/Trait/Callback.php#L1295) |

PHP inlines trait code into the composing class at compile time, so all three calls
resolve correctly at runtime. SonarQube's static analysis does not model this.

## The Fix

Change the visibility of the falsely-flagged method from `private` to `protected`:

```php
// Before — triggers S1144 false positive:
private function displayEditDeleteButtons(bool $read_only): bool { ... }

// After — S1144 only fires on private methods; protected is not flagged:
protected function displayEditDeleteButtons(bool $read_only): bool { ... }
```

S1144 is defined as **"Remove this unused *private* method."** Changing to
`protected` is both semantically appropriate (trait callers are effectively
subclass-like consumers) and sufficient to silence the rule without a `// NOSONAR`
suppression.

## Why Not `// NOSONAR`?

`// NOSONAR` suppresses all rules on a line permanently. Changing to `protected`
is the self-documenting fix: it communicates "this method is part of the
protected surface used by composed traits" rather than "we have silenced an
unknown warning here."

## Related

- [SonarQube S1448 — Too Many Methods](SONARQUBE_S107_APPLICATION_SERVICE.md)
  (entity classes split into trait groups to pass the 20-method limit — the
  same trait extraction pattern that introduces S1144 false positives)
- [PHP trait visibility rules](https://www.php.net/manual/en/language.oop5.traits.php) —
  `private` methods in traits are private to the *trait*; `private` methods in the
  *class* are accessible from inlined trait code at runtime but invisible to static
  analysis tools that do not model trait inlining.
