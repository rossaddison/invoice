<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\ORM\Entity\Behavior;
use DateTimeImmutable;
use App\User\User;
use Yiisoft\Translator\TranslatorInterface as Translator;

#[Entity(repository:\App\Invoice\UserInv\UserInvRepository::class)]
#[Behavior\CreatedAt(field: 'date_created', column: 'date_created')]
#[Behavior\UpdatedAt(field: 'date_modified', column: 'date_modified')]
class UserInv
{
    #[BelongsTo(target:User::class, nullable: false)]
    private ?User $user = null;

    #[Column(type: 'datetime', nullable:false)]
    private readonly DateTimeImmutable $date_created;

    #[Column(type: 'datetime', nullable:false)]
    private readonly DateTimeImmutable $date_modified;

    public function __construct(
        #[Column(type:'primary')]
        private ?int $id = null,
        #[Column(type: 'integer(11)', nullable:false)]
        private ?int $user_id = null,
        /**
         * @see src/Invoice/UserInv/UserInvForm 0 => Admin, 1 => Not Admin
         */
        #[Column(type:'integer(11)', nullable:false, default:0)]
        private ?int $type = null,
        #[Column(type:'bool', typecast:'bool', default:false)]
        private ?bool $active = false,
        #[Column(type:'string(191)', nullable:true, default:'system')]
        private ?string $language = '',
        #[Column(type:'string(151)', nullable:true)]
        private ?string $name = '',
        #[Column(type:'string(50)', nullable:true)]
        private ?string $company = '',
        #[Column(type:'string(50)', nullable:true)]
        private ?string $address_1 = '',
        #[Column(type:'string(50)', nullable:true)]
        private ?string $address_2 = '',
        #[Column(type:'string(50)', nullable:true)]
        private ?string $city = '',
        #[Column(type:'string(50)', nullable:true)]
        private ?string $state = '',
        #[Column(type:'string(10)', nullable:true)]
        private ?string $zip = '',
        #[Column(type:'string(50)', nullable:true)]
        private ?string $country = '',
        #[Column(type:'string(20)', nullable:true)]
        private ?string $phone = '',
        #[Column(type:'string(20)', nullable:true)]
        private ?string $fax = '',
        #[Column(type:'string(20)', nullable:true)]
        private ?string $mobile = '',
        #[Column(type:'string(100)', nullable:true)]
        private ?string $web = '',
        #[Column(type:'string(20)', nullable:true)]
        private ?string $vat_id = '',
        #[Column(type:'string(15)', nullable:true)]
        private ?string $tax_code = '',
        #[Column(type:'bool', typecast:'bool', default:false)]
        private ?bool $all_clients = false,
        #[Column(type:'string(40)', nullable:true)]
        private ?string $subscribernumber = '',
        #[Column(type:'string(34))', nullable:true)]
        private ?string $iban = '',
        #[Column(type:'bigInteger(20)', nullable:true)]
        private ?int $gln = null,
        #[Column(type:'string(7)', nullable:true)]
        private ?string $rcc = '',
        #[Column(type: 'integer(3)', nullable:true, default: 10)]
        private ?int $listLimit = 10
    ) {
        $this->date_created = new \DateTimeImmutable();
        $this->date_modified = new \DateTimeImmutable();
    }

    public function getId(): int|null
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * Use the getUser relation to retrieve the User Table email field
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getUser_id(): string
    {
        return (string)$this->user_id;
    }

    public function setUser_id(int $user_id): void
    {
        $this->user_id = $user_id;
    }

    public function getWeb(): ?string
    {
        return $this->web;
    }

    public function getType(): int|null
    {
        return $this->type;
    }

    public function setType(int $type): void
    {
        $this->type = $type;
    }

    public function getActive(): bool|null
    {
        return $this->active;
    }

    public function getActiveLabel(Translator $translator): string
    {
        return $this->active ? '<span class="label active">' . $translator->translate('i.yes') . '</span>' : '<span class="label inactive">' . $translator->translate('i.no') . '</span>';
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getDate_created(): DateTimeImmutable
    {
        return $this->date_created;
    }

    public function getDate_modified(): DateTimeImmutable
    {
        return $this->date_modified;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(string $company): void
    {
        $this->company = $company;
    }

    public function getAddress_1(): ?string
    {
        return $this->address_1;
    }

    public function setAddress_1(string $address_1): void
    {
        $this->address_1 = $address_1;
    }

    public function getAddress_2(): ?string
    {
        return $this->address_2;
    }

    public function setAddress_2(string $address_2): void
    {
        $this->address_2 = $address_2;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function getZip(): ?string
    {
        return $this->zip;
    }

    public function setZip(string $zip): void
    {
        $this->zip = $zip;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): void
    {
        $this->country = $country;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    public function getFax(): ?string
    {
        return $this->fax;
    }

    public function setFax(string $fax): void
    {
        $this->fax = $fax;
    }

    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    public function setMobile(string $mobile): void
    {
        $this->mobile = $mobile;
    }

    public function setWeb(string $web): void
    {
        $this->web = $web;
    }

    public function getVat_id(): string
    {
        return (string)$this->vat_id;
    }

    public function setVat_id(string $vat_id): void
    {
        $this->vat_id = $vat_id;
    }

    public function getTax_code(): ?string
    {
        return $this->tax_code;
    }

    public function setTax_code(string $tax_code): void
    {
        $this->tax_code = $tax_code;
    }

    public function getAll_clients(): bool|null
    {
        return $this->all_clients;
    }

    public function setAll_clients(bool $all_clients): void
    {
        $this->all_clients = $all_clients;
    }

    public function getSubscribernumber(): ?string
    {
        return $this->subscribernumber;
    }

    public function setSubscribernumber(string $subscribernumber): void
    {
        $this->subscribernumber = $subscribernumber;
    }

    public function getIban(): ?string
    {
        return $this->iban;
    }

    public function setIban(string $iban): void
    {
        $this->iban = $iban;
    }

    public function getGln(): ?int
    {
        return $this->gln;
    }

    public function setGln(int $gln): void
    {
        $this->gln = $gln;
    }

    public function getRcc(): ?string
    {
        return $this->rcc;
    }

    public function setRcc(string $rcc): void
    {
        $this->rcc = $rcc;
    }

    public function getListLimit(): ?int
    {
        return $this->listLimit;
    }

    public function setListLimit(int $listLimit): void
    {
        $this->listLimit = $listLimit;
    }

    public function isNewRecord(): bool
    {
        return $this->getId() === null;
    }
}
