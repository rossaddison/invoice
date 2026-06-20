<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\Setting\SettingRepository as SR;
use Cycle\Database\Injection\Parameter;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Security\Random;
use Yiisoft\Translator\TranslatorInterface as Translator;

trait InvStatusTrait
{
    public function open(): EntityReader
    {
        $query = $this->select()
                      ->where(['status_id' => ['in' => new Parameter([2,3])]])
                      ->where('deleted_at', null);
        return $this->prepareDataReader($query);
    }

    public function openCount(): int
    {
        return $this->select()
                      ->where(['status_id' => ['in' => new Parameter([2,3])]])
                      ->where('deleted_at', null)
                      ->count();
    }

    public function isDraft(): EntityReader
    {
        $query = $this->select()
                      ->where(['status_id' => ['in' => new Parameter([1])]])
                      ->where('deleted_at', null);
        return $this->prepareDataReader($query);
    }

    public function isSent(): EntityReader
    {
        $query = $this->select()
                      ->where(['status_id' => ['in' => new Parameter([2])]])
                      ->where('deleted_at', null);
        return $this->prepareDataReader($query);
    }

    public function isViewed(): EntityReader
    {
        $query = $this->select()
                      ->where(['status_id' => ['in' => new Parameter([3])]])
                      ->where('deleted_at', null);
        return $this->prepareDataReader($query);
    }

    public function isPaid(): EntityReader
    {
        $query = $this->select()
                      ->where(['status_id' => ['in' => new Parameter([4])]])
                      ->where('deleted_at', null);
        return $this->prepareDataReader($query);
    }

    public function isOverdue(): EntityReader
    {
        $query = $this->select()
                      ->where(['status_id' => ['in' => new Parameter([5])]])
                      ->where('deleted_at', null);
        return $this->prepareDataReader($query);
    }

    /**
     * @param Translator $translator
     * @return array
     */
    public function getStatuses(Translator $translator): array
    {
        return [
            '0' => [
                'label' => $translator->translate('all'),
                'class' => 'secondary',
                'href' => 0,
                'emoji' => '🌎 ',
            ],
            '1' => [
                'label' => $translator->translate('draft'),
                'class' => 'secondary',
                'href' => 1,
                'emoji' => '🗋 ',
            ],
            '2' => [
                'label' => $translator->translate('sent'),
                'class' => 'info',
                'href' => 2,
                'emoji' => '📨 ',
            ],
            '3' => [
                'label' => $translator->translate('viewed'),
                'class' => 'info',
                'href' => 3,
                'emoji' => '👀 ',
            ],
            '4' => [
                'label' => $translator->translate('paid'),
                'class' => 'success',
                'href' => 4,
                'emoji' => '😀 ',
            ],
            '5' => [
                'label' => $translator->translate('overdue'),
                'class' => 'warning',
                'href' => 5,
                'emoji' => '🏦 ',
            ],
            '6' => [
                'label' => $translator->translate('unpaid'),
                'class' => 'danger',
                'href' => 6,
                'emoji' => '💸 ',
            ],
            '7' => [
                'label' => $translator->translate('reminder'),
                'class' => 'info',
                'href' => 7,
                'emoji' => '🔔 ',
            ],
            '8' => [
                'label' => $translator->translate('letter'),
                'class' => 'danger',
                'href' => 8,
                'emoji' => '🗎 ',
            ],
            '9' => [
                'label' => $translator->translate('claim'),
                'class' => 'info',
                'href' => 9,
                'emoji' => '🛄 ',
            ],
            '10' => [
                'label' => $translator->translate('judgement'),
                'class' => 'success',
                'href' => 10,
                'emoji' => '🙌 ',
            ],
            '11' => [
                'label' => $translator->translate('enforcement'),
                'class' => 'primary',
                'href' => 11,
                'emoji' => '👮 ',
            ],
            '12' => [
                'label' => $translator->translate('credit.invoice.for.invoice'),
                'class' => 'secondary',
                'href' => 12,
                'emoji' => '🛑️ ',
            ],
            '13' => [
                'label' => $translator->translate('loss'),
                'class' => 'danger',
                'href' => 13,
                'emoji' => '❎ ',
            ],
        ];
    }

    /**
     * @param string $key
     * @return string
     */
    public function getSpecificStatusArrayLabel(string $key): string
    {
        $statuses_array = $this->getStatuses($this->translator);
        /**
         * @var array $statuses_array[$key]
         * @var string $statuses_array[$key]['label']
         */
        return $statuses_array[$key]['label'];
    }

    /**
     * @param int $status
     * @return string
     */
    public function getSpecificStatusArrayClass(int $status): string
    {
        $statuses_array = $this->getStatuses($this->translator);
        /**
         * @var array $statuses_array[$status]
         * @var string $statuses_array[$status]['class']
         */
        return $statuses_array[$status]['class'];
    }

    /**
     * @param int $status
     * @return string
     */
    public function getSpecificStatusArrayEmoji(int $status): string
    {
        $statuses_array = $this->getStatuses($this->translator);
        /**
         * @var array $statuses_array[$status]
         * @var string $statuses_array[$status]['emoji']
         */
        return $statuses_array[$status]['emoji'];
    }

    /**
     * @param string $invoice_date_created
     * @param SR $sR
     * @return string
     */
    public function getDateDue(string $invoice_date_created, SR $sR): string
    {
        $invoice_date_due = new \DateTime($invoice_date_created);
        $invoice_date_due->add(new \DateInterval('P'
                . $sR->getSetting('invoices_due_after') . 'D'));
        return $invoice_date_due->format('Y-m-d');
    }

    public function getUrlKey(): string
    {
        $random = new Random();
        return $random::string(32);
    }

    /**
     * @param int $group_id
     * @param GR $gR
     * @return mixed
     */
    public function getInvNumber(int $group_id, GR $gR): mixed
    {
        return $gR->generateNumber($group_id);
    }
}
