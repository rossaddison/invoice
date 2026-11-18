# SonarQube: returns per method, class size and returned temporaries (November 2026)

A scan after the November signup and Telegram work reported 14 findings under three rules. All three are cheap to
keep clean if the patterns below are used as the code is written.

| Rule | Limit | Findings |
|---|---|---|
| **S1142** a method has too many returns | 3 | 8 methods |
| **S1448** a class has too many methods | 20 | 2 classes (`UserInvController` 23, `TelegramController` 21) |
| **S1488** a variable is assigned and immediately returned | none | 4 places |

## S1142: more than 3 returns

Split the method into small named steps, or fold the branches into one expression. Four patterns came up.

**A chain of fallbacks becomes `??`** (`ClientIpResolver::fromServerParams`):

```php
return $this->validIp($server['HTTP_CF_CONNECTING_IP'] ?? null)
    ?? $this->firstUntrustedFromTheRight($server['HTTP_X_FORWARDED_FOR'] ?? null)
    ?? $remote;
```

**A step that either yields the thing or says why not returns a union** (`TelegramLinkService::link`): `takeToken()`
returns `Token|LinkOutcome`, so `link()` is "return the outcome, or carry on with the token" with three returns.

**Two branches that differ only in what they call become one conditional** (`AuthController::resolveLoginResponse`).
Three `if`s collapsed to `$goesThroughTfa = (active || admin) && (tfa on || admin)` and one ternary. Work the
boolean out on paper first: it is easy to get wrong.

**A long method splits at its natural seam** (`HomeCareSignupController::confirm`): the GET page stays in `confirm()`,
the POST provisioning moves to `provisionConfirmedSignup()`.

The same idea applied to a command: `CleanupUnconfirmedCommand::execute()` keeps the argument check and hands
everything else to `process()`, which shows the candidates and, with `--force`, removes them.

Where a `match (true)` replaces two guards that only differ in a message, Psalm can still narrow the variables if
the combined `if` tests them all (`processValidToken`).

## S1448: more than 20 methods

Move a coherent group of methods into their own class, or remove a helper that is not earning its place.

- `UserInvController` (23): the profile page's Telegram **link** and **unlink** moved to `UserInvTelegramController`.
  Same permission, same route names, same view folder (`protected string $controllerName`), no constructor of its
  own: it inherits `BaseController`'s, so the container builds it.
- `TelegramController` (21): a one-line flash helper, added with the webhook fail-closed change, had two call sites;
  inlining it brought the class back to 20.

Adding a method to a class that is already at 20 is the usual way the rule is broken, so check the count when a
change adds helpers.

## S1488: assign then return

Return the expression, and keep the type with a docblock on the `return`:

```php
/** @var list<UserInv> */
return $this->select()->where(['telegram_chat_id' => $chatId])->fetchAll();
```

## Tests first, when there are none

`CleanupUnconfirmedCommand` had no tests, only the cleaner it calls had. Before changing its control flow, seven
tests were written for it (dry run, `--force`, a failed removal, the hours limits) and run against the **original**
command as well as the new one. Passing on both is the evidence that the restructure changed nothing. Do the same
for any method without tests.

## Checking locally

SonarQube is not run from the editor here. A short script over the syntax tree (`nikic/php-parser`, already in
`vendor/`) counts returns per method excluding nested closures, methods per class, and the assign-then-return
pair. Run on the original files it reproduced exactly the 14 reported findings, which is what makes its "no
findings" on the new files trustworthy. The script was a one-off and is not committed.

## Related

- [SonarQube S1144: private methods called across trait boundaries](SONARQUBE_S1144_TRAIT_BOUNDARY.md)
- [SonarQube S107: parameter lists](SONARQUBE_S107_APPLICATION_SERVICE.md)
- [Typed mocks in tests, and what CI Psalm reports](TEST_MOCK_TYPING_AND_CI_PSALM_NOVEMBER_2026.md)
- [Telegram, November 2026](TELEGRAM_NOVEMBER_2026.md)
