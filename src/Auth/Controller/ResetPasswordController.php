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
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class ResetPasswordController
{
    public const string REQUEST_PASSWORD_RESET_TOKEN = 'request-password-reset';

    public function __construct(
        private WebControllerService $webService,
        private ViewRenderer $viewRenderer,
        private UrlGenerator $urlGenerator,
        private TranslatorInterface $translator,
        private LoggerInterface $logger
    ) {
        $this->webService = $webService;
        $this->viewRenderer = $viewRenderer->withControllerName('resetpassword');
        $this->urlGenerator = $urlGenerator;
        $this->translator = $translator;
        $this->logger = $logger;
    }

    /**
     * Note: After the user has clicked on their inbox link, the returned masked token will be unmasked. Only once the
     * 32bit randdom string has been compared with the unmasked request-password-reset token is the resetPasswordForm presented to the user
     * @param string $maskedToken
     * @param FormHydrator $formHydrator
     * @param ServerRequestInterface $request
     * @param ResetPasswordForm $resetPasswordForm
     * @param idR $idR
     * @param tR $tR
     * @return Response
     */
    public function resetpassword(
        #[RouteArgument('token')] string $maskedToken,
        FormHydrator $formHydrator,
        ServerRequestInterface $request,
        ResetPasswordForm $resetPasswordForm,
        idR $idR,
        tR $tR
    ): Response {
        $unMaskedToken = TokenMask::remove($maskedToken);
        $positionFromUnderscore = strrpos($unMaskedToken, '_');
        if ($positionFromUnderscore > -1) {
            $timestamp = substr($unMaskedToken, $positionFromUnderscore + 1);
            $lengthTimeStamp = strlen($timestamp);
            $tokenRandomStringOnly = substr($unMaskedToken, 0, -($lengthTimeStamp + 1));
            if ((int)$timestamp + 3600 >= time()) {
                $identity = $tR->findIdentityByToken($tokenRandomStringOnly, self::REQUEST_PASSWORD_RESET_TOKEN);
                if (null !== $identity) {
                    if (null !== ($user = $identity->getUser()) && null !== ($identityId = $identity->getId())) {
                        if ($formHydrator->populateFromPostAndValidate($resetPasswordForm, $request)) {
                            /**
                             * Following algorithm:
                             * 1.) setPassword in User
                             * 2.) nullify PasswordResetToken by setting the Token:token to null but retaining the Token:type
                             *     so that the token(Random::string(32)) for this type can be reset
                             * 3.) generateAuthKey in Identity
                             * @see https://github.com/yiisoft/yii2-app-advanced/blob/master/frontend/models/ResetPasswordForm.php
                             */
                            //1.)
                            $user->setPassword($resetPasswordForm->getNewPassword());
                            //2.)
                            $tokenRecord = $tR->findTokenByIdentityIdAndType($identityId, self::REQUEST_PASSWORD_RESET_TOKEN);
                            if (null !== $tokenRecord) {
                                $tokenRecord->setToken('');
                                $tR->save($tokenRecord);
                                //3.)
                                $identity->generateAuthKey();
                                $idR->save($identity);
                            }
                            return $this->webService->getRedirectResponse('site/resetpasswordsuccess');
                        }
                        return $this->viewRenderer->render('resetpassword', ['formModel' => $resetPasswordForm, 'token' => $maskedToken]);
                    }
                }
            }
        }
        $this->logger->error($this->translator->translate($this->translator->translate('password.reset.failed')));
        return $this->webService->getRedirectResponse('site/resetpasswordfailed');
    }
}
