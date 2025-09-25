<?php

declare(strict_types=1);

namespace App\Auth\Trait;

use App\Auth\TokenRepository;
use App\Invoice\UserInv\UserInvRepository;
use App\User\User;
use App\User\UserRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Input\Http\Attribute\Parameter\Query;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Security\Random;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\AuthClient\Widget\AuthChoice;

trait Callback
{
    public function callbackDeveloperGovSandboxHmrc(
        ServerRequestInterface $request,
        TranslatorInterface $translator,
        TokenRepository $tR,
        UserInvRepository $uiR,
        UserRepository $uR,
        #[RouteArgument('_language')]
        string $_language,
        #[Query('code')]
        string $code = null,
        #[Query('state')]
        string $state = null,
    ): ResponseInterface {
        if ($code == null || $state == null) {
            return $this->redirectToOauth2AuthError($translator->translate('oauth2.missing.authentication.code.or.state.parameter'));
        }

        $this->blockInvalidState('developergovsandboxhmrc', $state);
        $developerSandboxHmrc = (AuthChoice::widget())->getClient('developersandboxhmrc');
        /**
         * @psalm-suppress DocblockTypeContradiction $code
         */
        if (strlen($code) == 0) {
            $authorizationUrl = $developerSandboxHmrc->buildAuthUrl($request, []);
            return $this->webService->getRedirectResponse($authorizationUrl);
        }

        if ($code == 401) {
            return $this->redirectToOauth2CallbackResultUnAuthorised();
        }

        /**
         * @psalm-suppress DocblockTypeContradiction $state
         */
        if (strlen($state) == 0) {
            /**
             * State is invalid, possible cross-site request forgery.
             */
            return $this->redirectToOauth2AuthError($translator->translate('oauth2.missing.state.parameter.possible.csrf.attack'));
            // code and state are both present
        }

        $codeVerifier = (string) $this->session->get('code_verifier');
        /**
         * @see https://developer.service.hmrc.gov.uk/api-documentation/docs/authorisation
         * For user-restricted access, the 'Authorization Code' Grant Type is used
         * Use the code received, to get an access_token
         */
        $oAuthToken = $developerSandboxHmrc->fetchAccessTokenWithCodeVerifier($request, $code, $params = [
            'redirect_uri' => $developerSandboxHmrc->getOauth2ReturnUrl(),
            'code_verifier' => $codeVerifier,
            'grant_type' => 'authorization_code',
        ]);

        // e.g. string '476425f97e53ca1124161e491bee384e'
        $this->session->set('hmrc_access_token', $oAuthToken->getParam('access_token'));
        // e.g. string 'bearer'
        $this->session->set('hmrc_token_type', $oAuthToken->getParam('token_type'));
        // default 'expires_in' int 14400
        $this->session->set('hmrc_token_expires', time() + (int) $oAuthToken->getParam('expires_in'));
        // e.g. read:self-assessment write:self-assessment'
        $this->session->set('hmrc_scope', $oAuthToken->getParam('scope'));
        // e.g. string 'cbe7c4f01a6bc55034237718d3e4ded2'
        $this->session->set('hmrc_refresh_token', $oAuthToken->getParam('refresh_token'));

        if ($this->sR->getEnv() == 'dev') {
            /**
             * @see Yiisoft\Yii\AuthClient\Client\DeveloperSandboxHmrc function getTestUserArray;
             */
            $requestBody = [
                'serviceNames' => ['national-insurance'],
            ];
            /** @psalm-var \App\Auth\Client\DeveloperSandboxHmrc $developerSandboxHmrc */
            $userArray = $developerSandboxHmrc->createTestUserIndividual($oAuthToken, $requestBody);
        } else {
            return $this->redirectToOauth2AuthError($translator->translate('oauth2.test.user.creation.not.allowed.prod'));
        }

        /**
         * @var int $userArray['userId']
         */
        $hmrcId = $userArray['userId'] ?? 0;

        if ($hmrcId > 0) {
            // the id will be removed in the logout button
            $login = 'hmrc' . (string) $hmrcId;
            /**
             * Depending on the environment i.e. prod or dev, getApiBaseUrl1() will vary between 'https://api.service.hmrc.gov.uk' or 'https://test-api.service.hmrc.gov.uk' respectively
             *
             * @var string $userArray['emailAddress']
             */
            $email = $userArray['emailAddress'] ?? 'noemail' . $login . '@' . str_replace('https://', '', $developerSandboxHmrc->getApiBaseUrl1());
            $password = Random::string(32);
            if ($this->authService->oauthLogin($login)) {
                return $this->tfaCheckBeforeRedirects('developersandboxhmrc', $tR, $uiR);
            }
            $user = new User($login, $email, $password);
            $uR->save($user);
            $userId = $user->getId();
            if ($userId > 0) {
                // avoid autoincrement issues and using predefined user id of 1 ... and assign the first signed-up user ... admin rights
                if ($uR->repoCount() == 1) {
                    $this->manager->revokeAll($userId);
                    $this->manager->assign('admin', $userId);
                } else {
                    $this->manager->revokeAll($userId);
                    $this->manager->assign('observer', $userId);
                }
                $login = $user->getLogin();
                /**
                 * @var array $this->sR->locale_language_array()
                 */
                $languageArray = $this->sR->locale_language_array();
                /**
                 * @see Trait\Oauth2 function getAccessToken
                 * @var array $languageArray
                 * @var string $language
                 */
                $language = $languageArray[$_language];
                $randomAndTimeToken = $this->getAccessToken($user, $tR, self::DEVELOPER_SANDBOX_HMRC_ACCESS_TOKEN);
                /**
                 * @see A new UserInv (extension table of user) for the user is created.
                 */
                $proceedToMenuButton = $this->proceedToMenuButtonWithMaskedRandomAndTimeTokenLink($translator, $user, $uiR, $language, $_language, $randomAndTimeToken, 'developersandboxhmrc');
                return $this->viewRenderer->render('proceed', [
                    'proceedToMenuButton' => $proceedToMenuButton,
                ]);
            }
        }

        $this->authService->logout();
        return $this->redirectToMain();
    }

