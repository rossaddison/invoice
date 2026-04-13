<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Auth\Permissions;
use App\User\User;

use App\Invoice\{
    Entity\Inv, 
    Inv\InvRepository as IR,
    InvAmount\InvAmountRepository as IAR,
    InvRecurring\InvRecurringRepository as IRR,
    UserClient\UserClientRepository as UCR,
    UserInv\UserInvRepository as UIR
};
use Yiisoft\{
    Data\Reader\DataReaderInterface as DRI,
    Data\Reader\SortableDataInterface as SDI,
    Input\Http\Attribute\Parameter\Query,
    Router\HydratorAttribute\RouteArgument 
};
use Psr\{Http\Message\ResponseInterface as Response,
};

trait Guest
{
    
    /**
     * Related logic:
     *  see Route::get('/client_invoices[/page/{page:\d+}[/status/{status:\d+}]]')
     *  status and page are digits
     */
    public function guest(
        IAR $iaR,
        IRR $irR,
        IR $iR,
        UCR $ucR,
        UIR $uiR,
        #[RouteArgument('page')]
        string $page = '1',
        #[RouteArgument('status')]
        string $status = '0',
        #[Query('page')]
        ?string $queryPage = null,
        #[Query('sort')]
        ?string $querySort = null,
        #[Query('filterClient')]
        ?string $queryFilterClient = null,
        #[Query('filterInvNumber')]
        ?string $queryFilterInvNumber = null,
        #[Query('filterCreditInvNumber')]
        ?string $queryFilterCreditInvNumber = null,
        #[Query('filterInvAmountTotal')]
        ?string $queryFilterInvAmountTotal = null,
        #[Query('filterInvAmountPaid')]
        ?string $queryFilterInvAmountPaid = null,
        #[Query('filterInvAmountBalance')]
        ?string $queryFilterInvAmountBalance = null,
        #[Query('filterStatus')]
        ?string $queryFilterStatus = null,
    ): Response {
        $page = $queryPage ?? $page;
        $sortString = $querySort ?? '-id';
        // Get the current user and determine from (Related logic: see Settings
        // ...User Account) whether they have been given either guest or admin
        // rights. These rights are unrelated to rbac and serve as a second
        // 'line of defense' to support role based admin control. Retrieve the
        // user from Yii-Demo's list of users in the User Table
        $user = $this->userService->getUser();
        if ($user instanceof User && null !== $user->getId()) {
            $user_id = $user->getId();
            // Use this user's id to see whether a user has been setup under
            // UserInv ie. yii-invoice's list of users
            $userInv = ($uiR->repoUserInvUserIdcount((string) $user_id) > 0 ?
                $uiR->repoUserInvUserIdquery((string) $user_id) : null);
            if (null !== $userInv && null !== $user_id && $userInv->getActive()) {
                $userInvListLimit = $userInv->getListLimit();
                // Determine what clients have been allocated to this user
                // (Related logic: see Settings...User Account) by looking at
                // UserClient table eg. If the user is a guest-accountant, they
                // will have been allocated certain clients. A
                // user-quest-accountant will be allocated a series of clients
                // A user-guest-client will be allocated their client number by
                // the administrator so that they can view their invoices and
                // make payment
                $user_clients = $ucR->getAssignedToUser($user_id);
                if (!empty($user_clients)) {
                    $effectiveStatus = isset($queryFilterStatus)
                        && !empty($queryFilterStatus) ?
                            (int) $queryFilterStatus : (int) $status;
                    $invs = $this->invsStatusGuest($iR, $effectiveStatus,
                            $user_clients);
                    $preFilterInvs = $invs;
                    if (isset($queryFilterInvNumber)
                            && !empty($queryFilterInvNumber)) {
                        $invs = $iR->filterInvNumber($queryFilterInvNumber);
                    }
                    if (isset($queryFilterCreditInvNumber)
                            && !empty($queryFilterCreditInvNumber)) {
                        $invs = $iR->filterCreditInvNumber(
                            $queryFilterCreditInvNumber);
                    }
                    if (isset($queryFilterInvAmountTotal)
                            && !empty($queryFilterInvAmountTotal)) {
                        $invs = $iR->filterInvAmountTotal(
                            $queryFilterInvAmountTotal);
                    }
                    if (isset($queryFilterInvAmountPaid)
                            && !empty($queryFilterInvAmountPaid)) {
                        $invs = $iR->filterInvAmountPaid($queryFilterInvAmountPaid);
                    }
                    if (isset($queryFilterInvAmountBalance)
                            && !empty($queryFilterInvAmountBalance)) {
                        $invs = $iR->filterInvAmountBalance($queryFilterInvAmountBalance);
                    }
                    if ((isset($queryFilterInvNumber)
                            && !empty($queryFilterInvNumber))
                       && (isset($queryFilterInvAmountTotal)
                               && !empty($queryFilterInvAmountTotal))) {
                        $invs = $iR->filterInvNumberAndInvAmountTotal(
                            $queryFilterInvNumber,
                                (float) $queryFilterInvAmountTotal);
                    }
                    if (isset($queryFilterClient) && !empty($queryFilterClient)) {
                        $invs = $iR->filterGuestClient($queryFilterClient);
                    }
                    $inv_statuses = $iR->getStatuses($this->translator);
                    $label = $iR->getSpecificStatusArrayLabel($status);
                    $parameters = [
                        'alert' => $this->alert(),
                        'decimalPlaces' => (int) $this->sR->getSetting(
                            'tax_rate_decimal_places'),
                        'optionsClientsDropDownFilter' =>
                        $this->optionsDataUserClientsFilter($ucR, $user_id),
                        'optionsInvNumberDropDownFilter' => $this->                                                         optionsDataInvNumberGuestFilter($preFilterInvs),
                        'optionsCreditInvNumberDropDownFilter' =>
                        $this->optionsDataCreditInvNumberGuestFilter(
                            $preFilterInvs, $iR),
                        'optionsStatusDropDownFilter' =>
                                $this->optionsDataStatusFilter($iR),
                        'iaR' => $iaR,
                        'iR' => $iR,
                        'irR' => $irR,
                        'invs' => $invs,
                        'label' => $label,
                        // the guest will not have access to the pageSizeLimiter
                        'viewInv' =>
                        $this->userService->hasPermission(Permissions::VIEW_INV),
                        // update userinv with the user's listlimit preference
                        'userInv' => $userInv,
                        'userInvListLimit' => $userInvListLimit,
                        'defaultPageSizeOffsetPaginator' =>
                            $userInv->getListLimit() ?? 10,
                        // numbered tiles between the arrrows
                        'maxNavLinkCount' => 10,
                        'invStatuses' => $inv_statuses,
                        'page' => (int) $page > 0 ? (int) $page : 1,
                        // Clicking on a grid column sort hyperlink will
                        // generate a url query_param eg. ?sort=
                        'sortOrder' => $querySort ?? '',
                        'sortString' => $sortString,
                        'status' => $status,
                    ];
                    return $this->webViewRenderer->render('guest', $parameters);
                } // no clients assigned to this user
                $this->flashMessage('warning',
                    $this->translator->translate('user.clients.assigned.not'));
            } // $user_inv
            $this->flashMessage('info',
                        $this->translator->translate('user.inv.active.not'));
            return $this->webService->getNotFoundResponse();
        } // $user
        return $this->webService->getNotFoundResponse();
    }
    
    /**
     * @param IR $iR
     * @param mixed $status
     * @param array $user_clients
     * @return DRI&SDI
     *
     * @psalm-return SDI&DRI<int, Inv>
     */
    private function invsStatusGuest(IR $iR, mixed $status,
        array $user_clients): SDI
    {
        return $iR->repoGuestClientsPostDraft((int) $status, $user_clients);
    }
}
