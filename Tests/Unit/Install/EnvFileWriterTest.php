<?php

declare(strict_types=1);

namespace Tests\Unit\Install;

use App\Install\EnvFileWriter;
use PHPUnit\Framework\TestCase;

final class EnvFileWriterTest extends TestCase
{
    /** @var list<string> */
    private array $createdFiles = [];

    #[\Override]
    protected function tearDown(): void
    {
        foreach ($this->createdFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        parent::tearDown();
    }

    private function createTempEnvFile(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'env-writer-test-');
        self::assertNotFalse($path);
        file_put_contents($path, $contents);
        $this->createdFiles[] = $path;
        return $path;
    }

    public function testReplacesExistingKeyInPlace(): void
    {
        $path = $this->createTempEnvFile("# comment\nAPP_ENV=local\nDB_NAME=\nBUILD_DATABASE=\n");

        new EnvFileWriter()->write($path, ['DB_NAME' => 'yii3_i_test']);

        $contents = (string) file_get_contents($path);
        self::assertStringContainsString("DB_NAME=yii3_i_test\n", $contents);
        self::assertStringContainsString("# comment\n", $contents);
        self::assertStringContainsString("APP_ENV=local\n", $contents);
        self::assertStringContainsString("BUILD_DATABASE=\n", $contents);
    }

    public function testReplacesMultipleKeysAtOnce(): void
    {
        $path = $this->createTempEnvFile("DB_NAME=\nDB_USERNAME=\nBUILD_DATABASE=\n");

        new EnvFileWriter()->write($path, [
            'DB_NAME' => 'yii3_i',
            'DB_USERNAME' => 'root',
            'BUILD_DATABASE' => 'true',
        ]);

        $contents = (string) file_get_contents($path);
        self::assertStringContainsString("DB_NAME=yii3_i\n", $contents);
        self::assertStringContainsString("DB_USERNAME=root\n", $contents);
        self::assertStringContainsString("BUILD_DATABASE=true\n", $contents);
    }

    public function testAppendsMissingKey(): void
    {
        $path = $this->createTempEnvFile("APP_ENV=local\n");

        new EnvFileWriter()->write($path, ['COOKIE_SECRET_KEY' => 'abc123']);

        $contents = (string) file_get_contents($path);
        self::assertStringContainsString("APP_ENV=local\n", $contents);
        self::assertStringContainsString('COOKIE_SECRET_KEY=abc123', $contents);
    }

    public function testDoesNotTouchUnrelatedKeysWithSimilarPrefix(): void
    {
        $path = $this->createTempEnvFile("DB_NAME=old\nDB_NAME_BACKUP=keepme\n");

        new EnvFileWriter()->write($path, ['DB_NAME' => 'new']);

        $contents = (string) file_get_contents($path);
        self::assertStringContainsString("DB_NAME=new\n", $contents);
        self::assertStringContainsString("DB_NAME_BACKUP=keepme\n", $contents);
    }
}
