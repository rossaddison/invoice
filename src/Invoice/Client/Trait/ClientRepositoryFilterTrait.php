<?php

declare(strict_types=1);

namespace App\Invoice\Client\Trait;

use Yiisoft\Data\Cycle\Reader\EntityReader;

/**
 * The three name/surname search filters used by `ClientController`'s
 * client list ('filter_client_name'/'filter_client_surname' query params).
 * Split out of `ClientRepository` itself purely to stay under SonarQube's
 * php:S1448 method-count ceiling (max 20) — the same technique
 * `InvController` already uses to split itself across `Inv/Trait/*`
 * (`Add`, `Edit`, `Guest`, ...), just applied to a repository instead of a
 * controller. Relies on `select()` (from the parent `Select\Repository`)
 * and the private `prepareDataReader()` both being visible here exactly as
 * they would be on a normal method, since trait methods run in the
 * composing class's own scope.
 */
trait ClientRepositoryFilterTrait
{
    public function filterClientName(string $client_name): EntityReader
    {
        $select = $this->select();
        $query = $select->where(['client_name' => ltrim(rtrim($client_name))]);
        return $this->prepareDataReader($query);
    }

    public function filterClientSurname(string $client_surname): EntityReader
    {
        $select = $this->select();
        $query = $select->where(['client_surname' => ltrim(rtrim($client_surname))]);
        return $this->prepareDataReader($query);
    }

    public function filterClientNameSurname(string $client_name, string $client_surname): EntityReader
    {
        $select = $this->select();
        $query = $select->where(['client_name' => ltrim(rtrim($client_name))])
                        ->andWhere(['client_surname' => ltrim(rtrim($client_surname))]);
        return $this->prepareDataReader($query);
    }
}
