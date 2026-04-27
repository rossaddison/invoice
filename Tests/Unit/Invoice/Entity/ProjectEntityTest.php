<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\Project\Project;
use PHPUnit\Framework\TestCase;

class ProjectEntityTest extends TestCase
{
    public function testIsPersistedReturnsFalseByDefault(): void
    {
        $project = new Project();
        $this->assertFalse($project->isPersisted());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $project = new Project();
        $this->expectException(\LogicException::class);
        $project->reqId();
    }

    public function testSetIdUpdatesPersistedState(): void
    {
        $project = new Project();
        $project->setId(11);
        $this->assertTrue($project->isPersisted());
        $this->assertSame(11, $project->reqId());
    }

    public function testReqIdReturnType(): void
    {
        $project = new Project();
        $project->setId(1);
        $this->assertIsInt($project->reqId());
    }

    public function testClientIdIsNullByDefault(): void
    {
        $project = new Project();
        $this->assertNull($project->getClientId());
    }

    public function testClientIdSetterAndGetter(): void
    {
        $project = new Project();
        $project->setClientId(5);
        $this->assertSame(5, $project->getClientId());
    }

    public function testNameIsEmptyStringByDefault(): void
    {
        $project = new Project();
        $this->assertSame('', $project->getName());
    }

    public function testNameSetterAndGetter(): void
    {
        $project = new Project();
        $project->setName('Q4 Deliverables');
        $this->assertSame('Q4 Deliverables', $project->getName());
    }

    public function testConstructorWithArguments(): void
    {
        $project = new Project(client_id: 3, name: 'Alpha');
        $this->assertSame(3, $project->getClientId());
        $this->assertSame('Alpha', $project->getName());
    }

    public function testClientRelationIsNullByDefault(): void
    {
        $project = new Project();
        $this->assertNull($project->getClient());
    }

    public function testClientRelationSetterAndGetter(): void
    {
        $project = new Project();
        $client = $this->createMock(Client::class);
        $project->setClient($client);
        $this->assertSame($client, $project->getClient());
        $project->setClient(null);
        $this->assertNull($project->getClient());
    }
}