    /**
     * Purpose: Once Facebook redirects to this callback, in this callback function:
     * 1. the user is logged in, or a new user is created, and the proceedToMenuButton is created
     * 2. clicking on the proceedToMenuButton will further create a userinv extension of the user table
     * @see src/Invoice/UserInv/UserInvController function facebook
     * @param ServerRequestInterface $request
     * @param TranslatorInterface $translator
     * @param TokenRepository $tR
     * @param UserInvRepository $uiR
     * @param UserRepository $uR
     * @param string $_language
     * @param string $code
     * @param string $state
     * @param string $error
     * @param string $errorCode
     * @param string $errorReason
     * @return ResponseInterface
     */
    public function callbackFacebook(
        ServerRequestInterface $request,
        TranslatorInterface $translator,
        TokenRepository $tR,
        UserInvRepository $uiR,
        UserRepository $uR,
        #[RouteArgument('_language')]
        string $_language,
        #[Query('code')]
        string $code = null,
        #[Query('state')]
        string $state = null,
        #[Query('error')]
        string $error = null,
        #[Query('error_code')]
        string $errorCode = null,
        #[Query('error_reason')]
        string $errorReason = null,
    ): ResponseInterface {
        // Avoid MissingRequiredArgumentException
        if ($code == null || $state == null) {
            // e.g. User presses cancel button: callbackFacebook?error=access_denied&error_code=200&error_description=Permissions+error&error_reason=user_denied&state=
            if (($errorCode == 200) && ($error == 'access_denied') && ($errorReason == 'user_denied')) {
                return $this->redirectToUserCancelledOauth2();
            }
            return $this->redirectToOauth2AuthError($translator->translate('oauth2.missing.authentication.code.or.state.parameter'));
        }

        $this->blockInvalidState('facebook', $state);
        $facebook = (AuthChoice::widget())->getClient('facebook');

        /**
         * @psalm-suppress DocblockTypeContradiction $code
         */
        if (strlen($code) == 0) {
            // If we don't have an authorization code then get one
            // and use the protected function oauth2->generateAuthState to generate state param
            // which has a session id built into it
            $authorizationUrl = $facebook->buildAuthUrl($request, []);
            return $this->webService->getRedirectResponse($authorizationUrl);
        }

        if ($code == 401) {
            return $this->redirectToOauth2CallbackResultUnAuthorised();
        }
        /**
         * @psalm-suppress DocblockTypeContradiction $state
         */
        if (strlen($state) == 0) {
            /**
             * State is invalid, possible cross-site request forgery.
             */
            return $this->redirectToOauth2AuthError($translator->translate('oauth2.missing.state.parameter.possible.csrf.attack'));
            // code and state are both present
        }
        /** @psalm-var \Yiisoft\Yii\AuthClient\Client\Facebook $facebook */
        $oAuthTokenType = $facebook->fetchAccessToken($request, $code, $params = []);
        /**
         * @var array $userArray
         */
        $userArray = $facebook->getCurrentUserJsonArray($oAuthTokenType);
        /**
         * @var int $userArray['id']
         */
        $facebookId = $userArray['id'] ?? 0;
        if ($facebookId > 0) {
            /**
             * @var string $userArray['name']
             */
            $facebookLogin = strtolower($userArray['name'] ?? '');
            if (strlen($facebookLogin) > 0) {
                // the id will be removed in the logout button
                $login = 'facebook' . (string) $facebookId . $facebookLogin;
                /**
                 * @var string $userArray['email']
                 */
                $email = $userArray['email'] ?? 'noemail' . $login . '@facebook.com';
                $password = Random::string(32);
                // The password does not need to be validated here so use authService->oauthLogin($login) instead of authService->login($login, $password)
                // but it will be used later to build a passwordHash
                if ($this->authService->oauthLogin($login)) {
                    return $this->tfaCheckBeforeRedirects('facebook', $tR, $uiR);
                }
                $user = new User($login, $email, $password);
                $uR->save($user);
                $userId = $user->getId();
                if ($userId > 0) {
                    if ($uR->repoCount() == 1) {
                        $this->manager->revokeAll($userId);
                        $this->manager->assign('admin', $userId);
                    } else {
                        $this->manager->revokeAll($userId);
                        $this->manager->assign('observer', $userId);
                    }
                    $login = $user->getLogin();
                    /**
                     * @var array $this->sR->locale_language_array()
                     */
                    $languageArray = $this->sR->locale_language_array();
                    /**
                     * @see Trait\Oauth2 function getFacebookAccessToken
                     * @var array $languageArray
                     * @var string $language
                     */
                    $language = $languageArray[$_language];
                    $randomAndTimeToken = $this->getAccessToken($user, $tR, self::FACEBOOK_ACCESS_TOKEN);
                    /**
                     * @see A new UserInv (extension table of user) for the user is created.
                     */
                    $proceedToMenuButton = $this->proceedToMenuButtonWithMaskedRandomAndTimeTokenLink($translator, $user, $uiR, $language, $_language, $randomAndTimeToken, 'facebook');
                    return $this->viewRenderer->render('proceed', [
                        'proceedToMenuButton' => $proceedToMenuButton,
                    ]);
                }
            }
        }

        $this->authService->logout();
        return $this->redirectToMain();
    }

