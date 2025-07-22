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
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
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
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR, $flash);
        $this->companyService = $companyService;
    }

    public function index(CompanyRepository $companyRepository): \Yiisoft\DataResponse\DataResponse
    {
        $this->rbac();
        $companies = $this->companies($companyRepository);
        $paginator = (new OffsetPaginator($companies))
            ->withPageSize($this->sR->positiveListLimit());
        $parameters = [
            'paginator'      => $paginator,
            'company_public' => $this->translator->translate('company.public'),
            'alert'          => $this->alert(),
        ];

        return $this->viewRenderer->render('index', $parameters);
    }

    public function add(
        Request $request,
        FormHydrator $formHydrator,
    ): Response {
        $body       = $request->getParsedBody() ?? [];
        $form       = new CompanyForm(new Company());
        $parameters = [
            'title'           => $this->translator->translate('add'),
            'actionName'      => 'company/add',
            'actionArguments' => [],
            'errors'          => [],
            'form'            => $form,
            'companyPublic'   => $this->translator->translate('company.public'),
        ];

        if (Method::POST === $request->getMethod()) {
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                if (is_array($body)) {
                    $this->companyService->saveCompany(new Company(), $body);

                    return $this->webService->getRedirectResponse('company/index');
                }
            }
            $parameters['form']   = $form;
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
        }

        return $this->viewRenderer->render('_form', $parameters);
    }

    public function edit(
        Request $request,
        FormHydrator $formHydrator,
        CompanyRepository $companyRepository,
        CurrentRoute $currentRoute,
    ): Response {
        $company = $this->company($currentRoute, $companyRepository);
        if ($company) {
            $form       = new CompanyForm($company);
            $parameters = [
                'title'           => $this->translator->translate('edit'),
                'actionName'      => 'company/edit',
                'actionArguments' => ['id' => $company->getId()],
                'errors'          => [],
                'form'            => $form,
                'companyPublic'   => $this->translator->translate('company.public'),
            ];
            if (Method::POST === $request->getMethod()) {
                $body = $request->getParsedBody() ?? [];
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    if (is_array($body)) {
                        $this->companyService->saveCompany($company, $body);

                        return $this->webService->getRedirectResponse('company/index');
                    }
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form']   = $form;
            }

            return $this->viewRenderer->render('_form', $parameters);
        }

        return $this->webService->getRedirectResponse('company/index');
    }

    public function delete(
        CurrentRoute $currentRoute,
        CompanyRepository $companyRepository,
    ): Response {
        $company = $this->company($currentRoute, $companyRepository);
        if ($company) {
            if ($this->companyService->deleteCompany($company)) {
                $this->flashMessage('info', $this->translator->translate('company.deleted'));
            }
        }

        return $this->webService->getRedirectResponse('company/index');
    }

    public function view(
        CurrentRoute $currentRoute,
        CompanyRepository $companyRepository,
    ): Response {
        $company = $this->company($currentRoute, $companyRepository);
        if ($company) {
            $form       = new CompanyForm($company);
            $parameters = [
                'title'           => $this->translator->translate('view'),
                'actionName'      => 'company/view',
                'actionArguments' => ['id' => $company->getId()],
                'companyPublic'   => $this->translator->translate('company.public'),
                'form'            => $form,
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
            $this->flashMessage('warning', $this->translator->translate('permission'));

            return $this->webService->getRedirectResponse('company/index');
        }

        return $canEdit;
    }

    private function company(CurrentRoute $currentRoute, CompanyRepository $companyRepository): ?Company
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $companyRepository->repoCompanyquery($id);
        }

        return null;
    }

    /**
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function companies(CompanyRepository $companyRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $companyRepository->findAllPreloaded();
    }
}
