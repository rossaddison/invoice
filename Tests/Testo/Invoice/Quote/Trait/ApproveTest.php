<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Quote\Trait;

use App\Auth\Permissions;
use App\Infrastructure\Persistence\Quote\Quote;
use App\Infrastructure\Persistence\QuoteAmount\QuoteAmount;
use App\Infrastructure\Persistence\SalesOrder\SalesOrder as SoEntity;
use App\Infrastructure\Persistence\SalesOrderAmount\SalesOrderAmount;
use App\Infrastructure\Persistence\User\User;
use App\Invoice\BaseController;
use App\Invoice\Group\Exception\GroupException;
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\ProductClient\ProductClientService as PCS;
use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\QuoteAllowanceCharge\QuoteAllowanceChargeRepository as ACQR;
use App\Invoice\QuoteAmount\QuoteAmountRepository as QAR;
use App\Invoice\Quote\QuoteConvertCoreDeps;
use App\Invoice\Quote\QuoteConvertItemDeps;
use App\Invoice\Quote\QuoteConvertUserDeps;
use App\Invoice\QuoteCustom\QuoteCustomRepository as QCR;
use App\Invoice\QuoteItemAllowanceCharge\QuoteItemAllowanceChargeRepository as ACQIR;
use App\Invoice\QuoteItem\QuoteItemRepository as QIR;
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\QuoteTaxRate\QuoteTaxRateRepository as QTRR;
use App\Invoice\Quote\QuoteToSoTransferDeps;
use App\Invoice\Quote\Trait\QuoteToSo as QuoteToSoTrait;
use App\Invoice\SalesOrderAllowanceCharge\SalesOrderAllowanceChargeService as soACS;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use App\Invoice\SalesOrder\SalesOrderService as soS;
use App\Invoice\CustomField\CustomFieldRepository as CFR;
use App\Invoice\SalesOrderCustom\SalesOrderCustomService as soCS;
use App\Invoice\SalesOrderItemAllowanceCharge\SalesOrderItemAllowanceChargeRepository as ACSOIR;
use App\Invoice\SalesOrderItemAmount\SalesOrderItemAmountRepository as soIAR;
use App\Invoice\SalesOrderItemAmount\SalesOrderItemAmountService as soIAS;
use App\Invoice\SalesOrderItem\SalesOrderItemService as soIS;
use App\Invoice\SalesOrderTaxRate\SalesOrderTaxRateService as soTRS;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Task\TaskRepository as TASKR;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use App\Invoice\Unit\UnitRepository as UNR;
use App\Invoice\UserClient\UserClientRepository as UCR;
use App\Invoice\UserInv\UserInvRepository as UIR;
use App\Service\WebControllerService;
use App\User\UserRepository as UR;
use App\User\UserService;
use Mockery as m;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Test;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Json\Json;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Quote\Trait\QuoteToSo::approve() -- the guest/observer quote-approval
 * flow that converts a Quote into a SalesOrder -- had zero unit test
 * coverage before this, despite being exactly the method that hit a real
 * production incident the same day (a stray so_id column on
 * sales_order_amount blocked every SalesOrderService::addSo() insert --
 * see project_sales_order_amount_so_id_column_incident memory). No PHP-level
 * mock-based test could have caught that specific bug (it was a live DB
 * schema drift, invisible to mocks), but approve()'s own substantial
 * business logic -- guard ordering, the RBAC/active-user check, the
 * already-converted short-circuit, and the happy path's quote->salesorder
 * field/state transitions -- had never been exercised at all.
 *
 * QuoteToSo is a large, multi-purpose trait -- see
 * project_quote_reject_undefined_message_fix memory's note that a
 * minimal-harness technique "doesn't scale" to it. This file covers only
 * approve() specifically -- reject() is already covered via PR #1197, and
 * quoteToSoConfirm()/the five private quoteToSo* helpers' own deeper
 * per-item conversion logic are deliberately left for a future pass; the
 * happy-path test here keeps them in scope only by giving the quote zero
 * items/tax-rates/custom-fields/allowance-charges, so those loops are
 * no-ops.
 *
 * Response bodies are verified via Mockery's own argument matching on
 * $factory->createResponse(...) -- asserting the exact JSON string passed
 * in -- rather than trying to read a value back out of the (mocked)
 * response object afterward.
 */
