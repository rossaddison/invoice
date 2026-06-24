<?php

declare(strict_types=1);

namespace App\Auth\Trait;

use App\Auth\CallbackDeps;
use App\Auth\TokenRepository;
use App\Infrastructure\Persistence\User\User;
use App\Invoice\UserInv\UserInvRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LogLevel;
use Yiisoft\Input\Http\Attribute\Parameter\Query;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Security\Random;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\AuthClient\Widget\AuthChoice;

trait Callback
{
    public function callbackDeveloperGovSandboxHmrc(
        CallbackDeps $d,
        #[RouteArgument('_language')]
        string $_language,
        #[Query('code')]
        ?string $code = null,
        #[Query('state')]
        ?string $state = null,
    ): ResponseInterface {
        if ($code == null || $state == null) {
            return $this->redirectToOauth2AuthError(
                $d->translator->translate('oauth2.missing.authentication.code'
                        . '.or.state.parameter'));
        }

        $this->blockInvalidState('developergovsandboxhmrc', $state);
        $developerSandboxHmrc = (AuthChoice::widget())->getClient('developersandboxhmrc');

        $login = '';
        $email = '';
        $password = '';
        $response = null;

        /**
         * @psalm-suppress DocblockTypeContradiction $code
         */
        if (strlen($code) == 0) {
            $authorizationUrl = $developerSandboxHmrc->buildAuthUrl($d->request, []);
            $response = $this->webService->getRedirectResponse($authorizationUrl);
        } elseif ($code == 401) {
            $response = $this->redirectToOauth2CallbackResultUnAuthorised();
        } elseif (strlen($state) == 0) {
            /**
             * @psalm-suppress DocblockTypeContradiction $state
             * State is invalid, possible cross-site request forgery.
             */
            $response = $this->redirectToOauth2AuthError(
                    $d->translator->translate('oauth2.missing.state'
                            . '.parameter.possible.csrf.attack'));
        } elseif ($this->sR->getEnv() != 'dev') {
            $response = $this->redirectToOauth2AuthError(
                $d->translator->translate('oauth2.test.user.creation.not.allowed.prod'));
        } else {
            $codeVerifier = (string) $this->session->get('code_verifier');
            /**
             * @see https://developer.service.hmrc.gov.uk/
                api-documentation/docs/authorisation
             * For user-restricted access, the 'Authorization Code' Grant Type is used
             * Use the code received, to get an access_token
             */
            $oAuthToken = $developerSandboxHmrc->fetchAccessTokenWithCodeVerifier(
                $d->request, $code, [
                    'redirect_uri' => $developerSandboxHmrc->getOauth2ReturnUrl(),
                    'code_verifier' => $codeVerifier,
                    'grant_type' => 'authorization_code',
                ]);
            // e.g. string '476425f97e53ca1124161e491bee384e'
            $this->session->set('hmrc_access_token', $oAuthToken->getParam('access_token'));
            // e.g. string 'bearer'
            $this->session->set('hmrc_token_type', $oAuthToken->getParam('token_type'));
            // default 'expires_in' int 14400
            $this->session->set('hmrc_token_expires',
                    time() + (int) $oAuthToken->getParam('expires_in'));
            // e.g. read:self-assessment write:self-assessment'
            $this->session->set('hmrc_scope', $oAuthToken->getParam('scope'));
            // e.g. string 'cbe7c4f01a6bc55034237718d3e4ded2'
            $this->session->set('hmrc_refresh_token', $oAuthToken->getParam('refresh_token'));
            /**
             * @see Yiisoft\Yii\AuthClient\Client\DeveloperSandboxHmrc
             *  function getTestUserArray;
             */
            $requestBody = ['serviceNames' => ['national-insurance']];
            /** @psalm-var \App\Auth\Client\DeveloperSandboxHmrc $developerSandboxHmrc */
            $userArray = $developerSandboxHmrc->createTestUserIndividual($oAuthToken, $requestBody);
            /**
             * @var int $userArray['userId']
             */
            $hmrcId = $userArray['userId'] ?? 0;
            if ($hmrcId <= 0) {
                $this->authService->logout();
                $response = $this->redirectToMain();
            } else {
                $login = 'hmrc' . (string) $hmrcId;
                /**
                 * Depending on the environment i.e. prod or dev, getApiBaseUrl1()
                 *  will vary between 'https://api.service.hmrc.gov.uk' or
                 *  'https://test-api.service.hmrc.gov.uk' respectively
                 *
                 * @var string $userArray['emailAddress']
                 */
                $email = $userArray['emailAddress'] ?? 'noemail'
                        . $login . '@'
                        . str_replace('https://', '', $developerSandboxHmrc->getApiBaseUrl1());
                $password = Random::string(32);
            }
        }
        if ($response !== null) {
            return $response;
        }
        return $this->oauthRegisterAndProceed(
            'developersandboxhmrc', $login, $email, $password,
            $_language, self::DEVELOPER_SANDBOX_HMRC_ACCESS_TOKEN, $d);
    }

