<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Auth\Permissions;
use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\User\User;
use App\Infrastructure\Persistence\UserInv\UserInv;

use App\Invoice\{
    Client\ClientRepository as ClientR,
    Client\ClientService,
    Inv\InvGuestAccess,
    Inv\InvGuestDeps,
    Inv\InvGuestFilter,
    Inv\InvRepository as IR,
};
use Yiisoft\{
    Data\Reader\DataReaderInterface as DRI,
    Data\Reader\SortableDataInterface as SDI,
    Router\HydratorAttribute\RouteArgument,
    Router\UrlGeneratorInterface,
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
        $user = $this->userService->getUser();
        $user_id = ($user instanceof User) ? $user->reqId() : 0;
        if ($user_id <= 0) {
            return $this->webService->getNotFoundResponse();
        }
        $access = $this->resolveGuestAccess($d, $user_id);
        if (null === $access) {
            return $this->webService->getNotFoundResponse();
        }
        return $this->renderGuestView($d, $filter, $page, $status, $user_id, $access);
    }

    /**
     * Resolves what this logged-in user is allowed to see on inv/guest: a
     * HomeCare field worker (linked via Worker.user_id) bypasses the
     * UserClient gate entirely and is scoped by worker_id instead — see
     * InvRepository::repoWorkerVisible(). Everyone else keeps the existing
     * client-assignment gate.
     */
    private function resolveGuestAccess(InvGuestDeps $d, int $user_id): ?InvGuestAccess
    {
        $userInv = $d->uiR->repoUserInvUserIdcount($user_id) > 0
            ? $d->uiR->repoUserInvUserIdquery($user_id) : null;
        $worker = (null !== $userInv && $userInv->getActive())
            ? $d->wR->findByUserId($user_id) : null;
        if (null !== $userInv && null !== $worker) {
            return new InvGuestAccess($userInv, [], $worker);
        }
        $user_clients = (null !== $userInv && $userInv->getActive())
            ? $d->ucR->getAssignedToUser($user_id) : [];
        $this->flashGuestAccessWarnings($userInv, $user_clients);
        if (null === $userInv || !$userInv->getActive() || empty($user_clients)) {
            return null;
        }
        return new InvGuestAccess($userInv, $user_clients);
    }

    /**
     * Lets a signed-in guest print their own home-care QR code.
     * Related logic: see Route::get('/client_invoices/qr')
     */
    public function guestQrCode(
        ClientR $clientR,
        ClientService $clientService,
        UrlGeneratorInterface $urlGenerator,
        InvGuestDeps $d,
    ): Response {
        $client = $this->resolveGuestQrClient($clientR, $d);
        if (null === $client) {
            return $this->webService->getNotFoundResponse();
        }
        return $this->renderClientQrPrint($client, $clientService, $urlGenerator);
    }

    private function resolveGuestQrClient(ClientR $clientR, InvGuestDeps $d): ?Client
    {
        $user = $this->userService->getUser();
        $user_id = ($user instanceof User) ? $user->reqId() : 0;
        if ($user_id <= 0) {
            return null;
        }
        $userInv = $d->uiR->repoUserInvUserIdcount($user_id) > 0
            ? $d->uiR->repoUserInvUserIdquery($user_id) : null;
        $user_clients = (null !== $userInv && $userInv->getActive())
            ? $d->ucR->getAssignedToUser($user_id) : [];
        if (null === $userInv || !$userInv->getActive() || empty($user_clients)) {
            return null;
        }
        return $clientR->repoClientqueryOrig((int) $user_clients[0]);
    }

    private function renderClientQrPrint(
        Client $client,
        ClientService $clientService,
        UrlGeneratorInterface $urlGenerator,
    ): Response {
        $token = $clientService->getOrCreateQrToken($client);
        $scanUrl = $urlGenerator->generateAbsolute('public/homecare-scan', ['token' => $token]);
        return $this->webViewRenderer->renderPartial('//invoice/_shared/qr_print', [
            'client' => $client,
            'qrDataUri' => $clientService->renderQrDataUri($scanUrl),
        ]);
    }

    private function flashGuestAccessWarnings(?UserInv $userInv, array $user_clients): void
    {
        if (null === $userInv || !$userInv->getActive()) {
            $this->flashMessage('info', $this->translator->translate('user.inv.active.not'));
            return;
        }
        if (empty($user_clients)) {
            $this->flashMessage('warning', $this->translator->translate('user.clients.assigned.not'));
            $this->flashMessage('info', $this->translator->translate('user.inv.active.not'));
        }
    }

    private function renderGuestView(
        InvGuestDeps $d,
        InvGuestFilter $filter,
        string $page,
        string $status,
        int $user_id,
        InvGuestAccess $access,
    ): Response {
        $userInv = $access->userInv;
        $user_clients = $access->clients;
        $worker = $access->worker;
        $effectiveStatus = (isset($filter->filterStatus) && !empty($filter->filterStatus))
            ? (int) $filter->filterStatus : (int) $status;
        $invs = null !== $worker
            ? $d->iR->repoWorkerVisible($effectiveStatus, $worker->reqId())
            : $this->invsStatusGuest($d->iR, $effectiveStatus, $user_clients);
        $preFilterInvs = $invs;
        $invs = $this->applyGuestFilters($filter, $d->iR, $invs);
        $inv_statuses = $d->iR->getStatuses($this->translator);
        $label = $d->iR->getSpecificStatusArrayLabel($status);
        // Payment isn't relevant to a worker's job — see the worker branch
        // above and Permissions::VIEW_PAYMENT (excluded from the worker
        // RBAC role), which drives $viewPayment below.
        $bacsUnpaidInvs = null !== $worker ? [] : $d->iR->repoUnpaidByClientIds($user_clients);
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
                $this->optionsDataCreditInvNumberGuestFilter($preFilterInvs, $d->iR),
            'optionsStatusDropDownFilter' =>
                $this->optionsDataStatusFilter($d->iR),
            'iaR' => $d->iaR,
            'iR' => $d->iR,
            'irR' => $d->irR,
            'invs' => $invs,
            'label' => $label,
            // the guest will not have access to the pageSizeLimiter
            'viewInv' => $this->userService->hasPermission(Permissions::VIEW_INV),
            // false for the worker role (see resources/rbac/items.php) —
            // "not see payment, it is not relevant to him"
            'viewPayment' => $this->userService->hasPermission(Permissions::VIEW_PAYMENT),
            // update userinv with the user's listlimit preference
            'userInv' => $userInv,
            'userInvListLimit' => $userInv->getListLimit(),
            'defaultPageSizeOffsetPaginator' => $userInv->getListLimit() ?? 10,
            // numbered tiles between the arrows
            'maxNavLinkCount' => 10,
            'invStatuses' => $inv_statuses,
            'page' => (int) $page > 0 ? (int) $page : 1,
            // Clicking on a grid column sort hyperlink will
            // generate a url query_param eg. ?sort=
            'sortOrder' => $filter->sort ?? '',
            'sortString' => $filter->sort ?? '-id',
            'status' => $status,
        ];
        return $this->webViewRenderer->render('guest', $parameters);
    }

    private function applyGuestFilters(InvGuestFilter $filter, IR $iR, SDI $invs): SDI
    {
        if (isset($filter->filterInvNumber) && !empty($filter->filterInvNumber)) {
            $invs = $iR->filterInvNumber($filter->filterInvNumber);
        }
        if (isset($filter->filterCreditInvNumber) && !empty($filter->filterCreditInvNumber)) {
            $invs = $iR->filterCreditInvNumber($filter->filterCreditInvNumber);
        }
        if (isset($filter->filterInvAmountTotal) && !empty($filter->filterInvAmountTotal)) {
            $invs = $iR->filterInvAmountTotal((float) $filter->filterInvAmountTotal);
        }
        if (isset($filter->filterInvAmountPaid) && !empty($filter->filterInvAmountPaid)) {
            $invs = $iR->filterInvAmountPaid((float) $filter->filterInvAmountPaid);
        }
        if (isset($filter->filterInvAmountBalance) && !empty($filter->filterInvAmountBalance)) {
            $invs = $iR->filterInvAmountBalance((float) $filter->filterInvAmountBalance);
        }
        if ((isset($filter->filterInvNumber) && !empty($filter->filterInvNumber))
           && (isset($filter->filterInvAmountTotal) && !empty($filter->filterInvAmountTotal))) {
            $invs = $iR->filterInvNumberAndInvAmountTotal(
                $filter->filterInvNumber, (float) $filter->filterInvAmountTotal);
        }
        if (isset($filter->filterClient) && !empty($filter->filterClient)) {
            $invs = $iR->filterGuestClient($filter->filterClient);
        }
        return $invs;
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
