<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Invoice\Inv\InvAttachmentsForm;
use PHPUnit\Framework\TestCase;

class InvAttachmentsFormTest extends TestCase
{
    public function testGetFormNameReturnsClassName(): void
    {
        $this->assertSame('InvAttachmentsForm', (new InvAttachmentsForm())->getFormName());
    }

    public function testNewInstanceIsIsolated(): void
    {
        $this->assertNotSame(new InvAttachmentsForm(), new InvAttachmentsForm());
    }
}