    /**
     * Purpose: Once Facebook redirects to this callback, in this callback function:
     * 1. the user is logged in, or a new user is created, and the
     *       proceedToMenuButton is created
     * 2. clicking on the proceedToMenuButton will further create a userinv
     *       extension of the user table
     * @see src/Invoice/UserInv/UserInvController function facebook
     * @param CallbackDeps $d
     * @param string $_language
     * @param string|null $code
     * @param string|null $state
     * @param string|null $error
     * @param string|null $errorCode
     * @param string|null $errorReason
     * @return ResponseInterface
     */
    public function callbackFacebook(
        CallbackDeps $d,
        #[RouteArgument('_language')]
        string $_language,
        #[Query('code')]
        ?string $code = null,
        #[Query('state')]
        ?string $state = null,
        #[Query('error')]
        ?string $error = null,
        #[Query('error_code')]
        ?string $errorCode = null,
        #[Query('error_reason')]
        ?string $errorReason = null,
    ): ResponseInterface {
        // Avoid MissingRequiredArgumentException
        if ($code == null || $state == null) {
// e.g. User presses cancel button: callbackFacebook?error=access_denied&error_code=200&error_description=Permissions+error&error_reason=user_denied&state=
            return (($errorCode == 200) && ($error == 'access_denied') && ($errorReason == 'user_denied'))
                ? $this->redirectToUserCancelledOauth2()
                : $this->redirectToOauth2AuthError(
                    $d->translator->translate('oauth2.missing.authentication.code.'
                            . 'or.state.parameter'));
        }

        $this->blockInvalidState('facebook', $state);
        $facebook = (AuthChoice::widget())->getClient('facebook');
        $facebookId = 0;
        $facebookLogin = '';
        $userArray = [];
        $response = null;

        /**
         * @psalm-suppress DocblockTypeContradiction $code
         */
        if (strlen($code) == 0) {
// If we don't have an authorization code then get one
// and use the protected function oauth2->generateAuthState to generate state param
// which has a session id built into it
            $authorizationUrl = $facebook->buildAuthUrl($d->request, []);
            $response = $this->webService->getRedirectResponse($authorizationUrl);
        } elseif ($code == 401) {
            $response = $this->redirectToOauth2CallbackResultUnAuthorised();
        } elseif (strlen($state) == 0) {
            /**
             * @psalm-suppress DocblockTypeContradiction $state
             * State is invalid, possible cross-site request forgery.
             */
            $response = $this->redirectToOauth2AuthError(
                $d->translator->translate('oauth2.missing.state.parameter.'
                        . 'possible.csrf.attack'));
        } else {
            /** @psalm-var \Yiisoft\Yii\AuthClient\Client\Facebook $facebook */
            $oAuthTokenType = $facebook->fetchAccessToken($d->request, $code, []);
            $userArray = $facebook->getCurrentUserJsonArray($oAuthTokenType);
            /**
             * @var int $userArray['id']
             */
            $facebookId = $userArray['id'] ?? 0;
            /**
             * @var string $userArray['name']
             */
            $facebookLogin = strtolower($userArray['name'] ?? '');
            if ($facebookId <= 0 || strlen($facebookLogin) == 0) {
                $this->authService->logout();
                $response = $this->redirectToMain();
            }
        }
        if ($response !== null) {
            return $response;
        }
        // the id will be removed in the logout button
        $login = 'facebook' . (string) $facebookId . $facebookLogin;
        /**
         * @var string $userArray['email']
         */
        $email = $userArray['email'] ?? 'noemail' . $login . '@facebook.com';
        $password = Random::string(32);
// The password does not need to be validated here so use
//  authService->oauthLogin($login) instead of
//   authService->login($login, $password)
// but it will be used later to build a passwordHash
        return $this->oauthRegisterAndProceed(
            'facebook', $login, $email, $password,
            $_language, self::FACEBOOK_ACCESS_TOKEN, $d);
    }

