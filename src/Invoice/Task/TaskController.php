<?php
declare(strict_types=1); 

namespace App\Invoice\Task;

use App\Invoice\Entity\Task;
use App\Invoice\Entity\InvItem;

use App\Invoice\Helpers\DateHelper;
use App\Invoice\Helpers\NumberHelper;

use App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository as ACIR;
use App\Invoice\InvItemAmount\InvItemAmountService as iiaS;
use App\Invoice\InvItem\InvItemRepository as iiR;
use App\Invoice\InvTaxRate\InvTaxRateRepository as itrR;
use App\Invoice\InvAmount\InvAmountRepository as iaR;
use App\Invoice\Inv\InvRepository as iR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as iiaR;
use App\Invoice\Payment\PaymentRepository as pymR;
use App\Invoice\Project\ProjectRepository as prjctR;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\Task\TaskRepository as tR;
use App\Invoice\TaxRate\TaxRateRepository as trR;

use App\Invoice\Task\TaskService;
use App\Service\WebControllerService;
use App\User\UserService;
use App\Invoice\InvItem\InvItemService;

use App\Invoice\InvItem\InvItemForm;
use App\Invoice\Task\TaskForm;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Json\Json;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\ViewRenderer;
use Yiisoft\FormModel\FormHydrator;

final class TaskController
{
    private Flash $flash;
    private Session $session;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private TaskService $taskService;
    private TranslatorInterface $translator;     
    private DataResponseFactoryInterface $factory;
    private InvItemService $invitemService;
    
    public function __construct(
        Session $session,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        TaskService $taskService,
        TranslatorInterface $translator,
        DataResponseFactoryInterface $responseFactory,
        InvItemService $invitemService
    )    
    {
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/task')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->userService = $userService;
        $this->taskService = $taskService;
        $this->translator = $translator;
        $this->factory = $responseFactory;
        $this->invitemService = $invitemService;
    }
    
    /**
     * @param Request $request
     * @param tR $tR
     * @param DateHelper $dateHelper
     * @param prjctR $prjctR
     */
    public function index(Request $request, tR $tR, DateHelper $dateHelper, prjctR $prjctR, sR $sR) : \Yiisoft\DataResponse\DataResponse
    {            
        $pageNum = (int)$request->getAttribute('page','1');
        $paginator = (new OffsetPaginator($this->tasks($tR)))
        ->withPageSize((int)$sR->get_setting('default_list_limit'))
        ->withCurrentPage($pageNum);      
        $canEdit = $this->rbac();
        $parameters = [
            'paginator' => $paginator,
            'canEdit' => $canEdit,
            'alert' => $this->alert(),
            'prjct' => $prjctR,
            'grid_summary' => $sR->grid_summary($paginator, 
                                                $this->translator, 
                                                (int)$sR->get_setting('default_list_limit'), 
                                                $this->translator->translate('invoice.products'), ''),
            'statuses' => $this->getStatuses($this->translator),
            'tasks' => $this->tasks($tR),
        ];    
        return $this->viewRenderer->render('index', $parameters);  
    }
    
