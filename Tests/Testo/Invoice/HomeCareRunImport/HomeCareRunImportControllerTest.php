<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\HomeCareRunImport;

use App\Auth\Permissions;
use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\User\User;
use App\Invoice\CategoryPrimary\CategoryPrimaryRepository;
use App\Invoice\CategorySecondary\CategorySecondaryRepository;
use App\Invoice\HomeCareRunImport\HomeCareRunImportController;
use App\Invoice\HomeCareRunImport\ParsedRun;
use App\Invoice\HomeCareRunImport\RunHouseListParser;
use App\Invoice\HomeCareRunImport\RunImportResult;
use App\Invoice\HomeCareRunImport\RunImportService;
use App\Invoice\HomeCareRunImport\RunSheetCsv;
use App\Invoice\Product\ProductRepository;
use App\Invoice\Setting\SettingRepository;
use App\Service\WebControllerService;
use App\User\UserService;
use Mockery as m;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * The run import screens: paste or upload, preview, download the sheet, save. The save itself is
 * RunImportService (tested on its own); here it is a mock, so these tests are about what the
 * controller reads from the request and where it sends the user.
 */
#[Test]
final class HomeCareRunImportControllerTest
{
    private const string TEXT = "RUN ONE\nEXAMPLE CRESCENT AB1 2CD (2)\n3\n14\n";
    private const string SHEET = "street,postcode,number,client_name,price\r\nExample Crescent,AB1 2CD,3,Mrs A,9.5\r\n";

    /** @var \ArrayObject<int, array{string, string}> */
    private \ArrayObject $flashes;

    /** @var \ArrayObject<int, array{string, array<string, mixed>}> */
    private \ArrayObject $rendered;

    private Psr17Factory $http;

    public function __construct()
    {
        $this->flashes = new \ArrayObject();
        $this->rendered = new \ArrayObject();
        $this->http = new Psr17Factory();
        $this->redirects = new \ArrayObject();
    }

    /**
     * @return array{HomeCareRunImportController, Response&m\MockInterface, Response&m\MockInterface}
     */
    private function controller(
        ?RunImportService $importer = null,
        ?Product $product = null,
        bool $signedIn = true,
    ): array {
        /** @var UserService&m\MockInterface $userService */
        $userService = m::mock(UserService::class);
        $userService->shouldReceive('getUser')->andReturn($signedIn ? new User('admin', 'a@b.test', 'x') : null);
        $userService->shouldReceive('hasPermission')->with(Permissions::VIEW_INV)->andReturn(true);
        $userService->shouldReceive('hasPermission')->with(Permissions::EDIT_INV)->andReturn(true);

        /** @var SessionInterface&m\MockInterface $session */
        $session = m::mock(SessionInterface::class);
        $session->shouldReceive('getId')->andReturn('test-session-id');
        $session->shouldReceive('get')->with('tfa_verified')->andReturn(true);

        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturnUsing(static fn (string $id): string => $id);

        $this->flashes = new \ArrayObject();
        $flashes = $this->flashes;
        /** @var Flash&m\MockInterface $flash */
        $flash = m::mock(Flash::class);
        $flash->shouldReceive('has')->andReturn(false);
        $flash->shouldReceive('add')->andReturnUsing(static function (string $key, string $message) use ($flashes): void {
            $flashes->append([$key, $message]);
        });

        /** @var Response&m\MockInterface $page */
        $page = m::mock(Response::class);
        $this->rendered = new \ArrayObject();
        $rendered = $this->rendered;
        /** @var WebViewRenderer&m\MockInterface $views */
        $views = m::mock(WebViewRenderer::class);
        $views->shouldReceive('withControllerName')->andReturnSelf();
        $views->shouldReceive('withLayout')->andReturnSelf();
        $views->shouldReceive('renderPartialAsString')->andReturn('');
        $views->shouldReceive('render')->andReturnUsing(
            static function (string $view, array $parameters = []) use ($rendered, $page): Response {
                $rendered->append([$view, $parameters]);
                return $page;
            }
        );

        /** @var Response&m\MockInterface $redirect */
        $redirect = m::mock(Response::class);
        $redirects = $this->redirects;
        /** @var WebControllerService&m\MockInterface $webService */
        $webService = m::mock(WebControllerService::class);
        $webService->shouldReceive('getRedirectResponse')->andReturnUsing(
            static function (string $route) use ($redirect, $redirects): Response {
                $redirects->append($route);
                return $redirect;
            }
        );

        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);

