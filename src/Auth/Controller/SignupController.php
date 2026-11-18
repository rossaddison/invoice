<?php

declare(strict_types=1);

namespace App\Auth\Controller;

use App\Infrastructure\Persistence\Token\Token;
use App\Auth\TokenRepository as tR;
use App\Infrastructure\Persistence\User\User;
use App\User\UserRepository as uR;
use App\Auth\AuthService;
use App\Auth\Form\SignupForm;
use App\Auth\SignupConfirmationEmail;
use App\Auth\Trait\ClassList;
use App\Auth\Trait\Oauth2;
use App\Auth\Trait\TurnstileVerification;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\AppConstants;
use App\Invoice\UserInv\UserRbacLinkRepository;
use App\Invoice\UserInv\UserInvRepository as uiR;
use App\Service\ClientIpResolver;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Mailer\MailerInterface;
use Yiisoft\Rbac\Manager as Manager;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Security\Random;
use Yiisoft\Security\TokenMask;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface as Translator;
use RossAddison\OpenBankingClient\OpenBanking;
use RossAddison\OpenBankingClient\OpenBankingProviderRegistryInterface;
use Yiisoft\Yii\AuthClient\Widget\AuthChoice;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class SignupController
{
    use ClassList;

    use Oauth2;

    use TurnstileVerification;

    public const string EMAIL_VERIFICATION_TOKEN = 'email-verification';
    public string $telegramToken;

    public function __construct(
        private readonly Manager $manager,
        private Flash $flash,
        private readonly DataResponseFactoryInterface $factory,
        private readonly WebControllerService $webService,
        private readonly SessionInterface $session,
        private WebViewRenderer $webViewRenderer,
        private readonly MailerInterface $mailer,
        private readonly sR $sR,
        private readonly OpenBankingProviderRegistryInterface $openBankingProviderRegistry,
        private readonly Translator $translator,
        private readonly UrlGenerator $urlGenerator,
        private readonly CurrentRoute $currentRoute,
        private readonly LoggerInterface $logger,
        private readonly UserRbacLinkRepository $urlR,
        private readonly uiR $uiR,
        private readonly ClientIpResolver $clientIp,
    ) {
        $this->flash = new Flash($this->session);
        $this->webViewRenderer = $webViewRenderer->withControllerName('signup');
        $this->initializeOauth2IdentityProviderCredentials();
        $this->initializeOauth2IdentityProviderDualUrls();
        $this->telegramToken = $sR->getSetting('telegram_token');
    }

    /**
     * Related logic: see AuthChoice function authRoutedButtons()
     * @param ServerRequestInterface $request
     * @param AuthChoice $authChoice
     * @return ResponseInterface
     */
    public function authclient(
        ServerRequestInterface $request,
        AuthChoice $authChoice,
    ): ResponseInterface {
        $query = $request->getQueryParams();
        $clientName = (string) $query['authclient'];
        $client = $authChoice->getClient($clientName);
        $codeVerifier = Random::string(128);
        $this->session->set('code_verifier', $codeVerifier);
        $rTrim = rtrim(base64_encode(hash('sha256', $codeVerifier, true)), '=');
        $codeChallenge = strtr($rTrim, '+/', '-_');
        $selectedIdentityProviders = $this->idpList($codeChallenge);
        $selectedClient = (array) $selectedIdentityProviders[$clientName];
        $clientParams = (array) $selectedClient['params'];
        $clientAuthUrl = $client->buildAuthUrl($request, $clientParams);
        return $this->factory
                    ->createResponse(null, 302)
                    ->withHeader('Location', $clientAuthUrl);
    }

    /**
     * Whether this page is closed to the visitor: someone already signed in has no use for it, and
     * stop_signing_up (Settings > General, and the home-services setup) closes it for everyone, not
     * only the navbar link, so typing the address no longer gets a signup form. Same guard as
     * HomeCare signup.
     */
    private function signupIsClosedTo(AuthService $authService): bool
    {
        return !$authService->isGuest() || '1' === $this->sR->getSetting('stop_signing_up');
    }

    public function signup(
        AuthService $authService,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        ServerRequestInterface $request,
        SignupForm $signupForm,
        SignupDeps $deps,
    ): ResponseInterface {
        $uR = $deps->uR;
        if ($this->signupIsClosedTo($authService)) {
            return $this->webService->getRedirectResponse('site/index');
        }

        $openBankChoice = $this->sR->getSetting('open_banking_provider');
        $openBankingAuthUrl = $this->buildOpenBankingAuthUrl($openBankChoice);

        if ($request->getMethod() === 'POST') {
            $body = (array) $request->getParsedBody();
            $remoteIp = $this->clientIp->resolve($request) ?? '';
            if (!$this->verifyTurnstileRequired((string) ($body['cf-turnstile-response'] ?? ''), $remoteIp)) {
                return $this->webService->getRedirectResponse('site/signupfailed');
            }
        }

        $redirect = null;
        if ($formHydrator->populateFromPostAndValidate($signupForm, $request)) {
            $user = $signupForm->signup();
            $userId = $user->reqId();
            if ($userId > 0) {
                // The extension row exists, inactive, before the role is assigned so that
                // assignRoleAndVerify() can link it to RBAC; the emailed link activates it.
                $deps->uiR->createInactiveFor(
                    $user,
                    $this->signupLanguage($currentRoute),
                    $signupForm->getConsentPeriodicInvoice(),
                    $signupForm->getConsentTelegramOutstanding(),
                );
                $role = $uR->repoCount() == 1 ? AppConstants::ROLE_ADMIN : AppConstants::ROLE_OBSERVER;
                if (!$this->assignRoleAndVerify($userId, $role)) {
                    $redirect = $this->webService->getRedirectResponse('site/signupfailed');
                } else {
                    $redirect = $this->processSendSignupEmail($user, $currentRoute, $deps);
                }
            }
            $redirect = $redirect ?? $this->webService->getRedirectResponse('site/signupsuccess');
        }

        $codeVerifier = Random::string(128);
        $this->session->set('code_verifier', $codeVerifier);
        $rTrim = rtrim(base64_encode(hash('sha256', $codeVerifier, true)), '=');
        $codeChallenge = strtr($rTrim, '+/', '-_');
        $nocb = $this->sR->getSetting('no_openbanking_continue_button');
        $errors = $signupForm->isValidated()
            ? $signupForm->getValidationResult()->getErrorMessagesIndexedByProperty()
            : [];
        return $redirect ?? $this->webViewRenderer->render('signup', [
            'class' => $this->classList(),
            'formModel' => $signupForm,
            'errors' => $errors,
            'selectedOpenBankingProvider' => $openBankChoice,
            'noOpenBankingContinueButton' => $nocb === '1',
            'openBankingAuthUrl' => $openBankingAuthUrl,
            'sessionOtp' => $this->session->get('otp'),
            'telegramToken' => $this->telegramToken,
            'request' => $request,
            'idpList' => $this->idpList($codeChallenge),
            'turnstileSiteKey' => $this->sR->getSetting('turnstile_site_key'),
        ]);
    }

    private function buildOpenBankingAuthUrl(string $openBankChoice): string
    {
        if (strlen($openBankChoice) > 0) {
            $providerConfig = $this->openBankingProviderRegistry->getProviderConfig($openBankChoice);
            if ($providerConfig !== null) {
                /** @var OpenBanking $openBanking */
                $openBanking = (AuthChoice::widget())->getClient('openbanking');
                $openBanking->setAuthUrl((string) $providerConfig['authUrl']);
                $openBanking->setTokenUrl((string) $providerConfig['tokenUrl']);
                $openBanking->setScope(isset($providerConfig['scope']) ?
                    (string) $providerConfig['scope'] : null);
                $codeVerifier = Random::string(128);
                $hash = hash('sha256', $codeVerifier, true);
                $rTrim = rtrim(base64_encode($hash), '=');
                $codeChallenge = strtr($rTrim, '+/', '-_');
                $this->session->set('code_verifier', $codeVerifier);
                return $openBanking->getAuthUrl()
                    . '?'
                    . http_build_query([
                        'response_type' => 'code',
                        'scope' => $openBanking->getScope(),
                        'code_challenge' => $codeChallenge,
                        'code_challenge_method' => 'S256',
                    ]);
            }
        }
        return '';
    }

    /**
     * The language name (e.g. 'English') of the locale in the signup URL, e.g. 'en'.
     */
    private function signupLanguage(CurrentRoute $currentRoute): string
    {
        $languageArray = $this->sR->localeLanguageArray();
        /** @var string $_language */
        $_language = $currentRoute->getArgument('_language');
        /** @var string $language */
        $language = $languageArray[$_language];
        return $language;
    }

    private function processSendSignupEmail(
        User $user,
        CurrentRoute $currentRoute,
        SignupDeps $deps,
    ): ?ResponseInterface {
        $to = $user->getEmail();
        $login = $user->getLogin();
        /** @var string $_language */
        $_language = $currentRoute->getArgument('_language');
        $randomAndTimeToken = $this->getEmailVerificationToken($user, $deps->tR);
        $confirmUrl = $this->confirmUrl($this->signupLanguage($currentRoute), $_language, $randomAndTimeToken);
        $linkText = $this->translator->translate('email.link.click.confirm');
        if (($this->sR->getSetting('email_send_method') == 'symfony')
                || ($this->sR->mailerEnabled())) {
            $configEmail = $this->sR->getConfigAdminEmail();
            $tta = $this->translator->translate('administrator');
            $email = new \Yiisoft\Mailer\Message(
                charset: 'utf-8',
                headers: [
                    'X-Origin' => ['0', '1'],
                    'X-Pass' => 'pass',
                ],
                subject: $login . ': <' . $to . '>',
                date: new \DateTimeImmutable('now'),
                from: [$configEmail => $tta],
                to: $to,
                textBody: SignupConfirmationEmail::text($confirmUrl, $linkText),
                htmlBody: SignupConfirmationEmail::html($confirmUrl, $linkText),
            );
            $email->withAddedHeader('Message-ID', $this->sR->getConfigAdminEmail());
            try {
                $this->mailer->send($email);
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                return $this->webService->getRedirectResponse('site/signupfailed');
            }
        }
        return null;
    }

    /**
     * Assign a role to a newly signed-up user and verify the assignment
     * persisted correctly. Guards against silent failures where assign()
     * succeeds in memory but never reaches the yii_rbac_assignment DB table.
     *
     * @param int $userId
     * @param string $role e.g. 'admin' or 'observer'
     * @return bool True if assignment persisted, false if it failed silently
     */
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
        $userInv = $this->uiR->repoUserInvUserIdquery($userId);
        if ($userInv !== null) {
            $this->urlR->upsert($userInv->reqId(), $userId);
        }
        return true;
    }

    /**
     * The address the emailed link points to. When it is clicked, userinv/signup shows the confirm
     * button; pressing it makes the user active in the userinv extension table (created inactive by
     * signup()) and assigns a client when 'Assign a client to user on signup' is on in Settings.
     */
    private function confirmUrl(string $language, string $_language, string $randomAndTimeToken): string
    {
        return $this->urlGenerator->generateAbsolute(
            'userinv/signup',
            [
                '_language' => $_language,
                'language' => $language,
                'token' => TokenMask::apply($randomAndTimeToken),
                'tokenType' => 'email-verification',
            ],
        );
    }

    /**
     * @param User $user
     * @param tR $tR
     * @return string
     */
    private function getEmailVerificationToken(User $user, tR $tR): string
    {
        $identity = $user->getIdentity();
        $identityId = $identity->getId();
        $token = new Token((int) $identityId, self::EMAIL_VERIFICATION_TOKEN);
        // store the token amongst all the other types of tokens e.g.
        // password_reset_token, email_verification_token etc
        $tR->save($token);
        $tokenString = $token->getToken();
        $timeString = (string) $token->getCreatedAt()->getTimestamp();
        // build the token
        return null !== $tokenString ?
            ($tokenString . '_' . $timeString) : '';
    }
}
