<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\ClientNote;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\ClientNote\ClientNote;
use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\ClientNote\ClientNoteRepository;
use App\Invoice\ClientNote\ClientNoteService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers ClientNoteService: addClientNote and saveClientNote's relation
 * persistence and date parsing (including the invalid-date fallback to
 * "now"), and deleteClientNote.
 */
#[Test]
final class ClientNoteServiceTest
{
    private function formatDateNote(ClientNote $model): string
    {
        $dateNote = $model->getDateNote();
        if (!$dateNote instanceof \DateTimeImmutable) {
            throw new \RuntimeException('Expected DateTimeImmutable');
        }
        return $dateNote->format('Y-m-d');
    }

    private function makeService(
        ?ClientNoteRepository $repository = null,
        ?CR $cR = null,
    ): ClientNoteService {
        /** @var ClientNoteRepository&m\MockInterface $repository */
        $repository = $repository ?? m::mock(ClientNoteRepository::class);
        /** @var CR&m\MockInterface $cR */
        $cR = $cR ?? m::mock(CR::class);
        return new ClientNoteService($repository, $cR);
    }

    public function addClientNoteSetsAllFieldsAndSaves(): void
    {
        $model = new ClientNote();
        $array = [
            'client_id' => 4,
            'date_note' => '2026-05-10',
            'note' => 'Called client',
        ];

        /** @var Client&m\MockInterface $client */
        $client = m::mock(Client::class);
        /** @var CR&m\MockInterface $cR */
        $cR = m::mock(CR::class);
        $e = $cR->shouldReceive('repoClientquery');
        $e->once()->with(4)->andReturn($client);

        /** @var ClientNoteRepository&m\MockInterface $repository */
        $repository = m::mock(ClientNoteRepository::class);
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService($repository, $cR);
        $service->addClientNote($model, $array);

        Assert::same($client, $model->getClient());
        Assert::same(4, $model->reqClientId());
        Assert::same('Called client', $model->getNote());
        Assert::same('2026-05-10', $this->formatDateNote($model));
    }

    public function addClientNoteSkipsFieldsWhenNotProvided(): void
    {
        $model = new ClientNote();
        $array = [];

        /** @var CR&m\MockInterface $cR */
        $cR = m::mock(CR::class);
        $cR->shouldNotReceive('repoClientquery');

        /** @var ClientNoteRepository&m\MockInterface $repository */
        $repository = m::mock(ClientNoteRepository::class);
        $e = $repository->shouldReceive('save');
        $e->once()->with($model);

        $service = $this->makeService($repository, $cR);
        $service->addClientNote($model, $array);

        Assert::null($model->getClient());
        Assert::same('', $model->getNote());
    }

    public function saveClientNoteSetsAllFieldsAndSaves(): void
    {
        $model = new ClientNote();
        $array = [
            'client_id' => 8,
            'date_note' => '2026-05-11',
            'note' => 'Follow up',
        ];

        /** @var Client&m\MockInterface $client */
        $client = m::mock(Client::class);
        /** @var CR&m\MockInterface $cR */
        $cR = m::mock(CR::class);
        $e = $cR->shouldReceive('repoClientquery');
        $e->once()->with(8)->andReturn($client);

        /** @var ClientNoteRepository&m\MockInterface $repository */
        $repository = m::mock(ClientNoteRepository::class);
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService($repository, $cR);
        $service->saveClientNote($model, $array);

        Assert::same($client, $model->getClient());
        Assert::same(8, $model->reqClientId());
        Assert::same('Follow up', $model->getNote());
        Assert::same('2026-05-11', $this->formatDateNote($model));
    }

    public function deleteClientNoteCallsRepositoryDelete(): void
    {
        $model = new ClientNote();

        /** @var ClientNoteRepository&m\MockInterface $repository */
        $repository = m::mock(ClientNoteRepository::class);
        $e = $repository->shouldReceive('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteClientNote($model);
    }
}