    /**
     * Purpose: Once Github redirects to this callback, in this callback function:
     * 1. the user is logged in, or a new user is created, and the proceedToMenuButton is created
     * 2. clicking on the proceedToMenuButton will further create a userinv extension of the user table
     * @see src/Invoice/UserInv/UserInvController function github
     * @see https://docs.github.com/en/apps/oauth-apps/building-oauth-apps/authorizing-oauth-apps
     * @param ServerRequestInterface $request
     * @param TranslatorInterface $translator
     * @param TokenRepository $tR
     * @param UserInvRepository $uiR
     * @param UserRepository $uR
     * @param string $_language
     * @param string $code
     * @param string $state
     * @return ResponseInterface
     */
    public function callbackGithub(
        ServerRequestInterface $request,
        TranslatorInterface $translator,
        TokenRepository $tR,
        UserInvRepository $uiR,
        UserRepository $uR,
        #[RouteArgument('_language')]
        string $_language,
        #[Query('code')]
        string $code = null,
        #[Query('state')]
        string $state = null,
    ): ResponseInterface {
        if ($code == null || $state == null) {
            return $this->redirectToOauth2AuthError($translator->translate('oauth2.missing.authentication.code.or.state.parameter'));
        }

        $this->blockInvalidState('github', $state);
        $github = (AuthChoice::widget())->getClient('github');
        /**
         * @psalm-suppress DocblockTypeContradiction $code
         */
        if (strlen($code) == 0) {
            // If we don't have an authorization code then get one
            // and use the protected function oauth2->generateAuthState to generate state param 'authState'
            // which has a session id built into it

            $authorizationUrl = $github->buildAuthUrl($request, []);
            return $this->webService->getRedirectResponse($authorizationUrl);
        }

        if ($code == 401) {
            return $this->redirectToOauth2CallbackResultUnAuthorised();
        }

        /**
         * @psalm-suppress DocblockTypeContradiction $state
         */
        if (strlen($state) == 0) {
            /**
             * State is invalid, possible cross-site request forgery.
             */
            return $this->redirectToOauth2AuthError($translator->translate('oauth2.missing.state.parameter.possible.csrf.attack'));
            // code and state are both present
        }
        // Try to get an access token (using the 'authorization code' grant)
        // The 'request_uri' is included by default in $defaultParams[] and therefore not needed in $params
        // The $response->getBody()->getContents() for each Client e.g. Github will be parsed and loaded into an OAuthToken Type
        // For Github we know that the parameter key for the token is 'access_token' and not the default 'oauth_token'
        /** @psalm-var \Yiisoft\Yii\AuthClient\Client\GitHub $github */
        $oAuthTokenType = $github->fetchAccessToken($request, $code, $params = []);
        /**
         * Every time you receive an access token, you should use the token to revalidate the user's identity.
         * A user can change which account they are signed into when you send them to authorize your app,
         * and you risk mixing user data if you do not validate the user's identity after every sign in.
         * @see https://docs.github.com/en/apps/oauth-apps/building-oauth-apps/authorizing-oauth-apps#3-use-the-access-token-to-access-the-api
         */
        $userArray = $github->getCurrentUserJsonArray($oAuthTokenType);
        /**
         * @var int $userArray['id']
         */
        $githubId = $userArray['id'] ?? 0;
        if ($githubId > 0) {
            $githubLogin = 'g';
            if (strlen($githubLogin) > 0) {
                // Append github in case user has used same login for other identity providers
                // the id will be removed in the logout button
                $login = 'github' . (string) $githubId . $githubLogin;
                /**
                 * @var string $userArray['email']
                 */
                $email = $userArray['email'] ?? 'noemail' . $login . '@github.com';
                $password = Random::string(32);
                // The password does not need to be validated here so use authService->oauthLogin($login) instead of authService->login($login, $password)
                // but it will be used later to build a passwordHash
                if ($this->authService->oauthLogin($login)) {
                    return $this->tfaCheckBeforeRedirects('github', $tR, $uiR);
                }
                $user = new User($login, $email, $password);
                $uR->save($user);
                $userId = $user->getId();
                if ($userId > 0) {
                    // avoid autoincrement issues and using predefined user id of 1 ... and assign the first signed-up user ... admin rights
                    if ($uR->repoCount() == 1) {
                        $this->manager->revokeAll($userId);
                        $this->manager->assign('admin', $userId);
                    } else {
                        $this->manager->revokeAll($userId);
                        $this->manager->assign('observer', $userId);
                    }
                    $login = $user->getLogin();
                    /**
                     * @var array $this->sR->locale_language_array()
                     */
                    $languageArray = $this->sR->locale_language_array();
                    /**
                     * @see Trait\Oauth2 function getAccessToken
                     * @var array $languageArray
                     * @var string $language
                     */
                    $language = $languageArray[$_language];
                    $randomAndTimeToken = $this->getAccessToken($user, $tR, self::GITHUB_ACCESS_TOKEN);
                    /**
                     * @see A new UserInv (extension table of user) for the user is created.
                     */
                    $proceedToMenuButton = $this->proceedToMenuButtonWithMaskedRandomAndTimeTokenLink($translator, $user, $uiR, $language, $_language, $randomAndTimeToken, 'github');
                    return $this->viewRenderer->render('proceed', [
                        'proceedToMenuButton' => $proceedToMenuButton,
                    ]);
                }
            }
        }

        $this->authService->logout();
        return $this->redirectToMain();
    }

    /**
     * @see https://console.cloud.google.com/apis/credentials?project=YOUR_PROJECT
     */
    public function callbackGoogle(
        ServerRequestInterface $request,
        TranslatorInterface $translator,
        TokenRepository $tR,
        UserInvRepository $uiR,
        UserRepository $uR,
        #[RouteArgument('_language')]
        string $_language,
        #[Query('code')]
        string $code = null,
        #[Query('state')]
        string $state = null,
    ): ResponseInterface {
        if ($code == null || $state == null) {
            return $this->redirectToOauth2AuthError($translator->translate('oauth2.missing.authentication.code.or.state.parameter'));
        }

        $this->blockInvalidState('google', $state);
        $google = (AuthChoice::widget())->getClient('google');
        /**
         * @psalm-suppress DocblockTypeContradiction $code
         */
        if (strlen($code) == 0) {
            $authorizationUrl = $google->buildAuthUrl($request, []);
            return $this->webService->getRedirectResponse($authorizationUrl);
        }

        if ($code == 401) {
            return $this->redirectToOauth2CallbackResultUnAuthorised();
        }

        /**
         * @psalm-suppress DocblockTypeContradiction $state
         */
        if (strlen($state) == 0) {
            /**
             * State is invalid, possible cross-site request forgery.
             */
            return $this->redirectToOauth2AuthError($translator->translate('oauth2.missing.state.parameter.possible.csrf.attack'));
            // code and state are both present
        }
        /** @psalm-var \Yiisoft\Yii\AuthClient\Client\Google $google */
        $oAuthTokenType = $google->fetchAccessToken($request, $code, $params = [
            'grant_type' => 'authorization_code',
        ]);

        /**
         * @var array $userArray
         */
        $userArray = $google->getCurrentUserJsonArray($oAuthTokenType);

        /**
         * @var int $userArray['id']
         */
        $googleId = $userArray['id'] ?? 0;

        /**
         * VarDumper::dump($userArray) produces normally
         *
         * 'id' =>  google will produce an id here
         * 'email' => this is the email associated with google
         * 'verified_email' => true
         * 'name' => this is your name in lower case letters
         * 'given_name' => this is your first name
         * 'family_name' => this is your surname
         * 'picture' => 'https://lh3.googleusercontent.com/a/ACg8ocZiJZ8a-fpCKx-H4Dh8k-upEqQV3jSyQGH02--kLP_xZWQqrg=s96-c'
         */

        if ($googleId > 0) {
            // the id will be removed in the logout button
            $login = 'google' . (string) $googleId;
            /**
             * @var string $userArray['email']
             */
            $email = $userArray['email'] ?? 'noemail' . $login . '@google.com';
            $password = Random::string(32);
            if ($this->authService->oauthLogin($login)) {
                return $this->tfaCheckBeforeRedirects('google', $tR, $uiR);
            }
            $user = new User($login, $email, $password);
            $uR->save($user);
            $userId = $user->getId();
            if ($userId > 0) {
                // avoid autoincrement issues and using predefined user id of 1 ... and assign the first signed-up user ... admin rights
                if ($uR->repoCount() == 1) {
                    $this->manager->revokeAll($userId);
                    $this->manager->assign('admin', $userId);
                } else {
                    $this->manager->revokeAll($userId);
                    $this->manager->assign('observer', $userId);
                }
                $login = $user->getLogin();
                /**
                 * @var array $this->sR->locale_language_array()
                 */
                $languageArray = $this->sR->locale_language_array();
                /**
                 * @see Trait\Oauth2 function getGoogleAccessToken
                 * @var array $languageArray
                 * @var string $language
                 */
                $language = $languageArray[$_language];
                $randomAndTimeToken = $this->getAccessToken($user, $tR, self::GOOGLE_ACCESS_TOKEN);
                /**
                 * @see A new UserInv (extension table of user) for the user is created.
                 */
                $proceedToMenuButton = $this->proceedToMenuButtonWithMaskedRandomAndTimeTokenLink($translator, $user, $uiR, $language, $_language, $randomAndTimeToken, 'google');
                return $this->viewRenderer->render('proceed', [
                    'proceedToMenuButton' => $proceedToMenuButton,
                ]);
            }
        }

        $this->authService->logout();
        return $this->redirectToMain();
    }

