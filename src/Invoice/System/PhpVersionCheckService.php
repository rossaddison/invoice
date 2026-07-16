<?php

declare(strict_types=1);

namespace App\Invoice\System;

use App\Infrastructure\Persistence\Setting\Setting;
use App\Invoice\Setting\SettingRepositoryInterface as SR;
use DateTimeImmutable;
use GuzzleHttp\Client as HttpClient;
use Psr\Http\Message\RequestFactoryInterface;
use Throwable;

/**
 * Checks whether a newer PHP patch release is available for the running
 * major.minor branch, via php.net's release JSON feed, and caches the
 * result in the settings table so page loads never depend on an external
 * network call.
 *
 * @see https://www.php.net/releases/index.php?json&version=8.4
 */
final class PhpVersionCheckService
{
    private const string SETTING_KEY_LATEST_VERSION = 'system_php_latest_patch_version';

    private const string SETTING_KEY_IS_SECURITY = 'system_php_latest_patch_version_is_security';

    private const string SETTING_KEY_CHECKED_AT = 'system_php_latest_patch_version_checked_at';

    private const string SETTING_KEY_ERROR = 'system_php_latest_patch_version_error';

    public function __construct(
        private readonly HttpClient $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly SR $settingRepository,
    ) {}

    /**
     * Calls php.net, persists the result to settings, and returns it.
     */
    public function check(): PhpVersionCheckResult
    {
        $currentVersion = PHP_VERSION;
        $branch = $this->branchOf($currentVersion);

        try {
            $request = $this->requestFactory->createRequest(
                'GET',
                'https://www.php.net/releases/index.php?json&version=' . $branch,
            );
            $response = $this->httpClient->sendRequest($request);
            /** @var array{version?: string, tags?: list<string>} $data */
            $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            $this->persist(null, false, null, $e->getMessage());
            return new PhpVersionCheckResult($currentVersion, null, false, false, null, $e->getMessage());
        }

        $latestVersion = $data['version'] ?? null;
        $isSecurity = in_array('security', $data['tags'] ?? [], true);
        $checkedAt = new DateTimeImmutable()->format('Y-m-d H:i:s');

        $this->persist($latestVersion, $isSecurity, $checkedAt, null);

        return new PhpVersionCheckResult(
            $currentVersion,
            $latestVersion,
            $this->isOutdated($currentVersion, $latestVersion),
            $isSecurity,
            $checkedAt,
            null,
        );
    }

    /**
     * Reads the last cached result without making a network call.
     */
    public function getCached(): PhpVersionCheckResult
    {
        $currentVersion = PHP_VERSION;
        $latestVersion = $this->settingRepository->getSetting(self::SETTING_KEY_LATEST_VERSION);
        $checkedAt = $this->settingRepository->getSetting(self::SETTING_KEY_CHECKED_AT);
        $error = $this->settingRepository->getSetting(self::SETTING_KEY_ERROR);

        return new PhpVersionCheckResult(
            $currentVersion,
            $latestVersion !== '' ? $latestVersion : null,
            $this->isOutdated($currentVersion, $latestVersion !== '' ? $latestVersion : null),
            $this->settingRepository->getSetting(self::SETTING_KEY_IS_SECURITY) === '1',
            $checkedAt !== '' ? $checkedAt : null,
            $error !== '' ? $error : null,
        );
    }

    private function isOutdated(string $currentVersion, ?string $latestVersion): bool
    {
        return $latestVersion !== null && version_compare($currentVersion, $latestVersion, '<');
    }

    private function branchOf(string $version): string
    {
        $parts = explode('.', $version);
        return $parts[0] . '.' . ($parts[1] ?? '0');
    }

    private function persist(?string $latestVersion, bool $isSecurity, ?string $checkedAt, ?string $error): void
    {
        $this->save(self::SETTING_KEY_LATEST_VERSION, $latestVersion ?? '');
        $this->save(self::SETTING_KEY_IS_SECURITY, $isSecurity ? '1' : '0');
        $this->save(self::SETTING_KEY_CHECKED_AT, $checkedAt ?? '');
        $this->save(self::SETTING_KEY_ERROR, $error ?? '');
    }

    private function save(string $key, string $value): void
    {
        $setting = $this->settingRepository->withKey($key);
        if ($setting === null) {
            $setting = new Setting();
            $setting->setSettingKey($key);
        }
        $setting->setSettingValue($value);
        $this->settingRepository->save($setting);
    }
}
