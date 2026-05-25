<?php

declare(strict_types=1);

namespace App\Invoice\InvRecurring;

use App\Auth\Permissions;
use App\Invoice\BaseController;
// Entities
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvItem\InvItem;
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
use App\Invoice\Helpers\Telegram\TelegramHelper;
use App\User\UserService;
use App\Invoice\InvItem\InvItemService;
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
use Yiisoft\Log\Logger;
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
        private Logger $_logger,
        private DataResponseFactoryInterface $factory,
        private InvCustomService $invCustomService,
        private InvAmountService $invAmountService,
        private InvItemService $invItemService,
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
        $this->_logger = new Logger();
        $this->factory = $factory;
        $this->invCustomService = $invCustomService;
        $this->invAmountService = $invAmountService;
        $this->invItemService = $invItemService;
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
        $paginator = (new OffsetPaginator($this->invrecurrings($irR)))
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
            'invrecurrings' => $this->invrecurrings($irR),
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
        $invRecurring = new InvRecurring();
        $form = new InvRecurringForm();
        if ($inv_id > 0) {
            $baseInvoice = $iR->repoInvUnloadedquery($inv_id);
            if (null !== $baseInvoice) {
                // Only invoices with a status of sent can be  made recurring
                if ($baseInvoice->reqStatusId() == 2) {
                    $invDateCreated = $baseInvoice->getDateCreated();
                    $parameters = [
                        'title' => $this->translator->translate('add'),
                        'actionName' => 'invrecurring/add',
                        'actionArguments' => ['inv_id' => $inv_id],
                        'errors' => [],
                        'invDateCreated' => $invDateCreated,
                        'form' => $form,
                    ];
                    if ($request->getMethod() === Method::POST) {
                        $body = $request->getParsedBody() ?? [];
                        if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                            if (is_array($body)) {
                                $this->invrecurringService->saveInvRecurring($invRecurring, $body);
                                return $this->webService->getRedirectResponse('invrecurring/index');
                            }
                        }
                        $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                        $parameters['form'] = $form;
                    }
                    return $this->webViewRenderer->render('_form', $parameters);
                }
                $this->flashMessage('danger', $this->translator->translate('recurring.status.sent.only') . '❗');
                // Redirect back to the invoice view instead of showing 404
                return $this->webService->getRedirectResponse('inv/view', ['id' => $inv_id]);
            }
        }
        return $this->webService->getNotFoundResponse();
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
     * @return Response
     */
    public function createFromProductClient(
        Request $request,
        CurrentRoute $currentRoute,
        CR $cR,
        GR $gR,
        PCR $pcR,
        InvItemDeps $itemDeps,
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

                $this->addProductItemsToInv($productClients, (string) $savedInv->reqId(), $itemDeps);

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
     * No session authentication required; secured by cron_key query parameter only.
     *
     * @param Request $request
     * @param IRR $irR
     * @param IR $iR
     * @param GR $gR
     * @param PCR $pcR
     * @param InvItemDeps $itemDeps
     * @param InvCronUserDeps $cronDeps
     * @return Response
     */
    public function cron(
        Request $request,
        IRR $irR,
        IR $iR,
        GR $gR,
        PCR $pcR,
        InvItemDeps $itemDeps,
        InvCronUserDeps $cronDeps,
    ): Response {
        $params = $request->getQueryParams();
        $cronKey = (string) ($params['cron_key'] ?? '');
        if ($cronKey === '' || $cronKey !== $this->sR->getSetting('cron_key')) {
            return $this->factory->createResponse(Json::encode(['success' => false, 'error' => 'Forbidden']));
        }

        $user = $this->userService->getUser() ?? $this->resolveAdminUser($cronDeps);
        if (null === $user) {
            return $this->factory->createResponse(Json::encode(['success' => false, 'error' => 'No admin user found']));
        }

        $created = 0;
        $reminded = 0;
        $token = $this->sR->getSetting('telegram_token');
        $telegramEnabled = $this->sR->getSetting('enable_telegram') === '1';

        /** @var InvRecurring $invRecurring */
        foreach ($irR->active() as $invRecurring) {
            $prevInvId = $invRecurring->reqInvId();
            $baseInv = $iR->repoInvUnloadedquery($prevInvId);
            if (null === $baseInv) {
                continue;
            }
            $clientId = $baseInv->reqClientId();

            $userClient = $cronDeps->uclR->repoUserquery($clientId);
            if (null === $userClient) {
                continue;
            }
            $userInv = $cronDeps->uiR->repoUserInvUserIdquery($userClient->reqUserId());
            if (null === $userInv) {
                continue;
            }

            if ($userInv->getConsentPeriodicInvoice()) {
                /** @var array<int,\App\Infrastructure\Persistence\ProductClient\ProductClient> $productClients */
                $productClients = $pcR->findByClientId($clientId);
                if (count($productClients) > 0) {
                    $savedInv = $this->iS->saveInv($user, new Inv(), [
                        'client_id'     => $clientId,
                        'group_id'      => 1,
                        'status_id'     => 1,
                        'date_created'  => (new \DateTimeImmutable())->format('Y-m-d'),
                        'date_supplied' => (new \DateTimeImmutable())->format('Y-m-d'),
                    ], $this->sR, $gR);
                    $this->addProductItemsToInv($productClients, (string) $savedInv->reqId(), $itemDeps);
                    ++$created;
                }
            }

            $this->advanceRecurringDate($invRecurring, $irR);

            if ($telegramEnabled && strlen($token) > 1) {
                $reminded += $this->sendTelegramReminderIfNeeded($prevInvId, $userInv, $cronDeps, $token);
            }
        }

        return $this->factory->createResponse(Json::encode([
            'success'  => true,
            'created'  => $created,
            'reminded' => $reminded,
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
        /**
         * Purpose: Provide a list of ids from inv/index checkbox column as an array
         * @var array $data['keylist']
         */
        $keyList = $data['keylist'] ?? [];
        if (!empty($keyList)) {
            /**
             * @var string $value
             */
            foreach ($keyList as $value) {
                $baseInvoice = $iR->repoInvUnloadedquery((int) $value);
                if (null !== $baseInvoice) {
                    if ($baseInvoice->reqStatusId() == 2) {
                        $invRecurring = new InvRecurring();
                        $form = new InvRecurringForm();
                        $body_array = [
                            'inv_id' => $value,
                            'start' => $data['recur_start_date'] ?? null,
                            'end' => $data['recur_end_date'] ?? null,
                            'frequency' => $data['recur_frequency'],
                            'next' => $data['recur_start_date'] ?? null,
                        ];
                        if ($formHydrator->populateAndValidate($form, $body_array)) {
                            $this->invrecurringService->saveInvRecurring($invRecurring, $body_array);
                        }
                    } else {
                        return $this->factory->createResponse(Json::encode(['success' => 0,
                            'message' => $this->translator->translate('recurring.status.sent.only')]));
                    }
                } else {
                    return $this->factory->createResponse(Json::encode(['success' => 0, 'message' => '']));
                }
            }
            return $this->factory->createResponse(Json::encode(['success' => 1]));
        }
        return $this->factory->createResponse(Json::encode(['success' => 0, 'message' => $this->translator->translate('recurring.no.invoices.selected')]));
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
                    if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                        if (is_array($body)) {
                            $this->invrecurringService->saveInvRecurring($inv_recurring, $body);
                            return $this->webService->getRedirectResponse('invrecurring/index');
                        }
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
     * @param array<int,\App\Infrastructure\Persistence\ProductClient\ProductClient> $productClients
     */
    private function addProductItemsToInv(array $productClients, string $invId, InvItemDeps $d): void
    {
        foreach ($productClients as $productClient) {
            $productId = $productClient->getProductId();
            if (null === $productId) {
                continue;
            }
            $product = $d->pR->repoProductquery($productId);
            if (null !== $product) {
                $this->invItemService->addInvItemProduct(
                    new InvItem(),
                    [
                        'product_id'      => $product->reqId(),
                        'tax_rate_id'     => $product->reqTaxRateId(),
                        'quantity'        => 1.00,
                        'price'           => $product->getProductPrice() ?? 0.00,
                        'discount_amount' => 0.00,
                        'product_unit_id' => $product->reqUnitId(),
                    ],
                    $invId,
                    $d->pR,
                    $d->trR,
                    $d->iias,
                    $d->iiar,
                    $this->sR,
                    $d->unR,
                );
            }
        }
    }

    private function resolveAdminUser(InvCronUserDeps $d): ?\App\Infrastructure\Persistence\User\User
    {
        /** @var UserInv $ui */
        foreach ($d->uiR->findAllPreloaded() as $ui) {
            if ($ui->getType() === 0) {
                return $d->userRepository->findById($ui->reqUserId());
            }
        }
        return null;
    }

    private function advanceRecurringDate(InvRecurring $invRecurring, IRR $irR): void
    {
        $dateHelper = new DateHelper($this->sR);
        $nextRaw = $invRecurring->getNext();
        $nextString = match (true) {
            $nextRaw instanceof \DateTimeImmutable => $nextRaw->format('Y-m-d'),
            is_string($nextRaw) && $nextRaw !== '' => $nextRaw,
            default                                => date('Y-m-d'),
        };
        $invRecurring->setNext($dateHelper->incrementDateStringToDateTime($nextString, $invRecurring->getFrequency()));
        $irR->save($invRecurring);
    }

    private function sendTelegramReminderIfNeeded(
        int $prevInvId,
        UserInv $userInv,
        InvCronUserDeps $d,
        string $token,
    ): int {
        $invAmount = $d->iaR->repoInvquery($prevInvId);
        $balance = $invAmount?->getBalance() ?? 0.0;
        if ($balance > 0.0 && $userInv->getConsentTelegramOutstanding()) {
            $chatId = $userInv->getTelegramChatId();
            if (null !== $chatId && $chatId !== '') {
                $telegramHelper = new TelegramHelper($token, $this->_logger);
                $telegramHelper->getBotApi()->sendMessage(
                    $chatId,
                    'Invoice #' . $prevInvId . ' has an outstanding balance of '
                        . number_format($balance, 2) . '. Please log in to make a payment.',
                );
                return 1;
            }
        }
        return 0;
    }

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
     * @param IRR $invrecurringRepository
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function invrecurrings(IRR $invrecurringRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $invrecurringRepository->findAllPreloaded();
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
