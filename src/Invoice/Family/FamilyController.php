<?php

declare(strict_types=1);

namespace App\Invoice\Family;

use App\Invoice\BaseController;
use App\Invoice\Entity\Family;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\CategoryPrimary\CategoryPrimaryRepository as cpR;
use App\Invoice\CategorySecondary\CategorySecondaryRepository as csR;
use App\Invoice\Family\FamilyRepository as fR;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Json\Json;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class FamilyController extends BaseController
{
    protected string $controllerName = 'invoice/family';

    public function __construct(
        private FamilyService $familyService,
        private DataResponseFactoryInterface $factory,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR);
        $this->familyService = $familyService;
        $this->factory = $factory;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param FamilyRepository $familyRepository
     * @param cpR $cpR
     * @param csR $csR
     */
    public function index(
        CurrentRoute $currentRoute,
        fR $familyRepository,
        cpR $cpR,
        csR $csR
    ): \Yiisoft\DataResponse\DataResponse {
        $familys = $this->familys($familyRepository);
        $pageNum = (int)$currentRoute->getArgument('page', '1');
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = $pageNum > 0 ? $pageNum : 1;
        $paginator = (new OffsetPaginator($familys))
            ->withPageSize($this->sR->positiveListLimit())
            ->withCurrentPage($currentPageNeverZero);
        $parameters = [
            'alert' => $this->alert(),
            'familys' => $familys,
            'paginator' => $paginator,
            /**
             * The family repository does not include a loaded query
             * because there are no relations (for backward compatibility purposes) therefore
             * pass the dependent repositories so that we can identify
             * the respective names of each repository.
             */
            'cpR' => $cpR,
            'csR' => $csR,
            'defaultPageSizeOffsetPaginator' => (int)$this->sR->getSetting('default_list_limit'),
        ];
        return $this->viewRenderer->render('index', $parameters);
    }

    /**
     * Build a 3 tiered dependency drop down search form
     * @param cpR $cpR
     * @param csR $csR
     * @return Response
     */
    public function search(cpR $cpR, csR $csR): Response
    {
        $family = new Family();
        $form = new FamilyForm($family);
        $parameters = [
            'title' => $this->translator->translate('invoice.search.family'),
            'form' => $form,
            'actionName' => 'family/search',
            'actionArguments' => [],
            'categoryPrimaries' => $cpR->optionsDataCategoryPrimaries(),
            'categorySecondaries' => [],
            'familyNames' => [],
        ];
        return $this->viewRenderer->render('_search', $parameters);
    }

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param cpR $cpR
     * @param csR $csR
     * @return Response
     */
    public function add(Request $request, FormHydrator $formHydrator, cpR $cpR, csR $csR): Response
    {
        $family = new Family();
        $form = new FamilyForm($family);
        $parameters = [
            'title' => $this->translator->translate('i.add_family'),
            'actionName' => 'family/add',
            'actionArguments' => [],
            'categoryPrimaries' => $cpR->optionsDataCategoryPrimaries(),
            'categorySecondaries' => $csR->optionsDataCategorySecondaries(),
            'errors' => [],
            'form' => $form,
        ];
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody() ?? [];
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                if (is_array($body)) {
                    $this->familyService->saveFamily($family, $body);
                    return $this->webService->getRedirectResponse('family/index');
                }
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form'] = $form;
        }
        return $this->viewRenderer->render('_form', $parameters);
    }

    /**
     * @param Request $request
     * @param fR $fR
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function names(Request $request, fR $fR): \Yiisoft\DataResponse\DataResponse
    {
        $queryParams = $request->getQueryParams();

        $categorySecondaryId = (string)$queryParams['category_secondary_id'];

        if ($categorySecondaryId) {
            $familyNames = $fR->optionsDataFamilyNamesWithCategorySecondaryId($categorySecondaryId);

            $parameters = [
                'success' => 1,
                'family_names' => $familyNames,
            ];
            return $this->factory->createResponse(Json::encode($parameters));
        }

        $parameters = [
            'success' => 0,
        ];

        //return response to family.js
        return $this->factory->createResponse(Json::encode($parameters));
    }

    /**
     * @param Request $request
     * @param csR $csR
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function secondaries(Request $request, csR $csR): \Yiisoft\DataResponse\DataResponse
    {
        $queryParams = $request->getQueryParams();

        $categoryPrimaryId = (string)$queryParams['category_primary_id'];

        if ($categoryPrimaryId) {
            $secondaryCategories = $csR->optionsDataCategorySecondariesWithCategoryPrimaryId($categoryPrimaryId);

            $parameters = [
                'success' => 1,
                'secondary_categories' => $secondaryCategories,
            ];
            return $this->factory->createResponse(Json::encode($parameters));
        }

        $parameters = [
            'success' => 0,
        ];

        //return response to family.js to reload page at location
        return $this->factory->createResponse(Json::encode($parameters));
    }

    /**
     * @param string $id
     * @param Request $request
     * @param FamilyRepository $familyRepository
     * @param cpR $cpR
     * @param csR $csR
     * @param FormHydrator $formHydrator
     * @return Response
     */
    public function edit(#[RouteArgument('id')] string $id, Request $request, fR $familyRepository, cpR $cpR, csR $csR, FormHydrator $formHydrator): Response
    {
        $family = $this->family($id, $familyRepository);
        if ($family) {
            $form = new FamilyForm($family);
            $parameters = [
                'title' => $this->translator->translate('i.edit'),
                'actionName' => 'family/edit',
                'actionArguments' => ['id' => $family->getFamily_id()],
                'categoryPrimaries' => $cpR->optionsDataCategoryPrimaries(),
                'categorySecondaries' => $csR->optionsDataCategorySecondaries(),
                'errors' => [],
                'form' => $form,
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    if (is_array($body)) {
                        $this->familyService->saveFamily($family, $body);
                        return $this->webService->getRedirectResponse('family/index');
                    }
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('family/index');
    }

    /**
     * @param string $id
     * @param FamilyRepository $familyRepository
     * @return Response
     */
    public function delete(#[RouteArgument('id')] string $id, fR $familyRepository): Response
    {
        try {
            $family = $this->family($id, $familyRepository);
            if ($family) {
                $this->familyService->deleteFamily($family);
                return $this->webService->getRedirectResponse('family/index');
            }
            return $this->webService->getRedirectResponse('family/index');
        } catch (\Exception $e) {
            unset($e);
            $this->flashMessage('danger', $this->translator->translate('invoice.family.history'));
            return $this->webService->getRedirectResponse('family/index');
        }
    }

    /**
     * @param string $id
     * @param FamilyRepository $familyRepository
     * @param cpR $cpR
     * @param csR $csR
     */
    public function view(#[RouteArgument('id')] string $id, fR $familyRepository, cpR $cpR, csR $csR): Response
    {
        $family = $this->family($id, $familyRepository);
        if ($family) {
            $form = new FamilyForm($family);
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'actionName' => 'family/view',
                'actionArguments' => ['id' => $family->getFamily_id()],
                'categoryPrimaries' => $cpR->optionsDataCategoryPrimaries(),
                'categorySecondaries' => $csR->optionsDataCategorySecondaries(),
                'errors' => [],
                'family' => $family,
                'form' => $form,
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('family/index');
    }

    /**
     * @param string $id
     * @param FamilyRepository $familyRepository
     * @return Family|null
     */
    private function family(#[RouteArgument('id')] string $id, fR $familyRepository): Family|null
    {
        return $familyRepository->repoFamilyquery($id);
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function familys(fR $familyRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $familyRepository->findAllPreloaded();
    }
}
