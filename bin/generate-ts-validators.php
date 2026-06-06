#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Generates angular/src/app/peppol/validators.ts from a Peppol BIS Billing 3.0
 * Schematron file by running it through the TypeScriptRuleEmitter pipeline.
 *
 * Usage:
 *   php bin/generate-ts-validators.php [--sch=PATH] [--out=PATH] [--vo=IMPORT_PREFIX]
 *
 * Defaults:
 *   --sch  resources/peppol/PEPPOL-EN16931-UBL.sch
 *   --out  angular/src/app/peppol/validators.ts
 *   --vo   ../vo
 *
 * The Schematron file is NOT bundled in this repo.  Download it from:
 *   https://docs.peppol.eu/poacc/billing/3.0/
 * and place it at resources/peppol/PEPPOL-EN16931-UBL.sch before running.
 */

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

use App\Invoice\Helpers\Peppol\Emit\TypeScriptExpressionEmitter;
use App\Invoice\Helpers\Peppol\Emit\TypeScriptRuleEmitter;
use App\Invoice\Helpers\Peppol\Emit\TypeScriptVoPathMapper;
use App\Invoice\Helpers\Peppol\Exception\SchematronParseException;
use App\Invoice\Helpers\Peppol\SchematronParser;

// ── Argument parsing ───────────────────────────────────────────────────────────

$opts = getopt('', ['sch:', 'out:', 'vo:']);

$schPath  = isset($opts['sch'])
    ? (string) $opts['sch']
    : $root . '/resources/peppol/PEPPOL-EN16931-UBL.sch';

$outPath  = isset($opts['out'])
    ? (string) $opts['out']
    : $root . '/angular/src/app/peppol/validators.ts';

$voPrefix = isset($opts['vo'])
    ? (string) $opts['vo']
    : '../vo';

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
echo "Emitting TypeScript to: {$outPath}\n";

$emitter = new TypeScriptRuleEmitter(
    new TypeScriptExpressionEmitter(
        new TypeScriptVoPathMapper()
    )
);

$ts = $emitter->emitFile($doc, $voPrefix);

if (file_put_contents($outPath, $ts) === false) {
    fwrite(STDERR, "Error: Failed to write output file: {$outPath}\n");
    exit(1);
}

$lines = substr_count($ts, "\n");
echo "  Done — {$lines} lines written.\n";
