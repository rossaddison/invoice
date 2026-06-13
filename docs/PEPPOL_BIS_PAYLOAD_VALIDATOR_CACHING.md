# Peppol BIS Payload Validator — Schematron Caching

`PeppolBisPayloadValidator` caches the parsed `SchematronDocument` in a static
property keyed by file path.  The if-statement view:

```
if (first request in this FPM worker) {
    parse the Schematron file → store in static $documentCache
} else {
    reuse $documentCache  // zero parse cost
}

if (different FPM worker) {
    // static cache does not exist yet → parse once for that worker too
}

if (deploy / worker restart) {
    // static cache wiped → each new worker parses once on first use
}

if (running in a test) {
    // static cache outlives the test method → call clearCache() in tearDown()
    // otherwise the second test reuses the first test's parsed document
}

if (two validators pointing to different .sch files) {
    // cache is keyed by path → each file gets its own entry
    // e.g. invoice.sch and creditnote.sch coexist without collision
}
```

## Why static rather than instance property

An instance property cache only works if the DI container gives you the same
instance every time (a singleton registration).  A static property belongs to
the class itself, not any one object, so it survives across instances regardless
of how the container is configured.

## Lifetime in PHP-FPM

PHP is share-nothing between requests, but within a single FPM worker process —
which handles many requests before recycling — static properties persist.  The
first request that calls `validate()` pays the parse cost; every subsequent
request in that worker reuses the cached `SchematronDocument` at zero cost.

## Related files

- [`src/Invoice/As4/PeppolBisPayloadValidator.php`](../src/Invoice/As4/PeppolBisPayloadValidator.php)
- [`src/Invoice/As4/As4PayloadValidatorInterface.php`](../src/Invoice/As4/As4PayloadValidatorInterface.php)
- [`Tests/Unit/Invoice/As4/PeppolBisPayloadValidatorTest.php`](../Tests/Unit/Invoice/As4/PeppolBisPayloadValidatorTest.php)
