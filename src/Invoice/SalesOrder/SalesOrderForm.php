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

    #[Number(min: 0, max: 100)]
    private ?float $discount_percent = 0;

    #[Length(min: 0, max: 32, skipOnEmpty: true)]
    private ?string $url_key = '';

    #[Length(min: 0, max: 90, skipOnEmpty: true)]
    private ?string $password = '';

    private ?string $notes = '';

    private ?string $payment_term = '';

    public function __construct(SalesOrder $salesOrder)
    {
        $this->number = $salesOrder->getNumber();
        $this->date_created = $salesOrder->getDate_created();
        $this->quote_id = $salesOrder->getQuote_id();
        $this->inv_id = $salesOrder->getInv_id();
        $this->group_id = (int) $salesOrder->getGroup_id();
        $this->client_id = (int) $salesOrder->getClient_id();
        $this->client_po_number = $salesOrder->getClient_po_number();
        $this->client_po_line_number = $salesOrder->getClient_po_line_number();
        $this->client_po_person = $salesOrder->getClient_po_person();
        $this->status_id = $salesOrder->getStatus_id();
        $this->discount_amount = $salesOrder->getDiscount_amount();
        $this->discount_percent = $salesOrder->getDiscount_percent();
        $this->url_key = $salesOrder->getUrl_key();
        $this->password = $salesOrder->getPassword();
        $this->notes = $salesOrder->getNotes();
        $this->payment_term = $salesOrder->getPaymentTerm();
    }

    // The Entities ie. Entity/SalesOrder.php have return type string => return type strings in the form
    // get => string ;

    public function getQuote_id(): string|null
    {
        return $this->quote_id;
    }

    public function getInv_id(): string|null
    {
        return $this->inv_id;
    }

    public function getClient_po_number(): string|null
    {
        return $this->client_po_number;
    }

    public function getClient_po_line_number(): string|null
    {
        return $this->client_po_line_number;
    }

    public function getClient_po_person(): string|null
    {
        return $this->client_po_person;
    }

    public function getClient_id(): int|null
    {
        return $this->client_id;
    }

    public function getGroup_id(): int|null
    {
        return $this->group_id;
    }

    public function getStatus_id(): int|null
    {
        return $this->status_id;
    }

    public function getDate_created(): string|null|DateTimeImmutable
    {
        /**
         * @var DateTimeImmutable|string|null $this->date_created
         */
        return $this->date_created;
    }

    public function getNumber(): string|null
    {
        return $this->number;
    }

    public function getDiscount_amount(): float|null
    {
        return $this->discount_amount;
    }

    public function getDiscount_percent(): float|null
    {
        return $this->discount_percent;
    }

    public function getUrl_key(): string|null
    {
        return $this->url_key;
    }

    public function getPassword(): string|null
    {
        return $this->password;
    }

    public function getNotes(): string|null
    {
        return $this->notes;
    }

    public function getPaymentTerm(): string|null
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
