<?php

declare(strict_types=1);

namespace App\Invoice\InvAllowanceCharge;

use App\Auth\Permissions;
use App\Invoice\BaseController;
use App\Invoice\Entity\InvAllowanceCharge;
use App\Invoice\AllowanceCharge\AllowanceChargeRepository;
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository as aciR;
use App\Invoice\Setting\SettingRepository as sR;
use App\User\UserService;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Http\Method;
use Yiisoft\Input\Http\Attribute\Parameter\Query;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;
use Exception;

final class InvAllowanceChargeController extends BaseController
{
    protected string $controllerName = 'invoice/invallowancecharge';

    public function __construct(
        private InvAllowanceChargeService $invallowancechargeService,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR, $flash);
        $this->invallowancechargeService = $invallowancechargeService;
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param AllowanceChargeRepository $allowanceChargeRepository
     * @return Response
     */
    public function add(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        AllowanceChargeRepository $allowanceChargeRepository,
    ): Response {
        $invAllowanceCharge = new InvAllowanceCharge();
        $inv_id = $currentRoute->getArgument('inv_id');
        $form = new InvAllowanceChargeForm($invAllowanceCharge, (int) $inv_id);
        $parameters = [
            'title' => $this->translator->translate('add'),
            'actionName' => 'invallowancecharge/add',
            'actionArguments' => ['inv_id' => $inv_id],
            'errors' => [],
            'form' => $form,
            'optionsDataAllowanceCharges' => $allowanceChargeRepository->optionsDataAllowanceCharges(),
        ];

        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody() ?? [];
            if (is_array($body)) {
                $body['inv_id'] = $inv_id;
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    $this->invallowancechargeService->saveInvAllowanceCharge($invAllowanceCharge, $body);
                    // Redirect to the inv / view after adding an overall allowance or charge
                    // The inv view will automatically recalculate the totals using NumberHelper calculate_inv
                    return $this->webService->getRedirectResponse('inv/view', ['id' => $inv_id]);
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            } // is_array
        }
        return $this->viewRenderer->render('modal_add_allowance_charge_form', $parameters);
    }

    /**
     * @param InvAllowanceChargeRepository $invallowancechargeRepository
     * @return Response
     */
    public function index(
        aciR $aciR,
        #[RouteArgument('_language')]
        string $_language,
        #[RouteArgument('page')]
        string $page = '1',
        #[Query('page')]
        string $queryPage = null,
        #[Query('sort')]
        string $querySort = null,
        #[Query('filterInvNumber')]
        string $queryFilterInvNumber = null,
        #[Query('filterReasonCode')]
        string $queryFilterReasonCode = null,
        #[Query('filterReason')]
        string $queryFilterReason = null,
    ): Response {
        // If the language dropdown changes
        $this->session->set('_language', $_language);
        $invAllowanceCharges = $aciR->findAllPreloaded();
        if (isset($queryFilterReasonCode) && !empty($queryFilterReasonCode)) {
            $invAllowanceCharges = $aciR->repoReasonCodeQuery($queryFilterReasonCode);
        }
        if (isset($queryFilterReason) && !empty($queryFilterReason)) {
            $invAllowanceCharges = $aciR->repoReasonQuery($queryFilterReason);
        }
        if (isset($queryFilterInvNumber) && !empty($queryFilterInvNumber)) {
            $invAllowanceCharges = $aciR->repoInvNumberQuery($queryFilterInvNumber);
        }
        $page = $queryPage ?? $page;
        $parameters = [
            'defaultPageSizeOffsetPaginator' => (int) $this->sR->getSetting('default_list_limit') ?: 1,
            'invAllowanceCharges' => $invAllowanceCharges,
            'optionsDataInvNumberDropDownFilter' => $this->optionsDataInvNumberFilter($aciR),
            'page' => (int) $page > 0 ? (int) $page : 1,
            'sortString' => $querySort ?? '-id',
            'alert' => $this->alert(),
        ];
        return $this->viewRenderer->render('index', $parameters);
    }

    /**
     * @param aciR $aciR
     * @return array
     */
    public function optionsDataInvNumberFilter(aciR $aciR): array
    {
        $optionsDataInvNumbers = [];
        $acis = $aciR->findAllPreloaded();
        /**
         * @var InvAllowanceCharge $invAllowanceCharge
         */
        foreach ($acis as $invAllowanceCharge) {
            $invNumber = $invAllowanceCharge->getInv()?->getNumber();
            if (null !== $invNumber) {
                if (!in_array($invNumber, $optionsDataInvNumbers)) {
                    $optionsDataInvNumbers[$invNumber] = $invNumber;
                }
            }
        }
        return $optionsDataInvNumbers;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param InvAllowanceChargeRepository $invAllowanceChargeRepository
     * @return Response
     */
    public function delete(
        CurrentRoute $currentRoute,
        InvAllowanceChargeRepository $invAllowanceChargeRepository,
    ): Response {
        try {
            $invAllowanceCharge = $this->invallowancecharge($currentRoute, $invAllowanceChargeRepository);
            if ($invAllowanceCharge) {
                $invId = $invAllowanceCharge->getId();
                $this->invallowancechargeService->deleteInvAllowanceCharge($invAllowanceCharge);
                $this->flashMessage('info', $this->translator->translate('record.successfully.deleted'));
                return $this->webService->getRedirectResponse('inv/view', ['id' => $invId]);
            }
            return $this->webService->getRedirectResponse('invallowancecharge/index');
        } catch (Exception $e) {
            $this->flashMessage('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('invallowancecharge/index');
        }
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param AllowanceChargeRepository $allowanceChargeRepository
     * @param InvAllowanceChargeRepository $invAllowanceChargeRepository
     * @param InvAmountRepository $iaR
     * @return Response
     */
    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        AllowanceChargeRepository $allowanceChargeRepository,
        InvAllowanceChargeRepository $invAllowanceChargeRepository,
    ): Response {
        $invAllowanceCharge = $this->invallowancecharge($currentRoute, $invAllowanceChargeRepository);
        if ($invAllowanceCharge) {
            $inv_id = $invAllowanceCharge->getInv_id();
            $form = new InvAllowanceChargeForm($invAllowanceCharge, (int) $inv_id);
            $parameters = [
                'title' => $this->translator->translate('allowance.or.charge'),
                'actionName' => 'invallowancecharge/edit',
                'actionArguments' => ['id' => $invAllowanceCharge->getId()],
                'errors' => [],
                'form' => $form,
                'optionsDataAllowanceCharges' => $allowanceChargeRepository->optionsDataAllowanceCharges(),
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    if (is_array($body)) {
                        $this->invallowancechargeService->saveInvAllowanceCharge($invAllowanceCharge, $body);
                        return $this->webService->getRedirectResponse('invallowancecharge/index');
                    }
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('invallowancecharge/index');
    }

    //For rbac refer to AccessChecker

    /**
     * @param CurrentRoute $currentRoute
     * @param InvAllowanceChargeRepository $invallowancechargeRepository
     * @return InvAllowanceCharge|null
     */
    private function invallowancecharge(CurrentRoute $currentRoute, InvAllowanceChargeRepository $invallowancechargeRepository): ?InvAllowanceCharge
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $invallowancechargeRepository->repoInvAllowanceChargeLoadedquery($id);
        }
        return null;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function invallowancecharges(InvAllowanceChargeRepository $invallowancechargeRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $invallowancechargeRepository->findAllPreloaded();
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param InvAllowanceChargeRepository $invallowancechargeRepository
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function view(
        CurrentRoute $currentRoute,
        InvAllowanceChargeRepository $invallowancechargeRepository,
        AllowanceChargeRepository $allowanceChargeRepository,
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $invAllowanceCharge = $this->invallowancecharge($currentRoute, $invallowancechargeRepository);
        if ($invAllowanceCharge) {
            $inv_id = $invAllowanceCharge->getInv_id();
            $form = new InvAllowanceChargeForm($invAllowanceCharge, (int) $inv_id);
            $parameters = [
                'title' => $this->translator->translate('view'),
                'actionName' => 'invallowancecharge/view',
                'actionArguments' => ['id' => $invAllowanceCharge->getId()],
                'form' => $form,
                'optionsDataAllowanceCharges' => $allowanceChargeRepository->optionsDataAllowanceCharges(),
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('invallowancecharge/index');
    }
}
