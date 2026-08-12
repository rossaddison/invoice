<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Invoice\{
    CategorySecondary\CategorySecondaryRepository as CSR,
    Inv\HomeCareRunContext,
    Inv\InvIndexFilter,
    Inv\InvIndexListDeps,
    Inv\InvIndexNavDeps,
    Inv\InvForm,
    Inv\InvRepository as IR,
    Inv\Widget\InvsFilterOptions,
    Inv\Widget\InvsListWidget,
    InvRecurring\InvRecurringRepository as IRR,
    Worker\WorkerRepository as WR,
};
use App\Widget\Bootstrap5ModalInv;
use Yiisoft\{
    Data\Paginator\OffsetPaginator,
    Data\Paginator\PageToken,
    Data\Reader\Sort,
    Html\Html,
    Router\HydratorAttribute\RouteArgument,
};
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

trait Index
{

public function index(
        Request $request,
        InvIndexListDeps $list,
        InvIndexNavDeps $nav,
        InvIndexFilter $filter,
        #[RouteArgument('_language')]
        string $_language,
        #[RouteArgument('page')]
        string $page = '1',
        #[RouteArgument('status')]
        string $status = '0',
    ): Response {
        $visible = $this->sR->getSetting('columns_all_visible');
        $visibleToggleInvSentLogColumn =
            $this->sR->getSetting('column_inv_sent_log_visible');
        $invForm = new InvForm();
        $bootstrap5ModalInv = new Bootstrap5ModalInv(
            $this->translator,
            $this->webViewRenderer,
            $list->clientRepo,
            $list->groupRepo,
            $this->sR,
            $nav->ucR,
            $invForm,
        );
        $this->session->set('_language', $_language);
        $this->disableReadOnlyStatusMessage();
        $active_clients = $nav->ucR->getClientsWithUserAccounts();
        if ($active_clients) {
            $page           = $filter->page ?? $page;
            $effectiveStatus = isset($filter->filterStatus)
                && !empty($filter->filterStatus)
                ? (int) $filter->filterStatus : (int) $status;
            $sortString     = $filter->sort ?? '-id';
            $sort           = Sort::only([
                'id', 'status_id', 'number', 'date_created', 'date_due', 'client_id',
            ])->withOrderString($sortString);

            $run  = $this->indexHomeCareRunContext($request, $filter);
            $invs = $list->invRepo->filterCombined($filter, $run, $effectiveStatus);

            $currentPage = max(1, (int) $page);
            $pageSize    = max(1, (int) ($this->sR->getSetting('default_list_limit') ?: 1));

            $paginator = (new OffsetPaginator($invs))
                ->withPageSize($pageSize)
                ->withCurrentPage($currentPage)
                ->withSort($sort)
                ->withToken(PageToken::next((string) $currentPage));

            $inv_statuses = $list->invRepo->getStatuses($this->translator);
            $label        = $list->invRepo->getSpecificStatusArrayLabel($status);

            $this->draftFlash($_language);
            $this->markSentFlash($_language);
            $this->homeCareRunFlash($nav->csR);

            $gridSummary = $this->indexGridSummary($paginator, $pageSize, $label);

            $defaultInvoiceGroup = $this->indexDefaultInvoiceGroup($list);
            $defaultInvoicePaymentMethod = $this->indexDefaultInvoicePaymentMethod($nav);

            $parameters = [
                'alert'              => $this->alert(),
                'clientCount'        => $list->clientRepo->count(),
                'decimalPlaces'      =>
                    (int) $this->sR->getSetting('tax_rate_decimal_places'),
                'defaultInvoiceGroup'         => $defaultInvoiceGroup,
                'defaultInvoicePaymentMethod' => $defaultInvoicePaymentMethod,
                'groupBy'            => $filter->groupBy,
                'gridSummary'        => $gridSummary,
                'iR'                 => $list->invRepo,
                'irR'                => $list->irR,
                'islR'               => $list->islR,
                'inv_statuses'       => $inv_statuses,

                'paginator'          => $paginator,
                'qR'                 => $nav->qR,
                'dlR'                => $nav->dlR,
                'soR'                => $nav->soR,
                'wR'                 => $nav->wR,
                'csR'                => $nav->csR,
                'sortString'         => $sortString,
                'status'             => $effectiveStatus,
                'visible'            => $visible !== '0',
                'visibleToggleInvSentLogColumn' =>
                    $visibleToggleInvSentLogColumn !== '0',
                'optionsClientsDropDownFilter' =>
                    $this->optionsDataClientsFilter($list->invRepo),
                'optionsClientGroupDropDownFilter' =>
                    $this->optionsDataClientGroupFilter($list->clientRepo),
                'optionsInvNumberDropDownFilter' =>
                    $this->optionsDataInvNumberFilter($list->invRepo),
                'optionsCreditInvNumberDropDownFilter' =>
                    $this->optionsDataCreditInvNumberFilter($list->invRepo),
                'optionsFamilyNameDropDownFilter' =>
                    $this->optionsDataFamilyNameFilter($list->invRepo),
                'optionsYearMonthDropDownFilter' =>
                    $this->optionsDataYearMonthFilter(),
                'optionsStatusDropDownFilter' =>
                    $this->optionsDataStatusFilter($list->invRepo),
                'optionsCategorySecondaryRunDropDownFilter' =>
                    $nav->csR->optionsDataCategorySecondaries(),
                'modal_add_inv' =>
                    $bootstrap5ModalInv->renderPartialLayoutWithFormAsString(
                        'inv', []),
                'modal_create_recurring_multiple' =>
                    $this->indexModalCreateRecurringMultiple($list->irR),
                'modal_copy_inv_multiple' =>
                    $this->indexModalCopyInvMultiple(
                        $list->clientRepo->repoUserClient(
                            $nav->ucR->getClientsWithUserAccounts()
                        )
                    ),
                'etR'                => $nav->etR,
                'fdR'                => $nav->fdR,
            ];

            if ($request->hasHeader('Hx-Request')) {
                return $this->htmlResponseFactory->createResponse(
                    InvsListWidget::widget()
                        ->withPaginator($paginator)
                        ->withIR($list->invRepo)
                        ->withLogRepositories($list->irR, $list->islR)
                        ->withRelationRepositories($nav->qR, $nav->soR, $nav->dlR)
                        ->withSR($this->sR)
                        ->withEmailRepositories($nav->etR, $nav->fdR)
                        ->withWorkerRepository($nav->wR)
                        ->withCategorySecondaryRepository($nav->csR)
                        ->withDwellingRepository($nav->dwR)
                        ->withCsrf((string) ($request->getParsedBody()['_csrf'] ?? ''))
                        ->withDecimalPlaces(
                            (int) $this->sR->getSetting('tax_rate_decimal_places'))
                        ->withVisible($visible !== '0')
                        ->withVisibleInvSentLogColumn(
                            $visibleToggleInvSentLogColumn !== '0')
                        ->withGroupBy($filter->groupBy ?? 'none')
                        ->withClientCount($list->clientRepo->count())
                        ->withGridDisplayOptions($gridSummary, $sortString)
                        ->withFilterOptions(new InvsFilterOptions([
                            'invNumber'       => $this->optionsDataInvNumberFilter($list->invRepo),
                            'creditInvNumber' => $this->optionsDataCreditInvNumberFilter($list->invRepo),
                            'familyName'      => $this->optionsDataFamilyNameFilter($list->invRepo),
                            'clients'         => $this->optionsDataClientsFilter($list->invRepo),
                            'clientGroup'     => $this->optionsDataClientGroupFilter($list->clientRepo),
                            'yearMonth'       => $this->optionsDataYearMonthFilter(),
                            'status'          => $this->optionsDataStatusFilter($list->invRepo),
                            'categorySecondaryRun' => $nav->csR->optionsDataCategorySecondaries(),
                        ]))
                        ->render()
                );
            }

            return $this->webViewRenderer->render('index', $parameters);
        }
        $this->flashMessage('info',
            $this->translator->translate('user.client.active.no'));
        return $this->webService->getRedirectResponse('client/index');
    }

