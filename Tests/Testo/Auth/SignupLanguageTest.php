<?php

declare(strict_types=1);

namespace Tests\Testo\Auth;

use App\Auth\SignupLanguage;
use Testo\Assert;
use Testo\Test;

/**
 * The confirmation link's `language` segment can be rewritten by whoever holds the link, so it is
 * never stored as given.
 *
 * @see SignupLanguage
 */
#[Test]
final class SignupLanguageTest
{
    private const array KNOWN = ['en' => 'English', 'de' => 'German', 'fr' => 'French'];

    public function theLanguageRecordedAtSignupWins(): void
    {
        Assert::same(SignupLanguage::resolve('French', 'German', self::KNOWN), 'German');
    }

    public function aKnownLanguageInTheLinkIsUsedWhenNothingWasRecorded(): void
    {
        Assert::same(SignupLanguage::resolve('French', null, self::KNOWN), 'French');
        Assert::same(SignupLanguage::resolve('French', '', self::KNOWN), 'French');
    }

    public function anInventedLanguageInTheLinkFallsBackToEnglish(): void
    {
        foreach (['<script>alert(1)</script>', 'Klingon', '', str_repeat('x', 5000), "English\n", 'english'] as $fromLink) {
            Assert::same(SignupLanguage::resolve($fromLink, null, self::KNOWN), SignupLanguage::FALLBACK);
        }
    }

    public function aRecordedValueThatIsNotAKnownLanguageIsIgnoredToo(): void
    {
        Assert::same(SignupLanguage::resolve('German', 'Klingon', self::KNOWN), 'German');
        Assert::same(SignupLanguage::resolve('Klingon', 'Klingon', self::KNOWN), SignupLanguage::FALLBACK);
    }

    public function nonStringConfigurationEntriesAreIgnored(): void
    {
        Assert::same(SignupLanguage::resolve('German', null, ['en' => 'English', 'x' => null, 'y' => 5]), SignupLanguage::FALLBACK);
    }
}