        /** @var CategorySecondaryRepository&m\MockInterface $csR */
        $csR = m::mock(CategorySecondaryRepository::class);
        $csR->shouldReceive('optionsDataCategorySecondaries')->andReturn([7 => 'Run Seven']);
        /** @var CategoryPrimaryRepository&m\MockInterface $cpR */
        $cpR = m::mock(CategoryPrimaryRepository::class);
        $cpR->shouldReceive('optionsDataCategoryPrimaries')->andReturn([3 => 'Windows']);
        /** @var ProductRepository&m\MockInterface $pR */
        $pR = m::mock(ProductRepository::class);
        /** @var EntityReader&m\MockInterface $all */
        $all = m::mock(EntityReader::class);
        $all->shouldReceive('getIterator')->andReturnUsing(
            static function () use ($product): \Generator {
                if (null !== $product) {
                    yield $product;
                }
            }
        );
        $pR->shouldReceive('findAllPreloaded')->andReturn($all);
        $pR->shouldReceive('repoProductquery')->andReturn($product);

        $controller = new HomeCareRunImportController(
            $session,
            $sR,
            $translator,
            $userService,
            $views,
            $webService,
            $flash,
            new RunHouseListParser(),
            $importer ?? $this->importer(),
            new RunSheetCsv(),
            $csR,
            $cpR,
            $pR,
            $this->http,
            $this->http,
        );
        return [$controller, $page, $redirect];
    }

    /** @var \ArrayObject<int, string> */
    private \ArrayObject $redirects;

    private function importer(): RunImportService&m\MockInterface
    {
        /** @var RunImportService&m\MockInterface $importer */
        $importer = m::mock(RunImportService::class);
        return $importer;
    }

    private function previewedRun(): ParsedRun
    {
        $run = $this->rendered[0][1]['run'] ?? null;
        Assert::true($run instanceof ParsedRun);
        return $run;
    }

    private function product(): Product
    {
        $product = new Product();
        $product->setId(5);
        $product->setProductName('Window clean');
        return $product;
    }

    /**
     * @param array<string, string> $body
     */
    private function post(array $body): ServerRequestInterface
    {
        return $this->http->createServerRequest('POST', '/')->withParsedBody($body);
    }

    private function startRedirects(): void
    {
        $this->redirects = new \ArrayObject();
    }

    public function theIndexPageIsRendered(): void
    {
        $this->startRedirects();
        [$controller, $page] = $this->controller();

        Assert::same($controller->index(), $page);
        Assert::same($this->rendered[0][0], 'index');
    }

    public function aPastedRunIsPreviewedWithTheChoicesForSavingIt(): void
    {
        $this->startRedirects();
        [$controller, $page] = $this->controller(null, $this->product());

        $response = $controller->preview($this->post(['run_text' => self::TEXT]));

        Assert::same($response, $page);
        [$view, $parameters] = $this->rendered[0];
        Assert::same($view, 'preview');
        Assert::same($this->previewedRun()->houseCount(), 2);
        Assert::same($parameters['runs'], [7 => 'Run Seven']);
        Assert::same($parameters['primaries'], [3 => 'Windows']);
        Assert::same($parameters['products'], [5 => 'Window clean']);
    }

    public function textThatCannotBeReadSendsYouBackWithAWarning(): void
    {
        $this->startRedirects();
        [$controller, , $redirect] = $this->controller();

        $response = $controller->preview($this->post(['run_text' => 'nothing here']));

        Assert::same($response, $redirect);
        Assert::same($this->redirects->getArrayCopy(), ['homecarerunimport/index']);
        Assert::same($this->flashes[0], ['danger', 'homecare.runimport.nothing.read']);
    }

    public function aFilledInSheetUploadedAsAFileIsPreviewed(): void
    {
        $this->startRedirects();
        [$controller, $page] = $this->controller(null, $this->product());
        $file = $this->http->createUploadedFile($this->http->createStream(self::SHEET), null, UPLOAD_ERR_OK, 'run.csv');

        $response = $controller->upload($this->http->createServerRequest('POST', '/')->withUploadedFiles(['sheet' => $file]));

        Assert::same($response, $page);
        Assert::same($this->rendered[0][0], 'preview');
        Assert::same($this->previewedRun()->segments[0]->houses[0]->clientName, 'Mrs A');
    }

    public function aFileThatIsNotASheetOrNoFileAtAllIsRefused(): void
    {
        $this->startRedirects();
        [$controller, , $redirect] = $this->controller();
        $notSheet = $this->http->createUploadedFile($this->http->createStream('hello'), null, UPLOAD_ERR_OK, 'x.csv');

        Assert::same($controller->upload($this->http->createServerRequest('POST', '/')->withUploadedFiles(['sheet' => $notSheet])), $redirect);
        Assert::same($controller->upload($this->http->createServerRequest('POST', '/')), $redirect);
        Assert::same($this->flashes[0], ['danger', 'homecare.runimport.sheet.invalid']);
        Assert::same(count($this->flashes), 2);
    }

    public function theSheetDownloadIsACsvAttachmentBlankOrFilledWithTheRun(): void
    {
        $this->startRedirects();
        [$controller] = $this->controller();

        $blank = $controller->template($this->post([]));
        $filled = $controller->template($this->post(['run_text' => self::TEXT]));

        Assert::true(str_contains($blank->getHeaderLine('Content-Type'), 'text/csv'));
        Assert::true(str_contains($blank->getHeaderLine('Content-Disposition'), 'run-import-sheet.csv'));
        Assert::true(str_contains((string) $blank->getBody(), '"street","postcode","number"'));
        Assert::true(str_contains((string) $filled->getBody(), '"Example Crescent","AB1 2CD","14"'));
    }

    public function theCanonicalRunFileIsAJsonDownloadNamedAfterTheRun(): void
    {
        $this->startRedirects();
        [$controller] = $this->controller();

        $response = $controller->export($this->post(['run_text' => self::TEXT]));

        Assert::true(str_contains($response->getHeaderLine('Content-Type'), 'application/json'));
        Assert::true(str_contains($response->getHeaderLine('Content-Disposition'), 'run-run-one.json'));
        Assert::true(str_contains((string) $response->getBody(), '"street": "Example Crescent"'));
    }

    public function savingNeedsAProductARunOrANewRunNameAndAUser(): void
    {
        $this->startRedirects();
        $importer = $this->importer();
        $importer->shouldNotReceive('save');

        // no product
        [$controller, , $redirect] = $this->controller($importer);
        Assert::same($controller->save($this->post(['run_text' => self::TEXT, 'product_id' => '5', 'run_id' => '7'])), $redirect);
        // a new run with no name
        [$controller] = $this->controller($importer, $this->product());
        $controller->save($this->post(['run_text' => self::TEXT, 'product_id' => '5', 'run_id' => '', 'run_name' => ' ', 'category_primary_id' => '3']));
        // not signed in
        [$controller] = $this->controller($importer, $this->product(), false);
        $controller->save($this->post(['run_text' => self::TEXT, 'product_id' => '5', 'run_id' => '7']));
        // nothing readable
        [$controller] = $this->controller($importer, $this->product());
        $controller->save($this->post(['run_text' => 'nothing', 'product_id' => '5', 'run_id' => '7']));

        Assert::same($this->redirects->getArrayCopy(), array_fill(0, 4, 'homecarerunimport/index'));
        Assert::same($this->flashes[0], ['danger', 'homecare.runimport.save.invalid']);
    }

    public function savingAnExistingRunPassesTheChoicesToTheImporterAndGoesToTheSchedule(): void
    {
        $this->startRedirects();
        $product = $this->product();
        $importer = $this->importer();
        $importer->shouldReceive('save')->once()->withArgs(
            static fn (ParsedRun $run, ?int $runId, string $name, int $primary, Product $p, ?float $price, User $_u): bool
                => 2 === $run->houseCount() && 7 === $runId && 12.5 === $price && $p->reqId() === 5
        )->andReturn(new RunImportResult(7, false, 0, 2, ['1 A St'], ['2 A St']));
        [$controller, , $redirect] = $this->controller($importer, $product);

        $response = $controller->save($this->post([
            'run_text' => self::TEXT, 'run_id' => '7', 'product_id' => '5', 'price' => '12.5',
        ]));

        Assert::same($response, $redirect);
        Assert::same($this->redirects->getArrayCopy(), ['homecareschedule/index']);
        Assert::same($this->flashes[0][0], 'success');
    }

    public function savingANewRunNeedsTheNameAndCategoryAndAnEmptyPriceUsesTheProductPrice(): void
    {
        $this->startRedirects();
        $importer = $this->importer();
        $importer->shouldReceive('save')->once()->withArgs(
            static fn (ParsedRun $run, ?int $runId, string $name, int $primary, Product $p, ?float $price, User $_u): bool
                => null === $runId && 'Run One' === $name && 3 === $primary && null === $price
        )->andReturn(new RunImportResult(9, true, 1, 2, [], []));
        [$controller] = $this->controller($importer, $this->product());

        $controller->save($this->post([
            'run_text' => self::TEXT, 'run_id' => '', 'run_name' => ' Run One ',
            'category_primary_id' => '3', 'product_id' => '5', 'price' => '',
        ]));

        Assert::same($this->redirects->getArrayCopy(), ['homecareschedule/index']);
    }
}
