<?php

declare(strict_types=1);

namespace App\Invoice;

use App\Auth\Permissions;
use App\Invoice\Client\ClientRepository;
use App\Invoice\Family\FamilyRepository;
use App\Invoice\Inv\InvRepository;
use App\Invoice\Product\ProductRepository;
use App\Invoice\Quote\QuoteRepository;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\TaxRate\TaxRateRepository;
use App\Invoice\Trait\InvoiceInstallTrait;
use App\Invoice\Trait\InvoiceStoreCoveTrait;
use App\Invoice\Unit\UnitRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Session\SessionInterface;

final class InvoiceController extends BaseController
{
    use InvoiceInstallTrait;
    use InvoiceStoreCoveTrait;

    protected string $controllerName = 'invoice';

    public function faq(SettingRepository $sR, #[RouteArgument('topic')] string $topic): Response
    {
        $fontSize = (int) ($sR->getSetting('bootstrap5_form_font_size') ?: 16);
        $view = match ($topic) {
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
        $d->gR->repoCountAll() === 0 ?
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
}
