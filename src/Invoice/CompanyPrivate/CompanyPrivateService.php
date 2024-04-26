<?php

declare(strict_types=1); 

namespace App\Invoice\CompanyPrivate;

use App\Invoice\Entity\CompanyPrivate;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Setting\SettingRepository;


final class CompanyPrivateService
{

    private CompanyPrivateRepository $repository;

    public function __construct(CompanyPrivateRepository $repository)
    {
        $this->repository = $repository;
    }
    
    /**
     * @param CompanyPrivate $model
     * @param array $array
     * @param SettingRepository $s
     * @return void
     */
    public function saveCompanyPrivate(CompanyPrivate $model, array $array, SettingRepository $s): void
    {
       //$model->nullifyRelationOnChange((int)$array['company_id']);
       isset($array['company_id']) ? $model->setCompany_id((int)$array['company_id']) : '';
       
       isset($array['vat_id']) ? $model->setVat_id((string)$array['vat_id']) : '';
       isset($array['tax_code']) ? $model->setTax_code((string)$array['tax_code']) : '';
       isset($array['iban']) ? $model->setIban((string)$array['iban']) : '';
       isset($array['gln']) ? $model->setGln((string)$array['gln']) : '';
       isset($array['rcc']) ? $model->setRcc((string)$array['rcc']) : '';
       isset($array['logo_filename']) ? $model->setLogo_filename((string)$array['logo_filename']) : '';
       isset($array['logo_width']) ? $model->setLogo_width((int)$array['logo_width']) : '';
       isset($array['logo_height']) ? $model->setLogo_height((int)$array['logo_height']) : '';
       isset($array['logo_margin']) ? $model->setLogo_margin((int)$array['logo_margin']) : '';
       
       $datehelper = new DateHelper($s);
       
       $datetime_start_date = new \DateTime();
       isset($array['start_date']) ? $model->setStart_date($datetime_start_date::createFromFormat($datehelper->style(),(string)$array['start_date'])) : '';
       
       $datetime_end_date = new \DateTime();
       isset($array['end_date']) ? $model->setEnd_date($datetime_end_date::createFromFormat($datehelper->style(),(string)$array['end_date'])) : '';
       
       $this->repository->save($model);
    }
    
    /**
     * 
     * @param array|CompanyPrivate|null $model
     * @return void
     */
    public function deleteCompanyPrivate(array|CompanyPrivate|null $model): void
    {
        $this->repository->delete($model);
    }
}