<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\HomeCareRunImport;

use App\Infrastructure\Persistence\CategorySecondary\CategorySecondary;
use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\Dwelling\Dwelling;
use App\Infrastructure\Persistence\Family\Family;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\User\User;
use App\Invoice\CategorySecondary\CategorySecondaryService;
use App\Invoice\Client\ClientDwellingRepository;
use App\Invoice\Client\ClientRepository;
use App\Invoice\Dwelling\DwellingRepository;
use App\Invoice\Dwelling\DwellingService;
use App\Invoice\Family\FamilyRepository;
use App\Invoice\Family\FamilyService;
use App\Invoice\Helpers\InvRecalculator;
use App\Invoice\HomeCareRunImport\RunHouseListParser;
use App\Invoice\HomeCareRunImport\RunImportDeps;
use App\Invoice\HomeCareRunImport\RunImportService;
use App\Invoice\Inv\InvService;
use App\Invoice\InvItem\InvItemService;
use App\Invoice\ProductClient\ProductClientService;
use App\Invoice\Product\ProductRepository;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\TaxRate\TaxRateRepository;
use App\Invoice\Unit\UnitRepository;
use Cycle\ORM\Heap\HeapInterface;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\RepositoryInterface;
use Mockery as m;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use ReflectionClass;
use Testo\Assert;
use Testo\Test;

/**
 * Saving a parsed run: the run, a street per street, and for each house a dwelling, a client linked
 * to it and a draft invoice. A house that already has a client is left alone; cancelled houses are
 * not created.
 */
#[Test]
final class RunImportServiceTest
{
    private const string TEXT = "RUN ONE\nBERRYHILL CRESCENT ML2 0NE (3)\n3\n14\n41 CANCELLED\nSHIELD RD ML1 2LP\n462\n";

    /** @var array<string, m\MockInterface> */
    private array $mocks = [];

    private function mock(string $class): m\MockInterface
    {
        return $this->mocks[$class] = m::mock($class);
    }

