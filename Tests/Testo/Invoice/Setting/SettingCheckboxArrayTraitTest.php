<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Setting;

use App\Invoice\Setting\Trait\SettingCheckboxArrayTrait;
use Mockery as m;
use Psr\Http\Message\ServerRequestInterface as Request;
use Testo\Assert;
use Testo\Test;

/**
 * The settings posted from the settings form: the checkboxes[] ones (hidden HomeCare columns, the
 * services offered) are joined into comma-separated strings, everything else is left as it was.
 */
#[Test]
final class SettingCheckboxArrayTraitTest
{
    private function harness(): SettingCheckboxArrayTraitHarness
    {
        return new SettingCheckboxArrayTraitHarness();
    }

    /**
     * @param array<string, mixed>|null $body
     */
    private function request(?array $body): Request&m\MockInterface
    {
        /** @var Request&m\MockInterface $request */
        $request = m::mock(Request::class);
        $request->shouldReceive('getParsedBody')->andReturn($body);
        return $request;
    }

    public function theCheckboxListsAreJoinedAndOtherSettingsAreLeftAlone(): void
    {
        $posted = $this->harness()->posted($this->request(['settings' => [
            'services_offered' => ['', 'bin_cleaning', 'grass_mowing'],
            'homecare_hidden_inv_columns' => ['workflow_type', 'credit_note'],
            'homecare_hidden_inv_guest_columns' => [''],
            'bootstrap5_offcanvas_placement' => 'top',
        ]]));

        Assert::same($posted, [
            'services_offered' => ',bin_cleaning,grass_mowing',
            'homecare_hidden_inv_columns' => 'workflow_type,credit_note',
            'homecare_hidden_inv_guest_columns' => '',
            'bootstrap5_offcanvas_placement' => 'top',
        ]);
    }

    public function aSettingThatIsAlreadyAStringIsNotTouched(): void
    {
        $posted = $this->harness()->posted($this->request(['settings' => ['services_offered' => 'bin_cleaning']]));

        Assert::same($posted, ['services_offered' => 'bin_cleaning']);
    }

    public function aPostWithoutAParsedBodyCarriesNoSettings(): void
    {
        Assert::same($this->harness()->posted($this->request(null)), null);
    }
}

/**
 * Makes the trait's private method callable.
 */
final class SettingCheckboxArrayTraitHarness
{
    use SettingCheckboxArrayTrait;

    /** @return array<string, string>|null */
    public function posted(Request $request): ?array
    {
        return $this->postedSettingsWithCheckboxesJoined($request);
    }
}