    /**
     * Purpose: Once Github redirects to this callback, in this callback function:
     * 1. the user is logged in, or a new user is created, and the
     *     proceedToMenuButton is created
     * 2. clicking on the proceedToMenuButton will further create a userinv
     *     extension of the user table
     * @see src/Invoice/UserInv/UserInvController function github
     * @see https://docs.github.com/en/apps/oauth-apps/building-oauth-apps/
     *       authorizing-oauth-apps
     * @param CallbackDeps $d
     * @param string $_language
     * @param string|null $code
     * @param string|null $state
     * @return ResponseInterface
     */
    public function callbackGithub(
        CallbackDeps $d,
        #[RouteArgument('_language')]
        string $_language,
        #[Query('code')]
        ?string $code = null,
        #[Query('state')]
        ?string $state = null,
    ): ResponseInterface {
        if ($code == null || $state == null) {
            return $this->redirectToOauth2AuthError(
                $d->translator->translate('oauth2.missing.authentication.code.'
                        . 'or.state.parameter'));
        }

        $this->blockInvalidState('github', $state);
        $github = (AuthChoice::widget())->getClient('github');
        /** @psalm-suppress DocblockTypeContradiction $code */
        if (strlen($code) == 0 || $code == 401 || strlen($state) == 0) {
            return match(true) {
                strlen($code) == 0 => $this->webService->getRedirectResponse(
                    $github->buildAuthUrl($d->request, [])),
                $code == 401 => $this->redirectToOauth2CallbackResultUnAuthorised(),
                default => $this->redirectToOauth2AuthError(
                    $d->translator->translate('oauth2.missing.state.parameter'
                        . '.possible.csrf.attack')),
            };
        }
        // Try to get an access token (using the 'authorization code' grant)
        // The 'request_uri' is included by default in $defaultParams[] and
        //  therefore not needed in $params
        // The $response->getBody()->getContents() for each Client e.g. Github
        //  will be parsed and loaded into an OAuthToken Type
        // For Github we know that the parameter key for the token is
        //  'access_token' and not the default 'oauth_token'
        /** @psalm-var \Yiisoft\Yii\AuthClient\Client\GitHub $github */
        $oAuthTokenType = $github->fetchAccessToken($d->request, $code, []);
        /**
         * Every time you receive an access token, you should use the token
         *  to revalidate the user's identity.
         * A user can change which account they are signed into when you send
         *  them to authorize your app,
         * and you risk mixing user data if you do not validate the user's
         *  identity after every sign in.
         * @see https://docs.github.com/en/apps/oauth-apps/building-oauth-apps/
         * authorizing-oauth-apps#3-use-the-access-token-to-access-the-api
         */
        $userArray = $github->getCurrentUserJsonArray($oAuthTokenType);
        /**
         * @var int $userArray['id']
         */
        $githubId = $userArray['id'] ?? 0;
        if ($githubId <= 0) {
            $this->authService->logout();
        }
        // Append github in case user has used same login for other identity providers
        // the id will be removed in the logout button
        $login = 'github' . (string) $githubId . 'g';
        /**
         * @var string $userArray['email']
         */
        $email = $userArray['email'] ?? 'noemail' . $login . '@github.com';
        $password = Random::string(32);
        // The password does not need to be validated here so use
        //  authService->oauthLogin($login) instead of
        //   authService->login($login, $password)
        // but it will be used later to build a passwordHash
        return $githubId <= 0
            ? $this->redirectToMain()
            : $this->oauthRegisterAndProceed(
                'github', $login, $email, $password,
                $_language, self::GITHUB_ACCESS_TOKEN, $d);
    }

    /**
     * @see https://console.cloud.google.com/apis/credentials?project=YOUR_PROJECT
     */
    public function callbackGoogle(
        CallbackDeps $d,
        #[RouteArgument('_language')]
        string $_language,
        #[Query('code')]
        ?string $code = null,
        #[Query('state')]
        ?string $state = null,
    ): ResponseInterface {
        if ($code == null || $state == null) {
            return $this->redirectToOauth2AuthError(
                    $d->translator->translate(
                        'oauth2.missing.authentication.code.or.state.parameter'));
        }

        $this->blockInvalidState('google', $state);
        $google = (AuthChoice::widget())->getClient('google');

        /**
         * @psalm-suppress DocblockTypeContradiction $code
         * @psalm-suppress DocblockTypeContradiction $state
         */
        if (strlen($code) == 0 || $code == 401 || strlen($state) == 0) {
            return match(true) {
                strlen($code) == 0 => $this->webService->getRedirectResponse(
                    $google->buildAuthUrl($d->request, [])),
                $code == 401 => $this->redirectToOauth2CallbackResultUnAuthorised(),
                default => $this->redirectToOauth2AuthError(
                    $d->translator->translate(
                        'oauth2.missing.state.parameter.possible.csrf.attack')),
            };
        }

        /** @psalm-var \Yiisoft\Yii\AuthClient\Client\Google $google */
        $oAuthTokenType = $google->fetchAccessToken($d->request, $code, [
            'grant_type' => 'authorization_code',
        ]);
        $userArray = $google->getCurrentUserJsonArray($oAuthTokenType);
        /** @var int $userArray['id'] */
        $googleId = $userArray['id'] ?? 0;
        if ($googleId <= 0) {
            $this->authService->logout();
        }
        // the id will be removed in the logout button
        $login = 'google' . (string) $googleId;
        /** @var string $userArray['email'] */
        $email = $userArray['email'] ?? 'noemail' . $login . '@google.com';
        $password = Random::string(32);
        return $googleId <= 0
            ? $this->redirectToMain()
            : $this->oauthRegisterAndProceed(
                'google', $login, $email, $password,
                $_language, self::GOOGLE_ACCESS_TOKEN, $d);
    }