    /**
     * Builds the service over mocks. $claimed lists the dwelling ids that already have a client.
     *
     * @param list<int> $claimed
     * @param list<Family> $existingFamilies the families the run already has
     * @param bool $invoiceFails the draft invoice cannot be saved
     * @param list<string> $log filled with "client:<name>", "invoice:<client id>" ... as things happen
     */
    private function service(array $claimed, array &$log, array $existingFamilies = [], bool $invoiceFails = false): RunImportService
    {
        $nextId = 100;
        $csService = $this->mock(CategorySecondaryService::class);
        $csService->shouldReceive('saveCategorySecondary')->andReturnUsing(
            static function (CategorySecondary $c, array $body) use (&$log): void {
                $c->setId(7);
                $log[] = 'run:' . (string) $body['name'];
            }
        );
        $fR = $this->mock(FamilyRepository::class);
        $fR->shouldReceive('repoFamilyByNameAndSecondaryCategoryQuery')->andReturn(null);
        /** @var EntityReader&m\MockInterface $existing */
        $existing = m::mock(EntityReader::class);
        $existing->shouldReceive('getIterator')->andReturnUsing(static function () use ($existingFamilies): \Generator {
            yield from $existingFamilies;
        });
        $fR->shouldReceive('repoCategorySecondaryIdQuery')->andReturn($existing);
        $families = $this->mock(FamilyService::class);
        $families->shouldReceive('findOrCreateByStreetName')->andReturnUsing(
            static function (string $street) use (&$nextId, &$log): Family {
                $family = new Family();
                $family->setId($nextId++);
                $log[] = 'street:' . $street;
                return $family;
            }
        );
        $families->shouldReceive('saveStreetOrders')->atMost()->once()->andReturnUsing(
            /** @param list<int> $ids */
            static function (array $ids) use (&$log): void {
                $log[] = 'order:' . implode(',', $ids);
            }
        );
        $dwellings = $this->mock(DwellingService::class);
        $dwellings->shouldReceive('findOrCreateDwelling')->andReturnUsing(
            static function (int $familyId, string $number) use (&$nextId): Dwelling {
                $dwelling = new Dwelling();
                $dwelling->setId((int) $number);
                return $dwelling;
            }
        );
        $dwR = $this->mock(DwellingRepository::class);
        $dwR->shouldReceive('save');
        $cR = $this->mock(ClientRepository::class);
        $cR->shouldReceive('delete')->andReturnUsing(static function (Client $c) use (&$log): void {
            $log[] = 'deleted:' . $c->getClientName();
        });
        $cR->shouldReceive('save')->andReturnUsing(static function (Client $c) use (&$nextId, &$log): void {
            $c->setId($nextId++);
            $log[] = 'client:' . $c->getClientName() . '@' . (string) $c->getDwellingId();
        });
        $cdR = $this->mock(ClientDwellingRepository::class);
        $cdR->shouldReceive('isClaimedByAnother')->andReturnUsing(
            static fn (int $dwellingId): bool => in_array($dwellingId, $claimed, true)
        );
        $invService = $this->mock(InvService::class);
        $invService->shouldReceive('withTransaction')->andReturnUsing(static function (callable $fn): void {
            $fn();
        });
        $invService->shouldReceive('saveInv')->andReturnUsing(static function (User $u, Inv $inv, array $body) use (&$log, $invoiceFails): Inv {
            if ($invoiceFails) {
                throw new \RuntimeException('cannot save');
            }
            $inv->setId(900 + (int) $body['client_id']);
            $log[] = 'invoice:' . (string) $body['client_id'] . ':status' . (string) $body['status_id'];
            return $inv;
        });
        $items = $this->mock(InvItemService::class);
        $items->shouldReceive('addInvItemProduct')->andReturnUsing(
            static function (InvItem $_item, array $body) use (&$log): void {
                $log[] = 'item:product' . (string) $body['product_id'] . ':price' . (string) $body['price'];
            }
        );
        $recalc = $this->mock(InvRecalculator::class);
        $recalc->shouldReceive('recalculate');
        $productClient = $this->mock(ProductClientService::class);
        $productClient->shouldReceive('syncFromInvItems');
        $settings = $this->mock(SettingRepository::class);
        $settings->shouldReceive('getSetting')->with('default_invoice_group')->andReturn('1');

        $taxRates = $this->mock(TaxRateRepository::class);
        $taxRates->shouldReceive('repoFirstByIdQuery')->andReturn(null);
        $units = $this->mock(UnitRepository::class);
        $units->shouldReceive('repoFirstByIdQuery')->andReturn(null);

        // each invoice is made with the user and product fetched fresh, after the unit of work is emptied
        /** @var ProductRepository&m\MockInterface $products */
        $products = $this->mock(ProductRepository::class);
        $products->shouldReceive('repoProductquery')->with(5)->andReturn($this->product());
        /** @var RepositoryInterface&m\MockInterface $userRepository */
        $userRepository = m::mock(RepositoryInterface::class);
        $userRepository->shouldReceive('findByPK')->with(1)->andReturn($this->user());
        /** @var HeapInterface&m\MockInterface $heap */
        $heap = m::mock(HeapInterface::class);
        $heap->shouldReceive('clean')->andReturnUsing(static function () use (&$log): void {
            $log[] = 'heap cleaned';
        });
        /** @var ORMInterface&m\MockInterface $orm */
        $orm = $this->mock(ORMInterface::class);
        $orm->shouldReceive('getRepository')->with(User::class)->andReturn($userRepository);
        $orm->shouldReceive('getHeap')->andReturn($heap);

        $reflection = new ReflectionClass(RunImportDeps::class);
        $args = [];
        foreach ($reflection->getConstructor()?->getParameters() ?? [] as $parameter) {
            $type = $parameter->getType();
            $class = $type instanceof \ReflectionNamedType ? $type->getName() : '';
            $args[$parameter->getName()] = match ($class) {
                CategorySecondaryService::class => $csService,
                FamilyRepository::class => $fR,
                FamilyService::class => $families,
                DwellingService::class => $dwellings,
                DwellingRepository::class => $dwR,
                ClientRepository::class => $cR,
                ClientDwellingRepository::class => $cdR,
                InvService::class => $invService,
                InvItemService::class => $items,
                InvRecalculator::class => $recalc,
                ProductClientService::class => $productClient,
                SettingRepository::class => $settings,
                ProductRepository::class => $products,
                ORMInterface::class => $orm,
                TaxRateRepository::class => $taxRates,
                UnitRepository::class => $units,
                default => m::mock($class),
            };
        }
        return new RunImportService($reflection->newInstanceArgs($args));
    }

