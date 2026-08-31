<?php

declare(strict_types=1);

namespace Tests\Testo\Widget;

use App\Invoice\Client\ClientForm;
use App\Widget\ClientFormFields;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Covers clientTelephoneField()'s value shown for client_mobile -- the only
 * one of client_mobile/client_phone/client_fax with a
 * #[Regex('/^\+[1-9]\d{1,14}$/')] rule (ClientForm.php), so a stored
 * legacy local-format number (no leading '+') silently fails validation on
 * every save with no visual cue why. Found 2026-08-31 via a real client
 * whose stored '07726232648' blocked an unrelated field (postaladdress_id)
 * from ever saving, since form validation fails as a whole.
 */
#[Test]
final class ClientFormFieldsTest
{
    private function fields(): ClientFormFields
    {
        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $e = $translator->shouldReceive('translate');
        $e->andReturnUsing(static fn (string $key): string => $key);

        return new ClientFormFields($translator);
    }

    private function valueAttribute(string $html): ?string
    {
        // The Html builder renders a truly empty value as the bare
        // attribute `value` (no `="..."` at all), not `value=""` --
        // matched here too, as an empty string, rather than treated as
        // "attribute absent".
        if (preg_match('/\bvalue="([^"]*)"/', $html, $m) === 1) {
            return $m[1];
        }
        return preg_match('/\bvalue\b(?!=)/', $html) === 1 ? '' : null;
    }

    public function prependsAPlusToANonEmptyMobileValueMissingOne(): void
    {
        $form = new ClientForm();
        $form->client_mobile = '07726232648';

        $html = $this->fields()->clientTelephoneField($form, 'client_mobile', 'mobile');

        Assert::same('+07726232648', $this->valueAttribute($html));
    }

    public function leavesAnAlreadyPlusPrefixedMobileValueUnchanged(): void
    {
        $form = new ClientForm();
        $form->client_mobile = '+447726232648';

        $html = $this->fields()->clientTelephoneField($form, 'client_mobile', 'mobile');

        Assert::same('+447726232648', $this->valueAttribute($html));
    }

    public function leavesABlankMobileValueBlankSoThePlaceholderStillShows(): void
    {
        // Not '+' -- a lone '+' with nothing after it would itself fail
        // the Regex rule on submit if left untouched, turning an
        // optional field into a hard validation failure. An empty
        // `value` already lets the "+447700900000" placeholder show.
        $form = new ClientForm();
        $form->client_mobile = '';

        $html = $this->fields()->clientTelephoneField($form, 'client_mobile', 'mobile');

        Assert::same('', $this->valueAttribute($html));
    }

    public function doesNotPrependAPlusToClientPhoneOrFax(): void
    {
        // Only client_mobile carries the E.164 Regex rule -- client_phone
        // and client_fax have no such format requirement.
        $form = new ClientForm();
        $form->client_phone = '01234567890';

        $html = $this->fields()->clientTelephoneField($form, 'client_phone', 'phone');

        Assert::same('01234567890', $this->valueAttribute($html));
    }
}
