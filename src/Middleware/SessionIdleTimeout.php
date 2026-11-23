<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Invoice\Setting\SettingRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Session\SessionInterface;

/**
 * Ends a session that has sat unused for longer than Settings -> General -> Session Idle Timeout (Minutes).
 *
 * PHP's own session.gc_maxlifetime only says when an old session file MAY be deleted: the clean-up runs on a random
 * chance (or a cron job), so it is not a timeout anyone can rely on. This keeps the time of the last request in the
 * session and clears the session when the gap is too long. The visitor is then signed out, and the access gate sends a
 * page request to the login page with the "session expired" notice. A valid remember-me cookie still signs the
 * visitor straight back in; that is a separate, longer-lived choice they made at login.
 *
 * gc_maxlifetime is raised to the same length (never lowered) before the session opens, so PHP does not delete the
 * session file sooner than the setting says.
 *
 * Only a request that already carries a session cookie is touched, so bots, webhooks and the Peppol endpoints do not
 * start a session (and receive a cookie) just by arriving.
 *
 * Must run directly after SessionMiddleware and before anything reads the session.
 */
final readonly class SessionIdleTimeout implements MiddlewareInterface
{
    /** Session key holding the time (Unix seconds) of the last request that was counted. */
    public const string LAST_ACTIVITY_KEY = 'last_activity';

    /** The time is rewritten at most this often, so a page does not cost a session write per request. */
    public const int TOUCH_INTERVAL_SECONDS = 60;

    public function __construct(
        private SessionInterface $session,
        private SettingRepository $settings,
    ) {
    }

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $seconds = $this->settings->sessionIdleMinutes() * 60;
        $this->raiseGcLifetime($seconds);

        if (array_key_exists($this->session->getName(), $request->getCookieParams())) {
            $this->countThisRequest($seconds, time());
        }

        return $handler->handle($request);
    }

    /** True when more than $allowedSeconds have passed since $lastActivity. A session with no recorded time is not idle. */
    public static function isIdle(?int $lastActivity, int $now, int $allowedSeconds): bool
    {
        return null !== $lastActivity && $now - $lastActivity > $allowedSeconds;
    }

    /** What the session holds under LAST_ACTIVITY_KEY, as Unix seconds; null when there is no usable value. */
    private static function asTimestamp(mixed $recorded): ?int
    {
        return is_numeric($recorded) ? (int) $recorded : null;
    }

    private function countThisRequest(int $allowedSeconds, int $now): void
    {
        $lastActivity = self::asTimestamp($this->session->get(self::LAST_ACTIVITY_KEY));

        if (self::isIdle($lastActivity, $now, $allowedSeconds)) {
            // the same wipe a logout does: the identity lives in the session
            $this->session->clear();
            $lastActivity = null;
        }

        if (null === $lastActivity || $now - $lastActivity >= self::TOUCH_INTERVAL_SECONDS) {
            $this->session->set(self::LAST_ACTIVITY_KEY, $now);
        }
    }

    /** Never lowers the value: another application sharing the session folder may need a longer one. */
    private function raiseGcLifetime(int $seconds): void
    {
        if ($seconds > (int) ini_get('session.gc_maxlifetime')) {
            ini_set('session.gc_maxlifetime', (string) $seconds);
        }
    }
}
