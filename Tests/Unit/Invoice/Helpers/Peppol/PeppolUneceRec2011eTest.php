<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Helpers\Peppol;

use App\Invoice\Helpers\Peppol\PeppolUneceRec2011e;
use PHPUnit\Framework\TestCase;

class PeppolUneceRec2011eTest extends TestCase
{
    private PeppolUneceRec2011e $unece;

    #[\Override]
    protected function setUp(): void
    {
        $this->unece = new PeppolUneceRec2011e();
    }

    public function testGetUNECERec2011eReturnsNonEmptyArray(): void
    {
        $codes = $this->unece->getUNECERec2011e();

        $this->assertIsArray($codes);
        $this->assertNotEmpty($codes);
    }

    public function testGetUNECERec2011eHasSubstantialCodeCount(): void
    {
        $codes = $this->unece->getUNECERec2011e();

        // All 84 chunks across 5 traits merged; sanity floor well below the true total.
        $this->assertGreaterThan(1500, count($codes));
    }

    public function testGetUNECERec2011eEntryStructure(): void
    {
        $codes = $this->unece->getUNECERec2011e();
        $first = $codes[array_key_first($codes)];

        $this->assertArrayHasKey('Id', $first);
        $this->assertArrayHasKey('Name', $first);
        $this->assertArrayHasKey('Description', $first);
    }

    public function testGetUNECERec2011eContainsChunkFromTrait1(): void
    {
        $ids = array_column($this->unece->getUNECERec2011e(), 'Id');

        $this->assertContains('10', $ids);
    }

    public function testGetUNECERec2011eContainsChunkFromTrait2(): void
    {
        $codes = $this->unece->getUNECERec2011e();
        $names = array_column($codes, 'Name', 'Id');

        $this->assertArrayHasKey('D68', $names);
        $this->assertSame('number of words', $names['D68']);
    }

    public function testGetUNECERec2011eContainsChunkFromTrait5(): void
    {
        $codes = $this->unece->getUNECERec2011e();
        $names = array_column($codes, 'Name', 'Id');

        $this->assertArrayHasKey('STC', $names);
        $this->assertSame('stick', $names['STC']);
    }
}