    public function callbackGovUk(
        ServerRequestInterface $request,
        TranslatorInterface $translator,
        TokenRepository $tR,
        UserInvRepository $uiR,
        UserRepository $uR,
        #[RouteArgument('_language')]
        string $_language,
        #[Query('code')]
        string $code = null,
        #[Query('state')]
        string $state = null,
    ): ResponseInterface {
        if ($code == null || $state == null) {
            return $this->redirectToOauth2AuthError($translator->translate('oauth2.missing.authentication.code.or.state.parameter'));
        }

        $govUk = (AuthChoice::widget())->getClient('govuk');

        $this->blockInvalidState('govUk', $state);

        /**
         * @psalm-suppress DocblockTypeContradiction $code
         */
        if (strlen($code) == 0) {
            $authorizationUrl = $govUk->buildAuthUrl($request, []);
            return $this->webService->getRedirectResponse($authorizationUrl);
        }

        if ($code == 401) {
            return $this->redirectToOauth2CallbackResultUnAuthorised();
        }

        /**
         * @psalm-suppress DocblockTypeContradiction $state
         */
        if (strlen($state) == 0) {
            /**
             * State is invalid, possible cross-site request forgery.
             */
            return $this->redirectToOauth2AuthError($translator->translate('oauth2.missing.state.parameter.possible.csrf.attack'));
            // code and state are both present
        }
        /** @psalm-var \App\Auth\Client\GovUk $govUk */
        $oAuthTokenType = $govUk->fetchAccessToken($request, $code, $params = []);
        /**
         * @var array $userArray
         */
        $userArray = $govUk->getCurrentUserJsonArray($oAuthTokenType);
        /**
         * @var int $userArray['id']
         */
        $govUkId = $userArray['id'] ?? 0;
        if ($govUkId > 0) {
            // the id will be removed in the logout button
            $login = 'govuk' . (string) $govUkId;
            /**
             * @var string $userArray['email']
             */
            $email = $userArray['email'] ?? 'noemail' . $login . '@gov.uk';
            $password = Random::string(32);
            if ($this->authService->oauthLogin($login)) {
                return $this->tfaCheckBeforeRedirects('govuk', $tR, $uiR);
            }
            $user = new User($login, $email, $password);
            $uR->save($user);
            $userId = $user->getId();
            if ($userId > 0) {
                // avoid autoincrement issues and using predefined user id of 1 ... and assign the first signed-up user ... admin rights
                if ($uR->repoCount() == 1) {
                    $this->manager->revokeAll($userId);
                    $this->manager->assign('admin', $userId);
                } else {
                    $this->manager->revokeAll($userId);
                    $this->manager->assign('observer', $userId);
                }
                $login = $user->getLogin();
                /**
                 * @var array $this->sR->locale_language_array()
                 */
                $languageArray = $this->sR->locale_language_array();
                /**
                 * @see Trait\Oauth2 function getAccessToken
                 * @var array $languageArray
                 * @var string $language
                 */
                $language = $languageArray[$_language];
                $randomAndTimeToken = $this->getAccessToken($user, $tR, self::GOVUK_ACCESS_TOKEN);
                /**
                 * @see A new UserInv (extension table of user) for the user is created.
                 */
                $proceedToMenuButton = $this->proceedToMenuButtonWithMaskedRandomAndTimeTokenLink($translator, $user, $uiR, $language, $_language, $randomAndTimeToken, 'govuk');
                return $this->viewRenderer->render('proceed', [
                    'proceedToMenuButton' => $proceedToMenuButton,
                ]);
            }
        }

        $this->authService->logout();
        return $this->redirectToMain();
    }

