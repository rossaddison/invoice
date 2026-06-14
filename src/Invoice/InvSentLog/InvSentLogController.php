<?php

declare(strict_types=1);

namespace App\Invoice\InvSentLog;

use App\Auth\Permissions;
use App\Invoice\BaseController;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvSentLog\InvSentLog;
use App\Invoice\InvSentLog\InvSentLogRepository as ISLR;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\UserClient\UserClientRepository as UCR;
use App\Invoice\UserInv\UserInvRepository as UIR;
use App\User\UserService;
use App\Infrastructure\Persistence\User\User;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Input\Http\Attribute\Parameter\Query;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class InvSentLogController extends BaseController
{
    protected string $controllerName = 'invoice/invsentlog';

    public function __construct(
        private InvSentLogService $invsentlogService,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator,
                                        $webViewRenderer, $session, $sR, $flash);
        $this->invsentlogService = $invsentlogService;
    }

    /**
     * @param ISLR $islR
     * @param UIR $uiR
     * @param string $page
     * @param string $queryPage
     * @param string $queryFilterInvNumber
     * @param int $queryFilterClientId
     * @return Response
     */
    public function guest(
        ISLR $islR,
        UIR $uiR,
        #[RouteArgument('page')]
        string $page = '1',
        #[Query('page')]
        ?string $queryPage = null,
        #[Query('filterInvNumber')]
        ?string $queryFilterInvNumber = null,
        #[Query('filterClient')]
        ?int $queryFilterClientId = null,
    ): Response {
        $user = $this->userService->getUser();
        if ($user instanceof User && (($userId = $user->reqId()) > 0)) {
            // Use this user's id to see whether a user has been setup under
            //  UserInv ie. yii-invoice's list of users
            $userinv = ($uiR->repoUserInvUserIdcount($userId) > 0 ?
                    $uiR->repoUserInvUserIdquery($userId) : null);
            if (null!==$userinv && $userinv->getActive()) {
                $userInvListLimit = $userinv->getListLimit();
                $invsentlogs = $islR->withUser($userId);
                $finalPage = $queryPage ?? $page;
                /** @psalm-var positive-int $currentPageNeverZero */
                $currentPageNeverZero = (int) $finalPage > 0 ? (int) $finalPage : 1;
                if (isset($queryFilterInvNumber) && !empty($queryFilterInvNumber)) {
                    $invsentlogs = $islR->filterInvNumber($queryFilterInvNumber);
                }
                if (isset($queryFilterClientId) && ($queryFilterClientId > 0)) {
                    $invsentlogs = $islR->filterClient($queryFilterClientId);
                }
                if ((isset($queryFilterInvNumber) && !empty($queryFilterInvNumber))
                && (isset($queryFilterClientId) && ($queryFilterClientId > 0))) {
                    $invsentlogs = $islR->filterInvNumberWithClient(
                                    $queryFilterInvNumber, $queryFilterClientId);
                }
                $paginator = (new OffsetPaginator($invsentlogs))
                ->withPageSize($userInvListLimit > 0 ? $userInvListLimit : 10)
                ->withCurrentPage($currentPageNeverZero)
                ->withToken(PageToken::next($finalPage));
                $parameters = [
                    'paginator' => $paginator,
                    'alert' => $this->alert(),
                    'viewInv' =>
                        $this->userService->hasPermission(Permissions::VIEW_INV),
                    'userInv' => $userinv,
                    'defaultPageSizeOffsetPaginator' =>
                                                $userinv->getListLimit() ?? 10,
                    'optionsDataGuestInvNumberDropDownFilter' =>
                    $this->optionsDataGuestInvNumberFilter($islR, $userId),
                    // Get all the clients that have been assigned to this user
                    'optionsDataGuestClientDropDownFilter' =>
                        $this->optionsDataGuestClientsFilter($islR, $userId),
                ];
                return $this->webViewRenderer->render('guest', $parameters);
            }
            $this->flashMessage('info',
                        $this->translator->translate('user.inv.active.not'));
            return $this->webService->getNotFoundResponse();
        }
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @param ISLR $islR
     * @param string $page
     * @param string $queryPage
     * @param string $queryFilterInvNumber
     * @param int $queryFilterClientId
     * @return Response
     */
    public function index(
        ISLR $islR,
        #[RouteArgument('page')]
        string $page = '1',
        #[Query('page')]
        ?string $queryPage = null,
        #[Query('filterInvNumber')]
        ?string $queryFilterInvNumber = null,
        #[Query('filterClient')]
        ?int $queryFilterClientId = null,
    ): Response {
        $invsentlogs = $islR->findAllPreloaded();
        $finalPage = $queryPage ?? $page;
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = (int) $finalPage > 0 ? (int) $finalPage : 1;
        if (isset($queryFilterInvNumber) && !empty($queryFilterInvNumber)) {
            $invsentlogs = $islR->filterInvNumber($queryFilterInvNumber);
        }
        if (isset($queryFilterClientId) && ($queryFilterClientId > 0)) {
            $invsentlogs = $islR->filterClient($queryFilterClientId);
        }
        if ((isset($queryFilterInvNumber) && !empty($queryFilterInvNumber))
        && (isset($queryFilterClientId) && ($queryFilterClientId > 0))) {
            $invsentlogs =
            $islR->filterInvNumberWithClient(
                                    $queryFilterInvNumber, $queryFilterClientId);
        }
        $paginator = (new OffsetPaginator($invsentlogs))
        ->withPageSize($this->sR->positiveListLimit())
        ->withCurrentPage($currentPageNeverZero)
        ->withToken(PageToken::next($finalPage));
        $parameters = [
            'paginator' => $paginator,
            'alert' => $this->alert(),
            'defaultPageSizeOffsetPaginator' =>
                $this->sR->getSetting('default_list_limit') ?
                (int) $this->sR->getSetting('default_list_limit') : 1,
            'optionsDataInvNumberDropDownFilter' =>
                                        $this->optionsDataInvNumberFilter($islR),
            'optionsDataClientsDropDownFilter' =>
                                        $this->optionsDataClientsFilter($islR),
        ];
        return $this->webViewRenderer->render('index', $parameters);
    }

    /**
     * @param ISLR $islR
     * @param int $id
     * @return InvSentLog|null
     */
    private function invsentlog(ISLR $islR, int $id): ?InvSentLog
    {
        if ($id) {
            /**
             * @var InvSentLog $invsentlog
             */
            return $islR->repoInvSentLogLoadedquery($id);
        }
        return null;
    }

    /**
     * @param int id
     * @param ISLR $islR
     * @param UCR $ucR
     * @param UIR $uiR
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function view(#[RouteArgument('id')] int $id, ISLR $islR,
        UCR $ucR, UIR $uiR): \Psr\Http\Message\ResponseInterface
    {
        $invsentlog = $this->invsentlog($islR, $id);
        if ($invsentlog) {
            $form = InvSentLogForm::show($invsentlog);
            $parameters = [
                'title' => $this->translator->translate('view'),
                'actionName' => 'invsentlog/view',
                'actionArguments' => ['id' => $id],
                'form' => $form,
            ];
            $inv = $invsentlog->getInv();
            if (null!==$inv) {
                if ($this->rbacObserver($inv, $ucR, $uiR)) {
                    return $this->webViewRenderer->render('view', $parameters);
                }
                if ($this->rbacAdmin()) {
                    return $this->webViewRenderer->render('view', $parameters);
                }
                if ($this->rbacAccountant()) {
                    return $this->webViewRenderer->render('view', $parameters);
                }
            }
        }
        return $this->webService->getRedirectResponse('invsentlog/index');
    }

    private function rbacAccountant() : bool {
        return $this->userService->hasPermission(Permissions::VIEW_INV)
            && $this->userService->hasPermission(Permissions::VIEW_PAYMENT)
            && $this->userService->hasPermission(Permissions::EDIT_PAYMENT);
    }

    private function rbacAdmin() : bool {
        return $this->userService->hasPermission(Permissions::VIEW_INV)
            && $this->userService->hasPermission(Permissions::EDIT_INV);
    }

    /**
     * Purpose:
     * Prevent browser manipulation and ensure that views are only accessible
     * to users 1. with the observer role's VIEW_INV permission and 2. supervise a
     * client requested invoice and are an active current user for these client's
     * invoices.
     * @param Inv $inv
     * @param UCR $ucR
     * @param UIR $uiR
     * @return bool
     */
    private function rbacObserver(Inv $inv, UCR $ucR, UIR $uiR) : bool {
        $statusId = $inv->reqStatusId();
        // has observer role
        if ($this->userService->hasPermission(Permissions::VIEW_INV)
            && !($this->userService->hasPermission(Permissions::EDIT_INV))
            // the invoice  is not a draft i.e. has been sent
            && $statusId !== 1
            // the invoice is intended for the current user
            && ($inv->reqUserId() === $this->userService->getUser()?->reqId())
            // the invoice client is associated with the above user
            // the observer user may be paying for more than one client
            && ($ucR->repoUserClientqueryCount($inv->reqUserId(),
                                            $inv->reqClientId()) > 0)) {
            $userInv = $uiR->repoUserInvUserIdquery($statusId);
            // the current observer user is active
            if (null !== $userInv && $userInv->getActive()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param ISLR $islR
     * @return array
     */
    public function optionsDataInvNumberFilter(ISLR $islR): array
    {
        $optionsDataInvNumbers = [];
        // Get all the invoices emailed
        $invsentlogs = $islR->findAllPreloaded();
        /**
         * @var InvSentLog $invsentlog
         */
        foreach ($invsentlogs as $invsentlog) {
            $invNumber = $invsentlog->getInv()?->getNumber();
            if (null !== $invNumber && !in_array($invNumber, $optionsDataInvNumbers)) {
                $optionsDataInvNumbers[$invNumber] = $invNumber;
            }
        }
        return $optionsDataInvNumbers;
    }

    /**
     * Get all the clients that have been emailed
     * @param ISLR $islR
     * @return array
     */
    public function optionsDataClientsFilter(ISLR $islR): array
    {
        $optionsDataClients = [];
        $invsentlogs = $islR->findAllPreloaded();
        /**
         * @var InvSentLog $invsentlog
         */
        foreach ($invsentlogs as $invsentlog) {
            $clientFullName =
                    $invsentlog->getInv()?->getClient()?->getClientFullName();
            $clientId = $invsentlog->getInv()?->getClient()?->reqId();
            if (null !== $clientFullName && null !== $clientId
                && !in_array($clientFullName, $optionsDataClients)) {
                $optionsDataClients[$clientId] = $clientFullName;
            }
        }
        return $optionsDataClients;
    }

    /**
     * Retrieve all the invoices sent to this user
     * @param ISLR $islR
     * @return array
     */
    public function optionsDataGuestInvNumberFilter(
                                                ISLR $islR, int $user_id): array
    {
        $optionsDataGuestInvNumbers = [];
        // Get all the invoices sent to this user
        // This user may have more than one client
        $invsentlogs = $islR->findAllPreloaded();
        /**
         * @var InvSentLog $invSentLog
         */
        foreach ($invsentlogs as $invSentLog) {
            $invNumber = $invSentLog->getInv()?->getNumber();
            if (null !== $invNumber) {
                $invUserId = $invSentLog->getInv()?->getUser()->reqId();
                if ($user_id == $invUserId && !in_array($invNumber, $optionsDataGuestInvNumbers)) {
                    $optionsDataGuestInvNumbers[$invNumber] = $invNumber;
                }
            }
        }
        return $optionsDataGuestInvNumbers;
    }

    /**
     * @param ISLR $islR
     * @param int $userId
     * @return array
     */
    private function optionsDataGuestClientsFilter(ISLR $islR, int $userId): array
    {
        $optionsDataGuestClientsOfUser = [];
        // Get all the invoices sent to this user
        // This user may have more than one client
        $invsentlogs = $islR->withUser($userId);
        /**
         * @var InvSentLog $invSentLog
         */
        foreach ($invsentlogs as $invSentLog) {
            $invClientFullName =
                    $invSentLog->getInv()?->getClient()?->getClientFullName();
            $invClientId = $invSentLog->getInv()?->getClient()?->reqId();
            $invUserId = $invSentLog->getInv()?->getUser()?->reqId();
            if (null !== $invUserId && null !== $invClientId
                && null !== $invClientFullName
                && $userId == $invUserId
                && !in_array($invClientFullName, $optionsDataGuestClientsOfUser)) {
                $optionsDataGuestClientsOfUser[$invClientId] =
                        $invClientFullName;
            }
        }
        return $optionsDataGuestClientsOfUser;
    }
}
