<?php

declare(strict_types=1);

namespace App\Invoice\HomeCareRunSheet;

use Anthropic\Core\Exceptions\AnthropicException;
use Anthropic\Messages\Base64ImageSource\MediaType;
use App\Infrastructure\Persistence\HomeCareRunSheet\HomeCareRunSheet;
use App\Infrastructure\Persistence\User\User;
use App\Invoice\BaseController;
use App\Invoice\CategorySecondary\CategorySecondaryRepository as CSR;
use App\Invoice\HomeCareRunSheet\Exception\VisionApiKeyNotConfiguredException;
use App\Invoice\HomeCareRunSheetItem\HomeCareRunSheetItemRepositoryInterface;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\Worker\WorkerRepository as WR;
use App\Service\WebControllerService;
use App\User\UserService;
use DateTimeImmutable;
use LogicException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Step 4 of the run-sheet reconciliation (see project_homecare_run_signoff_design
 * memory): the "temporary index of adjustments" staging screen — shows only
 * the HomeCareRunSheetItem rows a vision read actually changed
 * (repoChangedForRunSheetquery()), lets the office accept/reject each one,
 * and saves that decision. Deliberately does not touch Inv or flip anything
 * to "sent" here — that's Step 5 (Apply), a separate action once this
 * review is confirmed.
 */
final class HomeCareRunSheetController extends BaseController
{
    protected string $controllerName = 'invoice/homecarerunsheet';

