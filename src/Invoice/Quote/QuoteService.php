<?php
declare(strict_types=1); 

namespace App\Invoice\Quote;
// Entities
use App\User\User;
use App\Invoice\Entity\Quote;
use App\Invoice\Entity\QuoteCustom;
use App\Invoice\Entity\QuoteItem;
use App\Invoice\Entity\QuoteTaxRate;

// Repositories
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\QuoteAmount\QuoteAmountRepository as QAR;
use App\Invoice\QuoteCustom\QuoteCustomRepository as QCR;
use App\Invoice\QuoteItem\QuoteItemRepository as QIR;
use App\Invoice\QuoteTaxRate\QuoteTaxRateRepository as QTRR;
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\Setting\SettingRepository as SR;
// Services
use App\Invoice\QuoteAmount\QuoteAmountService as QAS;
use App\Invoice\QuoteCustom\QuoteCustomService as QCS;
use App\Invoice\QuoteItem\QuoteItemService as QIS;
use App\Invoice\QuoteTaxRate\QuoteTaxRateService as QTRS;
// Ancillary
use Yiisoft\Security\Random;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;

final class QuoteService
{
    private QuoteRepository $repository;
    private SessionInterface $session;

    public function __construct(QR $repository, SessionInterface $session)
    {
        $this->repository = $repository;
        $this->session = $session;        
    }
    
    /**
     * @param User $user
     * @param Quote $model
     * @param QuoteForm $form
     * @param SR $s
     * @return void
     */
    public function addQuote(User $user, Quote $model, QuoteForm $form, SR $s): void
    { 
        null!==$form->getInv_id() ? $model->setInv_id((int)$form->getInv_id()) : '';
        null!==$form->getSo_id() ? $model->setSo_id((int)$form->getSo_id()) : '';
        null!==$form->getGroup_id() ? $model->setGroup_id($form->getGroup_id()) : '';
        null!==$form->getClient_id() ? $model->setClient_id($form->getClient_id()) : '';
        null!==$form->getStatus_id() ? $model->setStatus_id($form->getStatus_id()) : '';
        null!==$form->getDiscount_amount() ? $model->setDiscount_amount($form->getDiscount_amount()) : '';
        null!==$form->getDiscount_percent() ? $model->setDiscount_percent($form->getDiscount_percent()) : '';       
        null!==$form->getUrl_key() ? $model->setUrl_key($form->getUrl_key()) : '';
        null!==$form->getPassword() ? $model->setPassword($form->getPassword()) : '';
        null!==$form->getNotes() ? $model->setNotes($form->getNotes()) : '';       
        if ($model->isNewRecord()) {
             $model->setInv_id(0);             
             $model->setSo_id(0);
             !empty($form->getNumber()) ? $model->setNumber($form->getNumber()) : '';
             $model->setStatus_id(1);
             $model->setUser_id((int)$user->getId());
             $model->setUrl_key(Random::string(32));            
             $model->setDate_created(new \DateTimeImmutable('now'));
             $model->setDate_expires($s);
             $model->setDiscount_amount(0.00);
        }
        $this->repository->save($model);
    }
    
    /**
     * @param User $user
     * @param Quote $model
     * @param QuoteForm $form
     * @param SR $s
     * @return Quote
     */
    public function saveQuote(User $user, Quote $model, QuoteForm $form, SR $s, GR $gR): Quote
    { 
        $model->setInv_id((int)$form->getInv_id());
        $model->setSo_id((int)$form->getSo_id());
        null!==$form->getClient_id() ? $model->setClient($model->getClient()?->getClient_id() == $form->getClient_id() ? $model->getClient() : null): '';
        $model->setClient_id((int)$form->getClient_id());
       
        null!==$form->getGroup_id() ? $model->setGroup($model->getGroup()?->getId() == $form->getGroup_id() ? $model->getGroup() : null): '';
        $model->setGroup_id((int)$form->getGroup_id());          
        
        null!==$form->getStatus_id() ? $model->setStatus_id($form->getStatus_id()) : '';
        null!==$form->getDiscount_percent() ? $model->setDiscount_percent($form->getDiscount_percent()) : '';
        null!==$form->getDiscount_amount() ? $model->setDiscount_amount($form->getDiscount_amount()) : '';
        null!==$form->getUrl_key() ? $model->setUrl_key($form->getUrl_key()) : '';
        null!==$form->getPassword() ? $model->setPassword($form->getPassword()) : '';
        null!==$form->getNotes() ? $model->setNotes($form->getNotes()) : '';
        if ($model->isNewRecord()) {
             $model->setInv_id(0); 
             $model->setSo_id(0);
             !empty($form->getNumber()) ? $model->setNumber($form->getNumber()) : '';
             $model->setStatus_id(1);
             $model->setUser($user);
             $model->setUser_id((int)$user->getId());
             $model->setUrl_key(Random::string(32));            
             $model->setDate_created(new \DateTimeImmutable('now'));
             $model->setDate_expires($s);
             $model->setDiscount_amount(0.00);
        }
        // Regenerate quote numbers if the setting is changed
        if (!$model->isNewRecord() && $s->get_setting('generate_quote_number_for_draft') === '1') {
             null!==$form->getGroup_id() ? $model->setNumber((string)$gR->generate_number($form->getGroup_id(), true)) : '';  
        }
        $this->repository->save($model);
        return $model;
    }
    
