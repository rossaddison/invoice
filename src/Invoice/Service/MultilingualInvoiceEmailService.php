<?php

declare(strict_types=1);

/**
 * Multilingual Invoice Email Service
 */

namespace App\Invoice\Service;

use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Mailer\MailerInterface;
use Yiisoft\Mailer\MessageInterface;
use NumberFormatter;
use IntlDateFormatter;
use DateTime;
use DateTimeInterface;

final class MultilingualInvoiceEmailService
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly MailerInterface $mailer
    ) {}

    /**
     * Send invoice with automatic language detection
     * @param array<string, mixed> $invoice
     * @param array<string, mixed> $client
     */
    public function sendInvoice(array $invoice, array $client, string $action = 'created'): ?bool
    {
        // Validate required fields
        if (!isset($client['email']) || !is_string($client['email'])) {
            return false;
        }
        
        if (!isset($invoice['number'])) {
            return false;
        }

        // Detect client language preference
        $clientLanguage = $this->detectClientLanguage($client);
        
        // Switch translator to client's language
        $originalLocale = $this->translator->getLocale();
        $translatorWithLocale = $this->translator->withLocale($clientLanguage);
        
        try {
            $subject = $translatorWithLocale->translate("email.invoice.{$action}.subject", [
                'invoice_number' => (string) $invoice['number'],
                'company_name' => (string) ($invoice['company_name'] ?? '')
            ]);
            
            $body = $this->renderInvoiceEmailBody($invoice, $client, $action, $translatorWithLocale);
            $plainText = $this->renderPlainTextVersion($invoice, $client, $action, $translatorWithLocale);
            
            // Create message using Yii mailer pattern
            $message = new \Yiisoft\Mailer\Message(
                charset: 'utf-8',
                subject: $subject,
                date: new \DateTimeImmutable('now'),
                to: [$client['email']],
                htmlBody: $body,
                textBody: $plainText,
            );
                
            $this->mailer->send($message);
            return true; // Mailer send() returns void, so success means true
            
        } finally {
            // Restore original locale - though withLocale should return new instance
            // This is defensive programming
        }
    }

    /**
     * Advanced language detection from client data
     * @param array<string, mixed> $client
     */
    private function detectClientLanguage(array $client): string
    {
        // 1. Check client's explicit language preference
        if (isset($client['language']) && is_string($client['language']) && !empty($client['language'])) {
            return $client['language'];
        }
        
        // 2. Detect from country/region
        if (isset($client['country']) && is_string($client['country']) && !empty($client['country'])) {
            /** @var array<string, string> $countryToLanguage */
            $countryToLanguage = [
                'DE' => 'de',
                'FR' => 'fr', 
                'ES' => 'es',
                'IT' => 'it',
                'JP' => 'ja',
                'CN' => 'zh-CN',
                'BR' => 'pt-BR',
                'RU' => 'ru',
                'PL' => 'pl',
                'NL' => 'nl'
            ];
            
            if (isset($countryToLanguage[$client['country']])) {
                return $countryToLanguage[$client['country']];
            }
        }
        
        // 3. Default to application default
        return 'en';
    }

    /**
     * Render rich HTML email body
     * @param array<string, mixed> $invoice
     * @param array<string, mixed> $client
     */
    private function renderInvoiceEmailBody(array $invoice, array $client, string $action, TranslatorInterface $translator): string
    {
        $template = $this->getEmailTemplate($action);
        
        return $translator->translate($template, [
            'client_name' => (string) ($client['name'] ?? ''),
            'invoice_number' => (string) ($invoice['number'] ?? ''),
            'invoice_date' => $this->formatDate((string) ($invoice['date'] ?? date('Y-m-d')), $translator),
            'due_date' => $this->formatDate((string) ($invoice['due_date'] ?? date('Y-m-d')), $translator),
            'amount' => $this->formatCurrency(
                (float) ($invoice['total'] ?? 0.0), 
                (string) ($invoice['currency'] ?? 'USD'),
                $translator
            ),
            'company_name' => (string) ($invoice['company_name'] ?? ''),
            'payment_terms' => $translator->translate('payment.terms.' . (string) ($invoice['payment_terms'] ?? 'net30')),
            'view_link' => $this->generateInvoiceLink((int) ($invoice['id'] ?? 0)),
            'payment_link' => $this->generatePaymentLink((int) ($invoice['id'] ?? 0))
        ]);
    }

    /**
     * Render plain text version
     * @param array<string, mixed> $invoice
     * @param array<string, mixed> $client
     */
    private function renderPlainTextVersion(array $invoice, array $client, string $action, TranslatorInterface $translator): string
    {
        return $translator->translate("email.invoice.{$action}.plain", [
            'client_name' => (string) ($client['name'] ?? ''),
            'invoice_number' => (string) ($invoice['number'] ?? ''),
            'amount' => $this->formatCurrency(
                (float) ($invoice['total'] ?? 0.0), 
                (string) ($invoice['currency'] ?? 'USD'),
                $translator
            )
        ]);
    }

    /**
     * Context-aware email templates
     */
    private function getEmailTemplate(string $action): string
    {
        /** @var array<string, string> $templates */
        $templates = [
            'created' => 'email.invoice.created.body',
            'sent' => 'email.invoice.sent.body', 
            'reminder' => 'email.invoice.reminder.body',
            'overdue' => 'email.invoice.overdue.body',
            'paid' => 'email.invoice.paid.body',
            'cancelled' => 'email.invoice.cancelled.body'
        ];
        
        return $templates[$action] ?? 'email.invoice.generic.body';
    }

    /**
     * Currency formatting with proper locale
     */
    private function formatCurrency(float $amount, string $currency, TranslatorInterface $translator): string
    {
        $locale = $translator->getLocale();
        $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        $formatted = $formatter->formatCurrency($amount, $currency);
        
        return $formatted !== false ? $formatted : "{$amount} {$currency}";
    }

    /**
     * Date formatting with locale awareness
     */
    private function formatDate(string $date, TranslatorInterface $translator): string
    {
        try {
            $dateTime = new DateTime($date);
        } catch (\Exception) {
            return $date; // Return original if parsing fails
        }
        
        $locale = $translator->getLocale();
        
        $formatter = new IntlDateFormatter(
            $locale,
            IntlDateFormatter::LONG,
            IntlDateFormatter::NONE
        );
        
        $formatted = $formatter->format($dateTime);
        return $formatted !== false ? $formatted : $dateTime->format('Y-m-d');
    }

    /**
     * Generate invoice view link
     */
    private function generateInvoiceLink(int $invoiceId): string
    {
        // This should use proper URL generator from Yii
        return "/invoice/view/{$invoiceId}";
    }

    /**
     * Generate payment link
     */
    private function generatePaymentLink(int $invoiceId): string
    {
        // This should use proper URL generator from Yii
        return "/invoice/pay/{$invoiceId}";
    }
}