    public function callbackGovUk(
        CallbackDeps $d,
        #[RouteArgument('_language')]
        string $_language,
        #[Query('code')]
        ?string $code = null,
        #[Query('state')]
        ?string $state = null,
    ): ResponseInterface {
        if ($code == null || $state == null) {
            return $this->redirectToOauth2AuthError(
                    $d->translator->translate('oauth2.missing.'
                            . 'authentication.code.or.state.parameter'));
        }

        $govUk = (AuthChoice::widget())->getClient('govuk');
        $this->blockInvalidState('govUk', $state);

        /**
         * @psalm-suppress DocblockTypeContradiction $code
         * @psalm-suppress DocblockTypeContradiction $state
         */
        if (strlen($code) == 0 || $code == 401 || strlen($state) == 0) {
            return match(true) {
                strlen($code) == 0 => $this->webService->getRedirectResponse(
                    $govUk->buildAuthUrl($d->request, [])),
                $code == 401 => $this->redirectToOauth2CallbackResultUnAuthorised(),
                default => $this->redirectToOauth2AuthError(
                    $d->translator->translate(
                        'oauth2.missing.state.parameter.possible.csrf.attack')),
            };
        }

        /** @psalm-var \App\Auth\Client\GovUk $govUk */
        $oAuthTokenType = $govUk->fetchAccessToken($d->request, $code, []);
        $userArray = $govUk->getCurrentUserJsonArray($oAuthTokenType);
        /** @var int $userArray['id'] */
        $govUkId = $userArray['id'] ?? 0;
        if ($govUkId <= 0) {
            $this->authService->logout();
        }
        // the id will be removed in the logout button
        $login = 'govuk' . (string) $govUkId;
        /** @var string $userArray['email'] */
        $email = $userArray['email'] ?? 'noemail' . $login . '@gov.uk';
        $password = Random::string(32);
        return $govUkId <= 0
            ? $this->redirectToMain()
            : $this->oauthRegisterAndProceed(
                'govuk', $login, $email, $password,
                $_language, self::GOVUK_ACCESS_TOKEN, $d);
    }

    public function callbackLinkedIn(
        CallbackDeps $d,
        #[RouteArgument('_language')]
        string $_language,
        #[Query('code')]
        ?string $code = null,
        #[Query('state')]
        ?string $state = null,
    ): ResponseInterface {
        if ($code == null || $state == null) {
            return $this->redirectToOauth2AuthError(
                    $d->translator->translate(
                        'oauth2.missing.authentication.code.or.state.parameter'));
        }

        $this->blockInvalidState('linkedIn', $state);
        $linkedIn = (AuthChoice::widget())->getClient('linkedin');

        /**
         * @psalm-suppress DocblockTypeContradiction $code
         * @psalm-suppress DocblockTypeContradiction $state
         */
        if (strlen($code) == 0 || $code == 401 || strlen($state) == 0) {
            return match(true) {
                strlen($code) == 0 => $this->webService->getRedirectResponse(
                    $linkedIn->buildAuthUrl($d->request, [])),
                $code == 401 => $this->redirectToOauth2CallbackResultUnAuthorised(),
                default => $this->redirectToOauth2AuthError(
                    $d->translator->translate(
                        'oauth2.missing.state.parameter.possible.csrf.attack')),
            };
        }

        $params = [
            'grant_type' => 'authorization_code',
            'redirect_uri' => $linkedIn->getOauth2ReturnUrl(),
        ];
        /** @psalm-var \Yiisoft\Yii\AuthClient\Client\LinkedIn $linkedIn */
        $oAuthTokenType = $linkedIn->fetchAccessToken($d->request, $code, $params);
        $userArray = $linkedIn->getCurrentUserJsonArray(
                $oAuthTokenType,
                $this->configWebDiAuthGuzzle,
                $this->requestFactory);
        /** @var string $userArray['sub'] e.g. P1c9jkRFSy — sub is returned instead of an id */
        $linkedInSub = $userArray['sub'] ?? '';
        if (strlen($linkedInSub) == 0) {
            $this->authService->logout();
        }
        /** @var string $userArray['name'] */
        $linkedInName = $userArray['name'] ?? 'unknown';
        $login = 'linkedIn' . $linkedInName;
        /** @var string $userArray['email'] */
        $email = $userArray['email'] ?? 'noemail' . $login . '@linkedin.com';
        $password = Random::string(32);
        return strlen($linkedInSub) == 0
            ? $this->redirectToMain()
            : $this->oauthRegisterAndProceed(
                'linkedin', $login, $email, $password,
                $_language, self::LINKEDIN_ACCESS_TOKEN, $d);
    }

