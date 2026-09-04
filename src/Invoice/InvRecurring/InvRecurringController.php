<?php

declare(strict_types=1);

namespace App\Invoice\InvRecurring;

use App\Auth\Permissions;
use App\Invoice\BaseController;
// Entities
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvRecurring\InvRecurring;
use App\Infrastructure\Persistence\UserInv\UserInv;
// Repositories
use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\Inv\InvService as IS;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\InvRecurring\InvRecurringRepository as IRR;
use App\Invoice\ProductClient\ProductClientRepository as PCR;
use App\Invoice\Setting\SettingRepository as SR;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Helpers\NumberHelper;
use App\User\UserService;
use App\Invoice\InvAmount\InvAmountService;
use App\Invoice\InvTaxRate\InvTaxRateService;
use App\Invoice\InvCustom\InvCustomService;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Json\Json;
use Yiisoft\Mailer\MailerInterface;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class InvRecurringController extends BaseController
{
    protected string $controllerName = 'invoice/invrecurring';

    public function __construct(
        private DataResponseFactoryInterface $factory,
        private InvCustomService $invCustomService,
        private InvAmountService $invAmountService,
        private InvRecurringService $invrecurringService,
        private InvTaxRateService $invTaxRateService,
        private IS $iS,
        private MailerInterface $mailer,
        SessionInterface $session,
        SR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $webViewRenderer, $session, $sR, $flash);
        $this->factory = $factory;
        $this->invCustomService = $invCustomService;
        $this->invAmountService = $invAmountService;
        $this->invrecurringService = $invrecurringService;
        $this->invTaxRateService = $invTaxRateService;
        $this->iS = $iS;
        $this->mailer = $mailer;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param IRR $irR
     */
    public function index(CurrentRoute $currentRoute, IRR $irR): \Psr\Http\Message\ResponseInterface
    {
        $pageNum = (int) $currentRoute->getArgument('page', '1');
        $currentPageNeverZero = $pageNum > 0 ? $pageNum : 1;
        $paginator = (new OffsetPaginator($irR->findAllPreloaded()))
        ->withPageSize($this->sR->positiveListLimit())
        ->withCurrentPage($currentPageNeverZero);
        $numberhelper = new NumberHelper($this->sR);
        $canEdit = $this->rbac();
        $parameters = [
            'paginator' => $paginator,
            'canEdit' => $canEdit,
            'defaultPageSizeOffsetPaginator' => $this->sR->getSetting('default_list_limit')
                                                      ? (int) $this->sR->getSetting('default_list_limit') : 1,
            'recur_frequencies' => $numberhelper->recurFrequencies(),
            'invrecurrings' => $irR->findAllPreloaded(),
            'alert' => $this->alert(),
        ];
        return $this->webViewRenderer->render('index', $parameters);
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param IR $iR
     * @return Response
     */
    public function add(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        IR $iR,
    ): Response {
        $inv_id = (int) $currentRoute->getArgument('inv_id');
        $baseInvoice = $inv_id > 0 ? $iR->repoInvUnloadedquery($inv_id) : null;
        if (null === $baseInvoice) {
            return $this->webService->getNotFoundResponse();
        }
        if ($baseInvoice->reqStatusId() != 2) {
            $this->flashMessage('danger', $this->translator->translate('recurring.status.sent.only') . '❗');
            return $this->webService->getRedirectResponse('inv/view', ['id' => $inv_id]);
        }
        $invRecurring = new InvRecurring();
        $form = new InvRecurringForm();
        $parameters = [
            'title' => $this->translator->translate('add'),
            'actionName' => 'invrecurring/add',
            'actionArguments' => ['inv_id' => $inv_id],
            'errors' => [],
            'invDateCreated' => $baseInvoice->getDateCreated(),
            'form' => $form,
        ];
        $redirect = null;
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody() ?? [];
            if ($formHydrator->populateFromPostAndValidate($form, $request) && is_array($body)) {
                $this->invrecurringService->saveInvRecurring($invRecurring, $body);
                $redirect = $this->webService->getRedirectResponse('invrecurring/index');
            } else {
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
        }
        return $redirect ?? $this->webViewRenderer->render('_form', $parameters);
    }

    /**
     * Build a draft invoice from a client's ProductClient associations and
     * immediately set it up as a recurring invoice with the chosen frequency.
     * The admin triggers this once per client; the cron (Phase 2) handles
     * subsequent auto-creation after checking consent flags.
     *
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param CR $cR
     * @param GR $gR
     * @param PCR $pcR
     * @param InvItemDeps $itemDeps
     * @param InvRecurringCronService $cronService
     * @return Response
     */
    public function createFromProductClient(
        Request $request,
        CurrentRoute $currentRoute,
        CR $cR,
        GR $gR,
        PCR $pcR,
        InvItemDeps $itemDeps,
        InvRecurringCronService $cronService,
    ): Response {
        $clientId = (int) $currentRoute->getArgument('client_id');
        $client = $cR->repoClientquery($clientId);
        /** @var array<int,\App\Infrastructure\Persistence\ProductClient\ProductClient> $productClients */
        $productClients = $pcR->findByClientId($clientId);
        $frequencies = (new NumberHelper($this->sR))->recurFrequencies();

        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody() ?? [];
            if (is_array($body) && count($productClients) > 0) {
                $frequency = (string) ($body['frequency'] ?? '1M');
                $user = $this->userService->getUser();
                if (null === $user) {
                    return $this->webService->getNotFoundResponse();
                }

                $savedInv = $this->iS->saveInv($user, new Inv(), [
                    'client_id'      => $clientId,
                    'group_id'       => 1,
                    'status_id'      => 1,
                    'date_created'   => (new \DateTimeImmutable())->format('Y-m-d'),
                    'date_supplied'  => (new \DateTimeImmutable())->format('Y-m-d'),
                ], $this->sR, $gR);

                $cronService->addProductItemsToInv($productClients, (string) $savedInv->reqId(), $itemDeps);

                $this->invrecurringService->saveInvRecurring(new InvRecurring(), [
                    'inv_id'    => $savedInv->reqId(),
                    'frequency' => $frequency,
                    'start'     => (new \DateTimeImmutable())->format('Y-m-d'),
                ]);

                $this->m('CS');
                return $this->webService->getRedirectResponse('invrecurring/index');
            }
        }

        return $this->webViewRenderer->render('create_from_productclient', [
            'client'         => $client,
            'productClients' => $productClients,
            'frequencies'    => $frequencies,
            'canEdit'        => $this->rbac(),
        ]);
    }

    /**
     * Cron endpoint — create new recurring invoices and send Telegram reminders.
     * No session authentication — authorized instead by an HTTP Bearer token
     * (`Authorization: Bearer <cron_key>`), enforced by
     * Yiisoft\Auth\Middleware\Authentication (routes-inv-recurring.php) before
     * this action ever runs: an unauthenticated request never reaches here at
     * all, it gets a `401` + `WWW-Authenticate` challenge straight from the
     * middleware. See docs/INVRECURRING_CRON_BEARER_AUTH_AUGUST_2026.md.
     * Prefer the `invrecurring/process` console command for new setups; this
     * endpoint remains for existing curl+cron_key triggers (now sent as a
     * Bearer token rather than a URL query parameter).
     *
     * @param InvItemDeps $itemDeps
     * @param InvCronUserDeps $cronDeps
     * @param InvRecurringCronService $cronService
     * @return Response
     */
    public function cron(
        InvItemDeps $itemDeps,
        InvCronUserDeps $cronDeps,
        InvRecurringCronService $cronService,
    ): Response {
        $user = $this->userService->getUser() ?? $cronService->resolveAdminUser($cronDeps);
        if (null === $user) {
            return $this->factory->createResponse(Json::encode(['success' => false, 'error' => 'No admin user found']));
        }

        $result = $cronService->process($user, $itemDeps, $cronDeps);

        return $this->factory->createResponse(Json::encode([
            'success'  => true,
            'created'  => $result['created'],
            'reminded' => $result['reminded'],
        ]));
    }

    //inv.js create_recurring_confirm_multiple function calls this function

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     */
    public function multiple(Request $request, FormHydrator $formHydrator, IR $iR): \Psr\Http\Message\ResponseInterface
    {
        $data = $request->getQueryParams();
        /** @var array<int|string, string> $keyList */
        $keyList = $data['keylist'] ?? [];
        if (empty($keyList)) {
            return $this->factory->createResponse(Json::encode(['success' => 0, 'message' => $this->translator->translate('recurring.no.invoices.selected')]));
        }
        foreach ($keyList as $value) {
            $error = $this->processRecurringKey($value, $data, $formHydrator, $iR);
            if (null !== $error) {
                return $error;
            }
        }
        return $this->factory->createResponse(Json::encode(['success' => 1]));
    }

    /**
     * @param array<array-key, mixed> $data
     */
    private function processRecurringKey(string $value, array $data, FormHydrator $formHydrator, IR $iR): ?\Psr\Http\Message\ResponseInterface
    {
        $baseInvoice = $iR->repoInvUnloadedquery((int) $value);
        if (null === $baseInvoice) {
            return $this->factory->createResponse(Json::encode(['success' => 0, 'message' => '']));
        }
        if ($baseInvoice->reqStatusId() != 2) {
            return $this->factory->createResponse(Json::encode(['success' => 0, 'message' => $this->translator->translate('recurring.status.sent.only')]));
        }
        $invRecurring = new InvRecurring();
        $form = new InvRecurringForm();
        $body_array = [
            'inv_id' => $value,
            'start'     => $data['recur_start_date'] ?? null,
            'end'       => $data['recur_end_date'] ?? null,
            'frequency' => $data['recur_frequency'],
            'next'      => $data['recur_start_date'] ?? null,
        ];
        if ($formHydrator->populateAndValidate($form, $body_array)) {
            $this->invrecurringService->saveInvRecurring($invRecurring, $body_array);
        }
        return null;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param IRR $iR
     * @return Response
     */
    public function stop(CurrentRoute $currentRoute, IRR $iR): Response
    {
        $inv_recurring = $this->invrecurring($currentRoute, $iR);
        if ($inv_recurring) {
            $ivr = $iR->repoInvRecurringquery($inv_recurring->reqId());
            if ($ivr) {
                $dateTime = new \DateTime();
                $ivr->setEnd($dateTime);
                $ivr->setNext(null);
                $iR->save($ivr);
                return $this->webService->getRedirectResponse('invrecurring/index');
            }
            return $this->webService->getNotFoundResponse();
        }
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param IRR $invrecurringRepository
     * @return Response
     */
    public function start(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        IR $iR,
        IRR $invrecurringRepository,
    ): Response {
        $inv_recurring = $this->invrecurring($currentRoute, $invrecurringRepository);
        if ($inv_recurring) {
            $form = InvRecurringForm::show($inv_recurring, $inv_recurring->reqInvId());
            $base_invoice = $iR->repoInvUnloadedquery($inv_recurring->reqInvId());
            if (null !== $base_invoice) {
                $invDateCreated = $base_invoice->getDateCreated();
                $parameters = [
                    'title' => $this->translator->translate('edit'),
                    'actionName' => 'invrecurring/start',
                    'actionArguments' => ['id' => $inv_recurring->reqId()],
                    'errors' => [],
                    'invDateCreated' => $invDateCreated,
                    'form' => $form,
                ];
                if ($request->getMethod() === Method::POST) {
                    $body = $request->getParsedBody() ?? [];
                    if ($formHydrator->populateFromPostAndValidate($form, $request) && is_array($body)) {
                        $this->invrecurringService->saveInvRecurring($inv_recurring, $body);
                        return $this->webService->getRedirectResponse('invrecurring/index');
                    }
                    $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                    $parameters['form'] = $form;
                }
                return $this->webViewRenderer->render('_form', $parameters);
            } // null!== $base_invoice
        }
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param IRR $invrecurringRepository
     * @return Response
     */
    public function delete(
        CurrentRoute $currentRoute,
        IRR $invrecurringRepository,
    ): Response {
        try {
            $inv_recurring = $this->invrecurring($currentRoute, $invrecurringRepository);
            if ($inv_recurring) {
                $this->invrecurringService->deleteInvRecurring($inv_recurring);
                $this->flashMessage('info', $this->translator->translate('recurring.deleted'));
                return $this->webService->getRedirectResponse('invrecurring/index');
            }
            return $this->webService->getNotFoundResponse();
        } catch (\Exception $e) {
            $this->flashMessage('danger', $e->getMessage());
            unset($e);
            return $this->webService->getRedirectResponse('invrecurring/index');
        }
    }

    /**
     * @param Request $request
     * @param IR $iR
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getRecurStartDate(Request $request, IR $iR): \Psr\Http\Message\ResponseInterface
    {
        $body = $request->getQueryParams();
        $inv_id = (int) $body['inv_id'];
        $base_invoice = $iR->repoInvUnloadedquery($inv_id);
        if (null !== $base_invoice) {
            $immutable_invoice_date = $base_invoice->getDateCreated();
            // see InvRecurringRepository recur_frequencies eg. '8M' => 'calendar_month_8',
            $recur_frequency = (string) $body['frequency'];
            $dateHelper = new DateHelper($this->sR);
            $parameters = [
                'success' => 1,
                // Show the recur_start_date in Y-m-d format
                'start_date' => $dateHelper->addToImmutable($immutable_invoice_date, $recur_frequency),
            ];
            return $this->factory->createResponse(Json::encode($parameters));
        }
        return $this->factory->createResponse(Json::encode(
            [
                'success' => 0],
        ));
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param IRR $invrecurrR
     * @param IR $iR
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function view(CurrentRoute $currentRoute, IRR $invrecurrR, IR $iR): \Psr\Http\Message\ResponseInterface
    {
        $inv_recurring = $this->invrecurring($currentRoute, $invrecurrR);
        if ($inv_recurring) {
            $invRecurringId = $inv_recurring->reqId();
            $form = InvRecurringForm::show($inv_recurring, $inv_recurring->reqInvId());
            $invId = $inv_recurring->reqInvId();
            $base_invoice = $iR->repoInvUnloadedquery($invId);
            if (null !== $base_invoice) {
                $invDateCreated = $base_invoice->getDateCreated();
                $parameters = [
                    'title' => $this->translator->translate('view'),
                    'actionName' => 'invrecurring/view',
                    'actionArguments' => ['id' => $invRecurringId],
                    'errors' => [],
                    'form' => $form,
                    'invDateCreated' => $invDateCreated,
                    'invrecurring' =>
                    $invrecurrR->repoInvRecurringquery($invRecurringId),
                ];
                return $this->webViewRenderer->render('_view', $parameters);
            }
        }
        return $this->webService->getNotFoundResponse();
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    /**
     * @param CurrentRoute $currentRoute
     * @param IRR $invrecurringRepository
     * @return InvRecurring|null
     */
    private function invrecurring(CurrentRoute $currentRoute, IRR $invrecurringRepository): ?InvRecurring
    {
        $invrecurring = new InvRecurring();
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $invrecurringRepository->repoInvRecurringquery((int) $id);
            // InvRecurring/null can be returned here
        }
        return $invrecurring;
    }

    /**
     * @return Response|true
     */
    private function rbac(): bool|Response
    {
        $canEdit = $this->userService->hasPermission(Permissions::EDIT_INV);
        if (!$canEdit) {
            $this->flashMessage('warning', $this->translator->translate('permission'));
            return $this->webService->getRedirectResponse('invrecurring/index');
        }
        return $canEdit;
    }
}
