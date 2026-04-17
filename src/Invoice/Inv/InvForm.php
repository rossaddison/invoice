<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\Entity\Inv;
use App\Infrastructure\Persistence\Client\Client;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;
use DateTimeImmutable;

final class InvForm extends FormModel
{
    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    private ?string $number = '';
    private mixed $date_created = '';
    // Countries with VAT systems will need these fields
    private mixed $date_modified = '';
    private mixed $date_supplied = '';
    private mixed $date_paid_off = '';
    private mixed $date_tax_point = '';
    private mixed $date_due = '';
    // stand_in_code/description_code
    #[Length(min: 0, max: 3, skipOnEmpty: true)]
    private ?string $stand_in_code = '';
    private ?string $quote_id = '';
    private ?Client $client = null;

    #[Required]
    private ?string $group_id = '';

    #[Required]
    private ?string $client_id = '';

    private ?string $so_id = '';
    private ?int $creditinvoice_parent_id = null;
    private ?int $delivery_id = null;
    private ?int $delivery_location_id = null;
    private ?int $postal_address_id = null;
    private ?int $contract_id = null;
    private ?int $status_id = 1;
    private ?float $discount_amount = 0.00;
    private ?float $discount_percent = 0.00;
    #[Length(min: 0, max: 32, skipOnEmpty: true)]
    private ?string $url_key = '';
    #[Length(min: 0, max: 90, skipOnEmpty: true)]
    private ?string $password = '';
    private ?int $payment_method = 0;
    private ?string $terms = '';
    private ?string $note = '';
    #[Length(min: 0, max: 32, skipOnEmpty: true)]
    private ?string $document_description = '';
    private readonly bool $is_read_only;
    private mixed $time_created = '';

    public function __construct(Inv $inv)
    {
        $this->date_created = $inv->getDateCreated();
        $this->date_modified = $inv->getDateModified();
        $this->client_id = $inv->getClientId();
        $this->group_id = $inv->getGroupId();
        $this->status_id = $inv->getStatusId();
        $this->contract_id = (int) $inv->getContractId();
        $this->delivery_id = (int) $inv->getDeliveryId();
        $this->delivery_location_id = (int) $inv->getDeliveryLocationId();
        $this->postal_address_id = (int) $inv->getPostalAddressId();
        $this->so_id = $inv->getSoId();
        $this->quote_id = $inv->getQuoteId();
        $this->is_read_only = $inv->getIsReadOnly();
        $this->password = $inv->getPassword();
        $this->time_created = $inv->getTimeCreated();
        $this->date_tax_point = $inv->getDateTaxPoint();
        $this->stand_in_code = $inv->getStandInCode();
        $this->date_supplied = $inv->getDateSupplied();
        $this->date_due = $inv->getDateDue();
        $this->number = $inv->getNumber();
        $this->discount_amount = $inv->getDiscountAmount();
        $this->terms = $inv->getTerms();
        $this->note = $inv->getNote();
        $this->document_description = $inv->getDocumentDescription();
        $this->url_key = $inv->getUrlKey();
        $this->payment_method = $inv->getPaymentMethod();
        $this->creditinvoice_parent_id = (int) $inv->getCreditinvoiceParentId();
        /**
         * Related logic: see 
                #[BelongsTo(target: Client::class, nullable: false, fkAction: 'NO ACTION')]
                private ?Client $client = null;
         */
        $this->client = $inv->getClient();
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function getDateCreated(): string|DateTimeImmutable|null
    {
        /**
         * @var DateTimeImmutable|string|null $this->date_created
         */
        return $this->date_created;
    }

    public function getDateModified(): string|DateTimeImmutable|null
    {
        /**
         * @var DateTimeImmutable|string $this->date_modified
         */
        return $this->date_modified;
    }

    public function getDateSupplied(): string|DateTimeImmutable|null
    {
        /**
         * @var DateTimeImmutable|string $this->date_supplied
         */
        return $this->date_supplied;
    }

    public function getDatePaidOff(): string|DateTimeImmutable|null
    {
        /**
         * @var DateTimeImmutable|string $this->date_paid_off
         */
        return $this->date_paid_off;
    }

    public function getDateTaxPoint(): string|DateTimeImmutable|null
    {
        /**
         * @var DateTimeImmutable|string $this->date_tax_point
         */
        return $this->date_tax_point;
    }

    public function getDateDue(): string|DateTimeImmutable|null
    {
        /**
         * @var DateTimeImmutable|string $this->date_due
         */
        return $this->date_due;
    }

    public function getTimeCreated(): string|DateTimeImmutable|null
    {
        /**
         * @var DateTimeImmutable|string $this->time_created
         */
        return $this->time_created;
    }

    public function getStandInCode(): ?string
    {
        return $this->stand_in_code;
    }

    public function getQuoteId(): ?string
    {
        return $this->quote_id;
    }

    public function getClientId(): ?string
    {
        return $this->client_id;
    }

    public function getSoId(): ?string
    {
        return $this->so_id;
    }

    public function getGroupId(): ?string
    {
        return $this->group_id;
    }

    public function getCreditinvoiceParentId(): ?int
    {
        return $this->creditinvoice_parent_id;
    }

    public function getDeliveryId(): ?int
    {
        return $this->delivery_id;
    }

    public function getDeliveryLocationId(): ?int
    {
        return $this->delivery_location_id;
    }

    public function getPostalAddressId(): ?int
    {
        return $this->postal_address_id;
    }

    public function getContractId(): ?int
    {
        return $this->contract_id;
    }

    public function getStatusId(): ?int
    {
        return $this->status_id;
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

    public function getPaymentMethod(): ?int
    {
        return $this->payment_method;
    }

    public function getTerms(): ?string
    {
        return $this->terms;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function getDocumentDescription(): ?string
    {
        return $this->document_description;
    }

    public function getIsReadOnly(): bool
    {
        return $this->is_read_only;
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
