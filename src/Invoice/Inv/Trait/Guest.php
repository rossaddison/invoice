<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Auth\Permissions;
use App\Infrastructure\Persistence\User\User;

use App\Invoice\{
    Inv\InvGuestDeps,
    Inv\InvGuestFilter,
    Inv\InvRepository as IR,
};
use Yiisoft\{
    Data\Reader\DataReaderInterface as DRI,
    Data\Reader\SortableDataInterface as SDI,
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
        InvGuestDeps $d,
        InvGuestFilter $filter,
        #[RouteArgument('page')]
        string $page = '1',
        #[RouteArgument('status')]
        string $status = '0',
    ): Response {
        $page = $filter->page ?? $page;
        $sortString = $filter->sort ?? '-id';
        // Get the current user and determine from (Related logic: see Settings
        // ...User Account) whether they have been given either guest or admin
        // rights. These rights are unrelated to rbac and serve as a second
        // 'line of defense' to support role based admin control. Retrieve the
        // user from Yii-Demo's list of users in the User Table
        $user = $this->userService->getUser();
        if ($user instanceof User && (($user_id = $user->reqId()) > 0)) {
            // Use this user's id to see whether a user has been setup under
            // UserInv ie. yii-invoice's list of users
            $userInv = ($d->uiR->repoUserInvUserIdcount($user_id) > 0 ?
                $d->uiR->repoUserInvUserIdquery($user_id) : null);
            if (null !== $userInv && $user_id > 0 && $userInv->getActive()) {
                $userInvListLimit = $userInv->getListLimit();
                // Determine what clients have been allocated to this user
                // (Related logic: see Settings...User Account) by looking at
                // UserClient table eg. If the user is a guest-accountant, they
                // will have been allocated certain clients. A
                // user-quest-accountant will be allocated a series of clients
                // A user-guest-client will be allocated their client number by
                // the administrator so that they can view their invoices and
                // make payment
                $user_clients = $d->ucR->getAssignedToUser($user_id);
                if (!empty($user_clients)) {
                    $effectiveStatus = isset($filter->filterStatus)
                        && !empty($filter->filterStatus) ?
                            (int) $filter->filterStatus : (int) $status;
                    $invs = $this->invsStatusGuest($d->iR, $effectiveStatus,
                            $user_clients);
                    $preFilterInvs = $invs;
                    if (isset($filter->filterInvNumber)
                            && !empty($filter->filterInvNumber)) {
                        $invs = $d->iR->filterInvNumber($filter->filterInvNumber);
                    }
                    if (isset($filter->filterCreditInvNumber)
                            && !empty($filter->filterCreditInvNumber)) {
                        $invs = $d->iR->filterCreditInvNumber(
                            $filter->filterCreditInvNumber);
                    }
                    if (isset($filter->filterInvAmountTotal)
                            && !empty($filter->filterInvAmountTotal)) {
                        $invs = $d->iR->filterInvAmountTotal(
                            (float) $filter->filterInvAmountTotal);
                    }
                    if (isset($filter->filterInvAmountPaid)
                            && !empty($filter->filterInvAmountPaid)) {
                        $invs = $d->iR->filterInvAmountPaid(
                            (float) $filter->filterInvAmountPaid);
                    }
                    if (isset($filter->filterInvAmountBalance)
                            && !empty($filter->filterInvAmountBalance)) {
                        $invs = $d->iR->filterInvAmountBalance(
                                (float) $filter->filterInvAmountBalance);
                    }
                    if ((isset($filter->filterInvNumber)
                            && !empty($filter->filterInvNumber))
                       && (isset($filter->filterInvAmountTotal)
                               && !empty($filter->filterInvAmountTotal))) {
                        $invs = $d->iR->filterInvNumberAndInvAmountTotal(
                            $filter->filterInvNumber,
                                (float) $filter->filterInvAmountTotal);
                    }
                    if (isset($filter->filterClient) && !empty($filter->filterClient)) {
                        $invs = $d->iR->filterGuestClient($filter->filterClient);
                    }
                    $inv_statuses = $d->iR->getStatuses($this->translator);
                    $label = $d->iR->getSpecificStatusArrayLabel($status);
                    $bacsUnpaidInvs = $d->iR->repoUnpaidByClientIds($user_clients);
                    $dp = (int) $this->sR->getSetting('tax_rate_decimal_places');
                    $parameters = [
                        'alert' => $this->alert(),
                        'bacsPaymentService' => $d->bacsPaymentService,
                        'bacsUnpaidInvs' => $bacsUnpaidInvs,
                        'decimalPlaces' => $dp,
                        'modalBacsQuickPay' =>
                            $this->webViewRenderer->renderPartialAsString(
                                '_modal_bacs_quickpay', [
                                    'bacsPaymentService' => $d->bacsPaymentService,
                                    'bacsUnpaidInvs'     => $bacsUnpaidInvs,
                                    'decimalPlaces'      => $dp,
                        ]),
                        'optionsClientsDropDownFilter' =>
                        $this->optionsDataUserClientsFilter($d->ucR, $user_id),
                        'optionsInvNumberDropDownFilter' =>
                            $this->optionsDataInvNumberGuestFilter($preFilterInvs),
                        'optionsCreditInvNumberDropDownFilter' =>
                        $this->optionsDataCreditInvNumberGuestFilter(
                            $preFilterInvs, $d->iR),
                        'optionsStatusDropDownFilter' =>
                                $this->optionsDataStatusFilter($d->iR),
                        'iaR' => $d->iaR,
                        'iR' => $d->iR,
                        'irR' => $d->irR,
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
                        'sortOrder' => $filter->sort ?? '',
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
     * @psalm-return SDI&DRI<int, \App\Infrastructure\Persistence\Inv\Inv>
     */
    private function invsStatusGuest(IR $iR, mixed $status,
        array $user_clients): SDI
    {
        return $iR->repoGuestClientsPostDraft((int) $status, $user_clients);
    }
}