    /**
     * Allocates (or clears) the HomeCare worker on an invoice, from the
     * dropdown InvsColumnBuilder renders in the inv/index grid. Read-only
     * for a worker's own inv/guest visibility — see
     * InvRepository::repoWorkerVisible() / Trait\Guest.php.
     */
    public function setWorker(
        Request $request,
        #[RouteArgument('inv_id')] string $inv_id,
        IR $iR,
        WR $wR,
    ): Response {
        $body = $request->getParsedBody() ?? [];
        /** @var string $worker_id */
        $worker_id = is_array($body) ? ($body['worker_id'] ?? '') : '';
        $inv = $inv_id !== '' ? $iR->repoInvUnLoadedquery((int) $inv_id) : null;
        if ($inv !== null) {
            $worker = $worker_id !== '' ? $wR->repoWorkerquery((int) $worker_id) : null;
            $inv->setWorker($worker);
            $iR->save($inv);
            $this->flashMessage('info', $this->translator->translate('worker.assigned'));
        }
        return $this->webService->getRedirectResponse('inv/index');
    }

    /**
     * Resolves the effective HomeCare "current run" category_secondary for
     * this request: whatever the grid's filterCategorySecondaryRun dropdown
     * was submitted as, or — only when that query param wasn't present at
     * all (a plain, unfiltered visit) — the id configured in Settings.
     * array_key_exists() on the raw query params (not the hydrated $filter
     * object) is what distinguishes "never touched" from "explicitly
     * cleared via the dropdown's blank option", since both produce an empty
     * $filter->filterCategorySecondaryRun.
     */
    private function indexHomeCareRunContext(Request $request, InvIndexFilter $filter): HomeCareRunContext
    {
        $touched = array_key_exists('filterCategorySecondaryRun', $request->getQueryParams());
        $configuredId = (int) $this->sR->getSetting('homecare_current_run_category_secondary_id');
        $effectiveId = $touched ? (int) $filter->filterCategorySecondaryRun : $configuredId;
        return new HomeCareRunContext(
            categorySecondaryId: $effectiveId,
            applyDateFilter: $effectiveId > 0 && $effectiveId === $configuredId,
            lastRunDate: $this->sR->getSetting('homecare_current_run_last_run_date'),
        );
    }

