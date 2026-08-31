<?php

declare(strict_types=1);

namespace App\Invoice\Peppol\Console;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\ClientPeppol\ClientPeppol;
use App\Infrastructure\Persistence\DeliveryLocation\DeliveryLocation;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Infrastructure\Persistence\InvItemAmount\InvItemAmount;
use App\Infrastructure\Persistence\PostalAddress\PostalAddress;
use App\Infrastructure\Persistence\Product\Product;
use App\Invoice\Helpers\Peppol\PeppolHelper;
use App\Invoice\Helpers\Peppol\PeppolHelperChargeDeps;
use App\Invoice\Helpers\Peppol\PeppolHelperInvDeps;
use App\Invoice\Helpers\Peppol\PeppolHelperNetDeps;
use App\Invoice\Setting\SettingRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\Console\ExitCode;

/**
 * TEST-SUPPORT TOOL -- not for production use.
 *
 * Seeds (or tears down) a complete, clearly-labelled real domain fixture
 * -- Client, PostalAddress, ClientPeppol, DeliveryLocation, Product, Inv,
 * InvItem, InvItemAmount, InvAmount -- sufficient for
 * PeppolHelper::generateInvoicePeppolUblXmlTempFile() to run its real,
 * unmocked UBL-XML generation successfully end to end. Backs the
 * Codeception Acceptance happy-path test for InvController::peppol()/
 * peppolSend(), which needs a genuinely valid invoice -- something no
 * amount of Testo/Mockery unit testing can substitute for (see
 * PeppolTransmitDocumentTest / PeppolSendTest's own docblocks for why
 * that boundary was drawn where it was).
 *
 * Every row this creates is named "__TEST_PEPPOL_FIXTURE__..." so it's
 * unmistakable in the real app UI if cleanup is ever missed, and
 * --cleanup=<id> reverses everything in FK-safe order. Reuses existing
 * reference data (TaxRate, UnitPeppol, Group) rather than creating more
 * of it -- this fixture only owns rows it created itself.
 *
 * The fixture's Client is created active (see seed()'s own comment):
 * InvRepository's base query deliberately hides every invoice belonging to
 * an inactive client -- a data-protection control, not a Cycle bug -- and
 * that filter applies to every lookup on the repository, including by id.
 * A prior version of this fixture left the client inactive (the entity's
 * own default) and mistook the resulting "not found" for a genuine Cycle
 * staleness bug; root-caused and fixed 2026-08-31.
 *
 * --generate-only (seed then generate in the same process) previously
 * failed with "Missing invoice lines" even though --generate-existing
 * against the same freshly-seeded id worked fine right after, in a
 * separate process. Root cause: Cycle's identity map hands the
 * same-process re-query back the SAME $inv object (by class + id)
 * seed() already had in scope, rather than a freshly-hydrated one -- and
 * that object's own in-memory items collection was never updated when
 * the item was created, only persisted to the DB. Fixed by keeping it in
 * sync (see seed()'s own comment) -- 2026-08-31.
 */
final class SeedPeppolHappyPathFixtureCommand extends Command
{
    protected static string $defaultName = 'peppol/seed-happy-path-fixture';

    private const string MARKER = '__TEST_PEPPOL_FIXTURE__';
    private const int GROUP_ID = 1;
    private const int TAX_RATE_ID = 1;
    private const int UNIT_PEPPOL_ID = 1;
    private const int FAMILY_ID = 1;
    private const int UNIT_ID = 1;

    public function __construct(
        private readonly SeedFixtureDeps $deps,
        private readonly SettingRepository $sR,
        private readonly TranslatorInterface $translator,
    ) {
        parent::__construct();
    }

    #[\Override]
    public function configure(): void
    {
        $this
            ->setDescription('TEST-SUPPORT ONLY: seed or clean up a real Peppol happy-path fixture.')
            ->addOption('cleanup', null, InputOption::VALUE_REQUIRED, 'Inv id to delete, undoing a previous seed.')
            ->addOption('generate-only', null, InputOption::VALUE_NONE, 'Seed, then call PeppolHelper directly (no HTTP) and report the result.')
            ->addOption('generate-existing', null, InputOption::VALUE_REQUIRED, 'Skip seeding; call PeppolHelper directly for an already-seeded Inv id.');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $cleanupId = $input->getOption('cleanup');
        if ($cleanupId !== null) {
            $this->cleanup((int) $cleanupId, $io);
            return ExitCode::OK;
        }

        $existingId = $input->getOption('generate-existing');
        if ($existingId !== null) {
            $this->tryGenerate((int) $existingId, $io);
            return ExitCode::OK;
        }

        $invId = $this->seed($io);

        if ($input->getOption('generate-only')) {
            $this->tryGenerate($invId, $io);
        }

        $io->writeln("INV_ID={$invId}");
        return ExitCode::OK;
    }

