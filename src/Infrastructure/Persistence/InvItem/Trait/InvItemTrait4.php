<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\InvItem\Trait;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\Task\Task;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use DateTime;
use DateTimeImmutable;

/**
 * @method int requireId(?int $id, string $context)
 */
trait InvItemTrait4
{

    public function setNote(string $note): void
    {
        $this->note = $note;
    }

    public function getPeppolPoItemid(): ?string
    {
        return $this->peppol_po_itemid;
    }

    public function setPeppolPoItemid(string $peppol_po_itemid): void
    {
        $this->peppol_po_itemid = $peppol_po_itemid;
    }

    public function getPeppolPoLineid(): ?string
    {
        return $this->peppol_po_lineid;
    }

    public function setPeppolPoLineid(string $peppol_po_lineid): void
    {
        $this->peppol_po_lineid = $peppol_po_lineid;
    }
}
