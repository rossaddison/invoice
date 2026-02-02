<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

// Entities
use App\Invoice\Entity\Inv;
use App\Invoice\Entity\InvItem;
use App\Invoice\Entity\InvItemAllowanceCharge;
use App\Invoice\Entity\InvAllowanceCharge;
use App\Invoice\Entity\InvTaxRate;
use App\Invoice\Entity\InvCustom;
use App\User\User;
// Repositories
use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\InvCustom\InvCustomRepository as ICR;
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository as ACIR;
use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\InvItem\InvItemRepository as IIR;
use App\Invoice\InvTaxRate\InvTaxRateRepository as ITRR;
use App\Invoice\Setting\SettingRepository as SR;
use App\User\UserRepository as UR;
// Helpers
use App\Invoice\Helpers\DateHelper;
// Services
use App\Invoice\InvAmount\InvAmountService as IAS;
use App\Invoice\InvCustom\InvCustomService as ICS;
use App\Invoice\InvItem\InvItemService as IIS;
use App\Invoice\InvTaxRate\InvTaxRateService as ITRS;
// Ancillary
use Yiisoft\Session\SessionInterface;
use Yiisoft\Security\Random;
use Yiisoft\Translator\TranslatorInterface as Translator;
use DateTimeImmutable;

final readonly class InvService
{
    public function __construct(
        private InvRepository $repository,
        private SessionInterface $session,
        private Translator $translator,
        private CR $cR,
        private GR $gR,
        private UR $uR,
    ) {
    }

    public function saveInv(
        User $user,
        Inv $model,
        array $array,
        SR $s,
        GR $gR
    ): Inv {
        $this->persist($model, $array);
        /**
         * Give a legitimate invoice number to an invoice that currently:
         * 1. Exists
         * 2. Has no invoice number
         * 3. Has a status of 'sent'
         */
        if ((!$model->isNewRecord()) && (strlen($model->getNumber() ?? '') == 0)
                && ($array['status_id'] == 2)) {
            $model->setNumber(
                (string) $gR->generate_number(
                    (int) $array['group_id'], true));
        }

        /**
         * The following fields are not set on the form but are calculated
         *  automatically
         */

        $datetime_created = new DateTimeImmutable();

        /**
         * @var string $array['date_created']
         */
        $date_created = $array['date_created'] ??
                (new DateTimeImmutable('now'))->format('Y-m-d');
        $model->setDate_created($date_created);

        $datetime_supplied = new DateTimeImmutable();
        /**
         * @var string $array['date_supplied']
         */
        $date_supplied = $array['date_supplied'] ??
                (new DateTimeImmutable('now'))->format('Y-m-d');
        $model->setDate_supplied($datetime_supplied::createFromFormat('Y-m-d',
                $date_supplied) ?: new DateTimeImmutable('1901/01/01'));


        $datetimeimmutable_tax_point = $this->set_tax_point(
            $model,
            $datetime_supplied::createFromFormat('Y-m-d', $date_supplied) ?:
                new DateTimeImmutable('1901/01/01'),
            $datetime_created::createFromFormat('Y-m-d', $date_created) ?:
                new DateTimeImmutable('1901/01/01'),
        );
        null !== $datetimeimmutable_tax_point ?
                $model->setDate_tax_point($datetimeimmutable_tax_point) : '';

        $model->setDate_due($s);

        $model->setUrl_key(Random::string(32));
        $model->setStand_in_code($s->getSetting('stand_in_code'));

        /**
         * The following fields can be edited and set on the form 
         * with a value that is not null.
         */
        isset($array['client_id']) ? 
            $model->setClient_id((int) $array['client_id']) : '';
        isset($array['group_id']) ? 
            $model->setGroup_id((int) $array['group_id']) : '';
        /** user_id set on adding */

        isset($array['so_id']) ? 
            $model->setSo_id((int) $array['so_id']) : '';
        isset($array['quote_id']) ? 
            $model->setQuote_id((int) $array['quote_id']) : '';
        isset($array['status_id']) ? 
            $model->setStatus_id((int) $array['status_id']) : '';
        isset($array['delivery_id']) ? 
            $model->setDelivery_id((int) $array['delivery_id']) : '';
        isset($array['delivery_location_id']) ? 
            $model->setDelivery_location_id(
                (int) $array['delivery_location_id']) : '';
        isset($array['postal_address_id']) ? 
            $model->setPostal_address_id(
                (int) $array['postal_address_id']) : '';
        isset($array['discount_amount']) ? 
            $model->setDiscount_amount(
                (float) $array['discount_amount']) : '';
        isset($array['password']) ? 
            $model->setPassword((string) $array['password']) : '';
        isset($array['payment_method']) ? 
            $model->setPayment_method(
                (int) $array['payment_method']) : '';
        isset($array['terms']) ? 
            $model->setTerms((string) $array['terms']) : 
            $this->translator->translate('payment.term.general');
        isset($array['note']) ? 
            $model->setNote((string) $array['note']) : '';
        isset($array['document_description']) ? 
            $model->setDocumentDescription(
                (string) $array['document_description']) : '';
        isset($array['creditinvoice_parent_id']) ? 
            $model->setCreditinvoice_parent_id(
                (int) $array['creditinvoice_parent_id'] ?: 0) : '';
        isset($array['contract_id']) ? 
            $model->setContract_id((int) $array['contract_id']) : '';

        if ($model->isNewRecord()) {
            if ($s->getSetting('mark_invoices_sent_copy') === '1') {
// mark the copy as sent and make it read-only
                $model->setStatus_id(2);
                $model->setIs_read_only(true);
            } else {
// mark the invoice as a draft copy and make it editable i.e. not is read only
                $model->setStatus_id(1);
                $model->setIs_read_only(false);
            }
// if draft invoices must get invoice numbers
            if ($s->getSetting('generate_invoice_number_for_draft') === '1') {
                $model->setNumber(
                 (string) $gR->generate_number((int) $array['group_id'], true));
            } else {
                $model->setNumber('');
            }
            $model->setUser_id((int) $user->getId());
            $model->setTime_created(
                                (new DateTimeImmutable('now'))->format('H:i:s'));
            $model->setPayment_method(
                    (int) $s->getSetting('invoice_default_payment_method') ?: 4);
            if (!isset($array['discount_amount'])) {
                $model->setDiscount_amount(0.00);
            }
        }
        $this->repository->save($model);
        return $model;
    }

    private function persist(Inv $model, array $array): Inv
    {
        $client = 'client_id';
        if (isset($array[$client])) {
            $model->setClient(
                $this->cR->repoClientquery(
                    (string) $array[$client]));
        }
        $group = 'group_id';
        if (isset($array[$group])) {
            $model->setGroup(
                $this->gR->repoGroupQuery(
                    (string) $array[$group]));
        }
        $user = 'user_id';
        if (isset($array[$user])) {
            $userEntity = $this->uR->findById(
                (string) $array[$user]);
            if ($userEntity) {
                $model->setUser($userEntity);
            }
        }
        return $model;
    }

    public function copyInv(User $user, Inv $model, array $array, SR $s, GR $gR): Inv
    {
        /**
         * Follows Inv construct sequence to make sure no fields are missing.
         */
        $model->setClient_id((int) $array['client_id']);
        $model->setGroup_id((int) $array['group_id']);
        $model->setSo_id((int) $array['so_id']);
        $model->setQuote_id((int) $array['quote_id']);
        $model->setUser_id((int) $user->getId());
        $model->setStatus_id((int) $array['status_id']);
        $model->setIs_read_only((bool) $array['is_read_only']);
        $model->setPassword((string) $array['password']);
        $model->setDate_due($s);
        $model->setDate_supplied(
            $array['date_supplied'] instanceof DateTimeImmutable ? 
                $array['date_supplied'] : 
                new DateTimeImmutable('now'));
        $model->setDate_tax_point(
            $array['date_tax_point'] instanceof DateTimeImmutable ? 
                $array['date_tax_point'] : 
                new DateTimeImmutable('now'));
        $model->setTime_created((string) $array['time_created']);
        $model->setStand_in_code((string) $array['stand_in_code']);
        $model->setNumber((string) $array['number']);
        $model->setDiscount_amount(
            (float) $array['discount_amount']);
        $model->setTerms(
            (string) $array['terms'] ?: 
                $this->translator->translate(
                    'payment.term.general'));
        $model->setNote((string) $array['note']);
        $model->setDocumentDescription(
            (string) $array['document_description']);
        $model->setUrl_key((string) $array['url_key']);
        $model->setPayment_method(
            (int) $array['payment_method'] ?: 4);
        $model->setCreditinvoice_parent_id(
            (int) $array['creditinvoice_parent_id'] ?: 0);
        $model->setDelivery_id((int) $array['delivery_id']);
        $model->setDelivery_location_id(
            (int) $array['delivery_location_id']);
        $model->setPostal_address_id(
            (int) $array['postal_address_id']);
        $model->setContract_id((int) $array['contract_id']);
        $this->repository->save($model);
        return $model;
    }

    /**
     * Related logic: see 
     * https://www.gov.uk/hmrc-internal-manuals/
     * vat-time-of-supply/vattos3600
     * @param Inv $inv
     * @param DateTimeImmutable|null $date_supplied
     * @param DateTimeImmutable|null $date_created
     * @return DateTimeImmutable|null
     */
    public function set_tax_point(
        Inv $inv,
        ?DateTimeImmutable $date_supplied,
        ?DateTimeImmutable $date_created
    ): ?DateTimeImmutable {
        // Terminoligy: 'Date created' is used 
        // interchangeably with 'Date issued'
        if (null !== $inv->getClient()?->getClient_vat_id()) {
            if ($date_created > $date_supplied 
                && null !== $date_created 
                && null !== $date_supplied) {
                $diff = $date_supplied->diff($date_created)->format(
                    '%R%a');
                if ((int) $diff > 14) {
                    // date supplied more than 14 days before invoice date
                    return $date_supplied;
                }
// if the issue date (created) is within 14 days after the supply (basic) date
// then use the issue/created date.
                return $date_created;
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
        if (null == $inv->getClient()?->getClient_vat_id()) {
            return $date_supplied;
        }
        if (null == $date_supplied || null == $date_created) {
            return null;
        }
        return null;
    }

    /**
     * @param Inv $model
     * @param ACIR $aciR
     * @param ACIIR $aciiR
     * @param IIAR $iiaR
     * @param ICR $icR
     * @param ICS $icS
     * @param IIR $iiR
     * @param IIS $iiS
     * @param ITRR $itrR
     * @param ITRS $itrS
     * @param IAR $iaR
     * @param IAS $iaS
     */
    public function deleteInv(
        Inv $model,
        ACIR $aciR,
        ACIIR $aciiR,
        IIAR $iiaR,
        ICR $icR,
        ICS $icS,
        IIR $iiR,
        IIS $iiS,
        ITRR $itrR,
        ITRS $itrS,
        IAR $iaR,
        IAS $iaS,
    ): void {
        // Compare with function flush which follows LIFO (Last In First Out) record creation
        // To avoid foreign key constraint violations, delete entities that have FK's first
        // i.e a field(s) in a table/entity with _id at the end of it
        $inv_id = $model->getId();
        if (null !== $inv_id) {
            /** @var InvItem $item */
            foreach ($iiR->repoInvItemIdquery($inv_id) as $item) {
                $itemId = $item->getId();
                if (null !== $itemId) {
                    // InvItemAmount has inv_item_id as a foreign key so delete first to avoid Fk integrity error
                    $invItemAmount = $iiaR->repoInvItemAmountquery((string) $itemId);
                    if (null !== $invItemAmount) {
                        $iiaR->delete($invItemAmount);
                    }
                    // InvItemAllowanceCharge has an inv_item_id as a foreign key so delete first to avoid FK integrity error
                    $invItemAllowanceCharges = $aciiR->repoInvItemquery((string) $itemId);
                    /** @var InvItemAllowanceCharge $invItemAllowanceCharge */
                    foreach ($invItemAllowanceCharges as $invItemAllowanceCharge) {
                        $aciiR->delete($invItemAllowanceCharge);
                    }
                    // Now can delete the inv item
                    $iiS->deleteInvItem($item);
                }
            }
            $count = $iaR->repoInvAmountCount((int) $inv_id);
            if ($count > 0) {
                $inv_amount = $iaR->repoInvquery((int) $inv_id);
                null !== $inv_amount ? $iaS->deleteInvAmount($inv_amount) : '';
            }
            /** @var InvTaxRate */
            foreach ($itrR->repoInvquery($inv_id) as $inv_tax_rate) {
                $itrS->deleteInvTaxRate($inv_tax_rate);
            }
            /** @var InvCustom */
            foreach ($icR->repoFields($inv_id) as $inv_custom) {
                $icS->deleteInvCustom($inv_custom);
            }
            /** @var InvAllowanceCharge */
            foreach ($aciR->repoACIquery($inv_id) as $inv_allowance_charge) {
                $aciR->delete($inv_allowance_charge);
            }
        }

        $this->repository->delete($model);
    }

    /**
     * @param User $user
     * @param Inv $model
     * @param array $details
     * @param SR $s
     */
    public function saveInv_from_recurring(User $user, Inv $model, array $details, SR $s): void
    {
        $datehelper = new DateHelper($s);
        $datetime = $datehelper->get_or_set_with_style($details['date_created'] ?? new \DateTime());
        $datetimeimmutable = new DateTimeImmutable($datetime instanceof \DateTime ? $datetime->format('Y-m-d H:i:s') : 'now');
        $model->setDate_created($datetimeimmutable->format('Y-m-d'));

        $datetime_supplied = $datehelper->get_or_set_with_style($details['date_supplied'] ?? new \DateTime());
        $datetimeimmutable_supplied = new DateTimeImmutable($datetime_supplied instanceof \DateTime ? $datetime_supplied->format('Y-m-d H:i:s') : 'now');
        $model->setDate_supplied($datetimeimmutable_supplied);

        $datetime_tax_point = $datehelper->get_or_set_with_style($details['date_tax_point'] ?? new \DateTime());
        $datetimeimmutable_tax_point = new DateTimeImmutable($datetime_tax_point instanceof \DateTime ? $datetime_tax_point->format('Y-m-d H:i:s') : 'now');
        $model->setDate_tax_point($datetimeimmutable_tax_point);

        $model->setDate_due($s);
        //$model->setDate_created($form->getDate_created());
        $model->setClient_id((int) $details['client_id']);
        $model->setGroup_id((int) $details['group_id']);
        $model->setStatus_id((int) $details['status_id']);
        $model->setDiscount_amount((float) $details['discount_amount']);
        $model->setUrl_key((string) $details['url_key']);
        $model->setPassword((string) $details['password']);
        $model->setPayment_method((int) $details['payment_method']);
        $model->setTerms((string) $details['terms']);
        $model->setCreditinvoice_parent_id((int) $details['creditinvoice_parent_id'] ?: 0);
        $model->setDelivery_id((int) $details['delivery_id'] ?: 0);
        $model->setDelivery_location_id((int) $details['delivery_location_id'] ?: 0);
        $model->setPostal_address_id((int) $details['postal_address_id'] ?: 0);
        $model->setContract_id((int) $details['contract_id'] ?: 0);
        if ($model->isNewRecord()) {
            $model->setStatus_id(1);
            $model->setNumber((string) $details['number']);
            $random = new Random();
            $model->setUser($user);
            $model->setUrl_key($random::string(32));
            $model->setDate_created((new DateTimeImmutable('now'))->format('Y-m-d'));
            // VAT or cash basis tax system fields: ignore
            $model->setDate_supplied(new DateTimeImmutable('now'));
            $model->setDate_tax_point(new DateTimeImmutable('now'));
            $model->setTime_created((new DateTimeImmutable('now'))->format('H:i:s'));
            $model->setPayment_method(0);
            $model->setDate_due($s);
            $model->setDiscount_amount(0.00);
        }
        $this->repository->save($model);
    }
}
