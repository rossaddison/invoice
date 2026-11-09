<?php

declare(strict_types=1);

namespace App\Invoice\HomeCareRunImport;

use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\User\User;
use App\Invoice\BaseController;
use App\Invoice\CategoryPrimary\CategoryPrimaryRepository as CPR;
use App\Invoice\CategorySecondary\CategorySecondaryRepository as CSR;
use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\Setting\SettingRepository as sR;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Paste a run as it is kept in the lead's document and see how it was read:
 * streets, houses and their flags, with a warning for anything doubtful. Nothing
 * is saved here; the canonical run file (JSON) can be downloaded for keeping
 * and for comparing against what is stored later.
 */
final class HomeCareRunImportController extends BaseController
{
    protected string $controllerName = 'invoice/homecarerunimport';

    public function __construct(
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        Flash $flash,
        private readonly RunHouseListParser $parser,
        private readonly RunImportService $importer,
        private readonly RunSheetCsv $sheet,
        private readonly CSR $csR,
        private readonly CPR $cpR,
        private readonly PR $pR,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {
        parent::__construct(
            $webService,
            $userService,
            $translator,
            $webViewRenderer,
            $session,
            $sR,
            $flash
        );
    }

    public function index(): Response
    {
        return $this->webViewRenderer->render('index', ['alert' => $this->alert()]);
    }

    public function preview(Request $request): Response
    {
        return $this->previewText($this->text($request));
    }

    /**
     * The import sheet as a CSV download: blank, or with a row per house of the pasted run.
     */
    public function template(Request $request): Response
    {
        $text = $this->text($request);
        $run = '' === trim($text) ? null : $this->read($text);
        $csv = $this->sheet->template(null !== $run && [] !== $run->segments ? $run : null);
        return $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'text/csv; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="run-import-sheet.csv"')
            ->withBody($this->streamFactory->createStream($csv));
    }

    /**
     * A filled-in import sheet uploaded as a file: shown in the same preview as pasted text.
     */
    public function upload(Request $request): Response
    {
        /** @var array<string, UploadedFileInterface> $files */
        $files = $request->getUploadedFiles();
        $file = $files['sheet'] ?? null;
        $csv = $file instanceof UploadedFileInterface && UPLOAD_ERR_OK === $file->getError()
            ? (string) $file->getStream() : '';
        if ('' === trim($csv) || !$this->sheet->isSheet($csv)) {
            $this->flashMessage('danger', $this->translator->translate('homecare.runimport.sheet.invalid'));
            return $this->webService->getRedirectResponse('homecarerunimport/index');
        }
        return $this->previewText($csv);
    }

    private function read(string $text): ParsedRun
    {
        return $this->sheet->isSheet($text) ? $this->sheet->parse($text) : $this->parser->parse($text);
    }

    private function previewText(string $text): Response
    {
        $run = $this->read($text);
        if ([] === $run->segments) {
            $this->flashMessage('danger',
                $this->translator->translate('homecare.runimport.nothing.read'));
            return $this->webService->getRedirectResponse('homecarerunimport/index');
        }
        $products = [];
        /** @var iterable<Product> $all */
        $all = $this->pR->findAllPreloaded();
        foreach ($all as $product) {
            $products[$product->reqId()] = $product->getProductName() ?? '';
        }
        return $this->webViewRenderer->render('preview', [
            'run' => $run,
            'text' => $text,
            'runs' => $this->csR->optionsDataCategorySecondaries(),
            'primaries' => $this->cpR->optionsDataCategoryPrimaries(),
            'products' => $products,
            'alert' => $this->alert(),
        ]);
    }

    /**
     * Saves the run: the run, its streets, and a client, dwelling and draft invoice for each house.
     */
    public function save(Request $request): Response
    {
        $run = $this->read($this->text($request));
        $runChoice = $this->field($request, 'run_id');
        $runName = trim($this->field($request, 'run_name'));
        $product = $this->pR->repoProductquery((int) $this->field($request, 'product_id'));
        $user = $this->userService->getUser();
        $primaryId = (int) $this->field($request, 'category_primary_id');
        $runId = ctype_digit($runChoice) && (int) $runChoice > 0 ? (int) $runChoice : null;
        if ([] === $run->segments || null === $product || !$user instanceof User || (null === $runId && ('' === $runName || $primaryId <= 0))) {
            $this->flashMessage('danger', $this->translator->translate('homecare.runimport.save.invalid'));
            return $this->webService->getRedirectResponse('homecarerunimport/index');
        }
        $priceText = trim($this->field($request, 'price'));
        $result = $this->importer->save(
            $run,
            $runId,
            $runName,
            $primaryId,
            $product,
            is_numeric($priceText) ? (float) $priceText : null,
            $user,
        );
        $this->flashMessage('success', sprintf(
            $this->translator->translate('homecare.runimport.saved'),
            $result->housesCreated,
            $result->streetsCreated,
            count($result->alreadyThere),
            count($result->leftOut)
        ));
        return $this->webService->getRedirectResponse('homecareschedule/index');
    }

    /**
     * The canonical run file, as a JSON download.
     */
    public function export(Request $request): Response
    {
        $run = $this->read($this->text($request));
        $json = json_encode($run->toCanonical(), JSON_PRETTY_PRINT
                | JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_THROW_ON_ERROR);
        $name = trim((string) preg_replace('/[^a-z0-9]+/', '-',
            strtolower($run->title)), '-');
        return $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="run-' .
                    ('' === $name ? 'file' : $name) . '.json"')
            ->withBody($this->streamFactory->createStream($json));
    }

    private function text(Request $request): string
    {
        return $this->field($request, 'run_text');
    }

    private function field(Request $request, string $key): string
    {
        /** @var array<string, string>|null|object $body */
        $body = $request->getParsedBody();
        return is_array($body) ? ($body[$key] ?? '') : '';
    }
}
