<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\As4;

use App\Invoice\As4\As4PayloadValidationException;
use App\Invoice\As4\PeppolBisPayloadValidator;
use App\Invoice\Helpers\Peppol\Rule\Severity;
use App\Invoice\Helpers\Peppol\Rule\ValidationViolation;
use App\Invoice\Helpers\Peppol\SchematronDocument;
use App\Invoice\Helpers\Peppol\SchematronParserInterface;
use App\Invoice\Helpers\Peppol\SchematronRunnerInterface;
use DOMDocument;
use PHPUnit\Framework\TestCase;

class PeppolBisPayloadValidatorTest extends TestCase
{
    private const string SCH_PATH  = '/path/to/peppol.sch';
    private const string VALID_XML = '<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"/>';
    private const string DOCTYPE   = 'busdox-docid-qns::urn:oasis:names:specification:ubl:schema:xsd:Invoice-2';

    // ── Stubs ─────────────────────────────────────────────────────────────────

    private function emptyDocument(): SchematronDocument
    {
        return new SchematronDocument(namespaces: [], variables: [], rules: []);
    }

    /**
     * Returns a parser stub whose parseFile() returns $document and records
     * how many times it was called into $callCount.
     */
    private function parserStub(SchematronDocument $document, int &$callCount = 0): SchematronParserInterface
    {
        return new class($document, $callCount) implements SchematronParserInterface {
            public function __construct(
                private readonly SchematronDocument $document,
                private int &$callCount,
            ) {}

            #[\Override]
            public function parseFile(string $path): SchematronDocument
            {
                ++$this->callCount;
                return $this->document;
            }

            #[\Override]
            public function parseString(string $xml, string $source = '<string>'): SchematronDocument
            {
                return $this->document;
            }
        };
    }

    /**
     * Returns a runner stub that always returns $violations when run() is called.
     *
     * @param ValidationViolation[] $violations
     */
    private function runnerStub(array $violations = []): SchematronRunnerInterface
    {
        return new class($violations) implements SchematronRunnerInterface {
            /** @param ValidationViolation[] $violations */
            public function __construct(private readonly array $violations) {}

            /** @return array<int, ValidationViolation> */
            #[\Override]
            public function run(SchematronDocument $doc, DOMDocument $document): array
            {
                return array_values($this->violations);
            }
        };
    }

    private function fatal(string $ruleId = 'BR-01', string $msg = 'Fatal error'): ValidationViolation
    {
        return new ValidationViolation(Severity::Fatal, $ruleId, $msg, null, null);
    }

    private function warning(string $ruleId = 'BR-W1', string $msg = 'Warning'): ValidationViolation
    {
        return new ValidationViolation(Severity::Warning, $ruleId, $msg, null, null);
    }

    private function validator(
        SchematronParserInterface $parser,
        SchematronRunnerInterface $runner,
    ): PeppolBisPayloadValidator {
        return new PeppolBisPayloadValidator($parser, $runner, self::SCH_PATH);
    }

    #[\Override]
    protected function tearDown(): void
    {
        PeppolBisPayloadValidator::clearCache();
    }

    // ── Happy path ────────────────────────────────────────────────────────────

    public function testValidDocumentReturnsEmptyArray(): void
    {
        $v = $this->validator($this->parserStub($this->emptyDocument()), $this->runnerStub());
        $this->assertSame([], $v->validate(self::VALID_XML, self::DOCTYPE));
    }

    public function testReturnedArrayContainsWarnings(): void
    {
        $warn = $this->warning();
        $v    = $this->validator(
            $this->parserStub($this->emptyDocument()),
            $this->runnerStub([$warn]),
        );
        $result = $v->validate(self::VALID_XML, self::DOCTYPE);
        $this->assertCount(1, $result);
        $this->assertSame($warn, $result[0]);
    }

    public function testWarningsAreNotIncludedInException(): void
    {
        $v = $this->validator(
            $this->parserStub($this->emptyDocument()),
            $this->runnerStub([$this->fatal(), $this->warning()]),
        );
        try {
            $v->validate(self::VALID_XML, self::DOCTYPE);
            $this->fail('Expected As4PayloadValidationException');
        } catch (As4PayloadValidationException $e) {
            // Only the fatal violation is in the exception
            $this->assertCount(1, $e->violations);
            $this->assertSame(Severity::Fatal, $e->violations[0]->severity);
        }
    }

    // ── Fatal violations ──────────────────────────────────────────────────────

