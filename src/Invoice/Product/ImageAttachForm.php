<?php

declare(strict_types=1);

namespace App\Invoice\Product;

use Yiisoft\FormModel\FormModel;

final class ImageAttachForm extends FormModel
{
    private ?array $attachFile = null;

    /**
     * @psalm-return 'ImageAttachForm'
     */
    #[\Override]
    public function getFormName(): string
    {
        return 'ImageAttachForm';
    }
}
