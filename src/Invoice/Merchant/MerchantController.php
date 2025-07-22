<?php

declare(strict_types=1);

namespace App\Invoice\Merchant;

use App\Invoice\BaseController;
use App\Invoice\Entity\Merchant;
use App\Invoice\Inv\InvRepository;
use App\Invoice\Setting\SettingRepository as sR;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
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
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR, $flash);
        $this->merchantService = $merchantService;
    }

    public function index(MerchantRepository $merchantRepository): \Yiisoft\DataResponse\DataResponse
    {
        $canEdit    = $this->rbac();
        $merchants  = $this->merchants($merchantRepository);
        $paginator  = (new OffsetPaginator($merchants));
        $parameters = [
            'canEdit'   => $canEdit,
            'paginator' => $paginator,
            'merchants' => $this->merchants($merchantRepository),
            'alert'     => $this->alert(),
        ];

        return $this->viewRenderer->render('index', $parameters);
    }

    public function add(
        Request $request,
        FormHydrator $formHydrator,
        InvRepository $invRepository,
    ): Response {
        $merchant   = new Merchant();
        $form       = new MerchantForm($merchant);
        $parameters = [
            'title'           => $this->translator->translate('add'),
            'actionName'      => 'merchant/add',
            'actionArguments' => [],
            'errors'          => [],
            'form'            => $form,
            'invs'            => $invRepository->findAllPreloaded(),
        ];

        if (Method::POST === $request->getMethod()) {
            $body = $request->getParsedBody() ?? [];
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                if (is_array($body)) {
                    $this->merchantService->saveMerchant($merchant, $body);

                    return $this->webService->getRedirectResponse('merchant/index');
                }
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form']   = $form;
        }

        return $this->viewRenderer->render('_form', $parameters);
    }

    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        MerchantRepository $merchantRepository,
        InvRepository $invRepository,
    ): Response {
        $merchant = $this->merchant($currentRoute, $merchantRepository);
        if ($merchant) {
            $form       = new MerchantForm($merchant);
            $parameters = [
                'title'           => $this->translator->translate('edit'),
                'actionName'      => 'merchant/edit',
                'actionArguments' => ['id' => $merchant->getId()],
                'errors'          => [],
                'form'            => $form,
                'invs'            => $invRepository->findAllPreloaded(),
            ];
            if (Method::POST === $request->getMethod()) {
                $body = $request->getParsedBody() ?? [];
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    if (is_array($body)) {
                        $this->merchantService->saveMerchant($merchant, $body);

                        return $this->webService->getRedirectResponse('merchant/index');
                    }
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form']   = $form;
            }

            return $this->viewRenderer->render('_form', $parameters);
        }

        return $this->webService->getRedirectResponse('merchant/index');
    }

    public function delete(
        CurrentRoute $currentRoute,
        MerchantRepository $merchantRepository,
    ): Response {
        $merchant = $this->merchant($currentRoute, $merchantRepository);
        if ($merchant) {
            $this->merchantService->deleteMerchant($merchant);

            return $this->webService->getRedirectResponse('merchant/index');
        }

        return $this->webService->getRedirectResponse('merchant/index');
    }

    public function view(
        CurrentRoute $currentRoute,
        InvRepository $invRepository,
        MerchantRepository $merchantRepository,
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $merchant = $this->merchant($currentRoute, $merchantRepository);
        if ($merchant) {
            $form       = new MerchantForm($merchant);
            $parameters = [
                'title'           => $this->translator->translate('view'),
                'actionName'      => 'merchant/view',
                'actionArguments' => ['id' => $merchant->getId()],
                'form'            => $form,
                'invs'            => $invRepository->findAllPreloaded(),
            ];

            return $this->viewRenderer->render('_view', $parameters);
        }

        return $this->webService->getRedirectResponse('merchant/index');
    }

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
            $this->flashMessage('warning', $this->translator->translate('permission'));

            return $this->webService->getRedirectResponse('merchant/index');
        }

        return $canEdit;
    }

    private function merchant(CurrentRoute $currentRoute, MerchantRepository $merchantRepository): ?Merchant
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $merchantRepository->repoMerchantquery($id);
        }

        return null;
    }

    /**
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function merchants(MerchantRepository $merchantRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $merchantRepository->findAllPreloaded();
    }
}
