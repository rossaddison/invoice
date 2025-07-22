<?php

declare(strict_types=1);

namespace App\Invoice\ProductImage;

use App\Invoice\BaseController;
use App\Invoice\Entity\ProductImage;
use App\Invoice\Product\ProductRepository;
use App\Invoice\Setting\SettingRepository as sR;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class ProductImageController extends BaseController
{
    protected string $controllerName = 'invoice/productimage';

    public function __construct(
        private DataResponseFactoryInterface $factory,
        private ProductImageService $productimageService,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR, $flash);
        $this->factory             = $factory;
        $this->productimageService = $productimageService;
    }

    /** Note: A  productimage Upload can only be viewed with editInv permission.
     *
     * Refer to: config/common/routes/routes.php ... specifically AccessChecker
     *
     * Route::methods([Method::GET, Method::POST], '/productimage/view/{id}')
     * ->name('upload/view')
     * ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
     * ->middleware(Authentication::class)
     * ->action([UploadController::class, 'view']),
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
        $paginator     = (new OffsetPaginator($productimages))
            ->withPageSize($this->sR->positiveListLimit());

        $parameters = [
            'paginator'     => $paginator,
            'productimages' => $this->productimages($productimageRepository),
            'alert'         => $this->alert(),
        ];

        return $this->viewRenderer->render('index', $parameters);
    }

    /**
     * @see Alternative currently used: ProductController function image_attachment_move_to
     * @see This function is not currently used but can be adapted for use
     */
    public function add(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        ProductRepository $productRepository,
    ): Response {
        $product_id       = $currentRoute->getArgument('product_id');
        $productImage     = new ProductImage();
        $productImageForm = new ProductImageForm($productImage, (int) $product_id);
        $parameters       = [
            'title'           => $this->translator->translate('add'),
            'actionName'      => 'productimage/add',
            'actionArguments' => [],
            'errors'          => [],
            'form'            => $productImageForm,
            'products'        => $productRepository->findAllPreloaded(),
        ];

        if (Method::POST === $request->getMethod()) {
            if ($formHydrator->populateFromPostAndValidate($productImageForm, $request)) {
                $body = $request->getParsedBody();
                /*
                 * @psalm-suppress PossiblyInvalidArgument
                 */
                $this->productimageService->saveProductImage($productImage, $body);

                return $this->webService->getRedirectResponse('productimage/index');
            }
            $parameters['errors'] = $productImageForm->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form']   = $productImageForm;
        }

        return $this->viewRenderer->render('_form', $parameters);
    }

    public function delete(
        CurrentRoute $currentRoute,
        ProductImageRepository $productimageRepository,
    ): Response {
        try {
            $productimage = $this->productimage($currentRoute, $productimageRepository);
            if ($productimage) {
                $this->productimageService->deleteProductImage($productimage, $this->sR);
                $product_id = (string) $productimage->getProduct()?->getProduct_id();
                $this->flashMessage('info', $this->translator->translate('record.successfully.deleted'));

                return $this->factory->createResponse($this->viewRenderer->renderPartialAsString(
                    '//invoice/setting/inv_message',
                    [
                        'heading' => '',
                        'message' => $this->translator->translate('record.successfully.deleted'),
                        'url'     => 'product/view',
                        'id'      => $product_id,
                    ],
                ));
            }

            return $this->webService->getRedirectResponse('productimage/index');
        } catch (\Exception $e) {
            $this->flashMessage('danger', $e->getMessage());

            return $this->webService->getRedirectResponse('productimage/index');
        }
    }

    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        ProductImageRepository $productimageRepository,
        ProductRepository $productRepository,
    ): Response {
        $productImage = $this->productimage($currentRoute, $productimageRepository);
        if ($productImage) {
            $product_id = $productImage->getProduct_id();
            $form       = new ProductImageForm($productImage, (int) $product_id);
            $parameters = [
                'title'           => $this->translator->translate('edit'),
                'actionName'      => 'productimage/edit',
                'actionArguments' => ['id' => $productImage->getId()],
                'errors'          => [],
                'form'            => $form,
                'products'        => $productRepository->findAllPreloaded(),
            ];
            if (Method::POST === $request->getMethod()) {
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    $body = $request->getParsedBody() ?? [];
                    if (is_array($body)) {
                        $this->productimageService->saveProductImage($productImage, $body);

                        return $this->webService->getRedirectResponse('productimage/index');
                    }
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form']   = $form;
            }

            return $this->viewRenderer->render('_form', $parameters);
        }

        return $this->webService->getRedirectResponse('productimage/index');
    }

    public function view(CurrentRoute $currentRoute, ProductImageRepository $productimageRepository): \Yiisoft\DataResponse\DataResponse|Response
    {
        $productImage = $this->productimage($currentRoute, $productimageRepository);
        if ($productImage) {
            $parameters = [
                'title'           => $this->translator->translate('view'),
                'actionName'      => 'productimage/view',
                'actionArguments' => ['id' => $productImage->getId()],
                'form'            => new ProductImageForm($productImage, (int) $productImage->getProduct_id()),
                'productimage'    => $productimageRepository->repoProductImagequery($productImage->getId()),
            ];

            return $this->viewRenderer->render('_view', $parameters);
        }

        return $this->webService->getRedirectResponse('productimage/index');
    }

    public function productimage(CurrentRoute $currentRoute, ProductImageRepository $productimageRepository): ?ProductImage
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $productimageRepository->repoProductImagequery($id);
        }

        return null;
    }

    /**
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function productimages(ProductImageRepository $productimageRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $productimageRepository->findAllPreloaded();
    }

    /**
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