    public function __construct(
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        Flash $flash,
        private readonly HomeCareRunSheetRepositoryInterface $rsR,
        private readonly HomeCareRunSheetItemRepositoryInterface $rsiR,
        private readonly IR $iR,
        private readonly WR $wR,
        private readonly HomeCareRunSheetApplyService $applyService,
        private readonly HomeCareRunSheetExportService $exportService,
        private readonly HomeCareRunSheetScanService $scanService,
        private readonly CSR $csR,
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

    /**
     * Step 1's entry point: a small form to pick which run to export
     * (category_secondary + date, defaulting to today — the same pair
     * HomeCareRunContext already drives on inv/index), plus the recent-batch
     * list so staff can jump back into one already in progress.
     */
    public function index(): Response
    {
        $runSheets = [];
        $categoryNames = [];
        $itemCounts = [];
        foreach ($this->rsR->repoRecentquery(50) as $runSheet) {
            $runSheets[] = $runSheet;
            $id = $runSheet->reqId();
            $categoryNames[$id] = $this->csR->repoCategorySecondaryQuery($runSheet->reqCategorySecondaryId())
                ?->getName() ?? '';
            $itemCounts[$id] = count($this->rsiR->repoForRunSheetquery($id));
        }

        return $this->webViewRenderer->render('index', [
            'runSheets' => $runSheets,
            'categoryNames' => $categoryNames,
            'itemCounts' => $itemCounts,
            'optionsCategorySecondary' => $this->csR->optionsDataCategorySecondaries(),
            'today' => (new DateTimeImmutable('now'))->format('Y-m-d'),
            'alert' => $this->alert(),
        ]);
    }

    public function export(Request $request): Response
    {
        $body = $request->getParsedBody() ?? [];
        $categorySecondaryId = is_array($body) ? (int) ($body['category_secondary_id'] ?? 0) : 0;
        $dateInput = is_array($body) ? (string) ($body['run_date'] ?? '') : '';
        $runDate = $dateInput !== '' ? DateTimeImmutable::createFromFormat('Y-m-d', $dateInput) : false;

        if ($categorySecondaryId <= 0 || $runDate === false) {
            $this->flashMessage('danger', $this->translator->translate('homecare.runsheet.index.export.invalid'));
            return $this->webService->getRedirectResponse('homecarerunsheet/index');
        }

        $user = $this->userService->getUser();
        $userId = $user instanceof User ? $user->reqId() : 0;
        $runSheet = $this->exportService->export($categorySecondaryId, $runDate, $userId);

        return $this->webService->getRedirectResponse('homecarerunsheet/review', ['id' => (string) $runSheet->reqId()]);
    }

    /**
     * Serves the exported CSV back for printing — the file already sits
     * under SettingFileFolderTrait::getHomeCareRunSheetsFolderAliases(), this
     * just streams it with a download-friendly filename.
     */
    public function downloadSpreadsheet(#[RouteArgument('id')] string $id): Response
    {
        $runSheet = $this->rsR->repoLoadedquery((int) $id);
        $fileName = $runSheet?->getSpreadsheetFileName();
        if ($runSheet === null || $fileName === null) {
            return $this->webService->getNotFoundResponse();
        }
        $path = $this->sR->getHomeCareRunSheetsFolderAliases()->get('@homecare_run_sheets')
            . DIRECTORY_SEPARATOR . $fileName;
        $contents = is_file($path) ? file_get_contents($path) : false;
        if ($contents === false) {
            return $this->webService->getNotFoundResponse();
        }

        return $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"')
            ->withBody($this->streamFactory->createStream($contents));
    }

    /**
     * Step 3's entry point: accepts the scanned/photographed run sheet,
     * hands it to HomeCareRunSheetScanService (store + Haiku 4.5 read), then
     * lands back on review() — which will now show the PendingReview
     * staging table since the vision read runs synchronously in the same
     * request.
     */
    public function uploadScan(Request $request, #[RouteArgument('id')] string $id): Response
    {
        $runSheet = $this->rsR->repoLoadedquery((int) $id);
        if ($runSheet === null) {
            return $this->webService->getNotFoundResponse();
        }

        $files = $request->getUploadedFiles();
        /** @var UploadedFileInterface|null $file */
        $file = $files['scan'] ?? null;
        $mediaType = $file !== null ? $this->resolveMediaType($file) : null;
        if ($file === null || $file->getError() !== UPLOAD_ERR_OK || $mediaType === null) {
            $this->flashMessage('danger', $this->translator->translate('homecare.runsheet.review.upload.invalid'));
            return $this->webService->getRedirectResponse('homecarerunsheet/review', ['id' => $id]);
        }

        return $this->performScan($runSheet, $file, $mediaType, $id);
    }

    /**
     * Split out of uploadScan() purely to keep its own return count within
     * SonarQube's limit (php:S1142) — the not-found/invalid-upload guard
     * clauses above both happen before the scan itself runs, so this only
     * needs to cover the scan's own three outcomes (success, no vision key
     * configured, any other Anthropic API failure).
     */
    private function performScan(HomeCareRunSheet $runSheet, UploadedFileInterface $file, MediaType $mediaType, string $id): Response
    {
        $user = $this->userService->getUser();
        $userId = $user instanceof User ? $user->reqId() : 0;
        try {
            $this->scanService->scan($runSheet, $file->getStream()->getContents(), $mediaType, $userId);
        } catch (VisionApiKeyNotConfiguredException) {
            $this->flashMessage('danger', $this->translator->translate('homecare.runsheet.review.upload.not.configured'));
            return $this->webService->getRedirectResponse('homecarerunsheet/review', ['id' => $id]);
        } catch (AnthropicException) {
            // Batch is left at Scanned (the exception fires before
            // markPendingReview() runs) — see HomeCareRunSheetScanService's
            // own docblock, "left in Scanned for retry" is deliberate. Raw
            // Anthropic error bodies (rate limits, bad key, low credit
            // balance, ...) aren't shown here — just a pointer to check the
            // key/billing and try again.
            $this->flashMessage('danger', $this->translator->translate('homecare.runsheet.review.upload.api.failed'));
            return $this->webService->getRedirectResponse('homecarerunsheet/review', ['id' => $id]);
        }

        $this->flashMessage('info', $this->translator->translate('homecare.runsheet.review.upload.done'));
        return $this->webService->getRedirectResponse('homecarerunsheet/review', ['id' => $id]);
    }

    /**
     * Sniffs the actual image bytes rather than trusting the client-declared
     * content type — this file is about to be paid-API-uploaded to
     * Anthropic, worth the extra certainty that it really is one of the four
     * types the vision call's MediaType enum accepts.
     */
    private function resolveMediaType(UploadedFileInterface $file): ?MediaType
    {
        $contents = $file->getStream()->getContents();
        $file->getStream()->rewind();
        $info = getimagesizefromstring($contents);
        $mime = $info !== false ? $info['mime'] : null;
        return match ($mime) {
            'image/jpeg' => MediaType::IMAGE_JPEG,
            'image/png' => MediaType::IMAGE_PNG,
            'image/gif' => MediaType::IMAGE_GIF,
            'image/webp' => MediaType::IMAGE_WEBP,
            default => null,
        };
    }

    public function review(#[RouteArgument('id')] string $id): Response
    {
        $runSheet = $this->rsR->repoLoadedquery((int) $id);
        if ($runSheet === null) {
            return $this->webService->getNotFoundResponse();
        }

        $rows = [];
        foreach ($this->rsiR->repoChangedForRunSheetquery($runSheet->reqId()) as $item) {
            $inv = $this->iR->repoInvLoadedquery($item->reqInvId());
            $rows[] = [
                'item' => $item,
                'invoice_number' => $inv?->getNumber() ?? ('#' . $item->reqInvId()),
                'client_name' => $inv?->getClient()?->getClientFullName() ?? '',
                'expected_worker_name' => $this->workerName($item->getExpectedWorkerId()),
                'detected_worker_name' => $this->workerName($item->getDetectedWorkerId()),
            ];
        }

        return $this->webViewRenderer->render('review', [
            'runSheet' => $runSheet,
            'rows' => $rows,
            'alert' => $this->alert(),
        ]);
    }

    /**
     * A checkbox per row (name="accepted[]", value=item id) marks a row
     * accepted; an unchecked row is explicitly rejected — every changed row
     * gets its $accepted flag written, not just the ones present in the
     * submitted body, so a row the office unchecks actually persists as
     * rejected rather than silently keeping its previous value.
     */
    public function save(Request $request, #[RouteArgument('id')] string $id): Response
    {
        $runSheet = $this->rsR->repoLoadedquery((int) $id);
        if ($runSheet === null) {
            return $this->webService->getNotFoundResponse();
        }

        $body = $request->getParsedBody() ?? [];
        /** @var list<string> $rawAccepted */
        $rawAccepted = is_array($body) ? (array) ($body['accepted'] ?? []) : [];
        $acceptedIds = array_map('intval', $rawAccepted);

        foreach ($this->rsiR->repoChangedForRunSheetquery($runSheet->reqId()) as $item) {
            $item->setAccepted(in_array($item->reqId(), $acceptedIds, true));
            $this->rsiR->save($item);
        }

        $this->flashMessage('info', $this->translator->translate('homecare.runsheet.review.saved'));
        return $this->webService->getRedirectResponse('homecarerunsheet/review', ['id' => $id]);
    }

    /**
     * Writes every accepted adjustment onto Inv and marks the whole run
     * sent — see HomeCareRunSheetApplyService's own docblock for exactly
     * what "applying an adjustment" does and doesn't do. Terminal: a run
     * sheet not currently PendingReview (already applied, or scan not read
     * yet) is rejected with a flash rather than a 500.
     */
    public function apply(#[RouteArgument('id')] string $id): Response
    {
        $runSheet = $this->rsR->repoLoadedquery((int) $id);
        if ($runSheet === null) {
            return $this->webService->getNotFoundResponse();
        }

        $user = $this->userService->getUser();
        $userId = $user instanceof User ? $user->reqId() : 0;

        try {
            $this->applyService->apply($runSheet, $userId);
        } catch (LogicException) {
            $this->flashMessage('warning', $this->translator->translate('homecare.runsheet.review.apply.not.ready'));
            return $this->webService->getRedirectResponse('homecarerunsheet/review', ['id' => $id]);
        }

        $this->flashMessage('info', $this->translator->translate('homecare.runsheet.review.applied'));
        return $this->webService->getRedirectResponse('homecarerunsheet/review', ['id' => $id]);
    }

    private function workerName(?int $workerId): string
    {
        if ($workerId === null) {
            return '';
        }
        $worker = $this->wR->repoWorkerquery($workerId);
        return $worker !== null ? $worker->getFirstname() . ' ' . $worker->getLastname() : '';
    }
}
