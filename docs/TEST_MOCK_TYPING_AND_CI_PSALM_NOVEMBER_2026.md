# Typed mocks in tests, and what CI Psalm reports (November 2026)

For contributors writing Testo tests.

## What went wrong

Psalm runs at level 1 over `src/` **and** `Tests/` in CI. `Mockery::mock()` is typed as returning a plain
`MockInterface`, so Psalm rejects a mock wherever the mocked class is expected: a constructor argument, a
declared return type, a method argument. The tests added with the signup and login work produced 90 Psalm
errors, none in `src/`.

CI showed only a sample. GitHub caps annotations at ten per step, so the list on the pull request looked
like 10 errors (shown twice) while the run had many more. A run that is red on CI is red for **all** its
errors: run the full Psalm locally before pushing.

```
vendor/bin/psalm --no-cache --no-progress --threads=4
```

## `Tests\Testo\Support\TypedMock`

| Method | Returns | Use it for |
|---|---|---|
| `TypedMock::of(Foo::class)` | an object typed `Foo&MockInterface` | any mock: Psalm accepts it as a `Foo`, and `shouldReceive()` still works |
| `TypedMock::build(Foo::class, ['bar' => $bar])` | a **real** `Foo`, constructor arguments mocked | dependency bundles (`SignupDeps`, `HomeCareSignupConfirmDeps`, ...) whose properties are `readonly` |

`build()` mocks every object-typed constructor parameter you do not give, so a test names only the
dependencies it cares about. Do not assign to a `readonly` property of a mocked bundle: Psalm reports it,
and it only worked because of `BypassFinals`.

Other patterns that came out of the cleanup:

- Do not subclass a `final` class to stub it (it worked through `BypassFinals`, but Psalm reports it).
  The cleanup test passes the real in-memory `DatabaseManager` instead.
- A helper that returns a mocked class needs the helper's return type to say so, or a `@var` tag on the
  variable; `TypedMock::of()` does this for you.
- `Mockery`'s `->ordered()` orders calls **within one mock**. To assert an order **across** mocks, use
  `->globally()->ordered()`. Check the assertion bites: reverse the expectations in a throwaway copy and
  the test should fail.

## Guarding against typos in entities

`Tests/Testo/Infrastructure/EntityColumnTypesTest.php` scans every Cycle `#[Column(type: '...')]` for
unbalanced brackets. See [the user-inv page](USERINV_EXTENSION_ROW_AND_COLUMN_TYPES_NOVEMBER_2026.md).

## Related

- [Testo integration](TESTO_INTEGRATION.md)
- [Remember-me cookie fix](REMEMBER_ME_COOKIE_FIX_NOVEMBER_2026.md)
- [Live test checklist](SIGNUP_AND_LOGIN_LIVE_TEST_CHECKLIST_NOVEMBER_2026.md)
