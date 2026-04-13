<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Invoice\Entity\Inv;
use App\Invoice\{
    Client\ClientRepository as CR,
    DeliveryLocation\DeliveryLocationRepository as DLR,
    Group\GroupRepository as GR,
    Inv\InvRepository as IR,
    Inv\InvForm,
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
    Data\Reader\DataReaderInterface as DRI,
    Html\Html,
    Input\Http\Attribute\Parameter\Query,
    Router\HydratorAttribute\RouteArgument};
use Psr\Http\Message\ResponseInterface as Response;

trait Index
{
 
public function index(
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
        // build the inv and hasOne InvAmount table
        $visible = $this->sR->getSetting('columns_all_visible');
        $visibleToggleInvSentLogColumn =
            $this->sR->getSetting('column_inv_sent_log_visible');
        $inv = new Inv();
        $invForm = new InvForm($inv);
        $bootstrap5ModalInv = new Bootstrap5ModalInv(
            $this->translator,
            $this->webViewRenderer,
            $clientRepo,
            $groupRepo,
            $this->sR,
            $ucR,
            $invForm,
        );
        // If the language dropdown changes
        $this->session->set('_language', $_language);
        // ensure that admin is aware when read-only functionality ie.
        // invoice deletion prevention has changed
        $this->disableReadOnlyStatusMessage();
        $active_clients = $ucR->getClientsWithUserAccounts();
        if ($active_clients) {
            // All, Draft, Sent ... filter governed by routes eg.
            // invoice.myhost/invoice/inv/page/1/status/1 =>
            // #[RouteArgument('page')] string $page etc
            $page = $queryPage ?? $page;
            //status 0 => 'all';
            // Use query parameter if provided, otherwise use route parameter
            $effectiveStatus = isset($queryFilterStatus)
                && !empty($queryFilterStatus) ?
                    (int) $queryFilterStatus : (int) $status;
            $invs = $this->invsStatus($invRepo, $effectiveStatus);
            if (isset($queryFilterInvNumber) && !empty($queryFilterInvNumber)) {
                $invs = $invRepo->filterInvNumber($queryFilterInvNumber);
            }
            if (isset($queryFilterCreditInvNumber)
                    && !empty($queryFilterCreditInvNumber)) {
                $invs = $invRepo->filterCreditInvNumber(
                    $queryFilterCreditInvNumber);
            }
            if (isset($queryFilterFamilyName) && !empty($queryFilterFamilyName)) {
                //$family = $familyRepo->withName($queryFilterFamilyName);
                // family -> product -> inv
                $invs = $invRepo->filterFamilyName($queryFilterFamilyName);
            }
            if (isset($queryFilterInvAmountTotal)
                    && !empty($queryFilterInvAmountTotal)) {
                $invs = $invRepo->filterInvAmountTotal($queryFilterInvAmountTotal);
            }
            if (isset($queryFilterInvAmountPaid)
                    && !empty($queryFilterInvAmountPaid)) {
                $invs = $invRepo->filterInvAmountPaid($queryFilterInvAmountPaid);
            }
            if (isset($queryFilterInvAmountBalance)
                    && !empty($queryFilterInvAmountBalance)) {
                $invs = $invRepo->filterInvAmountBalance($queryFilterInvAmountBalance);
            }
            if ((isset($queryFilterInvNumber)
                    && !empty($queryFilterInvNumber))
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
                // Use the mySql format 'Y-m'
                $invs = $invRepo->filterDateCreatedLike('Y-m',
                    $queryFilterDateCreatedYearMonth);
            }
            $inv_statuses = $invRepo->getStatuses($this->translator);
            $label = $invRepo->getSpecificStatusArrayLabel($status);
            $this->draftFlash($_language);
            $this->markSentFlash($_language);
            $parameters = [
                'alert' => $this->alert(),
                'clientCount' => $clientRepo->count(),
                'decimalPlaces' =>
                    (int) $this->sR->getSetting('tax_rate_decimal_places'),
                'defaultPageSizeOffsetPaginator' =>
                    $this->sR->getSetting('default_list_limit') ?
                        (int) $this->sR->getSetting('default_list_limit') : 1,
                'defaultInvoiceGroup' =>
                    null !==
                        ($gR = $groupRepo->repoGroupquery(
                                $this->sR->getSetting('default_invoice_group'))
                        )   ? (strlen($groupName = $gR->getName() ?? '') > 0
                            ? $groupName
                            : $this->sR->getSetting('not.set'))
                            : $this->sR->getSetting('not.set'),
                'defaultInvoicePaymentMethod' =>
                    null !==
                        ($pmR = $pmR->repoPaymentMethodquery(
                        $this->sR->getSetting('invoice_default_payment_method')))
                        ? (strlen($paymentMethodName = $pmR->getName() ?? '') > 0
                        ? $paymentMethodName : $this->sR->getSetting('not.set'))
                        : $this->sR->getSetting('not.set'),
                // numbered tiles between the arrrows
                'maxNavLinkCount' => 10,
                'groupBy' => $queryGroupBy,
                'invs' => $invs,
                'inv_statuses' => $inv_statuses,
                'max' => (int) $this->sR->getSetting('default_list_limit'),
                'page' => (int) $page > 0 ? (int) $page : 1,
                'status' => $effectiveStatus,
                'qR' => $qR,
                'dlR' => $dlR,
                'soR' => $soR,
                // Use the invRepo to retrieve the Invoice Number of the invoice
                // that a credit note has been generated from
                'iR' => $invRepo,
                'irR' => $irR,
                'islR' => $islR,
                'label' => $label,
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
                'sortString' => $querySort ?? '-id',
                'viewRenderer' => $this->webViewRenderer,
                'visible' => $visible !== '0',
                'visibleToggleInvSentLogColumn' =>
                    $visibleToggleInvSentLogColumn !== '0',
                'locale' => new \Yiisoft\I18n\Locale($_language),
            ];
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
    private function invsStatus(IR $iR, int $status):
        DRI
    {
        return $iR->findAllWithStatus($status);
    }    
    
    /**
     * Use: Toggle Button on Flash message reminder
     */
    private function draftFlash(string $_language): void
    {
        // Get the current draft setting
        $draft = $this->sR->getSetting('generate_invoice_number_for_draft');
        // Get the setting_id to allow for editing
        $setting = $this->sR->withKey('generate_invoice_number_for_draft');
        $setting_url = '';
        if (null !== $setting) {
            $setting_id = $setting->getSettingId();
            // The route name has been simplified and differs from the action
            // 'setting/inv_draft_has_number_switch'
            $setting_url = $this->url_generator->generate(
                'setting/draft', ['_language' => $_language,
                    'setting_id' => $setting_id]);
        }
        $level = $draft == '0' ? 'warning' : 'info';
        $on_off = $draft == '0' ? 'off' : 'on';
        $message = $this->translator->translate('draft.number.'
          . $on_off) . str_repeat('&nbsp;', 2)
          . (!empty($setting_url) ? (string) Html::a(Html::tag('i', '',
                ['class' => 'bi bi-pencil']), $setting_url,
                    ['class' => 'btn btn-primary'])
          : '');
        $this->flashMessage($level, $message);
    }
}
