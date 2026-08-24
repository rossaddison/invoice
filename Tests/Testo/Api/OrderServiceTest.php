<?php

declare(strict_types=1);

namespace Tests\Testo\Api;

use App\Api\OrderService;
use App\Api\OrderServiceDeps;
use App\Auth\AuthService;
use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\User\User;
use App\Infrastructure\Persistence\UserClient\UserClient;
use App\Infrastructure\Persistence\UserInv\UserInv;
use App\Invoice\AppConstants;
use App\Invoice\Client\ClientRepository as cR;
use App\Invoice\Group\GroupRepository as gR;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\Inv\InvRepository as iR;
use App\Invoice\Inv\InvService as IS;
use App\Invoice\InvAmount\InvAmountRepository as iaR;
use App\Invoice\InvItem\IiAddProductDeps;
use App\Invoice\InvItem\InvItemRepository as iiR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as iiaR;
use App\Invoice\InvItemAmount\InvItemAmountService as iias;
use App\Invoice\InvItem\InvItemService;
use App\Invoice\Product\ProductRepository as pR;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\TaxRate\TaxRateRepository as trR;
use App\Invoice\Unit\UnitRepository as unR;
use App\Invoice\UserClient\UserClientRepository as ucR;
use App\Invoice\UserInv\UserInvRepository as uiR;
use App\Invoice\UserInv\UserRbacLinkRepository as urlR;
use App\User\UserRepository as uR;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Rbac\Manager;
use Yiisoft\Session\SessionInterface;

/**
 * Covers OrderService::createOrder() — account resolution (new customer
 * vs. repeat customer) and the direct in-session login that replaced the
 * one-time-login-link handoff once the `/shop` storefront started running
 * in-process (see docs/WEBSHOP_INPROCESS_MERGE_AUGUST_2026.md). Both cases
 * here use an already-existing Client (ClientRepository::findByEmail()
 * returns non-null) so the test surface stays focused on the account/
 * login logic rather than re-proving the pre-existing Client-creation/
 * invoice-shell/item-adding code path, which is untouched by this change.
 *
 * All repositories/services here are `final`, mockable only because
 * Tests/testo.php enables DG\BypassFinals — same established pattern as
 * InvPaymentSettlementServiceTest. `User` has no public setId() (only
 * populated by a real Cycle insert), so the mocked save() closures assign
 * one via ReflectionProperty — the same technique already used elsewhere
 * in this suite (e.g. InvRecurringServiceTest) for otherwise-unsettable
 * private entity state.
 */
#[Test]
final class OrderServiceTest
{
    private function setPrivateId(object $entity, int $id): void
    {
        $property = new \ReflectionProperty($entity, 'id');
        $property->setValue($entity, $id);
    }

    /** @return array{name: string, surname: string, email: string} */
    private function customer(string $email): array
    {
        return ['name' => 'Ada', 'surname' => 'Lovelace', 'email' => $email];
    }

    /** @return list<array{product_id: int, quantity: float}> */
    private function items(): array
    {
        return [['product_id' => 7, 'quantity' => 2.0]];
    }

    private function existingClient(int $id, string $email): Client
    {
        $client = new Client();
        $client->setId($id);
        $client->setClientEmail($email);
        return $client;
    }

    private function trackedProduct(): Product
    {
        $product = new Product();
        $product->setId(7);
        $product->setTaxRateId(1);
        $product->setUnitId(1);
        $product->setProductPrice(9.99);
        return $product;
    }

    /**
     * Deliberately gives retail_price a different value from product_price
     * — see chargesTheInvItemAtWebshopPriceNotWholesaleProductPrice(),
     * which needs the two to diverge to prove addOrderItem() bills at
     * Product::webshopPrice() (retail_price here) rather than the staff/
     * B2B product_price ("wholesale").
     */
    private function trackedProductWithRetailPrice(): Product
    {
        $product = $this->trackedProduct();
        $product->setRetailPrice(6.49);
        return $product;
    }

    /**
     * The Inv the mocked InvService::saveInv() returns — already has an
     * id and a number, so createInvoiceShell() never needs
     * GroupRepository::generateNumber().
     */
    private function savedInv(int $id): Inv
    {
        $inv = new Inv();
        $inv->setId($id);
        $inv->setNumber('WS-' . $id);
        return $inv;
    }

    private function invWithAmount(int $invId): Inv
    {
        /** @var Inv&m\MockInterface $inv */
        $inv = m::mock(Inv::class);
        $inv->shouldReceive('getInvAmount')->andReturn(new InvAmount(inv_id: $invId, total: 19.98, balance: 19.98));
        $inv->shouldReceive('getUrlKey')->andReturn('some-url-key-' . $invId);
        return $inv;
    }

