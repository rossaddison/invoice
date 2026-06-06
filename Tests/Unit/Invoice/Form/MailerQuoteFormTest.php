<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Invoice\Quote\MailerQuoteForm;
use PHPUnit\Framework\TestCase;

class MailerQuoteFormTest extends TestCase
{
    public function testGetFormNameReturnsClassName(): void
    {
        $this->assertSame('MailerQuoteForm', (new MailerQuoteForm())->getFormName());
    }

    public function testGetRulesContainsEmailAndRequiredFields(): void
    {
        $rules = (new MailerQuoteForm())->getRules();

        $this->assertArrayHasKey('to_email', $rules);
        $this->assertArrayHasKey('from_name', $rules);
        $this->assertArrayHasKey('from_email', $rules);
        $this->assertArrayHasKey('subject', $rules);
        $this->assertArrayHasKey('body', $rules);
        $this->assertCount(5, $rules);
    }

    public function testNewInstanceIsIsolated(): void
    {
        $this->assertNotSame(new MailerQuoteForm(), new MailerQuoteForm());
    }
}
