<?php

declare(strict_types=1);

namespace App\Invoice\Peppol;

use App\Invoice\Helpers\Peppol\PeppolValidator;
use Yiisoft\Html\Html;
use Yiisoft\Translator\TranslatorInterface;

/**
 * The gate in front of a Peppol send: the generated UBL goes through the same PeppolValidator the XML preview uses
 * (the EN 16931 Schematron when resources/peppol/PEPPOL-EN16931-UBL.sch is present, plus the arithmetic checks),
 * and a document with errors is not sent.
 *
 * Before this, inv/peppolSend generated the UBL and handed it straight to the access point: the validator only ran
 * in the preview, so an invoice the Schematron rejects could still be sent.
 *
 * Only errors block. Warnings never do.
 *
 * It is on unless the peppol_validate_before_send setting is exactly '0', so an install that has never saved the
 * setting is protected, and an operator can still switch it off if the validator ever wrongly rejects a valid
 * invoice.
 */
final readonly class PeppolPreSendValidation
{
    public const string SETTING_KEY = 'peppol_validate_before_send';

    /** How many errors are listed in the message; the rest are only counted. */
    public const int LISTED_ERRORS = 10;

    public function __construct(private TranslatorInterface $translator)
    {
    }

    public static function isEnabled(?string $setting): bool
    {
        return $setting !== '0';
    }

    /**
     * @return list<array{rule: string, text: string}> empty when the document is valid
     */
    public function errors(string $ublXml): array
    {
        $validator = new PeppolValidator($this->translator);
        if (!$validator->loadXML($ublXml)) {
            return $this->normalised($validator->getErrors());
        }
        $validator->validate();
        return $this->normalised($validator->getErrors());
    }

    /**
     * The errors as an HTML list for a flash message: the first LISTED_ERRORS, each with its rule id, then a count of
     * the rest. Everything from the document is encoded, so the message cannot inject markup.
     *
     * @param list<array{rule: string, text: string}> $errors
     */
    public function listHtml(array $errors): string
    {
        $items = '';
        foreach (array_slice($errors, 0, self::LISTED_ERRORS) as $error) {
            $items .= '<li>'
                . ($error['rule'] === '' ? '' : '<code>' . Html::encode($error['rule']) . '</code> ')
                . Html::encode($error['text'])
                . '</li>';
        }
        $more = count($errors) - self::LISTED_ERRORS;
        if ($more > 0) {
            $items .= '<li>' . Html::encode($this->translator->translate('peppol.send.blocked.more', ['count' => $more])) . '</li>';
        }
        return '<ul>' . $items . '</ul>';
    }

    /**
     * @param array<array-key, mixed> $errors
     * @return list<array{rule: string, text: string}>
     */
    private function normalised(array $errors): array
    {
        $result = [];
        /** @var mixed $error */
        foreach ($errors as $error) {
            $error = (array) $error;
            $result[] = [
                // The validator pads the rule id and adds a colon ("BR-CO-14          :"); show just the id.
                'rule' => trim((string) ($error['rule'] ?? ''), ' :'),
                'text' => (string) ($error['text'] ?? ''),
            ];
        }
        return $result;
    }
}
