<?php

declare(strict_types=1);

namespace App\Backend\Controller\Trait;

use App\Auth\Client\HmrcDeveloperHubLinks;
use Psr\Http\Message\ResponseInterface as Response;
use Yiisoft\Html\Html;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Yii\AuthClient\RequestUtil;

trait HmrcFphTrait
{
    /**
     * Live-tested 2026-09-09 against HMRC's real Test Fraud Prevention
     * Headers API (`txm-fph-validator-api`) OAS spec -- two real bugs found
     * and fixed here: this endpoint is GET, not POST, and `$api` must be
     * one of the spec's real `{service}-mtd` identifiers (e.g. `vat-mtd`),
     * not a bare `vat`/`self-assessment`/etc. -- both would 404 with
     * `MATCHING_RESOURCE_NOT_FOUND` otherwise (confirmed live). See
     * `resources/backend/views/hmrc/index.php`'s own link for the caller.
     * Full enum: https://developer.service.hmrc.gov.uk/api-documentation/docs/api/service/txm-fph-validator-api/1.0/oas/resolved
     *
     * Live-testing fix 2026-09-10: two more real bugs found together.
     *
     * 1. This called Guzzle's own get() (http_errors defaults to true
     *    there, unlike the PSR-18 sendRequest() every other action in
     *    this controller uses, which never throws for an HTTP error
     *    status) -- any non-2xx crashed with an uncaught
     *    GuzzleHttp\Exception\ClientException rendered as a raw stack
     *    trace, confirmed live (401 INVALID_CREDENTIALS). Switched to
     *    the same createRequest()/sendRequest() pattern as everything
     *    else here.
     *
     * 2. That 401 was real, not just badly presented: this endpoint
     *    (GET .../validation-feedback) is application-restricted per
     *    its own OAS spec -- it needs a Client Credentials grant token
     *    (this app's own client_id/secret exchanged directly, no user
     *    involved), not the user-restricted 3-legged OAuth token
     *    (hmrc_access_token) every other action here correctly uses.
     *    Fetches its own token via requestClientCredentialsToken()
     *    rather than reading the session's user token.
     *
     * Also added the Accept header the spec requires (missing before)
     * and a proper results view instead of returning HMRC's raw
     * response -- same reasoning as fphValidate()'s own fix.
     */
    public function fphFeedback(
        #[RouteArgument('api')]
        string $api,
    ): Response {
        $accessToken = $this->resolveFphFeedbackAccessToken();
        if (null === $accessToken) {
            return $this->webService->getRedirectResponse(self::INDEX_ROUTE);
        }

        return $this->fetchFphFeedback($api, $accessToken);
    }

    /**
     * The Client Credentials token fphFeedback() needs, or null (having
     * already flashed the specific reason) when it couldn't get one.
     * Split out of fphFeedback() itself, and further split from
     * exchangeFphFeedbackClientCredentialsForAccessToken() below, purely
     * to keep each method's own return count within SonarCloud's php:S1142
     * limit (3) -- the control flow is otherwise unchanged from before.
     */
    private function resolveFphFeedbackAccessToken(): ?string
    {
        $credentials = $this->fphFeedbackClientCredentials();
        if (null === $credentials) {
            return null;
        }

        return $this->exchangeFphFeedbackClientCredentialsForAccessToken(
            $credentials[0],
            $credentials[1],
        );
    }

    /**
     * @return array{0: string, 1: string}|null [clientId, clientSecret], or
     *     null (having already flashed) when either is unconfigured.
     */
    private function fphFeedbackClientCredentials(): ?array
    {
        $clientId = $_ENV['DEVELOPER_GOV_SANDBOX_HMRC_API_CLIENT_ID'] ?? '';
        $clientSecret = $_ENV['DEVELOPER_GOV_SANDBOX_HMRC_API_CLIENT_SECRET']
            ?? '';
        if ($clientId === '' || $clientSecret === '') {
            $this->flashMessage(
                'danger',
                $this->translator->translate(
                    'mtd.fph.feedback.no.client.credentials.token',
                ),
            );
            return null;
        }

        return [$clientId, $clientSecret];
    }

