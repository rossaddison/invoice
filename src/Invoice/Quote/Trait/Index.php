<?php

declare(strict_types=1);

namespace App\Invoice\Quote\Trait;

use App\Invoice\{
    Client\ClientRepository as CR,
    Group\GroupRepository as GR,
    Quote\QuoteIndexDeps,
    Quote\QuoteIndexFilter,
    Quote\QuoteRepository as QR,
    Quote\QuoteForm,
};
use App\Invoice\Quote\Widget\QuotesListWidget;
use App\Widget\Bootstrap5ModalQuote;
use Yiisoft\{
    Data\Paginator\OffsetPaginator as DataOffsetPaginator,
    Data\Paginator\PageToken,
    Data\Reader\Sort,
    Data\Reader\OrderHelper,
    Html\Html,
    Json\Json,
    Router\HydratorAttribute\RouteArgument,
    Yii\DataView\YiiRouter\UrlCreator,
};
use Psr\{
    Http\Message\ResponseInterface as Response,
    Http\Message\ServerRequestInterface as Request,
};

trait Index
{
    // Only users with editInv permission can access this index.
    // Refer to config/routes accesschecker.

    public function index(
        Request $request,
        QuoteIndexDeps $d,
        QuoteIndexFilter $filter,
        #[RouteArgument('_language')]
        string $_language,
        #[RouteArgument('page')]
        string $page = '1',
        #[RouteArgument('status')]
        string $status = '0',
    ): Response {
        $quoteForm = new QuoteForm();
        $bootstrap5ModalQuote = new Bootstrap5ModalQuote(
            $this->translator,
            $this->webViewRenderer,
            $d->clientRepo,
            $d->groupRepo,
            $this->sR,
            $d->ucR,
            $quoteForm,
        );
        // If the language dropdown changes
        $this->session->set('_language', $_language);
        $active_clients = $d->ucR->getClientsWithUserAccounts();
        if (!$active_clients == []) {
            $query_params = $request->getQueryParams();
            /**
             * @var string $query_params['page']
             */
            $page = $query_params['page'] ?? $page;
            /** @psalm-var positive-int $currentPageNeverZero */
            $currentPageNeverZero = (int) $page > 0 ? (int) $page : 1;
            //status 0 => 'all';
            $status = (int) $status;
            /** @var string $query_params['sort'] */
            $sortString = $query_params['sort'] ?? '-id';
            $urlCreator = new UrlCreator($this->url_generator);
            $order = OrderHelper::stringToArray($sortString);
            $urlCreator->__invoke([], $order);
            $sort = Sort::only(['id', 'status_id', 'number', 'date_created',
                'date_expires','client_id'])
                // (Related logic: see vendor\yiisoft\data\src\Reader\Sort
                // - => 'desc'  so -id => default descending on id
                // Show the latest quotes first => -id
                    ->withOrder($order);
            $effectiveStatus = isset($filter->filterStatus)
                && !empty($filter->filterStatus) ?
                    $filter->filterStatus : $status;
            /**
             * @psalm-var \Yiisoft\Data\Reader\ReadableDataInterface<array-key, array<array-key, mixed>|object>&\Yiisoft\Data\Reader\LimitableDataInterface&\Yiisoft\Data\Reader\OffsetableDataInterface&\Yiisoft\Data\Reader\CountableDataInterface $quotes
             */
            $quotes = $this->quotesStatusWithSort($d->quoteRepo, (int) $effectiveStatus, $sort);
            if (isset($query_params['filterQuoteNumber'])
                && !empty($query_params['filterQuoteNumber'])) {
                $quotes = $d->quoteRepo->filterQuoteNumber(
                    (string) $query_params['filterQuoteNumber']);
            }
            if (isset($query_params['filterQuoteAmountTotal'])
                && !empty($query_params['filterQuoteAmountTotal'])) {
                $quotes = $d->quoteRepo->filterQuoteAmountTotal(
                    (string) $query_params['filterQuoteAmountTotal']);
            }
            if (isset($filter->filterClient) && !empty($filter->filterClient)) {
                $quotes = $d->quoteRepo->filterClient($filter->filterClient);
            }
            if ((isset($query_params['filterQuoteNumber'])
                && !empty($query_params['filterQuoteNumber']))
                && (isset($query_params['filterQuoteAmountTotal'])
                && !empty($query_params['filterQuoteAmountTotal']))) {
                $quotes = $d->quoteRepo->filterQuoteNumberAndQuoteAmountTotal(
                    (string) $query_params['filterQuoteNumber'],
                        (float) $query_params['filterQuoteAmountTotal']);
            }
            $paginator = (new DataOffsetPaginator($quotes))
            ->withPageSize($this->sR->positiveListLimit())
            ->withCurrentPage($currentPageNeverZero)
            ->withSort($sort)
            ->withToken(PageToken::next($page));
            $quote_statuses = $d->quoteRepo->getStatuses($this->translator);
            $parameters = [
                'status' => $status,
                'decimalPlaces' => (int)
                    $this->sR->getSetting('tax_rate_decimal_places'),
                'paginator' => $paginator,
                'sortOrder' => $query_params['sort'] ?? '',
                'sortString' => $sortString,
                'alert' => $this->alert(),
                'clientCount' => $d->clientRepo->count(),
                'groupBy' => $filter->groupBy,
                'label' => $d->quoteRepo->getSpecificStatusArrayLabel((string) $status),
                'page' => $currentPageNeverZero,
                'quotes' => $quotes,
                'visible' => $this->sR->getSetting('columns_all_visible') === '1',
                'optionsDataClientsDropdownFilter' =>
                    $this->optionsDataClients($d->quoteRepo),
                'optionsDataClientGroupDropDownFilter' =>
                    $this->optionsDataClientGroup($d->quoteRepo),
                'optionsDataQuoteNumberDropDownFilter' =>
                    $this->optionsDataQuoteNumber($d->quoteRepo),
                'optionsDataStatusDropDownFilter' =>
                    $this->optionsDataStatuses($d->quoteRepo),
                'gridSummary' => $this->sR->gridSummary(
                    $paginator,
                    $this->translator,
                    (int) $this->sR->getSetting('default_list_limit'),
                    $this->translator->translate('quotes'),
                    $d->quoteRepo->getSpecificStatusArrayLabel((string) $status),
                ),
                'defaultPageSizeOffsetPaginator' =>
                    $this->sR->getSetting('default_list_limit')
                    ? (int) $this->sR->getSetting('default_list_limit') : 1,
                'defaultQuoteGroup' => $this->indexDefaultQuoteGroup($d->groupRepo),
                'quoteStatuses' => $quote_statuses,
                'max' => (int) $this->sR->getSetting('default_list_limit'),
                'qR' => $d->quoteRepo,
                'qaR' => $d->qaR,
                'soR' => $d->soR,
                'modal_add_quote' =>
                    $bootstrap5ModalQuote->renderPartialLayoutWithFormAsString(
                        'quote', []),
                'urlCreator' => $urlCreator,
            ];
            if ($request->hasHeader('Hx-Request')) {
                return $this->htmlResponseFactory->createResponse(
                    QuotesListWidget::widget()
                        ->withPaginator($paginator)
                        ->withQR($d->quoteRepo)
                        ->withSoR($d->soR)
                        ->withSR($this->sR)
                        ->withCsrf((string) ($request->getParsedBody()['_csrf'] ?? ''))
                        ->withDecimalPlaces((int) $this->sR->getSetting('tax_rate_decimal_places'))
                        ->withVisible($this->sR->getSetting('columns_all_visible') === '1')
                        ->withGroupBy($filter->groupBy ?? 'none')
                        ->withClientCount($d->clientRepo->count())
                        ->withGridSummary($this->sR->gridSummary(
                            $paginator,
                            $this->translator,
                            (int) $this->sR->getSetting('default_list_limit'),
                            $this->translator->translate('quotes'),
                            $d->quoteRepo->getSpecificStatusArrayLabel((string) $status),
                        ))
                        ->withSortString($sortString)
                        ->withOptionsDataClientsDropdownFilter($this->optionsDataClients($d->quoteRepo))
                        ->withOptionsDataStatusDropDownFilter($this->optionsDataStatuses($d->quoteRepo))
                        ->render()
                );
            }
            return $this->webViewRenderer->render('index', $parameters);
        }
        $this->flashMessage('info',
            $this->translator->translate('user.client.active.no'));
        return $this->webService->getRedirectResponse('client/index');
    }

