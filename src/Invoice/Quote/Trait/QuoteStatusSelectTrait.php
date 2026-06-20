<?php

declare(strict_types=1);

namespace App\Invoice\Quote\Trait;

use Cycle\Database\Injection\Parameter;
use Cycle\ORM\Select;

trait QuoteStatusSelectTrait
{
    public function isDraft(): Select
    {
        return $this->select()->where(['status_id' => 1]);
    }

    public function isSent(): Select
    {
        return $this->select()->where(['status_id' => 2]);
    }

    public function isViewed(): Select
    {
        return $this->select()->where(['status_id' => 3]);
    }

    public function isApproved(): Select
    {
        return $this->select()->where(['status_id' => 4]);
    }

    public function isRejected(): Select
    {
        return $this->select()->where(['status_id' => 5]);
    }

    public function isCanceled(): Select
    {
        return $this->select()->where(['status_id' => 6]);
    }

    /**
     * Used by guest; includes only sent and viewed
     */
    public function isOpen(): Select
    {
        return $this->select()->where(['status_id' => ['in' => new Parameter([2,3])]]);
    }
}
