<?php

declare(strict_types=1);

namespace App\Invoice\Quote\Trait;

use App\Auth\Permissions;
use App\Invoice\{
    Quote\QuoteRepository as QR,
    QuoteAmount\QuoteAmountRepository as QAR,
    UserClient\UserClientRepository as UCR,
    UserInv\UserInvRepository as UIR,
};
use Yiisoft\{
    Data\Paginator\OffsetPaginator as DataOffsetPaginator,
    Data\Paginator\PageToken,
    Data\Reader\DataReaderInterface as DRI,
    Data\Reader\Sort,
    Data\Reader\OrderHelper,
    Router\HydratorAttribute\RouteArgument,
    Yii\DataView\YiiRouter\UrlCreator,
};
use Psr\{
    Http\Message\ResponseInterface as Response,
    Http\Message\ServerRequestInterface as Request,
};

trait Guest
{
    public function guest(
        Request $request,
        QAR $qaR,
        QR $qR,
        UCR $ucR,
        UIR $uiR,
        #[RouteArgument('page')]
        int $page = 1,
        #[RouteArgument('status')]
        int $status = 0,
    ): Response {
        $query_params = $request->getQueryParams();
        /**
         * @var string $query_params['page']
         */
        $pageMixed = $query_params['page'] ?? $page;
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = (int) $pageMixed > 0 ? (int) $pageMixed : 1;
        /**
         * @var string|null $query_params['sort']
         */
        $sortString = $query_params['sort'] ?? '-id';
        $urlCreator = new UrlCreator($this->url_generator);
        $order = OrderHelper::stringToArray($sortString);
        $urlCreator->__invoke([], $order);
        $sort = Sort::only(['status_id','number','date_created','date_expires',
            'id','client_id'])->withOrderString($sortString);

        $user = $this->userService->getUser();
        if (!$user) {
            return $this->webService->getNotFoundResponse();
        }
        $userId = $user->reqId();
        $userinv = $uiR->repoUserInvUserIdcount($userId) > 0
            ? $uiR->repoUserInvUserIdquery($userId)
            : null;
        if (!$userinv || !$userinv->getActive()) {
            return $this->webService->getNotFoundResponse();
        }
        $user_clients = $ucR->getAssignedToUser($userId);
        if (empty($user_clients)) {
            $this->flashMessage('warning', $this->translator->translate('user.clients.assigned.not'));
            return $this->webService->getNotFoundResponse();
        }
        /** @var DRI<array-key, array<array-key, mixed>|object> $quotes */
        $quotes = $this->quotesStatusWithSortGuest($qR, $status, $user_clients, $sort);
        $quotes = $this->guestApplyFilters($query_params, $qR, $quotes);
        $userInvLimit = $userinv->getListLimit();
        $paginator = (new DataOffsetPaginator($quotes))
            ->withPageSize($userInvLimit !== null && $userInvLimit > 0
                ? $userInvLimit
                : $this->sR->positiveListLimit())
            ->withCurrentPage($currentPageNeverZero)
            ->withSort($sort)
            ->withToken(PageToken::next((string) $pageMixed));
        $quote_statuses = $qR->getStatuses($this->translator);
        $parameters = [
            'alert' => $this->alert(),
            'qR' => $qR,
            'qaR' => $qaR,
            'quotes' => $quotes,
            'editInv' => $this->userService->hasPermission(Permissions::EDIT_INV),
            'gridSummary' => $this->sR->gridSummary(
                $paginator,
                $this->translator,
                (int) $this->sR->getSetting('default_list_limit'),
                $this->translator->translate('quotes'),
                $qR->getSpecificStatusArrayLabel((string) $status),
            ),
            'defaultPageSizeOffsetPaginator' =>
                $this->sR->getSetting('default_list_limit')
                ? (int) $this->sR->getSetting('default_list_limit')
                : 1,
            'quoteStatuses' => $quote_statuses,
            'max' => (int) $this->sR->getSetting('default_list_limit'),
            'page' => (string) $pageMixed,
            'paginator' => $paginator,
            'sortOrder' => $sortString,
            'status' => $status,
            'urlCreator' => $urlCreator,
        ];
        return $this->webViewRenderer->render('guest', $parameters);
    }

    /**
     * @param QR $qR
     * @param int $status
     * @param array $user_clients
     * @param Sort $sort
     * @return \Yiisoft\Data\Reader\SortableDataInterface
     */
    private function quotesStatusWithSortGuest(QR $qR, int $status,
        array $user_clients, Sort $sort):
            \Yiisoft\Data\Reader\SortableDataInterface
    {
        return $qR->repoGuestClientsSentViewedApprovedRejectedCancelled(
            $status, $user_clients)
                     ->withSort($sort);
    }

    /** @param array<array-key, mixed> $query_params */
    private function guestApplyFilters(array $query_params, QR $qR, DRI $quotes): DRI
    {
        if (isset($query_params['filterQuoteNumber']) && !empty($query_params['filterQuoteNumber'])) {
            $quotes = $qR->filterQuoteNumber((string) $query_params['filterQuoteNumber']);
        }
        if (isset($query_params['filterQuoteAmountTotal']) && !empty($query_params['filterQuoteAmountTotal'])) {
            $quotes = $qR->filterQuoteAmountTotal((string) $query_params['filterQuoteAmountTotal']);
        }
        if ((isset($query_params['filterQuoteNumber']) && !empty($query_params['filterQuoteNumber']))
            && (isset($query_params['filterQuoteAmountTotal']) && !empty($query_params['filterQuoteAmountTotal']))) {
            $quotes = $qR->filterQuoteNumberAndQuoteAmountTotal(
                (string) $query_params['filterQuoteNumber'],
                (float) $query_params['filterQuoteAmountTotal']);
        }
        return $quotes;
    }
}
