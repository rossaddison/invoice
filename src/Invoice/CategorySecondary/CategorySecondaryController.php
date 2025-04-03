<?php

declare(strict_types=1);

namespace App\Invoice\CategorySecondary;

use App\Invoice\Entity\CategorySecondary;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Traits\FlashMessage;
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
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;
use Exception;

final class CategorySecondaryController
{
    use FlashMessage;

    private Flash $flash;

    public function __construct(
        private ViewRenderer $viewRenderer,
        private WebControllerService $webService,
        private CategorySecondaryService $categorySecondaryService,
        private UserService $userService,
        private DataResponseFactoryInterface $factory,
        private SessionInterface $session,
        private TranslatorInterface $translator,
    ) {
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/categorysecondary')
                                           // The Controller layout dir is now redundant: replaced with an alias
                                           ->withLayout('@invoice/layout/main.php');

        $this->viewRenderer = $viewRenderer;
        if ($this->userService->hasPermission('viewInv') && !$this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice/categorysecondary')
                                               ->withLayout('@views/layout/guest.php');
        }
        if ($this->userService->hasPermission('viewInv') && $this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice/categorysecondary')
                                               ->withLayout('@views/layout/invoice.php');
        }
        $this->webService = $webService;
        $this->flash = new Flash($this->session);
        $this->userService = $userService;
        $this->factory = $factory;
        $this->categorySecondaryService = $categorySecondaryService;
        $this->translator = $translator;
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
            'category_primarys' => $categoryPrimaryRepository->optionsDataCategoryPrimarys(),
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
            } // is_array($body)
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
                'errors' => [],
            ]
        );
    }

    public function index(
        CategorySecondaryRepository $categorySecondaryRepository,
        SettingRepository $settingRepository,
        #[RouteArgument('page')] int $page = 1
    ): Response
    {
        $categorySecondary = $categorySecondaryRepository->findAllPreloaded();
        /** @psalm-var positive-int $currentPageNeverZero */
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

    /**
     * @param CategorySecondaryRepository $categorysecondaryRepository
     * @param int $id
     * @return Response
     */
    public function delete(
        CategorySecondaryRepository $categorysecondaryRepository,
        #[RouteArgument('id')] int $id
    ): Response {
        try {
            $categorysecondary = $this->categorysecondary($categorysecondaryRepository, $id);
            if ($categorysecondary) {
                $this->categorySecondaryService->deleteCategorySecondary($categorysecondary);
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
    ): Response
    {
        $categorySecondary = $this->categorysecondary($categorySecondaryRepository, $id);
        if ($categorySecondary) {
            $form = new CategorySecondaryForm($categorySecondary);
            $parameters = [
                'title' => $this->translator->translate('i.edit'),
                'actionName' => 'categorysecondary/edit',
                'actionArguments' => ['id' => $id],
                'errors' => [],
                'form' => $form,
                'category_primarys' => $categoryPrimaryRepository->optionsDataCategoryPrimarys(),
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

    //For rbac refer to AccessChecker

    /**
     * @param CategorySecondaryRepository $categorysecondaryRepository
     * @param int $id
     * @return CategorySecondary|null
     */
    private function categorysecondary(CategorySecondaryRepository $categorysecondaryRepository, int $id): CategorySecondary|null
    {
        if ($id) {
            return $categorysecondaryRepository->repoCategorySecondaryLoadedQuery((string)$id);
        }
        return null;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function categorysecondarys(CategorySecondaryRepository $categorysecondaryRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $categorysecondaryRepository->findAllPreloaded();
    }

    /**
     * @param CategorySecondaryRepository $categorysecondaryRepository
     * @param SettingRepository $settingRepository
     * @param int id
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function view(
        CategorySecondaryRepository $categorysecondaryRepository,
        CategoryPrimaryRepository $categoryPrimaryRepository,
        #[RouteArgument('id')] int $id
    ): \Yiisoft\DataResponse\DataResponse|Response
    {
        $categorysecondary = $this->categorysecondary($categorysecondaryRepository, $id);
        if ($categorysecondary) {
            $form = new CategorySecondaryForm($categorysecondary);
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'actionName' => 'categorysecondary/view',
                'actionArguments' => ['id' => $id],
                'form' => $form,
                'categorysecondaries' => $categorysecondary,
                'category_primarys' => $categoryPrimaryRepository->optionsDataCategoryPrimarys(),
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('categorysecondary/index');
    }
}