    private function seed(SymfonyStyle $io): int
    {
        $client = new Client(client_name: self::MARKER . ' Client');
        // Active by design, not an oversight: InvRepository's base query
        // (see its constructor) deliberately hides every invoice belonging
        // to an inactive client -- a data-protection control, not a bug.
        // client_active defaults to false, so an inactive fixture client's
        // invoice is invisible to InvRepository's own lookups (including
        // by id) exactly as intended elsewhere in the app. This fixture's
        // happy-path test isn't exercising that lockout, so make the
        // client active. Root-caused 2026-08-31, resolving this file's own
        // former "freshly-created Inv unqueryable" KNOWN ISSUE below --
        // it was never a Cycle staleness bug.
        $client->setClientActive(true);
        $this->deps->clientRepo->save($client);
        $clientId = $client->reqId();

        $postalAddress = new PostalAddress(
            client_id: $clientId,
            street_name: 'Test Street',
            additional_street_name: '',
            building_number: '1',
            city_name: 'Test City',
            postalzone: 'AB1 2CD',
            countrysubentity: 'Test County',
            country: 'GB',
        );
        $this->deps->paR->save($postalAddress);
        // buildPeppolAccountingCustomerPartyArray() (the AccountingCustomerParty
        // builder) looks up the buyer's postal address via
        // Client::getPostaladdressId(), not by client_id -- so the Client
        // row itself has to point back at this PostalAddress, or it throws
        // PeppolBuyerPostalAddressNotFoundException. Found 2026-08-31.
        $client->setPostaladdressId($postalAddress->reqId());
        $this->deps->clientRepo->save($client);

        $clientPeppol = new ClientPeppol(
            client_id: $clientId,
            endpointid: '9999999999999',
            endpointid_schemeid: '9999',
            identificationid: 'ID1',
            identificationid_schemeid: '9999',
            taxschemecompanyid: 'GB123456789',
            taxschemeid: 'VAT',
            legal_entity_registration_name: self::MARKER . ' Client',
            legal_entity_companyid: 'CO1',
            legal_entity_companyid_schemeid: '9999',
            legal_entity_company_legal_form: 'LTD',
            financial_institution_branchid: 'BR1',
            accounting_cost: 'AC1',
            supplier_assigned_accountid: 'SA1',
        );
        $this->deps->cpR->save($clientPeppol);

        $deliveryLocation = new DeliveryLocation();
        $deliveryLocation->setClientId($clientId);
        $deliveryLocation->setName(self::MARKER . ' Delivery Location');
        $deliveryLocation->setAddress1('Test Street');
        $deliveryLocation->setAddress2('');
        $deliveryLocation->setBuildingNumber('1');
        $deliveryLocation->setCity('Test City');
        $deliveryLocation->setState('Test County');
        $deliveryLocation->setZip('AB1 2CD');
        $deliveryLocation->setCountry('GB');
        // Required by PeppolHelper::buildDeliveryLocationIDScheme() --
        // throws PeppolDeliveryLocationIDNotFoundException without a GLN.
        $deliveryLocation->setGlobalLocationNumber('9999999999999');
        $deliveryLocation->setElectronicAddressScheme('0088');
        $this->deps->dlR->save($deliveryLocation);
        $deliveryLocationId = $deliveryLocation->reqId();

        $product = new Product(
            product_name: self::MARKER . ' Product',
            product_description: self::MARKER . ' Product',
        );
        $product->setUnitPeppolId(self::UNIT_PEPPOL_ID);
        $product->setFamilyId(self::FAMILY_ID);
        $product->setTaxRateId(self::TAX_RATE_ID);
        $product->setUnitId(self::UNIT_ID);
        // Required by PeppolHelperInvoiceLineTrait::validateInvItem() --
        // throws PeppolProductItemClassificationCodeSchemeIdNotFoundException
        // without a listid.
        $product->setProductIccListid('SRV');
        $product->setProductIccId('9999999');
        $this->deps->productRepo->save($product);
        $productId = $product->reqId();

        $inv = new Inv(
            client_id: $clientId,
            user_id: 1,
            group_id: self::GROUP_ID,
            so_id: 0,
            payment_method: 6,
            note: self::MARKER . ' Please use our latest telephone number',
            postal_address_id: $postalAddress->reqId(),
            delivery_location_id: $deliveryLocationId,
            client_po_number: self::MARKER . '-PO-1',
            client_po_person: self::MARKER . ' PO Person',
        );
        // client_id/user_id/group_id above only set Inv's raw scalar
        // columns -- Inv::$client/$user/$group are separate
        // BelongsTo(nullable: false) relations that Cycle actually persists
        // from, and constructor args never touch them. Found 2026-08-30
        // fixing the identical shape blocking SalesOrder -> Invoice
        // conversion (see InvService::persist(), PR #1149): without the
        // object set, Cycle silently wrote no row at all for $inv here too
        // -- the leading suspect for this file's own still-parked
        // "freshly-created Inv unqueryable" mystery (see the KNOWN ISSUE
        // comment on tryGenerate() below).
        $inv->setClient($client);
        $inv->setGroup($this->deps->gR->repoGroupquery(self::GROUP_ID));
        $inv->setUser($this->deps->uR->findById(1));
        $this->deps->invRepo->save($inv);
        $invId = $inv->reqId();

        $invItem = new InvItem(
            name: self::MARKER . ' Item',
            description: self::MARKER . ' Item',
            quantity: 1.0,
            price: 100.0,
            discount_amount: 0.0,
            inv_id: $invId,
            tax_rate_id: self::TAX_RATE_ID,
        );
        $invItem->setProductId($productId);
        // Same shape as Inv::$client/$user/$group above: InvItem::$product
        // is a BelongsTo relation Cycle actually reads from -- without the
        // object set, PeppolHelperInvoiceLineTrait::buildInvoiceLinesArray()
        // sees $item->getProduct() === null and silently `continue`s past
        // this item, leaving invoiceLines empty ("Missing invoice lines").
        $invItem->setProduct($product);
        // Same reasoning as setProduct() above, plus the inverse side:
        // Cycle's identity map hands back the SAME $inv object (by class
        // + id) to a same-process re-query rather than a freshly-hydrated
        // one, so $inv's own in-memory items collection -- never updated
        // when this item was created, only persisted to the DB -- stays
        // empty for that re-query. --generate-existing (a separate
        // process, no prior heap entry) never hit this, which is what
        // made it look process-dependent. Found & fixed 2026-08-31.
        $invItem->setInv($inv);
        $this->deps->iiR->save($invItem);
        $invItemId = $invItem->reqId();
        $inv->getItems()->add($invItem);

        $invItemAmount = new InvItemAmount(
            inv_item_id: $invItemId,
            subtotal: 100.0,
            tax_total: 0.0,
            // Required by NumberHelper::invCalculateTotalsofItemTotals() --
            // it sums getTotal(), a separate field from subtotal/tax_total,
            // to build LegalMonetaryTotal/PayableAmount. Left at its 0.00
            // default here, PayableAmount stayed 0.00 regardless of the
            // real invoice total -- rejected by real Peppol validation
            // (BR-CO-14, found 2026-08-31 via the real HTTP Peppol route's
            // validator).
            total: 100.0,
        );
        $this->deps->iiaR->save($invItemAmount);

        $invAmount = $inv->getInvAmount();
        $invAmount->setInv($inv);
        $invAmount->setInvId($invId);
        $invAmount->setItemSubtotal(100.0);
        $invAmount->setItemTaxTotal(0.0);
        $this->deps->iaR->save($invAmount);

        $io->writeln("Seeded fixture: client={$clientId}, inv={$invId}, item={$invItemId}, product={$productId}, deliveryLocation={$deliveryLocationId}");

        return $invId;
    }

