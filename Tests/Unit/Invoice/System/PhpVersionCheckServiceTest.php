<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\System;

use App\Infrastructure\Persistence\Setting\Setting;
use App\Invoice\Setting\SettingRepositoryInterface;
use App\Invoice\System\PhpVersionCheckService;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Response;
use HttpSoft\Message\RequestFactory;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

final class PhpVersionCheckServiceTest extends TestCase
{
    /** @var array<string, string> */
    private array $savedSettings = [];

    private function makeSettingRepository(): SettingRepositoryInterface&Stub
    {
        $repo = $this->createStub(SettingRepositoryInterface::class);
        $repo->method('withKey')->willReturnCallback(
            fn(string $key): ?Setting => null,
        );
        $repo->method('save')->willReturnCallback(function (?Setting $setting): void {
            if ($setting !== null) {
                $this->savedSettings[$setting->getSettingKey()] = $setting->getSettingValue();
            }
        });
        $repo->method('getSetting')->willReturnCallback(
            fn(string $key): string => $this->savedSettings[$key] ?? '',
        );
        return $repo;
    }

    private function makeService(HttpClient $httpClient, SettingRepositoryInterface $settingRepository): PhpVersionCheckService
    {
        return new PhpVersionCheckService($httpClient, new RequestFactory(), $settingRepository);
    }

    private function makeHttpClient(MockHandler $mock): HttpClient
    {
        return new HttpClient(['handler' => HandlerStack::create($mock)]);
    }

    public function testCheckDetectsOutdatedVersion(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['version' => '99.9.9', 'tags' => []], JSON_THROW_ON_ERROR)),
        ]);
        $repo = $this->makeSettingRepository();
        $result = $this->makeService($this->makeHttpClient($mock), $repo)->check();

        $this->assertSame(PHP_VERSION, $result->currentVersion);
        $this->assertSame('99.9.9', $result->latestVersion);
        $this->assertTrue($result->isOutdated);
        $this->assertFalse($result->isSecurityRelease);
        $this->assertNull($result->error);
        $this->assertNotNull($result->checkedAt);
    }

    public function testCheckDetectsSecurityRelease(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['version' => '99.9.9', 'tags' => ['security']], JSON_THROW_ON_ERROR)),
        ]);
        $repo = $this->makeSettingRepository();
        $result = $this->makeService($this->makeHttpClient($mock), $repo)->check();

        $this->assertTrue($result->isOutdated);
        $this->assertTrue($result->isSecurityRelease);
    }

    public function testCheckDetectsUpToDateVersion(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['version' => PHP_VERSION, 'tags' => []], JSON_THROW_ON_ERROR)),
        ]);
        $repo = $this->makeSettingRepository();
        $result = $this->makeService($this->makeHttpClient($mock), $repo)->check();

        $this->assertFalse($result->isOutdated);
        $this->assertNull($result->error);
    }

    public function testCheckPersistsResultToSettings(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['version' => '99.9.9', 'tags' => ['security']], JSON_THROW_ON_ERROR)),
        ]);
        $repo = $this->makeSettingRepository();
        $this->makeService($this->makeHttpClient($mock), $repo)->check();

        $this->assertSame('99.9.9', $this->savedSettings['system_php_latest_patch_version']);
        $this->assertSame('1', $this->savedSettings['system_php_latest_patch_version_is_security']);
        $this->assertNotSame('', $this->savedSettings['system_php_latest_patch_version_checked_at']);
        $this->assertSame('', $this->savedSettings['system_php_latest_patch_version_error']);
    }

    public function testCheckGracefullyDegradesOnHttpFailure(): void
    {
        $mock = new MockHandler([
            new ConnectException('Connection refused', new GuzzleRequest('GET', 'https://www.php.net/')),
        ]);
        $repo = $this->makeSettingRepository();
        $result = $this->makeService($this->makeHttpClient($mock), $repo)->check();

        $this->assertSame(PHP_VERSION, $result->currentVersion);
        $this->assertNull($result->latestVersion);
        $this->assertFalse($result->isOutdated);
        $this->assertNotNull($result->error);
        $this->assertSame('Connection refused', $this->savedSettings['system_php_latest_patch_version_error'] ?? null);
    }

    public function testCheckGracefullyDegradesOnMalformedJson(): void
    {
        $mock = new MockHandler([
            new Response(200, [], 'not json'),
        ]);
        $repo = $this->makeSettingRepository();
        $result = $this->makeService($this->makeHttpClient($mock), $repo)->check();

        $this->assertNull($result->latestVersion);
        $this->assertNotNull($result->error);
    }

    public function testCheckUpdatesAnExistingSettingRowInsteadOfCreatingADuplicate(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['version' => '99.9.9', 'tags' => []], JSON_THROW_ON_ERROR)),
        ]);

        $existing = new Setting();
        $existing->setSettingKey('system_php_latest_patch_version');
        $existing->setSettingValue('1.0.0');

        $repo = $this->createStub(SettingRepositoryInterface::class);
        $repo->method('withKey')->willReturnCallback(
            fn(string $key): ?Setting => $key === 'system_php_latest_patch_version' ? $existing : null,
        );
        $savedInstances = [];
        $repo->method('save')->willReturnCallback(function (?Setting $setting) use (&$savedInstances): void {
            if ($setting !== null) {
                $savedInstances[] = $setting;
            }
        });
        $repo->method('getSetting')->willReturn('');

        $this->makeService($this->makeHttpClient($mock), $repo)->check();

        $updatedExisting = array_filter(
            $savedInstances,
            static fn(Setting $s): bool => $s->getSettingKey() === 'system_php_latest_patch_version',
        );
        $this->assertNotEmpty($updatedExisting);
        $this->assertSame($existing, reset($updatedExisting), 'The pre-existing Setting instance should be reused, not replaced.');
        $this->assertSame('99.9.9', $existing->getSettingValue());
    }

    public function testGetCachedReadsPersistedValues(): void
    {
        $repo = $this->createStub(SettingRepositoryInterface::class);
        $repo->method('getSetting')->willReturnMap([
            ['system_php_latest_patch_version', '99.9.9'],
            ['system_php_latest_patch_version_is_security', '1'],
            ['system_php_latest_patch_version_checked_at', '2026-07-16 12:00:00'],
            ['system_php_latest_patch_version_error', ''],
        ]);
        $result = $this->makeService($this->makeHttpClient(new MockHandler([])), $repo)->getCached();

        $this->assertSame('99.9.9', $result->latestVersion);
        $this->assertTrue($result->isSecurityRelease);
        $this->assertSame('2026-07-16 12:00:00', $result->checkedAt);
        $this->assertTrue($result->isOutdated);
        $this->assertNull($result->error);
    }

    public function testGetCachedReturnsNullsWhenNeverChecked(): void
    {
        $repo = $this->createStub(SettingRepositoryInterface::class);
        $repo->method('getSetting')->willReturn('');
        $result = $this->makeService($this->makeHttpClient(new MockHandler([])), $repo)->getCached();

        $this->assertNull($result->latestVersion);
        $this->assertFalse($result->isOutdated);
        $this->assertNull($result->checkedAt);
        $this->assertNull($result->error);
    }
}