#[Test]
final class ApproveTest
{
    /**
     * @param array<array-key, object> $items
     */
    private function entityReaderOf(array $items): EntityReader
    {
        /** @var EntityReader&m\MockInterface $reader */
        $reader = m::mock(EntityReader::class);
        $reader->shouldReceive('getIterator')->andReturnUsing(
            static function () use ($items) {
                yield from $items;
            },
        );
        return $reader;
    }

    /**
     * @param array<string, string> $settings
     */
    private function makeHarness(
        array $settings,
        ?User $activeUser,
        DataResponseFactoryInterface $factory,
        ?soS $soService = null,
        ?Response $notFoundResponse = null,
    ): QuoteApproveHarness {
        /** @var WebControllerService&m\MockInterface $webService */
        $webService = m::mock(WebControllerService::class);
        $webService->shouldReceive('getNotFoundResponse')
            ->andReturn($notFoundResponse ?? m::mock(Response::class));

        /** @var UserService&m\MockInterface $userService */
        $userService = m::mock(UserService::class);
        $userService->shouldReceive('hasPermission')->with(Permissions::VIEW_INV)->andReturn(true);
        $userService->shouldReceive('hasPermission')->with(Permissions::EDIT_INV)->andReturn(true);

        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturnUsing(static fn (string $key) => $key);

        /** @var SessionInterface&m\MockInterface $session */
        $session = m::mock(SessionInterface::class);
        $session->shouldReceive('getId')->andReturn('test-session-id');
        $session->shouldReceive('get')->with('tfa_verified')->andReturn(true);

        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        foreach ($settings as $key => $value) {
            $sR->shouldReceive('getSetting')->with($key)->andReturn($value);
        }

        /** @var Flash&m\MockInterface $flash */
        $flash = m::mock(Flash::class);
        $flash->shouldReceive('has')->andReturn(false);
        $flash->shouldReceive('add');

        /** @var WebViewRenderer&m\MockInterface $webViewRenderer */
        $webViewRenderer = m::mock(WebViewRenderer::class);
        $webViewRenderer->shouldReceive('withControllerName')->once()->with('base')->andReturnSelf();
        $webViewRenderer->shouldReceive('withLayout')->once()
            ->with('@views/invoice/layout/fullpage-loader.php')->andReturnSelf();
        $webViewRenderer->shouldReceive('withLayout')->once()
            ->with('@views/layout/invoice.php')->andReturnSelf();

        /** @var soS&m\MockInterface $soService */
        $soService = $soService ?? m::mock(soS::class);
        /** @var soTRS&m\MockInterface $soTaxRateService */
        $soTaxRateService = m::mock(soTRS::class);
        /** @var soCS&m\MockInterface $soCustomService */
        $soCustomService = m::mock(soCS::class);
        /** @var soIS&m\MockInterface $soItemService */
        $soItemService = m::mock(soIS::class);
        /** @var soACS&m\MockInterface $soacService */
        $soacService = m::mock(soACS::class);
        /** @var UrlGenerator&m\MockInterface $urlGenerator */
        $urlGenerator = m::mock(UrlGenerator::class);

        return new QuoteApproveHarness(
            $webService,
            $userService,
            $translator,
            $webViewRenderer,
            $session,
            $sR,
            $flash,
            $factory,
            $soService,
            $soTaxRateService,
            $soCustomService,
            $soItemService,
            $soacService,
            $activeUser,
            $urlGenerator,
        );
    }

    /**
     * @param array<string, mixed> $queryParams
     */
    private function makeRequest(array $queryParams): Request
    {
        /** @var Request&m\MockInterface $request */
        $request = m::mock(Request::class);
        $request->shouldReceive('getQueryParams')->andReturn($queryParams);
        return $request;
    }

    private function defaultRequest(): Request
    {
        return $this->makeRequest([
            'url_key' => 'the-key', 'client_po_number' => 'PO1', 'client_po_person' => 'Jim',
        ]);
    }