    public function callbackLinkedIn(
        ServerRequestInterface $request,
        TranslatorInterface $translator,
        TokenRepository $tR,
        UserInvRepository $uiR,
        UserRepository $uR,
        #[RouteArgument('_language')]
        string $_language,
        #[Query('code')]
        string $code = null,
        #[Query('state')]
        string $state = null,
    ): ResponseInterface {
        if ($code == null || $state == null) {
            return $this->redirectToOauth2AuthError($translator->translate('oauth2.missing.authentication.code.or.state.parameter'));
        }

        $this->blockInvalidState('linkedIn', $state);
        $linkedIn = (AuthChoice::widget())->getClient('linkedin');
        /**
         * @psalm-suppress DocblockTypeContradiction $code
         */
        if (strlen($code) == 0) {
            $authorizationUrl = $linkedIn->buildAuthUrl($request, []);
            return $this->webService->getRedirectResponse($authorizationUrl);
        }

        if ($code == 401) {
            return $this->redirectToOauth2CallbackResultUnAuthorised();
        }

        /**
         * @psalm-suppress DocblockTypeContradiction $state
         */
        if (strlen($state) == 0) {
            /**
             * State is invalid, possible cross-site request forgery.
             */
            return $this->redirectToOauth2AuthError($translator->translate('oauth2.missing.state.parameter.possible.csrf.attack'));
            // code and state are both present
        }
        $params = [
            'grant_type' => 'authorization_code',
            'redirect_uri' => $linkedIn->getOauth2ReturnUrl(),
        ];
        /** @psalm-var \Yiisoft\Yii\AuthClient\Client\LinkedIn $linkedIn */
        $oAuthTokenType = $linkedIn->fetchAccessToken($request, $code, $params);
        /**
         * @var array $userArray
         */
        $userArray = $linkedIn->getCurrentUserJsonArray($oAuthTokenType, $this->configWebDiAuthGuzzle, $this->requestFactory);
        /**
         * eg. [
         *      'sub' => 'P1c9jkRFSy',
         *      'email_verified' => true,
         *      'name' => 'Joe Bloggs',
         *      'locale' => ['country' => 'UK', 'language' => 'en'],
         *      'given_name' => 'Joe',
         *      'family_name' => 'Bloggs',
         *      'email' => 'joe.bloggs@website.com'
         *      ]
         *
         * @var string $userArray['sub'] e.g. P1c9jkRFSy   ... A sub string is returned instead of an id
         */
        $linkedInSub = $userArray['sub'] ?? '';
        if (strlen($linkedInSub) > 0) {
            /**
             * @var string $userArray['name']
             */
            $linkedInName = $userArray['name'] ?? 'unknown';
            $login = 'linkedIn' . $linkedInName;
            /**
             * @var string $userArray['email']
             */
            $email = $userArray['email'] ?? 'noemail' . $login . '@linkedin.com';
            $password = Random::string(32);
            if ($this->authService->oauthLogin($login)) {
                return $this->tfaCheckBeforeRedirects('linkedin', $tR, $uiR);
            }
            $user = new User($login, $email, $password);
            $uR->save($user);
            $userId = $user->getId();
            if ($userId > 0) {
                // avoid autoincrement issues and using predefined user id of 1 ... and assign the first signed-up user ... admin rights
                if ($uR->repoCount() == 1) {
                    $this->manager->revokeAll($userId);
                    $this->manager->assign('admin', $userId);
                } else {
                    $this->manager->revokeAll($userId);
                    $this->manager->assign('observer', $userId);
                }
                $login = $user->getLogin();
                /**
                 * @var array $this->sR->locale_language_array()
                 */
                $languageArray = $this->sR->locale_language_array();
                /**
                 * @see Trait\Oauth2 function getLinkedInAccessToken
                 * @var array $languageArray
                 * @var string $language
                 */
                $language = $languageArray[$_language];
                $randomAndTimeToken = $this->getAccessToken($user, $tR, self::LINKEDIN_ACCESS_TOKEN);
                /**
                 * @see A new UserInv (extension table of user) for the user is created.
                 */
                $proceedToMenuButton = $this->proceedToMenuButtonWithMaskedRandomAndTimeTokenLink($translator, $user, $uiR, $language, $_language, $randomAndTimeToken, 'linkedin');
                return $this->viewRenderer->render('proceed', [
                    'proceedToMenuButton' => $proceedToMenuButton,
                ]);
            }
        }

        $this->authService->logout();
        return $this->redirectToMain();
    }

    public function callbackMicrosoftOnline(
        ServerRequestInterface $request,
        TranslatorInterface $translator,
        TokenRepository $tR,
        UserInvRepository $uiR,
        UserRepository $uR,
        #[RouteArgument('_language')]
        string $_language,
        #[Query('code')]
        string $code = null,
        #[Query('state')]
        string $state = null,
        #[Query('session_state')]
        string $sessionState = null,
    ): ResponseInterface {
        if ($code == null || $state == null || $sessionState == null) {
            return $this->redirectToOauth2AuthError($translator->translate('oauth2.missing.authentication.code.or.state.parameter'));
        }

        $this->blockInvalidState('microsoftonline', $state);
        $microsoftOnline = (AuthChoice::widget())->getClient('microsoftonline');

        /**
         * @psalm-suppress DocblockTypeContradiction $code
         */
        if (strlen($code) == 0) {
            $authorizationUrl = $microsoftOnline->buildAuthUrl($request, $params = ['redirect_uri' => 'https://yii3i.co.uk/callbackMicrosoftOnline']);
            return $this->webService->getRedirectResponse($authorizationUrl);
        }

        /**
         * @psalm-suppress DocblockTypeContradiction $state
         */
        if ($code == '401') {
            return $this->redirectToOauth2CallbackResultUnAuthorised();
        }

        /**
         * @psalm-suppress DocblockTypeContradiction $state
         * @psalm-suppress DocblockTypeContradiction $sessionState
         */
        if (strlen($state) == 0 || strlen($sessionState) == 0) {
            /**
             * State is invalid, possible cross-site request forgery.
             */
            return $this->redirectToOauth2AuthError($translator->translate('oauth2.missing.state.parameter.possible.csrf.attack'));
            // code and state and stateSession are both present
        }
        /** @psalm-var \Yiisoft\Yii\AuthClient\Client\MicrosoftOnline $microsoftOnline */
        $oAuthTokenType = $microsoftOnline->fetchAccessToken($request, $code, $params = [
            'grant_type' => 'authorization_code',
            'redirect_uri' => 'https://yii3i.co.uk/callbackMicrosoftOnline',
        ]);
        /**
         * @var array $userArray
         */
        $userArray = $microsoftOnline->getCurrentUserJsonArray($oAuthTokenType, $this->configWebDiAuthGuzzle, $this->requestFactory);
        /**
         * @var int $userArray['id']
         */
        $microsoftOnlineId = $userArray['id'] ?? 0;
        if ($microsoftOnlineId > 0) {
            // Append the last four digits of the Id
            $login = 'ms' . substr((string) $microsoftOnlineId, strlen((string) $microsoftOnlineId) - 4, strlen((string) $microsoftOnlineId));
            /**
             * @var string $userArray['email']
             */
            $email = $userArray['email'] ?? 'noemail' . $login . '@microsoftonline.com';
            $password = Random::string(32);
            if ($this->authService->oauthLogin($login)) {
                return $this->tfaCheckBeforeRedirects('microsoftonline', $tR, $uiR);
            }
            $user = new User($login, $email, $password);
            $uR->save($user);
            $userId = $user->getId();
            if ($userId > 0) {
                // avoid autoincrement issues and using predefined user id of 1 ... and assign the first signed-up user ... admin rights
                if ($uR->repoCount() == 1) {
                    $this->manager->revokeAll($userId);
                    $this->manager->assign('admin', $userId);
                } else {
                    $this->manager->revokeAll($userId);
                    $this->manager->assign('observer', $userId);
                }
                $login = $user->getLogin();
                /**
                 * @var array $this->sR->locale_language_array()
                 */
                $languageArray = $this->sR->locale_language_array();
                /**
                 * @see Trait\Oauth2 function getMicrosoftOnlineAccessToken
                 * @var array $languageArray
                 * @var string $language
                 */
                $language = $languageArray[$_language];
                $randomAndTimeToken = $this->getAccessToken($user, $tR, self::MICROSOFTONLINE_ACCESS_TOKEN);
                /**
                 * @see A new UserInv (extension table of user) for the user is created.
                 */
                $proceedToMenuButton = $this->proceedToMenuButtonWithMaskedRandomAndTimeTokenLink($translator, $user, $uiR, $language, $_language, $randomAndTimeToken, 'microsoftonline');
                return $this->viewRenderer->render('proceed', [
                    'proceedToMenuButton' => $proceedToMenuButton,
                ]);
            }
        }

        $this->authService->logout();
        return $this->redirectToMain();
    }

