<?php

declare(strict_types=1);

namespace App\Invoice\Quote\Trait;

use Yiisoft\Translator\TranslatorInterface as Translator;

trait QuoteStatusTrait
{
    /**
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
                'label' => $translator->translate('sent'),
                'class' => 'primary',
                'href' => 2,
            ],
            '3' => [
                'label' => $translator->translate('viewed'),
                'class' => 'warning',
                'href' => 3,
            ],
            '4' => [
                'label' => $translator->translate('approved'),
                'class' => 'success',
                'href' => 4,
            ],
            '5' => [
                'label' => $translator->translate('rejected'),
                'class' => 'danger',
                'href' => 5,
            ],
            '6' => [
                'label' => $translator->translate('canceled'),
                'class' => 'secondary',
                'href' => 6,
            ],
        ];
    }

    public function getSpecificStatusArrayLabel(string $key): string
    {
        $statuses_array = $this->getStatuses($this->translator);
        /**
         * @var array $statuses_array[$key]
         * @var string $statuses_array[$key]['label']
         */
        return $statuses_array[$key]['label'];
    }

    public function getSpecificStatusArrayClass(string $key): string
    {
        $statuses_array = $this->getStatuses($this->translator);
        /**
         * @var array $statuses_array[$key]
         * @var string $statuses_array[$key]['class']
         */
        return $statuses_array[$key]['class'];
    }
}
