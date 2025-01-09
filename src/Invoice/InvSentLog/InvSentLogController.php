<?php

declare(strict_types=1);

namespace App\Invoice\InvSentLog;

use App\Invoice\Entity\InvSentLog;
use App\Invoice\InvSentLog\InvSentLogForm;
use App\Invoice\InvSentLog\InvSentLogService;
use App\Invoice\InvSentLog\InvSentLogRepository as ISLR;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\UserInv\UserInvRepository as UIR;
use App\Invoice\Traits\FlashMessage;
use App\User\UserService;
use App\User\User;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Input\Http\Attribute\Parameter\Query;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class InvSentLogController
{
    use FlashMessage;
    
    private Flash $flash;
    private Session $session;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private InvSentLogService $invsentlogService;
    private TranslatorInterface $translator;

    public function __construct(
        Session $session,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        InvSentLogService $invsentlogService,
        TranslatorInterface $translator
    ) {
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->userService = $userService;
        $this->viewRenderer = $viewRenderer;
        if ($this->userService->hasPermission('viewInv') && !$this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice/invsentlog')
                    ->withLayout('@views/layout/guest.php');
        }
        if ($this->userService->hasPermission('viewInv') && $this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice/invsentlog')
                    ->withLayout('@views/layout/invoice.php');
        }
        $this->webService = $webService;
        $this->invsentlogService = $invsentlogService;
        $this->translator = $translator;
    }


    /**
     * @return string
     */
    private function alert(): string
    {
        return $this->viewRenderer->renderPartialAsString(
            '//invoice/layout/alert',
            [
            'flash' => $this->flash,
            'errors' => [],
        ]
        );
    }

    /**
     * @param ISLR $islR
     * @param SettingRepository $settingRepository
     * @param UIR $uiR
     * @param string $page
     * @param string $queryPage
     * @param string $queryFilterInvNumber
     * @param string $queryFilterClient
     * @return Response
     */
    public function guest(
        ISLR $islR,
        SettingRepository $settingRepository,
        UIR $uiR,
        #[RouteArgument('page')] string $page = '1',
        #[Query('page')] string $queryPage = null,
        #[Query('filterInvNumber')] string $queryFilterInvNumber = null,
        #[Query('filterClient')] string $queryFilterClient = null,
    ): Response {
        $user = $this->userService->getUser();
        if ($user instanceof User && null !== $user->getId()) {
            $userId = $user->getId();
            // Use this user's id to see whether a user has been setup under UserInv ie. yii-invoice's list of users
            $userinv = ($uiR->repoUserInvUserIdcount((string) $userId) > 0 ? $uiR->repoUserInvUserIdquery((string) $userId) : null);
            if (null !== $userinv && null !== $userId) {
                $userInvListLimit = $userinv->getListLimit();
                $invsentlogs = $islR->withUser($userId);
                $finalPage = $queryPage ?? $page;
                /** @psalm-var positive-int $currentPageNeverZero */
                $currentPageNeverZero = (int)$finalPage > 0 ? (int)$finalPage : 1;
                if (isset($queryFilterInvNumber) && !empty($queryFilterInvNumber)) {
                    $invsentlogs = $islR->filterInvNumber($queryFilterInvNumber);
                }
                if (isset($queryFilterClient) && !empty($queryFilterClient)) {
                    $invsentlogs = $islR->filterClient($queryFilterClient);
                }
                if ((isset($queryFilterInvNumber) && !empty($queryFilterInvNumber))
                && ((isset($queryFilterClient) && !empty($queryFilterClient)))) {
                    $invsentlogs = $islR->filterInvNumberWithClient($queryFilterInvNumber, $queryFilterClient);
                }
                $paginator = (new OffsetPaginator($invsentlogs))
                ->withPageSize($userInvListLimit > 0 ? $userInvListLimit : 10)
                ->withCurrentPage($currentPageNeverZero)
                ->withToken(PageToken::next($finalPage));
                $parameters = [
                    'paginator' => $paginator,
                    'alert' => $this->alert(),
                    'viewInv' => $this->userService->hasPermission('viewInv'),
                    'userInv' => $userinv,
                    'defaultPageSizeOffsetPaginator' => null !== $userinv->getListLimit() ? $userinv->getListLimit() : 10,
                    'optionsDataGuestInvNumberDropDownFilter' => $this->optionsDataGuestInvNumberFilter($islR, (int)$userId),
                    // Get all the clients that have been assigned to this user
                    'optionsDataGuestClientDropDownFilter' => $this->optionsDataGuestClientsFilter($islR, $userId),
                ];
                return $this->viewRenderer->render('guest', $parameters);
            }
        }
        return $this->webService->getNotFoundResponse();
    }

    /**
     *
     * @param ISLR $islR
     * @param SettingRepository $settingRepository
     * @param string $page
     * @param string $queryPage
     * @param string $queryFilterInvNumber
     * @param string $queryFilterClientId
     * @return Response
     */
    public function index(
        ISLR $islR,
        SettingRepository $settingRepository,
        #[RouteArgument('page')] string $page = '1',
        #[Query('page')] string $queryPage = null,
        #[Query('filterInvNumber')] string $queryFilterInvNumber = null,
        #[Query('filterClient')] string $queryFilterClientId = null,
    ): Response {
        $invsentlogs = $islR->findAllPreloaded();
        $finalPage = $queryPage ?? $page;
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = (int)$finalPage > 0 ? (int)$finalPage : 1;
        if (isset($queryFilterInvNumber) && !empty($queryFilterInvNumber)) {
            $invsentlogs = $islR->filterInvNumber($queryFilterInvNumber);
        }
        if (isset($queryFilterClientId) && !empty($queryFilterClientId)) {
            $invsentlogs = $islR->filterClient($queryFilterClientId);
        }
        if ((isset($queryFilterInvNumber) && !empty($queryFilterInvNumber))
        && ((isset($queryFilterClientId) && !empty($queryFilterClientId)))) {
            $invsentlogs = $islR->filterInvNumberWithClient($queryFilterInvNumber, $queryFilterClientId);
        }
        $paginator = (new OffsetPaginator($invsentlogs))
        ->withPageSize($settingRepository->positiveListLimit())
        ->withCurrentPage($currentPageNeverZero)
        ->withToken(PageToken::next($finalPage));
        $parameters = [
            'paginator' => $paginator,
            'alert' => $this->alert(),
            'defaultPageSizeOffsetPaginator' => $settingRepository->getSetting('default_list_limit')
                                                    ? (int)$settingRepository->getSetting('default_list_limit') : 1,
            'optionsDataInvNumberDropDownFilter' => $this->optionsDataInvNumberFilter($islR),
            'optionsDataClientsDropDownFilter' => $this->optionsDataClientsFilter($islR),
        ];
        return $this->viewRenderer->render('index', $parameters);
    }

    //For rbac refer to AccessChecker

    /**
     * @param ISLR $islR
     * @param int $id
     * @return InvSentLog|null
     */
    private function invsentlog(ISLR $islR, int $id): InvSentLog|null
    {
        if ($id) {
            /**
             * @var InvSentLog $invsentlog
             */
            $invsentlog = $islR->repoInvSentLogLoadedquery((string)$id);
            return $invsentlog;
        }
        return null;
    }

    /**
     * @param ISLR $islR
     * @param SettingRepository $settingRepository
     * @param int id
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function view(ISLR $islR, #[RouteArgument('id')] int $id): \Yiisoft\DataResponse\DataResponse|Response
    {
        $invsentlog = $this->invsentlog($islR, $id);
        if ($invsentlog) {
            $form = new InvSentLogForm($invsentlog);
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'actionName' => 'invsentlog/view',
                'actionArguments' => ['id' => $id],
                'form' => $form
            ];
            return $this->viewRenderer->render('view', $parameters);
        }
        return $this->webService->getRedirectResponse('invsentlog/index');
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
            if (null !== $invNumber) {
                if (!in_array($invNumber, $optionsDataInvNumbers)) {
                    $optionsDataInvNumbers[$invNumber] = $invNumber;
                }
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
            $clientFullName = $invsentlog->getInv()?->getClient()?->getClient_full_name();
            $clientId = $invsentlog->getInv()?->getClient()?->getClient_id();
            if (null !== $clientFullName && null !== $clientId) {
                if (!in_array($clientFullName, $optionsDataClients)) {
                    $optionsDataClients[$clientId] = $clientFullName;
                }
            }
        }
        return $optionsDataClients;
    }

    /**
     * Retrieve all the invoices sent to this user
     * @param ISLR $islR
     * @return array
     */
    public function optionsDataGuestInvNumberFilter(ISLR $islR, int $user_id): array
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
                $invUserId = $invSentLog->getInv()?->getUser()->getId();
                if (null !== $invUserId) {
                    if ($user_id == (int)$invUserId) {
                        if (!in_array($invNumber, $optionsDataGuestInvNumbers)) {
                            $optionsDataGuestInvNumbers[$invNumber] = $invNumber;
                        }
                    }
                }
            }
        }
        return $optionsDataGuestInvNumbers;
    }

    /**
     *
     * @param ISLR $islR
     * @param string $userId
     * @return array
     */
    public function optionsDataGuestClientsFilter(ISLR $islR, string $userId): array
    {
        $optionsDataGuestClientsOfUser = [];
        // Get all the invoices sent to this user
        // This user may have more than one client
        $invsentlogs = $islR->withUser($userId);
        /**
         * @var InvSentLog $invSentLog
         */
        foreach ($invsentlogs as $invSentLog) {
            $invClientFullName = $invSentLog->getInv()?->getClient()?->getClient_full_name();
            $invClientId = $invSentLog->getInv()?->getClient()?->getClient_id();
            $invUserId = $invSentLog->getInv()?->getUser()?->getId();
            if (null !== $invUserId && null !== $invClientId) {
                if ((null !== $invClientFullName) && ($userId == (int)$invUserId)) {
                    if (!in_array($invClientFullName, $optionsDataGuestClientsOfUser)) {
                        $optionsDataGuestClientsOfUser[$invClientId] = $invClientFullName;
                    }
                }
            }
        }
        return $optionsDataGuestClientsOfUser;
    }
}