    private function makeFormHydrator(bool $validates): FormHydrator
    {
        /** @var FormHydrator&m\MockInterface $formHydrator */
        $formHydrator = m::mock(FormHydrator::class);
        $formHydrator->shouldReceive('populateAndValidate')->andReturn($validates);
        return $formHydrator;
    }

    /**
     * @return array{0: DataResponseFactoryInterface, 1: Response}
     */
    private function makeFactoryExpecting(string $expectedJson): array
    {
        /** @var Response&m\MockInterface $response */
        $response = m::mock(Response::class);
        /** @var DataResponseFactoryInterface&m\MockInterface $factory */
        $factory = m::mock(DataResponseFactoryInterface::class);
        $factory->shouldReceive('createResponse')->once()->with($expectedJson)->andReturn($response);
        return [$factory, $response];
    }

    private function makeCoreDeps(
        QR $qR,
        GR $gR,
        ?ACQR $acqR = null,
        ?ACQIR $acqiR = null,
        ?QAR $qaR = null,
        ?QCR $qcR = null,
    ): QuoteConvertCoreDeps {
        /** @var PCS&m\MockInterface $pcS */
        $pcS = m::mock(PCS::class);
        if ($acqR === null) {
            /** @var ACQR&m\MockInterface $acqR */
            $acqR = m::mock(ACQR::class);
        }
        if ($acqiR === null) {
            /** @var ACQIR&m\MockInterface $acqiR */
            $acqiR = m::mock(ACQIR::class);
        }
        if ($qaR === null) {
            /** @var QAR&m\MockInterface $qaR */
            $qaR = m::mock(QAR::class);
        }
        if ($qcR === null) {
            /** @var QCR&m\MockInterface $qcR */
            $qcR = m::mock(QCR::class);
        }
        return new QuoteConvertCoreDeps($gR, $pcS, $qR, $acqR, $acqiR, $qaR, $qcR);
    }

    private function makeItemDeps(?QIR $qiR = null, ?QTRR $qtrR = null): QuoteConvertItemDeps
    {
        /** @var PR&m\MockInterface $pR */
        $pR = m::mock(PR::class);
        if ($qiR === null) {
            /** @var QIR&m\MockInterface $qiR */
            $qiR = m::mock(QIR::class);
        }
        if ($qtrR === null) {
            /** @var QTRR&m\MockInterface $qtrR */
            $qtrR = m::mock(QTRR::class);
        }
        /** @var TASKR&m\MockInterface $taskR */
        $taskR = m::mock(TASKR::class);
        /** @var TRR&m\MockInterface $trR */
        $trR = m::mock(TRR::class);
        /** @var UNR&m\MockInterface $unR */
        $unR = m::mock(UNR::class);
        return new QuoteConvertItemDeps($pR, $qiR, $qtrR, $taskR, $trR, $unR);
    }

    private function makeUserDeps(): QuoteConvertUserDeps
    {
        /** @var UR&m\MockInterface $uR */
        $uR = m::mock(UR::class);
        /** @var UCR&m\MockInterface $ucR */
        $ucR = m::mock(UCR::class);
        /** @var UIR&m\MockInterface $uiR */
        $uiR = m::mock(UIR::class);
        return new QuoteConvertUserDeps($uR, $ucR, $uiR);
    }

    private function makeTransferDeps(?SOR $soR = null): QuoteToSoTransferDeps
    {
        /** @var CFR&m\MockInterface $cfR */
        $cfR = m::mock(CFR::class);
        if ($soR === null) {
            /** @var SOR&m\MockInterface $soR */
            $soR = m::mock(SOR::class);
        }
        /** @var ACSOIR&m\MockInterface $acsoiR */
        $acsoiR = m::mock(ACSOIR::class);
        /** @var soIAR&m\MockInterface $soiaR */
        $soiaR = m::mock(soIAR::class);
        /** @var soIAS&m\MockInterface $soiaS */
        $soiaS = m::mock(soIAS::class);
        return new QuoteToSoTransferDeps($cfR, $soR, $acsoiR, $soiaR, $soiaS);
    }