    public function callbackMicrosoftOnline(
        CallbackDeps $d,
        #[RouteArgument('_language')]
        string $_language,
        #[Query('code')]
        ?string $code = null,
        #[Query('state')]
        ?string $state = null,
        #[Query('session_state')]
        ?string $sessionState = null,
    ): ResponseInterface {
        if ($code == null || $state == null || $sessionState == null) {
            return $this->redirectToOauth2AuthError(
                    $d->translator->translate(
                        'oauth2.missing.authentication.code.or.state.parameter'));
        }

        $this->blockInvalidState('microsoftonline', $state);
        $microsoftOnline = (AuthChoice::widget())->getClient('microsoftonline');

        /**
         * @psalm-suppress DocblockTypeContradiction $code
         * @psalm-suppress DocblockTypeContradiction $state
         * @psalm-suppress DocblockTypeContradiction $sessionState
         */
        if (strlen($code) == 0 || $code == '401' || strlen($state) == 0 || strlen($sessionState) == 0) {
            return match(true) {
                strlen($code) == 0 => $this->webService->getRedirectResponse(
                    $microsoftOnline->buildAuthUrl($d->request,
                        ['redirect_uri' => 'https://yii3i.online/callbackMicrosoftOnline'])),
                $code == '401' => $this->redirectToOauth2CallbackResultUnAuthorised(),
                default => $this->redirectToOauth2AuthError(
                    $d->translator->translate(
                        'oauth2.missing.state.parameter.possible.csrf.attack')),
            };
        }

        /** @psalm-var \Yiisoft\Yii\AuthClient\Client\MicrosoftOnline $microsoftOnline */
        $oAuthTokenType = $microsoftOnline->fetchAccessToken($d->request, $code, [
            'grant_type' => 'authorization_code',
            'redirect_uri' => 'https://yii3i.online/callbackMicrosoftOnline',
        ]);
        $userArray = $microsoftOnline->getCurrentUserJsonArray(
                $oAuthTokenType,
                $this->configWebDiAuthGuzzle,
                $this->requestFactory
        );
        /** @var int $userArray['id'] */
        $microsoftOnlineId = $userArray['id'] ?? 0;
        if ($microsoftOnlineId <= 0) {
            $this->authService->logout();
        }
        // Append the last four digits of the Id
        $idStr = (string) $microsoftOnlineId;
        $login = 'ms' . substr($idStr, strlen($idStr) - 4, strlen($idStr));
        /** @var string $userArray['email'] */
        $email = $userArray['email'] ?? 'noemail' . $login . '@microsoftonline.com';
        $password = Random::string(32);
        return $microsoftOnlineId <= 0
            ? $this->redirectToMain()
            : $this->oauthRegisterAndProceed(
                'microsoftonline', $login, $email, $password,
                $_language, self::MICROSOFTONLINE_ACCESS_TOKEN, $d);
    }

    // Untested
    public function callbackOpenBanking(
        ServerRequestInterface $request,
        TranslatorInterface $translator,
        #[Query('code')]
        ?string $code = null,
        #[Query('state')]
        ?string $state = null,
    ): ResponseInterface {
        if ($code === null || $state === null) {
            return $this->redirectToOauth2AuthError(
                    $translator->translate('oauth2.missing.'
                            . 'authentication.code.or.state.parameter'));
        }

        $this->blockInvalidState('openbanking', $state);
        $openBanking = (AuthChoice::widget())->getClient('openbanking');

        if (strlen($code) === 0 || $code == 401 || strlen($state) === 0) {
            return match(true) {
                strlen($code) === 0 => $this->webService->getRedirectResponse(
                    $openBanking->buildAuthUrl($request, [])),
                $code == 401 => $this->redirectToOauth2CallbackResultUnAuthorised(),
                default => $this->redirectToOauth2AuthError(
                    $translator->translate(
                        'oauth2.missing.state.parameter.possible.csrf.attack')),
            };
        }

        $codeVerifier = (string) $this->session->get('code_verifier');

        // Exchange code for token with PKCE
        $oAuthToken =
            $openBanking->fetchAccessTokenWithCodeVerifier($request, $code, [
            'redirect_uri' => $openBanking->getOauth2ReturnUrl(),
            'code_verifier' => $codeVerifier,
            'grant_type' => 'authorization_code',
        ]);

        // Save tokens and claims as appropriate (these keys are your choice)
        $this->session->set('openbanking_access_token',
            $oAuthToken->getParam('access_token'));
        $this->session->set('openbanking_refresh_token',
            $oAuthToken->getParam('refresh_token'));
        $this->session->set('openbanking_id_token',
            $oAuthToken->getParam('id_token'));
        $this->session->set('openbanking_token_type',
            $oAuthToken->getParam('token_type'));
        $this->session->set('openbanking_token_expires',
                time() + (int) $oAuthToken->getParam('expires_in'));
        $this->session->set('openbanking_scope',
            $oAuthToken->getParam('scope'));

        // Optionally: store user claims from id_token if using OpenID Connect
        if ($oAuthToken->getParam('id_token')) {
            $this->session->set('openbanking_claims',
                $oAuthToken->getParam('id_token_payload') ?? []);
        }

        // Continue to app-specific logic (e.g., redirect to dashboard)
        $this->session->set('tfa_verified', true);
        return $this->redirectToInvoiceIndex();
    }

