<?php

declare(strict_types=1);

namespace App\Auth\Controller;

use App\Auth\AuthService;
use App\Auth\Form\HomeCareSignupForm;
use App\Auth\Trait\ClassList;
use App\Auth\Trait\TurnstileVerification;
use App\Infrastructure\Persistence\CategorySecondary\CategorySecondary;
use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\HomeCarePendingSignup\HomeCarePendingSignup;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Infrastructure\Persistence\Payment\Payment;
use App\Infrastructure\Persistence\Token\Token;
use App\Infrastructure\Persistence\User\User;
use App\Infrastructure\Persistence\UserClient\UserClient;
use App\Infrastructure\Persistence\UserInv\UserInv;
use App\Invoice\AppConstants;
use App\Invoice\Inv\InvForm;
use App\Invoice\InvItem\IiAddProductDeps;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\UserClient\UserClientRepository as UCR;
use App\Invoice\UserInv\UserInvRepository as UIR;
use App\Service\WebControllerService;
use App\User\UserRepository as UR;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Body;
use Yiisoft\Mailer\Message;
use Yiisoft\Mailer\MailerInterface;
use Yiisoft\Rbac\Manager;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Security\TokenMask;
use Yiisoft\Translator\TranslatorInterface as Translator;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Public HomeCare-specific signup flow, separate from the generic
 * `SignupController`. Always creates and assigns a Client — unlike the
 * generic flow, it does not consult `signup_automatically_assign_client`.
 * On confirmation-link click, resolves/creates the street (Family) and
 * house-number (Product, Service-type), raises the first invoice, and
 * either records it paid (cash) or leaves it sent/unpaid, so the existing
 * HomeCareCleaningEligibilityService can pick it up deterministically.
 */
final class HomeCareSignupController
{
    use ClassList;
    use TurnstileVerification;

    public const string EMAIL_VERIFICATION_TOKEN = 'homecare-email-verification';

    public function __construct(
        private readonly Manager $manager,
        private readonly AuthService $authService,
        private readonly WebControllerService $webService,
        private WebViewRenderer $webViewRenderer,
        private readonly MailerInterface $mailer,
        private readonly sR $sR,
        private readonly Translator $translator,
        private readonly UrlGenerator $urlGenerator,
        private readonly LoggerInterface $logger,
    ) {
        $this->webViewRenderer = $webViewRenderer->withControllerName('homecaresignup');
    }

    public function signup(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        HomeCareSignupForm $signupForm,
        HomeCareSignupDeps $d,
    ): Response {
        if (!$this->authService->isGuest()) {
            return $this->webService->getRedirectResponse('site/index');
        }

        if ($this->sR->getSetting('stop_homecare_signing_up') === '1') {
            return $this->webService->getRedirectResponse('site/index');
        }

        if ($request->getMethod() === 'POST') {
            $body = (array) $request->getParsedBody();
            $srv = $request->getServerParams();
            $remoteIp = (string) ($srv['HTTP_CF_CONNECTING_IP'] ?? $srv['REMOTE_ADDR'] ?? '');
            if (!$this->verifyTurnstile((string) ($body['cf-turnstile-response'] ?? ''), $remoteIp)) {
                return $this->webService->getRedirectResponse('site/signupfailed');
            }
        }

        $redirect = null;
        if ($formHydrator->populateFromPostAndValidate($signupForm, $request)) {
            $user = $signupForm->signup();
            $userId = $user->reqId();
            if ($userId > 0) {
                $this->savePendingSignup($userId, $signupForm, $d);
                $redirect = $this->processSendSignupEmail($user, $currentRoute, $d);
            }
            $redirect = $redirect ?? $this->webService->getRedirectResponse('site/signupsuccess');
        }

        $errors = $signupForm->isValidated()
            ? $signupForm->getValidationResult()->getErrorMessagesIndexedByProperty()
            : [];
        $categorySecondaryOptions = [
            HomeCareSignupForm::NEW_AREA_SECONDARY_CATEGORY_ID =>
                $this->translator->translate('homecare.signup.category.new.area'),
        ] + $d->csR->optionsDataCategorySecondaries();
        return $redirect ?? $this->webViewRenderer->render('homecaresignup', [
            'class' => $this->classList(),
            'formModel' => $signupForm,
            'errors' => $errors,
            'allowedPrices' => HomeCareSignupForm::ALLOWED_PRICES,
            'categorySecondaryOptions' => $categorySecondaryOptions,
            'turnstileSiteKey' => $this->sR->getSetting('turnstile_site_key'),
        ]);
    }