    private function makeUnusedFactory(): DataResponseFactoryInterface
    {
        /** @var DataResponseFactoryInterface&m\MockInterface $factory */
        $factory = m::mock(DataResponseFactoryInterface::class);
        $factory->shouldNotReceive('createResponse');
        return $factory;
    }

    private function makeUnusedGr(): GR
    {
        /** @var GR&m\MockInterface $gR */
        $gR = m::mock(GR::class);
        $gR->shouldNotReceive('generateNumber');
        return $gR;
    }

    public function approveReturnsNotFoundWhenUrlKeyIsEmpty(): void
    {
        /** @var QR&m\MockInterface $qR */
        $qR = m::mock(QR::class);
        $qR->shouldNotReceive('repoUrlKeyGuestCount');

        /** @var Response&m\MockInterface $notFound */
        $notFound = m::mock(Response::class);
        // Not-found path never touches the factory.
        $harness = $this->makeHarness([], null, $this->makeUnusedFactory(), notFoundResponse: $notFound);

        $result = $harness->approve(
            $this->makeRequest(['url_key' => '', 'client_po_number' => '', 'client_po_person' => '']),
            $this->makeFormHydrator(true),
            $this->makeCoreDeps($qR, $this->makeUnusedGr()),
            $this->makeItemDeps(),
            $this->makeUserDeps(),
            $this->makeTransferDeps(),
        );

        Assert::same($result, $notFound);
    }

    public function approveReturnsNotFoundWhenNoQuoteMatchesTheUrlKey(): void
    {
        /** @var QR&m\MockInterface $qR */
        $qR = m::mock(QR::class);
        $qR->shouldReceive('repoUrlKeyGuestCount')->once()->with('the-key')->andReturn(0);
        $qR->shouldNotReceive('repoUrlKeyGuestLoaded');

        /** @var Response&m\MockInterface $notFound */
        $notFound = m::mock(Response::class);
        $harness = $this->makeHarness([], null, $this->makeUnusedFactory(), notFoundResponse: $notFound);

        $result = $harness->approve(
            $this->defaultRequest(),
            $this->makeFormHydrator(true),
            $this->makeCoreDeps($qR, $this->makeUnusedGr()),
            $this->makeItemDeps(),
            $this->makeUserDeps(),
            $this->makeTransferDeps(),
        );

        Assert::same($result, $notFound);
    }

    #[ExpectException(GroupException::class)]
    public function approveThrowsWhenNoSalesOrderNumberCanBeGenerated(): void
    {
        /** @var Quote&m\MockInterface $quote */
        $quote = m::mock(Quote::class);

        /** @var QR&m\MockInterface $qR */
        $qR = m::mock(QR::class);
        $qR->shouldReceive('repoUrlKeyGuestCount')->once()->with('the-key')->andReturn(1);
        $qR->shouldReceive('repoUrlKeyGuestLoaded')->once()->with('the-key')->andReturn($quote);

        /** @var GR&m\MockInterface $gR */
        $gR = m::mock(GR::class);
        $gR->shouldReceive('generateNumber')->once()->with(3, true)->andReturn(null);

        [$factory] = $this->makeFactoryExpecting('unused');
        $harness = $this->makeHarness(['default_sales_order_group' => '3'], null, $factory);

        $harness->approve(
            $this->defaultRequest(),
            $this->makeFormHydrator(true),
            $this->makeCoreDeps($qR, $gR),
            $this->makeItemDeps(),
            $this->makeUserDeps(),
            $this->makeTransferDeps(),
        );
    }

    /**
     * @return Quote&m\MockInterface
     */
    private function stubQuoteForSuccessfulGuards(): Quote
    {
        /** @var Quote&m\MockInterface $quote */
        $quote = m::mock(Quote::class);
        $quote->shouldReceive('reqId')->andReturn(9);
        $quote->shouldReceive('reqClientId')->andReturn(3);
        $quote->shouldReceive('getDiscountAmount')->andReturn(0.0);
        $quote->shouldReceive('getUrlKey')->andReturn('the-key');
        $quote->shouldReceive('getPassword')->andReturn(null);
        $quote->shouldReceive('getNotes')->andReturn(null);
        return $quote;
    }

