<?php

declare(strict_types=1);

namespace App\Invoice\ClientNote;

use App\Invoice\Entity\ClientNote;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;
use DateTimeImmutable;

final class ClientNoteForm extends FormModel
{
    #[Required]
    private ?int $client_id = null;

    // Bugfix: Could not enter #[Required] here instead of ->required(true)
    private mixed $date_note = '';

    #[Required]
    private ?string $note = '';

    public function __construct(ClientNote $clientNote)
    {
        $this->client_id = (int) $clientNote->getClient_id();
        $this->date_note = $clientNote->getDate_note();
        $this->note = $clientNote->getNote();
    }

    public function getClient_id(): int|null
    {
        return $this->client_id;
    }

    public function getDate_note(): string|DateTimeImmutable
    {
        /**
         * @var DateTimeImmutable|string $this->date_note
         */
        return $this->date_note;
    }

    public function getNote(): string|null
    {
        return $this->note;
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
