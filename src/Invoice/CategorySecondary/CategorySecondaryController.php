<?php

declare(strict_types=1);

namespace App\Invoice\CategorySecondary;

use App\Invoice\BaseController;
use App\Invoice\Entity\CategorySecondary;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\CategoryPrimary\CategoryPrimaryRepository;
use App\User\UserService;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;
use Exception;

final class CategorySecondaryController extends BaseController
{
    protected string $controllerName = 'invoice/categorysecondary';

    public function __construct(
        private CategorySecondaryService $categorySecondaryService,
        private DataResponseFactoryInterface $factory,
        SessionInterface $session,
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        sR $sR,
        TranslatorInterface $translator,
        Flash $flash
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR, $flash);
        $this->categorySecondaryService = $categorySecondaryService;
        $this->factory = $factory;
    }

    public function add(
        Request $request,
        FormHydrator $formHydrator,
        CategoryPrimaryRepository $categoryPrimaryRepository
    ): Response {
        $categorySecondary = new CategorySecondary();
        $form = new CategorySecondaryForm($categorySecondary);
        $parameters = [
            'title' => $this->translator->translate('i.add'),
            'actionName' => 'categorysecondary/add',
            'actionArguments' => [],
            'errors' => [],
            'form' => $form,
            'category_primarys' => $categoryPrimaryRepository->optionsDataCategoryPrimaries(),
        ];

        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody() ?? [];
            if (is_array($body)) {
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    $this->categorySecondaryService->saveCategorySecondary($categorySecondary, $body);
                    return $this->webService->getRedirectResponse('categorysecondary/index');
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
        }
        return $this->viewRenderer->render('_form', $parameters);
    }

    public function index(
        CategorySecondaryRepository $categorySecondaryRepository,
        sR $settingRepository,
        #[RouteArgument('page')] int $page = 1
    ): Response {
        $categorySecondary = $categorySecondaryRepository->findAllPreloaded();
        $currentPageNeverZero = $page > 0 ? $page : 1;
        $paginator = (new OffsetPaginator($categorySecondary))
            ->withPageSize($settingRepository->positiveListLimit())
            ->withCurrentPage($currentPageNeverZero)
            ->withToken(PageToken::next((string)$page));
        $parameters = [
            'categorysecondarys' => $this->categorysecondarys($categorySecondaryRepository),
            'paginator' => $paginator,
            'alert' => $this->alert(),
            'defaultPageSizeOffsetPaginator' => $settingRepository->getSetting('default_list_limit')
                ? (int)$settingRepository->getSetting('default_list_limit') : 1,
        ];
        return $this->viewRenderer->render('index', $parameters);
    }

    public function delete(
        CategorySecondaryRepository $categorySecondaryRepository,
        #[RouteArgument('id')] int $id
    ): Response {
        try {
            $categorySecondary = $this->categorysecondary($categorySecondaryRepository, $id);
            if ($categorySecondary) {
                $this->categorySecondaryService->deleteCategorySecondary($categorySecondary);
                $this->flashMessage('info', $this->translator->translate('i.record_successfully_deleted'));
                return $this->webService->getRedirectResponse('categorysecondary/index');
            }
            return $this->webService->getRedirectResponse('categorysecondary/index');
        } catch (Exception $e) {
            $this->flashMessage('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('categorysecondary/index');
        }
    }

    public function edit(
        Request $request,
        FormHydrator $formHydrator,
        CategorySecondaryRepository $categorySecondaryRepository,
        CategoryPrimaryRepository $categoryPrimaryRepository,
        #[RouteArgument('id')] int $id
    ): Response {
        $categorySecondary = $this->categorysecondary($categorySecondaryRepository, $id);
        if ($categorySecondary) {
            $form = new CategorySecondaryForm($categorySecondary);
            $parameters = [
                'title' => $this->translator->translate('i.edit'),
                'actionName' => 'categorysecondary/edit',
                'actionArguments' => ['id' => $id],
                'category_primarys' => $categoryPrimaryRepository->optionsDataCategoryPrimaries(),
                'errors' => [],
                'form' => $form,
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                        $this->categorySecondaryService->saveCategorySecondary($categorySecondary, $body);
                        return $this->webService->getRedirectResponse('categorysecondary/index');
                    }
                    $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                    $parameters['form'] = $form;
                }
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('categorysecondary/index');
    }

    private function categorysecondary(CategorySecondaryRepository $categorySecondaryRepository, int $id): CategorySecondary|null
    {
        if ($id) {
            return $categorySecondaryRepository->repoCategorySecondaryLoadedQuery((string)$id);
        }
        return null;
    }

    private function categorysecondarys(CategorySecondaryRepository $categorySecondaryRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $categorySecondaryRepository->findAllPreloaded();
    }

    /**
     * @param CategorySecondaryRepository $categorysecondaryRepository
     * @param CategoryPrimaryRepository $categoryPrimaryRepository
     * @param int $id
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function view(
        CategorySecondaryRepository $categorysecondaryRepository,
        CategoryPrimaryRepository $categoryPrimaryRepository,
        #[RouteArgument('id')] int $id
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $categorysecondary = $this->categorysecondary($categorysecondaryRepository, $id);
        if ($categorysecondary) {
            $form = new CategorySecondaryForm($categorysecondary);
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'actionName' => 'categorysecondary/view',
                'actionArguments' => ['id' => $id],
                'form' => $form,
                'categorysecondaries' => $categorysecondary,
                'category_primarys' => $categoryPrimaryRepository->optionsDataCategoryPrimaries(),
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('categorysecondary/index');
    }
}