    public function approveReturnsUnsuccessfulWhenFormValidationFails(): void
    {
        $quote = $this->stubQuoteForSuccessfulGuards();
        $quote->shouldNotReceive('getSoId');

        /** @var QR&m\MockInterface $qR */
        $qR = m::mock(QR::class);
        $qR->shouldReceive('repoUrlKeyGuestCount')->once()->with('the-key')->andReturn(1);
        $qR->shouldReceive('repoUrlKeyGuestLoaded')->once()->with('the-key')->andReturn($quote);
        $qR->shouldNotReceive('save');

        /** @var GR&m\MockInterface $gR */
        $gR = m::mock(GR::class);
        $gR->shouldReceive('generateNumber')->once()->with(3, true)->andReturn('SO-0099');

        [$factory, $response] = $this->makeFactoryExpecting(Json::encode(['success' => 0]));
        $harness = $this->makeHarness(['default_sales_order_group' => '3'], null, $factory);

        $result = $harness->approve(
            $this->defaultRequest(),
            $this->makeFormHydrator(false),
            $this->makeCoreDeps($qR, $gR),
            $this->makeItemDeps(),
            $this->makeUserDeps(),
            $this->makeTransferDeps(),
        );

        Assert::same($result, $response);
    }

    public function approveReturnsUnsuccessfulWhenTheQuoteHasAlreadyBeenConverted(): void
    {
        $quote = $this->stubQuoteForSuccessfulGuards();
        $quote->shouldReceive('getSoId')->andReturn(77);

        /** @var QR&m\MockInterface $qR */
        $qR = m::mock(QR::class);
        $qR->shouldReceive('repoUrlKeyGuestCount')->once()->with('the-key')->andReturn(1);
        $qR->shouldReceive('repoUrlKeyGuestLoaded')->once()->with('the-key')->andReturn($quote);
        $qR->shouldNotReceive('save');

        /** @var GR&m\MockInterface $gR */
        $gR = m::mock(GR::class);
        $gR->shouldReceive('generateNumber')->once()->with(3, true)->andReturn('SO-0099');

        [$factory, $response] = $this->makeFactoryExpecting(Json::encode(['success' => 0]));
        $harness = $this->makeHarness(['default_sales_order_group' => '3'], null, $factory);

        $result = $harness->approve(
            $this->defaultRequest(),
            $this->makeFormHydrator(true),
            $this->makeCoreDeps($qR, $gR),
            $this->makeItemDeps(),
            $this->makeUserDeps(),
            $this->makeTransferDeps(),
        );

        Assert::same($result, $response);
    }

    public function approveReturnsUnsuccessfulWhenNoActiveUserIsFound(): void
    {
        $quote = $this->stubQuoteForSuccessfulGuards();
        $quote->shouldReceive('getSoId')->andReturn(0);

        /** @var QR&m\MockInterface $qR */
        $qR = m::mock(QR::class);
        $qR->shouldReceive('repoUrlKeyGuestCount')->once()->with('the-key')->andReturn(1);
        $qR->shouldReceive('repoUrlKeyGuestLoaded')->once()->with('the-key')->andReturn($quote);
        $qR->shouldNotReceive('save');

        /** @var GR&m\MockInterface $gR */
        $gR = m::mock(GR::class);
        $gR->shouldReceive('generateNumber')->once()->with(3, true)->andReturn('SO-0099');

        [$factory, $response] = $this->makeFactoryExpecting(Json::encode(['success' => 0]));
        // activeUser stubbed to null via the harness constructor.
        $harness = $this->makeHarness(['default_sales_order_group' => '3'], null, $factory);

        $result = $harness->approve(
            $this->defaultRequest(),
            $this->makeFormHydrator(true),
            $this->makeCoreDeps($qR, $gR),
            $this->makeItemDeps(),
            $this->makeUserDeps(),
            $this->makeTransferDeps(),
        );

        Assert::same($result, $response);
    }