    private function indexGridSummary(
        OffsetPaginator $paginator,
        int $pageSize,
        string $label,
    ): string {
        return $this->sR->gridSummary(
            $paginator,
            $this->translator,
            $pageSize,
            $this->translator->translate('invoices'),
            $label,
        );
    }

    private function indexDefaultInvoiceGroup(InvIndexListDeps $list): string
    {
        $notSet = $this->sR->getSetting('not.set');
        $gRObj = $list->groupRepo->repoGroupquery(
            (int) $this->sR->getSetting('default_invoice_group'));
        return (null !== $gRObj && strlen($gRObj->getName() ?? '') > 0)
            ? ($gRObj->getName() ?? $notSet)
            : $notSet;
    }

    private function indexDefaultInvoicePaymentMethod(InvIndexNavDeps $nav): string
    {
        $notSet = $this->sR->getSetting('not.set');
        $pmRObj = $nav->pmR->repoPaymentMethodquery(
            (int) $this->sR->getSetting('invoice_default_payment_method'));
        return (null !== $pmRObj && strlen($pmRObj->getName() ?? '') > 0)
            ? ($pmRObj->getName() ?? $notSet)
            : $notSet;
    }

    private function disableReadOnlyStatusMessage(): void
    {
        if ($this->sR->getSetting('disable_read_only') == '') {
            $this->flashMessage('warning', $this->translator->translate(
                'security.disable.read.only.empty'));
        }
        if ($this->sR->getSetting('disable_read_only') == '1') {
            $this->flashMessage('warning', $this->translator->translate(
                'security.disable.read.only.warning'));
        }
    }

    private function indexModalCreateRecurringMultiple(IRR $irR): string
    {
        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/inv/modal_create_recurring_multiple', [
            'recur_frequencies' => $irR->recurFrequencies(),
        ]);
    }

    private function indexModalCopyInvMultiple(iterable $clients): string
    {
        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/inv/modal_copy_inv_multiple',
            ['clients' => $clients]
        );
    }

    private function draftFlash(string $_language): void
    {
        $draft   = $this->sR->getSetting('generate_invoice_number_for_draft');
        $setting = $this->sR->withKey('generate_invoice_number_for_draft');
        $setting_url = '';
        if (null !== $setting) {
            $setting_id  = $setting->reqSettingId();
            $setting_url = $this->url_generator->generate(
                'setting/draft',
                ['_language' => $_language, 'setting_id' => $setting_id],
            );
        }
        $level  = $draft == '0' ? 'warning' : 'info';
        $on_off = $draft == '0' ? 'off' : 'on';
        $message = $this->translator->translate('draft.number.' . $on_off)
            . str_repeat('&nbsp;', 2)
            . (!empty($setting_url)
                ? (string) Html::a(
                    Html::tag('i', '', ['class' => 'bi bi-pencil']),
                    $setting_url,
                    ['class' => 'btn btn-primary'])
                : '');
        $this->flashMessage($level, $message);
    }

    /**
     * Surfaces the configured HomeCare "current run" (Settings → invoices
     * tab) as an info flash with a link back to it, so it stays
     * discoverable even after a manager has cleared or changed the grid's
     * Current Run dropdown filter.
     */
    private function homeCareRunFlash(CSR $csR): void
    {
        $categorySecondaryId = (int) $this->sR->getSetting(
            'homecare_current_run_category_secondary_id');
        if ($categorySecondaryId <= 0) {
            return;
        }
        $categorySecondary = $csR->repoCategorySecondaryQuery($categorySecondaryId);
        if ($categorySecondary === null) {
            return;
        }
        $lastRunDate = $this->sR->getSetting('homecare_current_run_last_run_date');
        $linkText = ($categorySecondary->getName() ?? '')
            . ($lastRunDate !== '' ? ' — ' . $this->translator->translate('since')
                . ' ' . $lastRunDate : '');
        $link = Html::a(
            $linkText,
            $this->url_generator->generate('inv/index', [], [
                'filterCategorySecondaryRun' => (string) $categorySecondaryId,
            ]),
            ['class' => 'btn btn-primary'],
        );
        $this->flashMessage('info',
            $this->translator->translate('homecare.current.run') . ' '
                . str_repeat('&nbsp;', 2) . (string) $link);
    }
}
