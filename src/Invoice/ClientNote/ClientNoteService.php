<?php

declare(strict_types=1);

namespace App\Invoice\ClientNote;

use App\Invoice\Entity\ClientNote;
use DateTime;

final readonly class ClientNoteService
{
    public function __construct(private ClientNoteRepository $repository)
    {
    }

    public function addClientNote(ClientNote $model, array $array): void
    {
        isset($array['client_id']) ? $model->setClient_id((int)$array['client_id']) : '';

        $datetime = new DateTime();
        /**
         * @var string $array['date_note']
         */
        $date = $array['date_note'] ?? '';
        /**
         * @see https://www.php.net/manual/en/datetime.createfromformat
         * @var bool|DateTime $result
         */
        $result = $datetime::createFromFormat('Y-m-d', $date);
        $model->setDate_note(!is_bool($result) ? $result : $datetime);

        isset($array['note']) ? $model->setNote((string)$array['note']) : '';
        $this->repository->save($model);
    }

    /**
     * @param ClientNote $model
     * @param array $array
     */
    public function saveClientNote(ClientNote $model, array $array): void
    {
        isset($array['client_id'])
        && $model->getClient()?->getClient_id() == $array['client_id']
        ? $model->setClient($model->getClient()) : $model->setClient(null);

        isset($array['client_id']) ? $model->setClient_id((int)$array['client_id']) : '';

        $datetime = new DateTime();
        /**
         * @var string $array['date_note']
         */
        $date = $array['date_note'] ?? '';
        /**
         * @see https://www.php.net/manual/en/datetime.createfromformat
         * @var bool|DateTime $result
         */
        $result = $datetime::createFromFormat('Y-m-d', $date);
        $model->setDate_note(!is_bool($result) ? $result : $datetime);

        isset($array['note']) ? $model->setNote((string)$array['note']) : '';
        $this->repository->save($model);
    }

    /**
     * @param array|ClientNote|null $model
     */
    public function deleteClientNote(array|ClientNote|null $model): void
    {
        $this->repository->delete($model);
    }
}
