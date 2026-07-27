<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\SalesOrder;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\Group\Group;
use App\Infrastructure\Persistence\Quote\Quote;
use App\Infrastructure\Persistence\SalesOrder\SalesOrder;
use App\Infrastructure\Persistence\SalesOrderAmount\SalesOrderAmount;
use App\Infrastructure\Persistence\SalesOrderCustom\SalesOrderCustom;
use App\Infrastructure\Persistence\SalesOrderItem\SalesOrderItem;
use App\Infrastructure\Persistence\SalesOrderTaxRate\SalesOrderTaxRate;
use App\Infrastructure\Persistence\User\User;
use App\Invoice\Client\ClientRepository;
use App\Invoice\Group\GroupRepository;
use App\Invoice\Quote\QuoteRepository;
use App\Invoice\SalesOrder\SalesOrderRepository;
use App\Invoice\SalesOrder\SalesOrderService;
use App\Invoice\SalesOrder\SoDeleteFinancialDeps;
use App\Invoice\SalesOrder\SoDeleteSubEntityDeps;
use App\Invoice\SalesOrderAmount\SalesOrderAmountRepository as SoAR;
use App\Invoice\SalesOrderAmount\SalesOrderAmountService as SoAS;
use App\Invoice\SalesOrderCustom\SalesOrderCustomRepository as SoCR;
use App\Invoice\SalesOrderCustom\SalesOrderCustomService as SoCS;
use App\Invoice\SalesOrderItem\SalesOrderItemRepository as SoIR;
use App\Invoice\SalesOrderItem\SalesOrderItemService as SoIS;
use App\Invoice\SalesOrderTaxRate\SalesOrderTaxRateRepository as SoTRR;
use App\Invoice\SalesOrderTaxRate\SalesOrderTaxRateService as SoTRS;
use App\User\UserRepository;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Data\Cycle\Reader\EntityReader;

/**
 * Covers SalesOrderService: addSo (new-record status/date-created
 * forcing) and saveSo (existing-record field assignment), both via the
 * shared client/group/user/quote relation persistence, and deleteSo's
 * cascade through its item/tax-rate/custom-field/amount dependencies.
 */
#[Test]
final class SalesOrderServiceTest
{
    /** @param list<mixed> $items */
    private function readerYielding(array $items): EntityReader
    {
        /** @var EntityReader&m\MockInterface $reader */
        $reader = m::mock(EntityReader::class);
        /** @var \Mockery\Expectation $e */
        $e = $reader->shouldReceive('getIterator');
        $e->andReturn((static function () use ($items) {
            yield from $items;
        })());
        return $reader;
    }

    private function makeService(
        ?SalesOrderRepository $repository = null,
        ?ClientRepository $clientRepository = null,
        ?GroupRepository $groupRepository = null,
        ?UserRepository $userRepository = null,
        ?QuoteRepository $quoteRepository = null,
    ): SalesOrderService {
        $repository = $repository ?? m::mock(SalesOrderRepository::class);
        $clientRepository = $clientRepository ?? m::mock(ClientRepository::class);
        $groupRepository = $groupRepository ?? m::mock(GroupRepository::class);
        $userRepository = $userRepository ?? m::mock(UserRepository::class);
        $quoteRepository = $quoteRepository ?? m::mock(QuoteRepository::class);
        return new SalesOrderService($repository, $clientRepository, $groupRepository, $userRepository, $quoteRepository);
    }