    private function exchangeFphFeedbackClientCredentialsForAccessToken(
        string $clientId,
        string $clientSecret,
    ): ?string {
        $tokenResponse = $this->requestClientCredentialsToken(
            $clientId,
            $clientSecret,
        );
        /** @var array<string, mixed> $tokenParsed */
        $tokenParsed = (array) json_decode(
            $tokenResponse->getBody()->getContents(),
            true,
        );

        if ($tokenResponse->getStatusCode() !== 200) {
            $this->flashClientCredentialsTokenError($tokenParsed);
            return null;
        }

        $hasAccessToken = isset($tokenParsed['access_token'])
            && is_string($tokenParsed['access_token'])
            && $tokenParsed['access_token'] !== '';
        if (!$hasAccessToken) {
            $this->flashMessage(
                'danger',
                $this->translator->translate(
                    'mtd.fph.feedback.unexpected.token.response',
                ),
            );
            return null;
        }

        return $tokenParsed['access_token'];
    }

    private function fetchFphFeedback(string $api, string $accessToken): Response
    {
        $otpReference = (string) $this->session->get('otpRef');
        $logFile = $this->sR->specificCommonConfigAliase('@hmrc')
            . '/hmrc-requests.log';

        $request = $this->createRequest(
            'GET',
            $this->getFphValidationFeedbackUrl($api),
        );
        $request = RequestUtil::addHeaders($request, array_merge(
            [
                'Accept'        => 'application/vnd.hmrc.1.0+json',
                'Authorization' => 'Bearer ' . $accessToken,
            ],
            $this->getWebAppViaServerHeaders($otpReference),
        ));

        // PSR-18 sendRequest() (not Guzzle's own get()) so an HTTP error
        // status comes back as a normal Response, not a thrown
        // exception -- same reasoning as the docblock above. Uses its
        // own logged client rather than $this->sendRequest() purely so
        // this specific request is still written to hmrc-requests.log,
        // same as before this fix.
        $loggedClient = $this->createLoggedGuzzleClient($logFile);
        $apiResponse = $loggedClient->sendRequest($request);
        /** @var array<string, mixed> $parsed */
        $parsed = (array) json_decode(
            $apiResponse->getBody()->getContents(),
            true,
        );

        if ($apiResponse->getStatusCode() !== 200) {
            $this->flashFphValidateError($parsed);
            return $this->webService->getRedirectResponse(self::INDEX_ROUTE);
        }

        /** @var list<array<string, mixed>> $requests */
        $requests = $parsed['requests'] ?? [];

        return $this->webViewRenderer->render('fphFeedback', [
            'alert'    => $this->alert(),
            'api'      => $api,
            'requests' => $requests,
        ]);
    }

    /**
     * Application-restricted OAuth 2.0 Client Credentials grant -- this
     * app's own client_id/secret exchanged directly for a token, no
     * user session involved (distinct from the 3-legged
     * hmrc_access_token every user-restricted action here uses). Returns
     * the raw response rather than just a token/null -- fphFeedback()
     * needs the body on failure too, to show the real reason (see
     * flashClientCredentialsTokenError()'s own docblock for why a
     * generic "could not get a token" message wasn't good enough live).
     *
     * Live-testing fix 2026-09-10: this used to hardcode the production
     * token host (api.service.hmrc.gov.uk) unconditionally -- confirmed
     * live as wrong ("invalid_client -- invalid client id or secret")
     * and against HMRC's own application-restricted-endpoints guide
     * (https://developer.service.hmrc.gov.uk/api-documentation/docs/authorisation/application-restricted-endpoints,
     * "The example URLs shown below are for the sandbox environment
     * only. In the production environment you should use
     * https://api.service.hmrc.gov.uk") that sandbox testing must POST
     * to test-api.service.hmrc.gov.uk/oauth/token instead. The only
     * caller of this method (fphFeedback(), via
     * getFphValidationFeedbackUrl()) exists purely to exercise HMRC's
     * Test Fraud Prevention Headers API, which this file's own
     * resolveHmrcApiBaseUrl() docblock already documents as sandbox-only
     * tooling that doesn't exist in production at all -- so this app's
     * client_id/secret for it is necessarily a sandbox-only Developer
     * Hub application, one production's own OAuth server has never
     * heard of. Always using the sandbox host here, unconditionally,
     * matches getFphValidateHeadersUrl()/getFphValidationFeedbackUrl()'s
     * own deliberate hardcoding for the exact same reason.
     */
    private function requestClientCredentialsToken(
        string $clientId,
        string $clientSecret,
    ): Response {
        $body = http_build_query([
            'grant_type'    => 'client_credentials',
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
        ]);

        $tokenUrl = 'https://test-api.service.hmrc.gov.uk/oauth/token';
        $request = $this->createRequest('POST', $tokenUrl);
        $request = RequestUtil::addHeaders($request, [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept'       => 'application/json',
        ]);
        $request = $request->withBody(\GuzzleHttp\Psr7\Utils::streamFor($body));

        return $this->sendRequest($request);
    }