    private function tryGenerate(int $invId, SymfonyStyle $io): void
    {
        // Root-caused 2026-08-31 (was logged here as an unresolved "row
        // exists but every query returns null" mystery): InvRepository's
        // constructor bakes an INNER JOIN client ... WHERE
        // client.client_active = 1 into the repository's own base query,
        // so every method on it -- including a bare
        // select()->where(['id' => $id]) -- silently excludes any invoice
        // whose client isn't active. That's a deliberate data-protection
        // control (deactivating a client locks down their invoices), not a
        // Cycle bug -- confirmed with the repo owner. seed() now creates
        // its Client active so this path is exercised normally; a
        // still-inactive Inv id here (e.g. --generate-existing against one)
        // is expected to 404, not a regression.
        $inv = $this->deps->invRepo->repoInvLoadInvAmountquery($invId);
        if ($inv === null) {
            $io->error("Inv #{$invId} not found -- its client may be inactive (see comment above), or the id doesn't exist.");
            return;
        }
        $delloc = $this->deps->dlR->repoDeliveryLocationquery((int) $inv->getDeliveryLocationId());
        if ($delloc === null) {
            $io->error('DeliveryLocation not found after seeding.');
            return;
        }

        $peppolHelper = new PeppolHelper(
            $this->sR,
            $this->deps->delRepo,
            $inv->getInvAmount(),
            $delloc,
            $this->translator,
            $this->deps->gR,
        );

        try {
            $path = $peppolHelper->generateInvoicePeppolUblXmlTempFile(
                $inv,
                new PeppolHelperInvDeps(
                    $this->deps->soR,
                    $this->deps->iaR,
                    $this->deps->iiaR,
                    $this->deps->iiR,
                    $this->deps->paR,
                    $this->deps->cpR,
                ),
                new PeppolHelperNetDeps(
                    $this->deps->contractRepo,
                    $this->deps->delRepo,
                    $this->deps->delPartyRepo,
                    $this->deps->unpR,
                    $this->deps->upR,
                ),
                new PeppolHelperChargeDeps(
                    $this->deps->aciR,
                    $this->deps->aciiR,
                    $this->deps->soiR,
                    $this->deps->trR,
                ),
            );
            $io->success("Generated: {$path}");
            $io->writeln((string) file_get_contents($path));
        } catch (\Throwable $e) {
            $io->error($e::class . ': ' . $e->getMessage());
            $io->writeln($e->getTraceAsString());
        }
    }