    public function testFatalViolationThrowsException(): void
    {
        $this->expectException(As4PayloadValidationException::class);
        $v = $this->validator(
            $this->parserStub($this->emptyDocument()),
            $this->runnerStub([$this->fatal()]),
        );
        $v->validate(self::VALID_XML, self::DOCTYPE);
    }

    public function testExceptionCarriesAllFatalViolations(): void
    {
        $v = $this->validator(
            $this->parserStub($this->emptyDocument()),
            $this->runnerStub([$this->fatal('BR-01'), $this->fatal('BR-02')]),
        );
        try {
            $v->validate(self::VALID_XML, self::DOCTYPE);
            $this->fail('Expected As4PayloadValidationException');
        } catch (As4PayloadValidationException $e) {
            $this->assertCount(2, $e->violations);
        }
    }

    public function testExceptionMessageMentionsViolationCount(): void
    {
        $v = $this->validator(
            $this->parserStub($this->emptyDocument()),
            $this->runnerStub([$this->fatal()]),
        );
        try {
            $v->validate(self::VALID_XML, self::DOCTYPE);
            $this->fail('Expected As4PayloadValidationException');
        } catch (As4PayloadValidationException $e) {
            $this->assertStringContainsString('1', $e->getMessage());
        }
    }

    public function testExceptionViolationPreservesRuleId(): void
    {
        $v = $this->validator(
            $this->parserStub($this->emptyDocument()),
            $this->runnerStub([$this->fatal('PEPPOL-EN16931-R001')]),
        );
        try {
            $v->validate(self::VALID_XML, self::DOCTYPE);
            $this->fail('Expected As4PayloadValidationException');
        } catch (As4PayloadValidationException $e) {
            $this->assertSame('PEPPOL-EN16931-R001', $e->violations[0]->ruleId);
        }
    }

    // ── Input guards ──────────────────────────────────────────────────────────

    public function testEmptyXmlThrowsInvalidArgument(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $v = $this->validator($this->parserStub($this->emptyDocument()), $this->runnerStub());
        $v->validate('', self::DOCTYPE);
    }

    public function testMalformedXmlThrowsInvalidArgument(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $v = $this->validator($this->parserStub($this->emptyDocument()), $this->runnerStub());
        $v->validate('not xml <<>>', self::DOCTYPE);
    }

    // ── Caching ───────────────────────────────────────────────────────────────

    /**
     * The Schematron file should be parsed exactly once no matter how many
     * validate() calls are made — even across different validator instances
     * pointing to the same path.
     *
     * This tests the static cache:  parseFile() is counted via $callCount.
     * After one warm-up call, a second instance reuses the cached document.
     */
    public function testSchematronFileParsedOnlyOncePerPath(): void
    {
        $callCount = 0;
        $parser    = $this->parserStub($this->emptyDocument(), $callCount);

        // Two instances, same path — first warms the cache, second must reuse it
        $v1 = new PeppolBisPayloadValidator($parser, $this->runnerStub(), self::SCH_PATH);
        $v1->validate(self::VALID_XML, self::DOCTYPE);

        $v2 = new PeppolBisPayloadValidator($parser, $this->runnerStub(), self::SCH_PATH);
        $v2->validate(self::VALID_XML, self::DOCTYPE);

        $this->assertSame(1, $callCount, 'Both calls should result in only one parse');
    }

    public function testDifferentPathsProduceSeparateCacheEntries(): void
    {
        $callCount = 0;
        $parser    = $this->parserStub($this->emptyDocument(), $callCount);

        $v1 = new PeppolBisPayloadValidator($parser, $this->runnerStub(), '/path/a.sch');
        $v1->validate(self::VALID_XML, self::DOCTYPE);

        $v2 = new PeppolBisPayloadValidator($parser, $this->runnerStub(), '/path/b.sch');
        $v2->validate(self::VALID_XML, self::DOCTYPE);

        $this->assertSame(2, $callCount, 'Different paths must each trigger one parse');
    }

    public function testClearCacheForcesParseonNextCall(): void
    {
        $callCount = 0;
        $parser    = $this->parserStub($this->emptyDocument(), $callCount);

        $v = new PeppolBisPayloadValidator($parser, $this->runnerStub(), self::SCH_PATH);
        $v->validate(self::VALID_XML, self::DOCTYPE);  // warms cache: parseFile called once

        PeppolBisPayloadValidator::clearCache();

        $v->validate(self::VALID_XML, self::DOCTYPE);  // cache empty: parseFile called again
        $this->assertSame(2, $callCount, 'After clearCache(), file must be re-parsed');
    }
}