    public function approveConvertsTheQuoteToASalesOrderOnTheHappyPath(): void
    {
        $quote = $this->stubQuoteForSuccessfulGuards();
        $quote->shouldReceive('getSoId')->andReturn(0);
        $quote->shouldReceive('setSoId')->once()->with(42);
        $quote->shouldReceive('setStatusId')->once()->with(4);

        /** @var QR&m\MockInterface $qR */
        $qR = m::mock(QR::class);
        $qR->shouldReceive('repoUrlKeyGuestCount')->once()->with('the-key')->andReturn(1);
        $qR->shouldReceive('repoUrlKeyGuestLoaded')->once()->with('the-key')->andReturn($quote);
        $qR->shouldReceive('save')->once()->with($quote);

        /** @var GR&m\MockInterface $gR */
        $gR = m::mock(GR::class);
        $gR->shouldReceive('generateNumber')->once()->with(3, true)->andReturn('SO-0099');

        /** @var User&m\MockInterface $activeUser */
        $activeUser = m::mock(User::class);

        /** @var soS&m\MockInterface $soService */
        $soService = m::mock(soS::class);
        $soService->shouldReceive('addSo')->once()->andReturnUsing(
            static function (User $user, SoEntity $so) {
                $so->setId(42);
                return $so;
            },
        );

        // The five quoteToSo* helpers -- kept as no-ops here by giving the
        // quote zero items/tax-rates/custom-fields/allowance-charges;
        // their own per-item conversion logic is out of scope for this
        // file (see class docblock). quoteToSoQuoteAmount() is the one
        // exception -- it runs unconditionally (no per-item loop), so it's
        // exercised fully below.
        /** @var QIR&m\MockInterface $qiR */
        $qiR = m::mock(QIR::class);
        $qiR->shouldReceive('repoQuoteItemIdquery')->once()->with(9)->andReturn($this->entityReaderOf([]));
        /** @var QTRR&m\MockInterface $qtrR */
        $qtrR = m::mock(QTRR::class);
        $qtrR->shouldReceive('repoQuotequery')->once()->with(9)->andReturn($this->entityReaderOf([]));
        /** @var QCR&m\MockInterface $qcR */
        $qcR = m::mock(QCR::class);
        $qcR->shouldReceive('repoFields')->once()->with(9)->andReturn($this->entityReaderOf([]));
        /** @var ACQR&m\MockInterface $acqR */
        $acqR = m::mock(ACQR::class);
        $acqR->shouldReceive('repoACQquery')->once()->with(9)->andReturn($this->entityReaderOf([]));

        /** @var QuoteAmount&m\MockInterface $basisQuote */
        $basisQuote = m::mock(QuoteAmount::class);
        $basisQuote->shouldReceive('getItemSubtotal')->andReturn(100.0);
        $basisQuote->shouldReceive('getItemTaxTotal')->andReturn(20.0);
        $basisQuote->shouldReceive('getPackhandleshipTotal')->andReturn(0.0);
        $basisQuote->shouldReceive('getPackhandleshipTax')->andReturn(0.0);
        $basisQuote->shouldReceive('getTaxTotal')->andReturn(0.0);
        $basisQuote->shouldReceive('getTotal')->andReturn(120.0);
        /** @var QAR&m\MockInterface $qaR */
        $qaR = m::mock(QAR::class);
        $qaR->shouldReceive('repoQuotequery')->once()->with(9)->andReturn($basisQuote);

        /** @var SalesOrderAmount&m\MockInterface $soAmount */
        $soAmount = m::mock(SalesOrderAmount::class);
        $soAmount->shouldReceive('setSalesOrderId')->once()->with(42);
        $soAmount->shouldReceive('setItemSubtotal')->once()->with(100.0);
        $soAmount->shouldReceive('setItemTaxTotal')->once()->with(20.0);
        $soAmount->shouldReceive('setPackhandleshipTotal')->once()->with(0.0);
        $soAmount->shouldReceive('setPackhandleshipTax')->once()->with(0.0);
        $soAmount->shouldReceive('setTaxTotal')->once()->with(0.0);
        $soAmount->shouldReceive('setTotal')->once()->with(120.0);

        /** @var SoEntity&m\MockInterface $fetchedSo */
        $fetchedSo = m::mock(SoEntity::class);
        $fetchedSo->shouldReceive('getSalesOrderAmount')->once()->andReturn($soAmount);

        /** @var SOR&m\MockInterface $soR */
        $soR = m::mock(SOR::class);
        $soR->shouldReceive('repoSalesOrderUnLoadedquery')->once()->with(42)->andReturn($fetchedSo);
        $soR->shouldReceive('save')->once()->with($fetchedSo);

        [$factory, $response] = $this->makeFactoryExpecting(Json::encode(['success' => 1]));
        $harness = $this->makeHarness(['default_sales_order_group' => '3'], $activeUser, $factory, $soService);

        $result = $harness->approve(
            $this->defaultRequest(),
            $this->makeFormHydrator(true),
            $this->makeCoreDeps($qR, $gR, acqR: $acqR, qaR: $qaR, qcR: $qcR),
            $this->makeItemDeps($qiR, $qtrR),
            $this->makeUserDeps(),
            $this->makeTransferDeps($soR),
        );

        Assert::same($result, $response);
    }

}

