<?php

declare(strict_types=1);

namespace App\Invoice\Merchant;

use App\Invoice\BaseController;
use App\Invoice\Entity\Merchant;
use App\Invoice\Inv\InvRepository;
use App\Invoice\Setting\SettingRepository as sR;
use App\User\UserService;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class MerchantController extends BaseController
{
    protected string $controllerName = 'invoice/merchant';

    public function __construct(
        private MerchantService $merchantService,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR);
        $this->merchantService = $merchantService;
    }

    /**
     * @param MerchantRepository $merchantRepository
     */
    public function index(MerchantRepository $merchantRepository): \Yiisoft\DataResponse\DataResponse
    {
        $canEdit = $this->rbac();
        $merchants = $this->merchants($merchantRepository);
        $paginator = (new OffsetPaginator($merchants));
        $parameters = [
            'canEdit' => $canEdit,
            'paginator' => $paginator,
            'merchants' => $this->merchants($merchantRepository),
            'alert' => $this->alert(),
        ];
        return $this->viewRenderer->render('index', $parameters);
    }

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param InvRepository $invRepository
     * @return Response
     */
    public function add(
        Request $request,
        FormHydrator $formHydrator,
        InvRepository $invRepository
    ): Response {
        $merchant = new Merchant();
        $form = new MerchantForm($merchant);
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'actionName' => 'merchant/add',
            'actionArguments' => [],
            'errors' => [],
            'form' => $form,
            'invs' => $invRepository->findAllPreloaded(),
        ];

        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody() ?? [];
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                if (is_array($body)) {
                    $this->merchantService->saveMerchant($merchant, $body);
                    return $this->webService->getRedirectResponse('merchant/index');
                }
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form'] = $form;
        }
        return $this->viewRenderer->render('_form', $parameters);
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param MerchantRepository $merchantRepository
     * @param InvRepository $invRepository
     * @return Response
     */
    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        MerchantRepository $merchantRepository,
        InvRepository $invRepository
    ): Response {
        $merchant = $this->merchant($currentRoute, $merchantRepository);
        if ($merchant) {
            $form = new MerchantForm($merchant);
            $parameters = [
                'title' => $this->translator->translate('i.edit'),
                'actionName' => 'merchant/edit',
                'actionArguments' => ['id' => $merchant->getId()],
                'errors' => [],
                'form' => $form,
                'invs' => $invRepository->findAllPreloaded(),
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    if (is_array($body)) {
                        $this->merchantService->saveMerchant($merchant, $body);
                        return $this->webService->getRedirectResponse('merchant/index');
                    }
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('merchant/index');
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param MerchantRepository $merchantRepository
     * @return Response
     */
    public function delete(
        CurrentRoute $currentRoute,
        MerchantRepository $merchantRepository
    ): Response {
        $merchant = $this->merchant($currentRoute, $merchantRepository);
        if ($merchant) {
            $this->merchantService->deleteMerchant($merchant);
            return $this->webService->getRedirectResponse('merchant/index');
        }
        return $this->webService->getRedirectResponse('merchant/index');
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param InvRepository $invRepository
     * @param MerchantRepository $merchantRepository
     */
    public function view(
        CurrentRoute $currentRoute,
        InvRepository $invRepository,
        MerchantRepository $merchantRepository
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $merchant = $this->merchant($currentRoute, $merchantRepository);
        if ($merchant) {
            $form = new MerchantForm($merchant);
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'actionName' => 'merchant/view',
                'actionArguments' => ['id' => $merchant->getId()],
                'form' => $form,
                'invs' => $invRepository->findAllPreloaded(),
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('merchant/index');
    }

    /**
     * @param MerchantRepository $mR
     */
    public function online_log(MerchantRepository $mR): \Yiisoft\DataResponse\DataResponse
    {
        $parameters = [
            'payment_logs' => $mR->findAllPreloaded(),
        ];
        return $this->viewRenderer->render('_view', $parameters);
    }

    /**
     * @return Response|true
     */
    private function rbac(): bool|Response
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit) {
            $this->flashMessage('warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('merchant/index');
        }
        return $canEdit;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param MerchantRepository $merchantRepository
     * @return Merchant|null
     */
    private function merchant(CurrentRoute $currentRoute, MerchantRepository $merchantRepository): Merchant|null
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $merchantRepository->repoMerchantquery($id);
        }
        return null;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function merchants(MerchantRepository $merchantRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $merchantRepository->findAllPreloaded();
    }
}