   /**
    * @param Request $request
    * @param FormHydrator $formHydrator
    * @param sR $sR
    * @param prjctR $pR
    * @param trR $trR
    * @return Response
    */
    public function add(Request $request, 
                        FormHydrator $formHydrator,
                        sR $sR,                        
                        prjctR $pR,
                        trR $trR
    ): Response
    {
        $task = new Task();
        $form = new TaskForm($task);
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['task/add'],
            'alert' => $this->alert(),
            'form' => $form,
            'errors' => [],
            'numberhelper'=>new NumberHelper($sR),
            'statuses' => $this->getStatuses($this->translator),
            'taxRates' => $trR->optionsDataTaxRates(),
            'projects' => $pR->optionsDataProjects()     
        ];
        if ($request->getMethod() === Method::POST) {
            if ($formHydrator->populateFromPostAndValidate($form,  $request)) {
                $body = $request->getParsedBody();
                /**
                 * @psalm-suppress PossiblyInvalidArgument $body
                 */
                $this->taskService->saveTask($task, $body);
                $this->flash_message('info', $this->translator->translate('i.record_successfully_created'));
                return $this->webService->getRedirectResponse('task/index');
            }
            $parameters['errors'] = $form->getValidationResult()?->getErrorMessagesIndexedByAttribute() ?? [];
            $parameters['form'] = $form;
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
    
    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param tR $tR
     * @param sR $sR
     * @param prjctR $pR
     * @param trR $trR
     * @return Response
     */
    public function edit(
                        Request $request, 
                        CurrentRoute $currentRoute,
                        FormHydrator $formHydrator,
                        tR $tR, 
                        sR $sR,                        
                        prjctR $pR,
                        trR $trR
    ): Response {
            $task = $this->task($currentRoute, $tR);
            if ($task) {
            $form = new TaskForm($task);    
            $parameters = [
                'title' => $this->translator->translate('i.edit'),
                'action' => ['task/edit', ['id' => $task->getId()]],
                'alert' => $this->alert(),
                'form' => $form,
                'errors' => [],
                'statuses' => $this->getStatuses($this->translator),
                'taxRates' => $trR->optionsDataTaxRates(),
                'projects' => $pR->optionsDataProjects()     
            ];
            if ($request->getMethod() === Method::POST) {
                if ($formHydrator->populateFromPostAndValidate($form,  $request)) {
                    $body = $request->getParsedBody();
                    /**
                     * @psalm-suppress PossiblyInvalidArgument $body
                     */
                    $this->taskService->saveTask($task, $body);
                    $this->flash_message('info', $this->translator->translate('i.record_successfully_updated'));
                    return $this->webService->getRedirectResponse('task/index');
                }
                $parameters['errors'] = $form->getValidationResult()?->getErrorMessagesIndexedByAttribute() ?? [];
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('_form', $parameters);
        }    
        return $this->webService->getRedirectResponse('task/index');
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param tR $tR
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute, tR $tR 
    ): Response {
            $task = $this->task($currentRoute, $tR);
            /** @var Task $task */
            $this->taskService->deleteTask($task); 
            $this->flash_message('info', $this->translator->translate('i.record_successfully_deleted'));
            return $this->webService->getRedirectResponse('task/index'); 	
    }
    
    /**
     * @return string[][]
     *
     * @psalm-return array{1: array{label: string, class: 'draft'}, 2: array{label: string, class: 'viewed'}, 3: array{label: string, class: 'sent'}, 4: array{label: string, class: 'paid'}}
     */
    public function getStatuses(TranslatorInterface $translator): array
    {
        return [
            1 => [
                'label' => $translator->translate('i.not_started'),
                'class' => 'draft'
            ],
            2 => [
                'label' => $translator->translate('i.in_progress'),
                'class' => 'viewed'
            ],
            3 => [
                'label' => $translator->translate('i.complete'),
                'class' => 'sent'
            ],
            4 => [
                'label' => $translator->translate('i.invoiced'),
                'class' => 'paid'
            ]
        ];
    }
    
    //views/invoice/task/modal-task-lookups-inv.php => modal_task_lookups_inv.js $(document).on('click', '.select-items-confirm-inv', function () 
    
    /**
     * @param FormHydrator $formHydrator
     * @param Request $request
     * @param ACIR $aciR
     * @param tR $taskR
     * @param sR $sR
     * @param trR $trR
     * @param iiaR $iiaR
     * @param iiR $iiR
     * @param itrR $itrR
     * @param iaR $iaR
     * @param iR $iR
     * @param pymR $pymR
     */
    public function selection_inv(FormHydrator $formHydrator, Request $request, 
                                  ACIR $aciR, tR $taskR, sR $sR, trR $trR, iiaR $iiaR, iiR $iiR, itrR $itrR, iaR $iaR, iR $iR, pymR $pymR)
                                  : \Yiisoft\DataResponse\DataResponse {        
        $select_items = $request->getQueryParams();
        /** @var array $task_ids */
        $task_ids = ($select_items['task_ids'] ? $select_items['task_ids'] : []);
        $inv_id = (string)$select_items['inv_id'];
        // Use Spiral||Cycle\Database\Injection\Parameter to build 'IN' array of tasks.
        $tasks = $taskR->findinTasks($task_ids);
        $numberHelper = new NumberHelper($sR);
        // Format the task prices according to comma or point or other setting choice.
        $order = 1;
        /** @var Task $task */ 
        foreach ($tasks as $task) {           
            $task->setPrice((float)$numberHelper->format_amount($task->getPrice()));
            $this->save_task_lookup_item_inv($order, $task, $inv_id, $taskR, $trR, $iiaR, $sR, $formHydrator);
            $order++;          
        }
        $numberHelper->calculate_inv((string)$this->session->get('inv_id'), $aciR, $iiR, $iiaR, $itrR, $iaR, $iR, $pymR);
        return $this->factory->createResponse(Json::encode($tasks));        
    }
    
    /**
     * 
     * @param int $order
     * @param Task $task
     * @param string $inv_id
     * @param tR $taskR
     * @param trR $trR
     * @param iiaR $iiaR
     * @param sR $sR
     * @param FormHydrator $formHydrator
     * @return void
     */    
    private function save_task_lookup_item_inv(int $order, Task $task, string $inv_id, tR $taskR, trR $trR, iiaR $iiaR, sR $sR, FormHydrator $formHydrator) : void {
      $invItem = new InvItem();
      $form = new InvItemForm($invItem, (int)$inv_id);
      $ajax_content = [
           'name'=> $task->getName(),        
           'inv_id'=> $inv_id,            
           'tax_rate_id'=> $task->getTax_rate_id(),
           'task_id'=> $task->getId(),
           'product_id'=>null,
           'date_added'=>new \DateTimeImmutable('now'),
           'description'=> $task->getDescription(),
           // A default quantity of 1 is used to initialize the item
           'quantity'=>floatval(1),
           'price'=> $task->getPrice(),
           // The user will determine how much discount to give on this item later
           'discount_amount'=>floatval(0),
           'order'=> $order
      ];
      if ($formHydrator->populate($form, $ajax_content) && $form->isValid()) {
           $this->invitemService->addInvItem_task($invItem, $ajax_content, $inv_id, $taskR, $trR, new iiaS($iiaR), $iiaR, $sR);                 
      }
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param tR $tR
     * @param trR $trR
     * @param prjctR $pR
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function view(CurrentRoute $currentRoute, tR $tR, trR $trR, prjctR $pR
      ): \Yiisoft\DataResponse\DataResponse|Response {
      $task = $this->task($currentRoute, $tR);
      if ($task) {
            $taskForm = new TaskForm($task);
            $parameters = [
               'title' => $this->translator->translate('i.view'),
               'action' => ['task/view', ['id' => $task->getId()]],
               'errors' => [],
               'form' => $taskForm,
               'statuses' => $this->getStatuses($this->translator), 
               'task' => $tR->repoTaskquery($task->getId()),
               'taxRates' => $trR->optionsDataTaxRates(),
               'projects' => $pR->optionsDataProjects()   
            ];
            return $this->viewRenderer->render('_view', $parameters);
      }
      return $this->webService->getRedirectResponse('task/index'); 	
    }
        
    /**
     * @return Response|true
     */
    private function rbac(): bool|Response 
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit){
            $this->flash_message('warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('task/index');
        }
        return $canEdit;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param tR $tR
     * @return Task|null
     */
    private function task(CurrentRoute $currentRoute, tR $tR): Task|null
    {
        $id = $currentRoute->getArgument('id');       
        if (null!==$id) {
            $task = $tR->repoTaskquery($id);
            return $task;
        }
        return null;
    }
    
    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function tasks(tR $tR): \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
    {
        $tasks = $tR->findAllPreloaded();        
        return $tasks;
    }
    
    /**
     * @return string
     */
    private function alert(): string {
        return $this->viewRenderer->renderPartialAsString('/invoice/layout/alert',
        [ 
            'flash' => $this->flash
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