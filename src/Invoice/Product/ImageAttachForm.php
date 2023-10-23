<?php

declare(strict_types=1);

namespace App\Invoice\Product;

use Yiisoft\Form\FormModel;

final class ImageAttachForm extends FormModel
{
    private ?array $attachFile = null; 

    /**
     * @return string
     *
     * @psalm-return 'ImageAttachForm'
     */
    public function getFormName(): string
    {
        return 'ImageAttachForm';
    }
}
