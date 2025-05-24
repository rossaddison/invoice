<?php

declare(strict_types=1);

namespace App\Backend\Exception;

use Yiisoft\FriendlyException\FriendlyExceptionInterface;

final class HeaderArrayNotFoundException extends \RuntimeException implements FriendlyExceptionInterface
{
    /**
     * @return string
     * @psalm-return 'An empty array has been submitted for headers.'
     */
    #[\Override]
    public function getName(): string
    {
        return 'An empty array has been submitted for headers.';
    }

    /**
     * @return string
     */
    #[\Override]
    public function getSolution(): string
    {
        return <<<'SOLUTION'
                Login to Hmrc Developer Sandbox after having received your One-time 6 digit password (OTP) through Telegram. 
                If you have been returned back to this site by the Hmrc, this will ensure that you have an oauth token from the Hmrc and an OTP which you can use to test api's
            SOLUTION;
    }
}
