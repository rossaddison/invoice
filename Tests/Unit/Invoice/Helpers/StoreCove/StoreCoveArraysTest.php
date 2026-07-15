<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Helpers\StoreCove;

use App\Invoice\Helpers\StoreCove\StoreCoveArrays;
use PHPUnit\Framework\TestCase;

class StoreCoveArraysTest extends TestCase
{
    public function testSenderIdentifierArrayReturnsNonEmptyArray(): void
    {
        $rows = StoreCoveArrays::storeCoveSenderIdentifierArray();

        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);
    }

    public function testSenderIdentifierArrayHasSubstantialRowCount(): void
    {
        // Chunks A-C merged; sanity floor well below the true total.
        $this->assertGreaterThan(30, count(StoreCoveArrays::storeCoveSenderIdentifierArray()));
    }

    public function testSenderIdentifierArrayContainsHeaderChunkEntry(): void
    {
        $rows = StoreCoveArrays::storeCoveSenderIdentifierArray();
        $countries = array_column($rows, 'Legal', 'Country');

        $this->assertArrayHasKey('US', $countries);
        $this->assertSame('DUNS, GLN, LEI', $countries['US']);
    }

    public function testSenderIdentifierArrayContainsLastChunkEntry(): void
    {
        $rows = StoreCoveArrays::storeCoveSenderIdentifierArray();
        $countries = array_column($rows, 'Legal', 'Country');

        $this->assertArrayHasKey('NL', $countries);
        $this->assertSame('NL:KVK', $countries['NL']);
    }

    public function testReceiverIdentifierArrayReturnsNonEmptyArray(): void
    {
        $rows = StoreCoveArrays::storeCoveReceiverIdentifierArray();

        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);
    }

    public function testReceiverIdentifierArrayHasSubstantialRowCount(): void
    {
        // Chunks A-D merged; sanity floor well below the true total.
        $this->assertGreaterThan(40, count(StoreCoveArrays::storeCoveReceiverIdentifierArray()));
    }

    public function testReceiverIdentifierArrayEntryStructure(): void
    {
        $rows = StoreCoveArrays::storeCoveReceiverIdentifierArray();
        $first = $rows[array_key_first($rows)];

        $this->assertArrayHasKey('region', $first);
        $this->assertArrayHasKey('country', $first);
        $this->assertArrayHasKey('b2x', $first);
        $this->assertArrayHasKey('legal', $first);
        $this->assertArrayHasKey('tax', $first);
        $this->assertArrayHasKey('routing', $first);
    }

    public function testReceiverIdentifierArrayContainsLastChunkEntry(): void
    {
        $rows = StoreCoveArrays::storeCoveReceiverIdentifierArray();
        $countries = array_column($rows, 'b2x', 'country');

        $this->assertArrayHasKey('RS', $countries);
        $this->assertSame('G+B', $countries['RS']);
    }
}
