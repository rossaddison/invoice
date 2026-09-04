<?php

declare(strict_types=1);

namespace App\Auth;

use App\Auth\Controller\HomeCareSignupController;
use App\Auth\Controller\HomeCareSignupDeps;
use App\Auth\Form\HomeCareSignupForm;
use App\Infrastructure\Persistence\HomeCarePendingSignup\HomeCarePendingSignup;
use App\Infrastructure\Persistence\Token\Token;
use App\Infrastructure\Persistence\User\User;
use App\Infrastructure\Persistence\UserInv\UserInv;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\UserInv\UserInvRepository as UIR;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Body;
use Yiisoft\Mailer\Message;
use Yiisoft\Mailer\MailerInterface;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Security\TokenMask;
use Yiisoft\Translator\TranslatorInterface as Translator;

/**
 * Pending-signup persistence and verification-email dispatch for the HomeCare public signup flow — split
 * out of HomeCareSignupController purely to keep that class under SonarQube's php:S1448 method-count
 * ceiling (20 methods), the same reasoning already documented for ClientDwellingRepository/
 * ProductNameTypeRepository splitting out of their respective repositories.
 */
final readonly class HomeCareSignupEmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private sR $sR,
        private Translator $translator,
        private UrlGenerator $urlGenerator,
        private LoggerInterface $logger,
        private WebControllerService $webService,
    ) {
    }

    public function savePendingSignup(int $userId, HomeCareSignupForm $signupForm, HomeCareSignupDeps $d): void
    {
        $pending = new HomeCarePendingSignup();
        $pending->setUserId($userId);
        $pending->setClientName($signupForm->getClientName());
        $pending->setClientSurname($signupForm->getClientSurname());
        $pending->setStreet($signupForm->getStreet());
        $pending->setBuildingNumber($signupForm->getBuildingNumber());
        $pending->setPrice((float) $signupForm->getPrice());
        $pending->setPaymentOption($signupForm->getPaymentOption());
        $pending->setSecondaryCategoryId($signupForm->getSecondaryCategoryId());
        $d->pendingR->save($pending);
    }

    public function processSendSignupEmail(
        User $user,
        CurrentRoute $currentRoute,
        HomeCareSignupDeps $d,
    ): ?Response {
        $to = $user->getEmail();
        $login = $user->getLogin();
        $languageArray = $this->sR->localeLanguageArray();
        $_language = $currentRoute->getArgument('_language');
        /**
         * @var string $_language
         * @var string $language
         */
        $language = $languageArray[$_language];
        $randomAndTimeToken = $this->getEmailVerificationToken($user, $d);
        $htmlBody = $this->htmlBodyWithMaskedRandomAndTimeTokenLink(
            $user,
            $d->uiR,
            $language,
            $_language,
            $randomAndTimeToken
        );
        if (($this->sR->getSetting('email_send_method') == 'symfony')
                || ($this->sR->mailerEnabled())) {
            $configEmail = $this->sR->getConfigAdminEmail();
            $tta = $this->translator->translate('administrator');
            $email = new Message(
                charset: 'utf-8',
                headers: [
                    'X-Origin' => ['0', '1'],
                    'X-Pass' => 'pass',
                ],
                subject: $login . ': <' . $to . '>',
                date: new \DateTimeImmutable('now'),
                from: [$configEmail => $tta],
                to: $to,
                htmlBody: $htmlBody,
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

    private function htmlBodyWithMaskedRandomAndTimeTokenLink(
        User $user,
        UIR $uiR,
        string $language,
        string $_language,
        string $randomAndTimeToken,
    ): string {
        $tokenWithMask = TokenMask::apply($randomAndTimeToken);
        $userInv = new UserInv();
        $userId = $user->reqId();
        $elcc = $this->translator->translate('email.link.click.confirm');
        $userInv->setUserId($userId);
        $userInv->setType($userId == 1 ? 0 : 1);
        // Kept inactive until the confirmation link is clicked, same as the
        // generic signup flow.
        $userInv->setActive(false);
        $userInv->setLanguage($language);
        $uiR->save($userInv);
        $content = new A()
            ->href($this->urlGenerator->generateAbsolute(
                'homecare/signup-confirm',
                [
                    '_language' => $_language,
                    'language' => $language,
                    'token' => $tokenWithMask,
                    'tokenType' => 'homecare-email-verification',
                ],
            ))
            ->content($elcc);
        return new Body()
            ->content($content)
            ->render();
    }

    private function getEmailVerificationToken(User $user, HomeCareSignupDeps $d): string
    {
        $identity = $user->getIdentity();
        $identityId = $identity->getId();
        $token = new Token((int) $identityId, HomeCareSignupController::EMAIL_VERIFICATION_TOKEN);
        $d->tR->save($token);
        $tokenString = $token->getToken();
        $timeString = (string) $token->getCreatedAt()->getTimestamp();
        return null !== $tokenString ?
            ($tokenString . '_' . $timeString) : '';
    }
}
