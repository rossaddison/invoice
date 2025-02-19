<?php

declare(strict_types=1);

namespace App\Invoice\ProductProperty;

use App\Invoice\Entity\ProductProperty;
use App\Invoice\Product\ProductRepository;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Traits\FlashMessage;
use App\User\UserService;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;
use Exception;

final class ProductPropertyController
{
    use FlashMessage;

    private Flash $flash;
    private ViewRenderer $viewRenderer;

    public function __construct(
        private SessionInterface $session,
        ViewRenderer $viewRenderer,
        private WebControllerService $webService,
        private UserService $userService,
        private ProductPropertyService $productpropertyService,
        private TranslatorInterface $translator
    ) {
        $this->flash = new Flash($this->session);
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/productproperty')
                                           // The Controller layout dir is now redundant: replaced with an alias
                                           ->withLayout('@views/layout/invoice.php');
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param SettingRepository $settingRepository
     * @param ProductRepository $productRepository
     * @return Response
     */
    public function add(
        CurrentRoute $currentRoute,
        Request $request,
        FormHydrator $formHydrator,
        ProductRepository $productRepository
    ): Response {
        $product_id = $currentRoute->getArgument('product_id');
        $productProperty = new ProductProperty();
        $form = new ProductPropertyForm($productProperty, (int)$product_id);
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'actionName' => 'productproperty/add',
            'actionArguments' => ['product_id' => $product_id],
            'errors' => [],
            'form' => $form,
            'products' => $productRepository->findAllPreloaded(),
        ];

        if ($request->getMethod() === Method::POST) {
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    $this->productpropertyService->saveProductProperty($productProperty, $body);
                    return $this->webService->getRedirectResponse('productproperty/index');
                }
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form'] = $form;
        }
        return $this->viewRenderer->render('_form', $parameters);
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
            ]
        );
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param ProductPropertyRepository $productpropertyRepository
     * @param SettingRepository $settingRepository
     * @return Response
     */
    public function index(CurrentRoute $currentRoute, ProductPropertyRepository $productpropertyRepository, SettingRepository $settingRepository): Response
    {
        $page = (int)$currentRoute->getArgument('page', '1');
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = $page > 0 ? $page : 1;
        $productproperty = $productpropertyRepository->findAllPreloaded();
        $paginator = (new OffsetPaginator($productproperty))
        ->withPageSize($settingRepository->positiveListLimit())
        ->withCurrentPage($currentPageNeverZero)
        ->withToken(PageToken::next((string)$page));
        $parameters = [
            'productpropertys' => $this->productpropertys($productpropertyRepository),
            'paginator' => $paginator,
            'alert' => $this->alert(),
            'max' => (int) $settingRepository->getSetting('default_list_limit'),
        ];
        return $this->viewRenderer->render('index', $parameters);
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param ProductPropertyRepository $productpropertyRepository
     * @return Response
     */
    public function delete(
        CurrentRoute $currentRoute,
        ProductPropertyRepository $productpropertyRepository
    ): Response {
        try {
            $productproperty = $this->productproperty($currentRoute, $productpropertyRepository);
            if ($productproperty) {
                $this->productpropertyService->deleteProductProperty($productproperty);
                $this->flashMessage('info', $this->translator->translate('i.record_successfully_deleted'));
                return $this->webService->getRedirectResponse('productproperty/index');
            }
            return $this->webService->getRedirectResponse('productproperty/index');
        } catch (Exception $e) {
            $this->flashMessage('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('productproperty/index');
        }
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param ProductPropertyRepository $productpropertyRepository
     * @param ProductRepository $productRepository
     * @return Response
     */
    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        ProductPropertyRepository $productpropertyRepository,
        ProductRepository $productRepository
    ): Response {
        $productProperty = $this->productproperty($currentRoute, $productpropertyRepository);
        if ($productProperty) {
            $form = new ProductPropertyForm($productProperty, (int)$productProperty->getProduct_id());
            $parameters = [
                'title' => $this->translator->translate('i.edit'),
                'actionName' => 'productproperty/edit',
                'actionArguments' => ['id' => $productProperty->getProperty_id()],
                'errors' => [],
                'form' => $form,
                'products' => $productRepository->findAllPreloaded(),
            ];
            if ($request->getMethod() === Method::POST) {
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    $body = $request->getParsedBody() ?? [];
                    if (is_array($body)) {
                        $this->productpropertyService->saveProductProperty($productProperty, $body);
                        return $this->webService->getRedirectResponse('productproperty/index');
                    }
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('productproperty/index');
    }

    //For rbac refer to AccessChecker

    /**
     * @param CurrentRoute $currentRoute
     * @param ProductPropertyRepository $productpropertyRepository
     * @return ProductProperty|null
     */
    private function productproperty(CurrentRoute $currentRoute, ProductPropertyRepository $productpropertyRepository): ProductProperty|null
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $productpropertyRepository->repoProductPropertyLoadedquery($id);
        }
        return null;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function productpropertys(ProductPropertyRepository $productpropertyRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $productpropertyRepository->findAllPreloaded();
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param ProductPropertyRepository $productpropertyRepository
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function view(CurrentRoute $currentRoute, ProductPropertyRepository $productpropertyRepository): \Yiisoft\DataResponse\DataResponse|Response
    {
        $productProperty = $this->productproperty($currentRoute, $productpropertyRepository);
        if ($productProperty) {
            $form = new ProductPropertyForm($productProperty, (int)$productProperty->getProduct_id());
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'actionName' => 'productproperty/view',
                'actionArguments' => ['id' => $productProperty->getProperty_id()],
                'form' => $form,
                'productproperty' => $productProperty,
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('productproperty/index');
    }
}