    private function user(): User
    {
        /** @var User&m\MockInterface $user */
        $user = m::mock(User::class);
        $user->shouldReceive('reqId')->andReturn(1);
        return $user;
    }

    private function product(): Product
    {
        $product = new Product();
        $product->setId(5);
        return $product;
    }

    public function aNewRunGetsItsStreetsAndAClientDwellingAndDraftInvoiceForEachHouse(): void
    {
        $log = [];
        $result = $this->service([], $log)->save(
            (new RunHouseListParser())->parse(self::TEXT),
            null,
            'Run One',
            3,
            $this->product(),
            12.5,
            $this->user(),
        );

        Assert::true($result->runCreated);
        Assert::same($result->runId, 7);
        Assert::same($result->streetsCreated, 2);
        Assert::same($result->housesCreated, 3);
        Assert::same($result->leftOut, ['41 Berryhill Crescent']);
        Assert::same($result->alreadyThere, []);
        Assert::true(in_array('run:Run One', $log, true));
        Assert::true(in_array('street:Berryhill Crescent', $log, true));
        Assert::true(in_array('street:Shield Rd', $log, true));
        // the client is linked to the dwelling that was created for the house
        Assert::true(in_array('client:3 Berryhill Crescent@3', $log, true));
        Assert::true(in_array('client:462 Shield Rd@462', $log, true));
        // a draft (status 1) invoice with the standard product at the given price
        Assert::true(in_array('item:product5:price12.5', $log, true));
        Assert::same(count(array_filter($log, static fn (string $l): bool => str_ends_with($l, ':status1'))), 3);
        // the unit of work is emptied after each invoice (a real database needs it for the amount row)
        Assert::same(count(array_filter($log, static fn (string $l): bool => 'heap cleaned' === $l)), 3);
    }

    public function aHouseThatAlreadyHasAClientIsLeftAlone(): void
    {
        $log = [];
        $result = $this->service([14], $log)->save(
            (new RunHouseListParser())->parse(self::TEXT),
            9,
            '',
            0,
            $this->product(),
            null,
            $this->user(),
        );

        Assert::false($result->runCreated);
        Assert::same($result->runId, 9);
        Assert::same($result->housesCreated, 2);
        Assert::same($result->alreadyThere, ['14 Berryhill Crescent']);
        Assert::false(in_array('client:14 Berryhill Crescent@14', $log, true));
        Assert::false(in_array('run:', $log, true));
    }

    public function aFailedInvoiceDeletesTheClientSoAReImportCanTryTheHouseAgain(): void
    {
        $log = [];
        $caught = null;
        try {
            $this->service([], $log, [], true)->save(
                (new RunHouseListParser())->parse("RUN ONE
EXAMPLE CRESCENT AB1 2CD
3
"),
                9,
                '',
                0,
                $this->product(),
                null,
                $this->user(),
            );
        } catch (\RuntimeException $e) {
            $caught = $e;
        }

        Assert::notNull($caught);
        Assert::true(in_array('deleted:3 Example Crescent', $log, true));
    }

    public function streetsAlreadyOrderedKeepTheirPlaceAndNewStreetsFollow(): void
    {
        $ordered = static function (int $id, int $order): Family {
            $family = new Family();
            $family->setId($id);
            $family->setStreetSortOrder($order);
            return $family;
        };
        $log = [];
        // the run already has families 40 (order 2) and 41 (order 1); the import meets two other streets
        $this->service([], $log, [$ordered(40, 2), $ordered(41, 1)])->save(
            (new RunHouseListParser())->parse(self::TEXT),
            9,
            '',
            0,
            $this->product(),
            null,
            $this->user(),
        );

        $orders = array_values(array_filter($log, static fn (string $l): bool => str_starts_with($l, 'order:')));
        Assert::same(count($orders), 1);
        // 41 (order 1) then 40 (order 2) stay first, the two streets met for the first time follow
        Assert::true(str_starts_with($orders[0], 'order:41,40,'));
        Assert::same(count(explode(',', substr($orders[0], 6))), 4);
    }
}
