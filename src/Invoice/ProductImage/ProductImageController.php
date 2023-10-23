<?php

declare(strict_types=1);

namespace App\Invoice\ProductImage;

use App\Invoice\Entity\ProductImage;
use App\Invoice\ProductImage\ProductImageForm;
use App\Invoice\ProductImage\ProductImageService;
use App\Invoice\ProductImage\ProductImageRepository;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Product\ProductRepository;
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
use Yiisoft\Form\FormHydrator;
use Yiisoft\Form\Helper\HtmlFormErrors;
use Yiisoft\Yii\View\ViewRenderer;
use \Exception;

final class ProductImageController {
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
     *
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param ProductImageRepository $productimageRepository
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function index(Request $request, CurrentRoute $currentRoute, ProductImageRepository $productimageRepository): \Yiisoft\DataResponse\DataResponse {
        /** @var string $query_params['sort'] */
        $page = (int) $currentRoute->getArgument('page', '1');
        $query_params = $request->getQueryParams();
        $sort = Sort::only(['id', 'product_id', 'file_name_original'])
                // (@see vendor\yiisoft\data\src\Reader\Sort
                // - => 'desc'  so -id => default descending on id
                // Show the latest uploads first => -id
                ->withOrderString($query_params['sort'] ?? '-id');
        $productimages = $this->productimages_with_sort($productimageRepository, $sort);
        $paginator = (new OffsetPaginator($productimages))
                ->withPageSize((int) $this->s->get_setting('default_list_limit'))
                ->withCurrentPage($page)
                ->withNextPageToken((string) $page);

        $parameters = [
            'paginator' => $paginator,
            'grid_summary' => $this->s->grid_summary($paginator, $this->translator, (int) $this->s->get_setting('default_list_limit'), $this->translator->translate('invoice.productimage.plural'), ''),
            'productimages' => $this->productimages($productimageRepository),
            'alert' => $this->alert()
        ];
        return $this->viewRenderer->render('index', $parameters);
    }

    /**
     * @param ViewRenderer $head
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param ProductRepository $productRepository
     * @return Response
     */
    public function add(ViewRenderer $head, Request $request,
            FormHydrator $formHydrator,
            ProductRepository $productRepository
    ): Response {
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['productimage/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            'head' => $head,
            'products' => $productRepository->findAllPreloaded(),
        ];

        if ($request->getMethod() === Method::POST) {
            $form = new ProductImageForm();
            $productimage = new ProductImage();
            if ($formHydrator->populate($form, $parameters['body']) && $form->isValid()) {
                $this->productimageService->saveProductImage($productimage, $form);
                return $this->webService->getRedirectResponse('productimage/index');
            }
            $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
        }
        return $this->viewRenderer->render('_form', $parameters);
    }

    /**
     * @return string
     */
    private function alert(): string {
      return $this->viewRenderer->renderPartialAsString('/invoice/layout/alert',
      [ 
        'flash' => $this->flash,
        'errors' => [],
      ]);
    }

    /**
     * @param string $level
     * @param string $message
     * @return Flash
     */
    private function flash_message(string $level, string $message): Flash {
      $this->flash->add($level, $message, true);
      return $this->flash;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param ProductImageRepository $productimageRepository
     * @param SettingRepository $settingRepository
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute,
            ProductImageRepository $productimageRepository,
            SettingRepository $settingRepository
    ): Response {
        try {
            $productimage = $this->productimage($currentRoute, $productimageRepository);
            if ($productimage) {
                $this->productimageService->deleteProductImage($productimage, $settingRepository);
                $product_id = (string) $productimage->getProduct()?->getProduct_id();
                $this->flash_message('info', $settingRepository->trans('record_successfully_deleted'));
                return $this->factory->createResponse($this->viewRenderer->renderPartialAsString('/invoice/setting/inv_message',
                                        ['heading' => '', 'message' => $settingRepository->trans('record_successfully_deleted'), 'url' => 'product/view', 'id' => $product_id]));
            }
            return $this->webService->getRedirectResponse('productimage/index');
        } catch (Exception $e) {
            $this->flash_message('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('productimage/index');
        }
    }

    /**
     * @param ViewRenderer $head
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param ProductImageRepository $productimageRepository
     * @param SettingRepository $settingRepository
     * @param ProductRepository $productRepository
     * @return Response
     */
    public function edit(ViewRenderer $head, 
            Request $request, CurrentRoute $currentRoute,
            FormHydrator $formHydrator,
            ProductImageRepository $productimageRepository,
            SettingRepository $settingRepository,
            ProductRepository $productRepository
    ): Response {
        $productimage = $this->productimage($currentRoute, $productimageRepository);
        if ($productimage) {
            $parameters = [
                'title' => $settingRepository->trans('edit'),
                'action' => ['productimage/edit', ['id' => $productimage->getId()]],
                'errors' => [],
                'body' => $this->body($productimage),
                'head' => $head,
                'products' => $productRepository->findAllPreloaded()
            ];
            if ($request->getMethod() === Method::POST) {
                $form = new ProductImageForm();
                $body = $request->getParsedBody();
                if ($formHydrator->populate($form, $body) && $form->isValid()) {
                    $this->productimageService->saveProductImage($productimage, $form);
                    return $this->webService->getRedirectResponse('productimage/index');
                }
                $parameters['body'] = $body;
                $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('productimage/index');
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param ProductImageRepository $productimageRepository
     * @param SettingRepository $settingRepository
     */
    public function view(CurrentRoute $currentRoute, ProductImageRepository $productimageRepository,
            SettingRepository $settingRepository,
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $productimage = $this->productimage($currentRoute, $productimageRepository);
        if ($productimage) {
            $parameters = [
                'alert' => $this->alert(),
                'title' => $settingRepository->trans('view'),
                'action' => ['productimage/view', ['id' => $productimage->getId()]],
                'errors' => [],
                'body' => $this->body($productimage),
                's' => $settingRepository,
                'productimage' => $productimageRepository->repoProductImagequery($productimage->getId()),
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
    public function productimage(CurrentRoute $currentRoute, ProductImageRepository $productimageRepository): ProductImage|null {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            $productimage = $productimageRepository->repoProductImagequery($id);
            return $productimage;
        }
        return null;
    }

    /**
     * @param ProductImageRepository $productimageRepository
     *
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function productimages(ProductImageRepository $productimageRepository): \Yiisoft\Yii\Cycle\Data\Reader\EntityReader {
        $productimages = $productimageRepository->findAllPreloaded();
        return $productimages;
    }

    /**
     * @param ProductImage $productimage
     * @return array
     */
    private function body(ProductImage $productimage): array {
        $body = [
            'id' => $productimage->getId(),
            'product_id' => $productimage->getProduct_id(),
            'file_name_original' => $productimage->getFile_name_original(),
            'file_name_new' => $productimage->getFile_name_new(),
            'description' => $productimage->getDescription(),
            'uploaded_date' => $productimage->getUploaded_date()
        ];
        return $body;
    }

    /**
     * @param ProductImageRepository $productimageRepository
     * @param Sort $sort
     *
     * @return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface
     *
     * @psalm-return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface<int, ProductImage>
     */
    private function productimages_with_sort(ProductImageRepository $productimageRepository, Sort $sort): \Yiisoft\Data\Reader\SortableDataInterface {
        $productimages = $productimageRepository->findAllPreloaded()
                ->withSort($sort);
        return $productimages;
    }
}
