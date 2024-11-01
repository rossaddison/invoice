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
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;
use Exception;

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
    ) {
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
        $this->rbac();
        $query_params = $request->getQueryParams();
        /**
         * @var string $query_params['page']
         */
        $page = $query_params['page'] ?? $currentRoute->getArgument('page', '1');
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = $page > 0 ? $page : 1;
        /** @var string $query_params['sort'] */
        $sort = Sort::only(['id', 'client_id', 'name', 'reference'])
            // (@see vendor\yiisoft\data\src\Reader\Sort
            // - => 'desc'  so -id => default descending on id
            // Show the latest quotes first => -id
            ->withOrderString($query_params['sort'] ?? '-id');
        $contracts = $this->contracts_with_sort($contractR, $sort);
        $this->flash_message('info', $this->translator->translate('invoice.invoice.contract.create'));
        $paginator = (new OffsetPaginator($contracts))
        ->withPageSize((int)$sR->getSetting('default_list_limit'))
        ->withCurrentPage($currentPageNeverZero)
        ->withToken(PageToken::next((string)$page));
        $parameters = [
            'alert' => $this->alert(),
            'paginator' => $paginator,
            'cR' => $cR,
            // Use the invoice Repository to retrieve all invoices associated with this contract
            'iR' => $iR
        ];
        return $this->viewRenderer->render('index', $parameters);
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param cR $cR
     * @param sR $sR
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function add(CurrentRoute $currentRoute, Request $request, FormHydrator $formHydrator, cR $cR, sR $sR): \Yiisoft\DataResponse\DataResponse|Response
    {
        $client_id = $currentRoute->getArgument('client_id');
        $contract = new Contract();
        // To pass the client id variable to the form, set it first in the entity
        $contract->setClient_id((int)$client_id);
        $title = '';
        $form = new ContractForm($contract);
        if (null !== $client_id) {
            $title = ($cR->repoClientquery($client_id))->getClient_name();
        } else {
            $title = $this->translator->translate('invoice.not.available');
        }
        $parameters = [
            'title' => $this->translator->translate('invoice.invoice.contract.add')
                       .': '
                       . $title,
            'actionName' => 'contract/add',
            'actionArguments' => ['client_id' => $client_id],
            'errors' => [],
            'form' => $form,
            'client_id' => $client_id
        ];

        if ($request->getMethod() === Method::POST) {
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $body = $request->getParsedBody();
                /**
                 * @psalm-suppress PossiblyInvalidArgument $body
                 */
                $this->contractService->saveContract($contract, $body, $sR);
                return $this->webService->getRedirectResponse('contract/index');
            }
            $parameters['form'] = $form;
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
        }
        return $this->viewRenderer->render('_form', $parameters);
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param contractR $contractRepository
     * @param sR $sR
     * @return Response
     */
    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        contractR $contractRepository,
        sR $sR
    ): Response {
        $contract = $this->contract($currentRoute, $contractRepository);
        if ($contract) {
            $form = new ContractForm($contract);
            $parameters = [
                'title' => $this->translator->translate('i.edit'),
                'actionName' => 'contract/edit',
                'actionArguments' => ['id' => $contract->getId()],
                'errors' => [],
                'form' => $form
            ];
            if ($request->getMethod() === Method::POST) {
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    $body = $request->getParsedBody() ?? [];
                    /**
                     * @psalm-suppress PossiblyInvalidArgument $body
                     */
                    $this->contractService->saveContract($contract, $body, $sR);
                    return $this->webService->getRedirectResponse('contract/index');
                }
                $parameters['form'] = $form;
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('contract/index');
    }

    /**
     * @return Response|true
     */
    private function rbac(): bool|Response
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit) {
            $this->flash_message('warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('contract/index');
        }
        return $canEdit;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param contractR $contractRepository
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute, contractR $contractRepository): Response
    {
        try {
            $contract = $this->contract($currentRoute, $contractRepository);
            if ($contract) {
                $this->contractService->deleteContract($contract);
                $this->flash_message('success', $this->translator->translate('i.record_successfully_deleted'));
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
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function view(CurrentRoute $currentRoute, contractR $contractRepository): \Yiisoft\DataResponse\DataResponse|Response
    {
        $contract = $this->contract($currentRoute, $contractRepository);
        if ($contract) {
            $form = new ContractForm($contract);
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'actionName' => 'contract/view',
                'actionArguments' => ['id' => $contract->getId()],
                'errors' => [],
                'form' => $form
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
    private function contract(CurrentRoute $currentRoute, contractR $contractRepository): Contract|null
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            $contract = $contractRepository->repoContractquery($id);
            return $contract;
        }
        return null;
    }

    /**
     *
     * @param contractR $contractRepository
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function contracts(contractR $contractRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
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
    private function contracts_with_sort(contractR $cR, Sort $sort): \Yiisoft\Data\Reader\SortableDataInterface
    {
        $contracts = $cR->findAllPreloaded()
                       ->withSort($sort);
        return $contracts;
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
       'errors' => [],
     ]
        );
    }

    /**
    * @param string $level
    * @param string $message
    * @return Flash|null
    */
    private function flash_message(string $level, string $message): Flash|null
    {
        if (strlen($message) > 0) {
            $this->flash->add($level, $message, true);
            return $this->flash;
        }
        return null;
    }
}
