<?php

declare(strict_types=1);

namespace App\Invoice\ProductImage;

use App\Invoice\Entity\ProductImage;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Product\ProductRepository;
use App\Invoice\Traits\FlashMessage;
use App\User\UserService;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;
use Exception;

final class ProductImageController
{
    use FlashMessage;

    private Flash $flash;
    private SessionInterface $session;
    private SettingRepository $s;
    private DataResponseFactoryInterface $factory;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private ProductImageService $productimageService;
    private TranslatorInterface $translator;

    public function __construct(
        SettingRepository $s,
        SessionInterface $session,
        DataResponseFactoryInterface $factory,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        ProductImageService $productimageService,
        TranslatorInterface $translator,
    ) {
        $this->s = $s;
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->factory = $factory;
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/productimage')
             ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->userService = $userService;
        $this->productimageService = $productimageService;
        $this->translator = $translator;
    }

    /** Note: A  productimage Upload can only be viewed with editInv permission
     *
     * Refer to: config/common/routes/routes.php ... specifically AccessChecker
     *
     * Route::methods([Method::GET, Method::POST], '/productimage/view/{id}')
      ->name('upload/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([UploadController::class, 'view']),
     */

    /**
     * @param Request $request
     * @param ProductImageRepository $productimageRepository
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function index(Request $request, ProductImageRepository $productimageRepository): \Yiisoft\DataResponse\DataResponse
    {
        $query_params = $request->getQueryParams();
        /** @var string $query_params['sort'] */
        $sort = Sort::only(['id', 'product_id', 'file_name_original'])
                // (@see vendor\yiisoft\data\src\Reader\Sort
                // - => 'desc'  so -id => default descending on id
                // Show the latest uploads first => -id
                ->withOrderString($query_params['sort'] ?? '-id');
        $productimages = $this->productimages_with_sort($productimageRepository, $sort);
        $paginator = (new OffsetPaginator($productimages))
                ->withPageSize($this->s->positiveListLimit());

        $parameters = [
            'paginator' => $paginator,
            'productimages' => $this->productimages($productimageRepository),
            'alert' => $this->alert(),
        ];
        return $this->viewRenderer->render('index', $parameters);
    }

    /**
     * @see Alternative currently used: ProductController function image_attachment_move_to
     * @see This function is not currently used but can be adapted for use
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param ProductRepository $productRepository
     * @return Response
     */
    public function add(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        ProductRepository $productRepository
    ): Response {
        $product_id = $currentRoute->getArgument('product_id');
        $productImage = new ProductImage();
        $productImageForm = new ProductImageForm($productImage, (int)$product_id);
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'actionName' => 'productimage/add',
            'actionArguments' => [],
            'errors' => [],
            'form' => $productImageForm,
            'products' => $productRepository->findAllPreloaded(),
        ];

        if ($request->getMethod() === Method::POST) {
            if ($formHydrator->populateFromPostAndValidate($productImageForm, $request)) {
                $body = $request->getParsedBody();
                /**
                 * @psalm-suppress PossiblyInvalidArgument
                 */
                $this->productimageService->saveProductImage($productImage, $body);
                return $this->webService->getRedirectResponse('productimage/index');
            }
            $parameters['errors'] = $productImageForm->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form'] = $productImageForm;
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
     * @param ProductImageRepository $productimageRepository
     * @param SettingRepository $settingRepository
     * @return Response
     */
    public function delete(
        CurrentRoute $currentRoute,
        ProductImageRepository $productimageRepository,
        SettingRepository $settingRepository
    ): Response {
        try {
            $productimage = $this->productimage($currentRoute, $productimageRepository);
            if ($productimage) {
                $this->productimageService->deleteProductImage($productimage, $settingRepository);
                $product_id = (string) $productimage->getProduct()?->getProduct_id();
                $this->flashMessage('info', $this->translator->translate('i.record_successfully_deleted'));
                return $this->factory->createResponse($this->viewRenderer->renderPartialAsString(
                    '//invoice/setting/inv_message',
                    [
                        'heading' => '',
                        'message' => $this->translator->translate('i.record_successfully_deleted'),
                        'url' => 'product/view',
                        'id' => $product_id,
                    ]
                ));
            }
            return $this->webService->getRedirectResponse('productimage/index');
        } catch (Exception $e) {
            $this->flashMessage('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('productimage/index');
        }
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param ProductImageRepository $productimageRepository
     * @param ProductRepository $productRepository
     * @return Response
     */
    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        ProductImageRepository $productimageRepository,
        ProductRepository $productRepository
    ): Response {
        $productImage = $this->productimage($currentRoute, $productimageRepository);
        if ($productImage) {
            $product_id = $productImage->getProduct_id();
            $form = new ProductImageForm($productImage, (int)$product_id);
            $parameters = [
                'title' => $this->translator->translate('i.edit'),
                'actionName' => 'productimage/edit',
                'actionArguments' => ['id' => $productImage->getId()],
                'errors' => [],
                'form' => $form,
                'products' => $productRepository->findAllPreloaded(),
            ];
            if ($request->getMethod() === Method::POST) {
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    $body = $request->getParsedBody() ?? [];
                    if (is_array($body)) {
                        $this->productimageService->saveProductImage($productImage, $body);
                        return $this->webService->getRedirectResponse('productimage/index');
                    }
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('productimage/index');
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param ProductImageRepository $productimageRepository
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function view(CurrentRoute $currentRoute, ProductImageRepository $productimageRepository): \Yiisoft\DataResponse\DataResponse|Response
    {
        $productImage = $this->productimage($currentRoute, $productimageRepository);
        if ($productImage) {
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'actionName' => 'productimage/view',
                'actionArguments' => ['id' => $productImage->getId()],
                'form' => new ProductImageForm($productImage, (int)$productImage->getProduct_id()),
                'productimage' => $productimageRepository->repoProductImagequery($productImage->getId()),
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('productimage/index');
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param ProductImageRepository $productimageRepository
     * @return ProductImage|null
     */
    public function productimage(CurrentRoute $currentRoute, ProductImageRepository $productimageRepository): ProductImage|null
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $productimageRepository->repoProductImagequery($id);
        }
        return null;
    }

    /**
     * @param ProductImageRepository $productimageRepository
     *
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function productimages(ProductImageRepository $productimageRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $productimageRepository->findAllPreloaded();
    }

    /**
     * @param ProductImageRepository $productimageRepository
     * @param Sort $sort
     *
     * @return \Yiisoft\Data\Reader\DataReaderInterface&\Yiisoft\Data\Reader\SortableDataInterface
     *
     * @psalm-return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface<int, ProductImage>
     */
    private function productimages_with_sort(ProductImageRepository $productimageRepository, Sort $sort): \Yiisoft\Data\Reader\SortableDataInterface
    {
        return $productimageRepository->findAllPreloaded()
                ->withSort($sort);
    }
}
