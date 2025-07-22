<?php

declare(strict_types=1);

namespace App\Invoice\ProductProperty;

use App\Invoice\BaseController;
use App\Invoice\Entity\ProductProperty;
use App\Invoice\Product\ProductRepository;
use App\Invoice\Setting\SettingRepository as sR;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class ProductPropertyController extends BaseController
{
    protected string $controllerName = 'invoice/productproperty';

    public function __construct(
        private ProductPropertyService $productpropertyService,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR, $flash);
        $this->productpropertyService = $productpropertyService;
    }

    public function add(
        CurrentRoute $currentRoute,
        Request $request,
        FormHydrator $formHydrator,
        ProductRepository $productRepository,
    ): Response {
        $product_id      = $currentRoute->getArgument('product_id');
        $productProperty = new ProductProperty();
        $form            = new ProductPropertyForm($productProperty, (int) $product_id);
        $parameters      = [
            'title'           => $this->translator->translate('add'),
            'actionName'      => 'productproperty/add',
            'actionArguments' => ['product_id' => $product_id],
            'errors'          => [],
            'form'            => $form,
            'products'        => $productRepository->findAllPreloaded(),
        ];

        if (Method::POST === $request->getMethod()) {
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    $this->productpropertyService->saveProductProperty($productProperty, $body);

                    return $this->webService->getRedirectResponse('productproperty/index');
                }
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form']   = $form;
        }

        return $this->viewRenderer->render('_form', $parameters);
    }

    public function index(CurrentRoute $currentRoute, ProductPropertyRepository $productpropertyRepository): Response
    {
        $page = (int) $currentRoute->getArgument('page', '1');
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = $page > 0 ? $page : 1;
        $productproperty      = $productpropertyRepository->findAllPreloaded();
        $paginator            = (new OffsetPaginator($productproperty))
            ->withPageSize($this->sR->positiveListLimit())
            ->withCurrentPage($currentPageNeverZero)
            ->withToken(PageToken::next((string) $page));
        $parameters = [
            'productpropertys' => $this->productpropertys($productpropertyRepository),
            'paginator'        => $paginator,
            'alert'            => $this->alert(),
            'max'              => (int) $this->sR->getSetting('default_list_limit'),
        ];

        return $this->viewRenderer->render('index', $parameters);
    }

    public function delete(
        CurrentRoute $currentRoute,
        ProductPropertyRepository $productpropertyRepository,
    ): Response {
        try {
            $productproperty = $this->productproperty($currentRoute, $productpropertyRepository);
            if ($productproperty) {
                $this->productpropertyService->deleteProductProperty($productproperty);
                $this->flashMessage('info', $this->translator->translate('record.successfully.deleted'));

                return $this->webService->getRedirectResponse('productproperty/index');
            }

            return $this->webService->getRedirectResponse('productproperty/index');
        } catch (\Exception $e) {
            $this->flashMessage('danger', $e->getMessage());

            return $this->webService->getRedirectResponse('productproperty/index');
        }
    }

    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        ProductPropertyRepository $productpropertyRepository,
        ProductRepository $productRepository,
    ): Response {
        $productProperty = $this->productproperty($currentRoute, $productpropertyRepository);
        if ($productProperty) {
            $form       = new ProductPropertyForm($productProperty, (int) $productProperty->getProduct_id());
            $parameters = [
                'title'           => $this->translator->translate('edit'),
                'actionName'      => 'productproperty/edit',
                'actionArguments' => ['id' => $productProperty->getProperty_id()],
                'errors'          => [],
                'form'            => $form,
                'products'        => $productRepository->findAllPreloaded(),
            ];
            if (Method::POST === $request->getMethod()) {
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    $body = $request->getParsedBody() ?? [];
                    if (is_array($body)) {
                        $this->productpropertyService->saveProductProperty($productProperty, $body);

                        return $this->webService->getRedirectResponse('productproperty/index');
                    }
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form']   = $form;
            }

            return $this->viewRenderer->render('_form', $parameters);
        }

        return $this->webService->getRedirectResponse('productproperty/index');
    }

    // For rbac refer to AccessChecker

    private function productproperty(CurrentRoute $currentRoute, ProductPropertyRepository $productpropertyRepository): ?ProductProperty
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $productpropertyRepository->repoProductPropertyLoadedquery($id);
        }

        return null;
    }

    /**
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function productpropertys(ProductPropertyRepository $productpropertyRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $productpropertyRepository->findAllPreloaded();
    }

    public function view(CurrentRoute $currentRoute, ProductPropertyRepository $productpropertyRepository): \Yiisoft\DataResponse\DataResponse|Response
    {
        $productProperty = $this->productproperty($currentRoute, $productpropertyRepository);
        if ($productProperty) {
            $form       = new ProductPropertyForm($productProperty, (int) $productProperty->getProduct_id());
            $parameters = [
                'title'           => $this->translator->translate('view'),
                'actionName'      => 'productproperty/view',
                'actionArguments' => ['id' => $productProperty->getProperty_id()],
                'form'            => $form,
                'productproperty' => $productProperty,
            ];

            return $this->viewRenderer->render('_view', $parameters);
        }

        return $this->webService->getRedirectResponse('productproperty/index');
    }
}
