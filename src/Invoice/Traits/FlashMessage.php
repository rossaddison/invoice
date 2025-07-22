<?php

declare(strict_types=1);

namespace App\Invoice\Traits;

use Yiisoft\Session\Flash\Flash;

trait FlashMessage
{
    protected function flashMessage(string $level, string $message): ?Flash
    {
        if ((strlen($message) > 0) && !$this->flash->has($message)) {
            $this->flash->add($level, $message, true);

            return $this->flash;
        }

        return null;
    }
}
