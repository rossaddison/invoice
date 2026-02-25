<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrderAllowanceCharge;

use App\Invoice\BaseController;
use App\Invoice\Entity\SalesOrderAllowanceCharge;
use App\Invoice\AllowanceCharge\AllowanceChargeRepository;
use App\Invoice\SalesOrderAllowanceCharge\SalesOrderAllowanceChargeRepository
    as acsoR;
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
use Yiisoft\Yii\View\Renderer\WebViewRenderer;
use Exception;

final class SalesOrderAllowanceChargeController extends BaseController
{
    protected string $controllerName = 'invoice/saleorderallowancecharge';

    public function __construct(
        private SalesOrderAllowanceChargeService $soacService,
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
        $this->soacService = $soacService;
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
        $salesorderAllowanceCharge = new SalesOrderAllowanceCharge();
        $salesorder_id = $currentRoute->getArgument('salesorder_id');
        $form = new SalesOrderAllowanceChargeForm($salesorderAllowanceCharge,
                (int) $salesorder_id);
        $parameters = [
            'title' => $this->translator->translate('add'),
            'actionName' => 'salesorderallowancecharge/add',
            'actionArguments' => ['salesorder_id' => $salesorder_id],
            'errors' => [],
            'form' => $form,
            'optionsDataAllowanceCharges' =>
                $allowanceChargeRepository->optionsDataAllowanceCharges(),
        ];

        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody() ?? [];
            if (is_array($body)) {
                $body['salesorder_id'] = $salesorder_id;
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    $this->soacService->saveSalesOrderAllowanceCharge(
                        $salesorderAllowanceCharge, $body);
                    return $this->webService->getRedirectResponse(
                            'salesorder/view',
                        ['id' => $salesorder_id]);
                }
                $parameters['errors'] =
                $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            } // is_array
        }
        return $this->webViewRenderer->render('modal_add_allowance_charge_form',
            $parameters);
    }

    public function index(
        acsoR $acsoR,
        #[RouteArgument('_language')]
        string $_language,
        #[RouteArgument('page')]
        string $page = '1',
        #[Query('page')]
        ?string $queryPage = null,
        #[Query('sort')]
        ?string $querySort = null,
        #[Query('filterSalesorderNumber')]
        ?string $queryFilterSalesorderNumber = null,
        #[Query('filterReasonCode')]
        ?string $queryFilterReasonCode = null,
        #[Query('filterReason')]
        ?string $queryFilterReason = null,
    ): Response {
        // If the language dropdown changes
        $this->session->set('_language', $_language);
        $salesorderAllowanceCharges = $acsoR->findAllPreloaded();
        if (isset($queryFilterReasonCode) && !empty($queryFilterReasonCode)) {
            $salesorderAllowanceCharges =
                $acsoR->repoReasonCodeQuery($queryFilterReasonCode);
        }
        if (isset($queryFilterReason) && !empty($queryFilterReason)) {
            $salesorderAllowanceCharges =
                $acsoR->repoReasonQuery($queryFilterReason);
        }
        if (isset($queryFilterSalesorderNumber)
                && !empty($queryFilterSalesorderNumber)) {
            $salesorderAllowanceCharges =
                $acsoR->repoSalesorderNumberQuery($queryFilterSalesorderNumber);
        }
        $page = $queryPage ?? $page;
        $parameters = [
            'defaultPageSizeOffsetPaginator' =>
                (int) $this->sR->getSetting('default_list_limit') ?: 1,
            'salesorderAllowanceCharges' => $salesorderAllowanceCharges,
            'optionsDataSalesorderNumberDropDownFilter' =>
                $this->optionsDataSalesOrderNumberFilter($acsoR),
            'page' => (int) $page > 0 ? (int) $page : 1,
            'sortString' => $querySort ?? '-id',
            'alert' => $this->alert(),
        ];
        return $this->webViewRenderer->render('index', $parameters);
    }

    /**
     * @param acsoR $acsoR
     * @return array
     */
    public function optionsDataSalesorderNumberFilter(acsoR $acsoR): array
    {
        $optionsDataSalesOrderNumbers = [];
        $acsos = $acsoR->findAllPreloaded();
        /**
         * @var SalesOrderAllowanceCharge $salesorderAllowanceCharge
         */
        foreach ($acsos as $salesorderAllowanceCharge) {
            $salesorderNumber =
                    $salesorderAllowanceCharge->getSalesOrder()?->getNumber();
            if (null !== $salesorderNumber) {
                if (!in_array($salesorderNumber,
                        $optionsDataSalesOrderNumbers)) {
                    $optionsDataSalesOrderNumbers[$salesorderNumber] =
                            $salesorderNumber;
                }
            }
        }
        return $optionsDataSalesOrderNumbers;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param acsoR $acsoR
     * @return Response
     */
    public function delete(
        CurrentRoute $currentRoute,
        acsoR $acsoR,
    ): Response {
        try {
            $salesorderAllowanceCharge =
                $this->salesorderallowancecharge($currentRoute, $acsoR);
            if ($salesorderAllowanceCharge) {
                $salesorderId = $salesorderAllowanceCharge->getId();
                $this->soacService->deleteSalesorderAllowanceCharge(
                    $salesorderAllowanceCharge);
                $this->flashMessage('info', $this->translator->translate(
                    'record.successfully.deleted'));
                return $this->webService->getRedirectResponse('salesorder/view',
                    ['id' => $salesorderId]);
            }
            return $this->webService->getRedirectResponse(
                'salesorderallowancecharge/index');
        } catch (Exception $e) {
            $this->flashMessage('danger', $e->getMessage());
            return $this->webService->getRedirectResponse(
                'salesorderallowancecharge/index');
        }
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param AllowanceChargeRepository $allowanceChargeRepository
     * @param acsoR $acsoR
     * @return Response
     */
    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        AllowanceChargeRepository $allowanceChargeRepository,
        acsoR $acsoR,
    ): Response {
        $salesorderAllowanceCharge = $this->salesorderallowancecharge(
                $currentRoute, $acsoR);
        if ($salesorderAllowanceCharge) {
            $salesorder_id = $salesorderAllowanceCharge->getSales_order_id();
            $form = new SalesOrderAllowanceChargeForm($salesorderAllowanceCharge,
                (int) $salesorder_id);
            $parameters = [
                'title' => $this->translator->translate('allowance.or.charge'),
                'actionName' => 'salesorderallowancecharge/edit',
                'actionArguments' =>
                    ['id' => $salesorderAllowanceCharge->getId()],
                'errors' => [],
                'form' => $form,
                'optionsDataAllowanceCharges' =>
                    $allowanceChargeRepository->optionsDataAllowanceCharges(),
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    if (is_array($body)) {
                        $this->soacService->saveSalesOrderAllowanceCharge(
                            $salesorderAllowanceCharge, $body);
                        return $this->webService->getRedirectResponse(
                            'salesorderallowancecharge/index');
                    }
                }
                $parameters['errors'] =
                $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->webViewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse(
            'salesorderallowancecharge/index');
    }

    //For rbac refer to AccessChecker

    /**
     * @param CurrentRoute $currentRoute
     * @param SalesOrderAllowanceChargeRepository $soacRepository
     * @return SalesOrderAllowanceCharge|null
     */
    private function salesorderallowancecharge(CurrentRoute $currentRoute,
        SalesOrderAllowanceChargeRepository $soacRepository):
            ?SalesOrderAllowanceCharge
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $soacRepository->repoSalesOrderAllowanceChargeLoadedquery($id);
        }
        return null;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function salesorderallowancecharges(acsoR $acsoR):
        \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $acsoR->findAllPreloaded();
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param acsoR $acsoR
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function view(
        CurrentRoute $currentRoute,
        acsoR $acsoR,
        AllowanceChargeRepository $allowanceChargeRepository,
    ): \Psr\Http\Message\ResponseInterface {
        $salesorderAllowanceCharge = $this->salesorderallowancecharge(
            $currentRoute, $acsoR);
        if ($salesorderAllowanceCharge) {
            $salesorder_id = $salesorderAllowanceCharge->getSales_order_id();
            $form = new SalesOrderAllowanceChargeForm($salesorderAllowanceCharge,
                (int) $salesorder_id);
            $parameters = [
                'title' => $this->translator->translate('view'),
                'actionName' => 'salesorderallowancecharge/view',
                'actionArguments' => ['id' => $salesorderAllowanceCharge->getId()],
                'form' => $form,
                'optionsDataAllowanceCharges' =>
                    $allowanceChargeRepository->optionsDataAllowanceCharges(),
            ];
            return $this->webViewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse(
            'salesorderallowancecharge/index');
    }
}
