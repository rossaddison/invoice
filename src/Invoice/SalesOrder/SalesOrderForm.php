<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrder;

use App\Invoice\Entity\SalesOrder;
use DateTimeImmutable;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class SalesOrderForm extends FormModel
{
    private ?string $number     = '';
    private mixed $date_created = '';

    #[Required]
    private ?string $quote_id = null;
    private ?string $inv_id   = null;

    #[Required]
    private ?int $group_id = null;

    #[Required]
    private ?int $client_id                = null;
    private ?string $client_po_number      = null;
    private ?string $client_po_line_number = null;
    private ?string $client_po_person      = null;
    private ?int $status_id                = 1;
    private ?float $discount_amount        = 0;
    private ?float $discount_percent       = 0;
    private ?string $url_key               = '';
    private ?string $password              = '';
    private ?string $notes                 = '';
    private ?string $payment_term          = '';

    public function __construct(SalesOrder $salesOrder)
    {
        $this->number                = $salesOrder->getNumber();
        $this->date_created          = $salesOrder->getDate_created();
        $this->quote_id              = $salesOrder->getQuote_id();
        $this->inv_id                = $salesOrder->getInv_id();
        $this->group_id              = (int) $salesOrder->getGroup_id();
        $this->client_id             = (int) $salesOrder->getClient_id();
        $this->client_po_number      = $salesOrder->getClient_po_number();
        $this->client_po_line_number = $salesOrder->getClient_po_line_number();
        $this->client_po_person      = $salesOrder->getClient_po_person();
        $this->status_id             = $salesOrder->getStatus_id();
        $this->discount_amount       = $salesOrder->getDiscount_amount();
        $this->discount_percent      = $salesOrder->getDiscount_percent();
        $this->url_key               = $salesOrder->getUrl_key();
        $this->password              = $salesOrder->getPassword();
        $this->notes                 = $salesOrder->getNotes();
        $this->payment_term          = $salesOrder->getPaymentTerm();
    }

    // The Entities ie. Entity/SalesOrder.php have return type string => return type strings in the form
    // get => string ;

    public function getQuote_id(): ?string
    {
        return $this->quote_id;
    }

    public function getInv_id(): ?string
    {
        return $this->inv_id;
    }

    public function getClient_po_number(): ?string
    {
        return $this->client_po_number;
    }

    public function getClient_po_line_number(): ?string
    {
        return $this->client_po_line_number;
    }

    public function getClient_po_person(): ?string
    {
        return $this->client_po_person;
    }

    public function getClient_id(): ?int
    {
        return $this->client_id;
    }

    public function getGroup_id(): ?int
    {
        return $this->group_id;
    }

    public function getStatus_id(): ?int
    {
        return $this->status_id;
    }

    public function getDate_created(): string|\DateTimeImmutable|null
    {
        /*
         * @var DateTimeImmutable|string|null $this->date_created
         */
        return $this->date_created;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function getDiscount_amount(): ?float
    {
        return $this->discount_amount;
    }

    public function getDiscount_percent(): ?float
    {
        return $this->discount_percent;
    }

    public function getUrl_key(): ?string
    {
        return $this->url_key;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getPaymentTerm(): ?string
    {
        return $this->payment_term;
    }

    /**
     * @psalm-return ''
     */
    #[\Override]
    public function getFormName(): string
    {
        return '';
    }
}
