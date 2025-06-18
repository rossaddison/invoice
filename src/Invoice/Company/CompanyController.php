<?php

declare(strict_types=1);

namespace App\Invoice\Company;

use App\Invoice\BaseController;
use App\Invoice\Entity\Company;
use App\Invoice\Setting\SettingRepository as sR;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class CompanyController extends BaseController
{
    protected string $controllerName = 'invoice/company';

    public function __construct(
        private CompanyService $companyService,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        Flash $flash
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR, $flash);
        $this->companyService = $companyService;
    }

    /**
     * @param CompanyRepository $companyRepository
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function index(CompanyRepository $companyRepository): \Yiisoft\DataResponse\DataResponse
    {
        $this->rbac();
        $companies = $this->companies($companyRepository);
        $paginator = (new OffsetPaginator($companies))
                         ->withPageSize($this->sR->positiveListLimit());
        $parameters = [
            'paginator' => $paginator,
            'company_public' => $this->translator->translate('invoice.company.public'),
            'alert' => $this->alert(),
        ];
        return $this->viewRenderer->render('index', $parameters);
    }

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @return Response
     */
    public function add(
        Request $request,
        FormHydrator $formHydrator,
    ): Response {
        $body = $request->getParsedBody() ?? [];
        $form = new CompanyForm(new Company());
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'actionName' => 'company/add',
            'actionArguments' => [],
            'errors' => [],
            'form' => $form,
            'companyPublic' => $this->translator->translate('invoice.company.public'),
        ];

        if ($request->getMethod() === Method::POST) {
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                if (is_array($body)) {
                    $this->companyService->saveCompany(new Company(), $body);
                    return $this->webService->getRedirectResponse('company/index');
                }
            }
            $parameters['form'] = $form;
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
        }
        return $this->viewRenderer->render('_form', $parameters);
    }

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param CompanyRepository $companyRepository
     * @param CurrentRoute $currentRoute
     * @return Response
     */
    public function edit(
        Request $request,
        FormHydrator $formHydrator,
        CompanyRepository $companyRepository,
        CurrentRoute $currentRoute
    ): Response {
        $company = $this->company($currentRoute, $companyRepository);
        if ($company) {
            $form = new CompanyForm($company);
            $parameters = [
                'title' => $this->translator->translate('i.edit'),
                'actionName' => 'company/edit',
                'actionArguments' => ['id' => $company->getId()],
                'errors' => [],
                'form' => $form,
                'companyPublic' => $this->translator->translate('invoice.company.public'),
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    if (is_array($body)) {
                        $this->companyService->saveCompany($company, $body);
                        return $this->webService->getRedirectResponse('company/index');
                    }
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('company/index');
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param CompanyRepository $companyRepository
     * @return Response
     */
    public function delete(
        CurrentRoute $currentRoute,
        CompanyRepository $companyRepository
    ): Response {
        $company = $this->company($currentRoute, $companyRepository);
        if ($company) {
            if ($this->companyService->deleteCompany($company)) {
                $this->flashMessage('info', $this->translator->translate('invoice.company.deleted'));
            }
        }
        return $this->webService->getRedirectResponse('company/index');
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param CompanyRepository $companyRepository
     * @return Response
     */
    public function view(
        CurrentRoute $currentRoute,
        CompanyRepository $companyRepository
    ): Response {
        $company = $this->company($currentRoute, $companyRepository);
        if ($company) {
            $form = new CompanyForm($company);
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'actionName' => 'company/view',
                'actionArguments' => ['id' => $company->getId()],
                'companyPublic' => $this->translator->translate('invoice.company.public'),
                'form' => $form,
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('company/index');
    }

    /**
     * @return Response|true
     */
    private function rbac(): bool|Response
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit) {
            $this->flashMessage('warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('company/index');
        }
        return $canEdit;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param CompanyRepository $companyRepository
     * @return Company|null
     */
    private function company(CurrentRoute $currentRoute, CompanyRepository $companyRepository): Company|null
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $companyRepository->repoCompanyquery($id);
        }
        return null;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function companies(CompanyRepository $companyRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $companyRepository->findAllPreloaded();
    }
}
