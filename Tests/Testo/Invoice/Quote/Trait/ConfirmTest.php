<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Quote\Trait;

use App\Auth\Permissions;
use App\Infrastructure\Persistence\Quote\Quote;
use App\Infrastructure\Persistence\QuoteAmount\QuoteAmount;
use App\Infrastructure\Persistence\SalesOrder\SalesOrder as SoEntity;
use App\Infrastructure\Persistence\SalesOrderAmount\SalesOrderAmount;
use App\Infrastructure\Persistence\User\User;
use App\Infrastructure\Persistence\UserClient\UserClient;
use App\Invoice\BaseController;
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
 * Quote\Trait\QuoteToSo::quoteToSoConfirm() -- the admin-side counterpart of
 * approve() (see ApproveTest, PR #1201): both convert a Quote into a
 * SalesOrder and both exercise the same quoteToSoQuoteAmount()/
 * SalesOrderAmount hydration cascade that hit the real production incident
 * documented in project_sales_order_amount_so_id_column_incident memory (a
 * stray so_id column on sales_order_amount). quoteToSoConfirm() had zero
 * test coverage before this file.
 *
 * Unlike approve() (guest/observer flow, looked up by url_key, gated on
 * activeUser()), quoteToSoConfirm() is looked up by a plain quote_id from
 * the request body and gated on "exactly one user account exists for this
 * client" -- a guard approve() doesn't have. When that guard fails,
 * $result stays null and the method falls through to the same
 * getNotFoundResponse() the not-found-quote guard uses; that's covered
 * here as its own case, distinct from an actual missing quote.
 *
 * As with ApproveTest, the five quoteToSo* helpers are kept as no-ops via
 * an empty-item quote (quoteToSoQuoteAmount is the one exception, run
 * unconditionally and exercised fully in the happy-path test) -- their own
 * per-item conversion logic is out of scope for this file. Response bodies
 * are verified via Mockery's own argument matching on
 * $factory->createResponse(...), not by reading a value back out of the
 * (mocked) response object afterward.
 */
#[Test]
final class ConfirmTest
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
        DataResponseFactoryInterface $factory,
        ?soS $soService = null,
        ?Response $notFoundResponse = null,
        ?UrlGenerator $urlGenerator = null,
    ): QuoteConfirmHarness {
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
        $session->shouldReceive('get')->with('_language')->andReturn('en');

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
        if ($urlGenerator === null) {
            /** @var UrlGenerator&m\MockInterface $urlGenerator */
            $urlGenerator = m::mock(UrlGenerator::class);
        }

        return new QuoteConfirmHarness(
            $webService, $userService, $translator, $webViewRenderer, $session, $sR, $flash,
            $factory, $soService, $soTaxRateService, $soCustomService, $soItemService, $soacService,
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
            'quote_id' => 9, 'client_id' => 5, 'group_id' => 3,
            'po_number' => 'PO1', 'po_person' => 'Jim', 'password' => '',
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
        ?GR $gR = null,
        ?ACQR $acqR = null,
        ?ACQIR $acqiR = null,
        ?QAR $qaR = null,
        ?QCR $qcR = null,
    ): QuoteConvertCoreDeps {
        /** @var PCS&m\MockInterface $pcS */
        $pcS = m::mock(PCS::class);
        if ($gR === null) {
            /** @var GR&m\MockInterface $gR */
            $gR = m::mock(GR::class);
            $gR->shouldNotReceive('generateNumber');
        }
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

    private function makeUserDeps(?UCR $ucR = null, ?UR $uR = null): QuoteConvertUserDeps
    {
        if ($uR === null) {
            /** @var UR&m\MockInterface $uR */
            $uR = m::mock(UR::class);
        }
        if ($ucR === null) {
            /** @var UCR&m\MockInterface $ucR */
            $ucR = m::mock(UCR::class);
        }
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

    /**
     * @return Quote&m\MockInterface
     */
    private function stubQuoteForSuccessfulGuards(): Quote
    {
        /** @var Quote&m\MockInterface $quote */
        $quote = m::mock(Quote::class);
        $quote->shouldReceive('getSoId')->andReturn(0);
        $quote->shouldReceive('getDiscountAmount')->andReturn(0.0);
        $quote->shouldReceive('getUrlKey')->andReturn('the-key');
        return $quote;
    }

    public function confirmReturnsNotFoundWhenNoQuoteMatchesTheId(): void
    {
        /** @var QR&m\MockInterface $qR */
        $qR = m::mock(QR::class);
        $qR->shouldReceive('repoQuoteUnLoadedquery')->once()->with(9)->andReturn(null);
        $qR->shouldNotReceive('save');

        /** @var Response&m\MockInterface $notFound */
        $notFound = m::mock(Response::class);
        $harness = $this->makeHarness([], $this->makeUnusedFactory(), notFoundResponse: $notFound);

        $result = $harness->quoteToSoConfirm(
            $this->defaultRequest(),
            $this->makeFormHydrator(true),
            $this->makeCoreDeps($qR),
            $this->makeItemDeps(),
            $this->makeUserDeps(),
            $this->makeTransferDeps(),
        );

        Assert::same($result, $notFound);
    }

    public function confirmReturnsUnsuccessfulWhenTheQuoteHasAlreadyBeenConverted(): void
    {
        /** @var Quote&m\MockInterface $quote */
        $quote = m::mock(Quote::class);
        $quote->shouldReceive('getSoId')->andReturn(77);
        $quote->shouldNotReceive('getDiscountAmount');
        $quote->shouldNotReceive('getUrlKey');

        /** @var QR&m\MockInterface $qR */
        $qR = m::mock(QR::class);
        $qR->shouldReceive('repoQuoteUnLoadedquery')->once()->with(9)->andReturn($quote);
        $qR->shouldNotReceive('save');

        [$factory, $response] = $this->makeFactoryExpecting(
            Json::encode(['success' => 0, 'flash_message' => 'quote.sales.order.already.created.from.quote']),
        );
        $harness = $this->makeHarness([], $factory);

        $result = $harness->quoteToSoConfirm(
            $this->defaultRequest(),
            $this->makeFormHydrator(true),
            $this->makeCoreDeps($qR),
            $this->makeItemDeps(),
            $this->makeUserDeps(),
            $this->makeTransferDeps(),
        );

        Assert::same($result, $response);
    }

    public function confirmReturnsUnsuccessfulWhenFormValidationFails(): void
    {
        $quote = $this->stubQuoteForSuccessfulGuards();

        /** @var QR&m\MockInterface $qR */
        $qR = m::mock(QR::class);
        $qR->shouldReceive('repoQuoteUnLoadedquery')->once()->with(9)->andReturn($quote);
        $qR->shouldNotReceive('save');

        [$factory, $response] = $this->makeFactoryExpecting(
            Json::encode(['success' => 0, 'flash_message' => 'quote.sales.order.not.created.from.quote']),
        );
        $harness = $this->makeHarness([], $factory);

        $result = $harness->quoteToSoConfirm(
            $this->defaultRequest(),
            $this->makeFormHydrator(false),
            $this->makeCoreDeps($qR),
            $this->makeItemDeps(),
            $this->makeUserDeps(),
            $this->makeTransferDeps(),
        );

        Assert::same($result, $response);
    }

    public function confirmReturnsNotFoundWhenNoSingleUserAccountMatchesTheClient(): void
    {
        $quote = $this->stubQuoteForSuccessfulGuards();

        /** @var QR&m\MockInterface $qR */
        $qR = m::mock(QR::class);
        $qR->shouldReceive('repoQuoteUnLoadedquery')->once()->with(9)->andReturn($quote);
        $qR->shouldNotReceive('save');

        /** @var UCR&m\MockInterface $ucR */
        $ucR = m::mock(UCR::class);
        $ucR->shouldReceive('repoUserquery')->once()->with(5)->andReturn(null);
        $ucR->shouldReceive('repoUserqueryCount')->once()->with(5)->andReturn(0);

        /** @var Response&m\MockInterface $notFound */
        $notFound = m::mock(Response::class);
        // The form-invalid unsuccessful branch never touches the factory
        // here -- this guard leaves $result null, falling through to the
        // same not-found response the missing-quote guard uses.
        $harness = $this->makeHarness([], $this->makeUnusedFactory(), notFoundResponse: $notFound);

        $result = $harness->quoteToSoConfirm(
            $this->defaultRequest(),
            $this->makeFormHydrator(true),
            $this->makeCoreDeps($qR),
            $this->makeItemDeps(),
            $this->makeUserDeps($ucR),
            $this->makeTransferDeps(),
        );

        Assert::same($result, $notFound);
    }

    public function confirmConvertsTheQuoteToASalesOrderOnTheHappyPath(): void
    {
        $quote = $this->stubQuoteForSuccessfulGuards();
        $quote->shouldReceive('setSoId')->once()->with(42);

        /** @var QR&m\MockInterface $qR */
        $qR = m::mock(QR::class);
        $qR->shouldReceive('repoQuoteUnLoadedquery')->once()->with(9)->andReturn($quote);
        $qR->shouldReceive('save')->once()->with($quote);

        /** @var GR&m\MockInterface $gR */
        $gR = m::mock(GR::class);
        $gR->shouldReceive('generateNumber')->once()->with(3, true)->andReturn('SO-0099');

        /** @var UserClient&m\MockInterface $userClient */
        $userClient = m::mock(UserClient::class);
        $userClient->shouldReceive('reqUserId')->once()->andReturn(11);

        /** @var UCR&m\MockInterface $ucR */
        $ucR = m::mock(UCR::class);
        $ucR->shouldReceive('repoUserquery')->once()->with(5)->andReturn($userClient);
        $ucR->shouldReceive('repoUserqueryCount')->once()->with(5)->andReturn(1);

        /** @var User&m\MockInterface $user */
        $user = m::mock(User::class);
        /** @var UR&m\MockInterface $uR */
        $uR = m::mock(UR::class);
        $uR->shouldReceive('findById')->once()->with(11)->andReturn($user);

        /** @var soS&m\MockInterface $soService */
        $soService = m::mock(soS::class);
        $soService->shouldReceive('addSo')->once()->andReturnUsing(
            static function (User $u, SoEntity $so) {
                $so->setId(42);
                return $so;
            },
        );

        // The five quoteToSo* helpers -- kept as no-ops here by giving the
        // quote zero items/tax-rates/custom-fields/allowance-charges;
        // their own per-item conversion logic is out of scope for this
        // file (see class docblock). quoteToSoQuoteAmount() is the one
        // exception -- it runs unconditionally, so it's exercised fully.
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

        /** @var UrlGenerator&m\MockInterface $urlGenerator */
        $urlGenerator = m::mock(UrlGenerator::class);
        $urlGenerator->shouldReceive('generate')->once()
            ->with('salesorder/view', ['_language' => 'en', 'id' => 42])
            ->andReturn('/en/salesorder/view?id=42');

        [$factory, $response] = $this->makeFactoryExpecting(Json::encode([
            'success' => 1,
            'flash_message' => 'quote.sales.order.created.from.quote',
            'redirect_url' => '/en/salesorder/view?id=42',
        ]));
        $harness = $this->makeHarness([], $factory, $soService, urlGenerator: $urlGenerator);

        $result = $harness->quoteToSoConfirm(
            $this->defaultRequest(),
            $this->makeFormHydrator(true),
            $this->makeCoreDeps($qR, $gR, acqR: $acqR, qaR: $qaR, qcR: $qcR),
            $this->makeItemDeps($qiR, $qtrR),
            $this->makeUserDeps($ucR, $uR),
            $this->makeTransferDeps($soR),
        );

        Assert::same($result, $response);
    }
}

/**
 * Test-only harness -- composes BaseController with just the Quote
 * quoteToSoConfirm()/QuoteToSo trait under test, sidestepping
 * QuoteController's own much larger constructor entirely.
 *
 * activeUser()/rbacObserver() are declared but never exercised by any test
 * in this file -- they're needed only because QuoteToSo also declares
 * approve() (covered via ApproveTest, PR #1201) and reject() (covered via
 * PR #1197), and Psalm type-checks a trait's entire surface against
 * whatever class composes it, not just the methods a given test happens to
 * call.
 */
final class QuoteConfirmHarness extends BaseController
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
        private readonly UrlGenerator $url_generator,
    ) {
        parent::__construct($webService, $userService, $translator, $webViewRenderer, $session, $sR, $flash);
    }

    /** @var list<array{int, UR, UCR, UIR}> */
    public array $activeUserCalls = [];

    // Stub -- never exercised by any test here (this file covers
    // quoteToSoConfirm() only; approve(), the one real caller of
    // activeUser(), is already covered via ApproveTest/PR #1201). Declared
    // solely because Psalm type-checks QuoteToSo's entire surface against
    // whatever class composes it; records its arguments on the same
    // principle used throughout this session rather than ignoring them.
    protected function activeUser(int $client_id, UR $uR, UCR $ucR, UIR $uiR): ?User
    {
        $this->activeUserCalls[] = [$client_id, $uR, $ucR, $uiR];
        return null;
    }

    /** @var list<array{Quote, UCR, UIR}> */
    public array $rbacObserverCalls = [];

    // Stub -- never exercised by any test here (reject(), the one real
    // caller of rbacObserver(), is already covered via PR #1197).
    protected function rbacObserver(Quote $quote, UCR $ucR, UIR $uiR): bool
    {
        $this->rbacObserverCalls[] = [$quote, $ucR, $uiR];
        return false;
    }
}
