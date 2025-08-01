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

        /**
         * @psalm-suppress DocblockTypeContradiction $code
         */
        if (strlen($code) == 0) {
            $authorizationUrl = $this->developerSandboxHmrc->buildAuthUrl($request, []);
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
         * Related logic: see https://developer.service.hmrc.gov.uk/api-documentation/docs/authorisation
         * For user-restricted access, the 'Authorization Code' Grant Type is used
         * Use the code received, to get an access_token
         */
        $oAuthToken = $this->developerSandboxHmrc->fetchAccessTokenWithCurlAndCodeVerifier($request, $code, $params = [
            'redirect_uri' => $this->developerSandboxHmrc->getOauth2ReturnUrl(),
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
             * Related logic: see Yiisoft\Yii\AuthClient\Client\DeveloperSandboxHmrc function getTestUserArray;
             */
            $requestBody = [
                'serviceNames' => ['national-insurance'],
            ];

            $userArray = $this->developerSandboxHmrc->createTestUserIndividual($oAuthToken, $requestBody);
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
            $email = $userArray['emailAddress'] ?? 'noemail' . $login . '@' . str_replace('https://', '', $this->developerSandboxHmrc->getApiBaseUrl1());
            $password = Random::string(32);
            if ($this->authService->oauthLogin($login)) {
                $identity = $this->authService->getIdentity();
                $userId = $identity->getId();
                if (null !== $userId) {
                    $userInv = $uiR->repoUserInvUserIdquery($userId);
                    if (null !== $userInv) {
                        $status = $userInv->getActive();
                        if ($status || $userId == 1) {
                            $userId == 1 ? $this->disableToken($tR, '1', 'developersandboxhmrc') : '';
                            return $this->redirectToInvoiceIndex();
                        }
                        $this->disableToken($tR, $userId, 'developersandboxhmrc');
                        return $this->redirectToAdminMustMakeActive();
                    }
                }
                return $this->redirectToMain();
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
                 * Related logic: see Trait\Oauth2 function getDeveloperSandboxHmrcAccessToken
                 * @var array $languageArray
                 * @var string $language
                 */
                $language = $languageArray[$_language];
                $randomAndTimeToken = $this->getDeveloperSandboxHmrcAccessToken($user, $tR);
                /**
                 * Related logic: see A new UserInv (extension table of user) for the user is created.
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
     * Related logic: see src/Invoice/UserInv/UserInvController function facebook
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

        /**
         * @psalm-suppress DocblockTypeContradiction $code
         */
        if (strlen($code) == 0) {
            // If we don't have an authorization code then get one
            // and use the protected function oauth2->generateAuthState to generate state param
            // which has a session id built into it
            $authorizationUrl = $this->facebook->buildAuthUrl($request, []);
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
        $oAuthTokenType = $this->facebook->fetchAccessToken($request, $code, $params = []);
        /**
         * @var array $userArray
         */
        $userArray = $this->facebook->getCurrentUserJsonArray($oAuthTokenType);
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
                    $identity = $this->authService->getIdentity();
                    $userId = $identity->getId();
                    if (null !== $userId) {
                        $userInv = $uiR->repoUserInvUserIdquery($userId);
                        if (null !== $userInv) {
                            // disable our facebook access token as soon as the user logs in for the first time
                            $status = $userInv->getActive();
                            if ($status || $userId == 1) {
                                $userId == 1 ? $this->disableToken($tR, '1', 'facebook') : '';
                                return $this->redirectToInvoiceIndex();
                            }
                            $this->disableToken($tR, $userId, 'facebook');
                            return $this->redirectToAdminMustMakeActive();
                        }
                    }
                    return $this->redirectToMain();
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
                     * Related logic: see Trait\Oauth2 function getFacebookAccessToken
                     * @var array $languageArray
                     * @var string $language
                     */
                    $language = $languageArray[$_language];
                    $randomAndTimeToken = $this->getFacebookAccessToken($user, $tR);
                    /**
                     * Related logic: see A new UserInv (extension table of user) for the user is created.
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
     * Related logic: see src/Invoice/UserInv/UserInvController function github
     * Related logic: see https://docs.github.com/en/apps/oauth-apps/building-oauth-apps/authorizing-oauth-apps
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

        /**
         * @psalm-suppress DocblockTypeContradiction $code
         */
        if (strlen($code) == 0) {
            // If we don't have an authorization code then get one
            // and use the protected function oauth2->generateAuthState to generate state param 'authState'
            // which has a session id built into it
            $authorizationUrl = $this->github->buildAuthUrl($request, []);
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
        $oAuthTokenType = $this->github->fetchAccessToken($request, $code, $params = []);
        /**
         * Every time you receive an access token, you should use the token to revalidate the user's identity.
         * A user can change which account they are signed into when you send them to authorize your app,
         * and you risk mixing user data if you do not validate the user's identity after every sign in.
         * Related logic: see https://docs.github.com/en/apps/oauth-apps/building-oauth-apps/authorizing-oauth-apps#3-use-the-access-token-to-access-the-api
         */
        $userArray = $this->github->getCurrentUserJsonArray($oAuthTokenType);
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
                    $identity = $this->authService->getIdentity();
                    $userId = $identity->getId();
                    if (null !== $userId) {
                        $userInv = $uiR->repoUserInvUserIdquery($userId);
                        if (null !== $userInv) {
                            // disable the github verification token as soon as the user logs in for the first time
                            $status = $userInv->getActive();
                            if ($status || $userId == 1) {
                                $userId == 1 ? $this->disableToken($tR, '1', 'github') : '';
                                return $this->redirectToInvoiceIndex();
                            }
                            $this->disableToken($tR, $userId, 'github');
                            return $this->redirectToAdminMustMakeActive();
                        }
                    }
                    return $this->redirectToMain();
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
                     * Related logic: see Trait\Oauth2 function getGithubAccessToken
                     * @var array $languageArray
                     * @var string $language
                     */
                    $language = $languageArray[$_language];
                    $randomAndTimeToken = $this->getGithubAccessToken($user, $tR);
                    /**
                     * Related logic: see A new UserInv (extension table of user) for the user is created.
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
     * Related logic: see https://console.cloud.google.com/apis/credentials?project=YOUR_PROJECT
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

        /**
         * @psalm-suppress DocblockTypeContradiction $code
         */
        if (strlen($code) == 0) {
            $authorizationUrl = $this->google->buildAuthUrl($request, []);
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

        $oAuthTokenType = $this->google->fetchAccessToken($request, $code, $params = [
            'grant_type' => 'authorization_code',
        ]);

        /**
         * @var array $userArray
         */
        $userArray = $this->google->getCurrentUserJsonArray($oAuthTokenType);

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
                $identity = $this->authService->getIdentity();
                $userId = $identity->getId();
                if (null !== $userId) {
                    $userInv = $uiR->repoUserInvUserIdquery($userId);
                    if (null !== $userInv) {
                        $status = $userInv->getActive();
                        if ($status || $userId == 1) {
                            $userId == 1 ? $this->disableToken($tR, '1', 'google') : '';
                            return $this->redirectToInvoiceIndex();
                        }
                        $this->disableToken($tR, $userId, 'google');
                        return $this->redirectToAdminMustMakeActive();
                    }
                }
                return $this->redirectToMain();
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
                 * Related logic: see Trait\Oauth2 function getGoogleAccessToken
                 * @var array $languageArray
                 * @var string $language
                 */
                $language = $languageArray[$_language];
                $randomAndTimeToken = $this->getGoogleAccessToken($user, $tR);
                /**
                 * Related logic: see A new UserInv (extension table of user) for the user is created.
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

        $this->blockInvalidState('govUk', $state);

        /**
         * @psalm-suppress DocblockTypeContradiction $code
         */
        if (strlen($code) == 0) {
            $authorizationUrl = $this->govUk->buildAuthUrl($request, []);
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
        $oAuthTokenType = $this->govUk->fetchAccessToken($request, $code, $params = []);
        /**
         * @var array $userArray
         */
        $userArray = $this->govUk->getCurrentUserJsonArray($oAuthTokenType);
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
                $identity = $this->authService->getIdentity();
                $userId = $identity->getId();
                if (null !== $userId) {
                    $userInv = $uiR->repoUserInvUserIdquery($userId);
                    if (null !== $userInv) {
                        $status = $userInv->getActive();
                        if ($status || $userId == 1) {
                            $userId == 1 ? $this->disableToken($tR, '1', 'govuk') : '';
                            return $this->redirectToInvoiceIndex();
                        }
                        $this->disableToken($tR, $userId, 'govuk');
                        return $this->redirectToAdminMustMakeActive();
                    }
                }
                return $this->redirectToMain();
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
                 * Related logic: see Trait\Oauth2 function getGovUkAccessToken
                 * @var array $languageArray
                 * @var string $language
                 */
                $language = $languageArray[$_language];
                $randomAndTimeToken = $this->getGovUkAccessToken($user, $tR);
                /**
                 * Related logic: see A new UserInv (extension table of user) for the user is created.
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

        /**
         * @psalm-suppress DocblockTypeContradiction $code
         */
        if (strlen($code) == 0) {
            $authorizationUrl = $this->linkedIn->buildAuthUrl($request, []);
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
            'redirect_uri' => $this->linkedIn->getOauth2ReturnUrl(),
        ];
        $oAuthTokenType = $this->linkedIn->fetchAccessTokenWithCurl($request, $code, $params);
        /**
         * @var array $userArray
         */
        $userArray = $this->linkedIn->getCurrentUserJsonArrayUsingCurl($oAuthTokenType);
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
                $identity = $this->authService->getIdentity();
                $userId = $identity->getId();
                if (null !== $userId) {
                    $userInv = $uiR->repoUserInvUserIdquery($userId);
                    if (null !== $userInv) {
                        $status = $userInv->getActive();
                        if ($status || $userId == 1) {
                            $userId == 1 ? $this->disableToken($tR, '1', 'linkedin') : '';
                            return $this->redirectToInvoiceIndex();
                        }
                        $this->disableToken($tR, $userId, 'linkedin');
                        return $this->redirectToAdminMustMakeActive();
                    }
                }
                return $this->redirectToMain();
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
                 * Related logic: see Trait\Oauth2 function getLinkedInAccessToken
                 * @var array $languageArray
                 * @var string $language
                 */
                $language = $languageArray[$_language];
                $randomAndTimeToken = $this->getLinkedInAccessToken($user, $tR);
                /**
                 * Related logic: see A new UserInv (extension table of user) for the user is created.
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

        $this->blockInvalidState('microsoftOnline', $state);

        /**
         * @psalm-suppress DocblockTypeContradiction $code
         */
        if (strlen($code) == 0) {
            $authorizationUrl = $this->microsoftOnline->buildAuthUrl($request, $params = ['redirect_uri' => 'https://yii3i.co.uk/callbackMicrosoftOnline']);
            return $this->webService->getRedirectResponse($authorizationUrl);
        }

        /**
         * @psalm-suppress DocblockTypeContradiction $state
         */
        if ($code == 401) {
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
        $oAuthTokenType = $this->microsoftOnline->fetchAccessTokenWithCurl($request, $code, $params = ['redirect_uri' => 'https://yii3i.co.uk/callbackMicrosoftOnline']);
        /**
         * @var array $userArray
         */
        $userArray = $this->microsoftOnline->getCurrentUserJsonArrayUsingCurl($oAuthTokenType);
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
                $identity = $this->authService->getIdentity();
                $userId = $identity->getId();
                if (null !== $userId) {
                    $userInv = $uiR->repoUserInvUserIdquery($userId);
                    if (null !== $userInv) {
                        $status = $userInv->getActive();
                        if ($status || $userId == 1) {
                            $userId == 1 ? $this->disableToken($tR, '1', 'microsoftonline') : '';
                            return $this->redirectToInvoiceIndex();
                        }
                        $this->disableToken($tR, $userId, 'microsoftonline');
                        return $this->redirectToAdminMustMakeActive();
                    }
                }
                return $this->redirectToMain();
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
                 * Related logic: see Trait\Oauth2 function getMicrosoftOnlineAccessToken
                 * @var array $languageArray
                 * @var string $language
                 */
                $language = $languageArray[$_language];
                $randomAndTimeToken = $this->getMicrosoftOnlineAccessToken($user, $tR);
                /**
                 * Related logic: see A new UserInv (extension table of user) for the user is created.
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

        if (strlen($code) === 0) {
            $authorizationUrl = $this->openBanking->buildAuthUrl($request, []);
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
        $oAuthToken = $this->openBanking->fetchAccessTokenWithCurlAndCodeVerifier($request, $code, [
            'redirect_uri' => $this->openBanking->getOauth2ReturnUrl(),
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

        /**
         * @psalm-suppress DocblockTypeContradiction $code
         */
        if (strlen($code) == 0) {
            $codeVerifier = Random::string(128);
            $codeChallenge = strtr(rtrim(base64_encode(hash('sha256', $codeVerifier, true)), '='), '+/', '-_');

            // Store code_verifier in session or other storage
            $this->session->set('code_verifier', $codeVerifier);

            $authorizationUrl = $this->x->buildAuthUrl(
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
            'redirect_uri' => $this->x->getOauth2ReturnUrl(),
            'code_verifier' => $codeVerifier,
        ];
        $oAuthTokenType = $this->x->fetchAccessTokenWithCurlAndCodeVerifier($request, $code, $params);
        $userArray = $this->x->getCurrentUserJsonArrayUsingCurl($oAuthTokenType);
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
                    $identity = $this->authService->getIdentity();
                    $userId = $identity->getId();
                    if (null !== $userId) {
                        $userInv = $uiR->repoUserInvUserIdquery($userId);
                        if (null !== $userInv) {
                            $status = $userInv->getActive();
                            if ($status || $userId == 1) {
                                $userId == 1 ? $this->disableToken($tR, '1', 'x') : '';
                                return $this->redirectToInvoiceIndex();
                            }
                            $this->disableToken($tR, $userId, 'x');
                            return $this->redirectToAdminMustMakeActive();
                        }
                    }
                    return $this->redirectToMain();
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
                     * Related logic: see Trait\Oauth2 function getXAccessToken
                     * @var array $languageArray
                     * @var string $language
                     */
                    $language = $languageArray[$_language];
                    $randomAndTimeToken = $this->getXAccessToken($user, $tR);
                    /**
                     * Related logic: see A new UserInv (extension table of user) for the user is created.
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

        /**
         * @psalm-suppress DocblockTypeContradiction $code
         */
        if (strlen($code) == 0) {
            $codeVerifier = Random::string(128);
            $codeChallenge = strtr(rtrim(base64_encode(hash('sha256', $codeVerifier, true)), '='), '+/', '-_');

            // Store code_verifier in session or other storage
            $this->session->set('code_verifier', $codeVerifier);

            $authorizationUrl = $this->vkontakte->buildAuthUrl(
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
            'redirect_uri' => $this->vkontakte->getOauth2ReturnUrl(),
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
        $oAuthTokenType = $this->vkontakte->fetchAccessTokenWithCurlAndCodeVerifier($request, $code, $params);

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
         */
        $userArray = $this->vkontakte->step8ObtainingUserDataArrayUsingCurlWithClientId($oAuthTokenType, $this->vkontakte->getClientId());

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
                $identity = $this->authService->getIdentity();
                $userId = $identity->getId();
                if (null !== $userId) {
                    $userInv = $uiR->repoUserInvUserIdquery($userId);
                    if (null !== $userInv) {
                        // disable the vkontakte verification token as soon as the user logs in for the first time
                        $status = $userInv->getActive();
                        if ($status || $userId == 1) {
                            $userId == 1 ? $this->disableToken($tR, '1', 'vkontakte') : '';
                            return $this->redirectToInvoiceIndex();
                        }
                        $this->disableToken($tR, $userId, 'vkontakte');
                        return $this->redirectToAdminMustMakeActive();
                    }
                }
                return $this->redirectToMain();
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
                 * Related logic: see Trait\Oauth2 function getYandexAccessToken
                 * @var array $languageArray
                 * @var string $language
                 */
                $language = $languageArray[$_language];
                $randomAndTimeToken = $this->getVKontakteAccessToken($user, $tR);
                /**
                 * Related logic: see A new UserInv (extension table of user) for the user is created.
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

        /**
         * @psalm-suppress DocblockTypeContradiction $code
         */
        if (strlen($code) == 0) {
            $codeVerifier = Random::string(128);
            $codeChallenge = strtr(rtrim(base64_encode(hash('sha256', $codeVerifier, true)), '='), '+/', '-_');

            // Store code_verifier in session or other storage
            $this->session->set('code_verifier', $codeVerifier);

            $authorizationUrl = $this->yandex->buildAuthUrl(
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
            'redirect_uri' => $this->yandex->getOauth2ReturnUrl(),
            'code_verifier' => $codeVerifier,
        ];
        $oAuthTokenType = $this->yandex->fetchAccessTokenWithCurlAndCodeVerifier($request, $code, $params);
        /**
         * @var array $userArray
         */
        $userArray = $this->yandex->getCurrentUserJsonArrayUsingCurl($oAuthTokenType);
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
                $identity = $this->authService->getIdentity();
                $userId = $identity->getId();
                if (null !== $userId) {
                    $userInv = $uiR->repoUserInvUserIdquery($userId);
                    if (null !== $userInv) {
                        // disable the yandex verification token as soon as the user logs in for the first time
                        $status = $userInv->getActive();
                        if ($status || $userId == 1) {
                            $userId == 1 ? $this->disableToken($tR, '1', 'yandex') : '';
                            return $this->redirectToInvoiceIndex();
                        }
                        $this->disableToken($tR, $userId, 'yandex');
                        return $this->redirectToAdminMustMakeActive();
                    }
                }
                return $this->redirectToMain();
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
                 * Related logic: see Trait\Oauth2 function getYandexAccessToken
                 * @var array $languageArray
                 * @var string $language
                 */
                $language = $languageArray[$_language];
                $randomAndTimeToken = $this->getYandexAccessToken($user, $tR);
                /**
                 * Related logic: see A new UserInv (extension table of user) for the user is created.
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
