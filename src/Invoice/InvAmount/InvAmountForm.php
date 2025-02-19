<?php

declare(strict_types=1);

namespace App\Invoice\InvAmount;

use App\Invoice\Entity\InvAmount;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class InvAmountForm extends FormModel
{
    private ?int $inv_id = null;

    private ?int $sign = null;

    #[Required]
    private ?float $item_subtotal = null;

    #[Required]
    private ?float $item_tax_total = null;

    #[Required]
    private ?float $tax_total = null;

    #[Required]
    private ?float $total = null;

    #[Required]
    private ?float $paid = null;

    #[Required]
    private ?float $balance = null;

    public function __construct(InvAmount $invAmount)
    {
        $this->inv_id = (int)$invAmount->getInv_id();
        $this->sign = $invAmount->getSign();
        $this->item_subtotal = $invAmount->getItem_subtotal();
        $this->item_tax_total = $invAmount->getItem_tax_total();
        $this->tax_total = $invAmount->getTax_total();
        $this->total = $invAmount->getTotal();
        $this->paid = $invAmount->getPaid();
        $this->balance = $invAmount->getBalance();
    }

    public function getInv_id(): int|null
    {
        return $this->inv_id;
    }

    public function getSign(): int|null
    {
        return $this->sign;
    }

    public function getItem_subtotal(): float|null
    {
        return $this->item_subtotal;
    }

    public function getItem_tax_total(): float|null
    {
        return $this->item_tax_total;
    }

    public function getTax_total(): float|null
    {
        return $this->tax_total;
    }

    public function getTotal(): float|null
    {
        return $this->total;
    }

    public function getPaid(): float|null
    {
        return $this->paid;
    }

    public function getBalance(): float|null
    {
        return $this->balance;
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

    /**
     * @return Required[][]
     *
     * @psalm-return array{item_subtotal: list{Required}, item_tax_total: list{Required}, tax_total: list{Required}, total: list{Required}, inv_id: list{Required}}
     */
    public function getRules(): array
    {
        return [
            'item_subtotal' => [new Required()],
            'item_tax_total' => [new Required()],
            'tax_total' => [new Required()],
            'total' => [new Required()],
            'inv_id' => [new Required()],
        ];
    }
}
