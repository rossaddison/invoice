<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\InvRecurring;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Infrastructure\Persistence\InvRecurring\InvRecurring;
use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\ProductClient\ProductClient;
use App\Infrastructure\Persistence\User\User;
use App\Infrastructure\Persistence\UserClient\UserClient;
use App\Infrastructure\Persistence\UserInv\UserInv;
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\Inv\InvService as IS;
use App\Invoice\InvAmount\InvAmountRepository;
use App\Invoice\InvItem\IiAddProductDeps;
use App\Invoice\InvItem\InvItemService;
use App\Invoice\InvItemAmount\InvItemAmountRepository;
use App\Invoice\InvItemAmount\InvItemAmountService;
use App\Invoice\InvRecurring\InvCronUserDeps;
use App\Invoice\InvRecurring\InvItemDeps;
use App\Invoice\InvRecurring\InvRecurringCronService;
use App\Invoice\InvRecurring\InvRecurringRepository;
use App\Invoice\Product\ProductRepository;
use App\Invoice\ProductClient\ProductClientRepository as PCR;
use App\Invoice\Setting\SettingRepository as SR;
use App\Invoice\TaxRate\TaxRateRepository;
use App\Invoice\Unit\UnitRepository;
use App\Invoice\UserClient\UserClientRepository;
use App\Invoice\UserInv\UserInvRepository;
use App\User\UserRepository;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Log\Logger;

/**
 * Covers InvRecurringCronService — extracted from
 * InvRecurringController::cron() so both the legacy cron_key HTTP endpoint
 * and the `invrecurring/process` console command share identical logic.
 *
 * EntityReader is final; findAllPreloaded()/active() are stubbed by
 * mocking EntityReader itself (BypassFinals, enabled in testo.php, allows
 * this) and overriding getIterator() to yield fixed items — the same
 * approach as a hand-written iterator stub, without needing one.
 */
#[Test]
final class InvRecurringCronServiceTest
{
    /**
     * @param list<mixed> $items
     */
    private function fakeReader(array $items): EntityReader
    {
        /** @var EntityReader&m\MockInterface $reader */
        $reader = m::mock(EntityReader::class);
        $generator = (static function () use ($items) {
            yield from $items;
        })();
        /** @var \Mockery\Expectation $e */
        $e = $reader->shouldReceive('getIterator');
        $e->andReturn($generator);
        return $reader;
    }

    private function makeService(
        InvRecurringRepository $irR,
        IR $iR,
        GR $gR,
        PCR $pcR,
        IS $iS,
        InvItemService $invItemService,
        SR $sR,
    ): InvRecurringCronService {
        return new InvRecurringCronService($irR, $iR, $gR, $pcR, $iS, $invItemService, $sR, new Logger());
    }

    private function makeCronUserDeps(
        ?UserClientRepository $uclR = null,
        ?UserInvRepository $uiR = null,
        ?UserRepository $userRepository = null,
    ): InvCronUserDeps {
        return new InvCronUserDeps(
            m::mock(InvAmountRepository::class),
            $uclR ?? m::mock(UserClientRepository::class),
            $uiR ?? m::mock(UserInvRepository::class),
            $userRepository ?? m::mock(UserRepository::class),
        );
    }

    private function makeItemDeps(?ProductRepository $pR = null): InvItemDeps
    {
        return new InvItemDeps(
            m::mock(InvItemAmountRepository::class),
            m::mock(InvItemAmountService::class),
            $pR ?? m::mock(ProductRepository::class),
            m::mock(TaxRateRepository::class),
            m::mock(UnitRepository::class),
        );
    }

    private function stubSettings(?SR $sR = null): SR
    {
        $sR = $sR ?? m::mock(SR::class);
        /** @var \Mockery\Expectation $token */
        $token = $sR->shouldReceive('getSetting');
        $token->with('telegram_token')->andReturn('');
        /** @var \Mockery\Expectation $enabled */
        $enabled = $sR->shouldReceive('getSetting');
        $enabled->with('enable_telegram')->andReturn('0');
        return $sR;
    }

