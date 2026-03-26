<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrder;

use App\Invoice\Entity\SalesOrder;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Number;
use Yiisoft\Validator\Rule\Required;
use DateTimeImmutable;

final class SalesOrderForm extends FormModel
{
    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    private ?string $number = '';

    private mixed $date_created = '';

    #[Required]
    private ?string $quote_id = null;

    private ?string $inv_id = null;

    #[Required]
    #[Integer(min: 1)]
    private ?int $group_id = null;

    #[Required]
    #[Integer(min: 1)]
    private ?int $client_id = null;

    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    private ?string $client_po_number = null;

    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    private ?string $client_po_line_number = null;

    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    private ?string $client_po_person = null;

    #[Integer(min: 1, max: 9)]
    private ?int $status_id = 1;

    #[Number(min: 0, max: 999999999999999999)]
    private ?float $discount_amount = 0;

    #[Length(min: 0, max: 32, skipOnEmpty: true)]
    private ?string $url_key = '';

    #[Length(min: 0, max: 90, skipOnEmpty: true)]
    private ?string $password = '';

    private ?string $notes = '';

    private ?string $payment_term = '';

    public function __construct(SalesOrder $salesOrder)
    {
        $this->number = $salesOrder->getNumber();
        $this->date_created = $salesOrder->getDateCreated();
        $this->quote_id = $salesOrder->getQuoteId();
        $this->inv_id = $salesOrder->getInvId();
        $this->group_id = (int) $salesOrder->getGroupId();
        $this->client_id = (int) $salesOrder->getClientId();
        $this->client_po_number = $salesOrder->getClientPoNumber();
        $this->client_po_line_number = $salesOrder->getClientPoLineNumber();
        $this->client_po_person = $salesOrder->getClientPoPerson();
        $this->status_id = $salesOrder->getStatusId();
        $this->discount_amount = $salesOrder->getDiscountAmount();
        $this->url_key = $salesOrder->getUrlKey();
        $this->password = $salesOrder->getPassword();
        $this->notes = $salesOrder->getNotes();
        $this->payment_term = $salesOrder->getPaymentTerm();
    }

    // The Entities ie. Entity/SalesOrder.php have return type string => return type strings in the form
    // get => string ;

    public function getQuoteId(): ?string
    {
        return $this->quote_id;
    }

    public function getInvId(): ?string
    {
        return $this->inv_id;
    }

    public function getClientPoNumber(): ?string
    {
        return $this->client_po_number;
    }

    public function getClientPoLineNumber(): ?string
    {
        return $this->client_po_line_number;
    }

    public function getClientPoPerson(): ?string
    {
        return $this->client_po_person;
    }

    public function getClientId(): ?int
    {
        return $this->client_id;
    }

    public function getGroupId(): ?int
    {
        return $this->group_id;
    }

    public function getStatusId(): ?int
    {
        return $this->status_id;
    }

    public function getDateCreated(): string|DateTimeImmutable|null
    {
        /**
         * @var DateTimeImmutable|string|null $this->date_created
         */
        return $this->date_created;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function getDiscountAmount(): ?float
    {
        return $this->discount_amount;
    }

    public function getUrlKey(): ?string
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
     * @return string
     *
     * @psalm-return ''
     */
    #[\Override]
    public function getFormName(): string
    {
        return '';
    }
}
