<?php

declare(strict_types=1);

namespace App\Invoice\HomeCareRunImport;

use App\Infrastructure\Persistence\CategorySecondary\CategorySecondary;
use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\Dwelling\Dwelling;
use App\Infrastructure\Persistence\Family\Family;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\User\User;
use App\Invoice\InvItem\IiAddProductDeps;

/**
 * Saves a parsed run. The run becomes a category secondary, each street a family under it, each
 * house a dwelling on that street with a client created for it (the client's dwelling_id pointing
 * at the dwelling), and each client gets a draft invoice with the one standard product.
 *
 * Safe to run again on the same run: a house whose dwelling already has a client is left alone, so
 * a re-import adds only the new houses. Cancelled and do-not-clean houses are not created.
 */
final readonly class RunImportService
{
    public const string SOURCE = 'run_import';

    public function __construct(private RunImportDeps $d)
    {
    }

    /**
     * @param int|null $runId an existing run to put the houses under; null creates one
     * @param string $runName the new run's name (used only when $runId is null)
     * @param int $categoryPrimaryId the category a new run is filed under
     * @param float|null $price the price of each house's invoice line; null uses the product's price
     */
    public function save(
        ParsedRun $run,
        ?int $runId,
        string $runName,
        int $categoryPrimaryId,
        Product $product,
        ?float $price,
        User $user,
    ): RunImportResult {
        $productId = $product->reqId();
        $userId = $user->reqId();
        $runCreated = null === $runId;
        if (null === $runId) {
            $category = new CategorySecondary();
            $this->d->csService->saveCategorySecondary($category, [
                'category_primary_id' => (string) $categoryPrimaryId,
                'name' => $runName,
            ]);
            $runId = $category->reqId();
        }

        $streetsCreated = 0;
        $housesCreated = 0;
        $alreadyThere = [];
        $leftOut = [];
        $familyIds = [];
        foreach ($run->segments as $segment) {
            if (!isset($familyIds[$segment->street])) {
                $isNew = null === $this->d->fR->repoFamilyByNameAndSecondaryCategoryQuery($segment->street, $runId);
                $family = $this->d->familyService->findOrCreateByStreetName($segment->street, $runId);
                $familyIds[$segment->street] = $family->reqId();
                $streetsCreated += $isNew ? 1 : 0;
            }
            foreach ($segment->houses as $house) {
                $label = $house->number . ' ' . $segment->street;
                if ($house->isInactive()) {
                    $leftOut[] = $label;
                    continue;
                }
                $dwelling = $this->dwelling($familyIds[$segment->street], $house, $segment->postcode);
                if ($this->d->cdR->isClaimedByAnother($dwelling->reqId(), null)) {
                    $alreadyThere[] = $label;
                    continue;
                }
                $client = $this->client($house, $label, $segment, $dwelling);
                try {
                    $this->draftInvoice($client, $productId, $house->price ?? $price, $userId);
                } catch (\Throwable $failure) {
                    // a client left linked to the dwelling would make a re-import skip the house as
                    // already there, so it never gets its invoice
                    try {
                        $this->d->cR->delete($client);
                    } catch (\Throwable) {
                        // the clean-up must not hide why the house failed
                    }
                    throw $failure;
                }
                ++$housesCreated;
            }
        }
        // streets already ordered keep their place; the streets met for the first time follow them in
        // the order they were met (a new run has no earlier order, so it is just the order met)
        $this->d->familyService->saveStreetOrders(
            $this->mergedStreetOrder($runCreated ? [] : $this->existingStreetOrder($runId), array_values($familyIds))
        );

        return new RunImportResult($runId, $runCreated, $streetsCreated, $housesCreated, $alreadyThere, $leftOut);
    }

    /**
     * The run's streets in their saved order (streets never ordered come last, by id).
     *
     * @return list<int>
     */
    private function existingStreetOrder(int $runId): array
    {
        $rows = [];
        /** @var iterable<Family> $families */
        $families = $this->d->fR->repoCategorySecondaryIdQuery($runId);
        foreach ($families as $family) {
            $order = $family->getStreetSortOrder();
            $rows[] = [null === $order || $order < 1 ? PHP_INT_MAX : $order, $family->reqId()];
        }
        usort($rows, static fn (array $a, array $b): int => $a <=> $b);
        return array_map(static fn (array $row): int => $row[1], $rows);
    }

    /**
     * @param list<int> $existing
     * @param list<int> $met
     * @return list<int>
     */
    private function mergedStreetOrder(array $existing, array $met): array
    {
        return array_values(array_unique([...$existing, ...$met]));
    }

    private function dwelling(int $familyId, RunHouse $house, ?string $postcode): Dwelling
    {
        $dwelling = $this->d->dwellingService->findOrCreateDwelling($familyId, $house->number);
        if (null !== $postcode && '' === $dwelling->getPostcode()) {
            $dwelling->setPostcode(str_replace(' ', '', $postcode));
        }
        if ($house->has(RunHouse::FLAG_PULL_POINT)) {
            $dwelling->setPullPoint(true);
        }
        if (null === $dwelling->getSource() || 'signup_freehand' === $dwelling->getSource()) {
            $dwelling->setSource(self::SOURCE);
        }
        $this->d->dwR->save($dwelling);
        return $dwelling;
    }

    private function client(RunHouse $house, string $label, RunSegment $segment, Dwelling $dwelling): Client
    {
        $client = new Client();
        $client->setClientActive(true);
        $client->setClientName('' === $house->clientName ? $label : $house->clientName);
        if ('' !== $house->phone) {
            $client->setClientPhone($house->phone);
        }
        $client->setClientAddress1($segment->street);
        $client->setClientBuildingNumber($house->number);
        if (null !== $segment->postcode) {
            $client->setClientZip($segment->postcode);
        }
        $client->setDwellingId($dwelling->reqId());
        $this->d->cR->save($client);
        return $client;
    }

    private function draftInvoice(Client $client, int $productId, ?float $price, int $userId): void
    {
        // Each invoice is made with entities fetched fresh: the unit of work is emptied after every
        // invoice (see below), so a user or product held from the previous house is no longer known to it.
        $product = $this->d->pR->repoProductquery($productId);
        $user = $this->d->orm->getRepository(User::class)->findByPK($userId);
        if (null === $product || !$user instanceof User) {
            throw new \RuntimeException('The product or the user for the import is gone.');
        }
        $taxRate = $product->getTaxRate() ?? $this->d->trR->repoFirstByIdQuery();
        $unit = $product->getUnit() ?? $this->d->unR->repoFirstByIdQuery();
        $clientId = $client->reqId();
        $invId = 0;
        $this->d->invService->withTransaction(function () use ($product, $price, $user, $taxRate, $unit, $clientId, &$invId): void {
            $body = [
                'client_id' => $clientId,
                'group_id' => (int) $this->d->sR->getSetting('default_invoice_group'),
                'status_id' => 1,
                'discount_amount' => 0.00,
                'password' => '',
                'terms' => '',
                'note' => '',
            ];
            $inv = $this->d->invService->saveInv($user, new Inv(), $body, $this->d->sR, $this->d->gR);
            $invId = $inv->reqId();
            $itemBody = [
                'tax_rate_id' => null === $taxRate ? 0 : $taxRate->reqId(),
                'product_id' => $product->reqId(),
                'quantity' => 1.00,
                'price' => $price ?? ($product->getProductPrice() ?? 0.00),
                'discount_amount' => 0.00,
                'product_unit_id' => null === $unit ? 0 : $unit->reqId(),
            ];
            $deps = new IiAddProductDeps($this->d->pR, $this->d->trR, $this->d->iias, $this->d->iiaR, $this->d->sR, $this->d->unR);
            $this->d->invItemService->addInvItemProduct(new InvItem(), $itemBody, (string) $invId, $deps);
        });
        // The new invoice's amount row was created through the invoice (an invoice always carries one),
        // and the unit of work still holds it without the link back to its invoice, so saving the
        // recalculated totals onto it fails ("invAmount.inv can not be null"). Emptying the unit of
        // work makes the recalculation load the row from the database like any other request would.
        $this->d->orm->getHeap()->clean();
        $this->d->productClientService->syncFromInvItems($clientId, [$productId]);
        $this->d->invRecalculator->recalculate($invId);
    }
}
