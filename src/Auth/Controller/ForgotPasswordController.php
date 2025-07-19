<?php

declare(strict_types=1);

namespace App\Auth\Controller;

use App\Auth\Token;
use App\Auth\TokenRepository as tR;
use App\User\User;
use App\User\UserRepository as uR;
use App\Auth\AuthService;
use App\Auth\Form\RequestPasswordResetTokenForm;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\UserInv\UserInvRepository as uiR;
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
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class ForgotPasswordController
{
    public const string REQUEST_PASSWORD_RESET_TOKEN = 'request-password-reset';

    public function __construct(
        private readonly WebControllerService $webService,
        private ViewRenderer $viewRenderer,
        private MailerInterface $mailer,
        private sR $sR,
        private Translator $translator,
        private UrlGenerator $urlGenerator,
        private CurrentRoute $currentRoute,
        private LoggerInterface $logger,
    ) {
        $this->viewRenderer = $viewRenderer->withControllerName('forgotpassword');
        $this->mailer = $mailer;
        $this->sR = $sR;
        $this->translator = $translator;
        $this->urlGenerator = $urlGenerator;
        $this->currentRoute = $currentRoute;
        $this->logger = $logger;
    }

    /**
     * Note: The actual token is not stored on the server. It is built from a random 32 bit string and a timestamp.
     * stored under entity Token. Two separate fields. This token when sent to user is always masked and when received by the
     * ResetPasswordController.php it is unmasked and compared and a form with 'new password' and confirm 'new password' are presented
     *
     * @see src\ViewInjection\CommonViewInjection.php
     * @see resources\views\site\forgotemailfailed.php and forgotusernotfound.php and forgotalert.php
     *
     * @param AuthService $authService
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param ServerRequestInterface $request
     * @param RequestPasswordResetTokenForm $requestPasswordResetTokenForm
     * @param tR $tR
     * @param uiR $uiR
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
        uiR $uiR,
        uR $uR,
    ): ResponseInterface {
        // only guests i.e. only unauthenticated users can access this function i.e NOT logged in before request
        if (!$authService->isGuest()) {
            return $this->webService->getRedirectResponse('site/index');
        }
        // check that symfony and the config/common/params.php mailer->senderEmail have been setup
        if (($this->sR->getSetting('email_send_method') !== 'symfony') || ($this->sR->mailerEnabled() == false) || empty($this->sR->getConfigSenderEmail())) {
            return $this->webService->getRedirectResponse('site/forgotemailfailed');
        }
        $requestPasswordResetToken = '';
        if ($formHydrator->populateFromPostAndValidate($requestPasswordResetTokenForm, $request)) {
            $user = $uR->findByEmail($requestPasswordResetTokenForm->getEmail());
            if (null !== $user) {
                $identityId = $user->getIdentity()->getId();
                if (null !== $identityId) {
                    $tokenRecord = $tR->findTokenByIdentityIdAndType($identityId, self::REQUEST_PASSWORD_RESET_TOKEN);
                    if (null == $tokenRecord) {
                        $requestPasswordResetToken = $this->requestPasswordResetToken($identityId, $tR);
                    } else {
                        $tokenString = $tokenRecord->getToken();
                        if (null !== $tokenString) {
                            $timeStamp = $tokenRecord->getCreated_at()->getTimestamp();
                            // check if token Random string is still valid by checking the timestamp
                            if ($timeStamp + 3600 >= time()) {
                                $requestPasswordResetToken = $tokenString . '_' . (string) $timeStamp;
                            } else {
                                /**
                                 * This new Token will be nullified when the password is actually reset in the Token extension table i.e.
                                 * by searching the user ... identity ... token belonging to the user named 'request-password-reset'
                                 * @see PasswordResetController.php
                                 * @see https://github.com/yiisoft/yii2-app-advanced/blob/master/common/models/User.php function removePasswordResetToken
                                 */
                                $requestPasswordResetToken = $this->requestPasswordResetToken($identityId, $tR);
                            }
                        }
                    }
                    if (strlen($requestPasswordResetToken) > 0) {
                        $to = $user->getEmail();
                        $login = $user->getLogin();
                        /**
                         * @var array $this->sR->locale_language_array()
                         */
                        $languageArray = $this->sR->locale_language_array();
                        $_language = $currentRoute->getArgument('_language');

                        /**
                         * @see A new UserInv (extension table of user) for the user is created.
                         * For additional headers to strengthen security refer to:
                         * @see https://en.wikipedia.org/wiki/Email#Message_format
                         * @see https://github.com/yiisoft/mailer/blob/1d3480bc26cbeba47b24e61f9ec0e717c244c0b7/tests/MessageTest.php#L217
                         * @var string $_language
                         */
                        $htmlBody = $this->htmlBodyWithMaskedRandomAndTimeTokenLink($user, $_language, $requestPasswordResetToken);
                        if (($this->sR->getSetting('email_send_method') == 'symfony') || ($this->sR->mailerEnabled() == true)) {
                            $email = new \Yiisoft\Mailer\Message(
                                charset: 'utf-8',
                                headers: [
                                    'X-Origin' => ['0', '1'],
                                    'X-Pass' => 'pass',
                                ],
                                subject: $login . ': <' . $to . '>',
                                date: new \DateTimeImmutable('now'),
                                from: [$this->sR->getConfigAdminEmail() => $this->translator->translate('administrator')],
                                to: $to,
                                htmlBody: $htmlBody,
                            );
                            $email->withAddedHeader('Message-ID', $this->sR->getConfigAdminEmail());

                            try {
                                $this->mailer->send($email);
                            } catch (\Exception $e) {
                                $this->logger->error($e->getMessage());
                                return $this->webService->getRedirectResponse('site/forgotemailfailed');
                            }
                        }
                    }
                }
            } else {
                $this->logger->error($this->translator->translate('loginalert.user.not.found'));
                return $this->webService->getRedirectResponse('site/forgotusernotfound');
            }
            /**
             * Once the user has presented their email address and submitted the form, show this message
             * @see resources/messages/en/app.php
             * @see resources/views/site/forgotalert.php
             * 'i.password_reset_email' .....
             * 'You requested a new password for your installation. Please click the link in your inbox to reset your password:',
             */
            return $this->webService->getRedirectResponse('site/forgotalert');
        }
        return $this->viewRenderer->render('forgotpassword', ['formModel' => $requestPasswordResetTokenForm]);
    }

    /**
     * @param string $identityId
     * @param tR $tR
     * @return string
     */
    private function requestPasswordResetToken(string $identityId, tR $tR): string
    {
        $newTokenRecord = new Token((int) $identityId, self::REQUEST_PASSWORD_RESET_TOKEN);
        $requestPasswordResetToken = '';
        $tR->save($newTokenRecord);
        $tokenString = $newTokenRecord->getToken();
        if (null !== $tokenString) {
            $timeStamp = (string) $newTokenRecord->getCreated_at()->getTimestamp();
            $requestPasswordResetToken = $tokenString . '_' . $timeStamp;
        }
        return $requestPasswordResetToken;
    }

    /**
     * @param User $user
     * @param string $_language
     * @param string $randomAndTimeToken
     * @return string
     */
    private function htmlBodyWithMaskedRandomAndTimeTokenLink(User $user, string $_language, string $randomAndTimeToken): string
    {
        $tokenWithMask = TokenMask::apply($randomAndTimeToken);
        if (null !== ($userId = $user->getId())) {
            $content = A::tag()
                       ->href($this->urlGenerator->generateAbsolute(
                           'auth/resetpassword',
                           ['_language' => $_language, 'token' => $tokenWithMask],
                       ))
                       ->content($this->translator->translate('password.reset.email'));
            return Body::tag()
                       ->content($content)
                       ->render();
        }
        return '';
    }
}
