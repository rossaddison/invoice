<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\Group\Group;
use PHPUnit\Framework\TestCase;

class GroupEntityTest extends TestCase
{
    public function testHasIdentityReturnsFalseByDefault(): void
    {
        $g = new Group();
        $this->assertFalse($g->hasIdentity());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $g = new Group();
        $this->expectException(\LogicException::class);
        $g->reqId();
    }

    public function testHasIdentityReturnsTrueAfterSetId(): void
    {
        $g = new Group();
        $g->setId(1);
        $this->assertTrue($g->hasIdentity());
    }

    public function testReqIdReturnsIntAfterSetId(): void
    {
        $g = new Group();
        $g->setId(4);
        $this->assertSame(4, $g->reqId());
    }

    public function testConstructorDefaults(): void
    {
        $g = new Group();
        $this->assertSame('', $g->getName());
        $this->assertSame('', $g->getIdentifierFormat());
        $this->assertNull($g->getNextId());
        $this->assertNull($g->getLeftPad());
    }

    public function testSetAndGetName(): void
    {
        $g = new Group();
        $g->setName('Default Group');
        $this->assertSame('Default Group', $g->getName());
    }

    public function testSetAndGetIdentifierFormat(): void
    {
        $g = new Group();
        $g->setIdentifierFormat('{YYYY}{MM}{DD}-{ID}');
        $this->assertSame('{YYYY}{MM}{DD}-{ID}', $g->getIdentifierFormat());
    }

    public function testSetAndGetNextId(): void
    {
        $g = new Group();
        $g->setNextId(100);
        $this->assertSame(100, $g->getNextId());
    }

    public function testSetAndGetLeftPad(): void
    {
        $g = new Group();
        $g->setLeftPad(4);
        $this->assertSame(4, $g->getLeftPad());
    }
}
