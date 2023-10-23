<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use Yiisoft\Form\FormModel;

final class InvAttachmentsForm extends FormModel
{
    private ?array $attachFile = null; 

    /**
     * @return string
     *
     * @psalm-return 'InvAttachmentsForm'
     */
    public function getFormName(): string
    {
        return 'InvAttachmentsForm';
    }
}
