<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Infrastructure\Persistence\{
    Client\Client, Inv\Inv
};
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;
use DateTimeImmutable;

final class InvForm extends FormModel
{
    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    public ?string $number = '';
    private mixed $date_created = '';
    // Countries with VAT systems will need these fields
    public string|DateTimeImmutable|null $date_modified = '';
    public string|DateTimeImmutable|null $date_supplied = '';
    public string|DateTimeImmutable|null $date_paid_off = '';
    public string|DateTimeImmutable|null $date_tax_point = '';
    public string|DateTimeImmutable|null $date_due = '';
    // stand_in_code/description_code
    #[Length(min: 0, max: 3, skipOnEmpty: true)]
    public ?string $stand_in_code = '';
    public ?int $quote_id = null;
    public ?Client $client = null;

    #[Required]
    private ?int $group_id = null;

    #[Required]
    private ?int $client_id = null;

    public ?int $so_id = null;
    public ?int $creditinvoice_parent_id = null;
    public ?int $delivery_id = null;
    public ?int $delivery_location_id = null;
    public ?int $postal_address_id = null;
    public ?int $contract_id = null;
    private ?int $status_id = 1;
    private ?float $discount_amount = 0.00;
    #[Length(min: 0, max: 32, skipOnEmpty: true)]
    public ?string $url_key = '';
    #[Length(min: 0, max: 90, skipOnEmpty: true)]
    private ?string $password = '';
    public ?int $payment_method = 0;
    public ?string $terms = '';
    private ?string $note = '';
    #[Length(min: 0, max: 32, skipOnEmpty: true)]
    public ?string $document_description = '';
    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    public ?string $client_po_number = '';
    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    public ?string $client_po_person = '';
    public bool $is_read_only = false;
    public string|DateTimeImmutable|null $time_created = '';

    public static function show(Inv $inv): self
    {
        $form = new self();
        $form->date_created = $inv->getDateCreated();
        $form->date_modified = $inv->getDateModified();
        $form->client_id = $inv->reqClientId();
        $form->group_id = $inv->reqGroupId();
        $form->status_id = $inv->reqStatusId();
        $form->contract_id = $inv->getContractId();
        $form->delivery_id = (int) $inv->getDeliveryId();
        $form->delivery_location_id = (int) $inv->getDeliveryLocationId();
        $form->postal_address_id = (int) $inv->getPostalAddressId();
        $form->so_id = $inv->getSoId();
        $form->quote_id = $inv->getQuoteId();
        $form->is_read_only = $inv->getIsReadOnly();
        $form->password = $inv->getPassword();
        $form->time_created = $inv->getTimeCreated();
        $form->date_tax_point = $inv->getDateTaxPoint();
        $form->stand_in_code = $inv->getStandInCode();
        $form->date_supplied = $inv->getDateSupplied();
        $form->date_due = $inv->getDateDue();
        $form->number = $inv->getNumber();
        $form->discount_amount = $inv->getDiscountAmount();
        $form->terms = $inv->getTerms();
        $form->note = $inv->getNote();
        $form->document_description = $inv->getDocumentDescription();
        $form->client_po_number = $inv->getClientPoNumber();
        $form->client_po_person = $inv->getClientPoPerson();
        $form->url_key = $inv->getUrlKey();
        $form->payment_method = $inv->getPaymentMethod();
        $form->creditinvoice_parent_id = (int) $inv->getCreditinvoiceParentId();
        /**
         * Related logic: see
                #[BelongsTo(target: Client::class, nullable: false, fkAction: 'NO ACTION')]
                private ?Client $client = null;
         */
        $form->client = $inv->getClient();
        return $form;
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

    public function getDiscountAmount(): ?float
    {
        return $this->discount_amount;
    }

    public function getDateCreated(): string|DateTimeImmutable|null
    {
        /**
         * @var DateTimeImmutable|string|null $this->date_created
         */
        return $this->date_created;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getNote(): ?string
    {
        return $this->note;
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