    /**
     * bothQuote replaced addQuote and saveQuote. addQuote and saveQuote will be merged into saveQuote ultimately
     * @param User $user
     * @param Quote $model
     * @param QuoteForm $form
     * @param SR $s
     * @param GR $gR
     * @return Quote
     */
    public function bothQuote(User $user, Quote $model, QuoteForm $form, SR $s, GR $gR): Quote
    {    
        $model->nullifyRelationOnChange((int)$form->getGroup_id(),(int)$form->getClient_id());
        null!==$form->getInv_id() ? $model->setInv_id((int)$form->getInv_id()) : '';
        null!==$form->getSo_id() ? $model->setSo_id((int)$form->getSo_id()) : '';
        null!==$form->getClient_id() ? $model->setClient_id($form->getClient_id()) : 0;
        null!==$form->getGroup_id() ? $model->setGroup_id($form->getGroup_id()) : 0;
        null!==$form->getStatus_id() ? $model->setStatus_id($form->getStatus_id()) : '';
        null!==$form->getDiscount_percent() ? $model->setDiscount_percent($form->getDiscount_percent()) : '';
        null!==$form->getDiscount_amount() ? $model->setDiscount_amount($form->getDiscount_amount()) : '';
        null!==$form->getUrl_key() ? $model->setUrl_key($form->getUrl_key()) : '';
        null!==$form->getPassword() ? $model->setPassword($form->getPassword()) : '';
        null!==$form->getNotes() ? $model->setNotes($form->getNotes()) : '';
        if ($model->isNewRecord()) {
             $model->setInv_id(0);
             $model->setSo_id(0);
             !empty($form->getNumber()) ? $model->setNumber($form->getNumber()) : '';
             $model->setStatus_id(1);
             $model->setUser($user);
             $model->setUser_id((int)$user->getId());
             $model->setUrl_key(Random::string(32));            
             $model->setDate_created(new \DateTimeImmutable('now'));
             $model->setDate_expires($s);
             $model->setDiscount_amount(0.00);
        }
        // Regenerate quote numbers if the setting is changed
        if (!$model->isNewRecord() && $s->get_setting('generate_quote_number_for_draft') === '1') {
             null!==$form->getGroup_id() ? $model->setNumber((string)$gR->generate_number($form->getGroup_id(), true)) : '';  
        }
        $this->repository->save($model);
        return $model;
    }
    
    /**
     * @param Quote $model
     * @param QCR $qcR
     * @param QCS $qcS
     * @param QIR $qiR
     * @param QIS $qiS
     * @param QTRR $qtrR
     * @param QTRS $qtrS
     * @param QAR $qaR
     * @param QAS $qaS
     * @return void
     */
    
    public function deleteQuote(Quote $model, QCR $qcR, QCS $qcS, QIR $qiR, QIS $qiS, QTRR $qtrR, QTRS $qtrS, QAR $qaR, QAS $qaS): void
    {
        $quote_id = $model->getId();
        // Quotes with no items: If there are no quote items there will be no quote amount record
        // so check if there is a quote amount otherwise null error will occur.
        if (null!==$quote_id){
            $count = $qaR->repoQuoteAmountCount($quote_id);        
            if ($count > 0) {
                $quote_amount = $qaR->repoQuotequery($quote_id);
                if ($quote_amount) {
                    $qaS->deleteQuoteAmount($quote_amount);
                }    
            }
            
            /** @var QuoteItem $item */
            foreach ($qiR->repoQuoteItemIdquery($quote_id) as $item) {
                     $qiS->deleteQuoteItem($item);
            }        
            
            /** @var QuoteTaxRate $quote_tax_rate */
            foreach ($qtrR->repoQuotequery($quote_id) as $quote_tax_rate) {
                     $qtrS->deleteQuoteTaxRate($quote_tax_rate);
            }
            
            /** @var QuoteCustom $quote_custom */
            foreach ($qcR->repoFields($quote_id) as $quote_custom) {
                     $qcS->deleteQuoteCustom($quote_custom);
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