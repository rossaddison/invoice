<?php

declare(strict_types=1);

/**
 * Enhanced Invoice Error Handler with Full Translation Support
 */

namespace App\Invoice\Handler;

use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Session\Flash\Flash;
use NumberFormatter;
use IntlDateFormatter;
use DateTimeInterface;

final class TranslatedErrorHandler
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly Flash $flash
    ) {}

    /**
     * Add validation errors with full translation support
     * @param array<string, array<int, string>> $errors
     */
    public function addValidationErrors(array $errors, string $context = 'invoice'): void
    {
        foreach ($errors as $field => $fieldErrors) {
            // Types are guaranteed by docblock - no runtime checks needed
            foreach ($fieldErrors as $error) {
                $translatedError = $this->translateValidationError($error, $field, $context);
                $this->flash->add('danger', $translatedError);
            }
        }
    }

    /**
     * Translate validation errors with context awareness
     */
    private function translateValidationError(string $error, string $field, string $context): string
    {
        // Try specific context first
        $key = "validation.{$context}.{$field}.{$error}";
        $translated = $this->translator->translate($key);
        
        if ($translated !== $key) {
            return $translated;
        }
        
        // Fallback to general validation messages
        $generalKey = "validation.{$field}.{$error}";
        $generalTranslated = $this->translator->translate($generalKey);
        
        if ($generalTranslated !== $generalKey) {
            return $generalTranslated;
        }
        
        // Final fallback to generic messages
        return $this->translator->translate("validation.{$error}", [
            'field' => $this->translator->translate("field.{$field}")
        ]);
    }

    /**
     * Business logic errors with dynamic parameters
     * @param array<string, mixed> $params
     */
    public function addBusinessError(string $errorKey, array $params = [], string $type = 'danger'): void
    {
        $message = $this->translator->translate("business.error.{$errorKey}", $params);
        $this->flash->add($type, $message);
    }

    /**
     * Success messages with context
     * @param array<string, mixed> $params
     */
    public function addSuccess(string $action, string $entity, array $params = []): void
    {
        $message = $this->translator->translate("success.{$action}.{$entity}", $params);
        $this->flash->add('success', $message);
    }

    /**
     * Currency and number formatting with locale awareness
     */
    public function formatCurrency(float $amount, string $currency = 'USD'): string
    {
        $locale = $this->translator->getLocale();
        
        $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        $formatted = $formatter->formatCurrency($amount, $currency);
        
        return $formatted !== false ? $formatted : "{$amount} {$currency}";
    }

    /**
     * Date formatting with translation
     */
    public function formatDate(DateTimeInterface $date, string $format = 'medium'): string
    {
        $locale = $this->translator->getLocale();
        
        $formatter = new IntlDateFormatter(
            $locale,
            IntlDateFormatter::MEDIUM,
            IntlDateFormatter::NONE
        );
        
        $formatted = $formatter->format($date);
        return $formatted !== false ? $formatted : $date->format('Y-m-d');
    }
}