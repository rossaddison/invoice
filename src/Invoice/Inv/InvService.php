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
use App\Invoice\DeliveryLocation\DeliveryLocationRepository as DLR;
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\InvCustom\InvCustomRepository as ICR;
use App\Invoice\InvItem\InvItemRepository as IIR;
use App\Invoice\InvTaxRate\InvTaxRateRepository as ITRR;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\PostalAddress\PostalAddressRepository as PAR;
use App\Invoice\Setting\SettingRepository as SR;
// Helpers
use App\Invoice\Helpers\DateHelper;
// Services
use App\Invoice\DeliveryLocation\DeliveryLocationService as DLS;
use App\Invoice\InvAmount\InvAmountService as IAS;
use App\Invoice\InvCustom\InvCustomService as ICS;
use App\Invoice\InvItem\InvItemService as IIS;
use App\Invoice\InvTaxRate\InvTaxRateService as ITRS;
use App\Invoice\PostalAddress\PostalAddressService as PAS;
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
    
    public function saveInv(User $user, Inv $model, array $array, SR $s, GR $gR): Inv 
    {  
       // Only generate an Invoice Number if the following criteria are met:
       // 1. It is NOT a new record ie. NOT a draft
       // 2. The setting allowing for drafts having invoice numbers is OFF ie. only non-draft invoices
       // 3. The current invoice ie. model (NOT form) has NO number associated with it ie. a NULL record
       // 4. As an extra measure, the FORM has status 'sent' only so an invoice number can only be generated if set to 'sent' on current form.
       if ((!$model->isNewRecord()) && ($s->get_setting('generate_invoice_number_for_draft') === '0') && (null==$model->getNumber())  && ($array['status_id'] == 2)) {
          $model->setNumber((string)$gR->generate_number((int)$array['group_id'], true));  
       }
       
       $datetime_created = new \DateTimeImmutable();
       /**
        * @var string $array['date_created']
        */
       $date_created = $array['date_created'] ?? '';
       $model->setDate_created($datetime_created::createFromFormat('Y-m-d' , $date_created) ?: new \DateTimeImmutable('1901/01/01'));
       
       $datetime_supplied = new \DateTimeImmutable();
       /**
        * @var string $array['date_supplied']
        */
       $date_supplied = $array['date_supplied'] ?? '';
       $model->setDate_supplied($datetime_supplied::createFromFormat('Y-m-d' , $date_supplied) ?: new \DateTimeImmutable('1901/01/01'));
       
              
       $datetimeimmutable_tax_point = $this->set_tax_point($model, 
                                      $datetime_supplied::createFromFormat('Y-m-d' , $date_supplied) ?: new \DateTimeImmutable('1901/01/01'), 
                                      $datetime_created::createFromFormat('Y-m-d' , $date_created) ?: new \DateTimeImmutable('1901/01/01'));
       null!==$datetimeimmutable_tax_point ? $model->setDate_tax_point($datetimeimmutable_tax_point) : '';
       
       $model->setDate_due($s);
       
       isset($array['client_id']) ? $model->setClient_id((int)$array['client_id']) : '';
       isset($array['group_id']) ? $model->setGroup_id((int)$array['group_id']) : '';
       isset($array['status_id']) ? $model->setStatus_id((int)$array['status_id']) : '';       
       isset($array['delivery_id']) ? $model->setDelivery_id((int)$array['delivery_id']) : '';
       isset($array['delivery_location_id']) ? $model->setDelivery_location_id((int)$array['delivery_location_id']) : '';
       isset($array['postal_address_id']) ? $model->setPostal_address_id((int)$array['postal_address_id']) : '';
       isset($array['discount_percent']) ? $model->setDiscount_percent((float)$array['discount_percent']) : '';
       isset($array['discount_amount']) ? $model->setDiscount_amount((float)$array['discount_amount']) : '';
       isset($array['url_key']) ? $model->setUrl_key((string)$array['url_key']) : '';
       isset($array['password']) ? $model->setPassword((string)$array['password']) : '';
       isset($array['payment_method']) ? $model->setPayment_method((int)$array['payment_method']) : '';
       isset($array['terms']) ? $model->setTerms((string)$array['terms']) 
                                : $this->translator->translate('invoice.payment.term.general'); 
       isset($array['note']) ? $model->setNote((string)$array['note']) : ''; 
       isset($array['document_description']) ? $model->setDocumentDescription((string)$array['document_description']) : ''; 
       isset($array['creditinvoice_parent_id']) ? $model->setCreditinvoice_parent_id((int)$array['creditinvoice_parent_id'] ?: 0) : '';
       isset($array['contract_id']) ? $model->setContract_id((int)$array['contract_id']) : '';
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
              $model->setNumber((string)$gR->generate_number((int)$array['group_id'], true));  
            } else {
              $model->setNumber('');
            }
            isset($array['so_id']) ? $model->setSo_id((int)$array['so_id']) : ''; 
            isset($array['delivery_id']) ? $model->setDelivery_id((int)$array['delivery_id']) : '';
            isset($array['quote_id']) ? $model->setQuote_id((int)$array['quote_id']) : '';
            isset($array['delivery_location_id']) ? $model->setDelivery_location_id((int)$array['delivery_location_id']) : '';
            isset($array['postal_address_id']) ? $model->setPostal_address_id((int)$array['postal_address_id']) : '';
            isset($array['contract_id']) ? $model->setContract_id((int)$array['contract_id']) : '';            
            $model->setUser_id((int)$user->getId());
            $model->setUrl_key(Random::string(32));            
            $model->setDate_created(new \DateTimeImmutable('now'));
            $model->setTime_created((new \DateTimeImmutable('now'))->format('H:i:s'));
            $model->setPayment_method((int)$s->get_setting('invoice_default_payment_method') ?: 4);
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
     * @param DLR $dlR
     * @param PAR $paR
     * @param DLS $dlS
     * @param PAS $paS
     * @return void
     */
    public function deleteInv(Inv $model, ICR $icR, ICS $icS, IIR $iiR, 
                              IIS $iiS, ITRR $itrR, ITRS $itrS, IAR $iaR, 
                              IAS $iaS, DLR $dlR, PAR $paR, DLS $dlS, PAS $paS) : void
    {
        $inv_id = $model->getId();
        $delivery_location_id = $model->getDelivery_location_id();
        $postal_address_id = $model->getPostal_address_id();
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
        
        if ($postal_address_id >  0) {
            /**
             * @var \Yiisoft\Data\Cycle\Reader\EntityReader $postalAddresses
             */
            $postalAddresses = $paR->repoPostalAddressLoadedquery($postal_address_id);
            /**
             * @var \App\Invoice\Entity\PostalAddress $postal_address
             */
            foreach ($postalAddresses as $postal_address) {
                $paS->deletePostalAddress($postal_address);
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
       $model->setDelivery_id((int)$details['delivery_id'] ?: 0);
       $model->setDelivery_location_id((int)$details['delivery_location_id'] ?: 0);
       $model->setPostal_address_id((int)$details['postal_address_id'] ?: 0);
       $model->setContract_id((int)$details['contract_id'] ?: 0);
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