    private function cleanup(int $invId, SymfonyStyle $io): void
    {
        $inv = $this->deps->invRepo->repoInvLoadInvAmountquery($invId);
        if ($inv === null) {
            $io->warning("Inv #{$invId} not found -- nothing to clean up.");
            return;
        }
        $clientId = $inv->getClient()?->reqId();
        $deliveryLocationId = $inv->getDeliveryLocationId();

        $this->deleteItemsAndProducts($inv);
        // Inv has a HasOne InvAmount child (outerKey inv_id) -- unlike
        // InvItem/InvItemAmount above, it was never deleted here, so Cycle
        // couldn't resolve deleting the parent while a required child still
        // pointed at it ("Transaction can't be finished ... Pool has gone
        // into an infinite loop"). Found & fixed 2026-08-31.
        $invAmount = $this->deps->iaR->repoInvquery($invId);
        if ($invAmount !== null) {
            $this->deps->iaR->delete($invAmount);
        }
        $this->deleteInv($inv, $invId);
        $this->deleteDeliveryLocation($deliveryLocationId);
        $this->deleteClient($clientId);

        $io->success("Cleaned up fixture for Inv #{$invId}.");
    }

    /**
     * $this->deps->invRepo->delete($inv) alone throws
     * Cycle\ORM\Exception\TransactionException ("Pool has gone into an
     * infinite loop") -- reproduced even with every child row already
     * gone. Inv has #[Behavior\SoftDelete] (delete becomes an UPDATE
     * setting deleted_at, per Cycle\ORM\Entity\Behavior\Listener\SoftDelete),
     * but its client/user/group BelongsTo relations are nullable: false;
     * the UnitOfWork still appears to try resolving those relations while
     * processing the delete-turned-update, and can't when only the scalar
     * *_id columns (not the relation objects) are hydrated on an entity
     * loaded via a plain query. Root cause not fully chased down -- worth
     * its own investigation if any other code path ever hard-deletes an
     * Inv -- but for this fixture's own teardown, a raw DELETE via the
     * app's own configured DatabaseManager (not a shell/mysql-cli
     * dependency) sidesteps the ORM relation-resolution step entirely.
     * Found & worked around 2026-08-31.
     */
    private function deleteInv(Inv $inv, int $invId): void
    {
        try {
            $this->deps->invRepo->delete($inv);
        } catch (\Throwable) {
            $this->deps->dbal->database()->delete('inv', ['id' => $invId])->run();
        }
    }

    private function deleteItemsAndProducts(Inv $inv): void
    {
        /** @var InvItem $item */
        foreach ($inv->getItems() as $item) {
            $itemAmount = $this->deps->iiaR->repoInvItemAmountquery($item->reqId());
            if ($itemAmount !== null) {
                $this->deps->iiaR->delete($itemAmount);
            }
            $productId = $item->getProductId();
            $this->deps->iiR->delete($item);
            if ($productId !== null) {
                $product = $this->deps->productRepo->repoProductquery($productId);
                if ($product !== null) {
                    $this->deps->productRepo->delete($product);
                }
            }
        }
    }

    private function deleteDeliveryLocation(?int $deliveryLocationId): void
    {
        if ($deliveryLocationId === null || $deliveryLocationId <= 0) {
            return;
        }
        $delloc = $this->deps->dlR->repoDeliveryLocationquery($deliveryLocationId);
        if ($delloc !== null) {
            $this->deps->dlR->delete($delloc);
        }
    }

    private function deleteClient(?int $clientId): void
    {
        if ($clientId === null) {
            return;
        }
        $clientPeppol = $this->deps->cpR->repoClientPeppolLoadedquery($clientId);
        if ($clientPeppol !== null) {
            $this->deps->cpR->delete($clientPeppol);
        }
        $this->deps->clientRepo->delete($this->deps->clientRepo->repoClientquery($clientId));
    }
}