    /**
     * Live-testing fix 2026-09-10: the first version of this flow just
     * flashed a generic "could not get a token" message for every
     * token-request failure -- reported live as unhelpful when
     * client_id/secret were genuinely configured and the real problem
     * was HMRC rejecting the request for a different reason (e.g. the
     * application not being enabled for the Client Credentials grant
     * type). This endpoint's errors use the plain OAuth 2.0 RFC 6749
     * shape (error/error_description) -- confirmed via HMRC's own
     * reference guide -- not the code/message shape every other HMRC
     * endpoint in this app uses (flashFphValidateError() handles that
     * one), so it needs its own parsing.
     *
     * @param array<string, mixed> $parsed
     */
    private function flashClientCredentialsTokenError(array $parsed): void
    {
        $error = (string) ($parsed['error'] ?? '');
        $description = (string) ($parsed['error_description'] ?? '');
        if ($error === '' && $description === '') {
            $description = 'HMRC returned an unreadable error response.';
        }

        $this->flashMessage('danger', $this->translator->translate(
            'mtd.fph.feedback.client.credentials.error',
            ['error' => $error, 'description' => $description],
        ));
    }

    /**
     * Live-testing fix 2026-09-10: this used to return HMRC's raw
     * response as-is -- fine for a 200 (still just raw JSON, now
     * rendered properly below instead), but an HMRC platform-level
     * error like RESOURCE_FORBIDDEN ("The application is not
     * subscribed to the API which it is attempting to invoke") showed
     * up as an unstyled raw JSON blob with no explanation. Now parsed
     * and turned into an explicit flash message -- including a direct
     * link to this application's own Subscriptions page
     * (https://developer.service.hmrc.gov.uk/developer/applications/{id}/subscriptions)
     * when RESOURCE_FORBIDDEN is the specific cause, since that's
     * exactly the page that fixes it.
     *
     * Live-testing fix 2026-09-10 (later same day): both early-exit
     * guards below used to redirect straight to invoice/index -- the
     * general dashboard, not even back to backend/hmrc -- with no flash
     * message at all. From the "Test FPH Headers" button on
     * backend/hmrc that read as the button "going nowhere" (reported
     * live): the page changes, but to something that doesn't look like
     * anything happened, no explanation why. Both guards now flash an
     * explicit reason and redirect back to backend/hmrc/index, matching
     * the pattern already used for the HMRC-side errors above.
     */
    public function fphValidate(): Response
    {
        $prerequisites = $this->fphValidatePrerequisites();
        if (null === $prerequisites) {
            return $this->webService->getRedirectResponse(self::INDEX_ROUTE);
        }
        [$tokenString, $otpReference] = $prerequisites;

        return $this->performFphValidate($tokenString, $otpReference);
    }

    /**
     * The signed-in user's OTP session and HMRC access token, or null
     * (having already flashed the specific reason) when either is
     * missing/invalid. Split out of fphValidate() itself, same reason as
     * fphFeedback()'s own split above: keeping each method's own return
     * count within SonarCloud's php:S1142 limit (3).
     *
     * @return array{0: string, 1: string}|null [hmrc_access_token, otpReference]
     */
    private function fphValidatePrerequisites(): ?array
    {
        $otp = (int) $this->session->get('otp');
        $otpReference = (string) $this->session->get('otpRef');
        if ($otp <= 99999 || $otp >= 1000000 || strlen($otpReference) === 0) {
            $this->flashMessage(
                'warning',
                $this->translator->translate('mtd.fph.missing.otp.session'),
            );
            return null;
        }

        $tokenString = (string) $this->session->get('hmrc_access_token');
        if (strlen($tokenString) === 0) {
            $this->flashMessage('warning', $this->translator->translate(
                'mtd.fph.missing.hmrc.token',
                ['link' => $this->hmrcLoginLink()],
            ));
            return null;
        }

        return [$tokenString, $otpReference];
    }