    public function createsANewCustomerAccountAndLogsThemIn(): void
    {
        $client = $this->existingClient(50, 'ada@example.test');
        $savedInv = $this->savedInv(900);

        /** @var iR&m\MockInterface $iR */
        $iR = m::mock(iR::class);
        /** @var iaR&m\MockInterface $iaR */
        $iaR = m::mock(iaR::class);
        /** @var pR&m\MockInterface $pR */
        $pR = m::mock(pR::class);
        /** @var cR&m\MockInterface $cR */
        $cR = m::mock(cR::class);
        /** @var sR&m\MockInterface $sR */
        $sR = m::mock(sR::class);
        /** @var uR&m\MockInterface $uR */
        $uR = m::mock(uR::class);
        /** @var uiR&m\MockInterface $uiR */
        $uiR = m::mock(uiR::class);
        /** @var ucR&m\MockInterface $ucR */
        $ucR = m::mock(ucR::class);
        /** @var urlR&m\MockInterface $urlR */
        $urlR = m::mock(urlR::class);
        /** @var Manager&m\MockInterface $manager */
        $manager = m::mock(Manager::class);
        /** @var AuthService&m\MockInterface $authService */
        $authService = m::mock(AuthService::class);
        /** @var SessionInterface&m\MockInterface $session */
        $session = m::mock(SessionInterface::class);

        $cR->shouldReceive('findByEmail')->once()->with('ada@example.test')->andReturn($client);
        $sR->shouldReceive('getSetting')->with('default_invoice_group')->andReturn('3');
        $pR->shouldReceive('repoProductquery')->with(7)->andReturn($this->trackedProduct());
        $iR->shouldReceive('save')->once()->with($savedInv);
        $iR->shouldReceive('repoInvLoadInvAmountquery')->once()->with(900)->andReturn($this->invWithAmount(900));
        $iaR->shouldReceive('save')->once();

        // First order for this Client — no existing UserClient link yet.
        $ucR->shouldReceive('repoUserquery')->once()->with(50)->andReturn(null);
        $uR->shouldReceive('findByLogin')->once()->with('ada@example.test')->andReturn(null);
        $uR->shouldReceive('save')->once()->andReturnUsing(function (User $user): void {
            $this->setPrivateId($user, 200);
            $this->setPrivateId($user->getIdentity(), 2000);
        });
        $uiR->shouldReceive('save')->once()->andReturnUsing(static function (UserInv $userInv): void {
            $userInv->setId(300);
        });
        $ucR->shouldReceive('save')->once()->with(m::on(
            static fn (UserClient $uc): bool => $uc->reqUserId() === 200 && $uc->reqClientId() === 50,
        ));
        $manager->shouldReceive('revokeAll')->once()->with('200');
        $manager->shouldReceive('assign')->once()->with(AppConstants::ROLE_OBSERVER, '200');
        $urlR->shouldReceive('upsert')->once()->with(300, 200);
        // The new customer account is logged into the caller's own
        // session directly — no one-time token issued at all.
        $authService->shouldReceive('oauthLogin')->once()->with('ada@example.test')->andReturn(true);
        $session->shouldReceive('set')->once()->with('tfa_verified', true);

        $service = $this->makeService(
            $iR, $iaR, $pR, $cR, $sR, $uR, $uiR, $ucR, $urlR, $manager, $authService, $session, $savedInv,
        );

        $invId = $service->createOrder($this->customer('ada@example.test'), $this->items());

        Assert::same(900, $invId);
    }

