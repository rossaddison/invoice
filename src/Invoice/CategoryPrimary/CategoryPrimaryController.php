<?php

declare(strict_types=1);

namespace App\Invoice\CategoryPrimary;

use App\Invoice\BaseController;
use App\Invoice\Entity\CategoryPrimary;
use App\Invoice\Setting\SettingRepository as sR;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class CategoryPrimaryController extends BaseController
{
    protected string $controllerName = 'invoice/categoryprimary';

    public function __construct(
        private CategoryPrimaryService $categoryPrimaryService,
        private DataResponseFactoryInterface $factory,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR, $flash);
        $this->categoryPrimaryService = $categoryPrimaryService;
        $this->factory                = $factory;
    }

    public function add(
        Request $request,
        FormHydrator $formHydrator,
    ): Response {
        $categoryPrimary = new CategoryPrimary();
        $form            = new CategoryPrimaryForm($categoryPrimary);
        $parameters      = [
            'title'           => $this->translator->translate('add'),
            'actionName'      => 'categoryprimary/add',
            'actionArguments' => [],
            'errors'          => [],
            'form'            => $form,
        ];

        if (Method::POST === $request->getMethod()) {
            $body = $request->getParsedBody() ?? [];
            if (is_array($body)) {
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    $this->categoryPrimaryService->saveCategoryPrimary($categoryPrimary, $body);

                    return $this->webService->getRedirectResponse('categoryprimary/index');
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form']   = $form;
            } // is_array($body)
        }

        return $this->viewRenderer->render('_form', $parameters);
    }

    public function index(
        CategoryPrimaryRepository $categoryPrimaryRepository,
        sR $settingRepository,
        #[RouteArgument('page')]
        int $page = 1,
    ): Response {
        $categoryPrimary = $categoryPrimaryRepository->findAllPreloaded();
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = $page > 0 ? $page : 1;
        $paginator            = (new OffsetPaginator($categoryPrimary))
            ->withPageSize($settingRepository->positiveListLimit())
            ->withCurrentPage($currentPageNeverZero)
            ->withToken(PageToken::next((string) $page));
        $parameters = [
            'categoryprimarys'               => $this->categoryprimaries($categoryPrimaryRepository),
            'paginator'                      => $paginator,
            'alert'                          => $this->alert(),
            'defaultPageSizeOffsetPaginator' => $settingRepository->getSetting('default_list_limit')
                                                          ? (int) $settingRepository->getSetting('default_list_limit') : 1,
        ];

        return $this->viewRenderer->render('index', $parameters);
    }

    public function delete(
        CategoryPrimaryRepository $categoryPrimaryRepository,
        #[RouteArgument('id')]
        int $id,
    ): Response {
        try {
            if ($id) {
                $categoryPrimary = $this->categoryprimary($categoryPrimaryRepository, $id);
                if ($categoryPrimary) {
                    $this->categoryPrimaryService->deleteCategoryPrimary($categoryPrimary);
                    $this->flashMessage('info', $this->translator->translate('record.successfully.deleted'));

                    return $this->webService->getRedirectResponse('categoryprimary/index');
                }
            }

            return $this->webService->getRedirectResponse('categoryprimary/index');
        } catch (\Exception $e) {
            $this->flashMessage('danger', $e->getMessage());

            return $this->webService->getRedirectResponse('categoryprimary/index');
        }
    }

    public function edit(
        Request $request,
        FormHydrator $formHydrator,
        CategoryPrimaryRepository $categoryPrimaryRepository,
        #[RouteArgument('id')]
        int $id,
    ): Response {
        if ($id) {
            $categoryprimary = $this->categoryprimary($categoryPrimaryRepository, $id);
            if ($categoryprimary) {
                $form       = new CategoryPrimaryForm($categoryprimary);
                $parameters = [
                    'title'              => $this->translator->translate('edit'),
                    'actionName'         => 'categoryprimary/edit',
                    'actionArguments'    => ['id' => $id],
                    'errors'             => [],
                    'form'               => $form,
                    'category_primaries' => $categoryPrimaryRepository->findAllPreloaded(),
                ];
                if (Method::POST === $request->getMethod()) {
                    $body = $request->getParsedBody() ?? [];
                    if (is_array($body)) {
                        if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                            $this->categoryPrimaryService->saveCategoryPrimary($categoryprimary, $body);

                            return $this->webService->getRedirectResponse('categoryprimary/index');
                        }
                        $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                        $parameters['form']   = $form;
                    }
                }

                return $this->viewRenderer->render('_form', $parameters);
            }
        }

        return $this->webService->getRedirectResponse('categoryprimary/index');
    }

    private function categoryprimary(CategoryPrimaryRepository $categoryPrimaryRepository, int $id): ?CategoryPrimary
    {
        if ($id) {
            $categoryPrimary = $categoryPrimaryRepository->repoCategoryPrimaryQuery((string) $id);
            if (null !== $categoryPrimary) {
                return $categoryPrimary;
            }
        }

        return null;
    }

    /**
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function categoryprimaries(CategoryPrimaryRepository $categoryPrimaryRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $categoryPrimaryRepository->findAllPreloaded();
    }

    /**
     * @param int id
     */
    public function view(CategoryPrimaryRepository $categoryPrimaryRepository, #[RouteArgument('id')] int $id): \Yiisoft\DataResponse\DataResponse|Response
    {
        if ($id) {
            $category_primary = $this->categoryprimary($categoryPrimaryRepository, $id);
            if ($category_primary) {
                $form       = new CategoryPrimaryForm($category_primary);
                $parameters = [
                    'title'            => $this->translator->translate('view'),
                    'actionName'       => 'categoryprimary/view',
                    'actionArguments'  => ['id' => $id],
                    'form'             => $form,
                    'category_primary' => $category_primary,
                ];

                return $this->viewRenderer->render('_view', $parameters);
            }
        }

        return $this->webService->getRedirectResponse('categoryprimary/index');
    }
}
