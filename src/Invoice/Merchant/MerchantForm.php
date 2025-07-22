<?php

declare(strict_types=1);

namespace App\Invoice\Merchant;

use App\Invoice\Entity\Inv;
use App\Invoice\Entity\Merchant;
use DateTimeImmutable;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class MerchantForm extends FormModel
{
    private ?int $inv_id = null;
    #[Required]
    private ?bool $successful = true;

    private mixed $date = '';
    #[Required]
    private ?string $driver = '';
    #[Required]
    private ?string $response = '';
    #[Required]
    private ?string $reference = '';
    private readonly ?Inv $inv;

    public function __construct(Merchant $merchant)
    {
        $this->inv_id     = (int) $merchant->getInv_id();
        $this->successful = $merchant->getSuccessful();
        $this->date       = $merchant->getDate();
        $this->driver     = $merchant->getDriver();
        $this->response   = $merchant->getResponse();
        $this->reference  = $merchant->getReference();
        $this->inv        = $merchant->getInv();
    }

    public function getInv(): ?Inv
    {
        return $this->inv;
    }

    public function getInv_id(): ?int
    {
        return $this->inv_id;
    }

    public function getSuccessful(): ?bool
    {
        return $this->successful;
    }

    public function getDate(): string|\DateTimeImmutable
    {
        /*
         * @var DateTimeImmutable|string $this->date
         */
        return $this->date;
    }

    public function getDriver(): ?string
    {
        return $this->driver;
    }

    public function getResponse(): ?string
    {
        return $this->response;
    }

    public function getReference(): ?string
    {
        return $this->reference;
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