    public function callbackX(
        CallbackDeps $d,
        #[RouteArgument('_language')]
        string $_language,
        #[Query('code')]
        ?string $code = null,
        #[Query('state')]
        ?string $state = null,
    ): ResponseInterface {
        if ($code == null || $state == null) {
            return $this->redirectToOauth2AuthError(
                $d->translator->translate('oauth2.missing.authentication.code.'
                    . 'or.state.parameter'));
        }

        $this->blockInvalidState('x', $state);
        $x = (AuthChoice::widget())->getClient('x');

        $login = '';
        $email = '';
        $password = '';
        $response = null;

        /**
         * @psalm-suppress DocblockTypeContradiction $code
         */
        if (strlen($code) == 0) {
            $codeVerifier = Random::string(128);
            $codeChallenge = strtr(rtrim(base64_encode(hash('sha256',
                    $codeVerifier, true)), '='), '+/', '-_');
            // Store code_verifier in session or other storage
            $this->session->set('code_verifier', $codeVerifier);
            $authorizationUrl = $x->buildAuthUrl(
                $d->request,
                [
                    'code_challenge' => $codeChallenge,
                    'code_challenge_method' => 'S256',
                ],
            );
            $response = $this->webService->getRedirectResponse($authorizationUrl);
        } elseif ($code == 401) {
            $response = $this->redirectToOauth2CallbackResultUnAuthorised();
        } elseif (strlen($state) == 0) {
            /**
             * @psalm-suppress DocblockTypeContradiction $state
             */
            $response = $this->redirectToOauth2AuthError(
                $d->translator->translate('oauth2.missing.state.parameter.'
                        . 'possible.csrf.attack'));
        } else {
            $codeVerifier = (string) $this->session->get('code_verifier');
            $params = [
                'grant_type' => 'authorization_code',
                'redirect_uri' => $x->getOauth2ReturnUrl(),
                'code_verifier' => $codeVerifier,
            ];
            /** @psalm-var \Yiisoft\Yii\AuthClient\Client\X $x */
            $oAuthTokenType = $x->fetchAccessTokenWithCodeVerifier(
                $d->request, $code, $params);
            $userArray = $x->getCurrentUserJsonArray(
                $oAuthTokenType, $this->configWebDiAuthGuzzle, $this->requestFactory);
            /**
             * @var array $userArray['data']
             */
            $data = $userArray['data'] ?? [];
            /**
             * @var int $data['id']
             */
            $xId = $data['id'] ?? 0;
            $xLogin = (string) ($data['username'] ?? '');
            if ($xId <= 0 || strlen($xLogin) == 0) {
                $this->authService->logout();
                $response = $this->redirectToMain();
            } else {
                $login = 'twitter' . (string) $xId . $xLogin;
                /**
                 * @var string $userArray['email']
                 */
                $email = $userArray['email'] ?? 'noemail' . $login . '@x.com';
                $password = Random::string(32);
            }
        }
        if ($response !== null) {
            return $response;
        }
        return $this->oauthRegisterAndProceed(
            'x', $login, $email, $password,
            $_language, self::X_ACCESS_TOKEN, $d);
    }

    public function callbackVKontakte(
        CallbackDeps $d,
        #[RouteArgument('_language')]
        string $_language,
        #[Query('code')]
        ?string $code = null,
        #[Query('state')]
        ?string $state = null,
        #[Query('device_id')]
        ?string $device_id = null,
    ): ResponseInterface {
        if ($code == null || $state == null) {
            return $this->redirectToOauth2AuthError(
                $d->translator->translate('oauth2.missing.authentication.code.'
                    . 'or.state.parameter'));
        }

        $this->blockInvalidState('vkontakte', $state);

        $vkontakte = (AuthChoice::widget())->getClient('vkontakte');

        $earlyResponse = null;
        /** @psalm-suppress DocblockTypeContradiction $code */
        if (strlen($code) == 0) {
            $codeVerifier = Random::string(128);
            $codeChallenge = strtr(rtrim(base64_encode(hash('sha256',
                    $codeVerifier, true)), '='), '+/', '-_');
            $this->session->set('code_verifier', $codeVerifier);
            $authorizationUrl = $vkontakte->buildAuthUrl(
                $d->request,
                [
                    'code_challenge' => $codeChallenge,
                    'code_challenge_method' => 'S256',
                    'device_id' => $device_id,
                ],
            );
            $earlyResponse = $this->webService->getRedirectResponse($authorizationUrl);
        } elseif ($code == 401) {
            $earlyResponse = $this->redirectToOauth2CallbackResultUnAuthorised();
        } elseif (strlen($state) == 0) {
            /** @psalm-suppress DocblockTypeContradiction $state */
            $earlyResponse = $this->redirectToOauth2AuthError(
                $d->translator->translate('oauth2.missing.state.parameter.'
                    . 'possible.csrf.attack'));
        }
        if ($earlyResponse !== null) {
            return $earlyResponse;
        }
        $codeVerifier = (string) $this->session->get('code_verifier');
        $params = [
            'device_id' => $device_id,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $vkontakte->getOauth2ReturnUrl(),
            'code_verifier' => $codeVerifier,
        ];

        /**
         * $oAuthTokenType = e.g.    'refresh_token' => '{string}'
         *                           'access_token' => '{string}'
         *                           'id_token' => '{string}'
         *                           'token_type' => 'Bearer'
         *                           'expires_in' => 3600
         *                           'user_id' => 1023583333
         *                           'state' => '{string}'
         *                           'scope' => 'vkid.personal_info email'
         */
        $oAuthTokenType = $vkontakte->fetchAccessTokenWithCodeVerifier(
            $d->request, $code, $params);

        /**
         * e.g.  'user' => [
         *          'user_id' => '1023581111'
         *          'first_name' => 'Joe'
         *          'last_name' => 'Bloggs'
         *          'avatar' => 'https://..'
         *          'email' => ''
         *          'sex' => 2
         *          'verified' => false
         *          'birthday' => '09.09.1999'
         *       ]
         * @psalm-var \Yiisoft\Yii\AuthClient\Client\VKontakte $vkontakte
         */
        $userArray =
            $vkontakte->step8ObtainingUserDataArrayWithClientId(
                $oAuthTokenType, $vkontakte->getClientId(),
                    $this->configWebDiAuthGuzzle, $this->requestFactory);

        /**
         * @var array $userArray['user']
         */
        $user = $userArray['user'] ?? [];

        /**
         * @var int $user['user_id']
         */
        $id = $user['user_id'] ?? 0;
        if ($id <= 0) {
            $this->authService->logout();
        }
        /**
         * @var string $user['first_name']
         */
        $userFirstName = $user['first_name'] ?? 'unknown';
        /**
         * @var string $user['last_name']
         */
        $userLastName = $user['last_name'] ?? 'unknown';
        $userName = (strlen($userFirstName) > 0 && strlen($userLastName) > 0)
            ? $userFirstName . ' ' . $userLastName
            : 'fullname unknown';
        // Append the last four digits of the Id
        $login = '' . $userName
                . substr((string) $id, strlen((string) $id) - 4,
                        strlen((string) $id));
        /**
         * @var string $userArray['email']
         */
        $email = $userArray['email'] ?? 'noemail' . $login . '@vk.ru';
        $password = Random::string(32);
        // The password does not need to be validated here so use
        //  authService->oauthLogin($login) instead of
        //   authService->login($login, $password)
        // but it will be used later to build a passwordHash
        return $id <= 0
            ? $this->redirectToMain()
            : $this->oauthRegisterAndProceed(
                'vkontakte', $login, $email, $password,
                $_language, self::VKONTAKTE_ACCESS_TOKEN, $d);
    }

