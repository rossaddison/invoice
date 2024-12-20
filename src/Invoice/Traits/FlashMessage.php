<?php

declare(strict_types=1);

namespace App\Invoice\Traits;

use Yiisoft\Session\Flash\Flash;

trait FlashMessage
{
    /**
     * @param string $level
     * @param string $message
     * @return Flash|null
     */
    private function flashMessage(string $level, string $message): Flash|null
    {
        if (strlen($message) > 0) {
            $this->flash->add($level, $message, true);
            return $this->flash;
        }
        return null;
    }
}
