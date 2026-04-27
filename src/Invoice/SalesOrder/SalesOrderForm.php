<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrder;

use App\Infrastructure\Persistence\SalesOrder\SalesOrder;
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
    private ?int $quote_id = null;

    private ?int $inv_id = null;

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

    public static function show(SalesOrder $salesOrder): self
    {
        $form = new self();
        $form->number = $salesOrder->getNumber();
        $form->date_created = $salesOrder->getDateCreated();
        $form->quote_id = $salesOrder->reqQuoteId();
        $form->inv_id = $salesOrder->reqInvId();
        $form->group_id = $salesOrder->reqGroupId();
        $form->client_id = $salesOrder->reqClientId();
        $form->client_po_number = $salesOrder->getClientPoNumber();
        $form->client_po_line_number = $salesOrder->getClientPoLineNumber();
        $form->client_po_person = $salesOrder->getClientPoPerson();
        $form->status_id = $salesOrder->getStatusId();
        $form->discount_amount = $salesOrder->getDiscountAmount();
        $form->url_key = $salesOrder->getUrlKey();
        $form->password = $salesOrder->getPassword();
        $form->notes = $salesOrder->getNotes();
        $form->payment_term = $salesOrder->getPaymentTerm();
        return $form;
    }

    // The Entities ie. Entity/SalesOrder.php have return type string => return type strings in the form
    // get => string ;

    public function getQuoteId(): ?int
    {
        return $this->quote_id;
    }

    public function getInvId(): ?int
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
