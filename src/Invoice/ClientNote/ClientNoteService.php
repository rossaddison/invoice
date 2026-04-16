<?php

declare(strict_types=1);

namespace App\Invoice\ClientNote;

use App\Invoice\Entity\ClientNote;
use App\Invoice\Client\ClientRepository as CR;
use DateTime;

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

        $datetime = new DateTime();
        /**
         * @var string $array['date_note']
         */
        $date = $array['date_note'] ?? '';
        /**
         * Related logic:
         * @see https://www.php.net/manual/en/datetime.createfromformat
         * @var bool|DateTime $result
         */
        $result = $datetime::createFromFormat('Y-m-d', $date);
        $model->setDateNote(
            !is_bool($result) ? $result : $datetime);

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

        $datetime = new DateTime();
        /**
         * @var string $array['date_note']
         */
        $date = $array['date_note'] ?? '';
        /**
         * Related logic:
         * @see https://www.php.net/manual/en/datetime.createfromformat
         * @var bool|DateTime $result
         */
        $result = $datetime::createFromFormat('Y-m-d', $date);
        $model->setDateNote(
            !is_bool($result) ? $result : $datetime);

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
