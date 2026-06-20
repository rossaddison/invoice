<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrder\Trait;

use Yiisoft\Translator\TranslatorInterface as Translator;

trait SalesOrderStatusTrait
{
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
            ],
            '1' => [
                'label' => $translator->translate('draft'),
                'class' => 'secondary',
                'href' => 1,
            ],
            '2' => [
                // Terms Agreement required
                'label' => $translator->translate('salesorder.sent.to.customer'),
                'class' => 'primary',
                'href' => 2,
            ],
            '3' => [
                // Client Confirmed Terms
                'label' => $translator->translate('salesorder.client.confirmed.terms'),
                'class' => 'warning',
                'href' => 3,
            ],
            '4' => [
                // Assembled/Packaged/Prepared
                'label' => $translator->translate('salesorder.assembled.packaged.prepared'),
                'class' => 'info',
                'href' => 4,
            ],
            '5' => [
                // Goods/Services Delivered
                'label' => $translator->translate('salesorder.goods.services.delivered'),
                'class' => 'success',
                'href' => 5,
            ],
            '6' => [
                // Customer Confirmed Delivery
                'label' => $translator->translate('salesorder.goods.services.confirmed'),
                'class' => 'success',
                'href' => 6,
            ],
            '7' => [
                'label' => $translator->translate('salesorder.invoice.generate'),
                'class' => 'warning',
                'href' => 7,
            ],
            '8' => [
                'label' => $translator->translate('salesorder.invoice.generated'),
                'class' => 'success',
                'href' => 8,
            ],
            '9' => [
                'label' => $translator->translate('rejected'),
                'class' => 'danger',
                'href' => 9,
            ],
            '10' => [
                'label' => $translator->translate('canceled'),
                'class' => 'secondary',
                'href' => 10,
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
}
