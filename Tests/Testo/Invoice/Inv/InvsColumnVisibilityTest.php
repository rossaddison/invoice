<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Invoice\Inv\Widget\InvsColumnBuilder;
use ReflectionClass;
use Testo\Assert;
use Testo\Test;

/**
 * Covers InvsColumnVisibilityTrait::isColumnHidden() — backs the HomeCare
 * "hidden columns" Settings checkboxes. Must be a no-op whenever HomeCare
 * is off, regardless of what's in the hidden-keys list, so the general
 * desktop grid for non-HomeCare businesses is never affected.
 *
 * InvsColumnBuilder is built via newInstanceWithoutConstructor() (its real
 * constructor needs a full DI dependency graph) since isColumnHidden()
 * doesn't touch any of those dependencies — same technique
 * InvsListWidgetTest.php already uses to reach InvRepository.
 */
#[Test]
final class InvsColumnVisibilityTest
{
    private function isColumnHidden(string $key, bool $homeCareEnabled, array $hiddenKeys): bool
    {
        $reflectionClass = new ReflectionClass(InvsColumnBuilder::class);
        $builder = $reflectionClass->newInstanceWithoutConstructor();

        $method = $reflectionClass->getMethod('isColumnHidden');

        /** @var bool */
        return $method->invoke($builder, $key, $homeCareEnabled, $hiddenKeys);
    }

    public function homeCareOffNeverHidesAnyColumnEvenIfListed(): void
    {
        Assert::false($this->isColumnHidden('client_group', false, ['client_group']));
    }

    public function homeCareOnHidesAListedColumn(): void
    {
        Assert::true($this->isColumnHidden('client_group', true, ['client_group']));
    }

    public function homeCareOnDoesNotHideAnUnlistedColumn(): void
    {
        Assert::false($this->isColumnHidden('client_group', true, ['time_created']));
    }

    public function emptyHiddenListHidesNothing(): void
    {
        Assert::false($this->isColumnHidden('client_group', true, []));
    }
}
