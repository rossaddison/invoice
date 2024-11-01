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
     * @param array $array
     * @param SR $s
     * @param GR $gR
     * @return Quote
     */
    public function saveQuote(User $user, Quote $model, array $array, SR $s, GR $gR): Quote
    {
        $model->nullifyRelationOnChange((int)$array['group_id'], (int)$array['client_id']);

        $datetime_created = new \DateTimeImmutable();
        /**
         * @var string $array['date_created']
         */
        $date_created = $array['date_created'] ?? '';
        $model->setDate_created($datetime_created::createFromFormat('Y-m-d', $date_created) ?: new \DateTimeImmutable('1901/01/01'));

        isset($array['inv_id']) ? $model->setInv_id((int)$array['inv_id']) : '';
        isset($array['so_id']) ? $model->setSo_id((int)$array['so_id']) : '';
        isset($array['client_id']) ? $model->setClient_id((int)$array['client_id']) : 0;
        isset($array['group_id']) ? $model->setGroup_id((int)$array['group_id']) : 0;
        isset($array['status_id']) ? $model->setStatus_id((int)$array['status_id']) : '';
        isset($array['delivery_location_id']) ? $model->setDelivery_location_id((int)$array['delivery_location_id']) : '';
        isset($array['discount_percent']) ? $model->setDiscount_percent((float)$array['discount_percent']) : '';
        isset($array['discount_amount']) ? $model->setDiscount_amount((float)$array['discount_amount']) : '';
        isset($array['url_key']) ? $model->setUrl_key((string)$array['url_key']) : '';
        isset($array['password']) ? $model->setPassword((string)$array['password']) : '';
        isset($array['notes']) ? $model->setNotes((string)$array['notes']) : '';
        if ($model->isNewRecord()) {
            $model->setInv_id(0);
            $model->setSo_id(0);
            isset($array['number']) ? $model->setNumber((string)$array['number']) : '';
            $model->setStatus_id(1);
            $model->setUser($user);
            $model->setUser_id((int)$user->getId());
            $model->setUrl_key(Random::string(32));
            $model->setDate_created(new \DateTimeImmutable('now'));
            $model->setDate_expires($s);
            $model->setDiscount_amount(0.00);
        }
        // Regenerate quote numbers if the setting is changed
        if (!$model->isNewRecord() && $s->getSetting('generate_quote_number_for_draft') === '1') {
            null !== $array['group_id'] ? $model->setNumber((string)$gR->generate_number((int)$array['group_id'], true)) : '';
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
        if (null !== $quote_id) {
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
    private function flash(string $level, string $message): Flash
    {
        $flash = new Flash($this->session);
        $flash->set($level, $message);
        return $flash;
    }
}
