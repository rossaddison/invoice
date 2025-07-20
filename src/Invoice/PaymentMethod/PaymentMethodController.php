<?php

declare(strict_types=1);

namespace App\Invoice\PaymentMethod;

use App\Invoice\BaseController;
use App\Invoice\Entity\PaymentMethod;
use App\Invoice\Setting\SettingRepository as sR;
use App\User\UserService;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class PaymentMethodController extends BaseController
{
    protected string $controllerName = 'invoice/paymentmethod';

    public function __construct(
        private PaymentMethodService $paymentmethodService,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR, $flash);
        $this->paymentmethodService = $paymentmethodService;
    }

    /**
     * @param PaymentMethodRepository $paymentmethodRepository
     * @param Request $request
     * @param PaymentMethodService $service
     */
    public function index(PaymentMethodRepository $paymentmethodRepository, Request $request, PaymentMethodService $service): \Yiisoft\DataResponse\DataResponse
    {
        $canEdit = $this->rbac();
        $parameters = [
            'canEdit' => $canEdit,
            'payment_methods' => $this->paymentmethods($paymentmethodRepository),
            'alert' => $this->alert(),
        ];
        return $this->viewRenderer->render('index', $parameters);
    }

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @return Response
     */
    public function add(Request $request, FormHydrator $formHydrator): Response
    {
        $form = new PaymentMethodForm(new PaymentMethod());
        $parameters = [
            'title' => $this->translator->translate('add'),
            'actionName' => 'paymentmethod/add',
            'actionArguments' => [],
            'errors' => [],
            'form' => $form,
        ];

        if ($request->getMethod() === Method::POST) {
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                /**
                 * @psalm-suppress PossiblyInvalidArgument $request->getParsedBody()
                 */
                $this->paymentmethodService->savePaymentMethod(new PaymentMethod(), $request->getParsedBody());
                return $this->webService->getRedirectResponse('paymentmethod/index');
            }
            $parameters['form'] = $form;
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
        }
        return $this->viewRenderer->render('_form', $parameters);
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param PaymentMethodRepository $paymentmethodRepository
     * @return Response
     */
    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        PaymentMethodRepository $paymentmethodRepository,
    ): Response {
        $payment_method = $this->paymentmethod($currentRoute, $paymentmethodRepository);
        if ($payment_method) {
            $form = new PaymentMethodForm($payment_method);
            $parameters = [
                'title' => $this->translator->translate('edit'),
                'actionName' => 'paymentmethod/edit',
                'actionArguments' => ['id' => $payment_method->getId()],
                'errors' => [],
                'form' => $form,
            ];
            if ($request->getMethod() === Method::POST) {
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    /**
                     * @psalm-suppress PossiblyInvalidArgument $request->getParsedBody()
                     */
                    $this->paymentmethodService->savePaymentMethod($payment_method, $request->getParsedBody());
                    return $this->webService->getRedirectResponse('paymentmethod/index');
                }
                $parameters['form'] = $form;
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            }
            return $this->viewRenderer->render('_form', $parameters);
        } // if payment_method
        return $this->webService->getRedirectResponse('paymentmethod/index');
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param PaymentMethodRepository $paymentmethodRepository
     * @return Response
     */
    public function delete(
        CurrentRoute $currentRoute,
        PaymentMethodRepository $paymentmethodRepository,
    ): Response {
        try {
            $payment_method = $this->paymentmethod($currentRoute, $paymentmethodRepository);
            if ($payment_method) {
                $this->paymentmethodService->deletePaymentMethod($payment_method);
                return $this->webService->getRedirectResponse('paymentmethod/index');
            }
            return $this->webService->getRedirectResponse('paymentmethod/index');
        } catch (\Exception $e) {
            unset($e);
            $this->flashMessage('danger', $this->translator->translate('payment.method.history'));
            return $this->webService->getRedirectResponse('paymentmethod/index');
        }
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param PaymentMethodRepository $paymentmethodRepository
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function view(CurrentRoute $currentRoute, PaymentMethodRepository $paymentmethodRepository): \Yiisoft\DataResponse\DataResponse|Response
    {
        $payment_method = $this->paymentmethod($currentRoute, $paymentmethodRepository);
        $parameters = [];
        if ($payment_method) {
            $form = new PaymentMethodForm($payment_method);
            $parameters = [
                'title' => $this->translator->translate('view'),
                'actionName' => 'paymentmethod/view',
                'actionArguments' => ['id' => $payment_method->getId()],
                'form' => $form,
                'paymentmethod' => $paymentmethodRepository->repoPaymentMethodquery($payment_method->getId()),
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('paymentmethod/index');
    }

    /**
     * @return Response|true
     */
    private function rbac(): bool|Response
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit) {
            $this->flashMessage('warning', $this->translator->translate('permission'));
            return $this->webService->getRedirectResponse('paymentmethod/index');
        }
        return $canEdit;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param PaymentMethodRepository $paymentmethodRepository
     * @return PaymentMethod|null
     */
    private function paymentmethod(
        CurrentRoute $currentRoute,
        PaymentMethodRepository $paymentmethodRepository,
    ): PaymentMethod|null {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $paymentmethodRepository->repoPaymentMethodquery($id);
        }
        return null;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function paymentmethods(PaymentMethodRepository $paymentmethodRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $paymentmethodRepository->findAllPreloaded();
    }
}
