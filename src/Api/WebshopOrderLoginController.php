<?php

declare(strict_types=1);

namespace App\Api;

use App\Auth\AuthService;
use App\Auth\TokenRepository;
use App\Invoice\AppConstants;
use App\Invoice\Traits\FlashMessage;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Security\TokenMask;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Redeems the one-time login link `OrderService` issues after a webshop
 * checkout (see `docs/STOCK_MOVEMENT_LEDGER_AND_WEBSHOP_API_AUGUST_2026.md`)
 * — establishes a real session for the auto-created account, then hands
 * the customer off to `inv/view/{id}`, the same page every other invoice
 * recipient already uses to pick a gateway and pay.
 *
 * Deliberately not under `RoutePermission::invoiceGroup()`: a brand-new
 * customer has no session yet. Secured by the masked, time-limited,
 * single-use token in the URL instead — the identical pattern
 * `UserInvController::signup()` already uses for its own
 * email-verification link (same 1-hour expiry, same masked
 * token-plus-timestamp shape), not a new security model.
 */
final class WebshopOrderLoginController
{
    use FlashMessage;

    public function __construct(
        private readonly WebControllerService $webService,
        private readonly TranslatorInterface $translator,
        private readonly Flash $flash,
        private readonly AuthService $authService,
        private readonly TokenRepository $tokenRepository,
        private readonly SessionInterface $session,
    ) {
    }

    public function login(#[RouteArgument('token')] string $token): ResponseInterface
    {
        $context = $this->resolveLoginContext($token);
        if ($context === null || !$this->authService->oauthLogin($context['login'])) {
            return $this->fail();
        }

        // No interactive password+TOTP prompt happens on this one-time-link
        // path — same situation every other non-password login in this app
        // is already in (see e.g. Callback::callbackGovUk()'s own
        // `$this->session->set('tfa_verified', true)` right after its own
        // oauth-style login), and the same flag `BaseController::
        // initializeViewRenderer()` requires before it will ever call
        // `WebViewRenderer::withControllerName()` for a VIEW_INV-only
        // (observer) account. Without it, `$this->webViewRenderer` is left
        // as the raw, un-configured renderer with no controller name set,
        // and `InvController::view()`'s own `render('view', ...)` call —
        // reached only *after* the RBAC ownership check now passes (see
        // OrderService's class docblock for that fix) — throws
        // ViewNotFoundException trying to resolve the bare `resources/
        // views/view.php` instead of `resources/views/invoice/inv/view.php`.
        // Confirmed live: a real webshop checkout → one-time-login-link →
        // inv/view 500'd on exactly this before this line was added. The
        // token itself is already a single-use, time-limited, masked
        // server-issued secret (see resolveLoginContext()) — at least as
        // strong a guarantee as a password, so trusting it in place of an
        // interactive TOTP prompt is consistent with the rest of the app's
        // own oauth-login precedent, not a new exception carved out here.
        $this->session->set('tfa_verified', true);

        return $this->webService->getRedirectResponse('inv/view', ['id' => (string) $context['invId']]);
    }

    /**
     * @return array{login: string, invId: int}|null
     */
    private function resolveLoginContext(string $tokenMasked): ?array
    {
        // Same unmask-plus-expiry shape as UserInvController::signup().
        $unMaskedToken = TokenMask::remove($tokenMasked);
        $positionFromUnderscore = strrpos($unMaskedToken, '_');
        $timestamp = $positionFromUnderscore === false
            ? '' : substr($unMaskedToken, $positionFromUnderscore + 1);
        if ($positionFromUnderscore === false || (int) $timestamp + 3600 < time()) {
            return null;
        }
        $tokenWithoutTimestamp = substr($unMaskedToken, 0, -(strlen($timestamp) + 1));

        $tokenEntity = $this->tokenRepository->findTokenByToken($tokenWithoutTimestamp);
        $invId = $this->extractInvId($tokenEntity?->getType());
        $login = $tokenEntity?->getIdentity()?->getUser()?->getLogin();
        if ($tokenEntity === null || $invId === null || $login === null) {
            return null;
        }

        // Invalidate the token so it cannot be used again — same pattern
        // as UserInvController::processValidToken().
        $tokenEntity->setToken('already_used_token_' . (string) time());
        $this->tokenRepository->save($tokenEntity);

        return ['login' => $login, 'invId' => $invId];
    }

    private function extractInvId(?string $type): ?int
    {
        if ($type === null) {
            return null;
        }
        $prefix = AppConstants::TOKEN_TYPE_WEBSHOP_ORDER_LOGIN . ':';
        if (!str_starts_with($type, $prefix)) {
            return null;
        }
        $invId = (int) substr($type, strlen($prefix));
        return $invId > 0 ? $invId : null;
    }

    private function fail(): ResponseInterface
    {
        $this->flashMessage('warning', $this->translator->translate('not.found'));
        return $this->webService->getRedirectResponse('site/index');
    }
}
