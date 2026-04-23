<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\ClientNote;

use App\Infrastructure\Persistence\Client\Client;
use App\Invoice\ClientNote\ClientNoteRepository;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use DateTimeImmutable;

#[Entity(repository: ClientNoteRepository::class)]
class ClientNote
{
    #[BelongsTo(target: Client::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Client $client = null;

    #[Column(type: 'primary')]
    private ?int $id = null;

    public function __construct(
        #[Column(type: 'integer(11)',
        nullable: false)]
        private ?int $client_id = null,
        #[Column(type: 'longText', nullable: false)]
        private string $note = '',
        #[Column(type: 'date', nullable: true)]
        private DateTimeImmutable|string|null $date_note = null,
    )
    {
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): void
    {
        $this->client = $client;
    }

    /**
     * @throws \LogicException if the entity has not been persisted yet.
     */
    public function reqId(): int
    {
        if ($this->id === null) {
            throw new \LogicException(
                'ClientNote has no ID (not persisted yet)'
            );
        }

        return $this->id;
    }

    public function isPersisted(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getClientId(): string
    {
        return (string) $this->client_id;
    }

    public function setClientId(int $client_id): void
    {
        $this->client_id = $client_id;
    }

    public function getDateNote(): string|DateTimeImmutable|null
    {
        /**
         * @var DateTimeImmutable|string|null $this->date_note
         */
        return $this->date_note;
    }

    public function setDateNote(?DateTimeImmutable $date_note): void
    {
        $this->date_note = $date_note;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    public function setNote(string $note): void
    {
        $this->note = $note;
    }
}