    public function addSoForNewRecordForcesStatusOneAndDateCreatedRegardlessOfArray(): void
    {
        $model = new SalesOrder();
        $currentUser = m::mock(User::class);
        /** @var \Mockery\Expectation $eUser */
        $eUser = $currentUser->shouldReceive('reqId');
        $eUser->andReturn(4);

        $array = [
            'client_id' => 1,
            'group_id' => 2,
            'quote_id' => 5,
            'inv_id' => 9,
            'client_po_number' => 'PO-1',
            'client_po_person' => 'Alice',
            'status_id' => 3,
            'discount_amount' => 10.0,
            'url_key' => 'my-key',
            'password' => 'secret',
            'notes' => 'Some notes',
            'payment_term' => 'net30',
            'number' => 'SO-0001',
        ];

        $client = new Client();
        $client->setId(1);
        $clientRepository = m::mock(ClientRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $clientRepository->shouldReceive('repoClientquery');
        $e->once()->with(1)->andReturn($client);

        $group = new Group();
        $group->setId(2);
        $groupRepository = m::mock(GroupRepository::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $groupRepository->shouldReceive('repoGroupquery');
        $e2->once()->with(2)->andReturn($group);

        $userRepository = m::mock(UserRepository::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $userRepository->shouldReceive('findById');
        $e3->once()->with(4)->andReturn($currentUser);

        $quote = new Quote();
        $quote->setId(5);
        $quoteRepository = m::mock(QuoteRepository::class);
        /** @var \Mockery\Expectation $e4 */
        $e4 = $quoteRepository->shouldReceive('repoQuoteUnLoadedquery');
        $e4->once()->with(5)->andReturn($quote);

        $repository = m::mock(SalesOrderRepository::class);
        /** @var \Mockery\Expectation $e5 */
        $e5 = $repository->shouldReceive('save');
        $e5->once()->with($model);

        $service = $this->makeService($repository, $clientRepository, $groupRepository, $userRepository, $quoteRepository);
        $result = $service->addSo($currentUser, $model, $array);

        Assert::same($model, $result);
        Assert::same(1, $model->reqClientId());
        Assert::same(2, $model->reqGroupId());
        Assert::same(4, $model->reqUserId());
        Assert::same(5, $model->reqQuoteId());
        Assert::same(9, $model->reqInvId());
        Assert::same('PO-1', $model->getClientPoNumber());
        Assert::same('Alice', $model->getClientPoPerson());
        // Forced to 1 for a new record, overriding the array's status_id=3.
        Assert::same(1, $model->getStatusId());
        Assert::same(10.0, $model->getDiscountAmount());
        Assert::same('my-key', $model->getUrlKey());
        Assert::same('secret', $model->getPassword());
        Assert::same('Some notes', $model->getNotes());
        Assert::same('net30', $model->getPaymentTerm());
        Assert::same('SO-0001', $model->getNumber());
    }

    public function addSoWithoutUrlKeyLeavesItUnsetDespiteGeneratingARandomOne(): void
    {
        // See PR #998 "Possible issues found": the missing-url_key branch
        // calls Random::string(32) but discards the result instead of
        // calling setUrlKey() with it, so the model's url_key stays ''.
        $model = new SalesOrder();
        $currentUser = m::mock(User::class);
        /** @var \Mockery\Expectation $eUser */
        $eUser = $currentUser->shouldReceive('reqId');
        $eUser->andReturn(1);

        $array = ['client_id' => 1, 'group_id' => 1, 'number' => 'SO-0002'];

        $client = new Client();
        $client->setId(1);
        $clientRepository = m::mock(ClientRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $clientRepository->shouldReceive('repoClientquery');
        $e->once()->with(1)->andReturn($client);

        $groupRepository = m::mock(GroupRepository::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $groupRepository->shouldReceive('repoGroupquery');
        $e2->once()->with(1)->andReturn(null);

        $userRepository = m::mock(UserRepository::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $userRepository->shouldReceive('findById');
        $e3->once()->with(1)->andReturn($currentUser);

        $repository = m::mock(SalesOrderRepository::class);
        /** @var \Mockery\Expectation $e4 */
        $e4 = $repository->shouldReceive('save');
        $e4->once()->with($model);

        $service = $this->makeService($repository, $clientRepository, $groupRepository, $userRepository);
        $service->addSo($currentUser, $model, $array);

        Assert::same('', $model->getUrlKey());
    }

    public function saveSoForExistingRecordAssignsProvidedFields(): void
    {
        $model = new SalesOrder();
        $model->setId(30);

        $array = [
            'client_id' => 1,
            'group_id' => 2,
            'inv_id' => 12,
            'client_po_number' => 'PO-9',
            'client_po_person' => 'Bob',
            'status_id' => 2,
            'discount_amount' => 5.0,
            'url_key' => 'existing-key',
            'password' => 'pw',
            'notes' => 'Notes here',
            'payment_term' => 'net15',
            'date_created' => '2026-04-01',
        ];

        $client = new Client();
        $client->setId(1);
        $clientRepository = m::mock(ClientRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $clientRepository->shouldReceive('repoClientquery');
        $e->once()->with(1)->andReturn($client);

        $group = new Group();
        $group->setId(2);
        $groupRepository = m::mock(GroupRepository::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $groupRepository->shouldReceive('repoGroupquery');
        $e2->once()->with(2)->andReturn($group);

        $userRepository = m::mock(UserRepository::class);
        $userRepository->shouldNotReceive('findById');

        $quoteRepository = m::mock(QuoteRepository::class);
        $quoteRepository->shouldNotReceive('repoQuoteUnLoadedquery');

        $repository = m::mock(SalesOrderRepository::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $repository->shouldReceive('save');
        $e3->once()->with($model);

        $service = $this->makeService($repository, $clientRepository, $groupRepository, $userRepository, $quoteRepository);
        $result = $service->saveSo($model, $array);

        Assert::same($model, $result);
        Assert::same(12, $model->reqInvId());
        Assert::same('PO-9', $model->getClientPoNumber());
        Assert::same('Bob', $model->getClientPoPerson());
        Assert::same(2, $model->getStatusId());
        Assert::same(5.0, $model->getDiscountAmount());
        Assert::same('existing-key', $model->getUrlKey());
        Assert::same('pw', $model->getPassword());
        Assert::same('Notes here', $model->getNotes());
        Assert::same('net15', $model->getPaymentTerm());
        Assert::same('2026-04-01', $model->getDateCreated()->format('Y-m-d'));
    }

    private function makeSubDeps(
        ?SalesOrderRepository $soR = null,
        ?SoCR $socR = null,
        ?SoCS $socS = null,
        ?SoIR $soiR = null,
        ?SoIS $soiS = null,
    ): SoDeleteSubEntityDeps {
        return new SoDeleteSubEntityDeps(
            $soR ?? m::mock(SalesOrderRepository::class),
            $socR ?? m::mock(SoCR::class),
            $socS ?? m::mock(SoCS::class),
            $soiR ?? m::mock(SoIR::class),
            $soiS ?? m::mock(SoIS::class),
        );
    }

    private function makeFinancialDeps(
        ?SoTRR $sotrR = null,
        ?SoTRS $sotrS = null,
        ?SoAR $soaR = null,
        ?SoAS $soaS = null,
    ): SoDeleteFinancialDeps {
        return new SoDeleteFinancialDeps(
            $sotrR ?? m::mock(SoTRR::class),
            $sotrS ?? m::mock(SoTRS::class),
            $soaR ?? m::mock(SoAR::class),
            $soaS ?? m::mock(SoAS::class),
        );
    }

    public function deleteSoOnUnpersistedModelTouchesNoRepositories(): void
    {
        $model = new SalesOrder();

        $soaR = m::mock(SoAR::class);
        $soaR->shouldNotReceive('repoSalesOrderAmountCount');

        $repository = m::mock(SalesOrderRepository::class);
        $repository->shouldNotReceive('delete');

        $service = $this->makeService($repository);
        $subDeps = $this->makeSubDeps(soR: m::mock(SalesOrderRepository::class));
        $financialDeps = $this->makeFinancialDeps(soaR: $soaR);

        $service->deleteSo($model, $subDeps, $financialDeps);
    }

    public function deleteSoWithNoAmountRecordDeletesItemsTaxRatesAndCustomsThenTheOrder(): void
    {
        $model = new SalesOrder();
        $model->setId(42);

        $soaR = m::mock(SoAR::class);
        /** @var \Mockery\Expectation $e */
        $e = $soaR->shouldReceive('repoSalesOrderAmountCount');
        $e->once()->with(42)->andReturn(0);
        $soaR->shouldNotReceive('repoSalesOrderquery');

        $soaS = m::mock(SoAS::class);
        $soaS->shouldNotReceive('deleteSalesOrderAmount');

        $item = m::mock(SalesOrderItem::class);
        $soiR = m::mock(SoIR::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $soiR->shouldReceive('repoSalesOrderItemIdquery');
        $e2->once()->with(42)->andReturn($this->readerYielding([$item]));

        $soiS = m::mock(SoIS::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $soiS->shouldReceive('deleteSalesOrderItem');
        $e3->once()->with($item);

        $taxRate = m::mock(SalesOrderTaxRate::class);
        $sotrR = m::mock(SoTRR::class);
        /** @var \Mockery\Expectation $e4 */
        $e4 = $sotrR->shouldReceive('repoSalesOrderquery');
        $e4->once()->with(42)->andReturn($this->readerYielding([$taxRate]));

        $sotrS = m::mock(SoTRS::class);
        /** @var \Mockery\Expectation $e5 */
        $e5 = $sotrS->shouldReceive('deleteSalesOrderTaxRate');
        $e5->once()->with($taxRate);

        $custom = m::mock(SalesOrderCustom::class);
        $socR = m::mock(SoCR::class);
        /** @var \Mockery\Expectation $e6 */
        $e6 = $socR->shouldReceive('repoFields');
        $e6->once()->with(42)->andReturn($this->readerYielding([$custom]));

        $socS = m::mock(SoCS::class);
        /** @var \Mockery\Expectation $e7 */
        $e7 = $socS->shouldReceive('deleteSalesOrderCustom');
        $e7->once()->with($custom);

        $repository = m::mock(SalesOrderRepository::class);
        /** @var \Mockery\Expectation $e8 */
        $e8 = $repository->shouldReceive('delete');
        $e8->once()->with($model);

        $service = $this->makeService($repository);
        $subDeps = $this->makeSubDeps(socR: $socR, socS: $socS, soiR: $soiR, soiS: $soiS);
        $financialDeps = $this->makeFinancialDeps(sotrR: $sotrR, sotrS: $sotrS, soaR: $soaR, soaS: $soaS);

        $service->deleteSo($model, $subDeps, $financialDeps);
    }

    public function deleteSoWithAmountRecordDeletesTheAmountToo(): void
    {
        $model = new SalesOrder();
        $model->setId(7);

        $amount = m::mock(SalesOrderAmount::class);
        $soaR = m::mock(SoAR::class);
        /** @var \Mockery\Expectation $e */
        $e = $soaR->shouldReceive('repoSalesOrderAmountCount');
        $e->once()->with(7)->andReturn(1);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $soaR->shouldReceive('repoSalesOrderquery');
        $e2->once()->with(7)->andReturn($amount);

        $soaS = m::mock(SoAS::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $soaS->shouldReceive('deleteSalesOrderAmount');
        $e3->once()->with($amount);

        $soiR = m::mock(SoIR::class);
        /** @var \Mockery\Expectation $e4 */
        $e4 = $soiR->shouldReceive('repoSalesOrderItemIdquery');
        $e4->once()->with(7)->andReturn($this->readerYielding([]));

        $sotrR = m::mock(SoTRR::class);
        /** @var \Mockery\Expectation $e5 */
        $e5 = $sotrR->shouldReceive('repoSalesOrderquery');
        $e5->once()->with(7)->andReturn($this->readerYielding([]));

        $socR = m::mock(SoCR::class);
        /** @var \Mockery\Expectation $e6 */
        $e6 = $socR->shouldReceive('repoFields');
        $e6->once()->with(7)->andReturn($this->readerYielding([]));

        $repository = m::mock(SalesOrderRepository::class);
        /** @var \Mockery\Expectation $e7 */
        $e7 = $repository->shouldReceive('delete');
        $e7->once()->with($model);

        $service = $this->makeService($repository);
        $subDeps = $this->makeSubDeps(soiR: $soiR, socR: $socR);
        $financialDeps = $this->makeFinancialDeps(sotrR: $sotrR, soaR: $soaR, soaS: $soaS);

        $service->deleteSo($model, $subDeps, $financialDeps);
    }

    public function deleteSoWithPositiveCountButNoAmountFoundSkipsAmountDelete(): void
    {
        $model = new SalesOrder();
        $model->setId(8);

        $soaR = m::mock(SoAR::class);
        /** @var \Mockery\Expectation $e */
        $e = $soaR->shouldReceive('repoSalesOrderAmountCount');
        $e->once()->with(8)->andReturn(1);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $soaR->shouldReceive('repoSalesOrderquery');
        $e2->once()->with(8)->andReturn(null);

        $soaS = m::mock(SoAS::class);
        $soaS->shouldNotReceive('deleteSalesOrderAmount');

        $soiR = m::mock(SoIR::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $soiR->shouldReceive('repoSalesOrderItemIdquery');
        $e3->once()->with(8)->andReturn($this->readerYielding([]));

        $sotrR = m::mock(SoTRR::class);
        /** @var \Mockery\Expectation $e4 */
        $e4 = $sotrR->shouldReceive('repoSalesOrderquery');
        $e4->once()->with(8)->andReturn($this->readerYielding([]));

        $socR = m::mock(SoCR::class);
        /** @var \Mockery\Expectation $e5 */
        $e5 = $socR->shouldReceive('repoFields');
        $e5->once()->with(8)->andReturn($this->readerYielding([]));

        $repository = m::mock(SalesOrderRepository::class);
        /** @var \Mockery\Expectation $e6 */
        $e6 = $repository->shouldReceive('delete');
        $e6->once()->with($model);

        $service = $this->makeService($repository);
        $subDeps = $this->makeSubDeps(soiR: $soiR, socR: $socR);
        $financialDeps = $this->makeFinancialDeps(sotrR: $sotrR, soaR: $soaR, soaS: $soaS);

        $service->deleteSo($model, $subDeps, $financialDeps);
    }
}
