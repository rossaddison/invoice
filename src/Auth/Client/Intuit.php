<?php

declare(strict_types=1);

namespace App\Auth\Client;

use Psr\Http\Message\RequestInterface;
use Yiisoft\Yii\AuthClient\OAuth2;
use Yiisoft\Yii\AuthClient\RequestUtil;

/**
 * Intuit/QuickBooks OAuth2 client, replacing the hand-rolled Guzzle flow
 * proven live in PR #1339 (`bin/quickbooks/QuickBooksOAuthClient.php`) and
 * the duplicated refresh logic that was inside
 * `App\Bookkeeping\Infrastructure\QuickBooks\QuickBooksGateway`. Used from
 * two different places, deliberately not both wired the same way:
 *
 * - The one-time interactive `authorization_code` consent
 *   (`buildAuthUrl()`/`fetchAccessToken()`) happens through
 *   `App\Bookkeeping\Controller\QuickBooksConnectController` — a small
 *   dedicated controller, NOT `App\Auth\Controller\AuthController`. Every
 *   other client here (HMRC, GitHub, Google...) plugs into AuthController
 *   because that controller identifies/links a *user* of this app;
 *   connecting to QuickBooks identifies a *company* this app instance
 *   exports bookkeeping data to, an unrelated concern.
 * - The ongoing `refresh_token` grant (`refreshAccessToken()`) is called
 *   from `QuickBooksGateway` itself, including from console context
 *   (background export) — genuinely headless, no HTTP session at all.
 *   `OAuth2::refreshAccessToken()`'s body touches no session/state
 *   storage, confirmed by reading it directly, which is what makes this
 *   safe.
 *
 * `$jsonTokenResponse = true` (see OAuth2's own docblock in the fork) is
 * required here: Intuit's token endpoint returns JSON, not the
 * `application/x-www-form-urlencoded` body every other provider in this
 * fork was written against.
 *
 * `applyClientCredentialsToRequest()` is overridden to send a Basic-auth
 * header rather than this base class's default of putting client_id/
 * client_secret in the request body -- Intuit's own docs require Basic
 * auth for the token endpoint, and this exact shape was already proven
 * live against the real Intuit sandbox in PR #1339.
 *
 * `OAuthToken::getToken()` returns null on tokens this class produces
 * (it looks up `oauth_token`, not `access_token` -- see OAuth2::
 * createToken()'s comment, bypassed by fetchAccessToken()/
 * refreshAccessToken() building `new OAuthToken()` directly). Every
 * caller must read `getParam('access_token')`/`getParam('refresh_token')`
 * instead, matching this app's own DeveloperSandboxHmrc callers.
 *
 * @see https://developer.intuit.com/app/developer/qbo/docs/develop/authentication-and-authorization/oauth-2.0
 */
final class Intuit extends OAuth2
{
    protected string $authUrl = 'https://appcenter.intuit.com/connect/oauth2';
    protected string $tokenUrl = 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer';
    protected bool $jsonTokenResponse = true;

    #[\Override]
    public function getName(): string
    {
        return 'intuit';
    }

    #[\Override]
    public function getTitle(): string
    {
        return 'QuickBooks';
    }

    #[\Override]
    protected function getDefaultScope(): string
    {
        return 'com.intuit.quickbooks.accounting';
    }

    #[\Override]
    public function getButtonClass(): string
    {
        return 'btn btn-primary';
    }

    #[\Override]
    protected function applyClientCredentialsToRequest(RequestInterface $request): RequestInterface
    {
        return RequestUtil::addHeaders($request, [
            'Authorization' => 'Basic ' . base64_encode($this->getClientId() . ':' . $this->getClientSecret()),
        ]);
    }
}
