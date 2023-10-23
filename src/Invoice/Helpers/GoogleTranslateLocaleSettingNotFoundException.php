<?php

declare(strict_types=1);

namespace App\Invoice\Helpers;

use Yiisoft\FriendlyException\FriendlyExceptionInterface;

class GoogleTranslateLocaleSettingNotFoundException extends \RuntimeException implements FriendlyExceptionInterface
{
    /**
     * @return string
     *
     * @psalm-return 'Settings...View...Google Translate...Locale has not been chosen.'
     */
    public function getName(): string
    {
        return 'Settings...View...Google Translate...Locale has not been chosen.';
    }
    
    /**
     * @return string
     *
     * @psalm-return '    Please select a locale. The translation to the eg. ip_lang can then start.'
     */
    public function getSolution(): ?string
    {
        return <<<'SOLUTION'
                Please select a locale. The translation to the eg. ip_lang can then start.
            SOLUTION;
    }
}