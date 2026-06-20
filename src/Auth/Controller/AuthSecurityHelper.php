<?php

declare(strict_types=1);

namespace App\Auth\Controller;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Yiisoft\Security\Random;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Yii\RateLimiter\CounterInterface;

final readonly class AuthSecurityHelper
{
    public function __construct(
        private CounterInterface $rateLimiter,
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

    public function getClientIpAddress(ServerRequestInterface $request): string
    {
        $serverParams = $request->getServerParams();
        $ipHeaders = [
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
