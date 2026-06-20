<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\PeppolMessage;

use App\Infrastructure\Persistence\Trait\RequireId;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\ORM\Entity\Behavior;
use DateTimeImmutable;
use App\Infrastructure\Persistence\PeppolMessage\Trait\PeppolMessageTrait1;
use App\Infrastructure\Persistence\PeppolMessage\Trait\PeppolMessageTrait2;

#[Entity(repository: \App\Invoice\Peppol\PeppolMessageRepository::class)]
#[Behavior\CreatedAt(field: 'created_at', column: 'created_at')]
class PeppolMessage
{
    use RequireId;
    use PeppolMessageTrait1;
    use PeppolMessageTrait2;

    private const string COL_STR255 = 'string(255)';

    #[Column(type: 'datetime', nullable: false)]
    private DateTimeImmutable $created_at;

    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $inv_id = null,
        #[Column(type: 'string(100)', nullable: true)]
        private ?string $message_id = null,
        #[Column(type: self::COL_STR255, nullable: false)]
        private ?string $recipient_id = null,
        #[Column(type: self::COL_STR255, nullable: false)]
        private ?string $document_type_id =
            'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2',
        #[Column(type: self::COL_STR255, nullable: false)]
        private ?string $process_id =
            'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0',
        #[Column(type: 'string(20)', nullable: false)]
        private string $status = 'QUEUED',
        #[Column(type: 'datetime', nullable: true)]
        private ?DateTimeImmutable $sent_at = null,
        #[Column(type: 'datetime', nullable: true)]
        private ?DateTimeImmutable $delivered_at = null,
        #[Column(type: 'string(1000)', nullable: true)]
        private ?string $error_message = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private int $retry_count = 0,
    ) {
        $this->created_at = new DateTimeImmutable();
    }

    #[Column(type: 'text', nullable: true)]
    private ?string $ubl_xml = null;
}
