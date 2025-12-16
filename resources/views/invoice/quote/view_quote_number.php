<?php

declare(strict_types=1);

/**
 * @var App\Invoice\Entity\Quote $quote
 * @var Yiisoft\Translator\TranslatorInterface $translator
 */ 

echo $translator->translate('quote') . ' ';
$number = $quote->getNumber();
$id = $quote->getId();
if (null !== ($number) && null !== $id) {
    echo($number ? '#' . $number : $id);
}
?>
