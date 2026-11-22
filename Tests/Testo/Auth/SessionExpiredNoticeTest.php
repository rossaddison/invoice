<?php

declare(strict_types=1);

namespace Tests\Testo\Auth;

use App\Auth\SessionExpiredNotice;
use Testo\Assert;
use Testo\Test;

#[Test]
final class SessionExpiredNoticeTest
{
    public function theRedirectCarriesTheKnownReason(): void
    {
        Assert::same(SessionExpiredNotice::queryParameters(), ['reason' => 'session-expired']);
    }

    public function theLoginPageShowsTheNoticeForThatReasonOnly(): void
    {
        Assert::true(SessionExpiredNotice::isRequested(['reason' => 'session-expired']));
        Assert::false(SessionExpiredNotice::isRequested([]));
        Assert::false(SessionExpiredNotice::isRequested(['reason' => 'something-else']));
        Assert::false(SessionExpiredNotice::isRequested(['reason' => ['session-expired']]));
        Assert::false(SessionExpiredNotice::isRequested(['other' => 'session-expired']));
    }

    public function theRoundTripFromTheRedirectToTheLoginPageWorks(): void
    {
        Assert::true(SessionExpiredNotice::isRequested(SessionExpiredNotice::queryParameters()));
    }

    public function theLoginViewRendersTheNoticeFromTheTwoTranslationKeys(): void
    {
        $root = dirname(__DIR__, 3);
        $view = (string) file_get_contents($root . '/resources/views/auth/login.php');
        $messages = (string) file_get_contents($root . '/resources/messages/en/app.php');

        Assert::true(str_contains($view, 'if ($sessionExpired)'));
        foreach (['session.expired', 'session.expired.log.in.again'] as $key) {
            Assert::true(str_contains($view, "translate('" . $key . "')"));
            Assert::same(preg_match("/'" . preg_quote($key, '/') . "'\\s*=>/", $messages), 1);
        }
    }
}
