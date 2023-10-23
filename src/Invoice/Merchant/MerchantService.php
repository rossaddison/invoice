<?php

declare(strict_types=1); 

namespace App\Invoice\Merchant;

use App\Invoice\Entity\Merchant;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Setting\SettingRepository as sR;


final class MerchantService
{

    private MerchantRepository $repository;

    public function __construct(MerchantRepository $repository)
    {
        $this->repository = $repository;
    }
    
    /**
     * 
     * @param Merchant $model
     * @param MerchantForm $form
     * @param sR $sR
     * @return void
     */
    public function saveMerchant(Merchant $model, MerchantForm $form, sR $sR): void
    {
       $form->getInv_id() ? $model->setInv_id($form->getInv_id()) : '';
       $form->getSuccessful() ? $model->setSuccessful($form->getSuccessful()) : '';
       $model->setDate($form->getDate($sR));
       $form->getDriver() ? $model->setDriver($form->getDriver()) : '';
       $form->getResponse() ? $model->setResponse($form->getResponse()) : '';
       $form->getReference() ? $model->setReference($form->getReference()) : '';
       $this->repository->save($model);
    }
    
    /**
     * 
     * @param Merchant $model
     * @param array $array
     * @return void
     */
    public function saveMerchant_via_payment_handler(Merchant $model, array $array): void
    {
       $model->setInv_id((int)$array['inv_id']);
       /** @var bool $array['merchant_response_successful'] */
       $model->setSuccessful($array['merchant_response_successful']);
       /** @var \DateTime $array['merchant_response_date'] */
       $model->setDate($array['merchant_response_date']);
       /** @var string $array['merchant_response_driver'] */
       $model->setDriver($array['merchant_response_driver']);
       // Payment success message
       /** @var string $array['merchant_response'] */
       $model->setResponse($array['merchant_response']);
       /** @var string $array['merchant_response_reference'] */
       $model->setReference($array['merchant_response_reference']);
       $this->repository->save($model);
    }
    
    /**
     * 
     * @param Merchant $model
     * @return void
     */
    public function deleteMerchant(Merchant $model): void
    {
        $this->repository->delete($model);
    }
    
    /**
     * 
     * @param sR $sR
     * @param string $date
     * @return \DateTime
     */
    public function getDateTime(sR $sR, string $date) : \DateTime
    {
        $datehelper = new DateHelper($sR);         
        $datetime = new \DateTime();
        $datetime->setTimezone(new \DateTimeZone($sR->get_setting('time_zone') ? $sR->get_setting('time_zone') : 'Europe/London')); 
        $datetime->format($datehelper->style());
        $str_replace = str_replace($datehelper->separator(), '-', $date);
        $datetime->modify($str_replace);
        return $datetime;
    }
}