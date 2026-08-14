<?php

declare(strict_types=1);

namespace App\Middleware;

use Closure;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\RateLimiter\Counter;
use Yiisoft\Yii\RateLimiter\LimitRequestsMiddleware;
use Yiisoft\Yii\RateLimiter\Policy\LimitAlways;
use Yiisoft\Yii\RateLimiter\Policy\LimitCallback;
use Yiisoft\Yii\RateLimiter\Storage\StorageInterface;

/**
 * Shared rate-limiter builders for config/common/routes/routes.php, avoiding
 * the repeated global/per-IP limiter closures duplicated across the auth
 * routes (login, forgotpassword, resetpassword, signup, change).
 *
 * Both builders wrap LimitRequestsMiddleware in RateLimitRedirectMiddleware,
 * so a request that actually trips the limit gets redirected back to the
 * page it was for instead of the bare, unstyled 429 LimitRequestsMiddleware
 * (and TooManyRequestsMiddleware, for the CAS-failure case) return on their
 * own — see RateLimitRedirectMiddleware's own docblock.
 */
final class RateLimiter
{
    /**
     * A global path-wide counter, regardless of requester IP.
     */
    public static function global(int $limit, int $window = 60): Closure
    {
        return static fn (ResponseFactoryInterface $responseFactory, StorageInterface $storage) => new RateLimitRedirectMiddleware(
            new LimitRequestsMiddleware(
                new Counter($storage, $limit, $window),
                $responseFactory,
                new LimitAlways(),
                new TooManyRequestsMiddleware($responseFactory),
            ),
            $responseFactory,
        );
    }

    /**
     * A per-real-IP counter (via CF-Connecting-IP, then X-Forwarded-For, then
     * REMOTE_ADDR), scoped by $salt so different routes don't share a bucket.
     */
    public static function perIp(int $limit, string $salt, int $window = 60): Closure
    {
        return static fn (ResponseFactoryInterface $responseFactory, StorageInterface $storage) => new RateLimitRedirectMiddleware(
            new LimitRequestsMiddleware(
                new Counter($storage, $limit, $window),
                $responseFactory,
                new LimitCallback(static function (ServerRequestInterface $r) use ($salt): string {
                    $srv = $r->getServerParams();
                    $cfIp = isset($srv['HTTP_CF_CONNECTING_IP']) ? (string) $srv['HTTP_CF_CONNECTING_IP'] : '';
                    $xfw = isset($srv['HTTP_X_FORWARDED_FOR']) ? (string) $srv['HTTP_X_FORWARDED_FOR'] : '';
                    $rem = isset($srv['REMOTE_ADDR']) ? (string) $srv['REMOTE_ADDR'] : '';
                    $ip = match (true) {
                        $cfIp !== '' => $cfIp,
                        $xfw !== '' => $xfw,
                        $rem !== '' => $rem,
                        default => 'unknown',
                    };
                    return hash('sha256', $salt . $ip);
                }),
                new TooManyRequestsMiddleware($responseFactory),
            ),
            $responseFactory,
        );
    }
}
