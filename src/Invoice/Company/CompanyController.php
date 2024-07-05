<?php
declare(strict_types=1); 

namespace App\Invoice\Company;

use App\Invoice\Company\CompanyService;
use App\Invoice\Company\CompanyRepository;
use App\Invoice\Company\CompanyForm;
use App\Invoice\Entity\Company;
use App\Invoice\Setting\SettingRepository;
use App\Service\WebControllerService;
use App\User\UserService;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class CompanyController
{
    private SessionInterface $session;
    private Flash $flash;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private CompanyService $companyService;
    private TranslatorInterface $translator;
        
    public function __construct(
        SessionInterface $session,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        CompanyService $companyService,
        TranslatorInterface $translator
    )    
    {
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/company')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->userService = $userService;
        $this->companyService = $companyService;
        $this->translator = $translator;
    }
    
    /**
     * @param CompanyRepository $companyRepository
     * @param SettingRepository $sR
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function index(CompanyRepository $companyRepository, SettingRepository $sR): \Yiisoft\DataResponse\DataResponse
    {      
        $canEdit = $this->rbac();
        $companies = $this->companies($companyRepository);
        $paginator = (new OffsetPaginator($companies))
                         ->withPageSize((int)$sR->get_setting('default_list_limit'));
        $parameters = [
         'canEdit' => $canEdit,
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
    public function add(Request $request, 
                        FormHydrator $formHydrator,                   
    ): Response
    {
        $body = $request->getParsedBody() ?? [];
        $form = new CompanyForm(new Company());
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['company/add'],
            'errors' => [],
            'form' => $form,
            'company_public' => $this->translator->translate('invoice.company.public'),
        ];
        
        if ($request->getMethod() === Method::POST) {
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                /**
                 * @psalm-suppress PossiblyInvalidArgument $body
                 */
                $this->companyService->saveCompany(new Company(), $body);
                return $this->webService->getRedirectResponse('company/index');
            }
            $parameters['form'] = $form;
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByAttribute();
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
    public function edit(Request $request, 
                         FormHydrator $formHydrator,
                         CompanyRepository $companyRepository,
                         CurrentRoute $currentRoute
    ): Response {
        $company = $this->company($currentRoute, $companyRepository);
        if ($company) {
            $form = new CompanyForm($company);
            $parameters = [
                'title' => $this->translator->translate('i.edit'),
                'action' => ['company/edit', ['id' => $company->getId()]],
                'errors' => [],
                'form' => $form,
                'company_public' => $this->translator->translate('invoice.company.public'),
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody();
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    /**
                     * @psalm-suppress PossiblyInvalidArgument $body
                     */
                    $this->companyService->saveCompany($company, $body);
                    return $this->webService->getRedirectResponse('company/index');
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByAttribute();
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
    public function delete(CurrentRoute $currentRoute, CompanyRepository $companyRepository 
    ): Response {
        $company = $this->company($currentRoute, $companyRepository);
        if ($company) {
            if ($this->companyService->deleteCompany($company)) {               
                $this->flash_message('info', $this->translator->translate('invoice.company.deleted')); 
            }
        }
        return $this->webService->getRedirectResponse('company/index');
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param CompanyRepository $companyRepository
     * @return Response
     */
    public function view(CurrentRoute $currentRoute, CompanyRepository $companyRepository
        ): Response {
        $company = $this->company($currentRoute, $companyRepository);
        if ($company) {
           $form = new CompanyForm($company);
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'action' => ['company/view', ['id' => $company->getId()]],
                'form' => $form,
                'errors' => [],    
                'company' => $companyRepository->repoCompanyquery((string)$company->getId()),
            ];
            return $this->viewRenderer->render('_view', $parameters);
        } else {
            return $this->webService->getRedirectResponse('company/index');
        }
    }
        
    /**
     * @return Response|true
     */
    private function rbac(): bool|Response 
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit){
            $this->flash_message('warning', $this->translator->translate('invoice.permission')); 
            return $this->webService->getRedirectResponse('company/index');
        }
        return $canEdit;
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param CompanyRepository $companyRepository
     * @return Company|null
     */
    private function company(CurrentRoute $currentRoute, CompanyRepository $companyRepository): Company|null 
    {
        $id = $currentRoute->getArgument('id');
        if (null!==$id) { 
            $company = $companyRepository->repoCompanyquery($id);
            return $company;
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
        $companies = $companyRepository->findAllPreloaded();        
        return $companies;
    }
        
  /**
  * @return string
  */
   private function alert(): string {
     return $this->viewRenderer->renderPartialAsString('//invoice/layout/alert',
     [ 
       'flash' => $this->flash,
       'errors' => [],
     ]);
   }

     /**
     * @param string $level
     * @param string $message
     * @return Flash|null
     */
    private function flash_message(string $level, string $message): Flash|null {
        if (strlen($message) > 0) {
            $this->flash->add($level, $message, true);
            return $this->flash;
        }
        return null;
    }
}