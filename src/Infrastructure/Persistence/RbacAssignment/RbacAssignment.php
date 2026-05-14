<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\RbacAssignment;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Table\PrimaryKey;

/**
 * @psalm-suppress UnusedClass
 * Cycle ORM entity — table auto-created by SyncTables generator.
 */
#[Entity(table: 'yii_rbac_assignment')]
#[Table(primary: new PrimaryKey(columns: ['item_name', 'user_id']))]
class RbacAssignment
{
    public function __construct(
        #[Column(type: 'string(126)', nullable: false)]
        private string $item_name,
        #[Column(type: 'string(126)', nullable: false)]
        private string $user_id,
        #[Column(type: 'integer', nullable: false)]
        private int $created_at,
    ) {
    }

    public function getItemName(): string
    {
        return $this->item_name;
    }

    public function getUserId(): string
    {
        return $this->user_id;
    }

    public function getCreatedAt(): int
    {
        return $this->created_at;
    }

    public function setCreatedAt(int $created_at): void
    {
        $this->created_at = $created_at;
    }
}
