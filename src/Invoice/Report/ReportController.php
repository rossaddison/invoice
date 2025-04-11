<?php

declare(strict_types=1);

namespace App\Invoice\Report;

use App\Invoice\BaseController;
// Entites
use App\Invoice\Entity\Client;
use App\Invoice\Entity\InvAmount;
use App\Invoice\Entity\Payment;
// Repositories
use App\Invoice\Client\ClientRepository;
use App\Invoice\Inv\InvRepository;
use App\Invoice\InvAmount\InvAmountRepository;
use App\Invoice\InvItemAmount\InvItemAmountRepository;
use App\Invoice\Payment\PaymentRepository;
use App\Invoice\Product\ProductRepository;
use App\Invoice\Task\TaskRepository;
use App\Invoice\Setting\SettingRepository as sR;
// Helpers
use App\Invoice\Helpers\ClientHelper;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Helpers\MpdfHelper;
use App\Invoice\Helpers\NumberHelper;
// Services and forms
use App\Service\WebControllerService;
use App\User\UserService;
// Psr
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
// Yiisoft
use Yiisoft\Http\Method;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

class ReportController extends BaseController
{
    protected string $controllerName = 'invoice/report';
    
    public function __construct(
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator, 
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR);
    }

    /**
     * @param Request $request
     * @param ViewRenderer $head
     * @param ClientRepository $cR
     * @param InvAmountRepository $iaR
     * @return array|\Mpdf\Mpdf|Response|string
     * @psalm-suppress MixedInferredReturnType
     */
    public function invoice_aging_index(
        Request $request,
        ViewRenderer $head,
        ClientRepository $cR,
        InvAmountRepository $iaR,
    ): Response|\Mpdf\Mpdf|array|string {
        $parameters = [
            'head' => $head,
            'alert' => $this->alert(),
            'actionName' => 'report/invoice_aging_index',
            'actionArguments' => [],
        ];
        if ($request->getMethod() === Method::POST) {
            $data = [
                'results' => $this->invoice_aging_report($cR, $iaR) ?: [],
                'numberHelper' => new NumberHelper($this->sR),
                'dueInvoices' => $this->invoice_aging_due_invoices($iaR) ?: [],
            ];
            $mpdfhelper = new MpdfHelper();
            // Forth parameter $password is empty because these reports are intended for management only
            // Sixth parameter $isInvoice is false because reports and not Invoices are being generated
            // Last parameter $quote_or_invoice is false because reports are being generated which are not meant for clients
            /** @psalm-suppress MixedReturnStatement */
            return $mpdfhelper->pdf_create(
                $this->viewRenderer->renderPartialAsString('//invoice/report/invoice_aging', $data),
                $this->translator->translate('i.invoice_aging'),
                true,
                '',
                $this->sR,
                null,
                null,
                false,
                false,
                [],
                null
            );
        }
        return $this->viewRenderer->render('invoice_aging_index', $parameters);
    }

    /**
     * @param ClientRepository $cR
     * @param InvAmountRepository $iaR
     * @return array
     */
    private function invoice_aging_report(
        ClientRepository $cR,
        InvAmountRepository $iaR
    ): array {
        $clienthelper = new ClientHelper($this->sR);
        $numberhelper = new NumberHelper($this->sR);
        $clients = $cR->count() > 0 ? $cR->findAllPreloaded() : null;
        $fifteens = $iaR->AgingCount(1, 15) > 0 ? $iaR->Aging(1, 15) : null;
        $thirties = $iaR->AgingCount(16, 30) > 0 ? $iaR->Aging(16, 30) : null;
        $overthirties = $iaR->AgingCount(31, 365) > 0 ? $iaR->Aging(31, 365) : null;
        $one_to_year = $iaR->AgingCount(1, 365) > 0 ? $iaR->Aging(1, 365) : null;
        $results = [];
        $row = [
            'client' => '',
            'range_1' => 0.00,
            'range_2' => 0.00,
            'range_3' => 0.00,
            'total_balance' => 0.00,
        ];
        if (null !== $clients) {
            /** @var Client $client */
            foreach ($clients as $client) {
                $row['client'] = $clienthelper->format_client($client);
                if (null !== $fifteens) {
                    $row['range_1'] = $numberhelper->format_amount($this->invoice_aging_sum($fifteens, $client->getClient_id()));
                } else {
                    $row['range_1'] = 0.00;
                }
                if (null !== $thirties) {
                    $row['range_2'] = $numberhelper->format_amount($this->invoice_aging_sum($thirties, $client->getClient_id()));
                } else {
                    $row['range_2'] = 0.00;
                }
                if (null !== $overthirties) {
                    $row['range_3'] = $numberhelper->format_amount($this->invoice_aging_sum($overthirties, $client->getClient_id()));
                } else {
                    $row['range_3'] = 0.00;
                }
                if (null !== $one_to_year) {
                    $row['total_balance'] = $numberhelper->format_amount($this->invoice_aging_sum($one_to_year, $client->getClient_id()));
                } else {
                    $row['total_balance'] = 0.00;
                }
                $results[] = $row;
            }
        }
        return $results;
    }

    /**
     * @param InvAmountRepository $iaR
     * @return array
     */
    private function invoice_aging_due_invoices(InvAmountRepository $iaR): array
    {
        $numberhelper = new NumberHelper($this->sR);
        $fifteens = $iaR->AgingCount(1, 15) > 0 ? $iaR->Aging(1, 15) : null;
        $thirties = $iaR->AgingCount(16, 30) > 0 ? $iaR->Aging(16, 30) : null;
        $overthirties = $iaR->AgingCount(31, 365) > 0 ? $iaR->Aging(31, 365) : null;
        $results = [];
        $row = [
            'range_index' => 0,
            'invoice_number' => '',
            'invoice_balance' => 0.00,
        ];
        if (null !== $fifteens) {
            /** @var InvAmount $fifteen */
            foreach ($fifteens as $fifteen) {
                if ($fifteen->getBalance() > 0) {
                    $row = [
                        'range_index' => 1,
                        'invoice_number' => $fifteen->getInv()?->getNumber(),
                        'invoice_balance' => $numberhelper->format_amount($fifteen->getBalance()),
                    ];
                }
                $results[] = $row;
            }
        }
        if (null !== $thirties) {
            /** @var InvAmount $thirty */
            foreach ($thirties as $thirty) {
                if ($thirty->getBalance() > 0) {
                    $row = [
                        'range_index' => 2,
                        'invoice_number' => $thirty->getInv()?->getNumber(),
                        'invoice_balance' => $numberhelper->format_amount($thirty->getBalance()),
                    ];
                }
                $results[] = $row;
            }
        }
        if (null !== $overthirties) {
            /** @var InvAmount $overthirty */
            foreach ($overthirties as $overthirty) {
                if ($overthirty->getBalance() > 0) {
                    $row = [
                        'range_index' => 3,
                        'invoice_number' => $overthirty->getInv()?->getNumber(),
                        'invoice_balance' => $numberhelper->format_amount($overthirty->getBalance()),
                    ];
                }
                $results[] = $row;
            }
        }
        return $results;
    }

    /**
     * @psalm-param \Yiisoft\Data\Reader\DataReaderInterface<array-key, array|object> $invamounts
     */
    private function invoice_aging_sum(\Yiisoft\Data\Reader\DataReaderInterface $invamounts, int|null $client_id): float
    {
        $sum = 0.00;
        foreach ($invamounts as $invamount) {
            if ($invamount instanceof InvAmount) {
                $sum += ($client_id == $invamount->getInv()?->getClient_id()) ? ($invamount->getBalance() ?? 0.00) : 0.00;
            }
        }
        return $sum;
    }

    /**
     * @param Request $request
     * @param ViewRenderer $head
     * @param PaymentRepository $pymtR
     * @return array|\Mpdf\Mpdf|Response|string
     * @psalm-suppress MixedInferredReturnType
     */
    public function payment_history_index(
        Request $request,
        ViewRenderer $head,
        PaymentRepository $pymtR,
    ): Response|\Mpdf\Mpdf|array|string {
        $dateHelper = new DateHelper($this->sR);
        $parameters = [
            'head' => $head,
            'alert' => $this->alert(),
            'actionName' => 'report/payment_history_index',
            'actionArguments' => [],
            'dateHelper' => $dateHelper,
            'startTaxYear' => $dateHelper->tax_year_to_immutable()->format('Y-m-d'),
        ];
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody();
            if (is_array($body)) {
                $from_date = (string)$body['from_date'];
                $to_date = (string)$body['to_date'];
                $data = [
                    'from_date' => $from_date,
                    'to_date' => $to_date,
                    //Date Invoice Client Payment Method Note Amount
                    'results' => $this->payment_history_report($pymtR, $from_date, $to_date)
                             ?: [],
                    'dateHelper' => $dateHelper,
                    'numberHelper' => new NumberHelper($this->sR),
                ];
                $mpdfHelper = new MpdfHelper();
                /** @psalm-suppress MixedReturnStatement */
                return $mpdfHelper->pdf_create(
                    $this->viewRenderer->renderPartialAsString('//invoice/report/payment_history', $data),
                    $this->translator->translate('i.payment_history'),
                    true,
                    '',
                    $this->sR,
                    null,
                    null,
                    false,
                    false,
                    [],
                    null
                );
            } //is_array body
            return $this->webService->getNotFoundResponse();
        }
        return $this->viewRenderer->render('payment_history_index', $parameters);
    }

    /**
     * @param PaymentRepository $pymtR
     * @param string $from
     * @param string $to
     *
     * @return (mixed|string)[][]
     *
     * @psalm-return list{0?: array{payment_date: mixed, payment_invoice: mixed, payment_client: string, payment_method: mixed, payment_note: mixed, payment_amount: mixed},...}
     */
    private function payment_history_report(
        PaymentRepository $pymtR,
        string $from,
        string $to
    ): array {
        $clienthelper = new ClientHelper($this->sR);
        $payments = $pymtR->repoPaymentLoaded_from_to_count($from, $to) > 0 ? $pymtR->repoPaymentLoaded_from_to($from, $to) : null;
        //Report Headings: Date, Invoice, Client, Payment Method, Note, Amount
        $results = [];
        $row = [
            'payment_date' => '',
            'payment_invoice' => '',
            'payment_client' => '',
            'payment_method' => '',
            'payment_note' => '',
            'payment_amount' => '',
        ];
        if (null !== $payments) {
            /** @var Payment $payment */
            foreach ($payments as $payment) {
                $row['payment_date'] = $payment->getPayment_date();
                $row['payment_invoice'] = $payment->getInv()?->getNumber();
                // Client Name and Surname
                $row['payment_client'] = $clienthelper->format_client($payment->getInv()?->getClient());
                $row['payment_method'] = $payment->getPaymentMethod()?->getName();
                $row['payment_note'] = $payment->getNote();
                $row['payment_amount'] = $payment->getAmount();
                $results[] = $row;
            }
            return $results;
        }
        return [];
    }

    /**
     * @param Request $request
     * @param ViewRenderer $head
     * @param ClientRepository $cR
     * @param InvRepository $iR
     * @param InvAmountRepository $iaR
     * @return array|\Mpdf\Mpdf|Response|string
     * @psalm-suppress MixedInferredReturnType
     */
    public function sales_by_client_index(
        Request $request,
        ViewRenderer $head,
        ClientRepository $cR,
        InvRepository $iR,
        InvAmountRepository $iaR,
    ): Response|\Mpdf\Mpdf|array|string {
        $dateHelper = new DateHelper($this->sR);
        $parameters = [
            'head' => $head,
            'alert' => $this->alert(),
            'actionName' => 'report/sales_by_client_index',
            'actionArguments' => [],
            'dateHelper' => $dateHelper,
            'startTaxYear' => $dateHelper->tax_year_to_immutable()->format('Y-m-d'),
        ];
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody();
            if (is_array($body)) {
                $from_date = (string)$body['from_date'];
                $to_date = (string)$body['to_date'];
                $data = [
                    'from_date' => $from_date,
                    'to_date' => $to_date,
                    'results' => $this->sales_by_client_report($cR, $iR, $dateHelper->date_to_mysql($from_date), $dateHelper->date_to_mysql($to_date), $iaR),
                    'numberHelper' => new NumberHelper($this->sR),
                    'clientHelper' => new ClientHelper($this->sR),
                ];
                $mpdfhelper = new MpdfHelper();
                /** @psalm-suppress MixedReturnStatement */
                return $mpdfhelper->pdf_create(
                    $this->viewRenderer->renderPartialAsString('//invoice/report/sales_by_client', $data),
                    $this->translator->translate('i.sales_by_client'),
                    true,
                    '',
                    $this->sR,
                    null,
                    null,
                    false,
                    false,
                    [],
                    null
                );
            } // is_array body
            return $this->webService->getNotFoundResponse();
        }
        return $this->viewRenderer->render('sales_by_client_index', $parameters);
    }

    /**
     * @param ClientRepository $cR
     * @param InvRepository $iR
     * @param string $from
     * @param string $to
     * @param InvAmountRepository $iaR
     * @return array
     */
    private function sales_by_client_report(
        ClientRepository $cR,
        InvRepository $iR,
        string $from,
        string $to,
        InvAmountRepository $iaR
    ): array {
        // Report Heading:  Sales by Client
        // Report Heading2: From To Date
        // Horizontal heading: Client Name and Surname, Inv Count, Sales Total, Item Tax, Tax, Sales With Tax
        $results = [];
        $row = [
            'client_name_surname' => '',
            'inv_count' => 0.00,
            'sales_no_tax' => 0.00,
            // plus (before/after item tax)
            'item_tax_total' => 0.00,
            // plus
            'tax_total' => 0.00,
            // equals
            'sales_with_tax' => 0.00,
        ];
        $clienthelper = new ClientHelper($this->sR);
        $clients = $cR->findAllPreloaded();
        /**
         * @var Client $client
         */
        foreach ($clients as $client) {
            $client_id = $client->getClient_id();
            if (null !== $client_id) {
                // Client Name and Surname
                $row['client_name_surname'] = $clienthelper->format_client($client);
                $row['inv_count'] = $iR->repoCountByClient($client_id);
                $row['sales_no_tax'] = $iR->repoCountByClient($client_id) > 0
                              ? $iR->with_item_subtotal_from_to($client_id, $from, $to, $iaR)
                              : 0.00;
                // plus
                $row['item_tax_total'] = $iR->repoCountByClient($client_id) > 0
                              ? $iR->with_item_tax_total_from_to($client_id, $from, $to, $iaR)
                              : 0.00;
                // plus
                $row['tax_total'] = $iR->repoCountByClient($client_id) > 0
                              ? $iR->with_tax_total_from_to($client_id, $from, $to, $iaR)
                              : 0.00;
                // equals
                $row['sales_with_tax'] = $iR->repoCountByClient($client_id) > 0
                              ? $iR->with_total_from_to($client_id, $from, $to, $iaR)
                              : 0.00;
                $results[] = $row;
            } // null!==$client_id;
        }
        return $results;
    }

    /*****************
     * PRODUCT
     ******************/

    /**
     * @param Request $request
     * @param ViewRenderer $head
     * @param ProductRepository $pR
     * @param InvRepository $iR
     * @param InvItemAmountRepository $iiaR
     * @return array|\Mpdf\Mpdf|Response|string
     * @psalm-suppress MixedInferredReturnType
     */
    public function sales_by_product_index(
        Request $request,
        ViewRenderer $head,
        ProductRepository $pR,
        InvRepository $iR,
        InvItemAmountRepository $iiaR,
    ): Response|\Mpdf\Mpdf|array|string {
        $this->flashMessage('info', $this->translator->translate('invoice.report.sales.by.product.info'));
        $dateHelper = new DateHelper($this->sR);
        $parameters = [
            'head' => $head,
            'alert' => $this->alert(),
            'actionName' => 'report/sales_by_product_index',
            'actionArguments' => [],
            'dateHelper' => $dateHelper,
            'startTaxYear' => $dateHelper->tax_year_to_immutable()->format('Y-m-d'),
        ];
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody();
            if (is_array($body)) {
                $from_date = (string)$body['from_date'];
                $to_date = (string)$body['to_date'];
                $data = [
                    'from_date' => $from_date,
                    'to_date' => $to_date,
                    'results' => $this->sales_by_product_report($pR, $iR, $dateHelper->date_to_mysql($from_date), $dateHelper->date_to_mysql($to_date), $iiaR),
                    'numberHelper' => new NumberHelper($this->sR),
                ];
                $mpdfhelper = new MpdfHelper();
                /** @psalm-suppress MixedReturnStatement */
                return $mpdfhelper->pdf_create(
                    $this->viewRenderer->renderPartialAsString('///invoice/report/sales_by_product', $data),
                    $this->translator->translate('invoice.report.sales.by.product'),
                    true,
                    '',
                    $this->sR,
                    null,
                    null,
                    false,
                    false,
                    [],
                    null
                );
            } // is_array body
            return $this->webService->getNotFoundResponse();
        }
        return $this->viewRenderer->render('sales_by_product_index', $parameters);
    }

    /**
     * @param ProductRepository $pR
     * @param InvRepository $iR
     * @param string $from
     * @param string $to
     * @param InvItemAmountRepository $iiaR
     * @return array
     */
    private function sales_by_product_report(
        ProductRepository $pR,
        InvRepository $iR,
        string $from,
        string $to,
        InvItemAmountRepository $iiaR,
    ): array {
        // Report Heading:  Sales by Product
        // Report Heading2: From To Date
        // Horizontal heading: Product Name, Inv Count, Sales Total, Item Tax, Tax, Sales With Tax
        $results = [];
        $row = [
            'product_name' => '',
            'inv_count' => 0.00,
            'sales_no_tax' => 0.00,
            // plus (before/after item tax)
            'item_tax_total' => 0.00,
        ];
        $products = $pR->findAllPreloaded();
        /**
         * @var \\App\Invoice\Entity\Product $product
         */
        foreach ($products as $product) {
            $product_id = (int)$product->getProduct_id();
            if (!empty($product_id)) {
                // Product name
                $row['product_name'] = (string) $product->getProduct_name();
                $row['inv_count'] = $iR->repoCountByProduct($product_id);
                $row['sales_no_tax'] = $iR->repoCountByProduct($product_id) > 0
                              ? $iR->with_item_subtotal_from_to_using_product($product_id, $from, $to, $iiaR)
                              : 0.00;
                // plus
                $row['item_tax_total'] = $iR->repoCountByProduct($product_id) > 0
                              ? $iR->with_item_tax_total_from_to_using_product($product_id, $from, $to, $iiaR)
                              : 0.00;
                $results[] = $row;
            } // null!==$product_id;
        }
        return $results;
    }

    /*******************
     * TASK
     *******************/

    /**
     * @param Request $request
     * @param ViewRenderer $head
     * @param TaskRepository $taskR
     * @param InvRepository $iR
     * @param InvItemAmountRepository $iiaR
     * @return array|\Mpdf\Mpdf|Response|string
     * @psalm-suppress MixedInferredReturnType
     */
    public function sales_by_task_index(
        Request $request,
        ViewRenderer $head,
        TaskRepository $taskR,
        InvRepository $iR,
        InvItemAmountRepository $iiaR
    ): Response|\Mpdf\Mpdf|array|string {
        $this->flashMessage('info', $this->translator->translate('invoice.report.sales.by.task.info'));
        $dateHelper = new DateHelper($this->sR);
        $body = $request->getParsedBody();
        $parameters = [
            'head' => $head,
            'body' => $body,
            'alert' => $this->alert(),
            'actionName' => 'report/sales_by_task_index',
            'actionArguments' => [],
            'dateHelper' => $dateHelper,
            'startTaxYear' => $dateHelper->tax_year_to_immutable()->format('Y-m-d'),
        ];
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody();
            if (is_array($body)) {
                $from_date = (string)$body['from_date'];
                $to_date = (string)$body['to_date'];
                $data = [
                    'from_date' => $from_date,
                    'to_date' => $to_date,
                    'results' => $this->sales_by_task_report($taskR, $iR, $dateHelper->date_to_mysql($from_date), $dateHelper->date_to_mysql($to_date), $iiaR),
                    'numberHelper' => new NumberHelper($this->sR),
                ];
                $mpdfhelper = new MpdfHelper();
                /** @psalm-suppress MixedReturnStatement */
                return $mpdfhelper->pdf_create(
                    $this->viewRenderer->renderPartialAsString('//invoice/report/sales_by_task', $data),
                    $this->translator->translate('invoice.report.sales.by.task'),
                    true,
                    '',
                    $this->sR,
                    null,
                    null,
                    false,
                    false,
                    [],
                    null
                );
            } // is_array body
            return $this->webService->getNotFoundResponse();
        }
        return $this->viewRenderer->render('sales_by_task_index', $parameters);
    }

    /**
     * @param TaskRepository $taskR
     * @param InvRepository $iR
     * @param string $from
     * @param string $to
     * @param InvItemAmountRepository $iiaR
     * @return array
     */
    private function sales_by_task_report(
        TaskRepository $taskR,
        InvRepository $iR,
        string $from,
        string $to,
        InvItemAmountRepository $iiaR,
    ): array {
        // Report Heading:  Sales by Task
        // Report Heading2: From To Date
        // Horizontal heading: Task Name, Inv Count, Sales Total, Item Tax, Tax, Sales With Tax
        $results = [];
        $row = [
            'task_name' => '',
            'inv_count' => 0.00,
            'sales_no_tax' => 0.00,
            // plus (before/after item tax)
            'item_tax_total' => 0.00,
        ];
        $tasks = $taskR->findAllPreloaded();
        /**
         * @var \\App\Invoice\Entity\Task $task
         */
        foreach ($tasks as $task) {
            $task_id = (int)$task->getId();
            if (!empty($task_id)) {
                // Task name
                $row['task_name'] = (string)$task->getName();
                $row['inv_count'] = $iR->repoCountByTask($task_id);
                $row['sales_no_tax'] = $iR->repoCountByTask($task_id) > 0
                              ? $iR->with_item_subtotal_from_to_using_task($task_id, $from, $to, $iiaR)
                              : 0.00;
                // plus
                $row['item_tax_total'] = $iR->repoCountByTask($task_id) > 0
                              ? $iR->with_item_tax_total_from_to_using_task($task_id, $from, $to, $iiaR)
                              : 0.00;
                $results[] = $row;
            } // null!==$task_id;
        }
        return $results;
    }

    /**
     * @param Request $request
     * @param ViewRenderer $head
     * @param ClientRepository $cR
     * @param InvRepository $iR
     * @param InvAmountRepository $iaR
     * @return array|\Mpdf\Mpdf|Response|string
     * @psalm-suppress MixedInferredReturnType
     */
    public function sales_by_year_index(
        Request $request,
        ViewRenderer $head,
        ClientRepository $cR,
        InvRepository $iR,
        InvAmountRepository $iaR
    ): Response|\Mpdf\Mpdf|array|string {
        $dateHelper = new DateHelper($this->sR);
        $body = $request->getParsedBody();
        $parameters = [
            'head' => $head,
            'body' => $body,
            'alert' => $this->alert(),
            'actionName' => 'report/sales_by_year_index',
            'actionArguments' => [],
            'dateHelper' => $dateHelper,
            'startTaxYear' => $dateHelper->tax_year_to_immutable()->format('Y-m-d'),
        ];
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody();
            if (is_array($body)) {
                $from_date = (string)$body['from_date'];
                $to_date = (string)$body['to_date'];
                $data = [
                    'from_date' => $from_date,
                    'to_date' => $to_date,
                    'results' => $this->sales_by_year_report($cR, $iR, $dateHelper->date_to_mysql($from_date), $dateHelper->date_to_mysql($to_date), $iaR)
                             ?: [],
                    'n' => new NumberHelper($this->sR),
                    'clienthelper' => new ClientHelper($this->sR),
                ];
                $mpdfhelper = new MpdfHelper();
                // Forth parameter $password is empty because these reports are intended for management only
                // Sixth parameter $isInvoice is false because reports and not Invoices are being generated
                // Last parameter $quote_or_invoice is false because reports are being generated which are not meant for clients
                /** @psalm-suppress MixedReturnStatement */
                return $mpdfhelper->pdf_create(
                    $this->viewRenderer->renderPartialAsString('//invoice/report/sales_by_year', $data),
                    $this->translator->translate('i.sales_by_date'),
                    true,
                    '',
                    $this->sR,
                    null,
                    null,
                    false,
                    false,
                    [],
                    null
                );
            } // is_array body
            return $this->webService->getNotFoundResponse();
        }
        return $this->viewRenderer->render('sales_by_year_index', $parameters);
    }

    private function sales_by_year_report(
        ClientRepository $cR,
        InvRepository $iR,
        string $from,
        string $to,
        InvAmountRepository $iaR
    ): array {
        $results = [];
        $year = [
            'year' => '',
            'Name' => '',
            'VAT_ID' => '',
            'period_sales_no_tax' => 0.00,
            // plus (before/after item tax)
            'period_item_tax_total' => 0.00,
            // plus
            'period_tax_total' => 0.00,
            // equals
            'period_sales_with_tax' => 0.00,
            // total of customer payments
            'period_total_paid' => 0.00,
            'quarters' => [
                'first' => [
                    'beginning' => '',
                    'end' => '',
                    'sales_no_tax' => 0.00,
                    'item_tax_total' => 0.00,
                    'tax_total' => 0.00,
                    'sales_with_tax' => 0.00,
                    'paid' => 0.00,
                ],
                'second' => [
                    'beginning' => '',
                    'end' => '',
                    'sales_no_tax' => 0.00,
                    'item_tax_total' => 0.00,
                    'tax_total' => 0.00,
                    'sales_with_tax' => 0.00,
                    'paid' => 0.00,
                ],
                'third' => [
                    'beginning' => '',
                    'end' => '',
                    'sales_no_tax' => 0.00,
                    'item_tax_total' => 0.00,
                    'tax_total' => 0.00,
                    'sales_with_tax' => 0.00,
                    'paid' => 0.00,
                ],
                'fourth' => [
                    'beginning' => '',
                    'end' => '',
                    'sales_no_tax' => 0.00,
                    'item_tax_total' => 0.00,
                    'tax_total' => 0.00,
                    'sales_with_tax' => 0.00,
                    'paid' => 0.00,
                ],
            ],
        ];
        $clientHelper = new ClientHelper($this->sR);
        $dateHelper = new DateHelper($this->sR);
        $clients = $cR->count() > 0 ? $cR->findAllPreloaded() : null;
        if (null !== $clients) {
            /** @var Client $client */
            foreach ($clients as $client) {
                // Convert the mysql $from which is a string into an immutable so that we can use the add function
                // associated with immutable dates
                $immutable_from = $dateHelper->ymd_to_immutable($from);
                $immutable_to = $dateHelper->ymd_to_immutable($to);
                $interval = new \DateInterval('P1Y');
                $daterange = new \DatePeriod($immutable_from, $interval, $immutable_to);
                $client_id = (int)$client->getClient_id();
                foreach ($daterange as $current_year) {
                    $additional_year = $this->quarters($year, $immutable_from, $current_year, $client, $clientHelper, $client_id, $iR, $iaR);
                    $results[] = $additional_year;
                    $immutable_from = $immutable_from->add(new \DateInterval('P1Y'));
                }
            }
            return $results;
        }
        return [];
    }

    /**
     * @param (float|(float|string)[][]|string)[] $year $year
     * @param \DateTimeImmutable $immutable_from
     * @param \DateTimeImmutable $current_year
     * @param Client $client
     * @param ClientHelper $clienthelper
     * @param int $client_id
     * @param InvRepository $iR
     * @param InvAmountRepository $iaR
     * @return array
     */
    private function quarters(
        array $year,
        \DateTimeImmutable $immutable_from,
        \DateTimeImmutable $current_year,
        Client $client,
        ClientHelper $clienthelper,
        int $client_id,
        InvRepository $iR,
        InvAmountRepository $iaR
    ): array {
        if ($client_id) {
            $quarters = ['first' => 3, 'second' => 6, 'third' => 9, 'fourth' => 12];
            // Develop all the quarters from ONE immutable (unchangeable) start date
            // Each immutable date is presented in the mysql Y-m-d format for comparison with the mysql dates
            $immutable_from_start_date = $immutable_from;

            foreach ($quarters as $quarter => $month_ending) {
                $quarter_from = $immutable_from_start_date->add(new \DateInterval('P' . (string)$month_ending . 'M'))
                                                          ->sub(new \DateInterval('P3M'))
                                                          ->add(new \DateInterval('P1D'))
                                                          ->format('Y-m-d');

                $quarter_to = $immutable_from_start_date->add(new \DateInterval('P' . (string)$month_ending . 'M'))
                                                          ->format('Y-m-d');

                $year['quarters'][$quarter]['beginning'] = $quarter_from;
                $year['quarters'][$quarter]['end'] = $quarter_to;

                $sales_no_tax = $iR->repoCountByClient($client_id) > 0
                              ? $iR->with_item_subtotal_from_to(
                                  $client_id,
                                  $quarter_from,
                                  $quarter_to,
                                  $iaR
                              )
                              : 0.00;
                $year['quarters'][$quarter]['sales_no_tax'] = $sales_no_tax;

                $item_tax_total = $iR->repoCountByClient($client_id) > 0
                                ? $iR->with_item_tax_total_from_to(
                                    $client_id,
                                    $quarter_from,
                                    $quarter_to,
                                    $iaR
                                )
                                : 0.00;
                $year['quarters'][$quarter]['item_tax_total'] = $item_tax_total;

                $tax_total = $iR->repoCountByClient($client_id) > 0
                              ? $iR->with_tax_total_from_to(
                                  $client_id,
                                  $quarter_from,
                                  $quarter_to,
                                  $iaR
                              )
                              : 0.00;
                $year['quarters'][$quarter]['tax_total'] = $tax_total;

                $sales_with_tax = $iR->repoCountByClient($client_id) > 0
                              ? $iR->with_total_from_to(
                                  $client_id,
                                  $quarter_from,
                                  $quarter_to,
                                  $iaR
                              )
                              : 0.00;
                $year['quarters'][$quarter]['sales_with_tax'] = $sales_with_tax;

                $paid = $iR->repoCountByClient($client_id) > 0
                              ? $iR->with_paid_from_to(
                                  $client_id,
                                  $quarter_from,
                                  $quarter_to,
                                  $iaR
                              )
                              : 0.00;
                $year['quarters'][$quarter]['paid'] = $paid;
            }
            $from = $year['quarters']['first']['beginning'];
            $to = $year['quarters']['fourth']['end'];
            $year['year'] = $current_year->format('Y');
            // Client Name and Surname
            $year['Name'] = $clienthelper->format_client($client);
            // Item subtotal = Sales without taxes
            $year['VAT_ID'] = $client->getClient_vat_id();
            $year['period_sales_no_tax'] = $iR->repoCountByClient($client_id) > 0
                          ? $iR->with_item_subtotal_from_to($client_id, $from, $to, $iaR)
                          : 0.00;
            // plus
            $year['period_item_tax_total'] = $iR->repoCountByClient($client_id) > 0
                          ? $iR->with_item_tax_total_from_to($client_id, $from, $to, $iaR)
                          : 0.00;
            // plus
            $year['period_tax_total'] = $iR->repoCountByClient($client_id) > 0
                          ? $iR->with_tax_total_from_to($client_id, $from, $to, $iaR)
                          : 0.00;
            // equals
            $year['period_sales_with_tax'] = $iR->repoCountByClient($client_id) > 0
                          ? $iR->with_total_from_to($client_id, $from, $to, $iaR)
                          : 0.00;
            // what the customer has actually paid towards the annual sales with tax
            $year['period_total_paid'] = $iR->repoCountByClient($client_id) > 0
                          ? $iR->with_paid_from_to($client_id, $from, $to, $iaR)
                          : 0.00;
            return $year;
        }
        return [];
    }
}
