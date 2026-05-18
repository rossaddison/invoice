<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Invoice\{
    Client\ClientRepository as CR,
    DeliveryLocation\DeliveryLocationRepository as DLR,
    Group\GroupRepository as GR,
    Inv\InvRepository as IR,
    Inv\InvForm,
    Inv\Widget\InvsListWidget,
    InvRecurring\InvRecurringRepository as IRR,
    InvSentLog\InvSentLogRepository as ISLR,
    PaymentMethod\PaymentMethodRepository as PMR,
    Quote\QuoteRepository as QR,
    SalesOrder\SalesOrderRepository as SOR,
    UserClient\UserClientRepository as UCR,
};
use App\Widget\Bootstrap5ModalInv;
use Yiisoft\{
    Data\Cycle\Reader\EntityReader,
    Data\Paginator\OffsetPaginator,
    Data\Paginator\PageToken,
    Data\Reader\DataReaderInterface as DRI,
    Data\Reader\Sort,
    Html\Html,
    Input\Http\Attribute\Parameter\Query,
    Router\HydratorAttribute\RouteArgument,
};
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

trait Index
{

public function index(
        Request $request,
        IR $invRepo,
        IRR $irR,
        ISLR $islR,
        CR $clientRepo,
        GR $groupRepo,
        QR $qR,
        PMR $pmR,
        SOR $soR,
        DLR $dlR,
        UCR $ucR,
        #[RouteArgument('_language')]
        string $_language,
        #[RouteArgument('page')]
        string $page = '1',
        #[RouteArgument('status')]
        string $status = '0',
        #[Query('page')]
        ?string $queryPage = null,
        #[Query('sort')]
        ?string $querySort = null,
        #[Query('filterInvNumber')]
        ?string $queryFilterInvNumber = null,
        #[Query('filterCreditInvNumber')]
        ?string $queryFilterCreditInvNumber = null,
        #[Query('filterFamilyName')]
        ?string $queryFilterFamilyName = null,
        #[Query('filterClient')]
        ?string $queryFilterClient = null,
        #[Query('filterInvAmountTotal')]
        ?string $queryFilterInvAmountTotal = null,
        #[Query('filterInvAmountPaid')]
        ?string $queryFilterInvAmountPaid = null,
        #[Query('filterInvAmountBalance')]
        ?string $queryFilterInvAmountBalance = null,
        #[Query('filterClientGroup')]
        ?string $queryFilterClientGroup = null,
        #[Query('filterClientAddress1')]
        ?string $queryFilterClientAddress1 = null,
        #[Query('filterDateCreatedYearMonth')]
        ?string $queryFilterDateCreatedYearMonth = null,
        #[Query('filterStatus')]
        ?string $queryFilterStatus = null,
        #[Query('groupBy')]
        ?string $queryGroupBy = 'none',
    ): Response {
        $visible = $this->sR->getSetting('columns_all_visible');
        $visibleToggleInvSentLogColumn =
            $this->sR->getSetting('column_inv_sent_log_visible');
        $invForm = new InvForm();
        $bootstrap5ModalInv = new Bootstrap5ModalInv(
            $this->translator,
            $this->webViewRenderer,
            $clientRepo,
            $groupRepo,
            $this->sR,
            $ucR,
            $invForm,
        );
        $this->session->set('_language', $_language);
        $this->disableReadOnlyStatusMessage();
        $active_clients = $ucR->getClientsWithUserAccounts();
        if ($active_clients) {
            $page           = $queryPage ?? $page;
            $effectiveStatus = isset($queryFilterStatus)
                && !empty($queryFilterStatus)
                ? (int) $queryFilterStatus : (int) $status;
            $sortString     = $querySort ?? '-id';
            $sort           = Sort::only([
                'id', 'status_id', 'number', 'date_created', 'date_due', 'client_id',
            ])->withOrderString($sortString);

            $invs = $this->invsStatus($invRepo, $effectiveStatus);

            if (isset($queryFilterInvNumber) && !empty($queryFilterInvNumber)) {
                $invs = $invRepo->filterInvNumber($queryFilterInvNumber);
            }
            if (isset($queryFilterCreditInvNumber)
                    && !empty($queryFilterCreditInvNumber)) {
                $invs = $invRepo->filterCreditInvNumber($queryFilterCreditInvNumber);
            }
            if (isset($queryFilterFamilyName) && !empty($queryFilterFamilyName)) {
                $invs = $invRepo->filterFamilyName($queryFilterFamilyName);
            }
            if (isset($queryFilterInvAmountTotal)
                    && !empty($queryFilterInvAmountTotal)) {
                $invs = $invRepo->filterInvAmountTotal(
                    (float) $queryFilterInvAmountTotal);
            }
            if (isset($queryFilterInvAmountPaid)
                    && !empty($queryFilterInvAmountPaid)) {
                $invs = $invRepo->filterInvAmountPaid(
                    (float) $queryFilterInvAmountPaid);
            }
            if (isset($queryFilterInvAmountBalance)
                    && !empty($queryFilterInvAmountBalance)) {
                $invs = $invRepo->filterInvAmountBalance(
                    (float) $queryFilterInvAmountBalance);
            }
            if ((isset($queryFilterInvNumber) && !empty($queryFilterInvNumber))
               && (isset($queryFilterInvAmountTotal)
                       && !empty($queryFilterInvAmountTotal))) {
                $invs = $invRepo->filterInvNumberAndInvAmountTotal(
                    $queryFilterInvNumber, (float) $queryFilterInvAmountTotal);
            }
            if (isset($queryFilterClient) && !empty($queryFilterClient)) {
                $invs = $invRepo->filterClient($queryFilterClient);
            }
            if (isset($queryFilterClientGroup) && !empty($queryFilterClientGroup)) {
                $invs = $invRepo->filterClientGroup($queryFilterClientGroup);
            }
            if (isset($queryFilterClientAddress1)
                    && !empty($queryFilterClientAddress1)) {
                $invs = $invRepo->filterClientAddress1($queryFilterClientAddress1);
            }
            if (isset($queryFilterDateCreatedYearMonth)
                    && !empty($queryFilterDateCreatedYearMonth)) {
                $invs = $invRepo->filterDateCreatedLike(
                    'Y-m', $queryFilterDateCreatedYearMonth);
            }

            $currentPage = max(1, (int) $page);
            $pageSize    = max(1, (int) ($this->sR->getSetting('default_list_limit') ?: 1));

            $paginator = (new OffsetPaginator($invs))
                ->withPageSize($pageSize)
                ->withCurrentPage($currentPage)
                ->withSort($sort)
                ->withToken(PageToken::next((string) $currentPage));

            $inv_statuses = $invRepo->getStatuses($this->translator);
            $label        = $invRepo->getSpecificStatusArrayLabel($status);

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

            $gRObj = $groupRepo->repoGroupquery(
                (int) $this->sR->getSetting('default_invoice_group'));
            $defaultInvoiceGroup = (null !== $gRObj
                && strlen($gRObj->getName() ?? '') > 0)
                ? ($gRObj->getName() ?? $notSet)
                : $notSet;

            $pmRObj = $pmR->repoPaymentMethodquery(
                (int) $this->sR->getSetting('invoice_default_payment_method'));
            $defaultInvoicePaymentMethod = (null !== $pmRObj
                && strlen($pmRObj->getName() ?? '') > 0)
                ? ($pmRObj->getName() ?? $notSet)
                : $notSet;

            $parameters = [
                'alert'              => $this->alert(),
                'clientCount'        => $clientRepo->count(),
                'decimalPlaces'      =>
                    (int) $this->sR->getSetting('tax_rate_decimal_places'),
                'defaultInvoiceGroup'         => $defaultInvoiceGroup,
                'defaultInvoicePaymentMethod' => $defaultInvoicePaymentMethod,
                'groupBy'            => $queryGroupBy,
                'gridSummary'        => $gridSummary,
                'iR'                 => $invRepo,
                'irR'                => $irR,
                'islR'               => $islR,
                'inv_statuses'       => $inv_statuses,
                'label'              => $label,
                'paginator'          => $paginator,
                'qR'                 => $qR,
                'dlR'                => $dlR,
                'soR'                => $soR,
                'sortString'         => $sortString,
                'status'             => $effectiveStatus,
                'visible'            => $visible !== '0',
                'visibleToggleInvSentLogColumn' =>
                    $visibleToggleInvSentLogColumn !== '0',
                'optionsClientsDropDownFilter' =>
                    $this->optionsDataClientsFilter($invRepo),
                'optionsClientGroupDropDownFilter' =>
                    $this->optionsDataClientGroupFilter($clientRepo),
                'optionsInvNumberDropDownFilter' =>
                    $this->optionsDataInvNumberFilter($invRepo),
                'optionsCreditInvNumberDropDownFilter' =>
                    $this->optionsDataCreditInvNumberFilter($invRepo),
                'optionsFamilyNameDropDownFilter' =>
                    $this->optionsDataFamilyNameFilter($invRepo),
                'optionsYearMonthDropDownFilter' =>
                    $this->optionsDataYearMonthFilter(),
                'optionsStatusDropDownFilter' =>
                    $this->optionsDataStatusFilter($invRepo),
                'modal_add_inv' =>
                    $bootstrap5ModalInv->renderPartialLayoutWithFormAsString(
                        'inv', []),
                'modal_create_recurring_multiple' =>
                    $this->indexModalCreateRecurringMultiple($irR),
                'modal_copy_inv_multiple' =>
                    $this->indexModalCopyInvMultiple(),
            ];

            if ($request->hasHeader('Hx-Request')) {
                return $this->htmlResponseFactory->createResponse(
                    InvsListWidget::widget()
                        ->withPaginator($paginator)
                        ->withIR($invRepo)
                        ->withIrR($irR)
                        ->withIslR($islR)
                        ->withQR($qR)
                        ->withSoR($soR)
                        ->withDlR($dlR)
                        ->withSR($this->sR)
                        ->withCsrf((string) ($request->getParsedBody()['_csrf'] ?? ''))
                        ->withDecimalPlaces(
                            (int) $this->sR->getSetting('tax_rate_decimal_places'))
                        ->withVisible($visible !== '0')
                        ->withVisibleInvSentLogColumn(
                            $visibleToggleInvSentLogColumn !== '0')
                        ->withGroupBy($queryGroupBy ?? 'none')
                        ->withClientCount($clientRepo->count())
                        ->withGridSummary($gridSummary)
                        ->withSortString($sortString)
                        ->withLabel($label)
                        ->withOptionsInvNumberDropDownFilter(
                            $this->optionsDataInvNumberFilter($invRepo))
                        ->withOptionsCreditInvNumberDropDownFilter(
                            $this->optionsDataCreditInvNumberFilter($invRepo))
                        ->withOptionsFamilyNameDropDownFilter(
                            $this->optionsDataFamilyNameFilter($invRepo))
                        ->withOptionsClientsDropDownFilter(
                            $this->optionsDataClientsFilter($invRepo))
                        ->withOptionsClientGroupDropDownFilter(
                            $this->optionsDataClientGroupFilter($clientRepo))
                        ->withOptionsYearMonthDropDownFilter(
                            $this->optionsDataYearMonthFilter())
                        ->withOptionsStatusDropDownFilter(
                            $this->optionsDataStatusFilter($invRepo))
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