    public function callbackYandex(
        CallbackDeps $d,
        #[RouteArgument('_language')]
        string $_language,
        #[Query('code')]
        ?string $code = null,
        #[Query('state')]
        ?string $state = null,
    ): ResponseInterface {
        if ($code == null || $state == null) {
            return $this->redirectToOauth2AuthError(
                $d->translator->translate('oauth2.missing.authentication.code.'
                        . 'or.state.parameter'));
        }

        $this->blockInvalidState('yandex', $state);
        $yandex = (AuthChoice::widget())->getClient('yandex');

        /**
         * @psalm-suppress DocblockTypeContradiction $code
         * @psalm-suppress DocblockTypeContradiction $state
         */
        if (strlen($code) == 0 || $code == 401 || strlen($state) == 0) {
            return $this->yandexCodeGuard($yandex, $d->request, $code, $d->translator);
        }

        $codeVerifier = (string) $this->session->get('code_verifier');
        $params = [
            'grant_type' => 'authorization_code',
            'redirect_uri' => $yandex->getOauth2ReturnUrl(),
            'code_verifier' => $codeVerifier,
        ];
        /** @psalm-var \Yiisoft\Yii\AuthClient\Client\Yandex $yandex */
        $oAuthTokenType = $yandex->fetchAccessTokenWithCodeVerifier($d->request, $code, $params);
        $userArray = $yandex->getCurrentUserJsonArray(
                $oAuthTokenType, $this->configWebDiAuthGuzzle, $this->requestFactory);
        /** @var int $userArray['id'] */
        $id = $userArray['id'] ?? 0;
        if ($id <= 0) {
            $this->authService->logout();
        }
        /** @var string $userArray['login'] e.g. john.doe.com */
        $idStr = (string) $id;
        $login = 'yx' . $userArray['login'] . substr($idStr, strlen($idStr) - 4, strlen($idStr));
        $email = 'noemail' . $login . '@yandex.com';
        $password = Random::string(32);
        return $id <= 0
            ? $this->redirectToMain()
            : $this->oauthRegisterAndProceed(
                'yandex', $login, $email, $password,
                $_language, self::YANDEX_ACCESS_TOKEN, $d);
    }

