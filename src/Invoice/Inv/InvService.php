<?php
declare(strict_types=1); 

namespace App\Invoice\Inv;
// Entities
use App\Invoice\Entity\Inv;
use App\Invoice\Entity\InvItem;
use App\Invoice\Entity\InvTaxRate;
use App\Invoice\Entity\InvCustom;
use App\User\User;
// Repositories
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\InvCustom\InvCustomRepository as ICR;
use App\Invoice\InvItem\InvItemRepository as IIR;
use App\Invoice\InvTaxRate\InvTaxRateRepository as ITRR;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\Setting\SettingRepository as SR;
// Helpers
use App\Invoice\Helpers\DateHelper;
// Services
use App\Invoice\InvAmount\InvAmountService as IAS;
use App\Invoice\InvCustom\InvCustomService as ICS;
use App\Invoice\InvItem\InvItemService as IIS;
use App\Invoice\InvTaxRate\InvTaxRateService as ITRS;
// Ancillary
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Security\Random;
use Yiisoft\Translator\TranslatorInterface as Translator;

use \DateTimeImmutable;

final class InvService
{
    private InvRepository $repository;
    private SessionInterface $session;
    private Translator $translator;
        
    public function __construct(IR $repository, SessionInterface $session, Translator $translator)
    {
        $this->repository = $repository;
        $this->session = $session;
        $this->translator = $translator;
    }    
    
    /**
     * bothInv replaces addInv and saveInv.
     * @see https://github.com/yiisoft/demo/issues/462
     * @param User $user
     * @param Inv $model
     * @param InvForm $form
     * @param SR $s
     * @param GR $gR
     * @return Inv
     */
    public function bothInv(User $user, Inv $model, InvForm $form, SR $s, GR $gR): Inv 
    {  
       // Only generate an Invoice Number if the following criteria are met:
       // 1. It is NOT a new record ie. NOT a draft
       // 2. The setting allowing for drafts having invoice numbers is OFF ie. only non-draft invoices
       // 3. The current invoice ie. model (NOT form) has NO number associated with it ie. a NULL record
       // 4. As an extra measure, the FORM has status 'sent' only so an invoice number can only be generated if set to 'sent' on current form.
       if ((!$model->isNewRecord()) && ($s->get_setting('generate_invoice_number_for_draft') === '0') && (null==$model->getNumber())  && ($form->getStatus_id() == 2)) {
          $model->setNumber((string)$gR->generate_number((int)$form->getGroup_id(), true));  
       }
      
       $model->nullifyRelationOnChange((int)$form->getGroup_id(),(int)$form->getClient_id(), );
       $datehelper = new DateHelper($s);
             
       $datetime_created = $datehelper->get_or_set_with_style(null!==$form->getDate_created()? $form->getDate_created() : new \DateTime());
       $datetimeimmutable_created = new \DateTimeImmutable($datetime_created instanceof \DateTime ? $datetime_created->format('Y-m-d H:i:s') : 'now');
       $model->setDate_created($datetimeimmutable_created);
       
       $datetime_supplied = $datehelper->get_or_set_with_style(null!==$form->getDate_supplied()? $form->getDate_supplied() : new \DateTime());
       $datetimeimmutable_supplied = new \DateTimeImmutable($datetime_supplied instanceof \DateTime ? $datetime_supplied->format('Y-m-d H:i:s') : 'now');
       $model->setDate_supplied($datetimeimmutable_supplied);
              
       $datetimeimmutable_tax_point = $this->set_tax_point($model, $datetimeimmutable_supplied, $datetimeimmutable_created);
       null!==$datetimeimmutable_tax_point ? $model->setDate_tax_point($datetimeimmutable_tax_point) : '';
              
       $model->setDate_due($s);
       
       null!==$form->getClient_id() ? $model->setClient_id((int)$form->getClient_id()) : '';
       null!==$form->getGroup_id() ? $model->setGroup_id((int)$form->getGroup_id()) : '';
       null!==$form->getStatus_id() ? $model->setStatus_id($form->getStatus_id()) : '';
       null!==$form->getDelivery_id() ? $model->setDelivery_id($form->getDelivery_id()) : '';
       null!==$form->getDelivery_location_id() ? $model->setDelivery_location_id($form->getDelivery_location_id()) : '';
       null!==$form->getDiscount_percent() ? $model->setDiscount_percent($form->getDiscount_percent()) : '';
       null!==$form->getDiscount_amount() ? $model->setDiscount_amount($form->getDiscount_amount()) : '';
       null!==$form->getUrl_key() ? $model->setUrl_key($form->getUrl_key()) : '';
       null!==$form->getPassword() ? $model->setPassword($form->getPassword()) : '';
       null!==$form->getPayment_method() ? $model->setPayment_method($form->getPayment_method()) : '';
       null!==$form->getTerms() ? $model->setTerms($form->getTerms()) 
                                : $this->translator->translate('invoice.payment.term.general'); 
       null!==$form->getNote() ? $model->setNote($form->getNote()) : ''; 
       null!==$form->getDocumentDescription() ? $model->setDocumentDescription($form->getDocumentDescription()) : ''; 
       null!==$form->getCreditinvoice_parent_id() ? $model->setCreditinvoice_parent_id($form->getCreditinvoice_parent_id() ?: 0) : '';
       null!==$form->getContract_id() ? $model->setContract_id($form->getContract_id()) : '';
       if ($model->isNewRecord()) {
            if ($s->get_setting('mark_invoices_sent_copy') === '1') {
                $model->setStatus_id(2);
                // If the read_only_toggle is set to 'sent', set this invoice to read only
                $model->setIs_read_only(true);
            } else {
                $model->setStatus_id(1);
                $model->setIs_read_only(false);                
            }
            // if draft invoices must get invoice numbers
            if ($s->get_setting('generate_invoice_number_for_draft') === '1') {
              $model->setNumber((string)$gR->generate_number((int)$form->getGroup_id(), true));  
            } else {
              $model->setNumber('');
            }
            null!==$form->getSo_id() ? $model->setSo_id((int)$form->getSo_id()) : ''; 
            null!==$form->getQuote_id() ? $model->setQuote_id((int)$form->getQuote_id()) : '';
            null!==$form->getDelivery_location_id() ? $model->setDelivery_location_id($form->getDelivery_location_id()) : '';
            null!==$form->getContract_id() ? $model->setContract_id($form->getContract_id()) : '';            
            $model->setUser_id((int)$user->getId());
            $model->setUrl_key(Random::string(32));            
            $model->setDate_created(new \DateTimeImmutable('now'));
            $model->setTime_created((new \DateTimeImmutable('now'))->format('H:i:s'));
            $model->setPayment_method(0);
            $model->setDate_due($s);
            $model->setDiscount_amount(0.00);
       }
       $this->repository->save($model);// Regenerate invoice numbers if the setting is changed
       
       $model->setStand_in_code($s->get_setting('stand_in_code'));
       $this->repository->save($model);
       return $model;
    }
    
