<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Helpers\Peppol;

use App\Invoice\Helpers\Peppol\PeppolHelper;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class PeppolHelperUnc7143TraitTest extends TestCase
{
    /**
     * PeppolHelperUnc7143Trait's Description strings reuse ICD_* constants
     * declared on PeppolHelper itself (traits may reference constants only
     * their consuming class defines) - so the trait is exercised through its
     * real host rather than a bare anonymous class.
     */
    private function createHelper(): PeppolHelper
    {
        $reflection = new ReflectionClass(PeppolHelper::class);
        return $reflection->newInstanceWithoutConstructor(); // NOSONAR: php:S3011
    }

    /** @return array<int, array{Id: string, Name: string, Description: string}> */
    private function getUnc7143(): array
    {
        return $this->createHelper()->getUnc7143();
    }

    public function testGetUnc7143ReturnsNonEmptyArray(): void
    {
        $codes = $this->getUnc7143();

        $this->assertIsArray($codes);
        $this->assertNotEmpty($codes);
    }

    public function testGetUnc7143HasSubstantialCodeCount(): void
    {
        // All 9 chunks (0-8, including 4a/4b) merged; sanity floor well below the true total.
        $this->assertGreaterThan(100, count($this->getUnc7143()));
    }

    public function testGetUnc7143EntryStructure(): void
    {
        $codes = $this->getUnc7143();
        $first = $codes[array_key_first($codes)];

        $this->assertArrayHasKey('Id', $first);
        $this->assertArrayHasKey('Name', $first);
        $this->assertArrayHasKey('Description', $first);
    }

    public function testGetUnc7143ContainsFirstChunkEntry(): void
    {
        $names = array_column($this->getUnc7143(), 'Name', 'Id');

        $this->assertArrayHasKey('AA', $names);
        $this->assertSame('Product version number', $names['AA']);
    }

    public function testGetUnc7143ContainsLastChunkEntry(): void
    {
        $names = array_column($this->getUnc7143(), 'Name', 'Id');

        $this->assertArrayHasKey('TSP', $names);
        $this->assertSame('EU Combined Nomenclature', $names['TSP']);
    }
}
