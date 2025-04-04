<?php

declare(strict_types=1);

namespace App\Invoice\Task;

use App\Invoice\Entity\Task;
use App\Invoice\Entity\InvItem;
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
use App\Invoice\Traits\FlashMessage;
use App\Service\WebControllerService;
use App\User\UserService;
use App\Invoice\InvItem\InvItemService;
use App\Invoice\InvItem\InvItemForm;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Json\Json;
use Yiisoft\Http\Method;
use Yiisoft\Input\Http\Attribute\Parameter\Query;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;
use Yiisoft\FormModel\FormHydrator;

final class TaskController
{
    use FlashMessage;

    private Flash $flash;
    private ViewRenderer $viewRenderer;

    public function __construct(
        private Session $session,
        ViewRenderer $viewRenderer,
        private WebControllerService $webService,
        private UserService $userService,
        private TaskService $taskService,
        private TranslatorInterface $translator,
        private DataResponseFactoryInterface $factory,
        private InvItemService $invitemService
    ) {
        $this->flash = new Flash($this->session);
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/task')
                                           ->withLayout('@views/layout/invoice.php');
    }

    /**
     * @param int $page
     * @param tR $tR
     * @param prjctR $prjctR
     */
    public function index(tR $tR, prjctR $prjctR, #[Query('page')] int $page = null): \Yiisoft\DataResponse\DataResponse
    {
        $canEdit = $this->rbac();
        $parameters = [
            'canEdit' => $canEdit,
            'alert' => $this->alert(),
            'page' => $page > 0 ? $page : 1,
            'prjctR' => $prjctR,
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
    public function add(
        Request $request,
        FormHydrator $formHydrator,
        sR $sR,
        prjctR $pR,
        trR $trR
    ): Response {
        $task = new Task();
        $form = new TaskForm($task);
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'actionName' => 'task/add',
            'actionArguments' => [],
            'alert' => $this->alert(),
            'form' => $form,
            'errors' => [],
            'numberhelper' => new NumberHelper($sR),
            'taxRates' => $trR->optionsDataTaxRates(),
            'projects' => $pR->optionsDataProjects(),
        ];
        if ($request->getMethod() === Method::POST) {
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    $this->taskService->saveTask($task, $body);
                    $this->flashMessage('info', $this->translator->translate('i.record_successfully_created'));
                    return $this->webService->getRedirectResponse('task/index');
                }
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
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
                'actionName' => 'task/edit',
                'actionArguments' => ['id' => $task->getId()],
                'alert' => $this->alert(),
                'form' => $form,
                'errors' => [],
                'taxRates' => $trR->optionsDataTaxRates(),
                'projects' => $pR->optionsDataProjects(),
            ];
            if ($request->getMethod() === Method::POST) {
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    $body = $request->getParsedBody() ?? [];
                    if (is_array($body)) {
                        $this->taskService->saveTask($task, $body);
                        $this->flashMessage('info', $this->translator->translate('i.record_successfully_updated'));
                        return $this->webService->getRedirectResponse('task/index');
                    }
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
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
    public function delete(
        CurrentRoute $currentRoute,
        tR $tR
    ): Response {
        $task = $this->task($currentRoute, $tR);
        /** @var Task $task */
        $this->taskService->deleteTask($task);
        $this->flashMessage('info', $this->translator->translate('i.record_successfully_deleted'));
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
                'class' => 'draft',
            ],
            2 => [
                'label' => $translator->translate('i.in_progress'),
                'class' => 'viewed',
            ],
            3 => [
                'label' => $translator->translate('i.complete'),
                'class' => 'sent',
            ],
            4 => [
                'label' => $translator->translate('i.invoiced'),
                'class' => 'paid',
            ],
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
    public function selection_inv(
        FormHydrator $formHydrator,
        Request $request,
        ACIR $aciR,
        tR $taskR,
        sR $sR,
        trR $trR,
        iiaR $iiaR,
        iiR $iiR,
        itrR $itrR,
        iaR $iaR,
        iR $iR,
        pymR $pymR
    ): \Yiisoft\DataResponse\DataResponse {
        $select_items = $request->getQueryParams();
        /** @var array $task_ids */
        $task_ids = ($select_items['task_ids'] ?: []);
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
     * @param int $order
     * @param Task $task
     * @param string $inv_id
     * @param tR $taskR
     * @param trR $trR
     * @param iiaR $iiaR
     * @param sR $sR
     * @param FormHydrator $formHydrator
     */
    private function save_task_lookup_item_inv(int $order, Task $task, string $inv_id, tR $taskR, trR $trR, iiaR $iiaR, sR $sR, FormHydrator $formHydrator): void
    {
        $invItem = new InvItem();
        $form = new InvItemForm($invItem, (int)$inv_id);
        $ajax_content = [
            'name' => $task->getName(),
            'inv_id' => $inv_id,
            'tax_rate_id' => $task->getTax_rate_id(),
            'task_id' => $task->getId(),
            'product_id' => null,
            'date_added' => new \DateTimeImmutable('now'),
            'description' => $task->getDescription(),
            // A default quantity of 1 is used to initialize the item
            'quantity' => (float) 1,
            'price' => $task->getPrice(),
            // The user will determine how much discount to give on this item later
            'discount_amount' => (float) 0,
            'order' => $order,
        ];
        if ($formHydrator->populateAndValidate($form, $ajax_content)) {
            $this->invitemService->addInvItem_task($invItem, $ajax_content, $inv_id, $taskR, $trR, new iiaS($iiaR), $iiaR, $sR);
        }
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param tR $tR
     * @param trR $trR
     * @param prjctR $pR
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function view(
        CurrentRoute $currentRoute,
        tR $tR,
        trR $trR,
        prjctR $pR
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $task = $this->task($currentRoute, $tR);
        if ($task) {
            $taskForm = new TaskForm($task);
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'actionName' => 'task/view',
                'actionArguments' => ['id' => $task->getId()],
                'errors' => [],
                'form' => $taskForm,
                'task' => $tR->repoTaskquery($task->getId()),
                'taxRates' => $trR->optionsDataTaxRates(),
                'projects' => $pR->optionsDataProjects(),
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
        if (!$canEdit) {
            $this->flashMessage('warning', $this->translator->translate('invoice.permission'));
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
        if (null !== $id) {
            return $tR->repoTaskquery($id);
        }
        return null;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function tasks(tR $tR): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $tR->findAllPreloaded();
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
            ]
        );
    }
}
