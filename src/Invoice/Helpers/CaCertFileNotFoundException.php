<?php

declare(strict_types=1);

namespace App\Invoice\Helpers;

use Yiisoft\FriendlyException\FriendlyExceptionInterface;

class CaCertFileNotFoundException extends \RuntimeException implements FriendlyExceptionInterface
{
    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Your SSL certificate cacert.pem for this version of PHP '.  PHP_VERSION .' from https://curl.haxx.se/ca/cacert.pem  does not exist under the server php directory  ...bin/php/'.PHP_VERSION;
    }
    
    /**
     * @return string
     *
     * @psalm-return string
     */
    public function getSolution(): string
    {
        return <<<'SOLUTION'
            Download from this website and just a reminder,
            don't forget to also:
            1. Create a project at https://cloud.google.com/resource-manager/docs/creating-managing-projects    
            2. Create a service account for your project
            3. Goto https://console.cloud.google.com/welcome?project={your_project_name}
            4. Quick access to my billing account so you can enable the Cloud Translation API. You will be charged a zero amount. Billing is dependent upon usage amount. 
            5. Quick access to IAM and Admin ... Keys ... Add Key ... Download the Json file to src/Invoice/Google_translate_unique_folder.
            SOLUTION;
    }
}