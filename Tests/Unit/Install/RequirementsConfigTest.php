<?php

declare(strict_types=1);

namespace Tests\Unit\Install;

use App\Install\RequirementsConfig;
use PHPUnit\Framework\TestCase;

final class RequirementsConfigTest extends TestCase
{
    private string $originalMaxExecutionTime = '';
    private string $originalMemoryLimit = '';

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->originalMaxExecutionTime = (string) ini_get('max_execution_time');
        $this->originalMemoryLimit = (string) ini_get('memory_limit');
    }

    #[\Override]
    protected function tearDown(): void
    {
        ini_set('max_execution_time', $this->originalMaxExecutionTime);
        ini_set('memory_limit', $this->originalMemoryLimit);
        parent::tearDown();
    }

    /**
     * @return array{name: string, mandatory: bool, condition: bool, by: string, memo: string}
     */
    private function requirement(string $name): array
    {
        foreach (RequirementsConfig::checks() as $requirement) {
            if ($requirement['name'] === $name) {
                return $requirement;
            }
        }
        self::fail('Requirement not found: ' . $name);
    }

    public function testMaxExecutionTimeFailsBelowThreshold(): void
    {
        ini_set('max_execution_time', '119');

        self::assertFalse($this->requirement('Maximum Execution Time of 400 seconds')['condition']);
    }

    public function testMaxExecutionTimeFailsJustBelowThreshold(): void
    {
        ini_set('max_execution_time', '399');

        self::assertFalse($this->requirement('Maximum Execution Time of 400 seconds')['condition']);
    }

    public function testMaxExecutionTimePassesAtThreshold(): void
    {
        ini_set('max_execution_time', '400');

        self::assertTrue($this->requirement('Maximum Execution Time of 400 seconds')['condition']);
    }

    public function testMaxExecutionTimePassesAboveThreshold(): void
    {
        ini_set('max_execution_time', '500');

        self::assertTrue($this->requirement('Maximum Execution Time of 400 seconds')['condition']);
    }

    public function testMaxExecutionTimePassesWhenUnlimited(): void
    {
        ini_set('max_execution_time', '0');

        self::assertTrue($this->requirement('Maximum Execution Time of 400 seconds')['condition']);
    }

    public function testMemoryLimitFailsBelowThreshold(): void
    {
        ini_set('memory_limit', '512M');

        self::assertFalse($this->requirement('Memory limit of 1024M or higher')['condition']);
    }

    public function testMemoryLimitPassesAtThreshold(): void
    {
        ini_set('memory_limit', '1024M');

        self::assertTrue($this->requirement('Memory limit of 1024M or higher')['condition']);
    }

    public function testMemoryLimitPassesWhenUnlimited(): void
    {
        ini_set('memory_limit', '-1');

        self::assertTrue($this->requirement('Memory limit of 1024M or higher')['condition']);
    }
}