    // Untested
    public function callbackOpenBanking(
        ServerRequestInterface $request,
        TranslatorInterface $translator,
        TokenRepository $tR,
        UserInvRepository $uiR,
        UserRepository $uR,
        #[RouteArgument('_language')]
        string $_language,
        #[Query('code')]
        string $code = null,
        #[Query('state')]
        string $state = null,
    ): ResponseInterface {
        if ($code === null || $state === null) {
            return $this->redirectToOauth2AuthError($translator->translate('oauth2.missing.authentication.code.or.state.parameter'));
        }

        $this->blockInvalidState('openbanking', $state);
        $openBanking = (AuthChoice::widget())->getClient('openbanking');

        if (strlen($code) === 0) {
            $authorizationUrl = $openBanking->buildAuthUrl($request, []);
            return $this->webService->getRedirectResponse($authorizationUrl);
        }

        if ($code == 401) {
            return $this->redirectToOauth2CallbackResultUnAuthorised();
        }

        if (strlen($state) === 0) {
            return $this->redirectToOauth2AuthError($translator->translate('oauth2.missing.state.parameter.possible.csrf.attack'));
        }

        $codeVerifier = (string) $this->session->get('code_verifier');

        // Exchange code for token with PKCE
        $oAuthToken = $openBanking->fetchAccessTokenWithCodeVerifier($request, $code, [
            'redirect_uri' => $openBanking->getOauth2ReturnUrl(),
            'code_verifier' => $codeVerifier,
            'grant_type' => 'authorization_code',
        ]);

        // Save tokens and claims as appropriate (these keys are your choice)
        $this->session->set('openbanking_access_token', $oAuthToken->getParam('access_token'));
        $this->session->set('openbanking_refresh_token', $oAuthToken->getParam('refresh_token'));
        $this->session->set('openbanking_id_token', $oAuthToken->getParam('id_token'));
        $this->session->set('openbanking_token_type', $oAuthToken->getParam('token_type'));
        $this->session->set('openbanking_token_expires', time() + (int) $oAuthToken->getParam('expires_in'));
        $this->session->set('openbanking_scope', $oAuthToken->getParam('scope'));

        // Optionally: store user claims from id_token if using OpenID Connect
        if ($oAuthToken->getParam('id_token')) {
            $this->session->set('openbanking_claims', $oAuthToken->getParam('id_token_payload') ?? []);
        }

        // Continue to app-specific logic (e.g., redirect to dashboard)
        return $this->redirectToInvoiceIndex();
    }

    public function callbackX(
        ServerRequestInterface $request,
        TranslatorInterface $translator,
        TokenRepository $tR,
        UserInvRepository $uiR,
        UserRepository $uR,
        #[RouteArgument('_language')]
        string $_language,
        #[Query('code')]
        string $code = null,
        #[Query('state')]
        string $state = null,
    ): ResponseInterface {
        if ($code == null || $state == null) {
            return $this->redirectToOauth2AuthError($translator->translate('oauth2.missing.authentication.code.or.state.parameter'));
        }

        $this->blockInvalidState('x', $state);
        $x = (AuthChoice::widget())->getClient('x');

        /**
         * @psalm-suppress DocblockTypeContradiction $code
         */
        if (strlen($code) == 0) {
            $codeVerifier = Random::string(128);
            $codeChallenge = strtr(rtrim(base64_encode(hash('sha256', $codeVerifier, true)), '='), '+/', '-_');

            // Store code_verifier in session or other storage
            $this->session->set('code_verifier', $codeVerifier);

            $authorizationUrl = $x->buildAuthUrl(
                $request,
                [
                    'code_challenge' => $codeChallenge,
                    'code_challenge_method' => 'S256',
                ],
            );
            return $this->webService->getRedirectResponse($authorizationUrl);
        }
        if ($code == 401) {
            return $this->redirectToOauth2CallbackResultUnAuthorised();
        }

        /**
         * @psalm-suppress DocblockTypeContradiction $state
         */
        if (strlen($state) == 0) {
            return $this->redirectToOauth2AuthError($translator->translate('oauth2.missing.state.parameter.possible.csrf.attack'));
        }
        $codeVerifier = (string) $this->session->get('code_verifier');
        $params = [
            'grant_type' => 'authorization_code',
            'redirect_uri' => $x->getOauth2ReturnUrl(),
            'code_verifier' => $codeVerifier,
        ];
        /** @psalm-var \Yiisoft\Yii\AuthClient\Client\X $x */
        $oAuthTokenType = $x->fetchAccessTokenWithCodeVerifier($request, $code, $params);
        $userArray = $x->getCurrentUserJsonArray($oAuthTokenType, $this->configWebDiAuthGuzzle, $this->requestFactory);
        /**
         * @var array $userArray['data']
         */
        $data = $userArray['data'] ?? [];
        /**
         * @var int $data['id']
         */
        $xId = $data['id'] ?? 0;
        if ($xId > 0) {
            $xLogin = (string) $data['username'];
            if (strlen($xLogin) > 0) {
                $login = 'twitter' . (string) $xId . $xLogin;
                /**
                 * @var string $userArray['email']
                 */
                $email = $userArray['email'] ?? 'noemail' . $login . '@x.com';
                $password = Random::string(32);
                if ($this->authService->oauthLogin($login)) {
                    return $this->tfaCheckBeforeRedirects('x', $tR, $uiR);
                }
                $user = new User($login, $email, $password);
                $uR->save($user);
                $userId = $user->getId();
                if ($userId > 0) {
                    if ($uR->repoCount() == 1) {
                        $this->manager->revokeAll($userId);
                        $this->manager->assign('admin', $userId);
                    } else {
                        $this->manager->revokeAll($userId);
                        $this->manager->assign('observer', $userId);
                    }
                    $login = $user->getLogin();
                    /**
                     * @var array $this->sR->locale_language_array()
                     */
                    $languageArray = $this->sR->locale_language_array();
                    /**
                     * @see Trait\Oauth2 function getXAccessToken
                     * @var array $languageArray
                     * @var string $language
                     */
                    $language = $languageArray[$_language];
                    $randomAndTimeToken = $this->getAccessToken($user, $tR, self::X_ACCESS_TOKEN);
                    /**
                     * @see A new UserInv (extension table of user) for the user is created.
                     */
                    $proceedToMenuButton = $this->proceedToMenuButtonWithMaskedRandomAndTimeTokenLink(
                        $translator,
                        $user,
                        $uiR,
                        $language,
                        $_language,
                        $randomAndTimeToken,
                        'x',
                    );
                    return $this->viewRenderer->render('proceed', [
                        'proceedToMenuButton' => $proceedToMenuButton,
                    ]);
                }
            }
        }

        $this->authService->logout();
        return $this->redirectToMain();
    }