    public function reusesTheExistingAccountForARepeatCustomer(): void
    {
        $client = $this->existingClient(51, 'bob@example.test');
        $savedInv = $this->savedInv(901);

        $existingLink = new UserClient();
        $existingLink->setUserId(201);
        $existingLink->setClientId(51);

        $bobUser = new User('bob@example.test', 'bob@example.test', 'irrelevant');
        $this->setPrivateId($bobUser, 201);
        $this->setPrivateId($bobUser->getIdentity(), 2010);

        /** @var iR&m\MockInterface $iR */
        $iR = m::mock(iR::class);
        /** @var iaR&m\MockInterface $iaR */
        $iaR = m::mock(iaR::class);
        /** @var pR&m\MockInterface $pR */
        $pR = m::mock(pR::class);
        /** @var cR&m\MockInterface $cR */
        $cR = m::mock(cR::class);
        /** @var sR&m\MockInterface $sR */
        $sR = m::mock(sR::class);
        /** @var uR&m\MockInterface $uR */
        $uR = m::mock(uR::class);
        /** @var uiR&m\MockInterface $uiR */
        $uiR = m::mock(uiR::class);
        /** @var ucR&m\MockInterface $ucR */
        $ucR = m::mock(ucR::class);
        /** @var urlR&m\MockInterface $urlR */
        $urlR = m::mock(urlR::class);
        /** @var Manager&m\MockInterface $manager */
        $manager = m::mock(Manager::class);
        /** @var AuthService&m\MockInterface $authService */
        $authService = m::mock(AuthService::class);
        /** @var SessionInterface&m\MockInterface $session */
        $session = m::mock(SessionInterface::class);

        $cR->shouldReceive('findByEmail')->once()->with('bob@example.test')->andReturn($client);
        $sR->shouldReceive('getSetting')->with('default_invoice_group')->andReturn('3');
        $pR->shouldReceive('repoProductquery')->with(7)->andReturn($this->trackedProduct());
        $iR->shouldReceive('save')->once()->with($savedInv);
        $iR->shouldReceive('repoInvLoadInvAmountquery')->once()->with(901)->andReturn($this->invWithAmount(901));
        $iaR->shouldReceive('save')->once();

        // Repeat order — existing UserClient link found, so no new
        // User/UserInv/UserClient/RBAC assignment happens.
        $ucR->shouldReceive('repoUserquery')->once()->with(51)->andReturn($existingLink);
        $uR->shouldReceive('findById')->with(201)->andReturn($bobUser);
        $uR->shouldNotReceive('save');
        $uiR->shouldNotReceive('save');
        $ucR->shouldNotReceive('save');
        $manager->shouldNotReceive('revokeAll');
        $manager->shouldNotReceive('assign');
        $urlR->shouldNotReceive('upsert');
        $authService->shouldReceive('oauthLogin')->once()->with('bob@example.test')->andReturn(true);
        $session->shouldReceive('set')->once()->with('tfa_verified', true);

        $service = $this->makeService(
            $iR, $iaR, $pR, $cR, $sR, $uR, $uiR, $ucR, $urlR, $manager, $authService, $session, $savedInv,
        );

        $invId = $service->createOrder($this->customer('bob@example.test'), $this->items());

        Assert::same(901, $invId);
    }

    /**
     * See App\Api\OrderService::addOrderItem()'s own docblock — the
     * InvItem must be billed at the exact price the customer was shown
     * on the storefront (App\Webshop\Catalog\CatalogQueryService::
     * toListing()'s webshopPrice()), never at the staff/B2B
     * product_price ("wholesale"), when the two diverge.
     */
    public function chargesTheInvItemAtWebshopPriceNotWholesaleProductPrice(): void
    {
        $client = $this->existingClient(52, 'carol@example.test');
        $savedInv = $this->savedInv(902);

        $existingLink = new UserClient();
        $existingLink->setUserId(202);
        $existingLink->setClientId(52);

        $carolUser = new User('carol@example.test', 'carol@example.test', 'irrelevant');
        $this->setPrivateId($carolUser, 202);
        $this->setPrivateId($carolUser->getIdentity(), 2020);

        /** @var iR&m\MockInterface $iR */
        $iR = m::mock(iR::class);
        /** @var iaR&m\MockInterface $iaR */
        $iaR = m::mock(iaR::class);
        /** @var pR&m\MockInterface $pR */
        $pR = m::mock(pR::class);
        /** @var cR&m\MockInterface $cR */
        $cR = m::mock(cR::class);
        /** @var sR&m\MockInterface $sR */
        $sR = m::mock(sR::class);
        /** @var uR&m\MockInterface $uR */
        $uR = m::mock(uR::class);
        /** @var uiR&m\MockInterface $uiR */
        $uiR = m::mock(uiR::class);
        /** @var ucR&m\MockInterface $ucR */
        $ucR = m::mock(ucR::class);
        /** @var urlR&m\MockInterface $urlR */
        $urlR = m::mock(urlR::class);
        /** @var Manager&m\MockInterface $manager */
        $manager = m::mock(Manager::class);
        /** @var AuthService&m\MockInterface $authService */
        $authService = m::mock(AuthService::class);
        /** @var SessionInterface&m\MockInterface $session */
        $session = m::mock(SessionInterface::class);

        $cR->shouldReceive('findByEmail')->once()->with('carol@example.test')->andReturn($client);
        $sR->shouldReceive('getSetting')->with('default_invoice_group')->andReturn('3');
        $pR->shouldReceive('repoProductquery')->with(7)->andReturn($this->trackedProductWithRetailPrice());
        $iR->shouldReceive('save')->once()->with($savedInv);
        $iR->shouldReceive('repoInvLoadInvAmountquery')->once()->with(902)->andReturn($this->invWithAmount(902));
        $iaR->shouldReceive('save')->once();

        $ucR->shouldReceive('repoUserquery')->once()->with(52)->andReturn($existingLink);
        $uR->shouldReceive('findById')->with(202)->andReturn($carolUser);
        $uR->shouldNotReceive('save');
        $uiR->shouldNotReceive('save');
        $ucR->shouldNotReceive('save');
        $manager->shouldNotReceive('revokeAll');
        $manager->shouldNotReceive('assign');
        $urlR->shouldNotReceive('upsert');
        $authService->shouldReceive('oauthLogin')->once()->with('carol@example.test')->andReturn(true);
        $session->shouldReceive('set')->once()->with('tfa_verified', true);

        $service = $this->makeService(
            $iR, $iaR, $pR, $cR, $sR, $uR, $uiR, $ucR, $urlR, $manager, $authService, $session, $savedInv,
            expectedItemPrice: 6.49,
        );

        $invId = $service->createOrder($this->customer('carol@example.test'), $this->items());

        Assert::same(902, $invId);
    }

