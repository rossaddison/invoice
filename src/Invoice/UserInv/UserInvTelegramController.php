<?php

declare(strict_types=1);

namespace App\Invoice\UserInv;

use App\Infrastructure\Persistence\User\User;
use App\Invoice\BaseController;
use App\Invoice\Helpers\Telegram\TelegramHelper;
use App\Invoice\Telegram\TelegramLinkService;
use App\Invoice\UserInv\UserInvRepository as uiR;
use Phptg\BotApi\Type\User as BotUser;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Yiisoft\Http\Method;

/**
 * The signed-in user's own Telegram link, from their profile page (userinv/guest): link it, and unlink it.
 *
 * The chat id is never typed in: pressing Link Telegram makes a one-time token and shows the t.me link that
 * carries it, and the webhook stores the chat id when the user presses Start in the bot. See
 * App\Invoice\Telegram\TelegramLinkService. Kept apart from UserInvController, which is at the limit of
 * methods a controller should have; it shares that controller's view folder.
 */
final class UserInvTelegramController extends BaseController
{
    protected string $controllerName = 'invoice/userinv';

    private const string PROFILE_ROUTE = 'userinv/guest';

    /**
     * Shows the one-time t.me link. Only a POST (the button) makes a token: opening the address does nothing.
     */
    public function link(
        Request $request,
        uiR $uiR,
        TelegramLinkService $links,
        LoggerInterface $logger,
    ): Response {
        $user = $this->userService->getUser();
        if (null === $user || null === $uiR->repoUserInvUserIdquery($user->reqId())) {
            return $this->webService->getNotFoundResponse();
        }
        if ($request->getMethod() !== Method::POST) {
            return $this->toProfile();
        }
        $deepLink = $this->deepLink($user, $links, $logger);
        return null === $deepLink
            ? $this->toProfile()
            : $this->webViewRenderer->render('_telegram_link', [
                'deepLink' => $deepLink,
                'minutes' => intdiv(TelegramLinkService::TOKEN_LIFETIME_SECONDS, 60),
            ]);
    }

    /**
     * Unlinks the user's Telegram and switches their Telegram reminders off.
     */
    public function unlink(Request $request, uiR $uiR, TelegramLinkService $links): Response
    {
        $user = $this->userService->getUser();
        $userinv = null !== $user ? $uiR->repoUserInvUserIdquery($user->reqId()) : null;
        if (null === $userinv) {
            return $this->webService->getNotFoundResponse();
        }
        if ($request->getMethod() === Method::POST) {
            $links->unlinkUserInv($userinv);
            $this->flashMessage('info', $this->translator->translate('telegram.unlinked'));
        }
        return $this->toProfile();
    }

    /**
     * @return string|null the t.me link carrying a new one-time token, or null (after telling the user) when
     *                     Telegram is switched off or not set up, or the bot cannot be reached
     */
    private function deepLink(User $user, TelegramLinkService $links, LoggerInterface $logger): ?string
    {
        $token = $this->sR->getSetting('telegram_token');
        $bot = $this->sR->getSetting('enable_telegram') === '1' && strlen($token) >= 2
            ? new TelegramHelper($token, $logger)->getBotApi()->getMe()
            : null;
        if (!$bot instanceof BotUser || null === $bot->username) {
            $this->flashMessage('warning', $this->translator->translate('telegram.link.unavailable'));
            return null;
        }
        return 'https://t.me/' . $bot->username . '?start=' . $links->createToken((int) $user->getIdentity()->getId());
    }

    private function toProfile(): Response
    {
        return $this->webService->getRedirectResponse(self::PROFILE_ROUTE);
    }
}