    /**
     * OAuth2 providers (Google, GitHub, Microsoft, LinkedIn, Facebook etc.)
     * enforce their own MFA before issuing an authorization code. By the time
     * any callback fires, the user has already passed the provider's own
     * security checks. Applying an additional TOTP challenge is therefore
     * redundant and is skipped entirely for all OAuth2 logins.
     * TFA is only applied to the local username/password login path.
     */
public function tfaCheckBeforeRedirects(
    string $providerName,
    TokenRepository $tR,
    UserInvRepository $uiR,
): ResponseInterface {
    $identity = $this->authService->getIdentity();
    $userId = $identity->getId();

    $this->logger->log(LogLevel::DEBUG,
        'tfaCheck — provider: ' . $providerName
        . ' userId: ' . var_export($userId, true));

    if (null !== $userId) {
        $userInv = $uiR->repoUserInvUserIdquery((int) $userId);

        $this->logger->log(LogLevel::DEBUG,
            'tfaCheck — userInv found: ' . var_export($userInv !== null, true));

        if (null !== $userInv) {
            $status = $userInv->getActive();
            $isAdminUser = $this->isAdminUser($userId);

            $this->logger->log(LogLevel::DEBUG,
                'tfaCheck — status: ' . var_export($status, true)
                . ' isAdminUser: ' . var_export($isAdminUser, true));

            if ($status || $isAdminUser) {
                $this->session->regenerateId();
                $this->session->set('tfa_verified', true);

                $this->logger->log(LogLevel::DEBUG,
                    'tfaCheck — session id after regenerate: '
                    . ($this->session->getId() ?? ' null')
                    . ' tfa_verified after set: '
                    . var_export($this->session->get('tfa_verified'), true));

                $isAdminUser ? $this->disableToken($tR, $userId,
                        $providerName) : '';
                return $this->redirectToInvoiceIndex();
            }

            $this->logger->log(LogLevel::DEBUG,
                'tfaCheck — status false and not admin'
                . ' redirecting to adminMustMakeActive');
            $this->disableToken($tR, $userId,
                    $this->getTokenType($providerName));
            return $this->redirectToAdminMustMakeActive();
        }
    }

    $this->logger->log(LogLevel::DEBUG,
        'tfaCheck — fell through to redirectToMain');
    return $this->redirectToMain();
}

    private function yandexCodeGuard(
        object $yandex,
        ServerRequestInterface $request,
        string $code,
        TranslatorInterface $translator,
    ): ResponseInterface {
        if (strlen($code) == 0) {
            /** @psalm-var \Yiisoft\Yii\AuthClient\Client\Yandex $yandex */
            $codeVerifier = Random::string(128);
            $codeChallenge = strtr(
                rtrim(base64_encode(hash('sha256', $codeVerifier, true)), '='),
                '+/', '-_'
            );
            $this->session->set('code_verifier', $codeVerifier);
            return $this->webService->getRedirectResponse(
                $yandex->buildAuthUrl($request, [
                    'code_challenge' => $codeChallenge,
                    'code_challenge_method' => 'S256',
                ])
            );
        }
        return $code == 401
            ? $this->redirectToOauth2CallbackResultUnAuthorised()
            : $this->redirectToOauth2AuthError(
                $translator->translate(
                    'oauth2.missing.state.parameter.possible.csrf.attack'));
    }

    private function assignRoleAndVerify(
        int $userId,
        string $role,
    ): bool {
        $this->manager->revokeAll((string) $userId);
        $this->manager->assign($role, (string) $userId);

        $roles = $this->manager->getRolesByUserId((string) $userId);
        if (empty($roles)) {
            $this->logger->log(
                LogLevel::ERROR,
                'RBAC assignment failed to persist for userId: ' . (string) $userId
                    . ' role: ' . $role
                    . ' — check yii_rbac_assignment table'
            );
            return false;
        }
        return true;
    }

    private function oauthRegisterAndProceed(
        string $provider,
        string $login,
        string $email,
        string $password,
        string $_language,
        string $tokenConst,
        CallbackDeps $d,
    ): ResponseInterface {
        if ($this->authService->oauthLogin($login)) {
            return $this->tfaCheckBeforeRedirects($provider, $d->tR, $d->uiR);
        }
        return $this->registerNewOauthUser($provider, $login, $email, $password, $_language, $tokenConst, $d);
    }

    private function registerNewOauthUser(
        string $provider,
        string $login,
        string $email,
        string $password,
        string $_language,
        string $tokenConst,
        CallbackDeps $d,
    ): ResponseInterface {
        $oauthUser = new User($login, $email, $password);
        $d->uR->save($oauthUser);
        $userId = $oauthUser->reqId();
        if ($userId <= 0) {
            $this->authService->logout();
            return $this->redirectToMain();
        }
        $role = $d->uR->repoCount() == 1 ? 'admin' : 'observer';
        if (!$this->assignRoleAndVerify($userId, $role)) {
            return $this->redirectToMain();
        }
        /**
         * @var array $this->sR->localeLanguageArray()
         */
        $languageArray = $this->sR->localeLanguageArray();
        /**
         * @var string $language
         */
        $language = $languageArray[$_language];
        $randomAndTimeToken = $this->getAccessToken($oauthUser, $d->tR, $tokenConst);
        $proceedToMenuButton =
            $this->proceedToMenuButtonWithMaskedRandomAndTimeTokenLink(
                $d->translator, $oauthUser, $d->uiR, $language, $_language,
                    $randomAndTimeToken, $provider);
        return $this->webViewRenderer->render('proceed', [
            'proceedToMenuButton' => $proceedToMenuButton,
        ]);
    }

    private function redirectToOauth2AuthError(string $message): ResponseInterface
    {
        return $this->webService->getRedirectResponse('site/oauth2autherror', [
            'message' => $message,
        ]);
    }

    private function redirectToUserCancelledOauth2(): ResponseInterface
    {
        return $this->webService->getRedirectResponse('site/usercancelledoauth2',
                ['_language' => 'en']);
    }

    private function redirectToOauth2CallbackResultUnAuthorised(): ResponseInterface
    {
        return $this->webService->getRedirectResponse(
            'site/oauth2callbackresultunauthorised', ['_language' => 'en']);
    }
}
