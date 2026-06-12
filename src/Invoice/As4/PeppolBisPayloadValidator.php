<?php

declare(strict_types=1);

namespace App\Invoice\As4;

use App\Invoice\Helpers\Peppol\Rule\Severity;
use App\Invoice\Helpers\Peppol\Rule\ValidationViolation;
use App\Invoice\Helpers\Peppol\SchematronDocument;
use App\Invoice\Helpers\Peppol\SchematronParserInterface;
use App\Invoice\Helpers\Peppol\SchematronRunnerInterface;
use DOMDocument;

/**
 * Validates an outbound UBL payload against the Peppol BIS Billing 3.0
 * Schematron rules before it is wrapped in an AS4 envelope.
 *
 * ## Caching
 *
 * Parsing a Schematron file is CPU-intensive (it tokenises and compiles every
 * assertion into an AST).  The parsed SchematronDocument is therefore cached in
 * a static property, keyed by file path, so that:
 *
 *   - The first validate() call for a given path parses and stores the document.
 *   - Every subsequent call within the same PHP process reuses the stored result.
 *
 * ### Why static rather than instance property?
 *
 * An instance property cache would only help if the DI container registers this
 * class as a *singleton* (one shared instance per container).  A static property
 * works regardless of how many instances the container creates, because static
 * storage belongs to the class rather than to any one object.
 *
 * ### Lifetime in PHP-FPM
 *
 * PHP is share-nothing: each FPM worker process has its own memory.  Static
 * properties persist for the lifetime of a worker process, which typically handles
 * many requests before being recycled.  In practice this means:
 *
 *   - First request to use this validator in a given worker: ~parse cost (ms range).
 *   - All subsequent requests in that worker: zero parse cost.
 *   - After worker recycle or deploy restart: one parse per new worker.
 *
 * This is the right trade-off for a file that changes only on deploy.
 *
 * ### Test isolation
 *
 * Because the cache outlives a single test, call clearCache() in tearDown() to
 * prevent one test's parsed document from leaking into the next.
 *
 * @psalm-suppress UnusedClass
 */
final class PeppolBisPayloadValidator implements As4PayloadValidatorInterface
{
    /**
     * Path-keyed cache of parsed SchematronDocuments.
     * Keyed by $schematronPath so multiple Schematron files can coexist.
     *
     * @var array<string, SchematronDocument>
     */
    private static array $documentCache = [];

    public function __construct(
        private readonly SchematronParserInterface $parser,
        private readonly SchematronRunnerInterface $runner,
        /** Absolute path to the Schematron .sch file */
        private readonly string $schematronPath,
    ) {}

    /**
     * @return ValidationViolation[]  Non-fatal violations (warnings / info).
     * @throws As4PayloadValidationException  On Fatal violations.
     * @throws \InvalidArgumentException      On empty or malformed XML.
     */
    #[\Override]
    public function validate(string $payloadXml, string $documentTypeId): array
    {
        $doc        = $this->loadDocument($payloadXml);
        $violations = $this->runner->run($this->schematronDocument(), $doc);

        $fatals   = array_values(array_filter($violations, static fn(ValidationViolation $v) => $v->severity === Severity::Fatal));
        $warnings = array_values(array_filter($violations, static fn(ValidationViolation $v) => $v->severity !== Severity::Fatal));

        if ($fatals !== []) {
            throw new As4PayloadValidationException($fatals);
        }

        return $warnings;
    }

    /** Clears the parse cache — call in tearDown() for test isolation. */
    public static function clearCache(): void
    {
        self::$documentCache = [];
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function schematronDocument(): SchematronDocument
    {
        if (!isset(self::$documentCache[$this->schematronPath])) {
            self::$documentCache[$this->schematronPath] = $this->parser->parseFile($this->schematronPath);
        }
        return self::$documentCache[$this->schematronPath];
    }

    private function loadDocument(string $xml): DOMDocument
    {
        if ($xml === '') {
            throw new \InvalidArgumentException('Payload XML must not be empty');
        }
        $doc        = new DOMDocument();
        $prevErrors = libxml_use_internal_errors(true);
        $loaded     = $doc->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($prevErrors);
        if (!$loaded) {
            throw new \InvalidArgumentException('Payload XML is not well-formed');
        }
        return $doc;
    }
}
