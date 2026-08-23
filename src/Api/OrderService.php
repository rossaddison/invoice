<?php

declare(strict_types=1);

namespace App\Api;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Infrastructure\Persistence\Token\Token;
use App\Infrastructure\Persistence\User\User;
use App\Infrastructure\Persistence\UserClient\UserClient;
use App\Infrastructure\Persistence\UserInv\UserInv;
use App\Invoice\AppConstants;
use App\Invoice\Inv\InvForm;
use Yiisoft\Security\Random;
use Yiisoft\Security\TokenMask;

/**
 * Creates a guest order for the external `/api/orders` endpoint (see
 * `docs/WEBSHOP_HEADLESS_STOREFRONT_DESIGN_AUGUST_2026.md`) — finds or
 * creates a Client by the checkout email, then an Inv + InvItems from the
 * cart, mirroring the shape of `HomeCareSignupController::createInvoice()`
 * (its own `saveInv()`/`addInvItemProduct()`/`InvRecalculator` sequence)
 * but for an arbitrary product cart rather than one fixed HomeCare service
 * line.
 *
 * The invoice is *authored* by the customer's own account — resolved (or,
 * for a first-time customer, created) up front via
 * `resolveOrCreateOrderUser()`, before the invoice itself exists — rather
 * than a system/admin account. This matches the existing
 * `InvController::activeUser()` convention (the Add/Credit/MultipleCopy
 * invoice-creation flows already resolve the client's own single linked
 * User this same way and use it as the new invoice's author) and is
 * required for `InvController::rbacObserver()`'s own-invoice check
 * (`$inv->reqUserId() === $this->userService->getUser()?->reqId()`) to
 * ever pass: an invoice authored by a system/admin account can never be
 * "this user's own invoice" for any real customer, so a customer
 * following the one-time login link this class hands back would 404 on
 * `/invoice/inv/view/{id}` even though the order itself was created
 * successfully — confirmed live before this fix. `InvService::saveInv()`
 * (via `initNewInvFields()`) only ever uses its `$user` argument for
 * `Inv::setUserId()` — no permission check runs against it at save time,
 * those all happen at *view* time via `rbacObserver()` — so passing the
 * customer's own (observer-role) account here is safe. `/invoice/inv/view/{id}`
 * still requires an authenticated, RBAC-permitted session, so
 * `createOrder()` returns a one-time login link (see
 * `WebshopOrderLoginController`) rather than a bare url_key a fresh,
 * never-logged-in customer could never actually reach.
 *
 * Item prices are always the product's own current `product_price` —
 * never trusted from the storefront's request body — so a tampered cart
 * payload can change quantities but never charge a different amount per
 * unit than what this app's own catalog says.
 */
final class OrderService
{
    public function __construct(private readonly OrderServiceDeps $d)
    {
    }

    /**
     * @param array{name: string, surname: string, email: string, address1?: string,
     *     address2?: string, city?: string, zip?: string, country?: string, phone?: string} $customer
     * @param list<array{product_id: int, quantity: float}> $items
     * @return string|null Absolute one-time login link, or null on failure.
     */
    public function createOrder(array $customer, array $items): ?string
    {
        $groupId = $this->resolveOrderGroupId($customer, $items);
        if ($groupId === null) {
            return null;
        }

        $client = $this->findOrCreateClient($customer);
        $orderUser = $this->resolveOrCreateOrderUser($client);
        if ($orderUser === null) {
            return null;
        }

        $invId = $this->createInvoiceAndItems($orderUser, $groupId, $client, $items);
        if ($invId === null) {
            return null;
        }

        return $this->buildOrderLoginRedirectUrl($orderUser, $invId);
    }

    /** @param list<array{product_id: int, quantity: float}> $items */
    private function createInvoiceAndItems(User $orderUser, int $groupId, Client $client, array $items): ?int
    {
        $invId = null;
        $this->d->invService->withTransaction(
            function () use ($orderUser, $groupId, $client, $items, &$invId): void {
                $invId = $this->createInvoiceShell($orderUser, $client, $groupId);
                if ($invId === null) {
                    return;
                }
                foreach ($items as $item) {
                    $this->addOrderItem($invId, $item);
                }
            },
        );
        if ($invId === null) {
            return null;
        }

        $urlKey = $this->finalizeInvAmount($invId);
        return ($urlKey === null || $urlKey === '') ? null : $invId;
    }

    /**
     * @param array{name: string, surname: string, email: string, address1?: string,
     *     address2?: string, city?: string, zip?: string, country?: string, phone?: string} $customer
     * @param list<array{product_id: int, quantity: float}> $items
     */
    private function resolveOrderGroupId(array $customer, array $items): ?int
    {
        if ($items === [] || $customer['email'] === '') {
            return null;
        }

        $groupId = (int) $this->d->sR->getSetting('default_invoice_group');
        return $groupId > 0 ? $groupId : null;
    }