    public function resolveAdminUserReturnsFirstAdminTypeUser(): void
    {
        /** @var UserInv&m\MockInterface $nonAdmin */
        $nonAdmin = m::mock(UserInv::class);
        /** @var \Mockery\Expectation $eType1 */
        $eType1 = $nonAdmin->shouldReceive('getType');
        $eType1->andReturn(1);

        /** @var UserInv&m\MockInterface $admin */
        $admin = m::mock(UserInv::class);
        /** @var \Mockery\Expectation $eType0 */
        $eType0 = $admin->shouldReceive('getType');
        $eType0->andReturn(0);
        /** @var \Mockery\Expectation $eUserId */
        $eUserId = $admin->shouldReceive('reqUserId');
        $eUserId->andReturn(42);

        /** @var UserInvRepository&m\MockInterface $uiR */
        $uiR = m::mock(UserInvRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $uiR->shouldReceive('findAllPreloaded');
        $e->andReturn($this->fakeReader([$nonAdmin, $admin]));

        /** @var User&m\MockInterface $adminUser */
        $adminUser = m::mock(User::class);
        /** @var UserRepository&m\MockInterface $userRepository */
        $userRepository = m::mock(UserRepository::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $userRepository->shouldReceive('findById');
        $e2->once()->with(42)->andReturn($adminUser);

        $service = $this->makeService(
            m::mock(InvRecurringRepository::class),
            m::mock(IR::class),
            m::mock(GR::class),
            m::mock(PCR::class),
            m::mock(IS::class),
            m::mock(InvItemService::class),
            m::mock(SR::class),
        );

        $result = $service->resolveAdminUser($this->makeCronUserDeps(uiR: $uiR, userRepository: $userRepository));

        Assert::same($adminUser, $result);
    }

    public function resolveAdminUserReturnsNullWhenNoAdminTypeFound(): void
    {
        /** @var UserInv&m\MockInterface $nonAdmin */
        $nonAdmin = m::mock(UserInv::class);
        /** @var \Mockery\Expectation $eType */
        $eType = $nonAdmin->shouldReceive('getType');
        $eType->andReturn(1);

        /** @var UserInvRepository&m\MockInterface $uiR */
        $uiR = m::mock(UserInvRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $uiR->shouldReceive('findAllPreloaded');
        $e->andReturn($this->fakeReader([$nonAdmin]));

        $service = $this->makeService(
            m::mock(InvRecurringRepository::class),
            m::mock(IR::class),
            m::mock(GR::class),
            m::mock(PCR::class),
            m::mock(IS::class),
            m::mock(InvItemService::class),
            m::mock(SR::class),
        );

        $result = $service->resolveAdminUser($this->makeCronUserDeps(uiR: $uiR));

        Assert::null($result);
    }

    public function processSkipsRecurringWhenBaseInvoiceNotFound(): void
    {
        $invRecurring = new InvRecurring(inv_id: 99, frequency: '1M', next: '2026-01-01');

        /** @var InvRecurringRepository&m\MockInterface $irR */
        $irR = m::mock(InvRecurringRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $irR->shouldReceive('active');
        $e->andReturn($this->fakeReader([$invRecurring]));
        $irR->shouldNotReceive('save');

        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $iR->shouldReceive('repoInvUnloadedquery');
        $e2->once()->with(99)->andReturn(null);

        /** @var PCR&m\MockInterface $pcR */
        $pcR = m::mock(PCR::class);
        $pcR->shouldNotReceive('findByClientId');

        $sR = $this->stubSettings();

        $service = $this->makeService(
            $irR,
            $iR,
            m::mock(GR::class),
            $pcR,
            m::mock(IS::class),
            m::mock(InvItemService::class),
            $sR,
        );

        $result = $service->process(m::mock(User::class), $this->makeItemDeps(), $this->makeCronUserDeps());

        Assert::same(['created' => 0, 'reminded' => 0], $result);
    }

    public function processAdvancesDateButSkipsInvoiceWhenNotConsented(): void
    {
        $invRecurring = new InvRecurring(inv_id: 5, frequency: '1M', next: '2026-01-01');

        /** @var InvRecurringRepository&m\MockInterface $irR */
        $irR = m::mock(InvRecurringRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $irR->shouldReceive('active');
        $e->andReturn($this->fakeReader([$invRecurring]));
        /** @var \Mockery\Expectation $eSave */
        $eSave = $irR->shouldReceive('save');
        $eSave->once()->with($invRecurring);

        /** @var Inv&m\MockInterface $baseInv */
        $baseInv = m::mock(Inv::class);
        /** @var \Mockery\Expectation $eClientId */
        $eClientId = $baseInv->shouldReceive('reqClientId');
        $eClientId->andReturn(7);

        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $iR->shouldReceive('repoInvUnloadedquery');
        $e2->once()->with(5)->andReturn($baseInv);

        /** @var UserClient&m\MockInterface $userClient */
        $userClient = m::mock(UserClient::class);
        /** @var \Mockery\Expectation $eUserId */
        $eUserId = $userClient->shouldReceive('reqUserId');
        $eUserId->andReturn(11);

        /** @var UserClientRepository&m\MockInterface $uclR */
        $uclR = m::mock(UserClientRepository::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $uclR->shouldReceive('repoUserquery');
        $e3->once()->with(7)->andReturn($userClient);

        /** @var UserInv&m\MockInterface $userInv */
        $userInv = m::mock(UserInv::class);
        /** @var \Mockery\Expectation $eConsent */
        $eConsent = $userInv->shouldReceive('getConsentPeriodicInvoice');
        $eConsent->andReturn(false);

        /** @var UserInvRepository&m\MockInterface $uiR */
        $uiR = m::mock(UserInvRepository::class);
        /** @var \Mockery\Expectation $e4 */
        $e4 = $uiR->shouldReceive('repoUserInvUserIdquery');
        $e4->once()->with(11)->andReturn($userInv);

        /** @var PCR&m\MockInterface $pcR */
        $pcR = m::mock(PCR::class);
        $pcR->shouldNotReceive('findByClientId');

        $sR = $this->stubSettings();

        $service = $this->makeService(
            $irR,
            $iR,
            m::mock(GR::class),
            $pcR,
            m::mock(IS::class),
            m::mock(InvItemService::class),
            $sR,
        );

        $result = $service->process(
            m::mock(User::class),
            $this->makeItemDeps(),
            $this->makeCronUserDeps(uclR: $uclR, uiR: $uiR),
        );

        Assert::same(['created' => 0, 'reminded' => 0], $result);
    }

    public function processCreatesInvoiceFromProductsWhenConsented(): void
    {
        $invRecurring = new InvRecurring(inv_id: 5, frequency: '1M', next: '2026-01-01');

        /** @var InvRecurringRepository&m\MockInterface $irR */
        $irR = m::mock(InvRecurringRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $irR->shouldReceive('active');
        $e->andReturn($this->fakeReader([$invRecurring]));
        /** @var \Mockery\Expectation $eSave */
        $eSave = $irR->shouldReceive('save');
        $eSave->once()->with($invRecurring);

        /** @var Inv&m\MockInterface $baseInv */
        $baseInv = m::mock(Inv::class);
        /** @var \Mockery\Expectation $eClientId */
        $eClientId = $baseInv->shouldReceive('reqClientId');
        $eClientId->andReturn(7);

        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $iR->shouldReceive('repoInvUnloadedquery');
        $e2->once()->with(5)->andReturn($baseInv);

        /** @var UserClient&m\MockInterface $userClient */
        $userClient = m::mock(UserClient::class);
        /** @var \Mockery\Expectation $eUserId */
        $eUserId = $userClient->shouldReceive('reqUserId');
        $eUserId->andReturn(11);

        /** @var UserClientRepository&m\MockInterface $uclR */
        $uclR = m::mock(UserClientRepository::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $uclR->shouldReceive('repoUserquery');
        $e3->once()->with(7)->andReturn($userClient);

        /** @var UserInv&m\MockInterface $userInv */
        $userInv = m::mock(UserInv::class);
        /** @var \Mockery\Expectation $eConsent */
        $eConsent = $userInv->shouldReceive('getConsentPeriodicInvoice');
        $eConsent->andReturn(true);

        /** @var UserInvRepository&m\MockInterface $uiR */
        $uiR = m::mock(UserInvRepository::class);
        /** @var \Mockery\Expectation $e4 */
        $e4 = $uiR->shouldReceive('repoUserInvUserIdquery');
        $e4->once()->with(11)->andReturn($userInv);

        $productClients = [new ProductClient(product_id: 10, client_id: 7)];
        /** @var PCR&m\MockInterface $pcR */
        $pcR = m::mock(PCR::class);
        /** @var \Mockery\Expectation $ePcr */
        $ePcr = $pcR->shouldReceive('findByClientId');
        $ePcr->once()->with(7)->andReturn($productClients);

        /** @var Inv&m\MockInterface $savedInv */
        $savedInv = m::mock(Inv::class);
        /** @var \Mockery\Expectation $eReqId */
        $eReqId = $savedInv->shouldReceive('reqId');
        $eReqId->andReturn(55);

        /** @var IS&m\MockInterface $iS */
        $iS = m::mock(IS::class);
        /** @var \Mockery\Expectation $eSaveInv */
        $eSaveInv = $iS->shouldReceive('saveInv');
        $eSaveInv->once()->andReturn($savedInv);

        /** @var Product&m\MockInterface $product */
        $product = m::mock(Product::class);
        /** @var \Mockery\Expectation $eProdId */
        $eProdId = $product->shouldReceive('reqId');
        $eProdId->andReturn(10);
        /** @var \Mockery\Expectation $eTaxRateId */
        $eTaxRateId = $product->shouldReceive('reqTaxRateId');
        $eTaxRateId->andReturn(2);
        /** @var \Mockery\Expectation $ePrice */
        $ePrice = $product->shouldReceive('getProductPrice');
        $ePrice->andReturn(9.99);
        /** @var \Mockery\Expectation $eUnitId */
        $eUnitId = $product->shouldReceive('reqUnitId');
        $eUnitId->andReturn(3);

        /** @var ProductRepository&m\MockInterface $pR */
        $pR = m::mock(ProductRepository::class);
        /** @var \Mockery\Expectation $ePr */
        $ePr = $pR->shouldReceive('repoProductquery');
        $ePr->once()->with(10)->andReturn($product);

        /** @var InvItemService&m\MockInterface $invItemService */
        $invItemService = m::mock(InvItemService::class);
        /** @var \Mockery\Expectation $eAdd */
        $eAdd = $invItemService->shouldReceive('addInvItemProduct');
        $eAdd->once()->with(
            m::type(InvItem::class),
            [
                'product_id'      => 10,
                'tax_rate_id'     => 2,
                'quantity'        => 1.00,
                'price'           => 9.99,
                'discount_amount' => 0.00,
                'product_unit_id' => 3,
            ],
            '55',
            m::type(IiAddProductDeps::class),
        );

        $sR = $this->stubSettings();

        $service = $this->makeService($irR, $iR, m::mock(GR::class), $pcR, $iS, $invItemService, $sR);

        $result = $service->process(
            m::mock(User::class),
            $this->makeItemDeps($pR),
            $this->makeCronUserDeps(uclR: $uclR, uiR: $uiR),
        );

        Assert::same(['created' => 1, 'reminded' => 0], $result);
    }

    public function addProductItemsToInvSkipsNullProductIdAndUnresolvedProduct(): void
    {
        $productWithNoId = new ProductClient(product_id: null, client_id: 1);
        $productWithUnresolvedId = new ProductClient(product_id: 999, client_id: 1);

        /** @var ProductRepository&m\MockInterface $pR */
        $pR = m::mock(ProductRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $pR->shouldReceive('repoProductquery');
        $e->once()->with(999)->andReturn(null);

        /** @var InvItemService&m\MockInterface $invItemService */
        $invItemService = m::mock(InvItemService::class);
        $invItemService->shouldNotReceive('addInvItemProduct');

        $service = $this->makeService(
            m::mock(InvRecurringRepository::class),
            m::mock(IR::class),
            m::mock(GR::class),
            m::mock(PCR::class),
            m::mock(IS::class),
            $invItemService,
            m::mock(SR::class),
        );

        $service->addProductItemsToInv(
            [$productWithNoId, $productWithUnresolvedId],
            '55',
            $this->makeItemDeps($pR),
        );
    }
}
