<?php

declare(strict_types=1);

namespace App\Invoice\PurchaseEntry;

use App\Auth\Permissions;
use App\Infrastructure\Persistence\PurchaseEntry\PurchaseEntry;
use App\Invoice\BaseController;
use App\Invoice\Setting\SettingRepository as sR;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Input\Http\Attribute\Parameter\Query;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class PurchaseEntryController extends BaseController
{
    private const ROUTE_INDEX = 'entry/index';

    protected string $controllerName = 'invoice/purchaseentry';

    public function __construct(
        private PurchaseEntryService $purchaseEntryService,
        private PurchaseEntryRepository $purchaseEntryRepository,
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator,
            $webViewRenderer, $session, $sR, $flash);
        $this->purchaseEntryService = $purchaseEntryService;
        $this->purchaseEntryRepository = $purchaseEntryRepository;
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
    }

    public function index(
        #[Query('page')] ?int $page = null,
        #[Query('groupBy')] ?string $groupBy = null,
    ): Response {
        $canEdit = $this->rbac();
        if ($canEdit instanceof Response) {
            return $canEdit;
        }
        $taxYearYear  = $this->sR->getSetting('this_tax_year_from_date_year');
        $taxYearMonth = $this->sR->getSetting('this_tax_year_from_date_month');
        $taxYearDay   = $this->sR->getSetting('this_tax_year_from_date_day');
        $taxYearSet   = $taxYearYear !== '' && $taxYearMonth !== '' && $taxYearDay !== '';
        if (!$taxYearSet) {
            $this->flashMessage('warning',
                $this->translator->translate('purchase.entry.tax.year.not.configured'));
        }
        $validGroups  = $taxYearSet ? ['month', 'supplier', 'quarter'] : ['month', 'supplier'];
        $safeGroupBy  = in_array($groupBy, $validGroups, true) ? $groupBy : 'none';
        $parameters = [
            'purchaseentries' => $this->purchaseEntryRepository->getReader(),
            'page'            => max(1, $page ?? 1),
            'groupBy'         => $safeGroupBy,
            'taxYearSet'      => $taxYearSet,
            'taxYearYear'     => $taxYearYear,
            'taxYearMonth'    => $taxYearMonth,
            'taxYearDay'      => $taxYearDay,
            'alert'           => $this->alert(),
        ];
        return $this->webViewRenderer->render('index', $parameters);
    }

    public function add(Request $request, FormHydrator $formHydrator): Response
    {
        $canEdit = $this->rbac();
        if ($canEdit instanceof Response) {
            return $canEdit;
        }
        $form = new PurchaseEntryForm();
        $parameters = [
            'title'          => $this->translator->translate('add'),
            'actionName'     => 'entry/add',
            'actionArguments' => [],
            'errors'         => [],
            'form'           => $form,
        ];
        if ($request->getMethod() === Method::POST) {
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $body = $request->getParsedBody();
                if (is_array($body)) {
                    $this->purchaseEntryService->saveEntry(new PurchaseEntry(), $body);
                }
                return $this->webService->getRedirectResponse(self::ROUTE_INDEX);
            }
            $parameters['form']   = $form;
            $parameters['errors'] =
                $form->getValidationResult()->getErrorMessagesIndexedByProperty();
        }
        return $this->webViewRenderer->render('_form', $parameters);
    }

    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
    ): Response {
        $canEdit = $this->rbac();
        if ($canEdit instanceof Response) {
            return $canEdit;
        }
        $entry = $this->entry($currentRoute);
        if ($entry === null) {
            return $this->webService->getRedirectResponse('index');
        }
        return $this->renderEditForm($request, $formHydrator, $entry);
    }

    private function renderEditForm(Request $request, FormHydrator $formHydrator,
        PurchaseEntry $entry): Response
    {
        $form = PurchaseEntryForm::show($entry);
        $parameters = [
            'title'           => $this->translator->translate('edit'),
            'actionName'      => 'entry/edit',
            'actionArguments' => ['id' => $entry->reqId()],
            'errors'          => [],
            'form'            => $form,
        ];
        if ($request->getMethod() === Method::POST) {
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $body = $request->getParsedBody();
                if (is_array($body)) {
                    $this->purchaseEntryService->saveEntry($entry, $body);
                }
                return $this->webService->getRedirectResponse(self::ROUTE_INDEX);
            }
            $parameters['form']   = $form;
            $parameters['errors'] =
                $form->getValidationResult()->getErrorMessagesIndexedByProperty();
        }
        return $this->webViewRenderer->render('_form', $parameters);
    }

    public function view(CurrentRoute $currentRoute): Response
    {
        $canEdit = $this->rbac();
        if ($canEdit instanceof Response) {
            return $canEdit;
        }
        $entry = $this->entry($currentRoute);
        if ($entry === null) {
            return $this->webService->getRedirectResponse(self::ROUTE_INDEX);
        }
        return $this->webViewRenderer->render('_view', [
            'entry' => $entry,
            'form'  => PurchaseEntryForm::show($entry),
        ]);
    }

    public function delete(CurrentRoute $currentRoute): Response
    {
        $canEdit = $this->rbac();
        if ($canEdit instanceof Response) {
            return $canEdit;
        }
        try {
            $entry = $this->entry($currentRoute);
            if ($entry) {
                $this->purchaseEntryService->deleteEntry($entry);
            }
        } catch (\Exception $e) {
            unset($e);
            $this->flashMessage('danger',
                $this->translator->translate('record.not.deleted'));
        }
        return $this->webService->getRedirectResponse(self::ROUTE_INDEX);
    }

    /**
     * Show the CSV upload form (GET) or process the uploaded file (POST).
     * Expected CSV: header row + data rows with columns:
     *   date, supplier, amount_ex_vat, vat_amount[, description]
     */
    public function csvImport(Request $request): Response
    {
        $canEdit = $this->rbac();
        if ($canEdit instanceof Response) {
            return $canEdit;
        }
        $parameters = ['alert' => $this->alert()];
        if ($request->getMethod() === Method::POST) {
            return $this->processCsvUpload($request, $parameters);
        }
        return $this->webViewRenderer->render('csv_import', $parameters);
    }

    /** @param array<string, mixed> $parameters */
    private function processCsvUpload(Request $request, array $parameters): Response
    {
        $files = $request->getUploadedFiles();
        /** @var UploadedFileInterface|null $file */
        $file = $files['csv_file'] ?? null;
        if ($file === null || $file->getError() !== UPLOAD_ERR_OK) {
            $this->flashMessage('danger',
                $this->translator->translate('purchase.entry.csv.no.file'));
            return $this->webViewRenderer->render('csv_import', $parameters);
        }
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            $this->flashMessage('danger',
                $this->translator->translate('purchase.entry.csv.no.file'));
            return $this->webViewRenderer->render('csv_import', $parameters);
        }
        fwrite($handle, $file->getStream()->getContents());
        rewind($handle);
        [$imported, $skipped] = $this->importCsvRows($handle);
        fclose($handle);
        $this->flashMessage(
            'success',
            $this->translator->translate('purchase.entry.csv.imported',
                ['count' => $imported, 'skipped' => $skipped]),
        );
        return $this->webService->getRedirectResponse(self::ROUTE_INDEX);
    }

    /**
     * @param resource $handle
     * @return array{int, int} [imported, skipped]
     */
    private function importCsvRows($handle): array
    {
        $imported = 0;
        $skipped  = 0;
        $rowIndex = 0;

        // Detect delimiter from the header row then rewind for normal processing.
        $headerRaw = fgets($handle);
        $header    = $headerRaw !== false ? $headerRaw : '';
        $delimiter = substr_count($header, ';') >= substr_count($header, ',') ? ';' : ',';
        rewind($handle);

        while (($cols = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (!is_array($cols)) {
                continue;
            }
            $rowIndex++;
            if ($rowIndex === 1) {
                continue; // skip header row
            }
            if (count($cols) < 4 || trim((string) $cols[0]) === '') {
                $skipped++;
                continue;
            }
            $entry = new PurchaseEntry();
            $this->purchaseEntryService->saveEntry($entry, [
                'date'          => trim((string) $cols[0]),
                'supplier'      => trim((string) $cols[1]),
                'amount_ex_vat' => trim((string) $cols[2]),
                'vat_amount'    => trim((string) $cols[3]),
                'description'   => isset($cols[4]) && trim($cols[4]) !== '' ?
                    trim($cols[4]) : null,
            ]);
            $imported++;
        }
        return [$imported, $skipped];
    }

    /**
     * Serve a blank CSV template the user can fill in and re-upload.
     * Columns: date, supplier, amount_ex_vat, vat_amount, description
     */
    public function csvTemplate(): Response
    {
        $rows = [
            ['date', 'supplier', 'amount_ex_vat', 'vat_amount', 'description'],
            ['2026-01-05', 'Office Supplies Ltd', '120.00', '24.00', 'Stationery — Jan'],
            ['2026-01-12', 'Cloud Hosting Co', '200.00', '40.00', 'Hosting Jan 2026'],
            ['2026-01-20', 'Your Supplier Name', '0.00', '0.00', 'Optional description'],
        ];

        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            return $this->responseFactory->createResponse(500);
        }
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $this->responseFactory
            ->createResponse(200)
            ->withHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->withHeader('Content-Disposition',
                'attachment; filename="purchase-entries-template.csv"')
            ->withHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->withBody($this->streamFactory->createStream($csv));
    }

    public function taxYearLocales(): Response
    {
        $canEdit = $this->rbac();
        if ($canEdit instanceof Response) {
            return $canEdit;
        }
        return $this->webViewRenderer->render('tax_year_locales', [
            'alert' => $this->alert(),
        ]);
    }

    public function applyTaxYearLocale(Request $request): Response
    {
        $canEdit = $this->rbac();
        if ($canEdit instanceof Response) {
            return $canEdit;
        }
        $body  = $request->getParsedBody();
        $month = is_array($body) ? str_pad((string) ($body['month'] ?? ''), 2, '0', STR_PAD_LEFT) : '';
        $day   = is_array($body) ? str_pad((string) ($body['day'] ?? ''), 2, '0', STR_PAD_LEFT) : '';
        if (preg_match('/^(0[1-9]|1[0-2])$/', $month) !== 1
            || preg_match('/^(0[1-9]|[12]\d|3[01])$/', $day) !== 1) {
            $this->flashMessage('danger',
                $this->translator->translate('purchase.entry.tax.year.locale.invalid'));
            return $this->webService->getRedirectResponse('entry/tax-year-locales');
        }
        $existingYear = $this->sR->getSetting('this_tax_year_from_date_year');
        $year = $existingYear !== '' ? $existingYear : date('Y');
        $this->saveSettingValue('this_tax_year_from_date_year', $year);
        $this->saveSettingValue('this_tax_year_from_date_month', $month);
        $this->saveSettingValue('this_tax_year_from_date_day', $day);
        $this->flashMessage('success',
            $this->translator->translate('purchase.entry.tax.year.locale.applied'));
        return $this->webService->getRedirectResponse(self::ROUTE_INDEX);
    }

    private function saveSettingValue(string $key, string $value): void
    {
        $setting = $this->sR->withKey($key);
        if ($setting !== null) {
            $setting->setSettingValue($value);
            $this->sR->save($setting);
        }
    }

    private function rbac(): bool|Response
    {
        $canEdit = $this->userService->hasPermission(Permissions::EDIT_INV);
        if (!$canEdit) {
            $this->flashMessage('warning', $this->translator->translate('permission'));
            return $this->webService->getRedirectResponse(self::ROUTE_INDEX);
        }
        return $canEdit;
    }

    private function entry(CurrentRoute $currentRoute): ?PurchaseEntry
    {
        return $this->purchaseEntryRepository
                    ->repoFindById((int) $currentRoute->getArgument('id'));
    }
}
