<?php

declare(strict_types=1);

namespace App\Invoice\TaxRate;

use App\Invoice\Entity\TaxRate;
use App\Invoice\Helpers\Peppol\PeppolArrays;
use App\Invoice\TaxRate\TaxRateRepository;
use App\Invoice\Setting\SettingRepository;
use App\Service\WebControllerService;
use App\User\UserService;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Translator\TranslatorInterface as Translator; 
use Yiisoft\Form\FormHydrator;
use Yiisoft\Form\Helper\HtmlFormErrors;
use Yiisoft\Yii\View\ViewRenderer;

final class TaxRateController
{
    private Flash $flash;
    private Session $session;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private TaxRateService $taxrateService;       
    private UserService $userService;
    private Translator $translator;

    public function __construct(
        Session $session,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        TaxRateService $taxrateService,
        UserService $userService,
        Translator $translator,
    ) {
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/taxrate')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->taxrateService = $taxrateService;        
        $this->userService = $userService;
        $this->translator = $translator;
    }
    
    /**
     * @param TaxRateRepository $taxrateRepository
     * @param SettingRepository $settingRepository
     * @param Request $request
     */
    public function index(TaxRateRepository $taxrateRepository, SettingRepository $settingRepository, Request $request): \Yiisoft\DataResponse\DataResponse
    {      
        $pageNum = (int)$request->getAttribute('page', '1');
        $paginator = (new OffsetPaginator($this->taxrates($taxrateRepository)))
        ->withPageSize((int)$settingRepository->get_setting('default_list_limit'))
        ->withCurrentPage($pageNum);
      
        $canEdit = $this->rbac();
        $parameters = [
              'paginator' => $paginator,  
              's'=>$settingRepository,
              'canEdit' => $canEdit,
              'taxrates' => $this->taxrates($taxrateRepository),
              'alert'=> $this->alert()
        ];
        return $this->viewRenderer->render('index', $parameters);
    }
    
    /**
     * 
     * @param ViewRenderer $head
     * @param Request $request
     * @param SettingRepository $settingRepository
     * @param FormHydrator $formHydrator
     * @return Response
     */
    public function add(ViewRenderer $head, Request $request,SettingRepository $settingRepository, FormHydrator $formHydrator): Response
    {
        $peppol_arrays = new PeppolArrays();
        $parameters = [
            'title' => $this->translator->translate('invoice.tax.rate.add'),
            'action' => ['taxrate/add'],
            'head'=>$head,
            'errors' => [],
            'body' => $request->getParsedBody(),
            'peppol_tax_rate_code_array' => $peppol_arrays->getUncl5305(),
            's'=>$settingRepository
        ];
        
        if ($request->getMethod() === Method::POST) {
            $form = new TaxRateForm();
            if ($formHydrator->populate($form, $parameters['body']) && $form->isValid()) {
              $this->taxrateService->saveTaxRate(new TaxRate(), $form);
              $this->flash_message('success', $settingRepository->trans('record_successfully_created'));
              return $this->webService->getRedirectResponse('taxrate/index');
            }
            $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
        }
        return $this->viewRenderer->render('__form', $parameters);
    }
    
