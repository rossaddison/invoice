<?php

declare(strict_types=1);

namespace App\Auth\Controller;

use App\Auth\Form\ResetPasswordForm;
use App\Auth\IdentityRepository as idR;
use App\Auth\TokenRepository as tR;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Security\TokenMask;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class ResetPasswordController
{
    public const string REQUEST_PASSWORD_RESET_TOKEN = 'request-password-reset';

    public function __construct(
        private readonly WebControllerService $webService,
        private WebViewRenderer $webViewRenderer,
        private readonly UrlGenerator $urlGenerator,
        private readonly TranslatorInterface $translator,
        private readonly LoggerInterface $logger,
    ) {
        // withControllerName returns a new instance so reassignment is needed
        $this->webViewRenderer = $webViewRenderer->withControllerName(
            'resetpassword');
    }

    /**
     * Note: After the user has clicked on their inbox link, the returned
     *  masked token will be unmasked. Only once the
     *  32bit random string has been compared with the unmasked
     *  request-password-reset token, is the resetPasswordForm presented
     *  to the user
     * @param string $maskedToken
     * @param FormHydrator $formHydrator
     * @param ServerRequestInterface $request
     * @param ResetPasswordForm $resetPasswordForm
     * @param idR $idR
     * @param tR $tR
     * @return Response
     */
    public function resetpassword(
        #[RouteArgument('token')]
        string $maskedToken,
        FormHydrator $formHydrator,
        ServerRequestInterface $request,
        ResetPasswordForm $resetPasswordForm,
        idR $idR,
        tR $tR,
    ): Response {
        $unMaskedToken = TokenMask::remove($maskedToken);
        $positionFromUnderscore = strrpos($unMaskedToken, '_');
        if ($positionFromUnderscore === false) {
            return $this->failedReset();
        }
        $timestamp = substr($unMaskedToken, $positionFromUnderscore + 1);
        $tokenRandomStringOnly = substr($unMaskedToken, 0, -(strlen($timestamp) + 1));
        if ((int) $timestamp + 3600 < time()) {
            return $this->failedReset();
        }
        $identity = $tR->findIdentityByToken(
            $tokenRandomStringOnly,
            self::REQUEST_PASSWORD_RESET_TOKEN);
        if (null === $identity) {
            return $this->failedReset();
        }
        $user = $identity->getUser();
        if (null === $user) {
            return $this->failedReset();
        }
        $identityId = (int) $identity->getId();
        if ($identityId <= 0) {
            return $this->failedReset();
        }
        if (!$formHydrator->populateFromPostAndValidate($resetPasswordForm, $request)) {
            return $this->webViewRenderer->render(
                'resetpassword',
                ['formModel' => $resetPasswordForm, 'token' => $maskedToken],
            );
        }
        // 1.) setPassword in User
        $user->setPassword($resetPasswordForm->getNewPassword());
        // 2.) nullify PasswordResetToken (retain Token:type so it can be reissued)
        // Related logic: see https://github.com/yiisoft/yii2-app-advanced/blob/master/
        //  frontend/models/ResetPasswordForm.php
        $tokenRecord = $tR->findTokenByIdentityIdAndType(
            $identityId,
            self::REQUEST_PASSWORD_RESET_TOKEN);
        if (null !== $tokenRecord) {
            $tokenRecord->setToken('');
            $tR->save($tokenRecord);
            // 3.) generateAuthKey in Identity
            $identity->generateAuthKey();
            $idR->save($identity);
        }
        return $this->webService->getRedirectResponse('site/resetpasswordsuccess');
    }

    private function failedReset(): Response
    {
        $this->logger->error($this->translator->translate('password.reset.failed'));
        return $this->webService->getRedirectResponse('site/resetpasswordfailed');
    }
}
