<?php

declare(strict_types=1);

namespace App\Auth;

/**
 * "Your session has expired" is carried to the login page in the query string (auth/login?reason=session-expired),
 * not in a flash message: a flash lives in the session, and the session is exactly what has expired. The value is
 * only ever compared with the one known reason, so nothing the visitor puts in the URL is echoed back; a forged
 * link can at worst show this notice.
 *
 * Set by App\Middleware\CsrfFailureHandler (a stale CSRF token is how an expired session shows itself on any
 * unsafe request, for example Logout from an old page); read by AuthController::login.
 */
final class SessionExpiredNotice
{
    public const string QUERY_PARAMETER = 'reason';
    public const string REASON = 'session-expired';

    /** @return array<string, string> the query parameters of the redirect to the login page */
    public static function queryParameters(): array
    {
        return [self::QUERY_PARAMETER => self::REASON];
    }

    /** @param array<array-key, mixed> $queryParams */
    public static function isRequested(array $queryParams): bool
    {
        return ($queryParams[self::QUERY_PARAMETER] ?? null) === self::REASON;
    }
}
