<?php

declare(strict_types=1);

namespace App\Invoice\Company;

use App\Invoice\Entity\Company;

final class CompanyService
{
    private CompanyRepository $repository;

    public function __construct(CompanyRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param Company $model
     * @param array $array
     * @return void
     */
    public function saveCompany(Company $model, array $array): void
    {
        $model->setCurrent($array['current'] === '1' ? 1 : 0);
        isset($array['name']) ? $model->setName((string)$array['name']) : '';
        isset($array['address_1']) ? $model->setAddress_1((string)$array['address_1']) : '';
        isset($array['address_2']) ? $model->setAddress_2((string)$array['address_2']) : '';
        isset($array['city']) ? $model->setCity((string)$array['city']) : '';
        isset($array['state']) ? $model->setState((string)$array['state']) : '';
        isset($array['zip']) ? $model->setZip((string)$array['zip']) : '';
        isset($array['country']) ? $model->setCountry((string)$array['country']) : '';
        isset($array['phone']) ? $model->setPhone((string)$array['phone']) : '';
        isset($array['fax']) ? $model->setFax((string)$array['fax']) : '';
        isset($array['email']) ? $model->setEmail((string)$array['email']) : '';
        isset($array['web']) ? $model->setWeb((string)$array['web']) : '';
        $this->repository->save($model);
    }

    /**
     *
     * @param array|Company|null $model
     * @return void
     */
    public function deleteCompany(array|Company|null $model): void
    {
        $this->repository->delete($model);
    }
}
