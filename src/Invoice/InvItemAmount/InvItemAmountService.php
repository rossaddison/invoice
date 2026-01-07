<?php

declare(strict_types=1);

namespace App\Invoice\InvItemAmount;

use App\Invoice\Entity\InvItemAmount;
use App\Invoice\InvItem\InvItemRepository as IIR;

final readonly class InvItemAmountService
{
    public function __construct(
        private InvItemAmountRepository $repository,
        private IIR $iiR,
    ) {
    }

    /**
     * @param InvItemAmount $model
     * @param array $invitem
     */
    public function saveInvItemAmountNoForm(
        InvItemAmount $model,
        array $invitem
    ): void {
        $this->persist($model, $invitem);
        $model->setInv_item_id((int) $invitem['inv_item_id']);
        $model->setSubtotal((float) $invitem['subtotal']);
        $model->setTax_total((float) $invitem['taxtotal']);
        $model->setDiscount((float) $invitem['discount']);
        $model->setCharge((float) $invitem['charge']);
        $model->setAllowance((float) $invitem['allowance']);
        $model->setTotal((float) $invitem['total']);
        $this->repository->save($model);
    }

    private function persist(
        InvItemAmount $model,
        array $array
    ): InvItemAmount {
        $inv_item = 'inv_item_id';
        if (isset($array[$inv_item])) {
            $invItemEntity = $this->iiR->repoInvItemquery(
                (string) $array[$inv_item]);
            if ($invItemEntity) {
                $model->setInvItem($invItemEntity);
            }
        }
        return $model;
    }

    /**
     * @param InvItemAmount $model
     */
    public function deleteInvItemAmount(InvItemAmount $model): void
    {
        $this->repository->delete($model);
    }
}