    /**
     * 
     * @param ViewRenderer $head
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param SettingRepository $settingRepository
     * @param TaxRateRepository $taxrateRepository
     * @param FormHydrator $formHydrator
     * @return Response
     */
    public function edit(ViewRenderer $head, Request $request, CurrentRoute $currentRoute,
            SettingRepository $settingRepository, TaxRateRepository $taxrateRepository, FormHydrator $formHydrator): Response 
    {
        $taxrate = $this->taxrate($currentRoute, $taxrateRepository);
        $peppol_arrays = new PeppolArrays();
        if ($taxrate) {
            $parameters = [
                'title' => $settingRepository->trans('edit'),
                'action' => ['taxrate/edit', ['tax_rate_id' => $taxrate->getTax_rate_id()]],
                'errors' => [],
                'head'=>$head,
                'translator'=>$this->translator,
                'peppol_tax_rate_code_array' => $peppol_arrays->getUncl5305(),
                'body' => [
                    'tax_rate_code' => $taxrate->getTax_rate_code(),
                    'peppol_tax_rate_code' => $taxrate->getPeppol_tax_rate_code(),
                    'storecove_tax_type' => $taxrate->getStorecove_tax_type(),
                    'tax_rate_name' => $taxrate->getTax_rate_name(),
                    'tax_rate_percent'=> $taxrate->getTax_rate_percent(),
                    'tax_rate_default'=> $taxrate->getTax_rate_default(),
                ],
                's'=>$settingRepository,
            ];
            if ($request->getMethod() === Method::POST) {
                $form = new TaxRateForm();
                $body = $request->getParsedBody();
                if ($formHydrator->populate($form, $body) && $form->isValid()) {
                    $this->taxrateService->saveTaxRate($taxrate, $form);                
                    $this->flash_message('success', $settingRepository->trans('record_successfully_updated'));
                    return $this->webService->getRedirectResponse('taxrate/index');
                }
                $parameters['body'] = $body;
                $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
            }
            return $this->viewRenderer->render('__form', $parameters);
        }
        return $this->webService->getRedirectResponse('taxrate/index'); 
    }    
    
    /**
     * @param CurrentRoute $currentRoute
     * @param TaxRateRepository $taxrateRepository
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute, TaxRateRepository $taxrateRepository): Response 
    {
        try {
            $taxrate = $this->taxrate($currentRoute, $taxrateRepository);
            if ($taxrate) {
                $this->taxrateService->deleteTaxRate($taxrate);               
            }
            return $this->webService->getRedirectResponse('taxrate/index'); 
	} catch (\Exception $e) {
            unset($e);
            $this->flash_message('danger', $this->translator->translate('invoice.tax.rate.history.exists'));
            return $this->webService->getRedirectResponse('taxrate/index');
        } 
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param TaxRateRepository $taxrateRepository
     * @param SettingRepository $settingRepository
     * @param FormHydrator $formHydrator
     */
    public function view(CurrentRoute $currentRoute, TaxRateRepository $taxrateRepository,SettingRepository $settingRepository, FormHydrator $formHydrator)
        : \Yiisoft\DataResponse\DataResponse|Response {
        $taxrate = $this->taxrate($currentRoute, $taxrateRepository);
        if ($taxrate) {
            $parameters = [
                'title' =>  $this->translator->translate('invoice.tax.rate.edit'),
                'action' => ['taxrate/edit', ['tax_rate_id' => $taxrate->getTax_rate_id()]],
                'errors' => [],
                'taxrate'=>$taxrate,
                's'=>$settingRepository,
                'translator'=>$this->translator,
                'body' => [
                    'tax_rate_code' => $taxrate->getTax_rate_code(),
                    'peppol_tax_rate_code' => $taxrate->getPeppol_tax_rate_code(),
                    'tax_rate_id'=>$taxrate->getTax_rate_id(),
                    'tax_rate_name'=>$taxrate->getTax_rate_name(),
                    'tax_rate_percent'=>$taxrate->getTax_rate_percent(),
                    'default'=>$taxrate->getTax_rate_default()
                ],            
            ];
            return $this->viewRenderer->render('__view', $parameters);
        }
        return $this->webService->getRedirectResponse('taxrate/index');     
    }
    
    /**
     * @return Response|true
     */
    private function rbac(): bool|Response {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit){
            $this->flash_message('warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('taxrate/index');
        }
        return $canEdit;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param TaxRateRepository $taxrateRepository
     * @return TaxRate|null
     */
    private function taxrate(CurrentRoute $currentRoute, TaxRateRepository $taxrateRepository): TaxRate|null
    {
        $tax_rate_id = $currentRoute->getArgument('tax_rate_id');
        if (null!==$tax_rate_id) {
            $taxrate = $taxrateRepository->repoTaxRatequery($tax_rate_id);
            return $taxrate; 
        }
        return null;
    }
    
    //$taxrates = $this->taxrates();
    
    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function taxrates(TaxRateRepository $taxrateRepository): \Yiisoft\Yii\Cycle\Data\Reader\EntityReader{
        $taxrates = $taxrateRepository->findAllPreloaded();
        return $taxrates;
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