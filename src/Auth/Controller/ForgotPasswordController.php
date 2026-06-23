<?php

declare(strict_types=1);

namespace App\Auth\Controller;

use App\Infrastructure\Persistence\Token\Token;
use App\Auth\TokenRepository as tR;
use App\Infrastructure\Persistence\User\User;
use App\User\UserRepository as uR;
use App\Auth\AuthService;
use App\Auth\Form\RequestPasswordResetTokenForm;
use App\Invoice\Setting\SettingRepository as sR;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Body;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Mailer\MailerInterface;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Security\TokenMask;
use Yiisoft\Translator\TranslatorInterface as Translator;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class ForgotPasswordController
{
    public const string REQUEST_PASSWORD_RESET_TOKEN = 'request-password-reset';

    public function __construct(
        private readonly WebControllerService $webService,
        private WebViewRenderer $webViewRenderer,
        private readonly MailerInterface $mailer,
        private readonly sR $sR,
        private readonly Translator $translator,
        private readonly UrlGenerator $urlGenerator,
        private readonly CurrentRoute $currentRoute,
        private readonly LoggerInterface $logger,
    ) {
        // withControllerName returns a new instance so reassignment is needed
        $this->webViewRenderer = $webViewRenderer->withControllerName(
            'forgotpassword');
    }

    /**
     * Note: The actual token is not stored on the server. It is built from
     *  a random 32 bit string and a timestamp.
     *  Stored under entity Token. Two separate fields. This token when sent
     *  to user is always masked and when received by the
     * ResetPasswordController.php it is unmasked and compared and a form with
     *  'new password' and confirm 'new password' are presented
     *
     * Related logic: see src\ViewInjection\CommonViewInjection.php
     * Related logic: see resources\views\site\forgotemailfailed.php and
     *  forgotusernotfound.php and forgotalert.php
     *
     * @param AuthService $authService
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param ServerRequestInterface $request
     * @param RequestPasswordResetTokenForm $requestPasswordResetTokenForm
     * @param tR $tR
     * @param uR $uR
     * @return ResponseInterface
     */
    public function forgot(
        AuthService $authService,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        ServerRequestInterface $request,
        RequestPasswordResetTokenForm $requestPasswordResetTokenForm,
        tR $tR,
        uR $uR,
    ): ResponseInterface {
        // only guests i.e. only unauthenticated users can access this function
        //  i.e NOT logged in before request
        // check that symfony and the config/common/params.php
        //  mailer->senderEmail have been setup
        $guard = match(true) {
            !$authService->isGuest() => 'site/index',
            $this->sR->getSetting('email_send_method') !== 'symfony'
                || !$this->sR->mailerEnabled()
                || empty($this->sR->getConfigSenderEmail()) => 'site/forgotemailfailed',
            default => null,
        };
        if ($guard !== null) {
            return $this->webService->getRedirectResponse($guard);
        }
        $response = null;
        if ($formHydrator->populateFromPostAndValidate($requestPasswordResetTokenForm, $request)) {
            $user = $uR->findByEmail($requestPasswordResetTokenForm->getEmail());
            $response = $this->handleForgotUser($user, $currentRoute, $tR);
            $response ??= $this->webService->getRedirectResponse('site/forgotalert');
        }
        if ($response !== null) {
            return $response;
        }
        return $this->webViewRenderer->render('forgotpassword', ['formModel' =>    $requestPasswordResetTokenForm]);
    }

    private function handleForgotUser(?User $user, CurrentRoute $currentRoute, tR $tR): ?ResponseInterface
    {
        if (null === $user) {
            $this->logger->error($this->translator->translate('loginalert.user.not.found'));
            return $this->webService->getRedirectResponse('site/forgotusernotfound');
        }
        $identityId = (int) $user->getIdentity()->getId();
        if ($identityId <= 0) {
            return null;
        }
        return $this->handleValidIdentity($identityId, $user, $currentRoute, $tR);
    }

    private function handleValidIdentity(
        int $identityId,
        User $user,
        CurrentRoute $currentRoute,
        tR $tR,
    ): ?ResponseInterface {
        $token = $this->resolvePasswordResetToken($identityId, $tR);
        if (strlen($token) <= 0) {
            return null;
        }
        $_language = (string) $currentRoute->getArgument('_language');
        return $this->trySendEmail($user, $token, $_language);
    }

    private function resolvePasswordResetToken(int $identityId, tR $tR): string
    {
        $tokenRecord = $tR->findTokenByIdentityIdAndType(
            $identityId, self::REQUEST_PASSWORD_RESET_TOKEN);
        if (null == $tokenRecord) {
            return $this->requestPasswordResetToken($identityId, $tR);
        }
        $tokenString = $tokenRecord->getToken();
        if (null === $tokenString) {
            return '';
        }
        $timeStamp = $tokenRecord->getCreatedAt()->getTimestamp();
        return ($timeStamp + 3600 >= time())
            ? $tokenString . '_' . (string) $timeStamp
            : $this->requestPasswordResetToken($identityId, $tR);
    }

    private function trySendEmail(User $user, string $token, string $_language): ?ResponseInterface
    {
        if (!($this->sR->getSetting('email_send_method') === 'symfony'
                || $this->sR->mailerEnabled())) {
            return null;
        }
        $to = $user->getEmail();
        $login = $user->getLogin();
        $htmlBody = $this->htmlBodyWithMaskedRandomAndTimeTokenLink($_language, $token);
        $email = new \Yiisoft\Mailer\Message(
            charset: 'utf-8',
            headers: [
                'X-Origin' => ['0', '1'],
                'X-Pass' => 'pass',
            ],
            subject: $login . ': <' . $to . '>',
            date: new \DateTimeImmutable('now'),
            from: [
                $this->sR->getConfigAdminEmail() => $this->translator->translate('administrator'),
            ],
            to: $to,
            htmlBody: $htmlBody,
        );
        $email->withAddedHeader('Message-ID', $this->sR->getConfigAdminEmail());
        try {
            $this->mailer->send($email);
            return null;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return $this->webService->getRedirectResponse('site/forgotemailfailed');
        }
    }

    /**
     * @param int $identityId
     * @param tR $tR
     * @return string
     */
    private function requestPasswordResetToken(
        int $identityId,
        tR $tR,
    ): string {
        $newTokenRecord = new Token($identityId,
            self::REQUEST_PASSWORD_RESET_TOKEN);
        $requestPasswordResetToken = '';
        $tR->save($newTokenRecord);
        $tokenString = $newTokenRecord->getToken();
        if (null !== $tokenString) {
            $timeStamp = (string) $newTokenRecord
                ->getCreatedAt()
                ->getTimestamp();
            $requestPasswordResetToken = $tokenString . '_' . $timeStamp;
        }
        return $requestPasswordResetToken;
    }

    /**
     * @param string $_language
     * @param string $randomAndTimeToken
     * @return string
     */
    private function htmlBodyWithMaskedRandomAndTimeTokenLink(
        string $_language,
        string $randomAndTimeToken,
    ): string {
        $tokenWithMask = TokenMask::apply($randomAndTimeToken);
        $content = new A()
            ->href($this->urlGenerator->generateAbsolute(
                'auth/resetpassword',
                [
                    '_language' => $_language,
                    'token' => $tokenWithMask,
                ],
            ))
            ->content($this->translator->translate('password.reset.email'));
        return new Body()
            ->content($content)
            ->render();
    }
}
