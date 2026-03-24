<?php

declare(strict_types=1);

namespace App\Invoice\CompanyPrivate;

use App\Invoice\Entity\CompanyPrivate;
use App\Invoice\Company\CompanyRepository as CR;

final readonly class CompanyPrivateService
{
    public function __construct(
        private CompanyPrivateRepository $repository,
        private CR $cR,
    ) {
    }

    /**
     * @param CompanyPrivate $model
     * @param array $array
     */
    public function saveCompanyPrivate(
        CompanyPrivate $model, array $array): void {
        $this->persist($model, $array);
        isset($array['company_id']) ?
            $model->setCompanyId((int) $array['company_id']) : '';
        isset($array['vat_id']) ?
            $model->setVatId((string) $array['vat_id']) : '';
        isset($array['tax_code']) ?
            $model->setTaxCode((string) $array['tax_code']) : '';
        isset($array['iban']) ?
            $model->setIban((string) $array['iban']) : '';
        isset($array['gln']) ?
            $model->setGln((string) $array['gln']) : '';
        isset($array['rcc']) ?
            $model->setRcc((string) $array['rcc']) : '';
        isset($array['logo_filename']) ?
            $model->setLogoFilename(
                (string) $array['logo_filename']) : '';
        isset($array['logo_width']) ?
            $model->setLogoWidth((int) $array['logo_width']) : '';
        isset($array['logo_height']) ?
            $model->setLogoHeight((int) $array['logo_height']) : '';
        isset($array['logo_margin']) ?
            $model->setLogoMargin((int) $array['logo_margin']) : '';
        $datetime_start_date = new \DateTime();
        isset($array['start_date']) ?
            $model->setStartDate(
                $datetime_start_date::createFromFormat(
                    'Y-m-d',
                    (string) $array['start_date'])) : '';
        $datetime_end_date = new \DateTime();
        isset($array['end_date']) ?
            $model->setEndDate(
                $datetime_end_date::createFromFormat(
                    'Y-m-d',
                    (string) $array['end_date'])) : '';
        $this->repository->save($model);
    }

    private function persist(
        CompanyPrivate $model,
        array $array
    ): CompanyPrivate {
        $company = 'company_id';
        if (isset($array[$company])) {
            $model->setCompany(
                $this->cR->repoCompanyquery(
                    (string) $array[$company]));
        }
        return $model;
    }

    /**
     * @param array|CompanyPrivate|null $model
     */
    public function deleteCompanyPrivate(array|CompanyPrivate|null $model): void
    {
        $this->repository->delete($model);
    }
}