    public function indexDefaultQuoteGroup(GR $groupRepo): string
    {
        $group = $groupRepo->repoGroupquery(
            (int) $this->sR->getSetting('default_quote_group')
        );
        $defaultQuoteGroup = $this->sR->getSetting('not_set');
        if ($group !== null) {
            $groupName = $group->getName() ?? '';
            if ($groupName !== '') {
                $defaultQuoteGroup = $groupName;
            }
        }
        return $defaultQuoteGroup;
    }

    // jquery function currently not used
    // Data parsed from quote.js:$(document).on('click',
    // '#client_change_confirm', function () {

    public function modalChangeClient(Request $request, CR $cR):
        Response
    {
        $body = $request->getQueryParams();
        $client = $cR->repoClientquery((int) $body['client_id']);
        $parameters = [
            'success' => 1,
            // Set a client id on quote/view.php so that details
            // can be saved later.
            'pre_save_client_id' => $body['client_id'],
            'client_address_1' => ($client->getClientAddress1() ?? '')
            . '<br>',
            'client_address_2' => ($client->getClientAddress2() ?? '')
            . '<br>',
            'client_townline' => ($client->getClientCity() ?? '')
                . '<br>' . ($client->getClientState() ?? '') . '<br>'
                . ($client->getClientZip() ?? '') . '<br>',
            'client_country' => $client->getClientCountry() ?? '',
            'client_phone' => $this->translator->translate('phone')
                . '&nbsp;' . ($client->getClientPhone() ?? ''),
            'client_mobile' => $this->translator->translate('mobile')
                . '&nbsp;' . ($client->getClientMobile() ?? ''),
            'client_fax' => $this->translator->translate('fax')
                . '&nbsp;' . ($client->getClientFax() ?? ''),
            'client_email' => $this->translator->translate('email') . '&nbsp;'
                . (string) Html::link($client->getClientEmail()),
            // Reset the a href id="after_client_change_url" link
            // to the new client url
            'after_client_change_url' => 'client/view/'
                . (string) $body['client_id'],
            'after_client_change_name' => $client->getClientName(),
        ];
        // return parameters to quote.js:client_change_confirm ajax success
        // function for processing
        return $this->factory->createResponse(Json::encode($parameters));
    }

    /**
     * @param QR $quoteRepo
     * @param int $status
     * @param Sort $sort
     * @return \Yiisoft\Data\Reader\SortableDataInterface
     */
    private function quotesStatusWithSort(QR $quoteRepo, int $status,
        Sort $sort): \Yiisoft\Data\Reader\SortableDataInterface
    {
        return $quoteRepo->findAllWithStatus($status)
                            ->withSort($sort);
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function quotes(QR $quoteRepo, int $status):
        \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $quoteRepo->findAllWithStatus($status);
    }
}
