<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\Project\Project;
use App\Infrastructure\Persistence\Task\Task;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use DateTime;
use PHPUnit\Framework\TestCase;

class TaskEntityTest extends TestCase
{
    public function testIsPersistedReturnsFalseByDefault(): void
    {
        $task = new Task();
        $this->assertFalse($task->hasIdentity());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $task = new Task();
        $this->expectException(\LogicException::class);
        $task->reqId();
    }

    public function testSetIdUpdatesPersistedState(): void
    {
        $task = new Task();
        $task->setId(9);
        $this->assertTrue($task->hasIdentity());
        $this->assertSame(9, $task->reqId());
    }

    public function testReqIdReturnType(): void
    {
        $task = new Task();
        $task->setId(1);
        $this->assertIsInt($task->reqId());
    }

    public function testReqProjectIdThrowsWhenNotSet(): void
    {
        $task = new Task();
        $this->expectException(\LogicException::class);
        $task->reqProjectId();
    }

    public function testSetProjectIdAndReqProjectId(): void
    {
        $task = new Task();
        $task->setProjectId(3);
        $this->assertSame(3, $task->reqProjectId());
    }

    public function testReqTaxRateIdThrowsWhenNotSet(): void
    {
        $task = new Task();
        $this->expectException(\LogicException::class);
        $task->reqTaxRateId();
    }

    public function testSetTaxRateIdAndReqTaxRateId(): void
    {
        $task = new Task();
        $task->setTaxRateId(2);
        $this->assertSame(2, $task->reqTaxRateId());
    }

    public function testNameIsEmptyStringByDefault(): void
    {
        $task = new Task();
        $this->assertSame('', $task->getName());
    }

    public function testNameSetterAndGetter(): void
    {
        $task = new Task();
        $task->setName('Write tests');
        $this->assertSame('Write tests', $task->getName());
    }

    public function testDescriptionIsEmptyStringByDefault(): void
    {
        $task = new Task();
        $this->assertSame('', $task->getDescription());
    }

    public function testDescriptionSetterAndGetter(): void
    {
        $task = new Task();
        $task->setDescription('A detailed description');
        $this->assertSame('A detailed description', $task->getDescription());
    }

    public function testPriceIsNullByDefault(): void
    {
        $task = new Task();
        $this->assertNull($task->getPrice());
    }

    public function testPriceSetterAndGetter(): void
    {
        $task = new Task();
        $task->setPrice(99.50);
        $this->assertSame(99.50, $task->getPrice());
    }

    public function testStatusIsNullByDefault(): void
    {
        $task = new Task();
        $this->assertNull($task->getStatus());
    }

    public function testStatusSetterAndGetter(): void
    {
        $task = new Task();
        $task->setStatus(1);
        $this->assertSame(1, $task->getStatus());
    }

    public function testIsOverdueReturnsFalseForPastDate(): void
    {
        $task = new Task();
        $task->setFinishDate(new DateTime('1999-01-01'));
        $this->assertFalse($task->isOverdue());
    }

    public function testIsOverdueReturnsTrueForFutureDate(): void
    {
        $task = new Task();
        $task->setFinishDate(new DateTime('2099-12-31'));
        $this->assertTrue($task->isOverdue());
    }

    public function testTaxRateRelationSetterAndGetter(): void
    {
        $task = new Task();
        $taxRate = $this->createMock(TaxRate::class);
        $task->setTaxRate($taxRate);
        $this->assertSame($taxRate, $task->getTaxRate());
        $task->setTaxRate(null);
        $this->assertNull($task->getTaxRate());
    }

    public function testProjectRelationSetterAndGetter(): void
    {
        $task = new Task();
        $project = $this->createMock(Project::class);
        $task->setProject($project);
        $this->assertSame($project, $task->getProject());
        $task->setProject(null);
        $this->assertNull($task->getProject());
    }
}
