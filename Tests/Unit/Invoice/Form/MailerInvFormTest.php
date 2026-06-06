<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Invoice\Inv\MailerInvForm;
use PHPUnit\Framework\TestCase;

class MailerInvFormTest extends TestCase
{
    public function testGetFormNameReturnsClassName(): void
    {
        $this->assertSame('MailerInvForm', (new MailerInvForm())->getFormName());
    }

    public function testNewInstanceIsIsolated(): void
    {
        $this->assertNotSame(new MailerInvForm(), new MailerInvForm());
    }
}
