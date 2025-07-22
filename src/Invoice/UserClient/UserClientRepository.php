<?php

declare(strict_types=1);

namespace App\Invoice\UserClient;

use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\Entity\Client;
use App\Invoice\Entity\UserClient;
use App\Invoice\Entity\UserInv;
use App\Invoice\UserClient\UserClientService as UCS;
use App\Invoice\UserInv\UserInvRepository as UIR;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\FormModel\FormHydrator;

/**
 * @template TEntity of UserClient
 *
 * @extends Select\Repository<TEntity>
 */
final class UserClientRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get userclients  without filter.
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
        return Sort::only(['id'])->withOrder(['id' => 'asc']);
    }

    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     *
     * @throws \Throwable
     */
    public function save(array|UserClient|null $userclient): void
    {
        $this->entityWriter->write([$userclient]);
    }

    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     *
     * @throws \Throwable
     */
    public function delete(UserClient $userclient): void
    {
        $this->entityWriter->delete([$userclient]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc']),
        );
    }

    /**
     * @psalm-return TEntity|null
     */
    public function repoUserClientquery(string $id): ?UserClient
    {
        $query = $this->select()
            ->load('user')
            ->load('client')
            ->where(['id' => $id]);

        return $query->fetchOne() ?: null;
    }

    public function repoUserquery(string $client_id): ?UserClient
    {
        $query = $this->select()
            ->where(['client_id' => $client_id]);

        return $query->fetchOne() ?: null;
    }

    /**
     * Get clients  with filter user_id.
     *
     * @psalm-return EntityReader
     */
    public function repoClientquery(string $user_id): EntityReader
    {
        $query = $this->select()
            ->load('client')
            ->where(['user_id' => $user_id]);

        return $this->prepareDataReader($query);
    }

    public function repoClientCountquery(string $user_id): int
    {
        $query = $this->select()
            ->where(['user_id' => $user_id]);

        return $query->count();
    }

    public function repoUserClientqueryCount(string $user_id, string $client_id): int
    {
        $query = $this->select()
            ->where(['user_id' => $user_id])
            ->andWhere(['client_id' => $client_id]);

        return $query->count();
    }

    public function repoUserqueryCount(string $client_id): int
    {
        $query = $this->select()
            ->where(['client_id' => $client_id]);

        return $query->count();
    }

    /**
     * Get a list of clients that have user accounts associated with their client_id.
     */
    public function getClients_with_user_accounts(): array
    {
        $client_ids = [];
        /**
         * @var UserClient $user_client
         */
        foreach ($this->findAllPreloaded() as $user_client) {
            $client_id = $user_client->getClient_id();
            if (!in_array($client_id, $client_ids)) {
                $client_ids[] = $client_id;
            }
        }

        return $client_ids;
    }

    public function get_assigned_to_user(string $user_id): array
    {
        // Get all clients assigned to this user
        $count_user_clients  = $this->repoClientCountquery($user_id);
        $assigned_client_ids = [];
        if ($count_user_clients > 0) {
            $user_clients = $this->repoClientquery($user_id);
            /** @var UserClient $user_client */
            foreach ($user_clients as $user_client) {
                // Include Non-active clients as well since these might be reactivated later
                $assigned_client_ids[] = $user_client->getClient_id();
            }
        }

        return $assigned_client_ids;
    }

    /**
     * @return (int|null)[]
     *
     * @psalm-return array<int<0, max>, int|null>
     */
    public function get_not_assigned_to_user(string $user_id, CR $cR): array
    {
        // Get an array of client ids that have been assigned to this user
        $assigned_client_ids = $this->get_assigned_to_user($user_id);

        // Get all existing clients including non-active ones
        $all_clients      = $cR->findAllPreloaded();
        $every_client_ids = [];
        /** @var Client $client */
        foreach ($all_clients as $client) {
            $client_id = $client->getClient_id();
            // Exclude clients, that already have user accounts, from the dropdown box
            // if the client id does not appear in the user client table as a client
            // => this client has not been already assigned therefore it can be made available
            if (!($this->repoUserquerycount((string) $client_id) > 0)) {
                $every_client_ids[] = $client_id;
            }
        }

        // Create unassigned client list for dropdown
        return array_diff($every_client_ids, $assigned_client_ids);
    }

    /**
     * @return (int|null)[]
     *
     * @psalm-return array<int<0, max>, int|null>
     */
    public function get_not_assigned_to_any_user(CR $cR): array
    {
        // Get all existing clients including non-active ones
        $all_clients           = $cR->findAllPreloaded();
        $unassigned_client_ids = [];
        /** @var Client $client */
        foreach ($all_clients as $client) {
            $client_id = $client->getClient_id();
            // Exclude clients, that already have user accounts, from the dropdown box
            // if the client id does not appear in the user client table as a client
            // => this client has not been already assigned therefore it can be made available
            if (!($this->repoUserquerycount((string) $client_id) > 0)) {
                $unassigned_client_ids[] = $client_id;
            }
        }

        return $unassigned_client_ids;
    }

    public function reset_users_all_clients(UIR $uiR, CR $cR, UCS $ucS, FormHydrator $formHydrator): void
    {
        // Users that have their all_clients setting active
        if ($uiR->countAllWithAllClients() > 0) {
            $users = $uiR->findAllWithAllClients();
            /** @var UserInv $user */
            foreach ($users as $user) {
                $user_id              = $user->getUser_id();
                $available_client_ids = $this->get_not_assigned_to_user($user_id, $cR);
                $this->assign_to_user_client($available_client_ids, $user_id, $formHydrator, $ucS);
            }
        }
    }

    public function assign_to_user_client(array $available_client_ids, string $user_id, FormHydrator $formHydrator, UCS $ucS): void
    {
        /** @var int $value */
        foreach ($available_client_ids as $_key => $value) {
            $user_client = [
                'user_id'   => $user_id,
                'client_id' => $value,
            ];
            $model = new UserClient();
            $form  = new UserClientForm($model);
            ($formHydrator->populateAndValidate($form, $user_client)) ? $ucS->saveUserClient($model, $user_client) : '';
        }
    }

    public function unassign_to_user_client(string $user_id): void
    {
        $user_clients = $this->repoClientquery($user_id);
        /** @var UserClient $user_client */
        foreach ($user_clients as $user_client) {
            $this->delete($user_client);
        }
    }
}
