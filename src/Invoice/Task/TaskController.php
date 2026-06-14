<?php

declare(strict_types=1);

namespace App\Invoice\Task;

use App\Auth\Permissions;
use App\Invoice\BaseController;
use App\Infrastructure\Persistence\Task\Task;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Infrastructure\Persistence\QuoteItem\QuoteItem;
use App\Invoice\Helpers\CalcInvDeps;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\InvItemAmount\InvItemAmountService as iiaS;
use App\Invoice\Project\ProjectRepository as prjctR;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\Task\TaskRepository as tR;
use App\Invoice\TaxRate\TaxRateRepository as trR;
use App\Service\WebControllerService;
use App\User\UserService;
use App\Invoice\InvItem\InvItemService;
use App\Invoice\InvItem\InvItemForm;
use App\Invoice\QuoteItem\QuoteItemService;
use App\Invoice\QuoteItem\QuoteItemForm;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
use Yiisoft\Json\Json;
use Yiisoft\Http\Method;
use Yiisoft\Input\Http\Attribute\Parameter\Query;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;
use Yiisoft\FormModel\FormHydrator;

final class TaskController extends BaseController
{
    protected string $controllerName = 'invoice/task';

    public function __construct(
        private readonly TaskService $taskService,
        private readonly DataResponseFactoryInterface $factory,
        private InvItemService $invitemService,
        private QuoteItemService $quoteitemService,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $webViewRenderer, $session, $sR, $flash);
        $this->invitemService = $invitemService;
        $this->quoteitemService = $quoteitemService;
    }

    /**
     * @param int $page
     * @param tR $tR
     * @param prjctR $prjctR
     */
    public function index(tR $tR, prjctR $prjctR, #[Query('page')] ?int $page = null): \Psr\Http\Message\ResponseInterface
    {
        $canEdit = $this->rbac();
        $parameters = [
            'canEdit' => $canEdit,
            'alert' => $this->alert(),
            'page' => $page > 0 ? $page : 1,
            'prjctR' => $prjctR,
            'tasks' => $this->tasks($tR),
        ];
        return $this->webViewRenderer->render('index', $parameters);
    }

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param prjctR $pR
     * @param trR $trR
     * @return Response
     */
    public function add(
        Request $request,
        FormHydrator $formHydrator,
        prjctR $pR,
        trR $trR,
    ): Response {
        $task = new Task();
        $form = new TaskForm();
        $parameters = [
            'title' => $this->translator->translate('add'),
            'actionName' => 'task/add',
            'actionArguments' => [],
            'alert' => $this->alert(),
            'form' => $form,
            'errors' => [],
            'numberhelper' => new NumberHelper($this->sR),
            'taxRates' => $trR->optionsDataTaxRates(),
            'projects' => $pR->optionsDataProjects(),
        ];
        if ($request->getMethod() === Method::POST) {
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    $this->taskService->saveTask($task, $body);
                    $this->flashMessage('info', $this->translator->translate('record.successfully.created'));
                    return $this->webService->getRedirectResponse('task/index');
                }
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form'] = $form;
        }
        return $this->webViewRenderer->render('_form', $parameters);
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param tR $tR
     * @param prjctR $pR
     * @param trR $trR
     * @return Response
     */
    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        tR $tR,
        prjctR $pR,
        trR $trR,
    ): Response {
        $task = $this->task($currentRoute, $tR);
        if ($task) {
            $form = TaskForm::show($task);
            $parameters = [
                'title' => $this->translator->translate('edit'),
                'actionName' => 'task/edit',
                'actionArguments' => ['id' => $task->reqId()],
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
                        $this->flashMessage('info', $this->translator->translate('record.successfully.updated'));
                        return $this->webService->getRedirectResponse('task/index');
                    }
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->webViewRenderer->render('_form', $parameters);
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
        tR $tR,
    ): Response {
        $task = $this->task($currentRoute, $tR);
        /** @var Task $task */
        $this->taskService->deleteTask($task);
        $this->flashMessage('info', $this->translator->translate('record.successfully.deleted'));
        return $this->webService->getRedirectResponse('task/index');
    }

    /**
     * @return string[][]
     *
     * @psalm-return array{1: array{label: string, class: 'secondary'}, 2: array{label: string, class: 'warning'}, 3: array{label: string, class: 'success'}, 4: array{label: string, class: 'primary'}}
     */
    public function getStatuses(TranslatorInterface $translator): array
    {
        return [
            1 => [
                'label' => $translator->translate('not.started'),
                'class' => 'secondary',
            ],
            2 => [
                'label' => $translator->translate('in.progress'),
                'class' => 'warning',
            ],
            3 => [
                'label' => $translator->translate('complete'),
                'class' => 'success',
            ],
            4 => [
                'label' => $translator->translate('invoiced'),
                'class' => 'primary',
            ],
        ];
    }

    //views/invoice/task/modal-task-lookups-inv.php => modal_task_lookups_inv.js $(document).on('click', '.select-items-confirm-inv', function ()

    public function selectionInv(
        FormHydrator $formHydrator,
        Request $request,
        TaskSelectionInvDeps $d,
    ): \Psr\Http\Message\ResponseInterface {
        $select_items = $request->getQueryParams();
        /** @var array $task_ids */
        $task_ids = ($select_items['task_ids'] ?: []);
        $inv_id = (int) $select_items['inv_id'];
        $tasks = $d->taskR->findinTasks($task_ids);
        $numberHelper = new NumberHelper($this->sR);
        $order = 1;
        /** @var Task $task */
        foreach ($tasks as $task) {
            $task->setPrice((float) $numberHelper->formatAmount($task->getPrice()));
            $this->saveTaskLookupItemInv($order, $task, $inv_id, $d, $formHydrator);
            $order++;
        }
        $numberHelper->calculateInv((int) $this->session->get('inv_id'), new CalcInvDeps($d->aciR, $d->iiR, $d->iiaR, $d->itrR, $d->iaR, $d->iR, $d->pymR));
        return $this->factory->createResponse(Json::encode($tasks));
    }

    private function saveTaskLookupItemInv(
        int $order,
        Task $task,
        int $inv_id,
        TaskSelectionInvDeps $d,
        FormHydrator $formHydrator,
    ): void {
        $invItem = new InvItem();
        $form = new InvItemForm();
        $ajax_content = [
            'name' => $task->getName(),
            'inv_id' => $inv_id,
            'tax_rate_id' => $task->reqTaxRateId(),
            'task_id' => $task->reqId(),
            'product_id' => null,
            'date_added' => new \DateTimeImmutable('now'),
            'description' => $task->getDescription(),
            'quantity' => (float) 1,
            'price' => $task->getPrice(),
            'discount_amount' => (float) 0,
            'order' => $order,
        ];
        if ($formHydrator->populateAndValidate($form, $ajax_content)) {
            $this->invitemService->addInvItemTask($invItem, $ajax_content,
                    (string) $inv_id, $d->taskR, $d->trR, new iiaS($d->iiaR, $d->iiR), $d->iiaR);
        }
    }

    //views/invoice/task/modal_task_lookups_quote.php => modal_task_lookups_quote.js $(document).on('click', '.select-items-confirm-task-quote', function ()

    public function selectionQuote(
        FormHydrator $formHydrator,
        Request $request,
        TaskSelectionQuoteDeps $d,
    ): \Psr\Http\Message\ResponseInterface {
        $select_items = $request->getQueryParams();
        /** @var array $task_ids */
        $task_ids = ($select_items['task_ids'] ?: []);
        $quote_id = (int) $select_items['quote_id'];
        $tasks = $d->taskR->findinTasks($task_ids);
        $numberHelper = new NumberHelper($this->sR);
        $order = 1;
        /** @var Task $task */
        foreach ($tasks as $task) {
            $task->setPrice((float) $numberHelper->formatAmount($task->getPrice()));
            $this->saveTaskLookupItemQuote($order, $task, $quote_id, $d, $formHydrator);
            $order++;
        }
        $numberHelper->calculateQuote($quote_id, $d->acqR, $d->qiR, $d->qiaR, $d->qtrR, $d->qaR, $d->qR);
        return $this->factory->createResponse(Json::encode($tasks));
    }

    private function saveTaskLookupItemQuote(
        int $order,
        Task $task,
        int $quote_id,
        TaskSelectionQuoteDeps $d,
        FormHydrator $formHydrator,
    ): void {
        $quoteItem = new QuoteItem();
        $form = new QuoteItemForm();
        $ajax_content = [
            'name' => $task->getName(),
            'quote_id' => $quote_id,
            'tax_rate_id' => $task->reqTaxRateId(),
            'task_id' => $task->reqId(),
            'product_id' => null,
            'date_added' => new \DateTimeImmutable('now'),
            'description' => $task->getDescription(),
            'quantity' => (float) 1,
            'price' => $task->getPrice(),
            'discount_amount' => (float) 0,
            'order' => $order,
        ];
        if ($formHydrator->populateAndValidate($form, $ajax_content)) {
            $this->quoteitemService->addQuoteItemTask($quoteItem, $ajax_content, $quote_id, $d->taskR, $d->qiaR, $d->qiaS, $d->trR);
        }
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param tR $tR
     * @param trR $trR
     * @param prjctR $pR
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function view(
        CurrentRoute $currentRoute,
        tR $tR,
        trR $trR,
        prjctR $pR,
    ): \Psr\Http\Message\ResponseInterface {
        $task = $this->task($currentRoute, $tR);
        if ($task) {
            $taskForm = TaskForm::show($task);
            $parameters = [
                'title' => $this->translator->translate('view'),
                'actionName' => 'task/view',
                'actionArguments' => ['id' => $task->reqId()],
                'errors' => [],
                'form' => $taskForm,
                'task' => $tR->repoTaskquery($task->reqId()),
                'taxRates' => $trR->optionsDataTaxRates(),
                'projects' => $pR->optionsDataProjects(),
            ];
            return $this->webViewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('task/index');
    }

    /**
     * @return Response|true
     */
    private function rbac(): bool|Response
    {
        $canEdit = $this->userService->hasPermission(Permissions::EDIT_INV);
        if (!$canEdit) {
            $this->flashMessage('warning', $this->translator->translate('permission'));
            return $this->webService->getRedirectResponse('task/index');
        }
        return $canEdit;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param tR $tR
     * @return Task|null
     */
    private function task(CurrentRoute $currentRoute, tR $tR): ?Task
    {
        return $tR->repoTaskquery((int) $currentRoute->getArgument('id'));
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
}
