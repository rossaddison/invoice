<?php

declare(strict_types=1);

namespace App\Invoice\InvItemAmount;

use App\Invoice\Entity\InvItemAmount;

final readonly class InvItemAmountService
{
    public function __construct(private InvItemAmountRepository $repository)
    {
    }

    /**
     * @param InvItemAmount $model
     * @param array $invitem
     */
    public function saveInvItemAmountNoForm(InvItemAmount $model, array $invitem): void
    {
        $model->setInv_item_id((int)$invitem['inv_item_id']);
        $model->setSubtotal((float)$invitem['subtotal']);
        $model->setTax_total((float)$invitem['taxtotal']);
        $model->setDiscount((float)$invitem['discount']);
        $model->setCharge((float)$invitem['charge']);
        $model->setAllowance((float)$invitem['allowance']);
        $model->setTotal((float)$invitem['total']);
        $this->repository->save($model);
    }

    /**
     * @param InvItemAmount $model
     */
    public function deleteInvItemAmount(InvItemAmount $model): void
    {
        $this->repository->delete($model);
    }
}
