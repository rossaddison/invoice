# PHPUnit 13's Stub-Without-Expectations Check — August 2026

## Summary

This project runs PHPUnit `>=13.1.4` (`composer.json`), currently `13.1.14`.
PHPUnit 13 added a check that flags `createMock()` objects configured with
return values but never actually verified against — 23 tests across 4
files in this suite were hitting it, silently, as `N` (notice) markers
that PHPUnit's default dot-progress output doesn't even print inline. Full
detail on the fix itself: `bd0c30e1`. This doc is about the PHPUnit
feature that surfaced it.

## What changed in PHPUnit 13

`PHPUnit\Framework\TestCase::verifyMockObjects()`
(`vendor/phpunit/phpunit/src/Framework/TestCase.php`) runs after every
test and inspects each mock object created during it. For each one, it
checks two things:

- `__phpunit_hasInvocationCountRule()` — was `->expects(...)` ever called
  on this mock? (`$this->once()`, `$this->never()`, `$this->exactly(N)`,
  etc.)
- `__phpunit_hasParametersRule()` — was `->with(...)` ever called on it?

If **neither** is true — the mock was only ever given `->method('x')
->willReturn(y)` or `->willReturnMap([...])`, with no assertion about
whether or how it's actually called — PHPUnit now emits a notice:

> No expectations were configured for the mock object for `Fully\Qualified\ClassName`.
> Consider refactoring your test code to use a test stub instead.
> The `#[AllowMockObjectsWithoutExpectations]` attribute can be used to
> opt out of this check.

## Why this is a real improvement, not noise

`createMock()` and `createStub()` do the same underlying job (a fake
object implementing a given class/interface's shape), but they document
different intent: a **mock** is for asserting *behavior* — "this method
must be called exactly once, with these arguments" — while a **stub** is
for controlling *state* — "when this method is called, return this
value," with no claim about whether it's called at all.

Before this check existed, both cases used `createMock()`
indiscriminately, so a reader couldn't tell from the test itself whether
a given mock's return value configuration was load-bearing (the test
would fail if the code under test stopped calling it a certain way) or
incidental (just satisfying a constructor's type hint). The check makes
that distinction visible automatically, at the exact moment it can be
verified — after the test has actually run.

## The fix: `#[AllowMockObjectsWithoutExpectations]`

`PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations` targets
both classes and methods (`Attribute::TARGET_CLASS |
Attribute::TARGET_METHOD`). This project's fix (`bd0c30e1`) applied it at
class level in all 4 affected files, since in every one **100%** of that
file's test methods were flagged (13/13, 5/5, 3/3, 2/2) — no method in
any of those classes would lose check coverage by silencing it at the
class level.

Each affected test method mixes real behavioral assertions
(`expects($this->once())->method(...)`) on some mocks with other mocks
used purely as stubs (unrelated repository dependencies a given test
doesn't care about, only present to satisfy a service's constructor). The
attribute does not touch existing `expects()` assertions — those still
run, and the test still fails if they're unmet. It only tells PHPUnit
"the stub-only mocks in this class are intentional, stop flagging them."

The alternative — converting every flagged `createMock()` call to
`createStub()` — was considered and rejected here: several of the same
mock *type* (e.g. `AllowanceChargeRepository` in
`InvAllowanceChargeServiceTest`) is used with real `expects()` in one test
method and as a pure stub in another, both drawing from the same shared
test-fixture helper. A blanket `createStub()` swap in the helper would
have silently broken the tests that rely on real verification.

## Result

```
vendor/bin/phpunit --testsuite=Unit
OK (3824 tests, 10243 assertions)
```

Zero `N` markers — the suite is genuinely notice-free, not just
tolerating a known set. `CLAUDE.md`'s test-quality standard was updated
(`4bfcc93c`) to reflect this as the expected baseline going forward,
replacing the earlier "known/acceptable" carve-out.

## Related PHPUnit 13 context (not used here, but adjacent)

`verifyMockObjects()` also checks `requireSealedMockObjects()` — a
separate, opt-in stricter mode that flags mocks whose invocation handler
was never "sealed." This project doesn't enable that mode; it's mentioned
here only because it's the other branch of the same method and worth
knowing about if PHPUnit's mocking strictness needs revisiting again.
