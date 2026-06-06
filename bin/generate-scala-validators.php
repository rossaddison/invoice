#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Generates src/scala/peppol/rules/PeppolValidators.scala from a Peppol BIS
 * Billing 3.0 Schematron file by running it through the ScalaRuleEmitter pipeline.
 *
 * Usage:
 *   php bin/generate-scala-validators.php [--sch=PATH] [--out=PATH] [--pkg=PACKAGE] [--vo=VO_PACKAGE]
 *
 * Defaults:
 *   --sch  resources/peppol/PEPPOL-EN16931-UBL.sch
 *   --out  src/scala/peppol/rules/PeppolValidators.scala
 *   --pkg  peppol.rules
 *   --vo   peppol.vo
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

use App\Invoice\Helpers\Peppol\Emit\ScalaExpressionEmitter;
use App\Invoice\Helpers\Peppol\Emit\ScalaRuleEmitter;
use App\Invoice\Helpers\Peppol\Emit\ScalaVoPathMapper;
use App\Invoice\Helpers\Peppol\Exception\SchematronParseException;
use App\Invoice\Helpers\Peppol\SchematronParser;

// ── Argument parsing ───────────────────────────────────────────────────────────

$opts = getopt('', ['sch:', 'out:', 'pkg:', 'vo:']);

$schPath   = isset($opts['sch'])
    ? (string) $opts['sch']
    : $root . '/resources/peppol/PEPPOL-EN16931-UBL.sch';

$outPath   = isset($opts['out'])
    ? (string) $opts['out']
    : $root . '/src/scala/peppol/rules/PeppolValidators.scala';

$package   = isset($opts['pkg'])
    ? (string) $opts['pkg']
    : 'peppol.rules';

$voPackage = isset($opts['vo'])
    ? (string) $opts['vo']
    : 'peppol.vo';

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
echo "Emitting Scala to: {$outPath}\n";

$emitter = new ScalaRuleEmitter(
    new ScalaExpressionEmitter(
        new ScalaVoPathMapper()
    )
);

$scala = $emitter->emitFile($doc, $package, $voPackage);

if (file_put_contents($outPath, $scala) === false) {
    fwrite(STDERR, "Error: Failed to write output file: {$outPath}\n");
    exit(1);
}

$lines = substr_count($scala, "\n");
echo "  Done — {$lines} lines written.\n";
