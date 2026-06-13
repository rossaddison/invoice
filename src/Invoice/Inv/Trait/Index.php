<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Invoice\{
    Inv\InvIndexFilter,
    Inv\InvIndexListDeps,
    Inv\InvIndexNavDeps,
    Inv\InvForm,
    Inv\InvRepository as IR,
    Inv\Widget\InvsListWidget,
    InvRecurring\InvRecurringRepository as IRR,
};
use App\Widget\Bootstrap5ModalInv;
use Yiisoft\{
    Data\Cycle\Reader\EntityReader,
    Data\Paginator\OffsetPaginator,
    Data\Paginator\PageToken,
    Data\Reader\DataReaderInterface as DRI,
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

            $invs = $this->invsStatus($list->invRepo, $effectiveStatus);

            if (isset($filter->filterInvNumber) && !empty($filter->filterInvNumber)) {
                $invs = $list->invRepo->filterInvNumber($filter->filterInvNumber);
            }
            if (isset($filter->filterCreditInvNumber)
                    && !empty($filter->filterCreditInvNumber)) {
                $invs = $list->invRepo->filterCreditInvNumber($filter->filterCreditInvNumber);
            }
            if (isset($filter->filterFamilyName) && !empty($filter->filterFamilyName)) {
                $invs = $list->invRepo->filterFamilyName($filter->filterFamilyName);
            }
            if (isset($filter->filterInvAmountTotal)
                    && !empty($filter->filterInvAmountTotal)) {
                $invs = $list->invRepo->filterInvAmountTotal(
                    (float) $filter->filterInvAmountTotal);
            }
            if (isset($filter->filterInvAmountPaid)
                    && !empty($filter->filterInvAmountPaid)) {
                $invs = $list->invRepo->filterInvAmountPaid(
                    (float) $filter->filterInvAmountPaid);
            }
            if (isset($filter->filterInvAmountBalance)
                    && !empty($filter->filterInvAmountBalance)) {
                $invs = $list->invRepo->filterInvAmountBalance(
                    (float) $filter->filterInvAmountBalance);
            }
            if ((isset($filter->filterInvNumber) && !empty($filter->filterInvNumber))
               && (isset($filter->filterInvAmountTotal)
                       && !empty($filter->filterInvAmountTotal))) {
                $invs = $list->invRepo->filterInvNumberAndInvAmountTotal(
                    $filter->filterInvNumber, (float) $filter->filterInvAmountTotal);
            }
            if (isset($filter->filterClient) && !empty($filter->filterClient)) {
                $invs = $list->invRepo->filterClient($filter->filterClient);
            }
            if (isset($filter->filterClientGroup) && !empty($filter->filterClientGroup)) {
                $invs = $list->invRepo->filterClientGroup($filter->filterClientGroup);
            }
            if (isset($filter->filterClientAddress1)
                    && !empty($filter->filterClientAddress1)) {
                $invs = $list->invRepo->filterClientAddress1($filter->filterClientAddress1);
            }
            if (isset($filter->filterDateCreatedYearMonth)
                    && !empty($filter->filterDateCreatedYearMonth)) {
                $invs = $list->invRepo->filterDateCreatedLike(
                    'Y-m', $filter->filterDateCreatedYearMonth);
            }

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

            $gridSummary = $this->sR->gridSummary(
                $paginator,
                $this->translator,
                $pageSize,
                $this->translator->translate('invoices'),
                $label,
            );

            $notSet = $this->sR->getSetting('not.set');

            $gRObj = $list->groupRepo->repoGroupquery(
                (int) $this->sR->getSetting('default_invoice_group'));
            $defaultInvoiceGroup = (null !== $gRObj
                && strlen($gRObj->getName() ?? '') > 0)
                ? ($gRObj->getName() ?? $notSet)
                : $notSet;

            $pmRObj = $nav->pmR->repoPaymentMethodquery(
                (int) $this->sR->getSetting('invoice_default_payment_method'));
            $defaultInvoicePaymentMethod = (null !== $pmRObj
                && strlen($pmRObj->getName() ?? '') > 0)
                ? ($pmRObj->getName() ?? $notSet)
                : $notSet;

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
                'label'              => $label,
                'paginator'          => $paginator,
                'qR'                 => $nav->qR,
                'dlR'                => $nav->dlR,
                'soR'                => $nav->soR,
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
                'modal_add_inv' =>
                    $bootstrap5ModalInv->renderPartialLayoutWithFormAsString(
                        'inv', []),
                'modal_create_recurring_multiple' =>
                    $this->indexModalCreateRecurringMultiple($list->irR),
                'modal_copy_inv_multiple' =>
                    $this->indexModalCopyInvMultiple(),
            ];

            if ($request->hasHeader('Hx-Request')) {
                return $this->htmlResponseFactory->createResponse(
                    InvsListWidget::widget()
                        ->withPaginator($paginator)
                        ->withIR($list->invRepo)
                        ->withIrR($list->irR)
                        ->withIslR($list->islR)
                        ->withQR($nav->qR)
                        ->withSoR($nav->soR)
                        ->withDlR($nav->dlR)
                        ->withSR($this->sR)
                        ->withCsrf((string) ($request->getParsedBody()['_csrf'] ?? ''))
                        ->withDecimalPlaces(
                            (int) $this->sR->getSetting('tax_rate_decimal_places'))
                        ->withVisible($visible !== '0')
                        ->withVisibleInvSentLogColumn(
                            $visibleToggleInvSentLogColumn !== '0')
                        ->withGroupBy($filter->groupBy ?? 'none')
                        ->withClientCount($list->clientRepo->count())
                        ->withGridSummary($gridSummary)
                        ->withSortString($sortString)
                        ->withLabel($label)
                        ->withOptionsInvNumberDropDownFilter(
                            $this->optionsDataInvNumberFilter($list->invRepo))
                        ->withOptionsCreditInvNumberDropDownFilter(
                            $this->optionsDataCreditInvNumberFilter($list->invRepo))
                        ->withOptionsFamilyNameDropDownFilter(
                            $this->optionsDataFamilyNameFilter($list->invRepo))
                        ->withOptionsClientsDropDownFilter(
                            $this->optionsDataClientsFilter($list->invRepo))
                        ->withOptionsClientGroupDropDownFilter(
                            $this->optionsDataClientGroupFilter($list->clientRepo))
                        ->withOptionsYearMonthDropDownFilter(
                            $this->optionsDataYearMonthFilter())
                        ->withOptionsStatusDropDownFilter(
                            $this->optionsDataStatusFilter($list->invRepo))
                        ->render()
                );
            }

            return $this->webViewRenderer->render('index', $parameters);
        }
        $this->flashMessage('info',
            $this->translator->translate('user.client.active.no'));
        return $this->webService->getRedirectResponse('client/index');
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

    private function indexModalCopyInvMultiple(): string
    {
        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/inv/modal_copy_inv_multiple');
    }

    /**
     * @psalm-return EntityReader<array-key, array<array-key, mixed>|object>
     */
    private function invsStatus(IR $iR, int $status): DRI
    {
        return $iR->findAllWithStatus($status);
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
}
