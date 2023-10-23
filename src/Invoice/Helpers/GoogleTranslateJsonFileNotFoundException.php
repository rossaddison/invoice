<?php

declare(strict_types=1);

namespace App\Invoice\Helpers;

use Yiisoft\FriendlyException\FriendlyExceptionInterface;

class GoogleTranslateJsonFileNotFoundException extends \RuntimeException implements FriendlyExceptionInterface
{
    /**
     * @return string
     *
     * @psalm-return 'The Json file that you downloaded at https://console.cloud.google.com/iam-admin/serviceaccounts/details/{unique_project_id}/keys?project={your_project_name} cannot be found in .../src/Invoice/Google_translate_unique_folder.'
     */
    public function getName(): string
    {
        return 'The Json file that you downloaded at https://console.cloud.google.com/iam-admin/serviceaccounts/details/{unique_project_id}/keys?project={your_project_name} cannot be found in .../src/Invoice/Google_translate_unique_folder.';
    }
    
    /**
     * @return string
     *
     * @psalm-return '    Please try again'
     */
    public function getSolution(): ?string
    {
        return <<<'SOLUTION'
                Please try again
            SOLUTION;
    }
}