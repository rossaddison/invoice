<?php

declare(strict_types=1);

namespace App\Invoice\InvRecurring;

// Entities
use App\Invoice\Entity\InvRecurring;
// Forms
use App\Invoice\Inv\InvService as IS;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\InvRecurring\InvRecurringService;
use App\Invoice\InvRecurring\InvRecurringForm;
use App\Invoice\InvRecurring\InvRecurringRepository as IRR;
use App\Invoice\Setting\SettingRepository as SR;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Helpers\NumberHelper;
use App\User\UserService;
use App\Invoice\InvItem\InvItemService;
use App\Invoice\InvAmount\InvAmountService;
use App\Invoice\InvTaxRate\InvTaxRateService;
use App\Invoice\InvCustom\InvCustomService;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Json\Json;
use Yiisoft\Log\Logger;
use Yiisoft\Mailer\MailerInterface;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class InvRecurringController
{
    private Flash $flash;
    private DataResponseFactoryInterface $factory;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private InvRecurringService $invrecurringService;
    private InvAmountService $invAmountService;
    private InvCustomService $invCustomService;
    private InvItemService $invItemService;
    private InvTaxRateService $invTaxRateService;
    private Session $session;
    private SR $s;
    private IS $iS;
    private TranslatorInterface $translator;
    private Logger $_logger;
    private MailerInterface $mailer;

    public function __construct(
        DataResponseFactoryInterface $factory,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        InvCustomService $invcustomService,
        InvAmountService $invamountService,
        InvItemService $invitemService,
        InvRecurringService $invrecurringService,
        InvTaxRateService $invtaxrateService,
        Session $session,
        SR $s,
        IS $iS,
        TranslatorInterface $translator,
        MailerInterface $mailer,
    ) {
        $this->factory = $factory;
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/invrecurring')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->userService = $userService;
        $this->invCustomService = $invcustomService;
        $this->invAmountService = $invamountService;
        $this->invItemService = $invitemService;
        $this->invrecurringService = $invrecurringService;
        $this->invTaxRateService = $invtaxrateService;
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->s = $s;
        $this->iS = $iS;
        $this->translator = $translator;
        $this->_logger = new Logger();
        $this->mailer = $mailer;
    }

    /**
     * @return string
     */
    private function alert(): string
    {
        return $this->viewRenderer->renderPartialAsString(
            '//invoice/layout/alert',
            [
        'flash' => $this->flash
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

    /**
     * @param CurrentRoute $currentRoute
     * @param IRR $irR
     */
    public function index(CurrentRoute $currentRoute, IRR $irR): \Yiisoft\DataResponse\DataResponse
    {
        $pageNum = (int)$currentRoute->getArgument('page', '1');
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = $pageNum > 0 ? $pageNum : 1;
        $paginator = (new OffsetPaginator($this->invrecurrings($irR)))
        ->withPageSize((int)$this->s->getSetting('default_list_limit'))
        ->withCurrentPage($currentPageNeverZero);
        $numberhelper = new NumberHelper($this->s);
        $canEdit = $this->rbac();
        $parameters = [
          'paginator' => $paginator,
          'canEdit' => $canEdit,
          'defaultPageSizeOffsetPaginator' => $this->s->getSetting('default_list_limit')
                                                    ? (int)$this->s->getSetting('default_list_limit') : 1,
          'recur_frequencies' => $numberhelper->recur_frequencies(),
          'invrecurrings' => $this->invrecurrings($irR),
          'alert' => $this->alert()
        ];
        return $this->viewRenderer->render('index', $parameters);
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param iR $iR
     * @return Response
     */
    public function add(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        iR $iR
    ): Response {
        $inv_id = $currentRoute->getArgument('inv_id');
        $invRecurring = new InvRecurring();
        $form = new InvRecurringForm($invRecurring, (int)$inv_id);
        if (null !== $inv_id) {
            $baseInvoice = $iR->repoInvUnloadedquery($inv_id);
            if (null !== $baseInvoice) {
                // Only invoices with a status of sent can be  made recurring
                if ($baseInvoice->getStatus_id() == 2) {
                    $invDateCreated = $baseInvoice->getDate_created();
                    $parameters = [
                        'title' => $this->translator->translate('invoice.add'),
                        'actionName' => 'invrecurring/add',
                        'actionArguments' => ['inv_id' => $inv_id],
                        'errors' => [],
                        'invDateCreated' => $invDateCreated,
                        'form' => $form
                    ];
                    if ($request->getMethod() === Method::POST) {
                        $body = $request->getParsedBody();
                        if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                            /**
                             * @psalm-suppress PossiblyInvalidArgument $body
                             */
                            $this->invrecurringService->saveInvRecurring($invRecurring, $body);
                            return $this->webService->getRedirectResponse('invrecurring/index');
                        }
                        $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                        $parameters['form'] = $form;
                    }
                    return $this->viewRenderer->render('_form', $parameters);
                }
                $this->flash_message('danger', $this->translator->translate('invoice.recurring.sent.only').'❗');
            }
        }
        return $this->webService->getNotFoundResponse();
    }

    //inv.js create_recurring_confirm_multiple function calls this function

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     */
    public function multiple(Request $request, FormHydrator $formHydrator, IR $iR): \Yiisoft\DataResponse\DataResponse
    {
        $data = $request->getQueryParams();
        $parameters = ['success' => 0];
        /**
         * Purpose: Provide a list of ids from inv/index checkbox column as an array
         * @var array $data['keylist']
         */
        $keyList = $data['keylist'] ?? [];
        if (!empty($keyList)) {
            /**
             * @var string $key
             * @var string $value
             */
            foreach ($keyList as $key => $value) {
                $baseInvoice = $iR->repoInvUnloadedquery($value);
                if (null !== $baseInvoice) {
                    if ($baseInvoice->getStatus_id() == '2') {
                        $invRecurring = new InvRecurring();
                        $form = new InvRecurringForm($invRecurring, (int)$value);
                        $body_array = [
                            'inv_id' => $value,
                            'start' => $data['recur_start_date'] ?? null,
                            'end' => $data['recur_end_date'] ?? null,
                            'frequency' => $data['recur_frequency'],
                            'next' => $data['recur_start_date'] ?? null
                        ];
                        if ($formHydrator->populateAndValidate($form, $body_array)) {
                            $this->invrecurringService->saveInvRecurring($invRecurring, $body_array);
                        }
                    } else {
                        return $this->factory->createResponse(Json::encode(['success' => 0,
                        'message' => $this->translator->translate('invoice.recurring.status.sent.only')]));
                    }
                } else {
                    return $this->factory->createResponse(Json::encode(['success' => 0, 'message' => '']));
                }
            }
            return $this->factory->createResponse(Json::encode(['success' => 1]));
        }
        return $this->factory->createResponse(Json::encode(['success' => 0, 'message' => $this->translator->translate('invoice.recurring.no.invoices.selected')]));
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
            $ivr = $iR->repoInvRecurringquery($inv_recurring->getId());
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
        IRR $invrecurringRepository
    ): Response {
        $inv_recurring = $this->invrecurring($currentRoute, $invrecurringRepository);
        if ($inv_recurring) {
            $form = new InvRecurringForm($inv_recurring, (int)$inv_recurring->getInv_id());
            $base_invoice = $iR->repoInvUnloadedquery($inv_recurring->getInv_id());
            if (null !== $base_invoice) {
                $invDateCreated = $base_invoice->getDate_created();
                $parameters = [
                    'title' => $this->translator->translate('i.edit'),
                    'actionName' => 'invrecurring/start',
                    'actionArguments' => ['id' => $inv_recurring->getId()],
                    'errors' => [],
                    'invDateCreated' => $invDateCreated,
                    'form' => $form,
                ];
                if ($request->getMethod() === Method::POST) {
                    $body = $request->getParsedBody();
                    if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                        /**
                         * @psalm-suppress PossiblyInvalidArgument $body
                         */
                        $this->invrecurringService->saveInvRecurring($inv_recurring, $body);
                        return $this->webService->getRedirectResponse('invrecurring/index');
                    }
                    $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                    $parameters['form'] = $form;
                }
                return $this->viewRenderer->render('_form', $parameters);
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
        IRR $invrecurringRepository
    ): Response {
        try {
            $inv_recurring = $this->invrecurring($currentRoute, $invrecurringRepository);
            if ($inv_recurring) {
                $this->invrecurringService->deleteInvRecurring($inv_recurring);
                $this->flash_message('info', $this->translator->translate('invoice.invoice.recurring.deleted'));
                return $this->webService->getRedirectResponse('invrecurring/index');
            }
            return $this->webService->getNotFoundResponse();
        } catch (\Exception $e) {
            $this->flash_message('danger', $e->getMessage());
            unset($e);
            return $this->webService->getRedirectResponse('invrecurring/index');
        }
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
            $invrecurring = $invrecurringRepository->repoInvRecurringquery($id);
            // InvRecurring/null can be returned here
            return $invrecurring;
        }
        return $invrecurring;
    }

    /**
     * @param IRR $invrecurringRepository
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function invrecurrings(IRR $invrecurringRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        $invrecurrings = $invrecurringRepository->findAllPreloaded();
        return $invrecurrings;
    }

    /**
     * @return Response|true
     */
    private function rbac(): bool|Response
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit) {
            $this->flash_message('warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('invrecurring/index');
        }
        return $canEdit;
    }

    /**
     * @param Request $request
     * @param IR $iR
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function get_recur_start_date(Request $request, IR $iR): \Yiisoft\DataResponse\DataResponse
    {
        $body = $request->getQueryParams();
        /**
         * @var int $body['inv_id']
         */
        $inv_id = $body['inv_id'];
        $base_invoice = $iR->repoInvUnloadedquery((string)$inv_id);
        if (null !== $base_invoice) {
            $immutable_invoice_date = $base_invoice->getDate_created();
            // see InvRecurringRepository recur_frequencies eg. '8M' => 'calendar_month_8',
            $recur_frequency = (string)$body['frequency'];
            $dateHelper = new DateHelper($this->s);
            $parameters = [
                'success' => 1,
                // Show the recur_start_date in Y-m-d format
                'start_date' => $dateHelper->add_to_immutable($immutable_invoice_date, $recur_frequency)
            ];
            return $this->factory->createResponse(Json::encode($parameters));
        }
        return $this->factory->createResponse(Json::encode(
            [
            'success' => 0]
        ));
    }

    /**
     *
     * @param CurrentRoute $currentRoute
     * @param IRR $invrecurringRepository
     * @param IR $iR
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function view(CurrentRoute $currentRoute, IRR $invrecurringRepository, IR $iR): \Yiisoft\DataResponse\DataResponse|Response
    {
        $inv_recurring = $this->invrecurring($currentRoute, $invrecurringRepository);
        if ($inv_recurring) {
            $invRecurringId = $inv_recurring->getId();
            $form = new InvRecurringForm($inv_recurring, (int)$invRecurringId);
            $invId = $inv_recurring->getInv_id();
            $base_invoice = $iR->repoInvUnloadedquery($invId);
            if (null !== $base_invoice) {
                $invDateCreated = $base_invoice->getDate_created();
                $parameters = [
                    'title' => $this->translator->translate('i.view'),
                    'actionName' => 'invrecurring/view',
                    'actionArguments' => ['id' => $invRecurringId],
                    'errors' => [],
                    'form' => $form,
                    'invDateCreated' => $invDateCreated,
                    'invrecurring' => $invrecurringRepository->repoInvRecurringquery($invRecurringId),
                ];
                return $this->viewRenderer->render('_view', $parameters);
            }
        }
        return $this->webService->getNotFoundResponse();
    }
}
