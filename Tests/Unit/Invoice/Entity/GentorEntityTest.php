<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\Gentor\Gentor;
use PHPUnit\Framework\TestCase;

class GentorEntityTest extends TestCase
{
    public function testHasIdentityReturnsFalseByDefault(): void
    {
        $g = new Gentor();
        $this->assertFalse($g->hasIdentity());
    }

    public function testReqGentorIdThrowsWhenNotPersisted(): void
    {
        $g = new Gentor();
        $this->expectException(\LogicException::class);
        $g->reqGentorId();
    }

    public function testConstructorDefaults(): void
    {
        $g = new Gentor();
        $this->assertSame('', $g->getRoutePrefix());
        $this->assertSame('', $g->getRouteSuffix());
        $this->assertSame('', $g->getCamelcaseCapitalName());
        $this->assertSame('', $g->getSmallSingularName());
        $this->assertSame('', $g->getSmallPluralName());
        $this->assertFalse($g->isCreatedInclude());
        $this->assertFalse($g->isUpdatedInclude());
        $this->assertFalse($g->isModifiedInclude());
        $this->assertFalse($g->isDeletedInclude());
    }

    public function testSetAndGetRoutePrefix(): void
    {
        $g = new Gentor();
        $g->setRoutePrefix('inv');
        $this->assertSame('inv', $g->getRoutePrefix());
    }

    public function testSetAndGetRouteSuffix(): void
    {
        $g = new Gentor();
        $g->setRouteSuffix('index');
        $this->assertSame('index', $g->getRouteSuffix());
    }

    public function testSetAndGetCamelcaseCapitalName(): void
    {
        $g = new Gentor();
        $g->setCamelcaseCapitalName('InvoiceItem');
        $this->assertSame('InvoiceItem', $g->getCamelcaseCapitalName());
    }

    public function testSetAndGetSmallNames(): void
    {
        $g = new Gentor();
        $g->setSmallSingularName('item');
        $g->setSmallPluralName('items');
        $this->assertSame('item', $g->getSmallSingularName());
        $this->assertSame('items', $g->getSmallPluralName());
    }

    public function testSetAndGetBooleanFlags(): void
    {
        $g = new Gentor();
        $g->setCreatedInclude(true);
        $g->setUpdatedInclude(true);
        $g->setModifiedInclude(true);
        $g->setDeletedInclude(true);
        $g->setFlashInclude(true);
        $this->assertTrue($g->isCreatedInclude());
        $this->assertTrue($g->isUpdatedInclude());
        $this->assertTrue($g->isModifiedInclude());
        $this->assertTrue($g->isDeletedInclude());
        $this->assertTrue($g->isFlashInclude());
    }

    public function testSetAndGetNamespacePath(): void
    {
        $g = new Gentor();
        $g->setNamespacePath('App\\Invoice\\InvItem');
        $this->assertSame('App\\Invoice\\InvItem', $g->getNamespacePath());
    }
}
