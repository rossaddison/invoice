<?php

declare(strict_types=1);

namespace Tests\Unit\Libraries;

use App\Invoice\Libraries\Crypt;
use Codeception\Test\Unit;

class CryptTest extends Unit
{
    private Crypt $crypt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crypt = new Crypt();
    }
}
