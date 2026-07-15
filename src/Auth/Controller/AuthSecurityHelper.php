<?php

declare(strict_types=1);

namespace App\Auth\Controller;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Yiisoft\Security\Random;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Yii\RateLimiter\Counter;
use Yiisoft\Yii\RateLimiter\CounterInterface;
use Yiisoft\Yii\RateLimiter\Storage\StorageInterface;

final readonly class AuthSecurityHelper
{
    /**
     * Account-keyed login throttle: independent of the (looser, IP-keyed)
     * $rateLimiter above, so that a distributed credential-stuffing attempt
     * spread across many IPs against one account is still blocked. Reuses
     * the same cache-backed storage, just with its own stricter Counter.
     */
    private const int ACCOUNT_LIMIT = 5;
    private const int ACCOUNT_PERIOD_SECONDS = 900;

    public function __construct(
        private CounterInterface $rateLimiter,
        private StorageInterface $rateLimiterStorage,
        private LoggerInterface $logger,
        private SessionInterface $session,
    ) {}

    public function checkRateLimit(string $key): bool
    {
        try {
            $result = $this->rateLimiter->hit($key);
            return !$result->isLimitReached();
        } catch (\Exception $e) {
            $this->logger->log(
                    LogLevel::ERROR, 'Rate limiter error: ' . $e->getMessage());
            return true;
        }
    }

    /**
     * Throttles login attempts against a specific account (keyed by login
     * name, not IP) — defense-in-depth against credential stuffing spread
     * across many source IPs against a single account.
     */
    public function checkAccountRateLimit(string $login): bool
    {
        if ($login === '') {
            return true;
        }
        try {
            $counter = new Counter($this->rateLimiterStorage, self::ACCOUNT_LIMIT, self::ACCOUNT_PERIOD_SECONDS);
            $result = $counter->hit(hash('sha256', 'login_account_' . mb_strtolower($login)));
            return !$result->isLimitReached();
        } catch (\Exception $e) {
            $this->logger->log(
                    LogLevel::ERROR, 'Account rate limiter error: ' . $e->getMessage());
            return true;
        }
    }

    public function getClientIpAddress(ServerRequestInterface $request): string
    {
        $serverParams = $request->getServerParams();
        $ipHeaders = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR',
        ];

        foreach ($ipHeaders as $header) {
            if (!empty($serverParams[$header])
                    && is_string($serverParams[$header])) {
                $ip = trim($serverParams[$header]);

                if (str_contains($ip, ',')) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }

                if (filter_var($ip,
                        FILTER_VALIDATE_IP,
                        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        /** @var string|null $serverParams['REMOTE_ADDR'] */
        return $serverParams['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    public function validateSessionIntegrity(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }
        $verifiedUserId = (int) $this->session->get('verified_2fa_user_id');
        return $verifiedUserId === $userId;
    }

    public function remSessTempsPermitEntryBase(int $verifiedUserId): void
    {
        $sivffuid = 'Session integrity validation failed for user ID: ';
        if (!$this->validateSessionIntegrity($verifiedUserId)) {
            $this->logger->log(LogLevel::WARNING, $sivffuid . $verifiedUserId);
            $this->session->clear();
            return;
        }
        $this->session->remove('pending_2fa_user_id');
        $this->session->remove('backup_recovery_codes');
        $this->session->remove('verified_2fa_user_id');
        $this->session->set('tfa_verified', true);
    }

    public function secureClearSensitiveData(array $sensitiveVars = []): void
    {
        $sensitiveSessionKeys = [
            '2fa_temp_secret',
            'otp',
            'otpRef',
            'code_verifier',
            'backup_recovery_codes',
        ];

        foreach ($sensitiveSessionKeys as $key) {
            if ($this->session->has($key)) {
                $this->session->remove($key);
            }
        }

        /** @var string[] $sensitiveVars */
        foreach ($sensitiveVars as &$var) {
            $var = str_repeat('0', strlen($var));
            $var = Random::string(strlen($var));
            unset($var);
        }
    }
}