    /**
     * @see https://www.gov.uk/hmrc-internal-manuals/vat-time-of-supply/vattos3600
     * @param Inv $inv
     * @param null|DateTimeImmutable $date_supplied
     * @param null|DateTimeImmutable $date_created
     * @return null|DateTimeImmutable
     */
    public function set_tax_point(Inv $inv, ?DateTimeImmutable $date_supplied, ?DateTimeImmutable $date_created) : ?DateTimeImmutable {
        // 'Date created' is used interchangeably with 'Date issued'
        if (null!==$inv->getClient()?->getClient_vat_id()) {
            if ($date_created > $date_supplied && null!==$date_created && null!==$date_supplied) {
                $diff = $date_supplied->diff($date_created)->format('%R%a');
                if ((int)$diff > 14) {
                    // date supplied more than 14 days before invoice date
                    return $date_supplied;
                } else {
                    // if the issue date (created) is within 14 days after the supply (basic) date then use the issue/created date.
                    return $date_created;
                }                                           
            }
            if ($date_created < $date_supplied) {
                // normally set the tax point to the date_created
                return $date_created;
            }
            if ($date_created === $date_supplied) {
                // normally set the tax point to the date_created
                return $date_created;
            }
        }
        // If the client is not VAT registered, the tax point is the date supplied
        if (null==$inv->getClient()?->getClient_vat_id()) {
            return $date_supplied;
        }
        if (null==$date_supplied || null==$date_created) {
            return null;
        }
        return null;
    } 
    
