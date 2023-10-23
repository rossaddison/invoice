<?php

declare(strict_types=1); 

namespace App\Invoice\Contract;

use App\Invoice\Entity\Contract;
use App\Invoice\Contract\ContractService;
use App\Invoice\Contract\ContractRepository as contractR;
use App\Invoice\Client\ClientRepository as cR;
use App\Invoice\Inv\InvRepository as iR;
use App\Invoice\Setting\SettingRepository as sR;
use App\User\UserService;
use App\Service\WebControllerService;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Yiisoft
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Form\FormHydrator;
use Yiisoft\Form\Helper\HtmlFormErrors;
use Yiisoft\Yii\View\ViewRenderer;

use \Exception;
use Yiisoft\Data\Paginator\OffsetPaginator;

final class ContractController
{
    private SessionInterface $session;
    private Flash $flash;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private ContractService $contractService;
    private const CONTRACTS_PER_PAGE = 1;
    private TranslatorInterface $translator;
    
    public function __construct(
        SessionInterface $session,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        ContractService $contractService,
        TranslatorInterface $translator
    )    
    {
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/contract')
                                           // The Controller layout dir is now redundant: replaced with an alias 
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->userService = $userService;
        $this->contractService = $contractService;
        $this->translator = $translator;
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param contractR $contractR
     * @param Request $request
     * @param cR $cR
     * @param iR $iR
     * @param sR $sR
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function index(CurrentRoute $currentRoute, contractR $contractR, Request $request, cR $cR, iR $iR, sR $sR): \Yiisoft\DataResponse\DataResponse
    {
        $canEdit = $this->rbac(); 
        $query_params = $request->getQueryParams();
        /** @var string $query_params['sort'] */
        $page = (int)$currentRoute->getArgument('page', '1');
        $sort = Sort::only(['id', 'client_id', 'name', 'reference'])
                    // (@see vendor\yiisoft\data\src\Reader\Sort
                    // - => 'desc'  so -id => default descending on id
                    // Show the latest quotes first => -id
                    ->withOrderString($query_params['sort'] ?? '-id');
        $contracts = $this->contracts_with_sort($contractR, $sort); 
        $this->flash_message('info',$this->translator->translate('invoice.invoice.contract.create'));
        $paginator = (new OffsetPaginator($contracts))
        ->withPageSize((int)$sR->get_setting('default_list_limit'))
        ->withCurrentPage($page)
        ->withNextPageToken((string) $page); 
        $parameters = [
            'alert' => $this->alert(),
            'paginator'=>$paginator,
            'canEdit' => $canEdit,
            'cR' => $cR,
            // Use the invoice Repository to retrieve all invoices associated with this contract
            'iR' => $iR,
            'grid_summary'=> $sR->grid_summary($paginator, 
                                               $this->translator, 
                                               (int)$sR->get_setting('default_list_limit'), 
                                               $this->translator->translate('invoice.invoice.contracts'), ''),
            'contracts' => $this->contracts($contractR),
        ]; 
        return $this->viewRenderer->render('index', $parameters);
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param ViewRenderer $head
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param sR $settingRepository
     * @return Response
     */
    public function add(CurrentRoute $currentRoute, ViewRenderer $head, Request $request, 
                       FormHydrator $formHydrator,
                       sR $settingRepository
    ) : Response
    {   
        $parameters = [
            'title' => $this->translator->translate('invoice.invoice.contract.add'),
            'action' => ['contract/add',['client_id'=> $currentRoute->getArgument('client_id')]],
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=> $settingRepository,
            'head'=>$head,
            'client_id'=> $currentRoute->getArgument('client_id')
        ];
        
        if ($request->getMethod() === Method::POST) {
            $form = new ContractForm();
            if ($formHydrator->populate($form, $parameters['body']) && $form->isValid()) {
                $this->contractService->bothContract(new Contract(),$form, $settingRepository);
                return $this->webService->getRedirectResponse('contract/index');
            }
            $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
    
    /**
     * 
     * @param ViewRenderer $head
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param contractR $contractRepository
     * @param sR $settingRepository
     * @return Response
     */
    public function edit(ViewRenderer $head, Request $request, CurrentRoute $currentRoute, 
                         FormHydrator $formHydrator,
                         contractR $contractRepository, 
                         sR $settingRepository
    ): Response {
        $contract = $this->contract($currentRoute, $contractRepository);
        if ($contract){
            $parameters = [
                'title' => $settingRepository->trans('edit'),
                'action' => ['contract/edit', ['id' => $contract->getId()]],
                'errors' => [],
                'body' => $this->body($contract),
                'head'=>$head,
                's'=>$settingRepository
            ];
            if ($request->getMethod() === Method::POST) {
                $form = new ContractForm();
                $body = $request->getParsedBody();
                if ($formHydrator->populate($form, $body) && $form->isValid()) {
                    $this->contractService->bothContract($contract, $form, $settingRepository);
                    return $this->webService->getRedirectResponse('contract/index');
                }
                $parameters['body'] = $body;
                $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('contract/index');
    }
    
    /**
     * @return Response|true
     */
    private function rbac(): bool|Response {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit){
            $this->flash_message('warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('contract/index');
        }
        return $canEdit;
    }
    
    /**
     * 
     * @param sR $settingRepository
     * @param CurrentRoute $currentRoute
     * @param contractR $contractRepository
     * @return Response
     */
    public function delete(sR $settingRepository, CurrentRoute $currentRoute,contractR $contractRepository 
    ): Response {
        try {
            $contract = $this->contract($currentRoute, $contractRepository);
            if ($contract) {
                $this->contractService->deleteContract($contract);               
                $this->flash_message('info', $settingRepository->trans('record_successfully_deleted'));
                return $this->webService->getRedirectResponse('contract/index'); 
            }
            return $this->webService->getRedirectResponse('contract/index'); 
	} catch (Exception $e) {
            $this->flash_message('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('contract/index'); 
        }
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param contractR $contractRepository
     * @param sR $settingRepository
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function view(CurrentRoute $currentRoute, contractR $contractRepository,
        sR $settingRepository,
        ): \Yiisoft\DataResponse\DataResponse|Response {
        $contract = $this->contract($currentRoute, $contractRepository); 
        if ($contract) {
            $parameters = [
                'title' => $settingRepository->trans('view'),
                'action' => ['contract/view', ['id' => $contract->getId()]],
                'errors' => [],
                'body' => $this->body($contract),
                'contract'=>$contract,
            ];        
        return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('contract/index');
    }
    
    //For rbac refer to AccessChecker    
    
    /**
     * @param CurrentRoute $currentRoute
     * @param contractR $contractRepository
     * @return Contract|null
     */
    private function contract(CurrentRoute $currentRoute, contractR $contractRepository) : Contract|null
    {
        $id = $currentRoute->getArgument('id');       
        if (null!==$id) {
            $contract = $contractRepository->repoContractquery($id);
            return $contract;
        }
        return null;
    }
    
    /**
     * 
     * @param contractR $contractRepository
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function contracts(contractR $contractRepository) : \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
    {
        $contracts = $contractRepository->findAllPreloaded();        
        return $contracts;
    }
    
    /**
     * @param contractR $cR
     * @param Sort $sort
     *
     * @return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface
     *
     * @psalm-return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface<int, Contract>
     */
    private function contracts_with_sort(contractR $cR, Sort $sort): \Yiisoft\Data\Reader\SortableDataInterface {       
        $contracts = $cR->findAllPreloaded()
                       ->withSort($sort);
        return $contracts;
    }
    
    /**
     * @param Contract $contract 
     * @return array
     */
    private function body(Contract $contract) : array {
        $body = [
          'id'=>$contract->getId(),
          'reference'=>$contract->getReference(),
          'name'=>$contract->getName(),
          'period_start'=>$contract->getPeriod_start(),
          'period_end'=>$contract->getPeriod_end(),
          'client_id'=>$contract->getClient()?->getClient_id()
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

