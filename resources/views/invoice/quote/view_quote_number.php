<?php

declare(strict_types=1);

/**
 * @var App\Infrastructure\Persistence\Quote\Quote $quote
 * @var Yiisoft\Translator\TranslatorInterface $translator
 */

echo $translator->translate('quote') . ' ';
$number = $quote->getNumber();
$id = $quote->reqId();
if (null !== $number) {
    echo($number ? '#' . $number : $id);
}
?>
