<?php

declare(strict_types=1);

namespace App\Invoice\Merchant;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\Merchant\Merchant;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;
use DateTimeImmutable;

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
    private ?Inv $inv = null;

    public static function show(Merchant $merchant): self
    {
        $form = new self();
        $form->inv_id = $merchant->reqInvId();
        $form->successful = $merchant->getSuccessful();
        $form->date = $merchant->getDate();
        $form->driver = $merchant->getDriver();
        $form->response = $merchant->getResponse();
        $form->reference = $merchant->getReference();
        $form->inv = $merchant->getInv();
        return $form;
    }

    public function getInv(): ?Inv
    {
        return $this->inv;
    }

    public function getInvId(): ?int
    {
        return $this->inv_id;
    }

    public function getSuccessful(): ?bool
    {
        return $this->successful;
    }

    public function getDate(): string|DateTimeImmutable
    {
        /**
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
