#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Generates src/Invoice/Helpers/Peppol/Generated/PeppolValidators.php from a
 * Peppol BIS Billing 3.0 Schematron file by running it through the PhpRuleEmitter
 * pipeline.
 *
 * Usage:
 *   php bin/generate-php-validators.php [--sch=PATH] [--out=PATH] [--ns=NAMESPACE]
 *
 * Defaults:
 *   --sch  resources/peppol/PEPPOL-EN16931-UBL.sch
 *   --out  src/Invoice/Helpers/Peppol/Generated/PeppolValidators.php
 *   --ns   App\Invoice\Helpers\Peppol\Generated
 *
 * The Schematron file is NOT bundled in this repo.  Download it from:
 *   https://docs.peppol.eu/poacc/billing/3.0/
 * and place it at resources/peppol/PEPPOL-EN16931-UBL.sch before running.
 *
 * The output file is generated — do not edit it by hand.  Re-run this script
 * whenever the Schematron version changes.
 */

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

use App\Invoice\Helpers\Peppol\Emit\PhpExpressionEmitter;
use App\Invoice\Helpers\Peppol\Emit\PhpRuleEmitter;
use App\Invoice\Helpers\Peppol\Emit\VoPathMapper;
use App\Invoice\Helpers\Peppol\Exception\SchematronParseException;
use App\Invoice\Helpers\Peppol\SchematronParser;

// ── Argument parsing ───────────────────────────────────────────────────────────

$opts = getopt('', ['sch:', 'out:', 'ns:']);

$schPath   = isset($opts['sch'])
    ? (string) $opts['sch']
    : $root . '/resources/peppol/PEPPOL-EN16931-UBL.sch';

$outPath   = isset($opts['out'])
    ? (string) $opts['out']
    : $root . '/src/Invoice/Helpers/Peppol/Generated/PeppolValidators.php';

$namespace = isset($opts['ns'])
    ? (string) $opts['ns']
    : 'App\\Invoice\\Helpers\\Peppol\\Generated';

// ── Validate inputs ────────────────────────────────────────────────────────────

if (!is_file($schPath)) {
    fwrite(STDERR, "Error: Schematron file not found: {$schPath}\n");
    fwrite(STDERR, "Download it from https://docs.peppol.eu/poacc/billing/3.0/ and place it at:\n");
    fwrite(STDERR, "  resources/peppol/PEPPOL-EN16931-UBL.sch\n");
    exit(1);
}

$outDir = dirname($outPath);
if (!is_dir($outDir) && !mkdir($outDir, 0755, true)) {
    fwrite(STDERR, "Error: Cannot create output directory: {$outDir}\n");
    exit(1);
}

// ── Parse → emit ──────────────────────────────────────────────────────────────

echo "Parsing: {$schPath}\n";

try {
    $doc = (new SchematronParser())->parseFile($schPath);
} catch (SchematronParseException $e) {
    fwrite(STDERR, "Parse error: {$e->getMessage()}\n");
    exit(1);
}

$ruleCount = array_sum(array_map(
    static fn($rule) => count($rule->assertions),
    $doc->rules,
));

echo "  Found " . count($doc->rules) . " rules, {$ruleCount} assertions.\n";
echo "Emitting PHP to: {$outPath}\n";

$emitter = new PhpRuleEmitter(
    new PhpExpressionEmitter(
        new VoPathMapper()
    )
);

$php = $emitter->emitFile($doc, $namespace);

if (file_put_contents($outPath, $php) === false) {
    fwrite(STDERR, "Error: Failed to write output file: {$outPath}\n");
    exit(1);
}

$lines = substr_count($php, "\n");
echo "  Done — {$lines} lines written.\n";
