<?php

declare(strict_types=1);

namespace App\Invoice\Client;

use Cycle\ORM\Select;
use App\Infrastructure\Persistence\Client\Client;
use App\Invoice\Client\Trait\ClientRepositoryFilterTrait;
use App\Invoice\UserClient\UserClientRepository;
use Cycle\Database\Injection\Parameter;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of Client
 * @extends Select\Repository<TEntity>
 */
final class ClientRepository extends Select\Repository implements ClientRepositoryInterface
{
    use ClientRepositoryFilterTrait;

    /**
     * @param Select<TEntity> $select
     * @param EntityWriter $entityWriter
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->select()
                      ->count();
    }

    /**
     * Get Client with filter active
     *
     * @psalm-return EntityReader
     */
    public function findAllWithActive(int $active): EntityReader
    {
        if ($active < 2) {
            $query = $this->select()
                   ->where(['client_active' => $active]);
            return $this->prepareDataReader($query);
        }
        return $this->findAllPreloaded();
    }

    /**
     * Get clients  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select();
        return $this->prepareDataReader($query);
    }

    /**
     * @psalm-return EntityReader
     */
    public function getReader(): EntityReader
    {
        return (new EntityReader($this->select()))
            ->withSort($this->getSort());
    }

    private function getSort(): Sort
    {
        return Sort::only(['id'])->withOrder(['id' => 'desc']);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|Client|null $client
     * @psalm-param TEntity $client
     * @throws Throwable
     */
    public function save(array|Client|null $client): void
    {
        $this->entityWriter->write([$client]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|Client|null $client
     * @throws Throwable
     */
    public function delete(array|Client|null $client): void
    {
        $this->entityWriter->delete([$client]);
    }

    /**
     * @param Select $query
     * @return EntityReader
     */
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc']),
        );
    }
    
    public function repoClientCount(int $id): int
    {
        return $this->select()
                      ->where(['id' => $id])
                      ->count();
    }

    /**
     * @return Client|null
     *
     * @psalm-return TEntity|null
     */
    #[\Override]
    public function repoClientqueryOrig(int $id): ?Client
    {
        $query = $this->select()
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @return Client
     *
     * @psalm-return TEntity
     */
    public function repoClientquery(int $id): Client
    {
        $query = $this->select()
                      ->where(['id' => $id]);
        return  $query->fetchOne();
    }

    /**
     * @psalm-return EntityReader
     */
    public function repoUserClient(array $available_client_id_list): EntityReader
    {
        $query = $this
        ->select()
        ->where(['id' => ['in' => new Parameter($available_client_id_list)]]);
        return $this->prepareDataReader($query);
    }

    /**
     * @psalm-return EntityReader
     */
    public function repoActivequery(bool $client_active): EntityReader
    {
        $query = $this->select()->where(['client_active' => $client_active]);
        return $this->prepareDataReader($query);
    }

    /**
     * @return Client|null
     *
     * @psalm-return TEntity|null
     */
    public function withName(string $client_name): ?Client
    {
        $query = $this
            ->select()
            ->where(['client_name' => $client_name]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * Lookup used by the external `/api/orders` endpoint
     * (`App\Api\OrderService`) to find an existing Client for a repeat
     * webshop customer by the email they checked out with, rather than
     * creating a duplicate Client per order.
     */
    public function findByEmail(string $email): ?Client
    {
        $query = $this
            ->select()
            ->where(['client_email' => $email]);
        return $query->fetchOne() ?: null;
    }

    /**
     * Lookup for the public home-care scan endpoint: resolves a client
     * from their printed QR token. Only active clients are eligible.
     *
     * @return Client|null
     *
     * @psalm-return TEntity|null
     */
    public function repoClientByQrTokenquery(string $token): ?Client
    {
        $query = $this
            ->select()
            ->where(['client_qr_token' => $token])
            ->where(['client_active' => true]);
        return $query->fetchOne() ?: null;
    }

    public function optionsData(UserClientRepository $ucR): array
    {
        $optionsData = [];
        if (!$ucR->getClientsWithUserAccounts() == []) {
            /**
             * @var Client $client
             */
            foreach ($this->repoUserClient($ucR->getClientsWithUserAccounts())
                    as $client) {
                $optionsData[$client->reqId()] = ($client->getClientName()
                    ?: '??')
                        . str_repeat(' ', 3)
                        . ($client->getClientSurname() ?? '??');
            }
        }
        return $optionsData;
    }
}
