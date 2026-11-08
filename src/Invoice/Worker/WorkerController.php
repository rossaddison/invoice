<?php

declare(strict_types=1);

namespace App\Invoice\Worker;

use App\Infrastructure\Persistence\Company\Company;
use App\Invoice\BaseController;
use App\Invoice\Company\CompanyRepository;
use App\Invoice\WorkerCompany\WorkerCompanyService;
use App\Infrastructure\Persistence\Worker\Worker;
use App\Invoice\Setting\SettingRepository as sR;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class WorkerController extends BaseController
{
    protected string $controllerName = 'invoice/worker';

    public function __construct(
        private WorkerService $workerService,
        private WorkerCompanyService $workerCompanyService,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $webViewRenderer, $session, $sR, $flash);
        $this->workerService = $workerService;
    }

    public function index(
        CurrentRoute $currentRoute,
        WorkerRepository $workerRepository,
        CompanyRepository $companyRepository,
    ): Response {
        $workers = $this->workers($workerRepository);
        $pageNum = (int) $currentRoute->getArgument('page', '1');
        $currentPageNeverZero = $pageNum > 0 ? $pageNum : 1;
        $paginator = (new OffsetPaginator($workers))
            ->withPageSize($this->sR->positiveListLimit())
            ->withCurrentPage($currentPageNeverZero);
        $parameters = [
            'alert' => $this->alert(),
            'paginator' => $paginator,
            'workers' => $workers,
            // worker id => the names of the businesses it is registered under
            'businessNames' => $this->businessNamesByWorker($companyRepository),
        ];
        return $this->webViewRenderer->render('index', $parameters);
    }

    public function add(Request $request, FormHydrator $formHydrator, CompanyRepository $companyRepository): Response
    {
        $worker = new Worker();
        $form = new WorkerForm();
        $parameters = [
            'title' => $this->translator->translate('add'),
            'actionName' => 'worker/add',
            'actionArguments' => [],
            'form' => $form,
            'errors' => [],
            'businesses' => $this->tickableBusinesses($companyRepository),
            'defaultBusiness' => $this->defaultBusinessName($companyRepository),
        ];
        if ($request->getMethod() === Method::POST) {
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    $this->workerService->saveWorker($worker, $body);
                    $this->workerCompanyService->syncCompanies($worker->reqId(), $this->tickedCompanies($body));
                    $this->flashMessage('info', $this->translator->translate('record.successfully.created'));
                    return $this->webService->getRedirectResponse('worker/index');
                }
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form'] = $form;
        }
        return $this->webViewRenderer->render('__form', $parameters);
    }

    public function edit(
        Request $request,
        #[RouteArgument('worker_id')]
        string $worker_id,
        WorkerRepository $workerRepository,
        FormHydrator $formHydrator,
        CompanyRepository $companyRepository,
    ): Response {
        $worker = $this->worker((int) $worker_id, $workerRepository);
        if ($worker) {
            $form = WorkerForm::show($worker, $this->workerCompanyService->storedCompanyIdsFor($worker->reqId()));
            $parameters = [
                'title' => $this->translator->translate('edit'),
                'actionName' => 'worker/edit',
                'actionArguments' => ['worker_id' => $worker_id],
                'form' => $form,
                'errors' => [],
                'businesses' => $this->tickableBusinesses($companyRepository),
                'defaultBusiness' => $this->defaultBusinessName($companyRepository),
            ];
            if ($request->getMethod() === Method::POST) {
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    $body = $request->getParsedBody() ?? [];
                    if (is_array($body)) {
                        $this->workerService->saveWorker($worker, $body);
                        $this->workerCompanyService->syncCompanies($worker->reqId(), $this->tickedCompanies($body));
                        $this->flashMessage('info', $this->translator->translate('record.successfully.updated'));
                        return $this->webService->getRedirectResponse('worker/index');
                    }
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->webViewRenderer->render('__form', $parameters);
        }
        return $this->webService->getRedirectResponse('worker/index');
    }

    public function delete(#[RouteArgument('worker_id')] string $worker_id, WorkerRepository $workerRepository): Response
    {
        try {
            /** @var Worker $worker */
            $worker = $this->worker((int) $worker_id, $workerRepository);
            $this->workerCompanyService->unlinkWorker($worker->reqId());
            $this->workerService->deleteWorker($worker);
            $this->flashMessage('success', $this->translator->translate('record.successfully.deleted'));
            return $this->webService->getRedirectResponse('worker/index');
        } catch (\Exception $e) {
            unset($e);
            $this->flashMessage('danger', $this->translator->translate('record.successfully.deleted.not'));
            return $this->webService->getRedirectResponse('worker/index');
        }
    }

    public function view(#[RouteArgument('worker_id')] string $worker_id, WorkerRepository $workerRepository): Response
    {
        $worker = $this->worker((int) $worker_id, $workerRepository);
        if (null !== $worker) {
            $form = WorkerForm::show($worker);
            $parameters = [
                'title' => $this->translator->translate('view'),
                'actionName' => 'worker/view',
                'actionArguments' => ['worker_id' => $worker_id],
                'form' => $form,
            ];
            return $this->webViewRenderer->render('__view', $parameters);
        }
        return $this->webService->getRedirectResponse('worker/index');
    }

    /**
     * The businesses ticked on the worker form (none ticked sends nothing at all).
     *
     * @param array<array-key, mixed> $body
     * @return list<int> the ticked company ids (a non-numeric tick becomes 0 and is ignored later)
     */
    private function tickedCompanies(array $body): array
    {
        return isset($body['company_ids']) && is_array($body['company_ids'])
            ? array_map('intval', array_values($body['company_ids']))
            : [];
    }

    /**
     * The businesses a worker can be registered under: id => name.
     *
     * @return array<int, string>
     */
    private function businessOptions(CompanyRepository $companyRepository): array
    {
        $options = [];
        /** @var iterable<array-key, Company> $companies */
        $companies = $companyRepository->findAllPreloaded()->read();
        foreach ($companies as $company) {
            $options[$company->reqId()] = (string) $company->getName();
        }
        return $options;
    }

    /**
     * The businesses the form lets you tick: all of them except the default, which every worker
     * belongs to and is shown separately.
     *
     * @return array<int, string>
     */
    private function tickableBusinesses(CompanyRepository $companyRepository): array
    {
        $options = $this->businessOptions($companyRepository);
        $default = $this->workerCompanyService->defaultCompanyId();
        if (null !== $default) {
            unset($options[$default]);
        }
        return $options;
    }

    /**
     * The name of the default business (the current company), or null when none is current.
     */
    private function defaultBusinessName(CompanyRepository $companyRepository): ?string
    {
        $default = $this->workerCompanyService->defaultCompanyId();
        return null === $default ? null : ($this->businessOptions($companyRepository)[$default] ?? ('#' . $default));
    }

    /**
     * @return array<int, string> worker id => the business names, comma separated
     */
    private function businessNamesByWorker(CompanyRepository $companyRepository): array
    {
        $options = $this->businessOptions($companyRepository);
        $names = [];
        foreach ($this->workerCompanyService->companyIdsByWorker() as $workerId => $companyIds) {
            $names[$workerId] = implode(', ', array_map(
                static fn (int $id): string => $options[$id] ?? ('#' . $id),
                $companyIds
            ));
        }
        return $names;
    }

    private function worker(int $worker_id, WorkerRepository $workerRepository): ?Worker
    {
        return $workerRepository->repoWorkerquery($worker_id);
    }

    /**
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function workers(WorkerRepository $workerRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $workerRepository->findAllPreloaded();
    }
}
