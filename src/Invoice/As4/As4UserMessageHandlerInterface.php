<?php

declare(strict_types=1);

namespace App\Invoice\As4;

interface As4UserMessageHandlerInterface
{
    public function handle(As4InboundMessage $message): string;
}
