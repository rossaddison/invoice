<?php

declare(strict_types=1);

namespace App\Invoice\InvSentLog;

use App\Invoice\Entity\InvSentLog;
use App\Invoice\Entity\Inv;
use Yiisoft\FormModel\FormModel;
use DateTimeImmutable;

final class InvSentLogForm extends FormModel
{
    private ?int $id = null;
    private ?int $inv_id = null;
    private mixed $date_sent = '';
    private ?Inv $inv = null;

    public function __construct(InvSentLog $invsentlog)
    {
        $this->id = $invsentlog->getId();
        $this->inv = $invsentlog->getInv();
        $this->inv_id = $invsentlog->getInvId();
        $this->date_sent = $invsentlog->getDateSent();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInvId(): ?int
    {
        return $this->inv_id;
    }

    public function getInv(): ?Inv
    {
        return $this->inv;
    }

    public function getDateSent(): string|DateTimeImmutable|null
    {
        /**
         * @var DateTimeImmutable|string $this->date_sent
         */
        return $this->date_sent;
    }

    /**
     * @return string
     * @psalm-return ''
     */
    #[\Override]
    public function getFormName(): string
    {
        return '';
    }
}