    /**
     * @param Inv $model
     * @param ICR $icR
     * @param ICS $icS
     * @param IIR $iiR
     * @param IIS $iiS
     * @param ITRR $itrR
     * @param ITRS $itrS
     * @param IAR $iaR
     * @param IAS $iaS
     * @return void
     */
    public function deleteInv(Inv $model, ICR $icR, ICS $icS, IIR $iiR, IIS $iiS, ITRR $itrR, ITRS $itrS, IAR $iaR, IAS $iaS): void
    {
        $inv_id = $model->getId();
        // Invs with no items: If there are no invoice items there will be no invoice amount record
        // so check if there is a invoice amount otherwise null error will occur.
        if (null!==$inv_id){
            $count = $iaR->repoInvAmountCount((int)$inv_id);        
            if ($count > 0) {
                $inv_amount = $iaR->repoInvquery((int)$inv_id);
                null!==$inv_amount ? $iaS->deleteInvAmount($inv_amount) : '';            
            }
            /** @var InvItem $item */
            foreach ($iiR->repoInvItemIdquery($inv_id) as $item) {
                $iiS->deleteInvItem($item);
            }        
            /** @var InvTaxRate */
            foreach ($itrR->repoInvquery($inv_id) as $inv_tax_rate) {
                $itrS->deleteInvTaxRate($inv_tax_rate);
            }
            /** @var InvCustom */
            foreach ($icR->repoFields($inv_id) as $inv_custom) {
                $icS->deleteInvCustom($inv_custom);
            }
        }
        $this->repository->delete($model);
    }
    
    /**
     * @param User $user
     * @param Inv $model
     * @param array $details
     * @param SR $s
     * @return void
     */
    public function saveInv_from_recurring(User $user, Inv $model, array $details, SR $s): void
    {  
       $datehelper = new DateHelper($s);
       $datetime = $datehelper->get_or_set_with_style(null!==$details['date_created'] ? $details['date_created'] : new \DateTime());
       $datetimeimmutable = new \DateTimeImmutable($datetime instanceof \DateTime ? $datetime->format('Y-m-d H:i:s') : 'now');
       $model->setDate_created($datetimeimmutable);
              
       $datetime_supplied = $datehelper->get_or_set_with_style(null!==$details['date_supplied']? $details['date_supplied'] : new \DateTime());
       $datetimeimmutable_supplied = new \DateTimeImmutable($datetime_supplied instanceof \DateTime ? $datetime_supplied->format('Y-m-d H:i:s') : 'now');
       $model->setDate_supplied($datetimeimmutable_supplied);
       
       $datetime_tax_point = $datehelper->get_or_set_with_style(null!==$details['date_tax_point']? $details['date_tax_point'] : new \DateTime());
       $datetimeimmutable_tax_point = new \DateTimeImmutable($datetime_tax_point instanceof \DateTime ? $datetime_tax_point->format('Y-m-d H:i:s') : 'now');
       $model->setDate_tax_point($datetimeimmutable_tax_point);
       
       $model->setDate_due($s);
       //$model->setDate_created($form->getDate_created());
       $model->setClient_id((int)$details['client_id']);
       $model->setGroup_id((int)$details['group_id']);
       $model->setStatus_id((int)$details['status_id']);
       $model->setDiscount_percent((float)$details['discount_percent']);
       $model->setDiscount_amount((float)$details['discount_amount']);
       $model->setUrl_key((string)$details['url_key']);
       $model->setPassword((string)$details['password']);
       $model->setPayment_method((int)$details['payment_method']);
       $model->setTerms((string)$details['terms']); 
       $model->setCreditinvoice_parent_id((int)$details['creditinvoice_parent_id'] ?: 0);
       if ($model->isNewRecord()) {
            $model->setStatus_id(1);            
            $model->setNumber((string)$details['number']);
            $random = new Random();            
            $model->setUser($user);
            $model->setUrl_key($random::string(32));            
            $model->setDate_created(new \DateTimeImmutable('now'));
            // VAT or cash basis tax system fields: ignore
            $model->setDate_supplied(new \DateTimeImmutable('now'));
            $model->setDate_tax_point(new \DateTimeImmutable('now'));
            
            $model->setTime_created((new \DateTimeImmutable('now'))->format('H:i:s'));
            $model->setPayment_method(0);
            $model->setDate_due($s);
            $model->setDiscount_amount(0.00);
       }
       $this->repository->save($model);
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