    /**
     * Sums the InvItemAmount rows `addInvItemProduct()` already created
     * (NumberHelper::invCalculateTotalsofItemTotals() — the same summation
     * `InvRecalculator`/`NumberHelper::calculateInv()` itself uses) and
     * writes the result onto the invoice's own InvAmount object directly,
     * rather than going through `InvRecalculator::recalculate()`.
     *
     * A fresh webshop order never has invoice-level allowances/charges or
     * invoice-level tax rates (`InvAllowanceCharge`/`InvTaxRate` rows —
     * both are added by hand after creation, never at creation time), so
     * the item-level sum alone is already the correct invoice total; no
     * loss of correctness versus the fuller `calculateInv()` for this
     * specific case.
     *
     * Bypassing `calculateInv()` here is deliberate, not a shortcut of
     * convenience: it internally re-fetches InvAmount via a plain
     * `InvAmountRepository::repoInvquery()` (no eager `inv` load) and then
     * saves it — confirmed live that this throws a real Cycle ORM
     * `NullException` on the `InvAmount`.`inv` BelongsTo relation for a
     * same-request, freshly created invoice. Setting `inv` explicitly here
     * (`repoInvLoadInvAmountquery()`'s own `Inv` object, not a second
     * separate fetch) sidesteps the relation entirely rather than relying
     * on Cycle's lazy-loading/identity-map behaviour resolving it.
     */
    private function finalizeInvAmount(int $invId): ?string
    {
        $inv = $this->d->iR->repoInvLoadInvAmountquery($invId);
        $invAmount = $inv?->getInvAmount();
        if ($inv === null || $invAmount === null) {
            return null;
        }

        /** @var array{subtotal: float, tax_total: float, discount: float, charge: float, allowance: float, total: float} $totals */
        $totals = $this->d->numberHelper->invCalculateTotalsofItemTotals(
            $invId,
            $this->d->iiR,
            $this->d->iiaDeps->iiaR,
        );
        $itemSubtotal = $totals['subtotal'] - $totals['discount'];

        $invAmount->setInv($inv);
        $invAmount->setItemSubtotal($itemSubtotal);
        $invAmount->setItemTaxTotal($totals['tax_total']);
        $invAmount->setTaxTotal(0.00);
        $invAmount->setTotal($totals['total']);
        $invAmount->setPaid(0.00);
        $invAmount->setBalance($totals['total']);
        $this->d->iaR->save($invAmount);

        return $inv->getUrlKey();
    }

    /**
     * Issues the one-time login link for `$orderUser` — the same account
     * `resolveOrCreateOrderUser()` resolved earlier in `createOrder()`
     * (before invoice creation, so it could also be used as the invoice's
     * own `user_id`; see the class docblock).
     */
    private function buildOrderLoginRedirectUrl(User $orderUser, int $invId): ?string
    {
        $maskedToken = $this->issueOrderLoginToken($orderUser, $invId);
        if ($maskedToken === null) {
            return null;
        }

        // Every route lives under Group::create('/{_language}')
        // (config/common/di/router.php), so `_language` is a required
        // generate() argument despite UrlGeneratorInterface's own
        // `setDefaultArgument('_language', 'en')` — that default only
        // ever applies to the *matching* side (inbound requests), not
        // to generate()'s own required-argument check, confirmed live:
        // omitting it here throws `Route \`webshopOrderLogin\` expects
        // at least argument values for [_language,token], but received
        // [token]` and silently fails the entire order (OrdersController
        // catches this as "could not place your order" — no invoice
        // ever gets created). This endpoint has no real request-locale
        // context to inherit anyway — it's the server-to-server
        // POST /api/orders call, not a browser navigation — so 'en'
        // (the same value the default above already intends) is the
        // correct, not just convenient, value to pass explicitly.
        return $this->d->urlGenerator->generateAbsolute(
            'webshopOrderLogin',
            ['_language' => 'en', 'token' => $maskedToken],
        );
    }

    /**
     * Reuses the existing linked account on a repeat order from the same
     * email (same `UserClientRepository::repoUserquery()` lookup
     * `InvController::activeUser()` already relies on elsewhere), rather
     * than creating a duplicate `User` per order.
     */
    private function resolveOrCreateOrderUser(Client $client): ?User
    {
        $existingLink = $this->d->ucR->repoUserquery($client->reqId());
        if ($existingLink !== null) {
            return $this->d->uR->findById($existingLink->reqUserId());
        }

        return $this->createOrderUser($client);
    }

