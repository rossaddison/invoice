<?php
declare(strict_types=1); 

namespace App\Invoice\Company;

use App\Invoice\Company\CompanyService;
use App\Invoice\Company\CompanyRepository;
use App\Invoice\Entity\Company;
use App\Invoice\Setting\SettingRepository;
use App\Service\WebControllerService;
use App\User\UserService;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Form\FormHydrator;
use Yiisoft\Form\Helper\HtmlFormErrors;
use Yiisoft\Yii\View\ViewRenderer;

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
     * @param SettingRepository $settingRepository
     * @param Request $request
     * @param CompanyService $service
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function index(CompanyRepository $companyRepository, SettingRepository $settingRepository, Request $request, CompanyService $service): \Yiisoft\DataResponse\DataResponse
    {      
         $canEdit = $this->rbac();
         $parameters = [
          's'=>$settingRepository,
          'canEdit' => $canEdit,
          'companies' => $this->companies($companyRepository),
          'company_public'=>$this->translator->translate('invoice.company.public'),   
          'alert'=> $this->alert()
         ];
        return $this->viewRenderer->render('index', $parameters);
    }
    
    /**
     * 
     * @param ViewRenderer $head
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param SettingRepository $settingRepository
     * @return Response
     */
    public function add(ViewRenderer $head, Request $request, 
                        FormHydrator $formHydrator,
                        SettingRepository $settingRepository,                   
    ): Response
    {
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['company/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=>$settingRepository,
            'company_public'=>$this->translator->translate('invoice.company.public'),
            'head'=>$head,
            
        ];
        
        if ($request->getMethod() === Method::POST) {
            $form = new CompanyForm();
            if ($formHydrator->populate($form, $parameters['body']) && $form->isValid()) {
                $this->companyService->saveCompany(new Company(),$form);
                return $this->webService->getRedirectResponse('company/index');
            }
            $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
    
    /**
     * 
     * @param ViewRenderer $head
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param CompanyRepository $companyRepository
     * @param SettingRepository $settingRepository
     * @param CurrentRoute $currentRoute
     * @return Response
     */
    public function edit(ViewRenderer $head, Request $request, 
                         FormHydrator $formHydrator,
                         CompanyRepository $companyRepository, 
                         SettingRepository $settingRepository,
                         CurrentRoute $currentRoute

    ): Response {
        $company = $this->company($currentRoute, $companyRepository);
        if ($company) {
            $parameters = [
                'title' => $settingRepository->trans('edit'),
                'action' => ['company/edit', ['id' => $company->getId()]],
                'errors' => [],
                'body' => $this->body($company),
                'head'=>$head,
                'company_public'=>$this->translator->translate('invoice.company.public'),
                's'=>$settingRepository,
            ];
            if ($request->getMethod() === Method::POST) {
                $form = new CompanyForm();
                $body = $request->getParsedBody();
                if ($formHydrator->populate($form, $body) && $form->isValid()) {
                    $this->companyService->saveCompany($company, $form);
                    return $this->webService->getRedirectResponse('company/index');
                }
                $parameters['body'] = $body;
                $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
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
            } else {
                $this->flash_message('warning', $this->translator->translate('invoice.company.not.deleted'));
            } 
        }
        return $this->webService->getRedirectResponse('company/index');
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param CompanyRepository $companyRepository
     * @param SettingRepository $settingRepository
     * @return Response
     */
    public function view(CurrentRoute $currentRoute, CompanyRepository $companyRepository,
        SettingRepository $settingRepository,
        ): Response {
        $company = $this->company($currentRoute, $companyRepository);
        if ($company) {
            $parameters = [
                'title' => $settingRepository->trans('view'),
                'action' => ['company/view', ['id' => $company->getId()]],
                'errors' => [],
                'body' => $this->body($company),
                's'=>$settingRepository,             
                'company'=>$companyRepository->repoCompanyquery((string)$company->getId()),
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
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function companies(CompanyRepository $companyRepository): \Yiisoft\Yii\Cycle\Data\Reader\EntityReader 
    {
        $companies = $companyRepository->findAllPreloaded();        
        return $companies;
    }
    
    /**
     * 
     * @param Company $company
     * @return array
     */
    private function body(Company $company): array {
        $body = [
                
          'id'=>$company->getId(),
          'current'=>$company->getCurrent(),
          'name'=>$company->getName(),
          'address_1'=>$company->getAddress_1(),
          'address_2'=>$company->getAddress_2(),
          'city'=>$company->getCity(),
          'state'=>$company->getState(),
          'zip'=>$company->getZip(),
          'country'=>$company->getCountry(),
          'phone'=>$company->getPhone(),
          'fax'=>$company->getFax(),
          'email'=>$company->getEmail(),
          'web'=>$company->getWeb(),
          'date_created'=>$company->getDate_created(),
          'date_modified'=>$company->getDate_modified()
                ];
        return $body;
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
}

