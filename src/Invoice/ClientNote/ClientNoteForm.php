<?php 

declare(strict_types=1);

namespace App\Invoice\ClientNote;

use App\Infrastructure\Persistence\ClientNote\ClientNote;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;
use DateTimeImmutable;

final class ClientNoteForm extends FormModel
{
    #[Required]
    private ?int $client_id = null;

    private ?string $date_note = null;

    #[Required]
    private ?string $note = '';

    public static function show(ClientNote $clientNote): self
    {
        $form = new self();
        $form->client_id = (int) $clientNote->getClientId();
        $dateNote = $clientNote->getDateNote();
        $form->date_note = $dateNote instanceof DateTimeImmutable
            ? $dateNote->format('Y-m-d')
            : null;
        $form->note = $clientNote->getNote();
        return $form;
    }

    public function getClientId(): ?int
    {
        return $this->client_id;
    }

    public function getDateNote(): ?string
    {
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
