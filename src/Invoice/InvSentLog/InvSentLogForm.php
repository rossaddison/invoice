<?php

declare(strict_types=1);

namespace App\Invoice\InvSentLog;

use App\Infrastructure\Persistence\{
    InvSentLog\InvSentLog, Inv\Inv
};
use Yiisoft\FormModel\FormModel;
use DateTimeImmutable;

final class InvSentLogForm extends FormModel
{
    private ?int $inv_id = null;
    private mixed $date_sent = '';
    private ?Inv $inv = null;

    public static function show(InvSentLog $invsentlog): self
    {
        $form = new self();
        $form->inv = $invsentlog->getInv();
        $form->inv_id = $invsentlog->reqInvId();
        $form->date_sent = $invsentlog->getDateSent();
        return $form;
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
