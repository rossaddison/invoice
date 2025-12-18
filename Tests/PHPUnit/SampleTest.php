<?php

declare(strict_types=1);

namespace Tests\PHPUnit;

use PHPUnit\Framework\TestCase;

class SampleTest extends TestCase
{
    public function testBasicAssertion(): void
    {
        $this->assertTrue(true);
        $this->assertEquals(1, 1);
        $this->assertSame('hello', 'hello');
    }

    public function testPhpVersion(): void
    {
        $this->assertGreaterThanOrEqual('8.0', PHP_VERSION);
    }

    public function testArrayOperations(): void
    {
        $array = [1, 2, 3];
        
        $this->assertCount(3, $array);
        $this->assertContains(2, $array);
        $this->assertNotContains(4, $array);
    }
}