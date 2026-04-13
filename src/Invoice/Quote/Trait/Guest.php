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

        // Get the current user and determine from (Related logic: see
        // Settings...User Account) whether they have been given
        // either guest or admin rights. These rights are unrelated to rbac and
        // serve as a second line of defense' to support role based admin
        // control.

        // Retrieve the user from Yii-Demo's list of users in the User Table
        $user = $this->userService->getUser();
        if ($user) {
            // Use this user's id to see whether a user has been setup under
            // UserInv ie. yii-invoice's list of users
            $userinv = ($uiR->repoUserInvUserIdcount(
                    (string) $user->getId()) > 0
                     ? $uiR->repoUserInvUserIdquery((string) $user->getId())
                     : null);
            if ($userinv && $userinv->getActive()) {
                // Determine what clients have been allocated to this user
                // (Related logic: see Settings...User Account)
                // by looking at UserClient table

                // eg. If the user is a guest-accountant, they will have been
                // allocated certain clients
                // A user-quest-accountant will be allocated a series of clients
                // A user-guest-client will be allocated their client number by
                // the administrator so that they can view their quotes when
                // they log in
                $user_clients = $ucR->getAssignedToUser(
                    (string) $user->getId());
                if (!empty($user_clients)) {
/**
 * @psalm-var \Yiisoft\Data\Reader\ReadableDataInterface<array-key, array<array-key, mixed>|object>&\Yiisoft\Data\Reader\LimitableDataInterface&\Yiisoft\Data\Reader\OffsetableDataInterface&\Yiisoft\Data\Reader\CountableDataInterface $quotes
 */
                    $quotes = $this->quotesStatusWithSortGuest($qR,
                            $status, $user_clients, $sort);
                    if (isset($query_params['filterQuoteNumber'])
                        && !empty($query_params['filterQuoteNumber'])) {
                        $quotes = $qR->filterQuoteNumber(
                            (string) $query_params['filterQuoteNumber']);
                    }
                    if (isset($query_params['filterQuoteAmountTotal'])
                        && !empty($query_params['filterQuoteAmountTotal'])) {
                        $quotes = $qR->filterQuoteAmountTotal(
                            (string) $query_params['filterQuoteAmountTotal']);
                    }
                    if ((isset($query_params['filterQuoteNumber'])
                        && !empty($query_params['filterQuoteNumber']))
                        && (isset($query_params['filterQuoteAmountTotal'])
                        && !empty($query_params['filterQuoteAmountTotal']))) {
                        $quotes = $qR->filterQuoteNumberAndQuoteAmountTotal(
                            (string) $query_params['filterQuoteNumber'],
                                (float) $query_params['filterQuoteAmountTotal']);
                    }
                    $paginator = (new DataOffsetPaginator($quotes))
                    ->withPageSize($this->sR->positiveListLimit())
                    ->withCurrentPage($currentPageNeverZero)
                    ->withSort($sort)
                    ->withToken(PageToken::next((string) $pageMixed));
                    $quote_statuses = $qR->getStatuses($this->translator);
                    $parameters = [
                        'alert' => $this->alert(),
                        'qR' => $qR,
                        'qaR' => $qaR,
                        'quotes' => $quotes,
                        // guests will not have access to the pageListLimiter
                        'editInv' => $this->userService->hasPermission(
                            Permissions::EDIT_INV),
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
                        'max' => (int)
                            $this->sR->getSetting('default_list_limit'),
                        'page' => (string) $pageMixed,
                        'paginator' => $paginator,
                        'sortOrder' => $sortString,
                        'status' => $status,
                        'urlCreator' => $urlCreator,
                    ];
                    return $this->webViewRenderer->render('guest', $parameters);
                } // empty user client
                $this->flashMessage('warning',
                            $this->translator->translate('user.clients.assigned.not'));
            } // userinv
        } //user
        return $this->webService->getNotFoundResponse();
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
}