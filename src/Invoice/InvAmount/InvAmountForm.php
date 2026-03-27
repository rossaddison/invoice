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
    private ?float $packhandleship_total = null;

    #[Required]
    private ?float $packhandleship_tax = null;

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
        $this->inv_id = (int) $invAmount->getInvId();
        $this->sign = $invAmount->getSign();
        $this->item_subtotal = $invAmount->getItemSubtotal();
        $this->item_tax_total = $invAmount->getItemTaxTotal();
        $this->packhandleship_total = $invAmount->getPackhandleshipTotal();
        $this->packhandleship_tax = $invAmount->getPackhandleshipTax();
        $this->tax_total = $invAmount->getTaxTotal();
        $this->total = $invAmount->getTotal();
        $this->paid = $invAmount->getPaid();
        $this->balance = $invAmount->getBalance();
    }

    public function getInvId(): ?int
    {
        return $this->inv_id;
    }

    public function getSign(): ?int
    {
        return $this->sign;
    }

    public function getItemSubtotal(): ?float
    {
        return $this->item_subtotal;
    }

    public function getItemTaxTotal(): ?float
    {
        return $this->item_tax_total;
    }

    public function getPackhandleshipTotal(): ?float
    {
        return $this->packhandleship_total;
    }

    public function getPackhandleshipTax(): ?float
    {
        return $this->packhandleship_tax;
    }

    public function getTaxTotal(): ?float
    {
        return $this->tax_total;
    }

    public function getTotal(): ?float
    {
        return $this->total;
    }

    public function getPaid(): ?float
    {
        return $this->paid;
    }

    public function getBalance(): ?float
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
     * @psalm-return array{item_subtotal: list{Required}, item_tax_total: list{Required}, packhandleship_total: list{Required}, packhandleship_tax: list{Required}, tax_total: list{Required}, total: list{Required}, inv_id: list{Required}}
     */
    public function getRules(): array
    {
        return [
            'item_subtotal' => [new Required()],
            'item_tax_total' => [new Required()],
            'packhandleship_total' => [new Required()],
            'packhandleship_tax' => [new Required()],
            'tax_total' => [new Required()],
            'total' => [new Required()],
            'inv_id' => [new Required()],
        ];
    }
}
