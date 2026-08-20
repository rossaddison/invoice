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
 * line. The invoice is *authored* by a system/admin account (resolved the
 * same way `InvRecurringCronService::resolveAdminUser()` does for its own
 * unattended context — the first admin-type UserInv on file), since a
 * webshop order has no staff session to attribute it to — but the
 * customer themselves also gets a real account: `/invoice/inv/view/{id}`
 * (like every other invoice page in this app) requires an authenticated,
 * RBAC-permitted session, so `createOrder()` returns a one-time login
 * link (see `WebshopOrderLoginController`) rather than a bare url_key a
 * fresh, never-logged-in customer could never actually reach.
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
        $context = $this->resolveOrderContext($customer, $items);
        if ($context === null) {
            return null;
        }

        $client = $this->findOrCreateClient($customer);
        $invId = $this->createInvoiceAndItems($context, $client, $items);
        if ($invId === null) {
            return null;
        }

        return $this->buildOrderLoginRedirectUrl($client, $invId);
    }

    /**
     * @param array{user: User, groupId: int} $context
     * @param list<array{product_id: int, quantity: float}> $items
     */
    private function createInvoiceAndItems(array $context, Client $client, array $items): ?int
    {
        $invId = null;
        $this->d->invService->withTransaction(
            function () use ($context, $client, $items, &$invId): void {
                $invId = $this->createInvoiceShell($context['user'], $client, $context['groupId']);
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
     * @return array{user: User, groupId: int}|null
     */
    private function resolveOrderContext(array $customer, array $items): ?array
    {
        if ($items === [] || $customer['email'] === '') {
            return null;
        }

        $user = $this->resolveApiOrderUser();
        $groupId = (int) $this->d->sR->getSetting('default_invoice_group');
        if ($user === null || $groupId <= 0) {
            return null;
        }

        return ['user' => $user, 'groupId' => $groupId];
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
     * Resolves (or, for a first-time customer, creates) the account the
     * customer themselves logs into via the one-time link, then issues
     * that link. Distinct from `resolveApiOrderUser()`/`$context['user']`
     * above, which is the *system* account that authored the invoice, not
     * the customer's own.
     */
    private function buildOrderLoginRedirectUrl(Client $client, int $invId): ?string
    {
        $orderUser = $this->resolveOrCreateOrderUser($client);
        if ($orderUser === null) {
            return null;
        }

        $maskedToken = $this->issueOrderLoginToken($orderUser, $invId);
        if ($maskedToken === null) {
            return null;
        }

        return $this->d->urlGenerator->generateAbsolute('webshopOrderLogin', ['token' => $maskedToken]);
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
     * Same lookup InvRecurringCronService::resolveAdminUser() uses for its
     * own unattended context — the first admin-type (type 0) UserInv on
     * file. A webshop order has no logged-in session to attribute the
     * created invoice to, so it borrows the same system-user convention
     * rather than inventing a second one.
     */
    private function resolveApiOrderUser(): ?User
    {
        /** @var UserInv $userInv */
        foreach ($this->d->uiR->findAllPreloaded() as $userInv) {
            if ($userInv->getType() === 0) {
                return $this->d->uR->findById($userInv->reqUserId());
            }
        }
        return null;
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