    private function savePendingSignup(int $userId, HomeCareSignupForm $signupForm, HomeCareSignupDeps $d): void
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

    private function processSendSignupEmail(
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
            $user, $d->uiR, $language, $_language, $randomAndTimeToken);
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
        $token = new Token((int) $identityId, self::EMAIL_VERIFICATION_TOKEN);
        $d->tR->save($token);
        $tokenString = $token->getToken();
        $timeString = (string) $token->getCreatedAt()->getTimestamp();
        return null !== $tokenString ?
            ($tokenString . '_' . $timeString) : '';
    }

    public function confirm(
        #[RouteArgument('language')]
        string $language,
        #[RouteArgument('token')]
        string $tokenMasked,
        #[RouteArgument('tokenType')]
        string $tokenType,
        FormHydrator $formHydrator,
        HomeCareSignupConfirmDeps $d,
    ): Response {
        $unMaskedToken = TokenMask::remove($tokenMasked);
        $positionFromUnderscore = strrpos($unMaskedToken, '_');
        if ($positionFromUnderscore === false) {
            return $this->webService->getRedirectResponse('site/index');
        }
        $timestamp = substr($unMaskedToken, $positionFromUnderscore + 1);
        $tokenWithoutTimestamp = substr($unMaskedToken, 0, -(strlen($timestamp) + 1));

        $identity = $d->tR->findIdentityByToken($tokenWithoutTimestamp, $tokenType);
        $userId = $identity?->getUser()?->reqId();
        if (null === $identity || null === $userId) {
            return $this->webService->getRedirectResponse('site/index');
        }

        // Read once and delete immediately, mirroring how the token itself
        // is invalidated after use — no stale pending rows can linger.
        $pending = $d->pendingR->repoByUserIdquery($userId);
        if ($pending !== null) {
            $d->pendingR->delete($pending);
        }

        $tokenEntity = $d->tR->findTokenByTokenAndType($tokenWithoutTimestamp, $tokenType);
        if ($tokenEntity !== null) {
            $tokenEntity->setToken('already_used_token_' . (string) time());
            $d->tR->save($tokenEntity);
        }

        if ($pending === null || (int) $timestamp + 3600 < time()) {
            return $this->renderConfirmed('expired');
        }

        $userInv = $d->uiR->repoUserInvUserIdquery($userId);
        if ($userInv === null) {
            return $this->webService->getRedirectResponse('site/index');
        }
        $userInv->setActive(true);
        $d->uiR->save($userInv);

        $email = $identity->getUser()?->getEmail() ?? '';
        $client = $this->createClient($email, $language, $pending, $d);
        $clientId = $client->reqId();
        $this->linkUserClient($userId, $clientId, $d);
        $this->assignSignupRole($userId, $userInv->reqId(), $d);

        $categorySecondaryId = $this->resolveSecondaryCategoryId($pending, $d);
        if ($categorySecondaryId === null) {
            return $this->renderConfirmed('setup_incomplete');
        }
        $family = $d->familyService->findOrCreateByStreetName($pending->getStreet(), $categorySecondaryId);
        $taxRate = $d->trR->repoFirstByIdQuery();
        $unit = $d->unR->repoFirstByIdQuery();
        if ($taxRate === null || $unit === null) {
            return $this->renderConfirmed('setup_incomplete');
        }
        $product = $d->productService->findOrCreateHouseNumberProduct(
            $family->reqId(),
            $pending->getBuildingNumber(),
            $pending->getPrice(),
            $taxRate->reqId(),
            $unit->reqId(),
        );

        $invId = $this->createInvoice($clientId, $product->reqId(), $taxRate->reqId(), $unit->reqId(), $pending, $formHydrator, $d);
        if ($invId <= 0) {
            return $this->renderConfirmed('setup_incomplete');
        }
        $d->productClientService->syncFromInvItems($clientId, [$product->reqId()]);
        $d->invRecalculator->recalculate($invId);

        $paidNow = false;
        if ($pending->getPaymentOption() === HomeCareSignupForm::PAYMENT_HAVE_PAID_CASH) {
            $paidNow = $this->recordCashPayment($invId, $d);
        }

        // Always provision the QR code, regardless of payment state — it's
        // ready to print immediately, even though scanning it won't
        // generate anything until HomeCareCleaningEligibilityService sees a
        // paid invoice with a Service-type item on file.
        $token = $d->clientService->getOrCreateQrToken($client);
        $scanUrl = $this->urlGenerator->generateAbsolute('public/homecare-scan', ['token' => $token]);
        $qrDataUri = $d->clientService->renderQrDataUri($scanUrl);

        return $this->renderConfirmed($paidNow ? 'paid' : 'unpaid', $qrDataUri);
    }

    /**
     * Resolves the pending signup's chosen secondary category to a concrete
     * CategorySecondary id. When the customer picked
     * `NEW_AREA_SECONDARY_CATEGORY_ID` (no existing option matched their
     * area), auto-creates a placeholder CategorySecondary — named
     * `not_set_yet_<timestamp>` so it's unmistakably a placeholder — under
     * the lowest-id CategoryPrimary, for staff to file and rename later.
     * Returns null only if there is no CategoryPrimary at all to file it
     * under (unconfigured install), signalling the caller to bail out.
     */
    private function resolveSecondaryCategoryId(HomeCarePendingSignup $pending, HomeCareSignupConfirmDeps $d): ?int
    {
        $categorySecondaryId = $pending->getSecondaryCategoryId();
        if ($categorySecondaryId !== HomeCareSignupForm::NEW_AREA_SECONDARY_CATEGORY_ID) {
            return $categorySecondaryId;
        }
        $categoryPrimary = $d->cpR->repoFirstByIdQuery();
        if ($categoryPrimary === null) {
            return null;
        }
        $categorySecondary = new CategorySecondary();
        $d->categorySecondaryService->saveCategorySecondary($categorySecondary, [
            'category_primary_id' => (string) $categoryPrimary->reqId(),
            'name' => 'not_set_yet_' . time(),
        ]);
        return $categorySecondary->reqId();
    }

    private function createClient(string $email, string $language, HomeCarePendingSignup $pending, HomeCareSignupConfirmDeps $d): Client
    {
        $client = new Client();
        $client->setClientActive(true);
        $client->setClientEmail($email);
        $client->setClientLanguage($language);
        $client->setClientName($pending->getClientName());
        $client->setClientSurname($pending->getClientSurname());
        $client->setClientAddress1($pending->getStreet());
        $client->setClientBuildingNumber($pending->getBuildingNumber());
        $d->cR->save($client);
        return $client;
    }

    private function linkUserClient(int $userId, int $clientId, HomeCareSignupConfirmDeps $d): void
    {
        $userClient = new UserClient();
        $userClient->setUserId($userId);
        $userClient->setClientId($clientId);
        $d->ucR->save($userClient);
    }

    private function assignSignupRole(int $userId, int $userInvId, HomeCareSignupConfirmDeps $d): void
    {
        if ($userId <= 0) {
            return;
        }
        $this->manager->revokeAll((string) $userId);
        $this->manager->assign(
            $userId > 1 ? AppConstants::ROLE_OBSERVER : AppConstants::ROLE_ADMIN,
            (string) $userId,
        );
        $d->urlR->upsert($userInvId, $userId);
    }

    private function createInvoice(
        int $clientId,
        int $productId,
        int $taxRateId,
        int $unitId,
        HomeCarePendingSignup $pending,
        FormHydrator $formHydrator,
        HomeCareSignupConfirmDeps $d,
    ): int {
        $user = $this->activeUser($clientId, $d->uR, $d->ucR, $d->uiR);
        if ($user === null) {
            return 0;
        }
        $groupId = (int) $d->sR->getSetting('default_invoice_group');
        $invoiceBody = [
            'client_id' => $clientId,
            'group_id' => $groupId,
            'status_id' => 2,
            'discount_amount' => 0.00,
            'password' => '',
            'terms' => '',
            'note' => '',
        ];
        $inv = new Inv();
        $form = new InvForm();
        $invId = 0;
        $d->invService->withTransaction(
            function () use (
                $user, $inv, $invoiceBody, $form, $formHydrator, $d,
                $productId, $taxRateId, $unitId, $pending, &$invId
            ): void {
                if (!$formHydrator->populateAndValidate($form, $invoiceBody)) {
                    return;
                }
                $saved = $d->invService->saveInv($user, $inv, $invoiceBody, $d->sR, $d->gR);
                if (!$saved->hasIdentity()) {
                    return;
                }
                // Fresh invoices only get status/number correctly from
                // saveInv() when 'mark_invoices_sent_copy' happens to be on;
                // this flow's invoices must always be sent, deterministically.
                $saved->setStatusId(2);
                if (strlen($saved->getNumber() ?? '') === 0) {
                    $saved->setNumber((string) $d->gR->generateNumber($invoiceBody['group_id'], true));
                }
                $d->iR->save($saved);
                $invId = $saved->reqId();

                $invItem = new InvItem();
                $iiaDeps = new IiAddProductDeps($d->pR, $d->trR, $d->iias, $d->iiaR, $d->sR, $d->unR);
                $itemBody = [
                    'tax_rate_id' => $taxRateId,
                    'product_id' => $productId,
                    'quantity' => 1.00,
                    'price' => $pending->getPrice(),
                    'discount_amount' => 0.00,
                    'product_unit_id' => $unitId,
                ];
                $d->invItemService->addInvItemProduct($invItem, $itemBody, (string) $invId, $iiaDeps);
            }
        );
        return $invId;
    }

    private function recordCashPayment(int $invId, HomeCareSignupConfirmDeps $d): bool
    {
        $cashMethod = $d->pmR->repoPaymentMethodByNameQuery('Cash');
        $invWithAmount = $d->iR->repoInvLoadInvAmountquery($invId);
        $total = $invWithAmount?->getInvAmount()?->getTotal();
        if ($cashMethod === null || $total === null || $total <= 0.00) {
            return false;
        }
        $payment = new Payment();
        $d->paymentService->savePayment($payment, [
            'inv_id' => $invId,
            'payment_method_id' => $cashMethod->reqId(),
            'payment_date' => (new \DateTimeImmutable('now'))->format('Y-m-d'),
            'amount' => $total,
            'note' => 'HomeCare signup — paid cash',
        ]);
        $invEntity = $d->iR->repoInvUnLoadedquery($invId);
        if ($invEntity !== null) {
            $invEntity->setStatusId(4);
            $d->iR->save($invEntity);
        }
        return true;
    }

    private function renderConfirmed(string $outcome, ?string $qrDataUri = null): Response
    {
        return $this->webViewRenderer->render('homecaresignup_confirmed', [
            'outcome' => $outcome,
            'qrDataUri' => $qrDataUri,
        ]);
    }

    /**
     * Duplicated from InvController/QuoteController by design — a small,
     * self-contained "does this client have exactly one active user
     * account" check with no shared base class between the three
     * controllers to hang it off.
     */
    private function activeUser(int $client_id, UR $uR, UCR $ucR, UIR $uiR): ?User
    {
        $user_client = $ucR->repoUserquery($client_id);
        if (null !== $user_client) {
            $user_client_count = $ucR->repoUserquerycount($client_id);
            if ($user_client_count == 1) {
                $user_id = $user_client->reqUserId();
                $user = $uR->findById($user_id);
                $user_inv = $uiR->repoUserInvUserIdquery($user_id);
                if (null !== $user_inv && $user_inv->getActive()) {
                    return $user;
                }
            }
        }
        return null;
    }
}
