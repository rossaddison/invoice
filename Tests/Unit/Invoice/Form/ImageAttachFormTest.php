<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Invoice\Product\ImageAttachForm;
use PHPUnit\Framework\TestCase;

class ImageAttachFormTest extends TestCase
{
    public function testGetFormNameReturnsClassName(): void
    {
        $this->assertSame('ImageAttachForm', (new ImageAttachForm())->getFormName());
    }

    public function testNewInstanceIsIsolated(): void
    {
        $this->assertNotSame(new ImageAttachForm(), new ImageAttachForm());
    }
}
