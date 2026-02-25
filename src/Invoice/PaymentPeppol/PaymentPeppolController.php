<?php

declare(strict_types=1);

namespace App\Invoice\PaymentPeppol;

use App\Auth\Permissions;
use App\Invoice\BaseController;
use App\Invoice\Entity\Inv;
use App\Invoice\Entity\PaymentPeppol;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\UserClient\UserClientRepository as UCR;
use App\Invoice\UserInv\UserInvRepository as UIR;
use App\Invoice\Setting\SettingRepository as sR;
use App\User\UserService;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;
use Exception;

final class PaymentPeppolController extends BaseController
{
    protected string $controllerName = 'invoice/paymentpeppol';

    public function __construct(
        private PaymentPeppolService $paymentpeppolService,
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
        $this->paymentpeppolService = $paymentpeppolService;
    }

    /**
     * 
     */
    public function add(
        #[RouteArgument('inv_id')]
        string $inv_id,
        #[RouteArgument('_language')]
        string $_language,
        IR $iR,
        UCR $ucR,
        UIR $uiR,
        Request $request,
        FormHydrator $formHydrator,
    ): Response {
        $paymentPeppol = new PaymentPeppol();
        $inv = $iR->repoInvUnLoadedquery($inv_id);
        $form = new PaymentPeppolForm($paymentPeppol);
        $parameters = [
            'title' => $this->translator->translate('add'),
            'actionName' => 'paymentpeppol/add',
            'errors' => [],
            'form' => $form,
            'response' => $this->webViewRenderer->renderPartial(
                '//invoice/layout/header_buttons',
                [
                    'hide_submit_button' => false,
                    'hide_cancel_button' => false,
                ],
            ),
        ];
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody() ?? [];
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                if (is_array($body)) {
                    $this->paymentpeppolService->savePaymentPeppol(
                            $paymentPeppol, $body);
                    return $this->webService->getRedirectResponse(
                        'paymentpeppol/index');
                }
            }
            $parameters['errors'] =
                $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form'] = $form;
        }
        if (null!==$inv) {
            if ($this->rbacObserver($inv, $ucR, $uiR)) {
                return $this->webViewRenderer->render('_form', $parameters);
            }
        }
        return $this->webService->getRedirectResponse('paymentpeppol/index');
    }

    /**
     * @param CurrentRoute $routeCurrent
     * @param PaymentPeppolRepository $paymentpeppolRepository
     * @return Response
     */
    public function index(
            CurrentRoute $routeCurrent,
            PaymentPeppolRepository $paymentpeppolRepository): Response
    {
        $page = (int) $routeCurrent->getArgument('page', '1');
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = $page > 0 ? $page : 1;
        $paymentpeppols = $paymentpeppolRepository->findAllPreloaded();
        $paginator = (new OffsetPaginator($paymentpeppols))
        ->withPageSize($this->sR->positiveListLimit())
        ->withCurrentPage($currentPageNeverZero)
        ->withToken(PageToken::next((string) $page));
        $parameters = [
            'paymentpeppols' => $this->paymentpeppols($paymentpeppolRepository),
            'paginator' => $paginator,
            'alert' => $this->alert(),
            'routeCurrent' => $routeCurrent,
        ];
        return $this->webViewRenderer->render('index', $parameters);
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param PaymentPeppolRepository $paymentpeppolRepository
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute,
                    PaymentPeppolRepository $paymentpeppolRepository): Response
    {
        try {
            $paymentpeppol = $this->paymentpeppol($currentRoute, $paymentpeppolRepository);
            if (null !== $paymentpeppol) {
                $this->paymentpeppolService->deletePaymentPeppol($paymentpeppol);
                $this->flashMessage('info',
                    $this->translator->translate('record.successfully.deleted'));
                return $this->webService->getRedirectResponse('paymentpeppol/index');
            }
            return $this->webService->getRedirectResponse('paymentpeppol/index');
        } catch (Exception $e) {
            $this->flashMessage('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('paymentpeppol/index');
        }
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param PaymentPeppolRepository $paymentpeppolRepository
     * @return Response
     */
    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        PaymentPeppolRepository $paymentpeppolRepository,
    ): Response {
        $paymentPeppol = $this->paymentpeppol($currentRoute, $paymentpeppolRepository);
        if ($paymentPeppol) {
            $form = new PaymentPeppolForm($paymentPeppol);
            $parameters = [
                'title' => $this->translator->translate('edit'),
                'actionName' => 'paymentpeppol/edit',
                'actionArguments' => ['id' => $paymentPeppol->getId()],
                'errors' => [],
                'form' => $form,
                'response' => $this->webViewRenderer->renderPartial(
                    '//invoice/layout/header_buttons',
                    [
                        'hide_submit_button' => false,
                        'hide_cancel_button' => false,
                    ],
                ),
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    if (is_array($body)) {
                        $this->paymentpeppolService->savePaymentPeppol(
                                                        $paymentPeppol, $body);
                        return $this->webService->getRedirectResponse(
                                                        'paymentpeppol/index');
                    }
                }
                $parameters['errors'] =
                $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->webViewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('paymentpeppol/index');
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param PaymentPeppolRepository $paymentpeppolRepository
     * @return PaymentPeppol|null
     */
    private function paymentpeppol(
            CurrentRoute $currentRoute,
            PaymentPeppolRepository $paymentpeppolRepository): ?PaymentPeppol
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $paymentpeppolRepository->repoPaymentPeppolLoadedquery($id);
        }
        return null;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function paymentpeppols(
            PaymentPeppolRepository $paymentpeppolRepository):
                                        \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $paymentpeppolRepository->findAllPreloaded();
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param PaymentPeppolRepository $paymentpeppolRepository
     * @param UCR $ucR
     * @param UIR $uiR
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function view(
        CurrentRoute $currentRoute,
        PaymentPeppolRepository $paymentpeppolRepository,
        UCR $ucR,
        UIR $uiR
    ): \Psr\Http\Message\ResponseInterface
    {
        $paymentPeppol = $this->paymentpeppol(
                                        $currentRoute, $paymentpeppolRepository);
        if ($paymentPeppol) {
            $form = new PaymentPeppolForm($paymentPeppol);
            $parameters = [
                'title' => $this->translator->translate('view'),
                'actionName' => 'paymentpeppol/view',
                'actionArguments' => ['id' => $paymentPeppol->getId()],
                'form' => $form,
                'paymentpeppol' => $paymentPeppol,
                'response' => $this->webViewRenderer->renderPartial(
                    '//invoice/layout/header_buttons',
                    [
                        'hide_submit_button' => false,
                        'hide_cancel_button' => false,
                    ],
                ),
            ];
            $inv = $paymentPeppol->getInv();
            if (null!==$inv) {
                if ($this->rbacObserver($inv, $ucR, $uiR)) {
                    return $this->webViewRenderer->render('_view', $parameters);
                }
                if ($this->rbacAdmin()) {
                    return $this->webViewRenderer->render('_view', $parameters);
                }
                if ($this->rbacAccountant()) {
                    return $this->webViewRenderer->render('_view', $parameters);
                }
            }
        }
        return $this->webService->getRedirectResponse('paymentpeppol/index');
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
        $statusId = $inv->getStatus_id();
        if (null!==$statusId) {
            // has observer role
            if ($this->userService->hasPermission(Permissions::VIEW_INV)
                && !($this->userService->hasPermission(Permissions::EDIT_INV))
                // the invoice  is not a draft i.e. has been sent
                && !($statusId === 1)
                // the invoice is intended for the current user        
                && ($inv->getUser_id() ===
                                        $this->userService->getUser()?->getId())
                // the invoice client is associated with the above user
                // the observer user may be paying for more than one client    
                && ($ucR->repoUserClientqueryCount($inv->getUser_id(),
                                                $inv->getClient_id()) > 0)) {
                $userInv = $uiR->repoUserInvUserIdquery((string) $statusId);
                // the current observer user is active
                if (null !== $userInv && $userInv->getActive()) {
                    return true;
                }
            }
        }
        return false;
    }
    
    private function rbacAccountant() : bool {
        // has accountant role
        if (($this->userService->hasPermission(Permissions::VIEW_INV)
            && ($this->userService->hasPermission(Permissions::VIEW_PAYMENT))
            && ($this->userService->hasPermission(Permissions::EDIT_PAYMENT)))) {
            return true;
        } else {
            return false;
        }
    }
    
    private function rbacAdmin() : bool {
        // has observer role
        if ($this->userService->hasPermission(Permissions::VIEW_INV)
            && ($this->userService->hasPermission(Permissions::EDIT_INV))) {
            return true;
        } else {
            return false;
        }
    }
}
