<?php

declare(strict_types=1);

namespace App\Invoice;

use App\Auth\Permissions;
use App\Invoice\Client\ClientRepository;
use App\Invoice\Family\FamilyRepository;
use App\Invoice\Inv\InvRepository;
use App\Invoice\Product\ProductRepository;
use App\Invoice\Quote\QuoteRepository;
use App\Invoice\Setting\QuoteSalesOrderInvResetService;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\TaxRate\TaxRateRepository;
use App\Invoice\Trait\InvoiceInstallTrait;
use App\Invoice\Trait\InvoiceStoreCoveTrait;
use App\Invoice\Unit\UnitRepository;
use App\Invoice\UserInv\UserInvRepository;
use App\Invoice\UserInv\UserRbacLinkRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Rbac\AssignmentsStorageInterface;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Session\SessionInterface;

final class InvoiceController extends BaseController
{
    use InvoiceInstallTrait;
    use InvoiceStoreCoveTrait;

    protected string $controllerName = 'invoice';

    private const string ROUTE_INDEX = 'invoice/index';

    public function faq(
        SettingRepository $sR,
        UrlGeneratorInterface $urlGenerator,
        #[RouteArgument('topic')] string $topic,
    ): Response {
        $fontSize = (int) ($sR->getSetting('bootstrap5_form_font_size') ?: 16);
        $view = match ($topic) {
            'homecare_auto_invoice' =>
                $this->webViewRenderer->renderPartialAsString(
                        '//invoice/info/homecare_auto_invoice',
                            ['fontSize' => $fontSize, 'urlGenerator' => $urlGenerator]),
            'ai_callback_session' =>
                $this->webViewRenderer->renderPartialAsString(
                        '//invoice/info/ai/ai_callback_session',
                            ['fontSize' => $fontSize]),
            'javascript_analysis' =>
                $this->webViewRenderer->renderPartialAsString(
                        '//invoice/info/javascript_analysis',
                            ['fontSize' => $fontSize]),
            'codeception_selectors_checklist' =>
                $this->webViewRenderer->renderPartialAsString(
                        '//invoice/info/codeception_selectors_checklist',
                            ['fontSize' => $fontSize]),
            'tp' => $this->webViewRenderer->renderPartialAsString(
                        '//invoice/info/taxpoint',
                            ['fontSize' => $fontSize]),
            'filter_combining' => $this->webViewRenderer->renderPartialAsString(
                        '//invoice/info/filter_combining',
                            ['fontSize' => $fontSize]),
            'shared' => $this->webViewRenderer->renderPartialAsString(
                        '//invoice/info/shared_hosting',
                            ['fontSize' => $fontSize]),
            'alpine' => $this->webViewRenderer->renderPartialAsString(
                        '//invoice/info/alpine',
                            ['fontSize' => $fontSize]),
            'wsl_to_alpine' => $this->webViewRenderer->renderPartialAsString(
                        '//invoice/info/wsl_to_alpine',
                            ['fontSize' => $fontSize]),
            'oauth2' => $this->webViewRenderer->renderPartialAsString(
                        '//invoice/info/oauth2',
                            ['fontSize' => $fontSize]),
            'paymentprovider' => $this->webViewRenderer->renderPartialAsString(
                        '//invoice/info/payment_provider',
                            ['fontSize' => $fontSize]),
            'consolecommands' => $this->webViewRenderer->renderPartialAsString(
                        '//invoice/info/console_commands',
                            ['fontSize' => $fontSize]),
            'ipaddress' => $this->webViewRenderer->renderPartialAsString(
                        '//invoice/info/ip_address',
                            ['fontSize' => $fontSize]),
            default => '',
        };
        return $this->webViewRenderer->render('info/view',
            ['topic' => $view]);
    }

