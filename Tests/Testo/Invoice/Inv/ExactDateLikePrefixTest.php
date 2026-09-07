<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Invoice\Inv\InvRepository;
use ReflectionClass;
use Testo\Assert;
use Testo\Test;

/**
 * Covers InvCombinedFilterTrait::exactDateLikePrefix() -- the rollover-date
 * rejection shared (PR #1252, deduplicating what
 * InvGuestTrait::filterGuestDateCreatedExact() used to copy verbatim)
 * between inv/index's staff-side exact-date filter and inv/guest/calendar's
 * own. A pure string-in/string-out method with no Select query of its own,
 * so it's directly testable via reflection on InvRepository (which
 * composes both traits) without needing to mock Cycle ORM at all.
 */
#[Test]
final class ExactDateLikePrefixTest
{
    private function invoke(string $dateString): string
    {
        $reflectionClass = new ReflectionClass(InvRepository::class);
        $repository = $reflectionClass->newInstanceWithoutConstructor();
        $reflectionMethod = $reflectionClass->getMethod('exactDateLikePrefix');
        /** @var string */
        return $reflectionMethod->invoke($repository, $dateString);
    }

    public function aValidDateProducesItsLikePrefix(): void
    {
        Assert::same('2026-09-15%', $this->invoke('2026-09-15'));
    }

    public function aRolloverDateIsRejectedRatherThanSilentlyNormalized(): void
    {
        // createFromFormat('Y-m-d', '2026-02-29') doesn't fail -- 2026
        // isn't a leap year, but it silently returns 2026-03-01 instead,
        // only surfacing via getLastErrors()'s warning_count. Confirmed
        // live during PR #1248's CodeRabbit pass.
        Assert::same('', $this->invoke('2026-02-29'));
    }

    public function aLeapYearFebruaryTwentyNinthIsAccepted(): void
    {
        Assert::same('2028-02-29%', $this->invoke('2028-02-29'));
    }

    public function completelyUnparseableInputProducesNoMatch(): void
    {
        Assert::same('', $this->invoke('not-a-date'));
    }

    public function anEmptyStringProducesNoMatch(): void
    {
        Assert::same('', $this->invoke(''));
    }
}
