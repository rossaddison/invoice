<?php

declare(strict_types=1);

namespace App\Invoice\InvAllowanceCharge;

use App\Invoice\BaseController;
use App\Invoice\Entity\InvAllowanceCharge;
use App\Invoice\AllowanceCharge\AllowanceChargeRepository;
use App\Invoice\Setting\SettingRepository as sR;
use App\User\UserService;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
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
                    return $this->webService->getRedirectResponse('invallowancecharge/index');
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
    public function index(InvAllowanceChargeRepository $invallowancechargeRepository): Response
    {
        $invallowancecharges = $this->invallowancecharges($invallowancechargeRepository);
        $paginator = (new OffsetPaginator($invallowancecharges));
        $parameters = [
            'paginator' => $paginator,
            'alert' => $this->alert(),
        ];
        return $this->viewRenderer->render('index', $parameters);
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param InvAllowanceChargeRepository $invallowancechargeRepository
     * @return Response
     */
    public function delete(
        CurrentRoute $currentRoute,
        InvAllowanceChargeRepository $invallowancechargeRepository,
    ): Response {
        try {
            $invallowancecharge = $this->invallowancecharge($currentRoute, $invallowancechargeRepository);
            if ($invallowancecharge) {
                $this->invallowancechargeService->deleteInvAllowanceCharge($invallowancecharge);
                $this->flashMessage('info', $this->translator->translate('record.successfully.deleted'));
                return $this->webService->getRedirectResponse('invallowancecharge/index');
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
     * @param InvAllowanceChargeRepository $invAllowanceChargeRepository
     * @param AllowanceChargeRepository $allowanceChargeRepository
     * @return Response
     */
    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        InvAllowanceChargeRepository $invAllowanceChargeRepository,
        AllowanceChargeRepository $allowanceChargeRepository,
    ): Response {
        $invAllowanceCharge = $this->invallowancecharge($currentRoute, $invAllowanceChargeRepository);
        if ($invAllowanceCharge) {
            $inv_id = $invAllowanceCharge->getInv_id();
            $form = new InvAllowanceChargeForm($invAllowanceCharge, (int) $inv_id);
            $parameters = [
                'title' => $this->translator->translate('allowance.or.charge'),
                'action' => ['invallowancecharge/edit', ['id' => $invAllowanceCharge->getId()]],
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
    private function invallowancecharge(CurrentRoute $currentRoute, InvAllowanceChargeRepository $invallowancechargeRepository): InvAllowanceCharge|null
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
                'action' => ['invallowancecharge/view', ['id' => $invAllowanceCharge->getId()]],
                'form' => $form,
                'optionsDataAllowanceCharges' => $allowanceChargeRepository->optionsDataAllowanceCharges(),
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('invallowancecharge/index');
    }
}