    private function performFphValidate(string $tokenString, string $otpReference): Response
    {
        $headers = $this->getWebAppViaServerHeaders($otpReference);
        $requestPartOne = $this->createRequest(
            'GET',
            $this->getFphValidateHeadersUrl(),
        );

        $acceptAndAuthorizationArray = [
            'Accept' => 'application/vnd.hmrc.1.0+json',
            'Authorization' => 'Bearer ' . $tokenString,
        ];

        $mergedArray = array_merge($acceptAndAuthorizationArray, $headers);

        $requestPartTwo = RequestUtil::addHeaders($requestPartOne, $mergedArray);

        $apiResponse = $this->sendRequest($requestPartTwo);
        /** @var array<string, mixed> $parsed */
        $parsed = (array) json_decode(
            $apiResponse->getBody()->getContents(),
            true,
        );

        if ($apiResponse->getStatusCode() !== 200) {
            $this->flashFphValidateError($parsed);
            return $this->webService->getRedirectResponse(self::INDEX_ROUTE);
        }

        /** @var list<array<string, mixed>> $errors */
        $errors = $parsed['errors'] ?? [];
        /** @var list<array<string, mixed>> $warnings */
        $warnings = $parsed['warnings'] ?? [];

        return $this->webViewRenderer->render('fphValidate', [
            'alert'       => $this->alert(),
            'specVersion' => (string) ($parsed['specVersion'] ?? ''),
            'code'        => (string) ($parsed['code'] ?? ''),
            'message'     => (string) ($parsed['message'] ?? ''),
            'errors'      => $errors,
            'warnings'    => $warnings,
        ]);
    }

    /**
     * The "Log in with HMRC" OAuth2 authorization URL, or '' if the
     * OAuth client isn't configured (no client_id) -- same condition
     * index() has always gated its own "Log in with HMRC" button on.
     * Extracted so both index() and hmrcLoginLink() below share the
     * exact same check rather than duplicating it.
     */
    private function hmrcAuthUrl(): string
    {
        if ($this->developerSandboxHmrc->getClientId() === '') {
            return '';
        }

        return $this->urlGenerator->generate(
            'auth/authclient',
            ['authclient' => 'developersandboxhmrc'],
        );
    }

    /**
     * "Log in with HMRC" as a real link when OAuth is configured (so a
     * flash message can send the user straight there, not just tell
     * them to find the button themselves), plain text otherwise.
     */
    private function hmrcLoginLink(): string
    {
        $url = $this->hmrcAuthUrl();
        if ($url === '') {
            return 'Log in with HMRC';
        }

        return (string) Html::a('Log in with HMRC', $url);
    }

    /**
     * @param array<string, mixed> $parsed
     */
    private function flashFphValidateError(array $parsed): void
    {
        $code = (string) ($parsed['code'] ?? '');
        $message = (string) ($parsed['message'] ?? '');
        if ($code === '' && $message === '') {
            $message = 'HMRC returned an unreadable error response.';
        }

        $developerHubAppId = $_ENV['DEVELOPER_GOV_SANDBOX_HMRC_API_APPLICATION_ID']
            ?? '';
        $subscriptionsUrl = HmrcDeveloperHubLinks::applicationSubscriptionsUrl(
            $developerHubAppId,
        );

        if ($code === 'RESOURCE_FORBIDDEN' && $subscriptionsUrl !== '') {
            $this->flashMessage('danger', $this->translator->translate(
                'mtd.fph.validate.error.not.subscribed',
                [
                    'code'    => $code,
                    'message' => $message,
                    'link'    => Html::a(
                        $this->translator->translate(
                            'mtd.fph.manage.subscriptions.link.text',
                        ),
                        $subscriptionsUrl,
                        ['target' => '_blank', 'rel' => 'noopener noreferrer'],
                    ),
                ],
            ));
            return;
        }

        $this->flashMessage('danger', $this->translator->translate(
            'mtd.fph.validate.error.generic',
            ['code' => $code, 'message' => $message],
        ));
    }
}
