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
        $this->client_id = (int) $clientNote->getClientId();
        $this->date_note = $clientNote->getDateNote();
        $this->note = $clientNote->getNote();
    }

    public function getClientId(): ?int
    {
        return $this->client_id;
    }

    public function getDateNote(): string|DateTimeImmutable
    {
        /**
         * @var DateTimeImmutable|string $this->date_note
         */
        return $this->date_note;
    }

    public function getNote(): ?string
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
