<?php

declare(strict_types=1);

namespace App\Auth\Controller;

use App\Auth\AuthService;
use App\Auth\Form\HomeCareSignupForm;
use App\Auth\HomeCareSignupEmailService;
use App\Auth\Trait\ClassList;
use App\Auth\Trait\TurnstileVerification;
use App\Infrastructure\Persistence\CategorySecondary\CategorySecondary;
use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\Dwelling\Dwelling;
use App\Infrastructure\Persistence\HomeCarePendingSignup\HomeCarePendingSignup;
use App\Infrastructure\Persistence\Identity\Identity;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Infrastructure\Persistence\Payment\Payment;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Infrastructure\Persistence\Unit\Unit;
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
use Yiisoft\FormModel\FormHydrator;
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
 * house (Dwelling) from the pending signup's free-text street/building-
 * number fields, links the Dwelling onto the new Client via
 * Client::setDwellingId(), raises the first invoice against the one shared
 * "HomeCare Service" catalog Product (ProductService::findOrCreateHomeCareServiceProduct()
 * — house identity no longer lives on Product), and either records it paid
 * (cash) or leaves it sent/unpaid, so the existing
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
        private readonly HomeCareSignupEmailService $emailService,
        private readonly sR $sR,
        private readonly Translator $translator,
        private readonly UrlGenerator $urlGenerator,
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
        if (!$this->authService->isGuest() || $this->sR->getSetting('stop_homecare_signing_up') === '1') {
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
                $this->emailService->savePendingSignup($userId, $signupForm, $d);
                $redirect = $this->emailService->processSendSignupEmail($user, $currentRoute, $d);
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
        $resolved = $this->resolveAndActivateSignup($tokenMasked, $tokenType, $d);
        if ($resolved instanceof Response) {
            return $resolved;
        }
        ['identity' => $identity, 'userId' => $userId, 'pending' => $pending, 'userInv' => $userInv] = $resolved;

        $finished = $this->resolveDwellingAndProvisionInvoice(
            $identity, $userId, $language, $pending, $userInv, $formHydrator, $d,
        );
        if ($finished instanceof Response) {
            return $finished;
        }
        ['client' => $client, 'invId' => $invId] = $finished;

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
     * Unmasks/parses the token and resolves the identity it belongs to.
     * Split out of resolveAndActivateSignup() purely to keep each method's
     * return count within SonarQube's limit (S1142) — the two failure
     * branches here both redirect to site/index and happen before any
     * pending-signup/token side effects, so merging them costs nothing.
     *
     * @return Response|array{identity: Identity, userId: int, tokenWithoutTimestamp: string, timestamp: string}
     */
    private function resolveIdentityFromToken(
        string $tokenMasked,
        string $tokenType,
        HomeCareSignupConfirmDeps $d,
    ): Response|array {
        $unMaskedToken = TokenMask::remove($tokenMasked);
        $positionFromUnderscore = strrpos($unMaskedToken, '_');
        $timestamp = $positionFromUnderscore !== false
            ? substr($unMaskedToken, $positionFromUnderscore + 1)
            : null;
        $tokenWithoutTimestamp = $timestamp !== null
            ? substr($unMaskedToken, 0, -(strlen($timestamp) + 1))
            : null;
        $identity = $tokenWithoutTimestamp !== null
            ? $d->tR->findIdentityByToken($tokenWithoutTimestamp, $tokenType)
            : null;
        $userId = $identity?->getUser()?->reqId();

        if ($timestamp === null || $tokenWithoutTimestamp === null || $identity === null || $userId === null) {
            return $this->webService->getRedirectResponse('site/index');
        }

        return [
            'identity' => $identity,
            'userId' => $userId,
            'tokenWithoutTimestamp' => $tokenWithoutTimestamp,
            'timestamp' => $timestamp,
        ];
    }

    /**
     * Reads/deletes the pending signup, invalidates the confirmation token,
     * checks expiry, and activates the UserInv — in that exact order, since
     * the pending-delete and token-invalidate side effects must happen
     * before the expiry check regardless of outcome (mirrors the original
     * unsplit method's ordering).
     *
     * @return Response|array{pending: HomeCarePendingSignup, userInv: UserInv}
     */
    private function activateSignupAccount(
        int $userId,
        string $tokenWithoutTimestamp,
        string $tokenType,
        string $timestamp,
        HomeCareSignupConfirmDeps $d,
    ): Response|array {
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

        return ['pending' => $pending, 'userInv' => $userInv];
    }

    /**
     * @return Response|array{identity: Identity, userId: int, pending: HomeCarePendingSignup, userInv: UserInv}
     */
    private function resolveAndActivateSignup(
        string $tokenMasked,
        string $tokenType,
        HomeCareSignupConfirmDeps $d,
    ): Response|array {
        $tokenResult = $this->resolveIdentityFromToken($tokenMasked, $tokenType, $d);
        if ($tokenResult instanceof Response) {
            return $tokenResult;
        }
        $accountResult = $this->activateSignupAccount(
            $tokenResult['userId'],
            $tokenResult['tokenWithoutTimestamp'],
            $tokenType,
            $tokenResult['timestamp'],
            $d,
        );
        if ($accountResult instanceof Response) {
            return $accountResult;
        }
        return [
            'identity' => $tokenResult['identity'],
            'userId' => $tokenResult['userId'],
            'pending' => $accountResult['pending'],
            'userInv' => $accountResult['userInv'],
        ];
    }

    /**
     * Resolves (and possibly creates) the CategorySecondary, then the
     * Family, then the Dwelling (house number under that street), then the
     * TaxRate/Unit — in that exact order, since resolveSecondaryCategoryId()
     * may create a new CategorySecondary row as a side effect that must
     * happen regardless of whether TaxRate/Unit later turn out to be
     * missing (mirrors the original unsplit ordering).
     *
     * HomeCareSignupForm's street/building-number fields stay free text —
     * this resolves them against existing Family/Dwelling rows (or creates
     * new ones) rather than requiring the customer to pick from a
     * pre-populated list. Called before createClient() (unlike the old
     * single-Product flow, which ran this from inside provisionInvoice(),
     * after the Client already existed) so the resolved Dwelling can be set
     * on the Client at creation time via Client::setDwellingId().
     *
     * @return Response|array{dwelling: Dwelling, taxRate: TaxRate, unit: Unit}
     */
    private function resolveDwellingForSignup(HomeCarePendingSignup $pending, HomeCareSignupConfirmDeps $d): Response|array
    {
        $categorySecondaryId = $this->resolveSecondaryCategoryId($pending, $d);
        if ($categorySecondaryId === null) {
            return $this->renderConfirmed('setup_incomplete');
        }
        $family = $d->familyService->findOrCreateByStreetName($pending->getStreet(), $categorySecondaryId);
        $dwelling = $d->dwellingService->findOrCreateDwelling($family->reqId(), $pending->getBuildingNumber());
        $taxRate = $d->trR->repoFirstByIdQuery();
        $unit = $d->unR->repoFirstByIdQuery();
        if ($taxRate === null || $unit === null) {
            return $this->renderConfirmed('setup_incomplete');
        }
        return ['dwelling' => $dwelling, 'taxRate' => $taxRate, 'unit' => $unit];
    }

    /**
     * Resolves the Dwelling, creates and links the Client, and provisions
     * the first invoice — split out of confirm() purely to keep its own
     * return count within SonarQube's limit (S1142), following the same
     * pattern already used for resolveIdentityFromToken()/
     * resolveAndActivateSignup(): each failure branch here happens before
     * any side effect the other doesn't already account for, so merging
     * them into one guard in the caller costs nothing.
     *
     * @return Response|array{client: Client, invId: int}
     */
    private function resolveDwellingAndProvisionInvoice(
        Identity $identity,
        int $userId,
        string $language,
        HomeCarePendingSignup $pending,
        UserInv $userInv,
        FormHydrator $formHydrator,
        HomeCareSignupConfirmDeps $d,
    ): Response|array {
        $dwellingResult = $this->resolveDwellingForSignup($pending, $d);
        if ($dwellingResult instanceof Response) {
            return $dwellingResult;
        }
        ['dwelling' => $dwelling, 'taxRate' => $taxRate, 'unit' => $unit] = $dwellingResult;

        $email = $identity->getUser()?->getEmail() ?? '';
        $client = $this->createClient($email, $language, $pending, $dwelling, $d);
        $clientId = $client->reqId();
        $this->linkUserClient($userId, $clientId, $d);
        $this->assignSignupRole($userId, $userInv->reqId(), $d);

        $provisioned = $this->provisionInvoice($clientId, $dwelling, $taxRate, $unit, $pending, $formHydrator, $d);
        if ($provisioned instanceof Response) {
            return $provisioned;
        }
        return ['client' => $client, 'invId' => $provisioned];
    }

    /**
     * Every HomeCare invoice line now references the one shared "HomeCare
     * Service" catalog Product (ProductService::findOrCreateHomeCareServiceProduct())
     * instead of a Product created per house — house identity lives on
     * Dwelling, resolved earlier in resolveDwellingForSignup() and already
     * linked onto the Client by the time this runs.
     */
    private function provisionInvoice(
        int $clientId,
        Dwelling $dwelling,
        TaxRate $taxRate,
        Unit $unit,
        HomeCarePendingSignup $pending,
        FormHydrator $formHydrator,
        HomeCareSignupConfirmDeps $d,
    ): Response|int {
        $product = $d->productService->findOrCreateHomeCareServiceProduct(
            $dwelling->reqFamilyId(),
            $taxRate->reqId(),
            $unit->reqId(),
        );

        $invId = $this->createInvoice($clientId, $product->reqId(), $taxRate->reqId(), $unit->reqId(), $pending, $formHydrator, $d);
        if ($invId <= 0) {
            return $this->renderConfirmed('setup_incomplete');
        }
        $d->productClientService->syncFromInvItems($clientId, [$product->reqId()]);
        $d->invRecalculator->recalculate($invId);

        return $invId;
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

    /**
     * client_address_1/client_building_number stay populated from the pending signup's free text — kept
     * as a snapshot for PDF/UBL bill-to backward compatibility, same snapshot philosophy this codebase
     * already trusts for InvItem.name from Product.name — alongside the new, authoritative dwelling_id
     * link.
     */
    private function createClient(
        string $email,
        string $language,
        HomeCarePendingSignup $pending,
        Dwelling $dwelling,
        HomeCareSignupConfirmDeps $d,
    ): Client {
        $client = new Client();
        $client->setClientActive(true);
        $client->setClientEmail($email);
        $client->setClientLanguage($language);
        $client->setClientName($pending->getClientName());
        $client->setClientSurname($pending->getClientSurname());
        $client->setClientAddress1($pending->getStreet());
        $client->setClientBuildingNumber($pending->getBuildingNumber());
        $client->setDwellingId($dwelling->reqId());
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