    public function dashboard(DashboardDeps $d): \Psr\Http\Message\ResponseInterface
    {
        $data = [
            'alerts' => $this->alert(),
            // Repositories
            'iR' => $d->iR,
            'irR' => $d->irR,
            'qR' => $d->qR,
            'qaR' => $d->qaR,
            'iaR' => $d->iaR,

            // All invoices and quotes
            'invoices' => $d->iR->findAllPreloaded(),
            'overdueInvoices' => $d->iR->isOverdue(),
            'quotes' => $d->qR->findAllPreloaded(),

            // Totals for status eg. draft, sent, viewed...
            'invoice_status_totals' => $d->iaR->getStatusTotals(
                    $d->iR, $this->sR, $this->translator, $this->sR->getSetting(
                            'invoice_overview_period') ?: 'this-month'),
            'quote_status_totals' => $d->qaR->getStatusTotals(
                    $d->qR, $this->sR, $this->translator, $this->sR->getSetting(
                            'quote_status_period') ?: 'this-month'),

            // Array of statuses: draft, sent, viewed, paid, cancelled
            'invoice_statuses' => $d->iR->getStatuses($this->translator),

            // Array of statuses: draft, sent, viewed, approved, rejected,
            // cancelled
            'quote_statuses' => $d->qR->getStatuses($this->translator),

            // this-month, last-month, this-quarter, lsat-quarter, this-year,
            // last-year
            'invoice_status_period' => str_replace('-', '_', $this->sR->getSetting(
                    'invoice_overview_period')),

            // this-month, last-month, this-quarter, lsat-quarter, this-year,
            // last-year
            'quote_status_period' => str_replace('-', '_', $this->sR->getSetting(
                    'quote_overview_period')),

            // Projects
            'projects' => $d->prjctR->findAllPreloaded(),

            // Current tasks
            'taskR' => $d->taskR,

            'modal_create_client' => $this->webViewRenderer->renderPartialAsString(
                    '//invoice/client/modal_create_client'),

            'client_count' => $d->cR->count(),
        ];
        return $this->webViewRenderer->render('dashboard/index', $data);
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param SessionInterface $session
     * @param InvoiceIndexDeps $d
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function index(
        CurrentRoute $currentRoute,
        SessionInterface $session,
        InvoiceIndexDeps $d,
        UserRbacLinkRepository $urlR,
        AssignmentsStorageInterface $assignmentsStorage,
        UserInvRepository $uiR,
    ): \Psr\Http\Message\ResponseInterface {
        if ($this->userService->hasPermission(
                Permissions::NO_ENTRY_TO_BASE_CONTROLLER)) {
            return $this->webService->getNotFoundResponse();
        }
        if (($this->sR->getSetting('debug_mode') == '1')
                && $this->userService->hasPermission(Permissions::EDIT_INV)) {
            // Load language-specific info file from locale subfolder
            // (e.g., ru/invoice.php)
            $language = (string) $session->get('_language', 'en');
            $languageFile = "//invoice/info/{$language}/invoice";

            // Check if language-specific file exists by attempting to render it
            try {
                $content = $this->webViewRenderer->renderPartialAsString(
                                                                $languageFile);
                $this->flashMessage('info', $content);
            } catch (\Throwable) {
                // Fallback to default English version
                //$this->flashMessage('info',
                //    $this->webViewRenderer->renderPartialAsString(
                //        '//invoice/info/en/invoice'));
            }
        }
        $urlR->syncIfEmpty($assignmentsStorage, $uiR);
        $d->gR->repoCountAll() < 4 ?
                $this->installDefaultInvoiceAndQuoteGroup($d->gR) : '';
        $d->pmR->count() === 0 ?
                $this->installDefaultPaymentMethods($d->pmR) : '';
        // If you want to reinstall the default settings, remove the
        // default_settings_exist setting => its count will be zero
        $this->sR->repoCount('default_settings_exist') === 0 ?
                $this->installDefaultSettingsOnFirstRun($this->sR) : '';
        $this->installCheckForPreexistingTestData(
                                                $this->sR, $d->fR, $d->uR, $d->pR, $d->trR, $d->cR);
        $session->set('_language', $currentRoute->getArgument('_language'));
        $parameters = [
            'alerts' => $this->alert(),
        ];
        return $this->webViewRenderer->render('index', $parameters);
    }

    /**
     * @param SettingRepository $sR
     * @return Response
     */
    public function settingReset(SettingRepository $sR): Response
    {
        $canEdit = $this->userService->hasPermission(Permissions::EDIT_INV);
        if ($canEdit) {
            $this->removeAllSettings($sR);
        }
        return $this->webService->getRedirectResponse('invoice/index');
    }

    /**
     * @param SettingRepository $sR
     * @param UnitRepository $uR
     * @param FamilyRepository $fR
     * @param ProductRepository $pR
     * @param ClientRepository $cR
     * @param QuoteRepository $qR
     * @param InvRepository $iR
     */
    public function testDataRemove(
        SettingRepository $sR,
        UnitRepository $uR,
        FamilyRepository $fR,
        ProductRepository $pR,
        ClientRepository $cR,
        QuoteRepository $qR,
        InvRepository $iR,
    ): \Psr\Http\Message\ResponseInterface {
        if ($sR->repoCount('use_test_data') > 0
                                && $sR->getSetting('use_test_data') == '0') {
            // Only remove the test data if the user's test quotes and
            // invoices have been removed FIRST else integrity constraint
            // violations
            if (($qR->repoCountAll() > 0) || ($iR->repoCountAll() > 0)) {
                $flash = $this->translator->translate('first.reset');
            } else {
            // Note: The Tax Rates are not deleted because you must have at
            // least one zero tax rate and one standard rate
            // for the quotes and invoices to function corrrectly
                $this->testDataDelete($uR, $fR, $pR, $cR);
                $flash = $this->translator->translate('deleted');
            }
        } else {
            // Settings...General...Install Test Data => change to 'no' before
            // you remove the test data
            $flash = $this->translator->translate('install.test.data');
        }
        $data = [
            'alerts' => $this->alert(),
        ];
        $this->flashMessage('info', $flash);
        return $this->webViewRenderer->render('index', $data);
    }

    /**
     * @param UnitRepository $uR
     * @param FamilyRepository $fR
     * @param ProductRepository $pR
     * @param ClientRepository $cR
     * @param QuoteRepository $qR
     * @param InvRepository $iR
     * @param TaxRateRepository $trR
     */
    public function testDataReset(
        UnitRepository $uR,
        FamilyRepository $fR,
        ProductRepository $pR,
        ClientRepository $cR,
        QuoteRepository $qR,
        InvRepository $iR,
        TaxRateRepository $trR,
    ): \Psr\Http\Message\ResponseInterface {
        if ($this->sR->repoCount('install_test_data') > 0 && $this->sR->getSetting(
                'install_test_data') == 1) {
            // Only remove the test data if the user's test quotes and invoices
            // have been removed FIRST else integrity constraint violations
            if (($qR->repoCountAll() > 0) || ($iR->repoCountAll() > 0)) {
                $flash = $this->translator->translate('first.reset');
            } else {
                $this->testDataDelete($uR, $fR, $pR, $cR);
                $this->installTestData($trR, $uR, $fR, $pR, $cR);
                $flash = $this->translator->translate('reset');
            }
        } else {
            $flash = $this->translator->translate('install.test.data');
        }
        $this->flashMessage('info', $flash);
        $data = [
            'alerts' => $this->alert(),
        ];
        return $this->webViewRenderer->render('index', $data);
    }

    public function debugLogs(Aliases $aliases): Response
    {
        if (($_ENV['YII_DEBUG'] ?? '') !== 'true' || !$this->userService->hasPermission(Permissions::EDIT_INV)) {
            return $this->webService->getNotFoundResponse();
        }
        $logFile = $aliases->get('@runtime/logs/app.log');
        $lines = [];
        if (is_file($logFile)) {
            $raw = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $lines = array_reverse(array_slice($raw === false ? [] : $raw, -100));
        }
        return $this->webViewRenderer->render('debug/logs', [
            'lines' => $lines,
            'logFile' => $logFile,
        ]);
    }

    /**
     * Confirmation page for resetQuoteSalesOrderInv() -- debug-mode only,
     * shows the exact table list and requires the DB password to proceed.
     */
    public function resetQuoteSalesOrderInvConfirm(): Response
    {
        if (!$this->debugResetAllowed()) {
            return $this->webService->getNotFoundResponse();
        }
        $this->flashMessage('warning', $this->translator->translate('debug.reset.tree.confirm'));
        return $this->webViewRenderer->render('debug/reset_quote_so_inv_confirm', [
            'alerts' => $this->alert(),
            'tables' => QuoteSalesOrderInvResetService::tables(),
        ]);
    }

    /**
     * Backs up the database, then drops the entire Inv, Quote and
     * SalesOrder entity trees and clears the cached schema -- see
     * QuoteSalesOrderInvResetService and
     * project_sales_order_amount_so_id_column_incident memory. Debug mode
     * and EDIT_INV permission are required (route middleware plus a
     * redundant in-action check -- see debugLogs() for the same pattern),
     * and the submitted db_password field must match $_ENV['DB_PASSWORD']
     * exactly, checked with hash_equals() to avoid a timing side-channel.
     *
     * Redirects to resetQuoteSalesOrderInvFinish() rather than recreating
     * the tables here directly: this request has already resolved (and
     * cached, for its own lifetime) the pre-drop ORM schema via
     * BaseController's constructor-injected SettingRepository, so nothing
     * in this same request can trigger a fresh rebuild. The *next*
     * request's controller construction does that naturally.
     */
    public function resetQuoteSalesOrderInv(
        Request $request,
        QuoteSalesOrderInvResetService $resetService,
    ): Response {
        if (!$this->debugResetAllowed()) {
            return $this->webService->getNotFoundResponse();
        }
        return $this->webService->getRedirectResponse(
            $this->dropQuoteSalesOrderInvTree($request, $resetService),
        );
    }

    /**
     * @return string the route name resetQuoteSalesOrderInv() should redirect to.
     */
    private function dropQuoteSalesOrderInvTree(
        Request $request,
        QuoteSalesOrderInvResetService $resetService,
    ): string {
        $body = (array) $request->getParsedBody();
        $submitted = (string) ($body['db_password'] ?? '');
        $expected = $_ENV['DB_PASSWORD'] ?? '';
        if ($expected === '' || !hash_equals($expected, $submitted)) {
            $this->flashMessage('danger', $this->translator->translate('debug.reset.tree.wrong.password'));
            return 'invoice/resetQuoteSalesOrderInvConfirm';
        }
        try {
            $resetService->dropAndClearSchema();
            return 'invoice/resetQuoteSalesOrderInvFinish';
        } catch (\Throwable $e) {
            $this->flashMessage('danger', $this->translator->translate('debug.reset.tree.failed')
                . ': ' . $e->getMessage());
            return self::ROUTE_INDEX;
        }
    }

    /**
     * Second leg of resetQuoteSalesOrderInv() -- by the time this action's
     * body runs, this request's own controller construction has already
     * resolved the ORM schema fresh (the previous request cleared the
     * cache), which is what actually recreates the Inv/Quote/SalesOrder
     * tables via Cycle\Schema\Generator\SyncTables. Only once that's true
     * is it safe to reset AUTO_INCREMENT on them.
     */
    public function resetQuoteSalesOrderInvFinish(QuoteSalesOrderInvResetService $resetService): Response
    {
        if (!$this->debugResetAllowed()) {
            return $this->webService->getNotFoundResponse();
        }
        $tables = QuoteSalesOrderInvResetService::tables();
        try {
            $resetService->resetAutoIncrement($tables);
            $this->flashMessage('success', $this->translator->translate('debug.reset.tree.done')
                . ' (' . count($tables) . ')');
        } catch (\Throwable $e) {
            $this->flashMessage('danger', $this->translator->translate('debug.reset.tree.failed')
                . ': ' . $e->getMessage());
        }
        return $this->webService->getRedirectResponse(self::ROUTE_INDEX);
    }

    private function debugResetAllowed(): bool
    {
        return ($_ENV['YII_DEBUG'] ?? '') === 'true' && $this->userService->hasPermission(Permissions::EDIT_INV);
    }
}
