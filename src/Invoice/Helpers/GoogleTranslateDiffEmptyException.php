<?php

declare(strict_types=1);

namespace App\Invoice\Helpers;

use Yiisoft\FriendlyException\FriendlyExceptionInterface;

class GoogleTranslateDiffEmptyException extends \RuntimeException implements FriendlyExceptionInterface
{
    /**
     * @psalm-return 'The diff array that has been built is empty. The existing target locale app.php already has all the necessary keys of the source app.php.'
     */
    #[\Override]
    public function getName(): string
    {
        return 'The diff array that has been built is empty. The existing target locale app.php already has all the necessary keys of the source app.php.';
    }

    #[\Override]
    public function getSolution(): string
    {
        return <<<'SOLUTION'
               There is no need to translate
            SOLUTION;
    }
}