    public function callbackVKontakte(
        ServerRequestInterface $request,
        TranslatorInterface $translator,
        TokenRepository $tR,
        UserInvRepository $uiR,
        UserRepository $uR,
        #[RouteArgument('_language')]
        string $_language,
        #[Query('code')]
        string $code = null,
        #[Query('state')]
        string $state = null,
        #[Query('device_id')]
        string $device_id = null,
    ): ResponseInterface {
        if ($code == null || $state == null) {
            return $this->redirectToOauth2AuthError($translator->translate('oauth2.missing.authentication.code.or.state.parameter'));
        }

        $this->blockInvalidState('vkontakte', $state);

        $vkontakte = (AuthChoice::widget())->getClient('vkontakte');

        /**
         * @psalm-suppress DocblockTypeContradiction $code
         */
        if (strlen($code) == 0) {
            $codeVerifier = Random::string(128);
            $codeChallenge = strtr(rtrim(base64_encode(hash('sha256', $codeVerifier, true)), '='), '+/', '-_');

            // Store code_verifier in session or other storage
            $this->session->set('code_verifier', $codeVerifier);

            $authorizationUrl = $vkontakte->buildAuthUrl(
                $request,
                [
                    'code_challenge' => $codeChallenge,
                    'code_challenge_method' => 'S256',
                    'device_id' => $device_id,
                ],
            );
            return $this->webService->getRedirectResponse($authorizationUrl);
        }
        if ($code == 401) {
            return $this->redirectToOauth2CallbackResultUnAuthorised();
        }

        /**
         * @psalm-suppress DocblockTypeContradiction $state
         */
        if (strlen($state) == 0) {
            return $this->redirectToOauth2AuthError($translator->translate('oauth2.missing.state.parameter.possible.csrf.attack'));
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
        $oAuthTokenType = $vkontakte->fetchAccessTokenWithCodeVerifier($request, $code, $params);

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
         * @var array $userArray
         * @psalm-var \Yiisoft\Yii\AuthClient\Client\VKontakte $vkontakte
         */
        $userArray = $vkontakte->step8ObtainingUserDataArrayWithClientId($oAuthTokenType, $vkontakte->getClientId(), $this->configWebDiAuthGuzzle, $this->requestFactory);

        /**
         * @var array $userArray['user']
         */
        $user = $userArray['user'] ?? [];

        /**
         * @var int $user['user_id']
         */
        $id = $user['user_id'] ?? 0;
        if ($id > 0) {
            /**
             * @var string $user['first_name']
             */
            $userFirstName = $user['first_name'] ?? 'unknown';
            /**
             * @var string $user['last_name']
             */
            $userLastName = $user['last_name'] ?? 'unknown';
            if (strlen($userFirstName) > 0 && strlen($userLastName) > 0) {
                $userName = $userFirstName . ' ' . $userLastName;
            } else {
                $userName = 'fullname unknown';
            }
            // Append the last four digits of the Id
            $login = '' . $userName . substr((string) $id, strlen((string) $id) - 4, strlen((string) $id));
            /**
             * @var string $userArray['email']
             */
            $email = $userArray['email'] ?? 'noemail' . $login . '@vk.ru';
            $password = Random::string(32);
            // The password does not need to be validated here so use authService->oauthLogin($login) instead of authService->login($login, $password)
            // but it will be used later to build a passwordHash
            if ($this->authService->oauthLogin($login)) {
                return $this->tfaCheckBeforeRedirects('vkontakte', $tR, $uiR);
            }
            $user = new User($login, $email, $password);
            $uR->save($user);
            $userId = $user->getId();
            if ($userId > 0) {
                // avoid autoincrement issues and using predefined user id of 1 ... and assign the first signed-up user ... admin rights
                if ($uR->repoCount() == 1) {
                    $this->manager->revokeAll($userId);
                    $this->manager->assign('admin', $userId);
                } else {
                    $this->manager->revokeAll($userId);
                    $this->manager->assign('observer', $userId);
                }
                $login = $user->getLogin();
                /**
                 * @var array $this->sR->locale_language_array()
                 */
                $languageArray = $this->sR->locale_language_array();
                /**
                 * @see Trait\Oauth2 function getYandexAccessToken
                 * @var array $languageArray
                 * @var string $language
                 */
                $language = $languageArray[$_language];
                $randomAndTimeToken = $this->getAccessToken($user, $tR, self::VKONTAKTE_ACCESS_TOKEN);
                /**
                 * @see A new UserInv (extension table of user) for the user is created.
                 */
                $proceedToMenuButton = $this->proceedToMenuButtonWithMaskedRandomAndTimeTokenLink(
                    $translator,
                    $user,
                    $uiR,
                    $language,
                    $_language,
                    $randomAndTimeToken,
                    'vkontakte',
                );
                return $this->viewRenderer->render('proceed', [
                    'proceedToMenuButton' => $proceedToMenuButton,
                ]);
            }
        }

        $this->authService->logout();
        return $this->redirectToMain();
    }

    public function callbackYandex(
        ServerRequestInterface $request,
        TranslatorInterface $translator,
        TokenRepository $tR,
        UserInvRepository $uiR,
        UserRepository $uR,
        #[RouteArgument('_language')]
        string $_language,
        #[Query('code')]
        string $code = null,
        #[Query('state')]
        string $state = null,
    ): ResponseInterface {
        if ($code == null || $state == null) {
            return $this->redirectToOauth2AuthError($translator->translate('oauth2.missing.authentication.code.or.state.parameter'));
        }

        $this->blockInvalidState('yandex', $state);
        $yandex = (AuthChoice::widget())->getClient('yandex');

        /**
         * @psalm-suppress DocblockTypeContradiction $code
         */
        if (strlen($code) == 0) {
            $codeVerifier = Random::string(128);
            $codeChallenge = strtr(rtrim(base64_encode(hash('sha256', $codeVerifier, true)), '='), '+/', '-_');

            // Store code_verifier in session or other storage
            $this->session->set('code_verifier', $codeVerifier);

            $authorizationUrl = $yandex->buildAuthUrl(
                $request,
                [
                    'code_challenge' => $codeChallenge,
                    'code_challenge_method' => 'S256',
                ],
            );
            return $this->webService->getRedirectResponse($authorizationUrl);
        }
        if ($code == 401) {
            return $this->redirectToOauth2CallbackResultUnAuthorised();
        }

        /**
         * @psalm-suppress DocblockTypeContradiction $state
         */
        if (strlen($state) == 0) {
            return $this->redirectToOauth2AuthError($translator->translate('oauth2.missing.state.parameter.possible.csrf.attack'));
        }
        $codeVerifier = (string) $this->session->get('code_verifier');
        $params = [
            'grant_type' => 'authorization_code',
            'redirect_uri' => $yandex->getOauth2ReturnUrl(),
            'code_verifier' => $codeVerifier,
        ];
        $oAuthTokenType = $yandex->fetchAccessTokenWithCodeVerifier($request, $code, $params);
        /**
         * @var array $userArray
         * @psalm-var \Yiisoft\Yii\AuthClient\Client\Yandex $yandex
         */
        $userArray = $yandex->getCurrentUserJsonArray($oAuthTokenType, $this->configWebDiAuthGuzzle, $this->requestFactory);
        /**
         * @var int $userArray['id']
         */
        $id = $userArray['id'] ?? 0;
        if ($id > 0) {
            /**
             * @var string $userArray['login'] e.g john.doe.com
             */
            $userName = $userArray['login'];
            // Append the last four digits of the Id
            $login = 'yx' . $userName . substr((string) $id, strlen((string) $id) - 4, strlen((string) $id));
            $email = 'noemail' . $login . '@yandex.com';
            $password = Random::string(32);
            // The password does not need to be validated here so use authService->oauthLogin($login) instead of authService->login($login, $password)
            // but it will be used later to build a passwordHash
            if ($this->authService->oauthLogin($login)) {
                return $this->tfaCheckBeforeRedirects('yandex', $tR, $uiR);
            }
            $user = new User($login, $email, $password);
            $uR->save($user);
            $userId = $user->getId();
            if ($userId > 0) {
                // avoid autoincrement issues and using predefined user id of 1 ... and assign the first signed-up user ... admin rights
                if ($uR->repoCount() == 1) {
                    $this->manager->revokeAll($userId);
                    $this->manager->assign('admin', $userId);
                } else {
                    $this->manager->revokeAll($userId);
                    $this->manager->assign('observer', $userId);
                }
                $login = $user->getLogin();
                /**
                 * @var array $this->sR->locale_language_array()
                 */
                $languageArray = $this->sR->locale_language_array();
                /**
                 * @see Trait\Oauth2 function getYandexAccessToken
                 * @var array $languageArray
                 * @var string $language
                 */
                $language = $languageArray[$_language];
                $randomAndTimeToken = $this->getAccessToken($user, $tR, self::YANDEX_ACCESS_TOKEN);
                /**
                 * @see A new UserInv (extension table of user) for the user is created.
                 */
                $proceedToMenuButton = $this->proceedToMenuButtonWithMaskedRandomAndTimeTokenLink(
                    $translator,
                    $user,
                    $uiR,
                    $language,
                    $_language,
                    $randomAndTimeToken,
                    'yandex',
                );
                return $this->viewRenderer->render('proceed', [
                    'proceedToMenuButton' => $proceedToMenuButton,
                ]);
            }
        }

        $this->authService->logout();
        return $this->redirectToMain();
    }

    public function tfaCheckBeforeRedirects(string $providerName, TokenRepository $tR, UserInvRepository $uiR): ResponseInterface
    {
        $identity = $this->authService->getIdentity();
        $userId = $identity->getId();
        if (null !== $userId) {
            $userInv = $uiR->repoUserInvUserIdquery($userId);
            if (null !== $userInv) {
                $user = $userInv->getUser();
                if (null !== $user) {
                    if ($this->sR->getSetting('enable_tfa') == '1') {
                        $this->tfaIsEnabledBlockBaseController($userId);
                        $enabled = $user->is2FAEnabled();
                        if ($enabled == false) {
                            $this->session->set('pending_2fa_user_id', $userId);
                            return $this->webService->getRedirectResponse('auth/showSetup');
                        }
                        $this->session->set('verified_2fa_user_id', $userId);
                        return $this->webService->getRedirectResponse('auth/verifyLogin');
                    }
                    $this->tfaNotEnabledUnblockBaseController($userId);
                    $status = $userInv->getActive();
                    $userRoles = $this->manager->getRolesByUserId($userId);
                    $isAdminUser = false;
                    foreach ($userRoles as $role) {
                        if ($role->getName() === 'admin') {
                            $isAdminUser = true;
                            break;
                        }
                    }
                    if ($status || $isAdminUser) {
                        $isAdminUser ? $this->disableToken($tR, '1', $providerName) : '';
                        $this->session->regenerateId();
                        return $this->redirectToInvoiceIndex();
                    }
                    $this->disableToken($tR, $userId, $this->getTokenType($providerName));
                    return $this->redirectToAdminMustMakeActive();
                }
            }
        }
        return $this->redirectToMain();
    }

    private function redirectToOauth2AuthError(string $message): ResponseInterface
    {
        return $this->webService->getRedirectResponse('site/oauth2autherror', [
            'message' => $message,
        ]);
    }

    private function redirectToUserCancelledOauth2(): ResponseInterface
    {
        return $this->webService->getRedirectResponse('site/usercancelledoauth2', ['_language' => 'en']);
    }

    private function redirectToOauth2CallbackResultUnAuthorised(): ResponseInterface
    {
        return $this->webService->getRedirectResponse('site/oauth2callbackresultunauthorised', ['_language' => 'en']);
    }
}
