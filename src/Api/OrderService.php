<?php

declare(strict_types=1);

namespace App\Api;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Infrastructure\Persistence\User\User;
use App\Infrastructure\Persistence\UserInv\UserInv;
use App\Invoice\Inv\InvForm;

/**
 * Creates a guest order for the external `/api/orders` endpoint (see
 * `docs/WEBSHOP_HEADLESS_STOREFRONT_DESIGN_AUGUST_2026.md`) — finds or
 * creates a Client by the checkout email, then an Inv + InvItems from the
 * cart, mirroring the shape of `HomeCareSignupController::createInvoice()`
 * (its own `saveInv()`/`addInvItemProduct()`/`InvRecalculator` sequence)
 * but for an arbitrary product cart rather than one fixed HomeCare service
 * line, and with no logged-in customer account involved at all — the
 * "creating user" is resolved the same way `InvRecurringCronService::
 * resolveAdminUser()` does for its own unattended/system context (the
 * first admin-type UserInv on file), since a webshop order has no admin
 * session to attribute it to.
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
     */
    public function createOrder(array $customer, array $items): ?string
    {
        $context = $this->resolveOrderContext($customer, $items);
        if ($context === null) {
            return null;
        }

        $client = $this->findOrCreateClient($customer);

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
        return $urlKey === '' ? null : $urlKey;
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
