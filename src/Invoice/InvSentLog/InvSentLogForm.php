<?php

declare(strict_types=1);

namespace App\Invoice\InvSentLog;

use App\Invoice\Entity\Inv;
use App\Invoice\Entity\InvSentLog;
use DateTimeImmutable;
use Yiisoft\FormModel\FormModel;

final class InvSentLogForm extends FormModel
{
    private ?int $id         = null;
    private ?int $inv_id     = null;
    private mixed $date_sent = '';
    private ?Inv $inv        = null;

    public function __construct(InvSentLog $invsentlog)
    {
        $this->id        = $invsentlog->getId();
        $this->inv       = $invsentlog->getInv();
        $this->inv_id    = $invsentlog->getInv_id();
        $this->date_sent = $invsentlog->getDate_sent();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInv_id(): ?int
    {
        return $this->inv_id;
    }

    public function getInv(): ?Inv
    {
        return $this->inv;
    }

    public function getDate_sent(): string|\DateTimeImmutable|null
    {
        /*
         * @var DateTimeImmutable|string $this->date_sent
         */
        return $this->date_sent;
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