    /**
     * Mirrors `UserInvController::handleSignupClientAssignment()`/
     * `assignSignupRole()`'s User+UserInv+UserClient+RBAC sequence for the
     * existing self-service signup flow — same account shape (RBAC role
     * `observer`), just system-created rather than filled in by hand. The
     * password is a random value never surfaced to the customer: they only
     * ever arrive via the one-time login link, never a password form.
     * `UserInv::active` is set true immediately, unlike that existing
     * flow's email-verification-gated `false` — this is a same-transaction,
     * server-verified context already, not an untrusted public form.
     */
    private function createOrderUser(Client $client): ?User
    {
        $email = $client->getClientEmail();
        if ($email === '' || $this->d->uR->findByLogin($email) !== null) {
            return null;
        }

        $user = new User($email, $email, Random::string(32));
        $this->d->uR->save($user);

        $userInv = new UserInv();
        $userInv->setUserId($user->reqId());
        $userInv->setType(1);
        $userInv->setActive(true);
        $userInv->setLanguage('en');
        $this->d->uiR->save($userInv);

        $userClient = new UserClient();
        $userClient->setUserId($user->reqId());
        $userClient->setClientId($client->reqId());
        $this->d->ucR->save($userClient);

        $this->d->manager->revokeAll((string) $user->reqId());
        $this->d->manager->assign(AppConstants::ROLE_OBSERVER, (string) $user->reqId());
        $this->d->urlR->upsert($userInv->reqId(), $user->reqId());

        return $user;
    }

    /**
     * Same masked-token-plus-timestamp shape as
     * `SignupController::getEmailVerificationToken()`/
     * `htmlBodyWithMaskedRandomAndTimeTokenLink()` — a random 32-char
     * token concatenated with its creation timestamp, then masked. The
     * stored `Token`->type is this constant plus ':{inv_id}' rather than
     * the bare constant, so `WebshopOrderLoginController` can recover
     * which invoice to redirect to directly from the token row itself.
     */
    private function issueOrderLoginToken(User $user, int $invId): ?string
    {
        $identityId = $user->getIdentity()->getId();
        if ($identityId === null) {
            return null;
        }

        $token = new Token((int) $identityId, AppConstants::TOKEN_TYPE_WEBSHOP_ORDER_LOGIN . ':' . $invId);
        $this->d->tR->save($token);

        $rawToken = $token->getToken();
        if ($rawToken === null) {
            return null;
        }

        return TokenMask::apply($rawToken . '_' . (string) $token->getCreatedAt()->getTimestamp());
    }

    /**
     * @param array{name: string, surname: string, email: string, address1?: string,
     *     address2?: string, city?: string, zip?: string, country?: string, phone?: string} $customer
     */
    private function findOrCreateClient(array $customer): Client
    {
        $existing = $this->d->cR->findByEmail($customer['email']);
        if ($existing !== null) {
            return $existing;
        }

        $client = new Client();
        $client->setClientActive(true);
        $client->setClientEmail($customer['email']);
        $client->setClientName($customer['name']);
        $client->setClientSurname($customer['surname']);
        $client->setClientAddress1($customer['address1'] ?? '');
        $client->setClientAddress2($customer['address2'] ?? '');
        $client->setClientCity($customer['city'] ?? '');
        $client->setClientZip($customer['zip'] ?? '');
        $client->setClientCountry($customer['country'] ?? '');
        $client->setClientPhone($customer['phone'] ?? '');
        $this->d->cR->save($client);

        return $client;
    }

    private function createInvoiceShell(User $user, Client $client, int $groupId): ?int
    {
        $inv = new Inv();
        $form = new InvForm();
        $invoiceBody = [
            'client_id' => $client->reqId(),
            'group_id' => $groupId,
            'status_id' => 2,
            'discount_amount' => 0.00,
            'password' => '',
            'terms' => '',
            'note' => 'Webshop order',
        ];
        if (!$this->d->formHydrator->populateAndValidate($form, $invoiceBody)) {
            return null;
        }

        $saved = $this->d->invService->saveInv($user, $inv, $invoiceBody, $this->d->sR, $this->d->gR);
        if (!$saved->hasIdentity()) {
            return null;
        }
        // Mirrors HomeCareSignupController::createInvoice() — saveInv()
        // only assigns a number/deterministic 'sent' state under specific
        // Setting-driven conditions; a webshop order must always get both.
        $saved->setStatusId(2);
        if (strlen($saved->getNumber() ?? '') === 0) {
            $saved->setNumber((string) $this->d->gR->generateNumber($groupId, true));
        }
        $this->d->iR->save($saved);

        return $saved->reqId();
    }

    /** @param array{product_id: int, quantity: float} $item */
    private function addOrderItem(int $invId, array $item): void
    {
        $product = $this->d->pR->repoProductquery($item['product_id']);
        if ($product === null) {
            return;
        }

        $invItem = new InvItem();
        $itemBody = [
            'tax_rate_id' => $product->reqTaxRateId(),
            'product_id' => $product->reqId(),
            'quantity' => $item['quantity'],
            'price' => $product->getProductPrice() ?? 0.00,
            'discount_amount' => 0.00,
            'product_unit_id' => $product->reqUnitId(),
        ];
        $this->d->invItemService->addInvItemProduct($invItem, $itemBody, (string) $invId, $this->d->iiaDeps);
    }
}