/**
 * Test-only harness -- composes BaseController with just the Quote
 * approve()/QuoteToSo trait under test, sidestepping QuoteController's own
 * much larger constructor entirely. activeUser() is stubbed (declared
 * directly on QuoteController, not in a shared trait); the five
 * so_*_service properties QuoteToSo's other private helpers reference are
 * accepted here too so the same harness covers both the guard-only tests
 * and the happy path.
 *
 * rbacObserver()/$url_generator are declared but never exercised by any
 * test in this file -- they're needed only because QuoteToSo also declares
 * reject() and quoteToSoConfirm() (this file covers approve() only; reject()
 * is already covered via PR #1197), and Psalm type-checks a trait's entire
 * surface against whatever class composes it, not just the methods a given
 * test happens to call.
 */
final class QuoteApproveHarness extends BaseController
{
    use QuoteToSoTrait;

    public function __construct(
        WebControllerService $webService,
        UserService $userService,
        TranslatorInterface $translator,
        WebViewRenderer $webViewRenderer,
        SessionInterface $session,
        SettingRepository $sR,
        Flash $flash,
        private readonly DataResponseFactoryInterface $factory,
        private readonly soS $so_service,
        private readonly soTRS $so_tax_rate_service,
        private readonly soCS $so_custom_service,
        private readonly soIS $so_item_service,
        private readonly soACS $soac_service,
        private readonly ?User $activeUserStub,
        private readonly UrlGenerator $url_generator,
    ) {
        parent::__construct($webService, $userService, $translator, $webViewRenderer, $session, $sR, $flash);
    }

    /** @var list<array{int, UR, UCR, UIR}> */
    public array $activeUserCalls = [];

    // Stub -- QuoteController's real activeUser() isn't reachable from a
    // trait-only harness (it's declared directly on QuoteController, not in
    // a shared trait). Records every call's arguments so a test can assert
    // on exactly what the trait under test passed, rather than ignoring them.
    protected function activeUser(int $client_id, UR $uR, UCR $ucR, UIR $uiR): ?User
    {
        $this->activeUserCalls[] = [$client_id, $uR, $ucR, $uiR];
        return $this->activeUserStub;
    }

    /** @var list<array{Quote, UCR, UIR}> */
    public array $rbacObserverCalls = [];

    // Stub -- never exercised by any test here (this file covers approve()
    // only; reject(), the one real caller of rbacObserver(), is already
    // covered via PR #1197). Declared solely because Psalm type-checks
    // QuoteToSo's entire surface against whatever class composes it; records
    // its arguments on the same principle as activeUser() above rather than
    // ignoring them outright.
    protected function rbacObserver(Quote $quote, UCR $ucR, UIR $uiR): bool
    {
        $this->rbacObserverCalls[] = [$quote, $ucR, $uiR];
        return false;
    }
}
