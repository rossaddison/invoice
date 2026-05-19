<?php

declare(strict_types=1);

namespace Tests\Unit\Entity;

use App\Infrastructure\Persistence\RbacAssignment\RbacAssignment;
use PHPUnit\Framework\TestCase;

class RbacAssignmentEntityTest extends TestCase
{
    public function testGetItemNameReturnsConstructorValue(): void
    {
        $entity = new RbacAssignment('admin', '1', 1700000000);

        $this->assertSame('admin', $entity->getItemName());
    }

    public function testGetUserIdReturnsConstructorValue(): void
    {
        $entity = new RbacAssignment('observer', '42', 1700000000);

        $this->assertSame('42', $entity->getUserId());
    }

    public function testGetCreatedAtReturnsConstructorValue(): void
    {
        $entity = new RbacAssignment('admin', '1', 1700000000);

        $this->assertSame(1700000000, $entity->getCreatedAt());
        $this->assertIsInt($entity->getCreatedAt());
    }

    public function testSetCreatedAtUpdatesValue(): void
    {
        $entity = new RbacAssignment('admin', '1', 1700000000);
        $entity->setCreatedAt(1800000000);

        $this->assertSame(1800000000, $entity->getCreatedAt());
    }

    public function testItemNameAndUserIdAreIndependent(): void
    {
        $a = new RbacAssignment('admin', '1', 1700000000);
        $b = new RbacAssignment('observer', '2', 1700000000);

        $this->assertNotSame($a->getItemName(), $b->getItemName());
        $this->assertNotSame($a->getUserId(), $b->getUserId());
    }
}
