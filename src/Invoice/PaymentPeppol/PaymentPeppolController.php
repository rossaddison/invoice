<?php

declare(strict_types=1);

namespace App\Invoice\PaymentPeppol;

use App\Invoice\BaseController;
use App\Invoice\Entity\PaymentPeppol;
use App\Invoice\Setting\SettingRepository as sR;
use App\User\UserService;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;
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
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        Flash $flash
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR, $flash);
        $this->paymentpeppolService = $paymentpeppolService;
    }

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @return Response
     */
    public function add(
        Request $request,
        FormHydrator $formHydrator
    ): Response {
        $paymentPeppol = new PaymentPeppol();
        $form = new PaymentPeppolForm($paymentPeppol);
        $parameters = [
            'title' => $this->translator->translate('add'),
            'actionName' => 'paymentpeppol/add',
            'errors' => [],
            'form' => $form,
            'response' => $this->viewRenderer->renderPartial(
                '//invoice/layout/header_buttons',
                [
                    'hide_submit_button' => false ,
                    'hide_cancel_button' => false,
                ]
            ),
        ];

        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody() ?? [];
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                if (is_array($body)) {
                    $this->paymentpeppolService->savePaymentPeppol($paymentPeppol, $body);
                    return $this->webService->getRedirectResponse('paymentpeppol/index');
                }
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form'] = $form;
        }
        return $this->viewRenderer->render('_form', $parameters);
    }

    /**
     * @param CurrentRoute $routeCurrent
     * @param PaymentPeppolRepository $paymentpeppolRepository
     * @return Response
     */
    public function index(CurrentRoute $routeCurrent, PaymentPeppolRepository $paymentpeppolRepository): Response
    {
        $page = (int)$routeCurrent->getArgument('page', '1');
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = $page > 0 ? $page : 1;
        $paymentpeppols = $paymentpeppolRepository->findAllPreloaded();
        $paginator = (new OffsetPaginator($paymentpeppols))
        ->withPageSize($this->sR->positiveListLimit())
        ->withCurrentPage($currentPageNeverZero)
        ->withToken(PageToken::next((string)$page));
        $parameters = [
            'paymentpeppols' => $this->paymentpeppols($paymentpeppolRepository),
            'paginator' => $paginator,
            'alert' => $this->alert(),
            'routeCurrent' => $routeCurrent,
        ];
        return $this->viewRenderer->render('paymentpeppol/index', $parameters);
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param PaymentPeppolRepository $paymentpeppolRepository
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute, PaymentPeppolRepository $paymentpeppolRepository): Response
    {
        try {
            $paymentpeppol = $this->paymentpeppol($currentRoute, $paymentpeppolRepository);
            if (null !== $paymentpeppol) {
                $this->paymentpeppolService->deletePaymentPeppol($paymentpeppol);
                $this->flashMessage('info', $this->translator->translate('record.successfully.deleted'));
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
        PaymentPeppolRepository $paymentpeppolRepository
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
                'response' => $this->viewRenderer->renderPartial(
                    '//invoice/layout/header_buttons',
                    [
                        'hide_submit_button' => false ,
                        'hide_cancel_button' => false,
                    ]
                ),
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    if (is_array($body)) {
                        $this->paymentpeppolService->savePaymentPeppol($paymentPeppol, $body);
                        return $this->webService->getRedirectResponse('paymentpeppol/index');
                    }
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('paymentpeppol/index');
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param PaymentPeppolRepository $paymentpeppolRepository
     * @return PaymentPeppol|null
     */
    private function paymentpeppol(CurrentRoute $currentRoute, PaymentPeppolRepository $paymentpeppolRepository): PaymentPeppol|null
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
    private function paymentpeppols(PaymentPeppolRepository $paymentpeppolRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $paymentpeppolRepository->findAllPreloaded();
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param PaymentPeppolRepository $paymentpeppolRepository
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function view(CurrentRoute $currentRoute, PaymentPeppolRepository $paymentpeppolRepository): \Yiisoft\DataResponse\DataResponse|Response
    {
        $paymentPeppol = $this->paymentpeppol($currentRoute, $paymentpeppolRepository);
        if ($paymentPeppol) {
            $form = new PaymentPeppolForm($paymentPeppol);
            $parameters = [
                'title' => $this->translator->translate('view'),
                'actionName' => 'paymentpeppol/view',
                'actionArguments' => ['id' => $paymentPeppol->getId()],
                'form' => $form,
                'paymentpeppol' => $paymentPeppol,
                'response' => $this->viewRenderer->renderPartial(
                    '//invoice/layout/header_buttons',
                    [
                        'hide_submit_button' => false ,
                        'hide_cancel_button' => false,
                    ]
                ),
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('paymentpeppol/index');
    }
}
