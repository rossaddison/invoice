<?php

declare(strict_types=1);

namespace App\Invoice\ClientNote;

use App\Infrastructure\Persistence\ClientNote\ClientNote;
use App\Invoice\Client\ClientRepository as CR;

final readonly class ClientNoteService
{
    public function __construct(
        private ClientNoteRepository $repository,
        private CR $cR,
    ) {
    }

    public function addClientNote(
        ClientNote $model,
        array $array
    ): void {
        $this->persist($model, $array);
        isset($array['client_id']) ?
            $model->setClientId((int) $array['client_id']) : '';
        $datetime = new \DateTimeImmutable();
        isset($array['date_note']) ?
            $model->setDateNote(\DateTimeImmutable::createFromFormat('Y-m-d',
                    (string) $array['date_note']) ?: $datetime) : '';
        isset($array['note']) ?
            $model->setNote((string) $array['note']) : '';
        $this->repository->save($model);
    }

    /**
     * @param ClientNote $model
     * @param array $array
     */
    public function saveClientNote(
        ClientNote $model,
        array $array
    ): void {
        $this->persist($model, $array);
        isset($array['client_id']) ?
            $model->setClientId((int) $array['client_id']) : '';
        
        $datetime = new \DateTimeImmutable();
        isset($array['date_note']) ?
            $model->setDateNote(\DateTimeImmutable::createFromFormat('Y-m-d',
                    (string) $array['date_note']) ?: $datetime) : '';
        
        isset($array['note']) ?
            $model->setNote((string) $array['note']) : '';
        $this->repository->save($model);
    }

    private function persist(
        ClientNote $model,
        array $array
    ): void {
        $client = 'client_id';
        if (isset($array[$client])) {
            $model->setClient(
                $this->cR->repoClientquery((int) $array[$client]));
        }
    }

    /**
     * @param array|ClientNote|null $model
     */
    public function deleteClientNote(array|ClientNote|null $model): void
    {
        $this->repository->delete($model);
    }
}