    private function makeService(
        iR $iR,
        iaR $iaR,
        pR $pR,
        cR $cR,
        sR $sR,
        uR $uR,
        uiR $uiR,
        ucR $ucR,
        urlR $urlR,
        Manager $manager,
        AuthService $authService,
        SessionInterface $session,
        Inv $savedInv,
        // Asserted against the InvItem's own 'price' array key when given
        // — see chargesTheInvItemAtWebshopPriceNotWholesaleProductPrice(),
        // which needs this to confirm addOrderItem() actually bills at
        // Product::webshopPrice(), not just that addInvItemProduct() was
        // called at all (the other tests here only need the latter).
        ?float $expectedItemPrice = null,
    ): OrderService {
        /** @var IS&m\MockInterface $invService */
        $invService = m::mock(IS::class);
        $invService->shouldReceive('withTransaction')->andReturnUsing(static function (callable $fn): void {
            $fn();
        });
        $invService->shouldReceive('saveInv')->once()->andReturn($savedInv);

        /** @var iiR&m\MockInterface $iiR */
        $iiR = m::mock(iiR::class);
        /** @var gR&m\MockInterface $gR */
        $gR = m::mock(gR::class);
        /** @var trR&m\MockInterface $trR */
        $trR = m::mock(trR::class);
        /** @var unR&m\MockInterface $unR */
        $unR = m::mock(unR::class);

        /** @var InvItemService&m\MockInterface $invItemService */
        $invItemService = m::mock(InvItemService::class);
        if ($expectedItemPrice === null) {
            $invItemService->shouldReceive('addInvItemProduct')->once();
        } else {
            $invItemService->shouldReceive('addInvItemProduct')->once()->with(
                m::type(\App\Infrastructure\Persistence\InvItem\InvItem::class),
                m::on(static fn (array $itemBody): bool => ($itemBody['price'] ?? null) === $expectedItemPrice),
                m::type('string'),
                m::any(),
            );
        }

        /** @var iiaR&m\MockInterface $iiaRMock */
        $iiaRMock = m::mock(iiaR::class);
        /** @var iias&m\MockInterface $iiasMock */
        $iiasMock = m::mock(iias::class);
        $iiaDeps = new IiAddProductDeps($pR, $trR, $iiasMock, $iiaRMock, $sR, $unR);

        /** @var NumberHelper&m\MockInterface $numberHelper */
        $numberHelper = m::mock(NumberHelper::class);
        $numberHelper->shouldReceive('invCalculateTotalsofItemTotals')->once()->andReturn([
            'subtotal' => 19.98,
            'tax_total' => 0.00,
            'discount' => 0.00,
            'charge' => 0.00,
            'allowance' => 0.00,
            'total' => 19.98,
        ]);

        /** @var FormHydrator&m\MockInterface $formHydrator */
        $formHydrator = m::mock(FormHydrator::class);
        $formHydrator->shouldReceive('populateAndValidate')->once()->andReturn(true);

        return new OrderService(new OrderServiceDeps(
            $cR, $pR, $trR, $unR, $iR, $invService, $gR, $iaR, $iiR, $invItemService, $iiaDeps,
            $numberHelper, $sR, $uR, $uiR, $ucR, $urlR, $manager, $authService, $session, $formHydrator,
        ));
    }
}
