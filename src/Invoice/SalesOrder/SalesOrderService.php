<?php
declare(strict_types=1); 

namespace App\Invoice\SalesOrder;
// Entities
use App\User\User;
use App\Invoice\Entity\SalesOrder;
use App\Invoice\Entity\SalesOrderCustom;
use App\Invoice\Entity\SalesOrderItem;
use App\Invoice\Entity\SalesOrderTaxRate;

// Repositories
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\SalesOrderAmount\SalesOrderAmountRepository as SoAR;
use App\Invoice\SalesOrderCustom\SalesOrderCustomRepository as SoCR;
use App\Invoice\SalesOrderItem\SalesOrderItemRepository as SoIR;
use App\Invoice\SalesOrderTaxRate\SalesOrderTaxRateRepository as SoTRR;
use App\Invoice\SalesOrder\SalesOrderRepository as SoR;
use App\Invoice\Setting\SettingRepository as SR;
// Services
use App\Invoice\SalesOrderAmount\SalesOrderAmountService as SoAS;
use App\Invoice\SalesOrderCustom\SalesOrderCustomService as SoCS;
use App\Invoice\SalesOrderItem\SalesOrderItemService as SoIS;
use App\Invoice\SalesOrderTaxRate\SalesOrderTaxRateService as SoTRS;
// Ancillary
use Yiisoft\Security\Random;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;

final class SalesOrderService
{
    private SalesOrderRepository $repository;
    private SessionInterface $session;

    public function __construct(SoR $repository, SessionInterface $session)
    {
        $this->repository = $repository;
        $this->session = $session;        
    }
    
    /**
     * 
     * @param User $user
     * @param SalesOrder $model
     * @param SalesOrderForm $form
     * @return void
     */
    public function addSo(User $user, SalesOrder $model, SalesOrderForm $form): void
    {  
        null!==$form->getQuote_id() ? $model->setQuote_id((int)$form->getQuote_id()) : '';
        null!==$form->getInv_id() ? $model->setInv_id((int)$form->getInv_id()) : '';
        null!==$form->getGroup_id() ? $model->setGroup_id($form->getGroup_id()) : '';
        null!==$form->getClient_id() ? $model->setClient_id($form->getClient_id()) : '';
        null!==$form->getClient_po_number() ? $model->setClient_po_number($form->getClient_po_number()) : '';
        null!==$form->getClient_po_person() ? $model->setClient_po_person($form->getClient_po_person()) : '';
        null!==$form->getStatus_id() ? $model->setStatus_id($form->getStatus_id()) : '';
        null!==$form->getDiscount_amount() ? $model->setDiscount_amount($form->getDiscount_amount()) : '';
        null!==$form->getDiscount_percent() ? $model->setDiscount_percent($form->getDiscount_percent()) : '';       
        null!==$form->getUrl_key() ? $model->setUrl_key($form->getUrl_key()) : Random::string(32);
        null!==$form->getPassword() ? $model->setPassword($form->getPassword()) : '';
        null!==$form->getNotes() ? $model->setNotes($form->getNotes()) : '';       
        null!==$form->getPaymentTerm() ? $model->setPaymentTerm($form->getPaymentTerm()) : '';
        $model->setNumber($form->getNumber());
        if ($model->isNewRecord()) {        
             $model->setStatus_id(1);
             $model->setUser_id((int)$user->getId());
             $model->setDate_created(new \DateTimeImmutable('now'));
             $model->setDiscount_amount(0.00);
        }
        $this->repository->save($model);
    }
    
    /**
     * @param SalesOrder $model
     * @param SalesOrderForm $form
     * @param SR $s
     * @return SalesOrder
     */
    public function saveSo(SalesOrder $model, SalesOrderForm $form, SR $s, GR $gR): SalesOrder
    { 
        $model->setQuote_id((int)$form->getQuote_id());
        $model->setInv_id((int)$form->getInv_id());
        null!==$form->getClient_id() ? $model->setClient_id($form->getClient_id()) : '';
        null!==$form->getGroup_id() ? $model->setGroup_id($form->getGroup_id()) : '';          
        null!==$form->getClient_po_number() ? $model->setClient_po_number($form->getClient_po_number()) : '';
        null!==$form->getClient_po_person() ? $model->setClient_po_person($form->getClient_po_person()) : '';
        null!==$form->getStatus_id() ? $model->setStatus_id($form->getStatus_id()) : '';
        null!==$form->getDiscount_percent() ? $model->setDiscount_percent($form->getDiscount_percent()) : '';
        null!==$form->getDiscount_amount() ? $model->setDiscount_amount($form->getDiscount_amount()) : '';
        null!==$form->getUrl_key() ? $model->setUrl_key($form->getUrl_key()) : '';
        null!==$form->getPassword() ? $model->setPassword($form->getPassword()) : '';
        null!==$form->getNotes() ? $model->setNotes($form->getNotes()) : '';       
        null!==$form->getPaymentTerm() ? $model->setPaymentTerm($form->getPaymentTerm()) : '';
        $this->repository->save($model);
        return $model;
    }
    
    /**
     * @param SalesOrder $model
     * @param SoCR $socR
     * @param SoCS $socS
     * @param SoIR $soiR
     * @param SoIS $soiS
     * @param SoTRR $sotrR
     * @param SoTRS $sotrS
     * @param SoAR $soaR
     * @param SoAS $soaS
     * @return void
     */
    
    public function deleteSo(SalesOrder $model, SoCR $socR, SoCS $socS, SoIR $soiR, SoIS $soiS, SoTRR $sotrR, SoTRS $sotrS, SoAR $soaR, SoAS $soaS): void
    {
        $so_id = $model->getId();
        // SalesOrders with no items: If there are no quote items there will be no quote amount record
        // so check if there is a quote amount otherwise null error will occur.
        if (null!==$so_id){
            $count = $soaR->repoSalesOrderAmountCount($so_id);        
            if ($count > 0) {
                $so_amount = $soaR->repoSalesOrderquery($so_id);
                if ($so_amount) {
                    $soaS->deleteSalesOrderAmount($so_amount);
                }    
            }
            
            /** @var SalesOrderItem $item */
            foreach ($soiR->repoSalesOrderItemIdquery($so_id) as $item) {
                     $soiS->deleteSalesOrderItem($item);
            }        
            
            /** @var SalesOrderTaxRate $so_tax_rate */
            foreach ($sotrR->repoSalesOrderquery($so_id) as $so_tax_rate) {
                     $sotrS->deleteSalesOrderTaxRate($so_tax_rate);
            }
            
            /** @var SalesOrderCustom $so_custom */
            foreach ($socR->repoFields($so_id) as $so_custom) {
                     $socS->deleteSalesOrderCustom($so_custom);
            }
            $this->repository->delete($model);
        }        
    }
    
    /**
     * @param string $level
     * @param string $message
     * @return Flash
     */
    private function flash(string $level, string $message): Flash{
        $flash = new Flash($this->session);
        $flash->set($level, $message); 
        return $flash;
    }
}