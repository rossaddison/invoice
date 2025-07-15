<?php

declare(strict_types=1);

namespace App;

use RuntimeException;

use function in_array;
use function sprintf;

final class Environment
{
    public const DEV = 'dev';
    public const TEST = 'test';
    public const PROD = 'prod';
    public const DOCKER = 'docker';

    private static array $values = [];

    public static function prepare(): void
    {
        /** Previously APP_ENV=docker */
        self::setString('DOCK_AMP', 'docker');

        /** Used in public/index line 21 */
        self::setBoolean('APP_C3', true);

        /** Previously YII_ENV */
        self::setString('APP_ENV', 'dev');

        /** Previously YII_DEBUG */
        self::setBoolean('APP_DEBUG', true);

        self::setBoolean('BUILD_DATABASE', false);
        self::setBoolean('SENTRY_DSN', false);
        self::setString('BASE_URL', '');

        /** Symfony Mailer */
        self::setString('SYMFONY_MAILER_USERNAME', '');
        self::setString('SYMFONY_MAILER_PASSWORD', '');

        /** OAuth2.0 */
        self::setString('FACEBOOK_API_CLIENT_ID', 'test_facebook_client_id');
        self::setString('FACEBOOK_API_CLIENT_SECRET', 'test_facebook_secret');
        self::setString('FACEBOOK_API_CLIENT_RETURN_URL', 'http://localhost/auth/facebook-return');
        self::setString('GITHUB_API_CLIENT_ID', 'test_github_client_id');
        self::setString('GITHUB_API_CLIENT_SECRET', 'test_github_secret');
        self::setString('GITHUB_API_CLIENT_RETURN_URL', 'http://localhost/auth/github-return');
        self::setString('GOOGLE_API_CLIENT_ID', 'test_google_client_id');
        self::setString('GOOGLE_API_CLIENT_SECRET', 'test_google_secret');
        self::setString('GOOGLE_API_CLIENT_RETURN_URL', 'http://localhost/auth/google-return');
        self::setString('GOVUK_API_CLIENT_ID', 'test_govuk_client_id');
        self::setString('GOVUK_API_CLIENT_SECRET', 'test_govuk_secret');
        self::setString('GOVUK_API_CLIENT_RETURN_URL', 'http://localhost/auth/govuk-return');
        self::setString('LINKEDIN_API_CLIENT_ID', 'test_linkedin_client_id');
        self::setString('LINKEDIN_API_CLIENT_SECRET', 'test_linkedin_secret');
        self::setString('LINKEDIN_API_CLIENT_RETURN_URL', 'http://localhost/auth/linkedin-return');
        self::setString('MICROSOFTONLINE_API_CLIENT_ID', 'test_msonline_client_id');
        self::setString('MICROSOFTONLINE_API_CLIENT_SECRET', 'test_msonline_secret');
        self::setString('MICROSOFTONLINE_API_CLIENT_RETURN_URL', 'http://localhost/auth/msonline-return');
        self::setString('MICROSOFTONLINE_API_CLIENT_TENANT', 'common');
        self::setString('OPENBANKING_API_CLIENT_ID', 'test_openbanking_client_id');
        self::setString('OPENBANKING_API_CLIENT_SECRET', 'test_openbanking_secret');
        self::setString('OPENBANKING_API_CLIENT_RETURN_URL', 'http://localhost/auth/openbanking-return');
        self::setString('VKONTAKTE_API_CLIENT_ID', 'test_vk_client_id');
        self::setString('VKONTAKTE_API_CLIENT_SECRET', 'test_vk_secret');
        self::setString('VKONTAKTE_API_CLIENT_RETURN_URL', 'http://localhost/auth/vk-return');
        self::setString('X_API_CLIENT_ID', 'test_x_client_id');
        self::setString('X_API_CLIENT_SECRET', 'test_x_secret');
        self::setString('X_API_CLIENT_RETURN_URL', 'http://localhost/auth/x-return');
        self::setString('YANDEX_API_CLIENT_ID', 'test_yandex_client_id');
        self::setString('YANDEX_API_CLIENT_SECRET', 'test_yandex_secret');
        self::setString('YANDEX_API_CLIENT_RETURN_URL', 'http://localhost/auth/yandex-return');
        self::setString('DEVELOPER_GOV_SANDBOX_HMRC_API_CLIENT_ID', 'test_developer_gov_sandbox_hmrc_client_id_from_dot_env');
        self::setString('DEVELOPER_GOV_SANDBOX_HMRC_API_CLIENT_SECRET', 'test_developer_gov_sandbox_hmrc_client_secret_from_dot_env');
        self::setString('DEVELOPER_GOV_SANDBOX_HMRC_API_CLIENT_RETURN_URL', 'test_developer_gov_sandbox_hmrc_client_return_url_from_dot_env');
        self::setEnvironment();
    }

    /**
     * @return non-empty-string
     */
    public static function dockerEnv(): string
    {
        /** @var non-empty-string */
        return self::$values['DOCK_AMP'];
    }

    /**
     * @return non-empty-string
     */
    public static function appEnv(): string
    {
        /** @var non-empty-string */
        return self::$values['APP_ENV'];
    }

    public static function isDev(): bool
    {
        return self::appEnv() === self::DEV;
    }

    public static function isTest(): bool
    {
        return self::appEnv() === self::TEST;
    }

    public static function isProd(): bool
    {
        return self::appEnv() === self::PROD;
    }

    public static function isDocker(): bool
    {
        return self::dockerEnv() === self::DOCKER;
    }

    public static function appC3(): bool
    {
        /** @var bool */
        return self::$values['APP_C3'];
    }

    public static function buildDatabase(): bool
    {
        /** @var bool */
        return self::$values['BUILD_DATABASE'];
    }

    public static function appDebug(): bool
    {
        /** @var bool */
        return self::$values['APP_DEBUG'];
    }

    private static function setEnvironment(): void
    {
        $environment = self::getRawValue('APP_ENV');
        if (!in_array($environment, [self::DEV, self::TEST, self::PROD], true)) {
            throw new RuntimeException(
                sprintf('"%s" is invalid environment.', $environment ?? '')
            );
        }
        self::$values['APP_ENV'] = $environment;
    }

    private static function setBoolean(string $key, bool $default): void
    {
        $value = self::getRawValue($key);
        self::$values[$key] = $value === null
            ? $default
            : (filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default);
    }

    private static function setInteger(string $key, int $default): void
    {
        $value = self::getRawValue($key);
        self::$values[$key] = $value === null ? $default : (int) $value;
    }

    private static function setString(string $key, string $default): void
    {
        $value = self::getRawValue($key);
        self::$values[$key] = $value ?? $default;
    }

    private static function getRawValue(string $key): ?string
    {
        $value = getenv($key, true);
        if ($value !== false) {
            return $value;
        }

        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }

        return isset($_ENV[$key]) ? (string) $_ENV[$key] : null;
    }
}
